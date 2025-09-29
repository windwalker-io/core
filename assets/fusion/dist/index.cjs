'use strict';

Object.defineProperties(exports, { __esModule: { value: true }, [Symbol.toStringTag]: { value: 'Module' } });

const Crypto = require('crypto');
const fg = require('fast-glob');
const fs = require('fs-extra');
const node_crypto = require('node:crypto');
const node_path = require('node:path');
const node_util = require('node:util');
const vite = require('vite');
const lodashEs = require('lodash-es');
const yargs = require('yargs');
const esbuild = require('esbuild');
const Module = require('module');
const node_fs = require('node:fs');
const archy = require('archy');
const chalk = require('chalk');
const fs$1 = require('fs');
const micromatch = require('micromatch');

function forceArray(item) {
  if (Array.isArray(item)) {
    return item;
  } else {
    return [item];
  }
}
function handleMaybeArray(items, callback) {
  if (Array.isArray(items)) {
    return items.map(callback);
  } else {
    return callback(items);
  }
}
function handleForceArray(items, callback) {
  items = forceArray(items);
  return items.map(callback);
}

function css(input, output, options = {}) {
  return new CssProcessor(input, output, options);
}
class CssProcessor {
  constructor(input, output, options = {}) {
    this.input = input;
    this.output = output;
    this.options = options;
  }
  config(taskName, builder) {
    return handleForceArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);
      builder.assetFileNamesCallbacks.push((assetInfo) => {
        const name = assetInfo.names[0];
        if (!name) {
          return void 0;
        }
        if (node_path.basename(name, ".css") === task.id) {
          if (!this.output) {
            return node_path.parse(input).name + ".css";
          }
          return task.normalizeOutput(this.output, ".css");
        }
      });
      return task;
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || node_path.basename(input),
        extra: {}
      };
    });
  }
}

function js(input, output) {
  return new JsProcessor(input, output);
}
class JsProcessor {
  constructor(input, output) {
    this.input = input;
    this.output = output;
  }
  config(taskName, builder) {
    return handleForceArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);
      builder.entryFileNamesCallbacks.push((chunkInfo) => {
        const name = chunkInfo.name;
        if (!name) {
          return;
        }
        if (name === task.id) {
          if (!this.output) {
            return node_path.parse(input).name + ".js";
          }
          return task.normalizeOutput(this.output);
        }
      });
      return task;
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || node_path.basename(input),
        extra: {}
      };
    });
  }
}

function move(input, dest) {
  return new MoveProcessor(input, dest);
}
class MoveProcessor {
  constructor(input, dest) {
    this.input = input;
    this.dest = dest;
  }
  config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      builder.moveTasks.push({ src: input, dest: this.dest, options: {} });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

function copy(input, dest) {
  return new CopyProcessor(input, dest);
}
class CopyProcessor {
  constructor(input, dest) {
    this.input = input;
    this.dest = dest;
  }
  config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      builder.copyTasks.push({ src: input, dest: this.dest, options: {} });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

function link(input, dest, options = {}) {
  return new LinkProcessor(input, dest, options);
}
class LinkProcessor {
  constructor(input, dest, options = {}) {
    this.input = input;
    this.dest = dest;
    this.options = options;
  }
  config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      builder.linkTasks.push({ src: input, dest: this.dest, options: this.options });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.dest,
        extra: {}
      };
    });
  }
}

function callback(handler) {
  return new CallbackProcessor(handler);
}
function callbackAfterBuild(handler) {
  return new CallbackProcessor(handler, true);
}
class CallbackProcessor {
  constructor(handler, afterBuild = false) {
    this.handler = handler;
    this.afterBuild = afterBuild;
  }
  config(taskName, builder) {
    if (this.afterBuild) {
      builder.postBuildCallbacks.push(() => this.handler(taskName, builder));
    } else {
      this.handler(taskName, builder);
    }
    return void 0;
  }
  preview() {
    return [];
  }
}

exports.params = void 0;
function prepareParams(p) {
  exports.params = p;
  exports.isVerbose = exports.params?.verbose ? exports.params?.verbose > 0 : false;
  return p;
}
exports.isVerbose = false;
const isProd = process.env.NODE_ENV === "production";
const isDev = !isProd;

function isWindows() {
  return process.platform === "win32";
}

