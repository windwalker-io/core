import { callback, css, callbackAfterBuild, copyGlob, symlink } from "@windwalker-io/fusion-next";
import isGlob from "is-glob";
import micromatch from "micromatch";
import path, { normalize, relative, resolve } from "node:path";
import fs from "node:fs";
import { randomBytes } from "node:crypto";
import { createRequire } from "node:module";
import fs$1 from "fs-extra";
import fg from "fast-glob";
import { parse } from "node-html-parser";
function loadJson(file) {
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
function findModules(suffix = "") {
  const pkg = path.resolve(process.cwd(), "composer.json");
  const pkgJson = loadJson(pkg);
  const vendors = Object.keys(pkgJson["require"] || {}).concat(Object.keys(pkgJson["require-dev"] || {})).map((id) => `vendor/${id}/composer.json`).map((file) => loadJson(file)).filter((pkgJson2) => pkgJson2?.extra?.windwalker != null).map((pkgJson2) => {
    return pkgJson2?.extra?.windwalker?.modules?.map((module) => {
      return `vendor/${pkgJson2.name}/${module}/${suffix}`;
    }) || [];
  }).flat();
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
function windwalkerAssets(options) {
  return {
    name: "ww:assets",
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
    name: "inject-systemjs",
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
    name: "systemjs.css.fix",
    async generateBundle(options, bundle) {
      if (options.format !== "system") {
        return;
      }
      for (const [fileName, chunk] of Object.entries(bundle)) {
        if (fileName.endsWith(".css") && "code" in chunk) {
          const regex = /__vite_style__\.textContent\s*=\s*"([\s\S]*?)";/;
          const match2 = chunk.code.match(regex);
          if (match2 && match2[1]) {
            chunk.code = match2[1].replace(/\\"/g, '"').replace(/\\n/g, "\n").replace(/\\t/g, "	").replace(/\\\\/g, "\\").replace(/\/\*\$vite\$:\d+\*\/$/, "");
          }
        }
      }
    }
  };
}
function cssModulize(entry, dest) {
  return new CssModulizeProcessor(css(entry, dest));
}
class CssModulizeProcessor {
  constructor(processor, bladePatterns = [], cssPatterns = []) {
    this.processor = processor;
    this.bladePatterns = bladePatterns;
    this.cssPatterns = cssPatterns;
  }
  parseBlades(...bladePatterns) {
    this.bladePatterns = bladePatterns.flat();
    return this;
  }
  mergeCss(...css2) {
    this.cssPatterns = css2.flat();
    return this;
  }
  config(taskName, builder) {
    const tasks = this.processor.config(taskName, builder);
    for (const task of tasks) {
      builder.loadCallbacks.push((src, options) => {
        const file = stripUrlQuery(src);
        if (normalize(file) === resolve(task.input)) {
          const patterns = fg.globSync(
            this.cssPatterns.map((v) => resolve(v)).map((v) => v.replace(/\\/g, "/"))
          );
          const imports = patterns.map((pattern) => `@import "${pattern}";`).concat(parseStylesFromBlades(this.bladePatterns)).join("\n");
          let main = fs$1.readFileSync(file, "utf-8");
          main += `

${imports}
`;
          return main;
        }
      });
    }
    return void 0;
  }
  preview() {
    return [];
  }
}
function parseStylesFromBlades(patterns) {
  let files = fg.globSync(patterns);
  return files.map((file) => {
    const bladeText = fs$1.readFileSync(file, "utf8");
    const html = parse(bladeText);
    return html.querySelectorAll("style[type],script[type]").filter(
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
function stripUrlQuery(src) {
  const qPos = src.indexOf("?");
  if (qPos !== -1) {
    return src.substring(0, qPos);
  }
  return src;
}
var define_process_env_default = {};
function installVendors(npmVendors = [], to = "www/assets/vendor") {
  return callbackAfterBuild(() => findAndInstall(npmVendors, to));
}
async function findAndInstall(npmVendors = [], to = "www/assets/vendor") {
  const root = to;
  let vendors = npmVendors;
  const action = define_process_env_default.INSTALL_VENDOR === "hard" ? "Copy" : "Link";
  console.log("");
  if (!fs$1.existsSync(root)) {
    fs$1.mkdirSync(root);
  }
  const dirs = fs$1.readdirSync(root, { withFileTypes: true }).filter((d) => d.isDirectory()).map((dir) => path.join(root, dir.name));
  dirs.unshift(root);
  dirs.forEach((dir) => {
    deleteExists(dir);
  });
  const composerJsons = getInstalledComposerVendors().map((cv) => `vendor/${cv}/composer.json`).map((file) => loadJson(file)).filter((composerJson) => composerJson?.extra?.windwalker != null);
  vendors = findNpmVendors(composerJsons).concat(vendors);
  vendors = [...new Set(vendors)];
  for (const vendor of vendors) {
    if (fs$1.existsSync(`node_modules/${vendor}/`)) {
      console.log(`[${action} NPM] node_modules/${vendor}/ => ${root}/${vendor}/`);
      doInstall(`node_modules/${vendor}/`, `${root}/${vendor}/`);
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
          console.log(`[${action} Local] resources/assets/vendor/${subVendorName}/ => ${root}/${subVendorName}/`);
          doInstall(staticVendorDir + subVendorName + "/", `${root}/${subVendorName}/`);
        }
      } else {
        console.log(`[${action} Local] resources/assets/vendor/${staticVendor}/ => ${root}/${staticVendor}/`);
        doInstall(staticVendorDir + staticVendor, `${root}/${staticVendor}/`);
      }
    }
  }
}
async function doInstall(source, dest) {
  if (define_process_env_default.INSTALL_VENDOR === "hard") {
    await copyGlob(source + "/**/*", dest);
  } else {
    await symlink(source, dest);
  }
}
function findNpmVendors(composerJsons = []) {
  const pkg = path.resolve(process.cwd(), "package.json");
  const pkgJson = loadJson(pkg);
  let vendors = Object.keys(pkgJson.devDependencies || {}).concat(Object.keys(pkgJson.dependencies || {})).map((id) => `node_modules/${id}/package.json`).map((file) => loadJson(file)).filter((pkgJson2) => pkgJson2?.windwalker != null).map((pkgJson2) => pkgJson2?.windwalker.vendors || []).flat();
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
  const composerJson = loadJson(composerFile);
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
  ensureDirPath,
  findModules,
  injectSystemJS,
  installVendors,
  loadJson,
  removeLastGlob,
  resolveModuleRealpath,
  systemCSSFix,
  uniqId,
  windwalkerAssets
};
