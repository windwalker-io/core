import { normalize, dirname, basename, isAbsolute, resolve as resolve$1, relative } from 'node:path';
import { merge, cloneDeep, uniqueId, get, set, uniq } from 'lodash-es';
import { inspect } from 'node:util';
import { resolve } from 'path';
import { mergeConfig } from 'vite';
import vuePlugin from '@vitejs/plugin-vue';
import Crypto from 'crypto';
import yargs from 'yargs';
import { build } from 'esbuild';
import Module from 'module';
import { writeFileSync, existsSync } from 'node:fs';
import archy from 'archy';
import chalk from 'chalk';
import { move } from 'fs-extra';

var MinifyOptions = /* @__PURE__ */ ((MinifyOptions2) => {
  MinifyOptions2["NONE"] = "none";
  MinifyOptions2["SAME_FILE"] = "same_file";
  MinifyOptions2["SEPARATE_FILE"] = "separate_file";
  return MinifyOptions2;
})(MinifyOptions || {});

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

function normalizeOutputs(output, defaultOptions = {}) {
  output = handleMaybeArray(output, (output2) => {
    if (typeof output2 === "string") {
      if (output2.endsWith("/")) {
        output2 = {
          dir: output2,
          ...defaultOptions
        };
      } else {
        output2 = {
          dir: dirname(output2),
          // Get file name with node library, consider Windows
          entryFileNames: normalize(output2).replace(/\\/g, "/").split("/").pop(),
          ...defaultOptions
        };
      }
    }
    return output2;
  });
  return forceArray(output);
}

function mergeOptions(base, ...overrides) {
  base ??= {};
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
      base = merge(base, override);
    }
  }
  return base;
}
function appendMinFileName(output) {
  output = cloneDeep(output);
  if (output.file) {
    const parts = output.file.split(".");
    const ext = parts.pop();
    output.file = `${parts.join(".")}.min.${ext}`;
  } else if (output.dir && typeof output.entryFileNames === "string") {
    const parts = output.entryFileNames.split(".");
    const ext = parts.pop();
    output.entryFileNames = `${parts.join(".")}.min.${ext}`;
  }
  return output;
}
function show(data, depth = 10) {
  console.log(inspect(data, { depth: null, colors: true }));
}

function createViteLibOptions(input, extraOptions) {
  return mergeOptions(
    {
      entry: input
    },
    extraOptions
  );
}
function createViteOptions(lib, output, plugins = [], override) {
  return mergeOptions(
    {
      resolve: {},
      build: {
        lib,
        rollupOptions: {
          output
        },
        emptyOutDir: false,
        target: "esnext"
      },
      plugins
    },
    override
  );
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
          const name2 = task.normalizeOutput(this.output);
          if (!isAbsolute(name2)) {
            return name2;
          } else {
            builder.moveFilesMap[task.id + ".css"] = name2;
          }
        }
      });
    });
  }
  preview() {
    return forceArray(this.input).map((input) => {
      return {
        input,
        output: this.output,
        extra: {}
      };
    });
  }
}

async function js(input, output, options = {}) {
  return useJsProcessor(
    output,
    options,
    (output2, isMinify) => {
      if (isMinify) {
        return createViteOptions(
          createViteLibOptions(input),
          output2,
          [],
          (config) => {
            return overrideViteJsOptions(config, options);
          }
        );
      }
      return createViteOptions(
        createViteLibOptions(input),
        output2,
        [],
        (config) => {
          return overrideViteJsOptions(config, options);
        }
      );
    }
  );
}
function useJsProcessor(output, options, createOptions) {
  options.verbose ??= isVerbose;
  const outputs = normalizeOutputs(output, { format: options?.format || "es" });
  for (const output2 of outputs) {
    if (output2.format === "umd") {
      output2.name = options?.umdName;
    }
  }
  const all = [];
  const opt = createOptions(outputs, false);
  all.push(mergeOptions(opt, options.vite));
  if (options?.minify === MinifyOptions.SEPARATE_FILE) {
    const minOutputs = outputs.map((output2) => {
      return appendMinFileName(output2);
    });
    const minOptions = createOptions(minOutputs, true);
    all.push(mergeOptions(minOptions, options?.vite));
  }
  return all;
}
function overrideViteJsOptions(config, options) {
  const esbuild = mergeOptions(
    {
      target: options?.target || "esnext"
    },
    options?.esbuild
  );
  config.build.minify = options?.minify === MinifyOptions.SAME_FILE ? "esbuild" : false;
  config.build.emptyOutDir = options.clean || false;
  config.build.target = options.target || "esnext";
  config.esbuild = esbuild;
  config = addExternals(config, options.externals);
  if (options.path) {
    config = mergeConfig(config, { resolve: { alias: {} } });
    if (typeof options.path === "string") {
      config.resolve.alias = {
        "@": resolve(options.path)
      };
    } else {
      const aliases = {};
      for (const alias in options.path) {
        aliases[alias] = resolve(options.path[alias]);
      }
      config.resolve.alias = aliases;
    }
  }
  return config;
}
function addExternals(config, externals) {
  if (!externals) {
    return config;
  }
  config = mergeConfig(config, { build: { rollupOptions: { external: [] } } });
  if (!Array.isArray(config.build.rollupOptions.external)) {
    throw new Error("Only array externals are supported now.");
  }
  for (const ext in externals) {
    if (!config.build.rollupOptions.external.includes(ext)) {
      config.build.rollupOptions.external.push(ext);
    }
  }
  config.build.rollupOptions.output = handleMaybeArray(config.build.rollupOptions.output, (output) => {
    output.globals = {
      ...output.globals,
      ...externals
    };
    return output;
  });
  return config;
}