function shortHash(bufferOrString, short = 8) {
  let hash = Crypto.createHash("sha1").update(bufferOrString).digest("hex");
  if (short && short > 0) {
    hash = hash.substring(0, short);
  }
  return hash;
}

function handleFilesOperation(src, dest, options) {
  const promises = [];
  src = normalizeFilePath(src, options.outDir);
  dest = normalizeFilePath(dest, options.outDir);
  const base = getGlobBaseFromPattern(src);
  const sources = isGlob(src) ? fg.globSync(src.replace(/\\/g, "/"), options.globOptions) : [src];
  for (let source of sources) {
    let dir;
    let resolvedDest = dest;
    if (endsWithSlash(dest)) {
      dir = resolvedDest;
      resolvedDest = resolvedDest + node_path.relative(base, source);
    } else {
      dir = node_path.dirname(resolvedDest);
    }
    fs.ensureDirSync(dir);
    promises.push(options.handler(source, resolvedDest));
  }
  return promises;
}
function moveFilesAndLog(tasks, outDir, logger) {
  const promises = [];
  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src2, dest2) => {
          logger.info(`Moving file from ${node_path.relative(outDir, src2)} to ${node_path.relative(outDir, dest2)}`);
          return fs.move(src2, dest2, { overwrite: true });
        },
        globOptions: { onlyFiles: true }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
function copyFilesAndLog(tasks, outDir, logger) {
  const promises = [];
  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src2, dest2) => {
          logger.info(`Copy file from ${node_path.relative(outDir, src2)} to ${node_path.relative(outDir, dest2)}`);
          return fs.copy(src2, dest2, { overwrite: true });
        },
        globOptions: { onlyFiles: true }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
