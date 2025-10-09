import { getGlobBaseFromPattern, callback, css, js, shortHash, plugin, callbackAfterBuild, copyGlob, symlink } from "@windwalker-io/fusion-next";
import isGlob from "is-glob";
import micromatch from "micromatch";
import path, { relative, normalize, resolve } from "node:path";
import fs from "node:fs";
import fg from "fast-glob";
import crypto, { randomBytes } from "node:crypto";
import { createRequire } from "node:module";
import fs$1 from "fs-extra";
import { parse } from "node-html-parser";
function loadJson$1(file) {
  if (!fs.existsSync(file)) {
    return null;
  }
  return JSON.parse(fs.readFileSync(file, "utf8"));
}
function containsMiddleGlob(str) {
  return isGlob(removeLastGlob(str));
}
function removeLastGlob(str) {
  return str.replace(/(\/\*|\/\*\*?|\*\*\/\*?)$/, "");
}
const ds = process.platform === "win32" ? "\\" : "/";
function ensureDirPath(path2, slash = ds) {
  if (!path2.endsWith(slash)) {
    return path2 + slash;
  }
  return path2;
}
function findFilesFromGlobArray(sources) {
  let files = [];
  for (const source of sources) {
    files = [
      ...files,
      ...findFiles(source)
    ];
  }
  return files;
}
function findFiles(src) {
  return fg.globSync(src).map((file) => {
    file = file.replace(/\\/g, "/");
    return {
      fullpath: file,
      relativePath: relative(getGlobBaseFromPattern(src), file).replace(/\\/g, "/")
    };
  });
}
function findModules$1(suffix = "", rootModule = "src/Module") {
  const pkg = path.resolve(process.cwd(), "composer.json");
  const pkgJson = loadJson$1(pkg);
  const vendors = Object.keys(pkgJson["require"] || {}).concat(Object.keys(pkgJson["require-dev"] || {})).map((id) => `vendor/${id}/composer.json`).map((file) => loadJson$1(file)).filter((pkgJson2) => pkgJson2?.extra?.windwalker != null).map((pkgJson2) => {
    return pkgJson2?.extra?.windwalker?.modules?.map((module) => {
      return `vendor/${pkgJson2.name}/${module}/${suffix}`;
    }) || [];
  }).flat();
  if (rootModule) {
    vendors.unshift(rootModule + "/" + suffix);
  }
  return [...new Set(vendors)];
}
function findPackages$1(suffix = "", withRoot = true) {
  const pkg = path.resolve(process.cwd(), "composer.json");
  const pkgJson = loadJson$1(pkg);
  const vendors = Object.keys(pkgJson["require"] || {}).concat(Object.keys(pkgJson["require-dev"] || {})).map((id) => `vendor/${id}/composer.json`).map((file) => loadJson$1(file)).filter((pkgJson2) => pkgJson2?.extra?.windwalker != null).map((pkgJson2) => `vendor/${pkgJson2.name}/${suffix}`).flat();
  if (withRoot) {
    vendors.unshift(suffix);
  }
  return [...new Set(vendors)];
}
function uniqId(prefix = "", size = 16) {
  let id = randomBytes(size).toString("hex");
  if (prefix) {
    id = prefix + id;
  }
  return id;
}
function resolveModuleRealpath(url, module) {
  const require2 = createRequire(url);
  return require2.resolve(module);
}
function stripUrlQuery(src) {
  const qPos = src.indexOf("?");
  if (qPos !== -1) {
    return src.substring(0, qPos);
  }
  return src;
}
function cloneAssets(patterns) {
  return callback((taskName, builder) => {
    const reposition = getAvailableForReposition(patterns);
    handleReposition(builder, reposition);
    handleCloneAssets(builder, Object.keys(patterns));
    return null;
  });
}
function getAvailableForReposition(patterns) {
  const reposition = {};
  for (const from in patterns) {
    if (!containsMiddleGlob(from)) {
      reposition[from] = patterns[from];
    }
  }
  return reposition;
}
function handleCloneAssets(builder, clonePatterns) {
  const id = uniqId("hidden:clone-asset-") + ".js";
  builder.addTask(id);
  builder.resolveIdCallbacks.push((src) => {
    if (src === id) {
      return id;
    }
  });
  builder.loadCallbacks.push((src) => {
    if (src === id) {
      const glob = clonePatterns.map((v) => v.replace(/\\/g, "/")).map((v) => v.startsWith("./") || !v.startsWith("/") ? `/${v}` : v).map((v) => `'${v}'`).join(", ");
      return `import.meta.glob(${glob});
`;
    }
  });
}
function handleReposition(builder, reposition) {
  builder.assetFileNamesCallbacks.push((assetInfo) => {
    const fileName = assetInfo.originalFileName;
    for (const base in reposition) {
      if (match(fileName, base)) {
        return normalize(reposition[base] + relative(removeLastGlob(base), fileName)).replace(/\\/g, "/");
      }
    }
  });
}
function match(str, pattern) {
  if (isGlob(pattern)) {
    return micromatch.isMatch(str, pattern);
  }
  return str.startsWith(pattern);
}
function globalAssets(options) {
  return {
    name: "core:global-assets",
    buildConfig(builder) {
      const clone = options.clone || {};
      let reposition = options.reposition || {};
      reposition = { ...reposition, ...getAvailableForReposition(clone) };
      handleReposition(builder, reposition);
      const clonePatterns = Object.keys(clone);
      if (clonePatterns.length > 0) {
        handleCloneAssets(builder, clonePatterns);
      }
    }
  };
}
function injectSystemJS(systemPath, filter) {
  systemPath ??= resolve("node_modules/systemjs/dist/system.min.js");
  return {
    name: "core:inject-systemjs",
    async generateBundle(options, bundle) {
      if (options.format !== "system") {
        return;
      }
      const systemjsCode = fs$1.readFileSync(
        resolve(systemPath),
        "utf-8"
      );
      for (const file of Object.values(bundle)) {
        if (filter && !filter(file)) {
          continue;
        }
        if (file.type === "chunk" && file.isEntry && file.fileName.endsWith(".js")) {
          file.code = systemjsCode + "\n" + file.code;
        }
      }
    }
  };
}
function systemCSSFix() {
  return {
    name: "core:systemjs-css-fix",
    async generateBundle(options, bundle) {
      if (options.format !== "system") {
        return;
      }
      for (const [fileName, chunk] of Object.entries(bundle)) {
        if (fileName.endsWith(".css") && "code" in chunk) {
          const regex = /__vite_style__\.textContent\s*=\s*"([\s\S]*?)";/;
          let match2 = chunk.code.match(regex);
          if (!match2) {
            const regex2 = /\.textContent\s*=\s*`([\s\S]*?)`/;
            match2 = chunk.code.match(regex2);
          }
          if (match2 && match2[1]) {
            chunk.code = match2[1].replace(/\\"/g, '"').replace(/\\n/g, "\n").replace(/\\t/g, "	").replace(/\\\\/g, "\\").replace(/\/\*\$vite\$:\d+\*\/$/, "");
          }
        }
      }
    }
  };
}
function loadJson(file) {
  if (!fs.existsSync(file)) {
    return null;
  }
  return JSON.parse(fs.readFileSync(file, "utf8"));
}
process.platform === "win32" ? "\\" : "/";
function findModules(suffix = "", rootModule = "src/Module") {
  const pkg = path.resolve(process.cwd(), "composer.json");
  const pkgJson = loadJson(pkg);
  const vendors = Object.keys(pkgJson["require"] || {}).concat(Object.keys(pkgJson["require-dev"] || {})).map((id) => `vendor/${id}/composer.json`).map((file) => loadJson(file)).filter((pkgJson2) => pkgJson2?.extra?.windwalker != null).map((pkgJson2) => {
    return pkgJson2?.extra?.windwalker?.modules?.map((module) => {
      return `vendor/${pkgJson2.name}/${module}/${suffix}`;
    }) || [];
  }).flat();
  if (rootModule) {
    vendors.unshift(rootModule + "/" + suffix);
  }
  return [...new Set(vendors)];
}
function findPackages(suffix = "", withRoot = true) {
  const pkg = path.resolve(process.cwd(), "composer.json");
  const pkgJson = loadJson(pkg);
  const vendors = Object.keys(pkgJson["require"] || {}).concat(Object.keys(pkgJson["require-dev"] || {})).map((id) => `vendor/${id}/composer.json`).map((file) => loadJson(file)).filter((pkgJson2) => pkgJson2?.extra?.windwalker != null).map((pkgJson2) => `vendor/${pkgJson2.name}/${suffix}`).flat();
  if (withRoot) {
    vendors.unshift(suffix);
  }
  return [...new Set(vendors)];
}
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
process.platform === "win32" ? "\\" : "/";
function cssModulize(entry, dest) {
  return new CssModulizeProcessor(css(entry, dest));
}
function cssModulizeDeep(stage, entry, dest, options = {}) {
  const processor = cssModulize(entry, dest);
  if (options.mergeCss ?? true) {
    processor.mergeCss(findModules(`${stage}/**/assets/*.scss`));
  }
  if (options.parseBlades ?? true) {
    processor.parseBlades(
      findModules(`${stage}/**/*.blade.php`),
      findPackages("views/**/*.blade.php")
    );
  }
  return processor;
}
class CssModulizeProcessor {
  constructor(processor, bladePatterns = [], cssPatterns = []) {
    this.processor = processor;
    this.bladePatterns = bladePatterns;
    this.cssPatterns = cssPatterns;
  }
  parseBlades(...bladePatterns) {
    this.bladePatterns = this.bladePatterns.concat(bladePatterns.flat());
    return this;
  }
  mergeCss(...css2) {
    this.cssPatterns = this.cssPatterns.concat(css2.flat());
    return this;
  }
  config(taskName, builder) {
    const tasks = this.processor.config(taskName, builder);
    const task = tasks[0];
    const inputFile = resolve(task.input);
    const bladeFiles = fg.globSync(this.bladePatterns);
    for (const file of bladeFiles) {
      builder.watches.push({
        file,
        moduleFile: inputFile,
        updateType: "css-update"
      });
    }
    builder.loadCallbacks.push((src, options) => {
      const file = stripUrlQuery(src);
      if (normalize(file) === inputFile) {
        const patterns = fg.globSync(
          this.cssPatterns.map((v) => resolve(v)).map((v) => v.replace(/\\/g, "/"))
        );
        const imports = patterns.map((pattern) => `@import "${pattern}";`).concat(this.parseStylesFromBlades(bladeFiles)).join("\n");
        let main = fs$1.readFileSync(file, "utf-8");
        main += `

${imports}
`;
        return main;
      }
    });
    return void 0;
  }
  parseStylesFromBlades(files) {
    return files.map((file) => {
      const bladeText = fs$1.readFileSync(file, "utf8");
      const html = parse(bladeText);
      return html.querySelectorAll("style[type][data-macro],script[type][data-macro]").filter(
        (el) => ["text/scss", "text/css"].includes(el.getAttribute("type") || "")
      ).map((el) => {
        const scope = el.getAttribute("data-scope");
        if (scope) {
          return `${scope} {
          ${el.innerHTML}
        }`;
        } else {
          return el.innerHTML;
        }
      });
    }).filter((c) => c.length > 0).flat();
  }
  preview() {
    return [];
  }
}
function jsModulize(entry, dest, options = {}) {
  return new JsModulizeProcessor(js(entry, dest), options);
}
function jsModulizeDeep(stage, entry, dest, options = {}) {
  const processor = jsModulize(entry, dest, options).stage(stage.toLowerCase());
  if (options.mergeScripts ?? true) {
    processor.mergeScripts(
      findModules(`${stage}/**/assets/*.ts`)
    );
  }
  if (options.parseBlades ?? true) {
    processor.parseBlades(
      findModules(`${stage}/**/*.blade.php`),
      findPackages("views/**/*.blade.php")
    );
  }
  return processor;
}
class JsModulizeProcessor {
  constructor(processor, options = {}) {
    this.processor = processor;
    this.options = options;
  }
  scriptPatterns = [];
  bladePatterns = [];
  stagePrefix = "";
  config(taskName, builder) {
    const tasks = this.processor.config(taskName, builder);
    const task = tasks[0];
    const inputFile = resolve(task.input);
    const tmpPath = this.options.tmpPath ?? resolve("./tmp/fusion/jsmodules/").replace(/\\/g, "/");
    const clean = this.options.cleanTmp ?? true;
    if (clean) {
      builder.postBuildCallbacks.push((options, bundle) => {
        fs$1.removeSync(tmpPath);
      });
    }
    this.ignoreMainImport(task);
    builder.resolveIdCallbacks.push((id) => {
      if (id === "@main") {
        return { id, external: true };
      }
    });
    const scriptFiles = findFilesFromGlobArray(this.scriptPatterns);
    const bladeFiles = parseScriptsFromBlades(this.bladePatterns);
    builder.loadCallbacks.push((src, options) => {
      const srcFile = stripUrlQuery(src);
      if (normalize(srcFile) === inputFile) {
        let listJS = "{\n";
        for (const scriptFile of scriptFiles) {
          let fullpath = scriptFile.fullpath;
          if (fullpath.endsWith(".d.ts")) {
            continue;
          }
          let key = scriptFile.relativePath.replace(/assets\//, "").toLowerCase();
          fullpath = resolve(fullpath).replace(/\\/g, "/");
          key = key.substring(0, key.lastIndexOf("."));
          if (this.stagePrefix) {
            key = this.stagePrefix + "/" + key;
          }
          key = "view:" + crypto.createHash("md5").update(key).digest("hex");
          listJS += `'${key}': () => import('${fullpath}'),
`;
        }
        const listens = [];
        fs$1.ensureDirSync(tmpPath);
        for (const result of bladeFiles) {
          let key = result.as;
          const tmpFile = tmpPath + "/" + result.path.replace(/\\|\//g, "_") + "-" + shortHash(result.code) + ".ts";
          if (!fs$1.existsSync(tmpFile) || fs$1.readFileSync(tmpFile, "utf8") !== result.code) {
            fs$1.writeFileSync(tmpFile, result.code);
          }
          listJS += `'inline:${key}': () => import('${tmpFile}'),
`;
          const fullpath = resolve(result.file.fullpath).replace(/\\/g, "/");
          if (!listens.includes(fullpath)) {
            listens.push(fullpath);
          }
        }
        listJS += "}";
        builder.watches.push(...listens);
        let { code, comments } = stripComments(fs$1.readFileSync(srcFile, "utf-8"));
        code = code.replace(/defineJsModules\((.*?)\)/g, listJS);
        return restoreComments(code, comments);
      }
    });
    return void 0;
  }
  /**
   * @see https://github.com/vitejs/vite/issues/6393#issuecomment-1006819717
   * @see https://stackoverflow.com/questions/76259677/vite-dev-server-throws-error-when-resolving-external-path-from-importmap
   */
  ignoreMainImport(task) {
    const VALID_ID_PREFIX = `/@id/`;
    const importKeys = ["@main"];
    const reg = new RegExp(
      `${VALID_ID_PREFIX}(${importKeys.join("|")})`,
      "g"
    );
    plugin({
      name: "keep-main-external-" + task.id,
      transform(code) {
        return reg.test(code) ? code.replace(reg, (m, s1) => s1) : code;
      }
    });
  }
  preview() {
    return [];
  }
  mergeScripts(...patterns) {
    this.scriptPatterns = this.scriptPatterns.concat(patterns.flat());
    return this;
  }
  parseBlades(...bladePatterns) {
    this.bladePatterns = this.bladePatterns.concat(bladePatterns.flat());
    return this;
  }
  stage(stage) {
    this.stagePrefix = stage;
    return this;
  }
}
function parseScriptsFromBlades(patterns) {
  let files = findFilesFromGlobArray(Array.isArray(patterns) ? patterns : [patterns]);
  return files.map((file) => {
    const bladeText = fs$1.readFileSync(file.fullpath, "utf8");
    const html = parse(bladeText);
    return html.querySelectorAll("script[lang][data-macro]").filter(
      (el) => ["ts", "typescript"].includes(el.getAttribute("lang") || "")
    ).map((el) => ({
      as: el.getAttribute("data-macro") || "",
      file,
      path: file.relativePath.replace(/.blade.php$/, ""),
      code: el.innerHTML
    })).filter((c) => c.code.trim() !== "");
  }).flat();
}
function stripComments(code) {
  const comments = [];
  let i = 0;
  code = code.replace(/\/\*[\s\S]*?\*\//g, (match2) => {
    const key = `__COMMENT_BLOCK_${i}__`;
    comments.push({ key, value: match2 });
    i++;
    return key;
  }).replace(/\/\/.*$/gm, (match2) => {
    const key = `__COMMENT_LINE_${i}__`;
    comments.push({ key, value: match2 });
    i++;
    return key;
  });
  return { code, comments };
}
function restoreComments(code, comments) {
  for (const { key, value } of comments) {
    const re = new RegExp(key, "g");
    code = code.replace(re, value);
  }
  return code;
}
function installVendors(npmVendors = [], to = "www/assets/vendor") {
  return callbackAfterBuild(() => findAndInstall(npmVendors, to));
}
async function findAndInstall(npmVendors = [], to = "www/assets/vendor") {
  const root = to;
  let vendors = npmVendors;
  const action = process.env.INSTALL_VENDOR === "hard" ? "Copy" : "Link";
  console.log("");
  if (!fs$1.existsSync(root)) {
    fs$1.mkdirSync(root);
  }
  const dirs = fs$1.readdirSync(root, { withFileTypes: true }).filter((d) => d.isDirectory()).map((dir) => path.join(root, dir.name));
  dirs.unshift(root);
  dirs.forEach((dir) => {
    deleteExists(dir);
  });
  const composerJsons = getInstalledComposerVendors().map((cv) => `vendor/${cv}/composer.json`).map((file) => loadJson$1(file)).filter((composerJson) => composerJson?.extra?.windwalker != null);
  vendors = findNpmVendors(composerJsons).concat(vendors);
  vendors = [...new Set(vendors)];
  for (const vendor of vendors) {
    const source = `node_modules/${vendor}/`;
    if (fs$1.existsSync(source)) {
      console.log(`[${action} NPM] node_modules/${vendor}/ => ${root}/${vendor}/`);
      doInstall(source, `${root}/${vendor}/`);
    }
  }
  for (const composerJson of composerJsons) {
    const vendorName = composerJson.name;
    let assets = composerJson?.extra?.windwalker?.assets?.link;
    if (!assets) {
      continue;
    }
    if (!assets.endsWith("/")) {
      assets += "/";
    }
    if (fs$1.existsSync(`vendor/${vendorName}/${assets}`)) {
      console.log(`[${action} Composer] vendor/${vendorName}/${assets} => ${root}/${vendorName}/`);
      doInstall(`vendor/${vendorName}/${assets}`, `${root}/${vendorName}/`);
    }
  }
  const staticVendorDir = "resources/assets/vendor/";
  if (fs$1.existsSync(staticVendorDir)) {
    const staticVendors = fs$1.readdirSync(staticVendorDir);
    for (const staticVendor of staticVendors) {
      if (staticVendor.startsWith("@")) {
        const subVendors = fs$1.readdirSync(staticVendorDir + staticVendor);
        for (const subVendor of subVendors) {
          const subVendorName = staticVendor + "/" + subVendor;
          const source = staticVendorDir + subVendorName + "/";
          if (fs$1.existsSync(source)) {
            console.log(`[${action} Local] resources/assets/vendor/${subVendorName}/ => ${root}/${subVendorName}/`);
            doInstall(source, `${root}/${subVendorName}/`);
          }
        }
      } else {
        let source = staticVendorDir + staticVendor;
        if (fs$1.existsSync(source)) {
          console.log(`[${action} Local] resources/assets/vendor/${staticVendor}/ => ${root}/${staticVendor}/`);
          doInstall(source, `${root}/${staticVendor}/`);
        }
      }
    }
  }
}
async function doInstall(source, dest) {
  if (process.env.INSTALL_VENDOR === "hard") {
    await copyGlob(source + "/**/*", dest);
  } else {
    await symlink(source, dest);
  }
}
function findNpmVendors(composerJsons = []) {
  const pkg = path.resolve(process.cwd(), "package.json");
  const pkgJson = loadJson$1(pkg);
  let vendors = Object.keys(pkgJson.devDependencies || {}).concat(Object.keys(pkgJson.dependencies || {})).map((id) => `node_modules/${id}/package.json`).map((file) => loadJson$1(file)).filter((pkgJson2) => pkgJson2?.windwalker != null).map((pkgJson2) => pkgJson2?.windwalker.vendors || []).flat();
  const vendorsFromComposer = composerJsons.map((composerJson) => {
    return [
      ...composerJson?.extra?.windwalker?.asset_vendors || [],
      ...composerJson?.extra?.windwalker?.assets?.exposes || [],
      ...Object.keys(composerJson?.extra?.windwalker?.assets?.vendors || {})
    ];
  }).flat();
  return [...new Set(vendors.concat(vendorsFromComposer))];
}
function getInstalledComposerVendors() {
  const composerFile = path.resolve(process.cwd(), "composer.json");
  const composerJson = loadJson$1(composerFile);
  return [
    ...new Set(
      Object.keys(composerJson["require"] || {}).concat(Object.keys(composerJson["require-dev"] || {}))
    )
  ];
}
function deleteExists(dir) {
  if (!fs$1.existsSync(dir)) {
    return;
  }
  const subDirs = fs$1.readdirSync(dir, { withFileTypes: true });
  for (const subDir of subDirs) {
    if (subDir.isSymbolicLink() || subDir.isFile()) {
      fs$1.unlinkSync(path.join(dir, subDir.name));
    } else if (subDir.isDirectory()) {
      deleteExists(path.join(dir, subDir.name));
    }
  }
  fs$1.rmdirSync(dir);
}
export {
  cloneAssets,
  containsMiddleGlob,
  cssModulize,
  cssModulizeDeep,
  ensureDirPath,
  findFilesFromGlobArray,
  findModules$1 as findModules,
  findPackages$1 as findPackages,
  globalAssets,
  injectSystemJS,
  installVendors,
  jsModulize,
  jsModulizeDeep,
  loadJson$1 as loadJson,
  removeLastGlob,
  resolveModuleRealpath,
  stripUrlQuery,
  systemCSSFix,
  uniqId
};