async function vue(input, output, options = {}) {
  return useJsProcessor(
    output,
    options,
    (output2, isMinify) => {
      return createViteOptions(
        createViteLibOptions(input),
        output2,
        [
          vuePlugin()
        ],
        (config) => {
          config = overrideViteJsOptions(config, options);
          config.build.sourcemap = isDev ? "inline" : false;
          return config;
        }
      );
    }
  );
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
  MinifyOptions,
  css,
  isDev,
  isProd,
  get isVerbose () { return isVerbose; },
  js,
  get params () { return params$1; },
  vue
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
  normalizeOutput(output) {
    if (output.endsWith("/") || output.endsWith("\\")) {
      output += basename(this.input);
    }
    if (output.startsWith(".")) {
      output = resolve$1(output);
    }
    return output;
  }
  static toFileId(input, group) {
    input = normalize(input);
    group ||= uniqueId();
    return group + "-" + shortHash(input);
  }
}

class ConfigBuilder {
  constructor(config, env, params) {
    this.config = config;
    this.env = env;
    this.params = params;
    this.config = mergeConfig(ConfigBuilder.defaultConfig, this.config);
    this.config = mergeConfig(this.config, {
      build: {
        rollupOptions: {
          input: {},
          output: this.getDefaultOutput()
        }
      },
      plugins: []
    });
  }
  static defaultConfig = {};
  entryFileNamesCallbacks = [];
  chunkFileNamesCallbacks = [];
  assetFileNamesCallbacks = [];
  moveFilesMap = {};
  copyFilesMap = {};
  deleteFilesMap = {};
  postBuildCallbacks = [];
  // fileNameMap: Record<string, string> = {};
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
        return "[name].[ext]";
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
    const modules = { ...m.exports };
    delete modules.__esModule;
    return { ...modules };
  } else {
    const modules = await import(path);
    return { ...modules };
  }
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
      found = resolve$1(root, found);
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
  let file = resolve$1(root, "fusionfile.js");
  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "commonjs",
      ts: false
    };
  }
  file = resolve$1(root, "fusionfile.mjs");
  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: false
    };
  }
  file = resolve$1(root, "fusionfile.ts");
  if (existsSync(file)) {
    return {
      path: file,
      // get filename from file path
      filename: file.split("/").pop() || "",
      type: "module",
      ts: true
    };
  }
  file = resolve$1(root, "fusionfile.mts");
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
  return argv.slice(2).join(" ").split("--").slice(1).join("--").trim().split(" ");
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

function moveFilesAndLog(files, outDir) {
  const promises = [];
  for (let src in files) {
    let dest = files[src];
    src = normalizeFilePath(src, outDir);
    dest = normalizeFilePath(dest, outDir);
    console.log(`Moving file from ${relative(outDir, src)} to ${relative(outDir, dest)}`);
    promises.push(move(src, dest));
  }
  return Promise.all(promises);
}
function normalizeFilePath(path, outDir) {
  if (path.startsWith(".")) {
    path = resolve$1(path);
  } else if (!isAbsolute(path)) {
    path = outDir + "/" + path;
  }
  return path;
}

const params = parseArgv(getArgsAfterDoubleDashes(process.argv));
prepareParams(params);
function useFusion(options = {}) {
  let builder;
  return {
    name: "fusion",
    async config(config, env) {
      let root;
      if (config.root) {
        root = resolve$1(config.root);
      } else {
        root = params.cwd || process.cwd();
      }
      delete config.root;
      process.chdir(root);
      builder = new ConfigBuilder(config, env, params);
      let tasks;
      if (typeof options.fusionfile !== "object") {
        params.config ??= options.fusionfile;
        const configFile = mustGetAvailableConfigFile(root, params);
        tasks = await loadConfigFile(configFile);
      } else {
        tasks = { ...options.fusionfile };
      }
      if (params.list) {
        await displayAvailableTasks(tasks);
        return;
      }
      const selectedTasks = selectRunningTasks([...params._], tasks);
      const runningTasks = await resolveAllTasksAsProcessors(selectedTasks);
      for (const taskName in runningTasks) {
        const processors = runningTasks[taskName];
        for (const processor of processors) {
          await processor.config(taskName, builder);
        }
      }
      console.log("plugin bottom", builder.config);
      return builder.config;
    },
    async writeBundle(options2, bundle) {
      await moveFilesAndLog(builder.moveFilesMap, options2.dir ?? process.cwd());
    }
  };
}
function mergeViteConfig(config) {
  ConfigBuilder.defaultConfig = mergeConfig(ConfigBuilder.defaultConfig, config);
}
function outDir(outDir2) {
  ConfigBuilder.defaultConfig = mergeConfig(ConfigBuilder.defaultConfig, {
    build: {
      outDir: outDir2
    }
  });
}

export { MinifyOptions, css, fusion as default, isDev, isProd, isVerbose, js, mergeViteConfig, outDir, params$1 as params, useFusion, vue };
//# sourceMappingURL=index.js.map