function linkFilesAndLog(tasks, outDir, logger) {
  const promises = [];
  for (const { src, dest, options } of tasks) {
    const ps = handleFilesOperation(
      src,
      dest,
      {
        outDir,
        handler: async (src2, dest2) => {
          logger.info(`Link file from ${node_path.relative(outDir, src2)} to ${node_path.relative(outDir, dest2)}`);
          return symlink(src2, dest2, options?.force ?? false);
        },
        globOptions: { onlyFiles: false }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
function cleanFiles(patterns, outDir) {
  const promises = [];
  outDir = outDir.replace(/\\/g, "/");
  for (let src of patterns) {
    src = normalizeFilePath(src, outDir);
    src = node_path.resolve(src);
    const sources = isGlob(src) ? fg.globSync(src.replace(/\\/g, "/"), { onlyFiles: false }) : [src];
    const protectDir = node_path.resolve(outDir + "/upload").replace(/\\/g, "/");
    for (let source of sources) {
      if (source.replace(/\\/g, "/").startsWith(protectDir)) {
        throw new Error("Refuse to delete `upload/*` folder.");
      }
      promises.push(fs.remove(source));
    }
  }
  return Promise.all(promises);
}
async function copyGlob(src, dest) {
  const promises = handleFilesOperation(
    src,
    dest,
    {
      outDir: process.cwd(),
      handler: async (src2, dest2) => fs.copy(src2, dest2, { overwrite: true }),
      globOptions: { onlyFiles: true }
    }
  );
  await Promise.all(promises);
}
async function moveGlob(src, dest) {
  const promises = handleFilesOperation(
    src,
    dest,
    {
      outDir: process.cwd(),
      handler: async (src2, dest2) => fs.move(src2, dest2, { overwrite: true }),
      globOptions: { onlyFiles: true }
    }
  );
  await Promise.all(promises);
}
async function symlink(target, link, force = false) {
  target = node_path.resolve(target);
  link = node_path.resolve(link);
  if (isWindows() && !fs.lstatSync(target).isFile()) {
    return fs.ensureSymlink(target, link, "junction");
  }
  if (isWindows() && fs.lstatSync(target).isFile() && force) {
    return fs.ensureLink(target, link);
  }
  return fs.ensureSymlink(target, link);
}
function endsWithSlash(path) {
  return path.endsWith("/") || path.endsWith("\\");
}
function getGlobBaseFromPattern(pattern) {
  const specialChars = ["*", "?", "[", "]"];
  const idx = [...pattern].findIndex((c) => specialChars.includes(c));
  if (idx === -1) {
    return node_path.dirname(pattern);
  }
  return node_path.dirname(pattern.slice(0, idx + 1));
}
function isGlob(pattern) {
  const specialChars = ["*", "?", "[", "]"];
  return specialChars.some((c) => pattern.includes(c));
}
function normalizeFilePath(path, outDir) {
  if (path.startsWith(".")) {
    path = node_path.resolve(path);
  } else if (!node_path.isAbsolute(path)) {
    path = outDir + "/" + path;
  }
  return path;
}
function fileToId(input, group) {
  input = node_path.normalize(input);
  group ||= node_crypto.randomBytes(4).toString("hex");
  return group + "-" + shortHash(input);
}

const fusion = /*#__PURE__*/Object.freeze(/*#__PURE__*/Object.defineProperty({
  __proto__: null,
  callback,
  callbackAfterBuild,
  copy,
  copyGlob,
  css,
  fileToId,
  getGlobBaseFromPattern,
  isDev,
  isProd,
  get isVerbose () { return exports.isVerbose; },
  isWindows,
  js,
  link,
  move,
  moveGlob,
  get params () { return exports.params; },
  shortHash,
  symlink
}, Symbol.toStringTag, { value: 'Module' }));

class BuildTask {
  constructor(input, group) {
    this.input = input;
    this.group = group;
    this.id = BuildTask.toFileId(input, group);
    this.input = node_path.normalize(input);
  }
  id;
  output;
  postCallbacks = [];
  dest(output) {
    if (typeof output === "string") {
      output = this.normalizeOutput(output);
    }
    this.output = output;
    return this;
  }
  addPostCallback(callback) {
    this.postCallbacks.push(callback);
    return this;
  }
  normalizeOutput(output, ext = ".js") {
    if (output.endsWith("/") || output.endsWith("\\")) {
      output += node_path.parse(this.input).name + ext;
    }
    return output;
  }
  static toFileId(input, group) {
    return fileToId(input, group);
  }
}

function mergeOptions(base, ...overrides) {
  if (!overrides.length) {
    return base;
  }
  for (const override of overrides) {
    if (!override) {
      continue;
    }
    if (typeof override === "function") {
      base = override(base) ?? base;
    } else {
      base = vite.mergeConfig(base, override);
    }
  }
  return base;
}
function show(data, depth = 10) {
  console.log(node_util.inspect(data, { depth, colors: true }));
}

class ConfigBuilder {
  constructor(config, env, fusionOptions) {
    this.config = config;
    this.env = env;
    this.fusionOptions = fusionOptions;
    this.config = vite.mergeConfig(
      {
        build: {
          manifest: "manifest.json",
          rollupOptions: {
            preserveEntrySignatures: "strict",
            input: {},
            output: this.getDefaultOutput()
            // external: (source: string, importer: string | undefined, isResolved: boolean) => {
            //   for (const external of this.externals) {
            //     const result = external(source, importer, isResolved);
            //
            //     if (result) {
            //       return true;
            //     }
            //   }
            // },
          },
          emptyOutDir: false,
          sourcemap: env.mode !== "production" ? "inline" : false
        },
        plugins: [],
        css: {
          devSourcemap: true
        },
        esbuild: {
          // Todo: Remove if esbuild supports decorators by default
          target: "es2022"
        }
      },
      this.config
    );
    this.addTask("hidden:placeholder");
  }
  server = null;
  static globalOverrideConfig = {};
  overrideConfig = {};
  entryFileNamesCallbacks = [];
  chunkFileNamesCallbacks = [];
  assetFileNamesCallbacks = [];
  moveTasks = [];
  copyTasks = [];
  linkTasks = [];
  postBuildCallbacks = [];
  resolveIdCallbacks = [];
  loadCallbacks = [];
  // fileNameMap: Record<string, string> = {};
  // externals: ((source: string, importer: string | undefined, isResolved: boolean) => boolean | string | NullValue)[] = [];
  watches = [];
  cleans = [];
  tasks = /* @__PURE__ */ new Map();
  merge(override) {
    if (typeof override === "function") {
      this.config = override(this.config) ?? this.config;
      return this;
    }
    this.config = vite.mergeConfig(this.config, override);
    return this;
  }
  getDefaultOutput() {
    let serial = 0;
    return {
      entryFileNames: (chunkInfo) => {
        const name = this.getChunkNameFromTask(chunkInfo);
        if (name) {
          return name;
        }
        for (const entryFileNamesCallback of this.entryFileNamesCallbacks) {
          const name2 = entryFileNamesCallback(chunkInfo);
          if (name2) {
            return name2;
          }
        }
        return "[name].js";
      },
      chunkFileNames: (chunkInfo) => {
        serial++;
        const name = this.getChunkNameFromTask(chunkInfo);
        if (name) {
          return name;
        }
        for (const chunkFileNamesCallback of this.chunkFileNamesCallbacks) {
          const name2 = chunkFileNamesCallback(chunkInfo);
          if (name2) {
            return name2;
          }
        }
        const chunkDir = this.getChunkDir();
        if (this.env.mode === "production" && this.fusionOptions.chunkNameObfuscation) {
          return `${chunkDir}${serial}.js`;
        }
        return `${chunkDir}[name]-[hash].js`;
      },
      assetFileNames: (assetInfo) => {
        for (const assetFileNamesCallback of this.assetFileNamesCallbacks) {
          const name = assetFileNamesCallback(assetInfo);
          if (name) {
            return name;
          }
        }
        return "[name].[ext]";
      }
    };
  }
  getChunkDir() {
    let chunkDir = this.fusionOptions.chunkDir ?? "chunks";
    chunkDir.replace(/\\/g, "/");
    if (chunkDir && !chunkDir.endsWith("/")) {
      chunkDir += "/";
    }
    if (chunkDir === "./" || chunkDir === "/") {
      chunkDir = "";
    }
    return chunkDir;
  }
  getChunkNameFromTask(chunkInfo) {
    if (this.tasks.has(chunkInfo.name)) {
      const output = this.tasks.get(chunkInfo.name)?.output;
      if (output) {
        const name = typeof output === "function" ? output(chunkInfo) : output;
        if (!node_path.isAbsolute(name)) {
          return name;
        }
      }
    }
    return void 0;
  }
  ensurePath(path, def = {}) {
    if (lodashEs.get(this.config, path) == null) {
      lodashEs.set(this.config, path, def);
    }
    return this;
  }
  get(path) {
    return lodashEs.get(this.config, path);
  }
  set(path, value) {
    lodashEs.set(this.config, path, value);
    return this;
  }
  addTask(input, group) {
    const task = new BuildTask(input, group);
    this.tasks.set(task.id, task);
    const inputOptions = this.config.build.rollupOptions.input;
    inputOptions[task.id] = task.input;
    return task;
  }
  addCleans(...paths) {
    this.cleans.push(...paths);
    return this;
  }
  // addExternals(externals: Externalize) {
  //   if (Array.isArray(externals)) {
  //     this.externals.push((rollupOptions) => {
  //       rollupOptions.external
  //     })
  //   } else if (typeof externals === 'object') {
  //
  //   } else {
  //
  //   }
  // }
  // addPlugin(plugin: PluginOption) {
  //   this.config.plugins?.push(plugin);
  // }
  //
  // removePlugin(plugin: string | PluginOption) {
  //   this.config.plugins = this.config.plugins?.filter((p) => {
  //     if (!p) {
  //       return true;
  //     }
  //
  //     if (typeof plugin === 'string' && typeof p === 'object' && 'name' in p) {
  //       return p.name !== plugin;
  //     } else if (typeof plugin === 'object' && typeof p === 'object') {
  //       return p !== plugin;
  //     }
  //
  //     return true;
  //   });
  // }
  relativePath(to) {
    return node_path.relative(process.cwd(), to);
  }
  debug() {
    show(this.config);
  }
}

function getArgsAfterDoubleDashes(argv) {
  argv ??= process.argv;
  return argv.slice(2).join(" ").split(" -- ").slice(1).join(" -- ").trim().split(" ").filter((v) => v !== "");
}
function parseArgv(argv) {
  const app = yargs();
  app.option("cwd", {
    type: "string",
    description: "Current working directory"
  });
  app.option("list", {
    alias: "l",
    type: "boolean",
    description: "List all available tasks"
  });
  app.option("config", {
    alias: "c",
    type: "string",
    description: "Path to config file"
  });
  app.option("server-file", {
    alias: "s",
    type: "string",
    description: "Path to server file"
  });
  app.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  });
  return app.parseSync(argv);
}

async function loadConfigFile(configFile) {
  let path = configFile.path;
  if (process.platform === "win32") {
    const winPath = path.replace(/\\/g, "/");
    if (!winPath.startsWith("file://")) {
      path = `file:///${winPath}`;
    }
  }
  if (configFile.ts) {
    const buildResult = await esbuild.build({
      entryPoints: [configFile.path],
      bundle: true,
      write: false,
      outdir: "dist",
      platform: "node",
      format: "cjs",
      target: "esnext",
      external: ["../dist", "../dist/*"],
      packages: "external",
      sourcemap: "inline"
    });
    const output = buildResult.outputFiles[0];
    const code = Buffer.from(output.contents).toString("utf8");
    node_fs.writeFileSync(output.path, code);
    const m = new Module(output.path, void 0);
    m.filename = output.path;
    m.paths = Module._nodeModulePaths(node_path.dirname(output.path));
    m._compile(code, output.path);
    return expandModules(m.exports);
  } else {
    const modules = await import(path);
    return expandModules(modules);
  }
}
function expandModules(modules) {
  modules = { ...modules };
  if (modules.__esModule) {
    delete modules.__esModule;
  }
  return modules;
}
async function resolveTaskOptions(task, resolveSubFunctions = false) {
  task = await task;
  if (!resolveSubFunctions && Array.isArray(task)) {
    const results = await Promise.all(task.map((task2) => resolveTaskOptions(task2, true)));
    return results.flat();
  }
  if (typeof task === "function") {
    return resolvePromisesToFlatArray(await task(), task?.name);
  }
  return resolvePromisesToFlatArray(await task, task?.name);
}
async function resolvePromisesToFlatArray(tasks, name) {
  if (!Array.isArray(tasks)) {
    return [await tasks];
  }
  const resolvedTasks = await Promise.all(tasks);
  const returnTasks = [];
  for (const resolvedTask of resolvedTasks) {
    if (Array.isArray(resolvedTask)) {
      returnTasks.push(...resolvedTask);
    } else {
      returnTasks.push(resolvedTask);
    }
  }
  return returnTasks;
}
function mustGetAvailableConfigFile(root, params) {
  const found = getAvailableConfigFile(root, params);
  if (!found) {
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  }
  return found;
}
function getAvailableConfigFile(root, params) {
  let found = params?.config;
  if (found) {
    if (!node_path.isAbsolute(found)) {
      found = node_path.resolve(root, found);
    }
    if (node_fs.existsSync(found)) {
      return {
        path: found,
        // get filename from file path
        filename: found.split("/").pop() || "",
        type: getConfigModuleType(found),
        ts: isConfigTypeScript(found)
      };
    }
    return null;
  }
  return findDefaultConfig(root);
}
function findDefaultConfig(root) {
  let file = node_path.resolve(root, "fusionfile.js");
  if (node_fs.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "commonjs",
      ts: false
    };
  }
  file = node_path.resolve(root, "fusionfile.mjs");
  if (node_fs.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: false
    };
  }
  file = node_path.resolve(root, "fusionfile.ts");
  if (node_fs.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: true
    };
  }
  file = node_path.resolve(root, "fusionfile.mts");
  if (node_fs.existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: true
    };
  }
  return null;
}
function getConfigModuleType(file) {
  let type = "unknown";
  if (file.endsWith(".cjs")) {
    type = "commonjs";
  } else if (file.endsWith(".mjs")) {
    type = "module";
  } else if (file.endsWith(".ts") || file.endsWith(".mts")) {
    type = "module";
  }
  return type;
}
function isConfigTypeScript(file) {
  return file.endsWith(".ts") || file.endsWith(".mts");
}

