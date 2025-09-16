import q from "crypto";
import { uniqueId as G, get as W, set as $, uniq as H } from "lodash-es";
import { basename as T, parse as O, relative as d, dirname as k, resolve as m, isAbsolute as N, normalize as M } from "node:path";
import { inspect as I } from "node:util";
import { mergeConfig as g } from "vite";
import J from "yargs";
import { build as U } from "esbuild";
import _ from "module";
import { writeFileSync as K, existsSync as y } from "node:fs";
import Q from "archy";
import w from "chalk";
import D from "fast-glob";
import p from "fs-extra";
function h(e) {
  return Array.isArray(e) ? e : [e];
}
function b(e, t) {
  return Array.isArray(e) ? e.map(t) : t(e);
}
function Vt(e, t, s = {}) {
  return new X(e, t, s);
}
class X {
  constructor(t, s, n = {}) {
    this.input = t, this.output = s, this.options = n;
  }
  async config(t, s) {
    b(this.input, (n) => {
      const i = s.addTask(n, t);
      s.assetFileNamesCallbacks.push((r) => {
        const o = r.names[0];
        if (o && T(o, ".css") === i.id)
          return this.output ? i.normalizeOutput(this.output, ".css") : O(n).name + ".css";
      });
    });
  }
  preview() {
    return h(this.input).map((t) => ({
      input: t,
      output: this.output || T(t),
      extra: {}
    }));
  }
}
function qt(e, t) {
  return new Y(e, t);
}
class Y {
  constructor(t, s) {
    this.input = t, this.output = s;
  }
  config(t, s) {
    b(this.input, (n) => {
      const i = s.addTask(n, t);
      s.entryFileNamesCallbacks.push((r) => {
        const o = r.name;
        if (o && o === i.id)
          return this.output ? i.normalizeOutput(this.output) : O(n).name + ".js";
      });
    });
  }
  preview() {
    return h(this.input).map((t) => ({
      input: t,
      output: this.output || T(t),
      extra: {}
    }));
  }
}
function Gt(e, t) {
  return new Z(e, t);
}
class Z {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    b(this.input, (n) => {
      s.moveTasks.push({ src: n, dest: this.dest, options: {} });
    });
  }
  preview() {
    return h(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function Ht(e, t) {
  return new tt(e, t);
}
class tt {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    b(this.input, (n) => {
      s.copyTasks.push({ src: n, dest: this.dest, options: {} });
    });
  }
  preview() {
    return h(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function It(e, t, s = {}) {
  return new et(e, t, s);
}
class et {
  constructor(t, s, n = {}) {
    this.input = t, this.dest = s, this.options = n;
  }
  config(t, s) {
    b(this.input, (n) => {
      s.linkTasks.push({ src: n, dest: this.dest, options: this.options });
    });
  }
  preview() {
    return h(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
let F;
function st(e) {
  return F = e, nt = F?.verbose ? F?.verbose > 0 : !1, e;
}
let nt = !1;
const it = process.env.NODE_ENV === "production", Jt = !it;
function L() {
  return process.platform === "win32";
}
function rt(e, t = 8) {
  let s = q.createHash("sha1").update(e).digest("hex");
  return t && t > 0 && (s = s.substring(0, t)), s;
}
function j(e, t, s) {
  const n = [];
  e = z(e, s.outDir), t = z(t, s.outDir);
  const i = ft(e), r = pt(e) ? D.globSync(D.convertPathToPattern(e), s.globOptions) : [e];
  for (let o of r) {
    let l, u = t;
    ct(t) ? (l = u, u = u + d(i, o)) : l = k(u), p.ensureDirSync(l), n.push(s.handler(o, u));
  }
  return n;
}
function ot(e, t, s) {
  const n = [];
  for (const { src: i, dest: r, options: o } of e) {
    const l = j(
      i,
      r,
      {
        outDir: t,
        handler: async (u, c) => (s.info(`Moving file from ${d(t, u)} to ${d(t, c)}`), p.move(u, c, { overwrite: !0 })),
        globOptions: { onlyFiles: !0 }
      }
    );
    n.push(...l);
  }
  return Promise.all(n);
}
function at(e, t, s) {
  const n = [];
  for (const { src: i, dest: r, options: o } of e) {
    const l = j(
      i,
      r,
      {
        outDir: t,
        handler: async (u, c) => (s.info(`Copy file from ${d(t, u)} to ${d(t, c)}`), p.copy(u, c, { overwrite: !0 })),
        globOptions: { onlyFiles: !0 }
      }
    );
    n.push(...l);
  }
  return Promise.all(n);
}
function ut(e, t, s) {
  const n = [];
  for (const { src: i, dest: r, options: o } of e) {
    const l = j(
      i,
      r,
      {
        outDir: t,
        handler: async (u, c) => (s.info(`Link file from ${d(t, u)} to ${d(t, c)}`), lt(u, c, o?.force ?? !1)),
        globOptions: { onlyFiles: !1 }
      }
    );
    n.push(...l);
  }
  return Promise.all(n);
}
async function lt(e, t, s = !1) {
  return L() && p.lstatSync(e).isDirectory() ? p.ensureSymlink(e, t, "junction") : L() && p.lstatSync(e).isFile() && s ? p.ensureLink(e, t) : p.ensureSymlink(e, t);
}
function ct(e) {
  return e.endsWith("/") || e.endsWith("\\");
}
function ft(e) {
  const t = ["*", "?", "[", "]"], s = [...e].findIndex((n) => t.includes(n));
  return s === -1 ? k(e) : k(e.slice(0, s + 1));
}
function pt(e) {
  return ["*", "?", "[", "]"].some((s) => e.includes(s));
}
function z(e, t) {
  return e.startsWith(".") ? e = m(e) : N(e) || (e = t + "/" + e), e;
}
class x {
  constructor(t, s) {
    this.input = t, this.group = s, this.id = x.toFileId(t, s), this.input = M(t);
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
    return (t.endsWith("/") || t.endsWith("\\")) && (t += O(this.input).name + s), t;
  }
  static toFileId(t, s) {
    return t = M(t), s ||= G(), s + "-" + rt(t);
  }
}
function dt(e, t = 10) {
  console.log(I(e, { depth: t, colors: !0 }));
}
class A {
  constructor(t, s, n) {
    this.config = t, this.env = s, this.params = n, this.config = g(this.config, {
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
  merge(t) {
    return typeof t == "function" ? (this.config = t(this.config) ?? this.config, this) : (this.config = g(this.config, t), this);
  }
  getDefaultOutput() {
    return {
      entryFileNames: (t) => {
        const s = this.getChunkNameFromTask(t);
        if (s)
          return s;
        for (const n of this.entryFileNamesCallbacks) {
          const i = n(t);
          if (i)
            return i;
        }
        return "[name].js";
      },
      chunkFileNames: (t) => {
        const s = this.getChunkNameFromTask(t);
        if (s)
          return s;
        for (const n of this.chunkFileNamesCallbacks) {
          const i = n(t);
          if (i)
            return i;
        }
        return "chunks/[name]-[hash].js";
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
  getChunkNameFromTask(t) {
    if (this.tasks.has(t.name)) {
      const s = this.tasks.get(t.name)?.output;
      if (s) {
        const n = typeof s == "function" ? s(t) : s;
        if (!N(n))
          return n;
      }
    }
  }
  ensurePath(t, s = {}) {
    return W(this.config, t) == null && $(this.config, t, s), this;
  }
  get(t) {
    return W(this.config, t);
  }
  set(t, s) {
    return $(this.config, t, s), this;
  }
  addTask(t, s) {
    const n = new x(t, s);
    this.tasks.set(n.id, n);
    const i = this.config.build.rollupOptions.input;
    return i[n.id] = n.input, n;
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
  addPlugin(t) {
    this.config.plugins?.push(t);
  }
  removePlugin(t) {
    this.config.plugins = this.config.plugins?.filter((s) => s ? typeof t == "string" && typeof s == "object" && "name" in s ? s.name !== t : typeof t == "object" && typeof s == "object" ? s !== t : !0 : !0);
  }
  relativePath(t) {
    return d(process.cwd(), t);
  }
  debug() {
    dt(this.config);
  }
}
function mt(e) {
  return e ??= process.argv, e.slice(2).join(" ").split(" -- ").slice(1).join(" -- ").trim().split(" ").filter((t) => t !== "");
}
function ht(e) {
  const t = J();
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
  }), t.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  }), t.parseSync(e);
}
async function gt(e) {
  let t = e.path;
  if (process.platform === "win32") {
    const s = t.replace(/\\/g, "/");
    s.startsWith("file://") || (t = `file:///${s}`);
  }
  if (e.ts) {
    const n = (await U({
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
    })).outputFiles[0], i = Buffer.from(n.contents).toString("utf8");
    K(n.path, i);
    const r = new _(n.path, void 0);
    return r.filename = n.path, r.paths = _._nodeModulePaths(k(n.path)), r._compile(i, n.path), C(r.exports);
  } else {
    const s = await import(t);
    return C(s);
  }
}
function C(e) {
  return e = { ...e }, e.__esModule && delete e.__esModule, e;
}
async function S(e, t = !1) {
  return e = await e, !t && Array.isArray(e) ? (await Promise.all(e.map((n) => S(n, !0)))).flat() : B(typeof e == "function" ? await e() : await e, e?.name);
}
async function B(e, t) {
  if (!Array.isArray(e))
    return [await e];
  const s = await Promise.all(e), n = [];
  for (const i of s)
    Array.isArray(i) ? n.push(...i) : n.push(i);
  return n;
}
function yt(e, t) {
  const s = wt(e, t);
  if (!s)
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  return s;
}
function wt(e, t) {
  let s = t?.config;
  return s ? (N(s) || (s = m(e, s)), y(s) ? {
    path: s,
    // get filename from file path
    filename: s.split("/").pop() || "",
    type: vt(s),
    ts: kt(s)
  } : null) : bt(e);
}
function bt(e) {
  let t = m(e, "fusionfile.js");
  return y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "commonjs",
    ts: !1
  } : (t = m(e, "fusionfile.mjs"), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !1
  } : (t = m(e, "fusionfile.ts"), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : (t = m(e, "fusionfile.mts"), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : null)));
}
function vt(e) {
  let t = "unknown";
  return e.endsWith(".cjs") ? t = "commonjs" : (e.endsWith(".mjs") || e.endsWith(".ts") || e.endsWith(".mts")) && (t = "module"), t;
}
function kt(e) {
  return e.endsWith(".ts") || e.endsWith(".mts");
}
async function Ct(e) {
  const t = Object.keys(e);
  t.sort((i, r) => i === "default" ? -1 : r === "default" ? 1 : i.localeCompare(r));
  const s = [];
  for (const i of t) {
    const r = e[i];
    s.push(await E(i, r));
  }
  const n = Q({
    label: w.magenta("Available Tasks"),
    nodes: s
  });
  console.log(n);
}
async function E(e, t) {
  const s = [];
  t = h(await t);
  for (let n of t) {
    const i = await S(n, !0);
    for (const r of i)
      typeof r == "function" ? s.push(
        await E(r.name, r)
      ) : s.push(...await Ft(r));
  }
  return {
    label: w.cyan(e),
    nodes: s
  };
}
async function Ft(e) {
  const t = await e.preview();
  return Promise.all(t.map((s) => Tt(s)));
}
async function Tt(e) {
  const t = [], { input: s, output: n, extra: i } = e, r = w.yellow(s);
  t.push(`Input: ${r}`);
  const o = w.green(n);
  return t.push(`Output: ${o}`), t.join(" - ");
}
function At(e, t) {
  e = H(e), e.length === 0 && e.push("default");
  const s = {};
  for (const n of e)
    if (t[n])
      s[n] = t[n];
    else
      throw new Error(`Task "${w.cyan(n)}" not found in fusion config.`);
  return s;
}
async function Pt(e) {
  const t = {}, s = {};
  for (const n in e) {
    const i = e[n];
    s[n] = await P(n, i, t);
  }
  return s;
}
async function P(e, t, s) {
  const n = [];
  if (Array.isArray(t))
    for (const i in t) {
      const r = t[i];
      n.push(...await P(i, r, s));
    }
  else if (typeof t == "function") {
    if (e = t.name || e, s[e])
      return [];
    s[e] = t;
    const i = await S(t, !0);
    if (Array.isArray(i))
      for (const r in i) {
        const o = i[r];
        n.push(...await P(r, o, s));
      }
  } else
    n.push(await t);
  return n;
}
const f = ht(mt(process.argv));
st(f);
let a;
const Ot = f._;
function Ut(e = {}, t) {
  let s;
  const n = Nt(e);
  return t !== void 0 || Array.isArray(t) && t.length > 0 ? f._ = h(t) : f._ = Ot, n.cwd !== void 0 && (f.cwd = n.cwd), [
    {
      name: "fusion",
      configResolved(i) {
        s = i.logger;
      },
      async config(i, r) {
        let o;
        i.root ? o = m(i.root) : o = f.cwd || process.cwd(), delete i.root, process.chdir(o), a = new A(i, r, f);
        let l;
        if (typeof n.fusionfile == "string" || !n.fusionfile) {
          f.config ??= n.fusionfile;
          const v = yt(o, f);
          l = await gt(v);
        } else typeof n.fusionfile == "function" ? l = C(await n.fusionfile()) : l = C(n.fusionfile);
        if (f.list) {
          await Ct(l);
          return;
        }
        const u = At([...f._], l), c = await Pt(u);
        for (const v in c) {
          const R = c[v];
          for (const V of R)
            await V.config(v, a);
        }
        return a.merge(A.globalOverrideConfig), a.merge(a.overrideConfig), Object.keys(a.config.build.rollupOptions.input)?.length === 0 && delete a.config.build.rollupOptions.input, a.config;
      }
    },
    {
      name: "fusion:post-handles",
      async writeBundle(i, r) {
        await ot(a.moveTasks, i.dir ?? process.cwd(), s), await at(a.copyTasks, i.dir ?? process.cwd(), s), await ut(a.linkTasks, i.dir ?? process.cwd(), s);
        for (const o of a.postBuildCallbacks)
          await o();
      }
    }
  ];
}
function Nt(e) {
  return typeof e == "string" ? {
    fusionfile: e
  } : typeof e == "function" ? {
    fusionfile: e
  } : e;
}
function Kt(e) {
  if (e === null) {
    a.overrideConfig = {};
    return;
  }
  a.overrideConfig = g(A.globalOverrideConfig, e);
}
function Qt(e) {
  a.overrideConfig = g(a.overrideConfig, {
    build: {
      outDir: e
    }
  });
}
function Xt(e, t) {
  a.overrideConfig = g(a.overrideConfig, {
    resolve: {
      alias: {
        [e]: t
      }
    }
  });
}
function Yt(e, t) {
  const s = {};
  t && (s[e] = t), a.overrideConfig = g(a.overrideConfig, {
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
export {
  Xt as alias,
  a as builder,
  Ht as copy,
  Vt as css,
  Yt as external,
  Jt as isDev,
  it as isProd,
  nt as isVerbose,
  L as isWindows,
  qt as js,
  It as link,
  Kt as mergeViteConfig,
  Gt as move,
  Qt as outDir,
  F as params,
  rt as shortHash,
  lt as symlink,
  Ut as useFusion
};
//# sourceMappingURL=index.js.map
