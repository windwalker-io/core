import ot from "crypto";
import V from "fast-glob";
import h from "fs-extra";
import { randomBytes as it } from "node:crypto";
import { basename as x, parse as j, resolve as f, normalize as H, dirname as P, relative as m, isAbsolute as W } from "node:path";
import { inspect as rt } from "node:util";
import { mergeConfig as y } from "vite";
import { get as I, set as E, uniq as $ } from "lodash-es";
import at from "yargs";
import { build as lt } from "esbuild";
import G from "module";
import { writeFileSync as U, existsSync as b } from "node:fs";
import ut from "archy";
import w from "chalk";
import R from "fs";
import ct from "micromatch";
function g(e) {
  return Array.isArray(e) ? e : [e];
}
function B(e, t) {
  return Array.isArray(e) ? e.map(t) : t(e);
}
function q(e, t) {
  return e = g(e), e.map(t);
}
function ft(e, t, s = {}) {
  return new pt(e, t, s);
}
class pt {
  constructor(t, s, n = {}) {
    this.input = t, this.output = s, this.options = n;
  }
  config(t, s) {
    return q(this.input, (n) => {
      const o = s.addTask(n, t);
      return s.assetFileNamesCallbacks.push((r) => {
        const i = r.names[0];
        if (i && x(i, ".css") === o.id)
          return this.output ? o.normalizeOutput(this.output, ".css") : j(n).name + ".css";
      }), o;
    });
  }
  preview() {
    return g(this.input).map((t) => ({
      input: t,
      output: this.output || x(t),
      extra: {}
    }));
  }
}
function dt(e, t) {
  return new ht(e, t);
}
class ht {
  constructor(t, s) {
    this.input = t, this.output = s;
  }
  config(t, s) {
    return q(this.input, (n) => {
      const o = s.addTask(n, t);
      return s.entryFileNamesCallbacks.push((r) => {
        const i = r.name;
        if (i && i === o.id)
          return this.output ? o.normalizeOutput(this.output) : j(n).name + ".js";
      }), o;
    });
  }
  preview() {
    return g(this.input).map((t) => ({
      input: t,
      output: this.output || x(t),
      extra: {}
    }));
  }
}
function mt(e, t) {
  return new gt(e, t);
}
class gt {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    B(this.input, (n) => {
      s.moveTasks.push({ src: n, dest: this.dest, options: {} });
    });
  }
  preview() {
    return g(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function yt(e, t) {
  return new wt(e, t);
}
class wt {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    B(this.input, (n) => {
      s.copyTasks.push({ src: n, dest: this.dest, options: {} });
    });
  }
  preview() {
    return g(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function kt(e, t, s = {}) {
  return new vt(e, t, s);
}
class vt {
  constructor(t, s, n = {}) {
    this.input = t, this.dest = s, this.options = n;
  }
  config(t, s) {
    B(this.input, (n) => {
      s.linkTasks.push({ src: n, dest: this.dest, options: this.options });
    });
  }
  preview() {
    return g(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function bt(e) {
  return new J(e);
}
function Ct(e) {
  return new J(e, !0);
}
class J {
  constructor(t, s = !1) {
    this.handler = t, this.afterBuild = s;
  }
  config(t, s) {
    this.afterBuild ? s.postBuildCallbacks.push(() => this.handler(t, s)) : this.handler(t, s);
  }
  preview() {
    return [];
  }
}
let T;
function Ft(e) {
  return T = e, K = T?.verbose ? T?.verbose > 0 : !1, e;
}
let K = !1;
const Q = process.env.NODE_ENV === "production", Tt = !Q;
function S() {
  return process.platform === "win32";
}
function X(e, t = 8) {
  let s = ot.createHash("sha1").update(e).digest("hex");
  return t && t > 0 && (s = s.substring(0, t)), s;
}
function F(e, t, s) {
  const n = [];
  e = N(e, s.outDir), t = N(t, s.outDir);
  const o = Z(e), r = tt(e) ? V.globSync(e.replace(/\\/g, "/"), s.globOptions) : [e];
  for (let i of r) {
    let c, a = t;
    Dt(t) ? (c = a, a = a + m(o, i)) : c = P(a), h.ensureDirSync(c), n.push(s.handler(i, a));
  }
  return n;
}
function Pt(e, t, s) {
  const n = [];
  for (const { src: o, dest: r, options: i } of e) {
    const c = F(
      o,
      r,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Moving file from ${m(t, a)} to ${m(t, u)}`), h.move(a, u, { overwrite: !0 })),
        globOptions: { onlyFiles: !0 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function At(e, t, s) {
  const n = [];
  for (const { src: o, dest: r, options: i } of e) {
    const c = F(
      o,
      r,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Copy file from ${m(t, a)} to ${m(t, u)}`), h.copy(a, u, { overwrite: !0 })),
        globOptions: { onlyFiles: !0 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function Ot(e, t, s) {
  const n = [];
  for (const { src: o, dest: r, options: i } of e) {
    const c = F(
      o,
      r,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Link file from ${m(t, a)} to ${m(t, u)}`), Y(a, u, i?.force ?? !1)),
        globOptions: { onlyFiles: !1 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function xt(e, t) {
  const s = [];
  t = t.replace(/\\/g, "/");
  for (let n of e) {
    n = N(n, t), n = f(n);
    const o = tt(n) ? V.globSync(n.replace(/\\/g, "/"), { onlyFiles: !1 }) : [n], r = f(t + "/upload").replace(/\\/g, "/");
    for (let i of o) {
      if (i.replace(/\\/g, "/").startsWith(r))
        throw new Error("Refuse to delete `upload/*` folder.");
      s.push(h.remove(i));
    }
  }
  return Promise.all(s);
}
async function St(e, t) {
  const s = F(
    e,
    t,
    {
      outDir: process.cwd(),
      handler: async (n, o) => h.copy(n, o, { overwrite: !0 }),
      globOptions: { onlyFiles: !0 }
    }
  );
  await Promise.all(s);
}
async function Nt(e, t) {
  const s = F(
    e,
    t,
    {
      outDir: process.cwd(),
      handler: async (n, o) => h.move(n, o, { overwrite: !0 }),
      globOptions: { onlyFiles: !0 }
    }
  );
  await Promise.all(s);
}
async function Y(e, t, s = !1) {
  return e = f(e), t = f(t), S() && !h.lstatSync(e).isFile() ? h.ensureSymlink(e, t, "junction") : S() && h.lstatSync(e).isFile() && s ? h.ensureLink(e, t) : h.ensureSymlink(e, t);
}
function Dt(e) {
  return e.endsWith("/") || e.endsWith("\\");
}
function Z(e) {
  const t = ["*", "?", "[", "]"], s = [...e].findIndex((n) => t.includes(n));
  return s === -1 ? P(e) : P(e.slice(0, s + 1));
}
function tt(e) {
  return ["*", "?", "[", "]"].some((s) => e.includes(s));
}
function N(e, t) {
  return e.startsWith(".") ? e = f(e) : W(e) || (e = t + "/" + e), e;
}
function et(e, t) {
  return e = H(e), t ||= it(4).toString("hex"), t + "-" + X(e);
}
const jt = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  callback: bt,
  callbackAfterBuild: Ct,
  copy: yt,
  copyGlob: St,
  css: ft,
  fileToId: et,
  getGlobBaseFromPattern: Z,
  isDev: Tt,
  isProd: Q,
  get isVerbose() {
    return K;
  },
  isWindows: S,
  js: dt,
  link: kt,
  move: mt,
  moveGlob: Nt,
  get params() {
    return T;
  },
  shortHash: X,
  symlink: Y
}, Symbol.toStringTag, { value: "Module" }));
class M {
  constructor(t, s) {
    this.input = t, this.group = s, this.id = M.toFileId(t, s), this.input = H(t);
  }
  id;
  output;
  postCallbacks = [];
  dest(t) {
    return typeof t == "string" && (t = this.normalizeOutput(t)), this.output = t, this;
  }
  addPostCallback(t) {
    return this.postCallbacks.push(t), this;
  }
  normalizeOutput(t, s = ".js") {
    return (t.endsWith("/") || t.endsWith("\\")) && (t += j(this.input).name + s), t;
  }
  static toFileId(t, s) {
    return et(t, s);
  }
}
function Wt(e, ...t) {
  if (!t.length)
    return e;
  for (const s of t)
    s && (typeof s == "function" ? e = s(e) ?? e : e = y(e, s));
  return e;
}
function $t(e, t = 10) {
  console.log(rt(e, { depth: t, colors: !0 }));
}
class z {
  constructor(t, s, n) {
    this.config = t, this.env = s, this.fusionOptions = n, this.config = y(this.config, {
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
        emptyOutDir: !1,
        sourcemap: s.mode !== "production" ? "inline" : !1
      },
      plugins: [],
      css: {
        devSourcemap: !0
      },
      esbuild: {
        // Todo: Remove if esbuild supports decorators by default
        target: "es2022"
      }
    }), this.addTask("hidden:placeholder");
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
  resolveIdCallbacks = [];
  loadCallbacks = [];
  // fileNameMap: Record<string, string> = {};
  // externals: ((source: string, importer: string | undefined, isResolved: boolean) => boolean | string | NullValue)[] = [];
  watches = [];
  cleans = [];
  tasks = /* @__PURE__ */ new Map();
  merge(t) {
    return typeof t == "function" ? (this.config = t(this.config) ?? this.config, this) : (this.config = y(this.config, t), this);
  }
  getDefaultOutput() {
    return {
      entryFileNames: (t) => {
        const s = this.getChunkNameFromTask(t);
        if (s)
          return s;
        for (const n of this.entryFileNamesCallbacks) {
          const o = n(t);
          if (o)
            return o;
        }
        return "[name].js";
      },
      chunkFileNames: (t) => {
        const s = this.getChunkNameFromTask(t);
        if (s)
          return s;
        for (const o of this.chunkFileNamesCallbacks) {
          const r = o(t);
          if (r)
            return r;
        }
        return `${this.getChunkDir()}[name]-[hash].js`;
      },
      assetFileNames: (t) => {
        for (const s of this.assetFileNamesCallbacks) {
          const n = s(t);
          if (n)
            return n;
        }
        return "[name].[ext]";
      }
    };
  }
  getChunkDir() {
    let t = this.fusionOptions.chunkDir ?? "chunks";
    return t.replace(/\\/g, "/"), t && !t.endsWith("/") && (t += "/"), (t === "./" || t === "/") && (t = ""), t;
  }
  getChunkNameFromTask(t) {
    if (this.tasks.has(t.name)) {
      const s = this.tasks.get(t.name)?.output;
      if (s) {
        const n = typeof s == "function" ? s(t) : s;
        if (!W(n))
          return n;
      }
    }
  }
  ensurePath(t, s = {}) {
    return I(this.config, t) == null && E(this.config, t, s), this;
  }
  get(t) {
    return I(this.config, t);
  }
  set(t, s) {
    return E(this.config, t, s), this;
  }
  addTask(t, s) {
    const n = new M(t, s);
    this.tasks.set(n.id, n);
    const o = this.config.build.rollupOptions.input;
    return o[n.id] = n.input, n;
  }
  addCleans(...t) {
    return this.cleans.push(...t), this;
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
  relativePath(t) {
    return m(process.cwd(), t);
  }
  debug() {
    $t(this.config);
  }
}
function Bt(e) {
  return e ??= process.argv, e.slice(2).join(" ").split(" -- ").slice(1).join(" -- ").trim().split(" ").filter((t) => t !== "");
}
function Mt(e) {
  const t = at();
  return t.option("cwd", {
    type: "string",
    description: "Current working directory"
  }), t.option("list", {
    alias: "l",
    type: "boolean",
    description: "List all available tasks"
  }), t.option("config", {
    alias: "c",
    type: "string",
    description: "Path to config file"
  }), t.option("server-file", {
    alias: "s",
    type: "string",
    description: "Path to server file"
  }), t.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  }), t.parseSync(e);
}
async function _t(e) {
  let t = e.path;
  if (process.platform === "win32") {
    const s = t.replace(/\\/g, "/");
    s.startsWith("file://") || (t = `file:///${s}`);
  }
  if (e.ts) {
    const n = (await lt({
      entryPoints: [e.path],
      bundle: !0,
      write: !1,
      outdir: "dist",
      platform: "node",
      format: "cjs",
      target: "esnext",
      external: ["../dist", "../dist/*"],
      packages: "external",
      sourcemap: "inline"
    })).outputFiles[0], o = Buffer.from(n.contents).toString("utf8");
    U(n.path, o);
    const r = new G(n.path, void 0);
    return r.filename = n.path, r.paths = G._nodeModulePaths(P(n.path)), r._compile(o, n.path), A(r.exports);
  } else {
    const s = await import(t);
    return A(s);
  }
}
function A(e) {
  return e = { ...e }, e.__esModule && delete e.__esModule, e;
}
async function _(e, t = !1) {
  return e = await e, !t && Array.isArray(e) ? (await Promise.all(e.map((n) => _(n, !0)))).flat() : L(typeof e == "function" ? await e() : await e, e?.name);
}
async function L(e, t) {
  if (!Array.isArray(e))
    return [await e];
  const s = await Promise.all(e), n = [];
  for (const o of s)
    Array.isArray(o) ? n.push(...o) : n.push(o);
  return n;
}
function It(e, t) {
  const s = Et(e, t);
  if (!s)
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  return s;
}
function Et(e, t) {
  let s = t?.config;
  return s ? (W(s) || (s = f(e, s)), b(s) ? {
    path: s,
    // get filename from file path
    filename: s.split("/").pop() || "",
    type: Rt(s),
    ts: zt(s)
  } : null) : Gt(e);
}
function Gt(e) {
  let t = f(e, "fusionfile.js");
  return b(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "commonjs",
    ts: !1
  } : (t = f(e, "fusionfile.mjs"), b(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !1
  } : (t = f(e, "fusionfile.ts"), b(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : (t = f(e, "fusionfile.mts"), b(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : null)));
}
function Rt(e) {
  let t = "unknown";
  return e.endsWith(".cjs") ? t = "commonjs" : (e.endsWith(".mjs") || e.endsWith(".ts") || e.endsWith(".mts")) && (t = "module"), t;
}
function zt(e) {
  return e.endsWith(".ts") || e.endsWith(".mts");
}
async function Lt(e) {
  const t = Object.keys(e);
  t.sort((o, r) => o === "default" ? -1 : r === "default" ? 1 : o.localeCompare(r));
  const s = [];
  for (const o of t) {
    const r = e[o];
    s.push(await st(o, r));
  }
  const n = ut({
    label: w.magenta("Available Tasks"),
    nodes: s
  });
  console.log(n);
}
async function st(e, t) {
  const s = [];
  t = g(await t);
  for (let n of t) {
    const o = await _(n, !0);
    for (const r of o)
      typeof r == "function" ? s.push(
        await st(r.name, r)
      ) : s.push(...await Vt(r));
  }
  return {
    label: w.cyan(e),
    nodes: s
  };
}
async function Vt(e) {
  const t = await e.preview();
  return Promise.all(t.map((s) => Ht(s)));
}
async function Ht(e) {
  const t = [], { input: s, output: n, extra: o } = e, r = w.yellow(s);
  t.push(`Input: ${r}`);
  const i = w.green(n);
  return t.push(`Output: ${i}`), t.join(" - ");
}
function Ut(e, t) {
  e = $(e), e.length === 0 && e.push("default");
  const s = {};
  for (const n of e)
    if (t[n])
      s[n] = t[n];
    else
      throw new Error(`Task "${w.cyan(n)}" not found in fusion config.`);
  return s;
}
async function qt(e) {
  const t = {}, s = {};
  for (const n in e) {
    const o = e[n];
    s[n] = await D(n, o, t);
  }
  return s;
}
async function D(e, t, s) {
  const n = [];
  if (Array.isArray(t))
    for (const o in t) {
      const r = t[o];
      n.push(...await D(o, r, s));
    }
  else if (typeof t == "function") {
    if (e = t.name || e, s[e])
      return [];
    s[e] = t;
    const o = await _(t, !0);
    if (Array.isArray(o))
      for (const r in o) {
        const i = o[r];
        n.push(...await D(r, i, s));
      }
  } else
    n.push(await t);
  return n;
}
let d = Mt(Bt(process.argv));
Ft(d);
let l;
const Jt = d._, nt = [];
function Kt(e = {}, t) {
  let s, n, o = !1;
  const r = Qt(e);
  return typeof t == "string" || Array.isArray(t) && t.length > 0 ? d._ = g(t) : d._ = Jt, d = Wt(d, r.cliParams), [
    {
      name: "fusion",
      configResolved(i) {
        n = i, s = i.logger, i.plugins.push(...nt);
        for (const c of i.plugins)
          "buildConfig" in c && c.buildConfig?.(l);
      },
      async config(i, c) {
        let a;
        i.root ? a = f(i.root) : a = d.cwd || process.cwd(), delete i.root, process.chdir(a), l = new z(i, c, r);
        let u;
        if (typeof r.fusionfile == "string" || !r.fusionfile) {
          d.config ??= r.fusionfile;
          const v = It(a, d);
          u = await _t(v);
        } else typeof r.fusionfile == "function" ? u = A(await r.fusionfile()) : u = A(r.fusionfile);
        if (d.list) {
          await Lt(u);
          return;
        }
        const p = Ut([...d._], u), k = await qt(p);
        for (const v in k) {
          const O = k[v];
          for (const C of O)
            await C.config(v, l);
        }
        return l.merge(z.globalOverrideConfig), l.merge(l.overrideConfig), l.config;
      },
      outputOptions(i) {
        if (n.build.emptyOutDir) {
          const c = n.build.outDir, a = f(c, "upload");
          if (b(a))
            throw new Error(
              `The output directory: "${c}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
            );
        }
      },
      async buildStart(i) {
        l.cleans.length > 0 && await xt(l.cleans, n.build.outDir || process.cwd());
      },
      // Server
      configureServer(i) {
        i.httpServer?.once("listening", () => {
          const u = i.config.server.https ? "https" : "http", p = i.httpServer?.address(), k = p && typeof p != "string" ? p.address : "localhost", v = p && typeof p != "string" ? p.port : 80, O = `${u}://${k}:${v}/`, C = f(
            i.config.root,
            r.cliParams?.serverFile ?? "tmp/vite-server"
          );
          U(f(i.config.root, C), O), o || (process.on("exit", () => {
            R.existsSync(C) && R.rmSync(C);
          }), process.on("SIGINT", () => process.exit()), process.on("SIGTERM", () => process.exit()), process.on("SIGHUP", () => process.exit()), o = !0);
        });
        const c = l.watches.map((u) => f(u).replace(/\\/g, "/"));
        i.watcher.add(c);
        const a = (u) => {
          ct.isMatch(u, c) && (i.ws.send({ type: "full-reload", path: "*" }), s.info(
            `${w.green("full reload")} ${w.dim(m(process.cwd(), u))}`,
            { timestamp: !0 }
          ));
        };
        i.watcher.on("add", a), i.watcher.on("change", a);
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
      async resolveId(i, c, a) {
        for (const u of l.resolveIdCallbacks) {
          if (typeof u != "function")
            continue;
          const p = await u.call(this, i, c, a);
          if (p)
            return p;
        }
        if (i.startsWith("hidden:"))
          return i;
      },
      async load(i, c) {
        for (const a of l.loadCallbacks) {
          if (typeof a != "function")
            continue;
          const u = await a.call(this, i, c);
          if (u)
            return u;
        }
        if (i.startsWith("hidden:"))
          return "";
      }
    },
    {
      name: "fusion:post-handles",
      generateBundle(i, c) {
        for (const [a, u] of Object.entries(c))
          u.type === "chunk" && u.facadeModuleId?.startsWith("hidden:") && delete c[a];
      },
      async writeBundle(i, c) {
        const a = n.build.outDir || process.cwd();
        await Pt(l.moveTasks, a, s), await At(l.copyTasks, a, s), await Ot(l.linkTasks, a, s);
        for (const u of l.postBuildCallbacks)
          await u(i, c);
        for (const [u, p] of l.tasks)
          for (const k of p.postCallbacks)
            await k(i, c);
      }
    }
  ];
}
function Qt(e) {
  return typeof e == "string" ? {
    fusionfile: e
  } : typeof e == "function" ? {
    fusionfile: e
  } : e;
}
function Xt(e) {
  e(l);
}
function Yt(e) {
  if (e === null) {
    l.overrideConfig = {};
    return;
  }
  l.overrideConfig = y(l.overrideConfig, e);
}
function Zt(e) {
  l.overrideConfig = y(l.overrideConfig, {
    build: {
      outDir: e
    }
  });
}
function te(e) {
  l.fusionOptions.chunkDir = e;
}
function ee(e, t) {
  l.overrideConfig = y(l.overrideConfig, {
    resolve: {
      alias: {
        [e]: t
      }
    }
  });
}
function se(e, t) {
  const s = {};
  t && (s[e] = t), l.overrideConfig = y(l.overrideConfig, {
    build: {
      rollupOptions: {
        external: [e],
        output: {
          globals: s
        }
      }
    }
  });
}
function ne(...e) {
  nt.push(...e);
}
function oe(...e) {
  l.addCleans(...e), l.cleans = $(l.cleans);
}
function ie(...e) {
  l.watches.push(...e), l.watches = $(l.watches);
}
const Ce = {
  ...jt,
  useFusion: Kt,
  configureBuilder: Xt,
  mergeViteConfig: Yt,
  outDir: Zt,
  chunkDir: te,
  alias: ee,
  external: se,
  plugin: ne,
  clean: oe,
  fullReloads: ie,
  params: d
};
export {
  ee as alias,
  l as builder,
  bt as callback,
  Ct as callbackAfterBuild,
  te as chunkDir,
  oe as clean,
  Xt as configureBuilder,
  yt as copy,
  St as copyGlob,
  ft as css,
  Ce as default,
  se as external,
  et as fileToId,
  ie as fullReloads,
  Z as getGlobBaseFromPattern,
  Tt as isDev,
  Q as isProd,
  K as isVerbose,
  S as isWindows,
  dt as js,
  kt as link,
  Yt as mergeViteConfig,
  mt as move,
  Nt as moveGlob,
  Zt as outDir,
  T as params,
  ne as plugin,
  X as shortHash,
  Y as symlink,
  Kt as useFusion
};
//# sourceMappingURL=index.js.map