async function displayAvailableTasks(tasks) {
  const keys = Object.keys(tasks);
  keys.sort((a, b) => {
    if (a === "default") {
      return -1;
    }
    if (b === "default") {
      return 1;
    }
    return a.localeCompare(b);
  });
  const nodes = [];
  for (const key of keys) {
    const task = tasks[key];
    nodes.push(await describeTasks(key, task));
  }
  const text = archy({
    label: chalk.magenta("Available Tasks"),
    nodes
  });
  console.log(text);
}
async function describeTasks(name, tasks) {
  const nodes = [];
  tasks = forceArray(await tasks);
  for (let task of tasks) {
    const processors = await resolveTaskOptions(task, true);
    for (const processor of processors) {
      if (typeof processor === "function") {
        nodes.push(
          await describeTasks(processor.name, processor)
        );
      } else {
        nodes.push(...await describeProcessor(processor));
      }
    }
  }
  return {
    label: chalk.cyan(name),
    nodes
  };
}
async function describeProcessor(processor) {
  const results = await processor.preview();
  return Promise.all(results.map((result) => describeProcessorPreview(result)));
}
async function describeProcessorPreview(preview) {
  const str = [];
  const { input: entry, output, extra } = preview;
  const inputStr = chalk.yellow(entry);
  str.push(`Input: ${inputStr}`);
  const outStr = chalk.green(output);
  str.push(`Output: ${outStr}`);
  return str.join(" - ");
}

