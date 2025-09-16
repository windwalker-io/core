import { basename, parse, normalize, isAbsolute, relative, dirname, resolve } from 'node:path';
import Crypto from 'crypto';
import { uniqueId, get, set, uniq } from 'lodash-es';
import { inspect } from 'node:util';
import { mergeConfig } from 'vite';
import yargs from 'yargs';
import { build } from 'esbuild';
import Module from 'module';
import { writeFileSync, existsSync } from 'node:fs';
import archy from 'archy';
import chalk from 'chalk';
import fg from 'fast-glob';
import fs from 'fs-extra';

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

function css(input, output, options = {}) {
  return new CssProcessor(input, output, options);
}
class CssProcessor {
  constructor(input, output, options = {}) {
    this.input = input;
    this.output = output;
    this.options = options;
  }
  async config(taskName, builder) {
    handleMaybeArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);
      builder.assetFileNamesCallbacks.push((assetInfo) => {
        const name = assetInfo.names[0];
        if (!name) {
          return void 0;
        }
        if (basename(name, ".css") === task.id) {
          if (!this.output) {
            return parse(input).name + ".css";
          }
          return task.normalizeOutput(this.output, ".css");
        }
      });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || basename(input),
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
    handleMaybeArray(this.input, (input) => {
      const task = builder.addTask(input, taskName);
      builder.entryFileNamesCallbacks.push((chunkInfo) => {
        const name = chunkInfo.name;
        if (!name) {
          return;
        }
        if (name === task.id) {
          if (!this.output) {
            return parse(input).name + ".js";
          }
          return task.normalizeOutput(this.output);
        }
      });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output || basename(input),
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

let params$1 = void 0;
function prepareParams(p) {
  params$1 = p;
  isVerbose = params$1?.verbose ? params$1?.verbose > 0 : false;
  return p;
}
let isVerbose = false;
const isProd = process.env.NODE_ENV === "production";
const isDev = !isProd;

const fusion = /*#__PURE__*/Object.freeze(/*#__PURE__*/Object.defineProperty({
  __proto__: null,
  copy,
  css,
  isDev,
  isProd,
  get isVerbose () { return isVerbose; },
  js,
  link,
  move,
  get params () { return params$1; }
}, Symbol.toStringTag, { value: 'Module' }));

function shortHash(bufferOrString, short = 8) {
  let hash = Crypto.createHash("sha1").update(bufferOrString).digest("hex");
  if (short && short > 0) {
    hash = hash.substring(0, short);
  }
  return hash;
}

class BuildTask {
  constructor(input, group) {
    this.input = input;
    this.group = group;
    this.id = BuildTask.toFileId(input, group);
    this.input = normalize(input);
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
      output += parse(this.input).name + ext;
    }
    return output;
  }
  static toFileId(input, group) {
    input = normalize(input);
    group ||= uniqueId();
    return group + "-" + shortHash(input);
  }
}

function show(data, depth = 10) {
  console.log(inspect(data, { depth, colors: true }));
}

class ConfigBuilder {
  constructor(config, env, params) {
    this.config = config;
    this.env = env;
    this.params = params;
    this.config = mergeConfig(this.config, {
      build: {
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
    });
  }
  static globalOverrideConfig = {};
  overrideConfig = {};
  entryFileNamesCallbacks = [];
  chunkFileNamesCallbacks = [];
  assetFileNamesCallbacks = [];
  moveTasks = [];
  copyTasks = [];
  linkTasks = [];
  postBuildCallbacks = [];
  // fileNameMap: Record<string, string> = {};
  // externals: ((source: string, importer: string | undefined, isResolved: boolean) => boolean | string | NullValue)[] = [];
  cleans = [];
  tasks = /* @__PURE__ */ new Map();
  merge(override) {
    if (typeof override === "function") {
      this.config = override(this.config) ?? this.config;
      return this;
    }
    this.config = mergeConfig(this.config, override);
    return this;
  }
  getDefaultOutput() {
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
        return "chunks/[name]-[hash].js";
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
  getChunkNameFromTask(chunkInfo) {
    if (this.tasks.has(chunkInfo.name)) {
      const output = this.tasks.get(chunkInfo.name)?.output;
      if (output) {
        const name = typeof output === "function" ? output(chunkInfo) : output;
        if (!isAbsolute(name)) {
          return name;
        }
      }
    }
    return void 0;
  }
  ensurePath(path, def = {}) {
    if (get(this.config, path) == null) {
      set(this.config, path, def);
    }
    return this;
  }
  get(path) {
    return get(this.config, path);
  }
  set(path, value) {
    set(this.config, path, value);
    return this;
  }
  addTask(input, group) {
    const task = new BuildTask(input, group);
    this.tasks.set(task.id, task);
    const inputOptions = this.config.build.rollupOptions.input;
    inputOptions[task.id] = task.input;
    return task;
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
  addPlugin(plugin) {
    this.config.plugins?.push(plugin);
  }
  removePlugin(plugin) {
    this.config.plugins = this.config.plugins?.filter((p) => {
      if (!p) {
        return true;
      }
      if (typeof plugin === "string" && typeof p === "object" && "name" in p) {
        return p.name !== plugin;
      } else if (typeof plugin === "object" && typeof p === "object") {
        return p !== plugin;
      }
      return true;
    });
  }
  relativePath(to) {
    return relative(process.cwd(), to);
  }
  debug() {
    show(this.config);
  }
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
    const buildResult = await build({
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
    writeFileSync(output.path, code);
    const m = new Module(output.path, void 0);
    m.filename = output.path;
    m.paths = Module._nodeModulePaths(dirname(output.path));
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
    if (!isAbsolute(found)) {
      found = resolve(root, found);
    }
    if (existsSync(found)) {
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
  let file = resolve(root, "fusionfile.js");
  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "commonjs",
      ts: false
    };
  }
  file = resolve(root, "fusionfile.mjs");
  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: false
    };
  }
  file = resolve(root, "fusionfile.ts");
  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: true
    };
  }
  file = resolve(root, "fusionfile.mts");
  if (existsSync(file)) {
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
  input = uniq(input);
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
  app.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  });
  return app.parseSync(argv);
}

function isWindows() {
  return process.platform === "win32";
}

function handleFilesOperation(src, dest, options) {
  const promises = [];
  src = normalizeFilePath(src, options.outDir);
  dest = normalizeFilePath(dest, options.outDir);
  const base = getBaseFromPattern(src);
  const sources = isGlob(src) ? fg.globSync(fg.convertPathToPattern(src), options.globOptions) : [src];
  for (let source of sources) {
    let dir;
    let resolvedDest = dest;
    if (endsWithSlash(dest)) {
      dir = resolvedDest;
      resolvedDest = resolvedDest + relative(base, source);
    } else {
      dir = dirname(resolvedDest);
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
          logger.info(`Moving file from ${relative(outDir, src2)} to ${relative(outDir, dest2)}`);
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
          logger.info(`Copy file from ${relative(outDir, src2)} to ${relative(outDir, dest2)}`);
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
          logger.info(`Link file from ${relative(outDir, src2)} to ${relative(outDir, dest2)}`);
          return symlink(src2, dest2, options?.force ?? false);
        },
        globOptions: { onlyFiles: false }
      }
    );
    promises.push(...ps);
  }
  return Promise.all(promises);
}
async function symlink(target, link, force = false) {
  if (isWindows() && fs.lstatSync(target).isDirectory()) {
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
function getBaseFromPattern(pattern) {
  const specialChars = ["*", "?", "[", "]"];
  const idx = [...pattern].findIndex((c) => specialChars.includes(c));
  if (idx === -1) {
    return dirname(pattern);
  }
  return dirname(pattern.slice(0, idx + 1));
}
function isGlob(pattern) {
  const specialChars = ["*", "?", "[", "]"];
  return specialChars.some((c) => pattern.includes(c));
}
function normalizeFilePath(path, outDir) {
  if (path.startsWith(".")) {
    path = resolve(path);
  } else if (!isAbsolute(path)) {
    path = outDir + "/" + path;
  }
  return path;
}

const params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);
let builder;
const originalTasks = params._;
function useFusion(fusionOptions = {}, tasks) {
  let logger;
  const options = prepareFusionOptions(fusionOptions);
  if (tasks !== void 0 || Array.isArray(tasks) && tasks.length > 0) {
    params._ = forceArray(tasks);
  } else {
    params._ = originalTasks;
  }
  if (options.cwd !== void 0) {
    params.cwd = options.cwd;
  }
  return [
    {
      name: "fusion",
      configResolved(config) {
        logger = config.logger;
      },
      async config(config, env) {
        let root;
        if (config.root) {
          root = resolve(config.root);
        } else {
          root = params.cwd || process.cwd();
        }
        delete config.root;
        process.chdir(root);
        builder = new ConfigBuilder(config, env, params);
        let tasks2;
        if (typeof options.fusionfile === "string" || !options.fusionfile) {
          params.config ??= options.fusionfile;
          const configFile = mustGetAvailableConfigFile(root, params);
          tasks2 = await loadConfigFile(configFile);
        } else if (typeof options.fusionfile === "function") {
          tasks2 = expandModules(await options.fusionfile());
        } else {
          tasks2 = expandModules(options.fusionfile);
        }
        if (params.list) {
          await displayAvailableTasks(tasks2);
          return;
        }
        const selectedTasks = selectRunningTasks([...params._], tasks2);
        const runningTasks = await resolveAllTasksAsProcessors(selectedTasks);
        for (const taskName in runningTasks) {
          const processors = runningTasks[taskName];
          for (const processor of processors) {
            await processor.config(taskName, builder);
          }
        }
        builder.merge(ConfigBuilder.globalOverrideConfig);
        builder.merge(builder.overrideConfig);
        if (Object.keys(builder.config.build.rollupOptions.input)?.length === 0) {
          delete builder.config.build.rollupOptions.input;
        }
        show(builder.config, 15);
        return builder.config;
      },
      closeBundle(error) {
      }
    },
    {
      name: "fusion:file-handles",
      async writeBundle(options2, bundle) {
        await moveFilesAndLog(builder.moveTasks, options2.dir ?? process.cwd(), logger);
        await copyFilesAndLog(builder.copyTasks, options2.dir ?? process.cwd(), logger);
        await linkFilesAndLog(builder.linkTasks, options2.dir ?? process.cwd(), logger);
        for (const callback of builder.postBuildCallbacks) {
          await callback();
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
function mergeViteConfig(config) {
  if (config === null) {
    builder.overrideConfig = {};
    return;
  }
  builder.overrideConfig = mergeConfig(ConfigBuilder.globalOverrideConfig, config);
}
function outDir(outDir2) {
  builder.overrideConfig = mergeConfig(builder.overrideConfig, {
    build: {
      outDir: outDir2
    }
  });
}
function alias(src, dest) {
  builder.overrideConfig = mergeConfig(builder.overrideConfig, {
    resolve: {
      alias: {
        [src]: dest
      }
    }
  });
}
function external(match, varName) {
  const globals = {};
  if (varName) {
    globals[match] = varName;
  }
  builder.overrideConfig = mergeConfig(builder.overrideConfig, {
    build: {
      rollupOptions: {
        external: [match],
        output: {
          globals
        }
      }
    }
  });
}

export { alias, builder, copy, css, fusion as default, external, isDev, isProd, isVerbose, js, link, mergeViteConfig, move, outDir, params$1 as params, useFusion };
//# sourceMappingURL=index.js.map