function selectRunningTasks(input, tasks) {
  input = lodashEs.uniq(input);
  if (input.length === 0) {
    input.push("default");
  }
  const selected = {};
  for (const name of input) {
    if (tasks[name]) {
      selected[name] = tasks[name];
    } else {
      throw new Error(`Task "${chalk.cyan(name)}" not found in fusion config.`);
    }
  }
  return selected;
}
async function resolveAllTasksAsProcessors(tasks) {
  const cache = {};
  const allTasks = {};
  for (const name in tasks) {
    const task = tasks[name];
    allTasks[name] = await resolveTaskAsFlat(name, task, cache);
  }
  return allTasks;
}
async function resolveTaskAsFlat(name, task, cache) {
  const results = [];
  if (Array.isArray(task)) {
    for (const n in task) {
      const t = task[n];
      results.push(...await resolveTaskAsFlat(n, t, cache));
    }
  } else if (typeof task === "function") {
    name = task.name || name;
    if (cache[name]) {
      return [];
    }
    cache[name] = task;
    const resolved = await resolveTaskOptions(task, true);
    if (Array.isArray(resolved)) {
      for (const n in resolved) {
        const t = resolved[n];
        results.push(...await resolveTaskAsFlat(n, t, cache));
      }
    }
  } else {
    results.push(await task);
  }
  return results;
}

let params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);
exports.builder = void 0;
const originalTasks = params._;
const extraVitePlugins = [];
const defaultFusionOptions = {
  chunkDir: "chunks",
  chunkNameObfuscation: true
};
let overrideFusionOptions = {};
function useFusion(fusionOptions = {}, tasks) {
  let logger;
  let resolvedConfig;
  let exitHandlersBound = false;
  let resolvedOptions = mergeOptions(defaultFusionOptions, prepareFusionOptions(fusionOptions));
  if (typeof tasks === "string" || Array.isArray(tasks) && tasks.length > 0) {
    params._ = forceArray(tasks);
  } else {
    params._ = originalTasks;
  }
  params = mergeOptions(params, resolvedOptions.cliParams);
  return [
    {
      name: "fusion",
      configResolved(config) {
        resolvedConfig = config;
        logger = config.logger;
        config.plugins.push(...extraVitePlugins);
        for (const plugin2 of config.plugins) {
          if ("buildConfig" in plugin2) {
            plugin2.buildConfig?.(exports.builder);
          }
        }
      },
      async config(config, env) {
        let root;
        if (config.root) {
          root = node_path.resolve(config.root);
        } else {
          root = params.cwd || process.cwd();
        }
        delete config.root;
        process.chdir(root);
        exports.builder = new ConfigBuilder(config, env, resolvedOptions);
        let tasks2;
        if (typeof resolvedOptions.fusionfile === "string" || !resolvedOptions.fusionfile) {
          params.config ??= resolvedOptions.fusionfile;
          const configFile = mustGetAvailableConfigFile(root, params);
          tasks2 = await loadConfigFile(configFile);
        } else if (typeof resolvedOptions.fusionfile === "function") {
          tasks2 = expandModules(await resolvedOptions.fusionfile());
        } else {
          tasks2 = expandModules(resolvedOptions.fusionfile);
        }
        exports.builder.fusionOptions = mergeOptions(exports.builder.fusionOptions, overrideFusionOptions);
        if (params.list) {
          await displayAvailableTasks(tasks2);
          return;
        }
        const selectedTasks = selectRunningTasks([...params._], tasks2);
        const runningTasks = await resolveAllTasksAsProcessors(selectedTasks);
        for (const taskName in runningTasks) {
          const processors = runningTasks[taskName];
          for (const processor of processors) {
            await processor.config(taskName, exports.builder);
          }
        }
        exports.builder.merge(ConfigBuilder.globalOverrideConfig);
        exports.builder.merge(exports.builder.overrideConfig);
        return exports.builder.config;
      },
      outputOptions(options) {
        if (resolvedConfig.build.emptyOutDir) {
          const dir = resolvedConfig.build.outDir;
          const uploadDir = node_path.resolve(dir, "upload");
          if (node_fs.existsSync(uploadDir)) {
            throw new Error(
              `The output directory: "${dir}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
            );
          }
        }
      },
      async buildStart(options) {
        if (exports.builder.cleans.length > 0 && resolvedConfig.command !== "serve") {
          await cleanFiles(exports.builder.cleans, resolvedConfig.build.outDir || process.cwd());
        }
      },
      // Server
      configureServer(server) {
        exports.builder.server = server;
        server.httpServer?.once("listening", () => {
          const scheme = server.config.server.https ? "https" : "http";
          const address = server.httpServer?.address();
          const host = address && typeof address !== "string" ? address.address : "localhost";
          const port = address && typeof address !== "string" ? address.port : 80;
          const url = `${scheme}://${host}:${port}/`;
          const serverFile = node_path.resolve(
            server.config.root,
            resolvedOptions.cliParams?.serverFile ?? "tmp/vite-server"
          );
          node_fs.writeFileSync(node_path.resolve(server.config.root, serverFile), url);
          if (!exitHandlersBound) {
            process.on("exit", () => {
              if (fs$1.existsSync(serverFile)) {
                fs$1.rmSync(serverFile);
              }
            });
            process.on("SIGINT", () => process.exit());
            process.on("SIGTERM", () => process.exit());
            process.on("SIGHUP", () => process.exit());
            exitHandlersBound = true;
          }
        });
        const fullReloadWatches = [];
        const customWatches = [];
        const checkReload = (event) => {
          return (path) => {
            for (const watchTask of customWatches) {
              if (watchTask.file === path) {
                const mods = server.moduleGraph.getModulesByFile(watchTask.moduleFile);
                if (mods) {
                  for (const mod of mods) {
                    server.moduleGraph.invalidateModule(mod);
                  }
                  const updateType = watchTask.updateType;
                  logger.info(
                    `${chalk.green(updateType)} ${chalk.dim(node_path.relative(process.cwd(), watchTask.file))}`,
                    { timestamp: true }
                  );
                  if (updateType === "full-reload") {
                    server.ws.send({ type: "full-reload", path: "*" });
                  } else {
                    server.ws.send({
                      type: "update",
                      updates: [...mods].map((m) => ({
                        type: updateType,
                        path: m.url,
                        acceptedPath: m.url,
                        timestamp: Date.now()
                      }))
                    });
                  }
                }
              }
            }
            if (micromatch.isMatch(path, fullReloadWatches)) {
              server.ws.send({ type: "full-reload", path: "*" });
              logger.info(
                `${chalk.green("full reload")} ${chalk.dim(node_path.relative(process.cwd(), path))}`,
                { timestamp: true }
              );
            }
          };
        };
        server.watcher.on("add", checkReload());
        server.watcher.on("change", checkReload());
        for (const watchTask of exports.builder.watches) {
          if (typeof watchTask === "string") {
            const file2 = node_path.resolve(watchTask).replace(/\\/g, "/");
            fullReloadWatches.push(file2);
            server.watcher.add(file2);
            continue;
          }
          const file = node_path.resolve(watchTask.file).replace(/\\/g, "/");
          customWatches.push(watchTask);
          server.watcher.add(file);
        }
      }
      // async handleHotUpdate(ctx) {
      //   if (builder.watches.includes(ctx.file)) {
      //     if (ctx.modules.length > 0) {
      //       return ctx.modules;
      //     }
      //
      //     const modules = ctx.server.moduleGraph.getModulesByFile(ctx.file);
      //
      //     if (modules) {
      //       return [...modules];
      //     }
      //
      //     // const resolved = await ctx.server.pluginContainer.resolveId(ctx.file);
      //     // if (resolved) {
      //     //   const vm = server.moduleGraph.getModuleById(resolved.id) || server.moduleGraph.getModuleById(virtualPrefixId)
      //     //   if (vm) {
      //     //     return [vm]
      //     //   }
      //     // }
      //
      //     ctx.server.ws.send({ type: 'full-reload', path: '*' })
      //
      //     return [];
      //   }
      // }
    },
    {
      name: "fusion:pre-handles",
      enforce: "pre",
      async resolveId(source, importer, options) {
        for (const resolveId of exports.builder.resolveIdCallbacks) {
          if (typeof resolveId !== "function") {
            continue;
          }
          const result = await resolveId.call(this, source, importer, options);
          if (result) {
            return result;
          }
        }
        if (source.startsWith("hidden:")) {
          return source;
        }
      },
      async load(source, options) {
        for (const load of exports.builder.loadCallbacks) {
          if (typeof load !== "function") {
            continue;
          }
          const result = await load.call(this, source, options);
          if (result) {
            return result;
          }
        }
        if (source.startsWith("hidden:")) {
          return "";
        }
      }
    },
    {
      name: "fusion:post-handles",
      generateBundle(options, bundle) {
        for (const [fileName, chunk] of Object.entries(bundle)) {
          if (chunk.type === "chunk" && chunk.facadeModuleId?.startsWith("hidden:")) {
            delete bundle[fileName];
          }
        }
      },
      async writeBundle(options, bundle) {
        const outDir2 = resolvedConfig.build.outDir || process.cwd();
        await moveFilesAndLog(exports.builder.moveTasks, outDir2, logger);
        await copyFilesAndLog(exports.builder.copyTasks, outDir2, logger);
        await linkFilesAndLog(exports.builder.linkTasks, outDir2, logger);
        for (const callback of exports.builder.postBuildCallbacks) {
          await callback(options, bundle);
        }
        for (const [name, task] of exports.builder.tasks) {
          for (const callback of task.postCallbacks) {
            await callback(options, bundle);
          }
        }
      }
    }
  ];
}
function prepareFusionOptions(options) {
  if (typeof options === "string") {
    return {
      fusionfile: options
    };
  }
  if (typeof options === "function") {
    return {
      fusionfile: options
    };
  }
  return options;
}
function configureBuilder(handler) {
  handler(exports.builder);
}
function overrideViteConfig(config) {
  if (config === null) {
    exports.builder.overrideConfig = {};
    return;
  }
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, config);
}
function overrideOptions(options) {
  return overrideFusionOptions = mergeOptions(overrideFusionOptions, options);
}
function outDir(outDir2) {
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, {
    build: {
      outDir: outDir2
    }
  });
}
function chunkDir(dir) {
  exports.builder.fusionOptions.chunkDir = dir;
}
function alias(src, dest) {
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, {
    resolve: {
      alias: {
        [src]: dest
      }
    }
  });
}
function externals(...externals2) {
  exports.builder.overrideConfig = vite.mergeConfig(exports.builder.overrideConfig, {
    build: {
      rollupOptions: {
        external: externals2
      }
    }
  });
}
function plugin(...plugins) {
  extraVitePlugins.push(...plugins);
}
function clean(...paths) {
  exports.builder.addCleans(...paths);
  exports.builder.cleans = lodashEs.uniq(exports.builder.cleans);
}
function fullReloads(...paths) {
  exports.builder.watches.push(...paths);
  exports.builder.watches = lodashEs.uniq(exports.builder.watches);
}
const index = {
  ...fusion,
  useFusion,
  configureBuilder,
  overrideViteConfig,
  overrideOptions,
  outDir,
  chunkDir,
  alias,
  externals,
  plugin,
  clean,
  fullReloads,
  params
};

exports.alias = alias;
exports.callback = callback;
exports.callbackAfterBuild = callbackAfterBuild;
exports.chunkDir = chunkDir;
exports.clean = clean;
exports.configureBuilder = configureBuilder;
exports.copy = copy;
exports.copyGlob = copyGlob;
exports.css = css;
exports.default = index;
exports.externals = externals;
exports.fileToId = fileToId;
exports.fullReloads = fullReloads;
exports.getGlobBaseFromPattern = getGlobBaseFromPattern;
exports.isDev = isDev;
exports.isProd = isProd;
exports.isWindows = isWindows;
exports.js = js;
exports.link = link;
exports.move = move;
exports.moveGlob = moveGlob;
exports.outDir = outDir;
exports.overrideOptions = overrideOptions;
exports.overrideViteConfig = overrideViteConfig;
exports.plugin = plugin;
exports.shortHash = shortHash;
exports.symlink = symlink;
exports.useFusion = useFusion;
//# sourceMappingURL=index.cjs.map
