import nt from "crypto";
import G from "fast-glob";
import d from "fs-extra";
import { randomBytes as ot } from "node:crypto";
import { basename as A, parse as N, normalize as L, resolve as p, relative as g, dirname as T, isAbsolute as D } from "node:path";
import { inspect as it } from "node:util";
import { mergeConfig as w } from "vite";
import { get as B, set as _, uniq as R } from "lodash-es";
import rt from "yargs";
import { build as at } from "esbuild";
import M from "module";
import { writeFileSync as V, existsSync as k } from "node:fs";
import lt from "archy";
import b from "chalk";
import I from "fs";
function y(e) {
  return Array.isArray(e) ? e : [e];
}
function j(e, t) {
  return Array.isArray(e) ? e.map(t) : t(e);
}
function H(e, t) {
  return e = y(e), e.map(t);
}
function ut(e, t, s = {}) {
  return new ct(e, t, s);
}
class ct {
  constructor(t, s, n = {}) {
    this.input = t, this.output = s, this.options = n;
  }
  config(t, s) {
    return H(this.input, (n) => {
      const o = s.addTask(n, t);
      return s.assetFileNamesCallbacks.push((i) => {
        const r = i.names[0];
        if (r && A(r, ".css") === o.id)
          return this.output ? o.normalizeOutput(this.output, ".css") : N(n).name + ".css";
      }), o;
    });
  }
  preview() {
    return y(this.input).map((t) => ({
      input: t,
      output: this.output || A(t),
      extra: {}
    }));
  }
}
function ft(e, t) {
  return new pt(e, t);
}
class pt {
  constructor(t, s) {
    this.input = t, this.output = s;
  }
  config(t, s) {
    return H(this.input, (n) => {
      const o = s.addTask(n, t);
      return s.entryFileNamesCallbacks.push((i) => {
        const r = i.name;
        if (r && r === o.id)
          return this.output ? o.normalizeOutput(this.output) : N(n).name + ".js";
      }), o;
    });
  }
  preview() {
    return y(this.input).map((t) => ({
      input: t,
      output: this.output || A(t),
      extra: {}
    }));
  }
}
function dt(e, t) {
  return new ht(e, t);
}
class ht {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    j(this.input, (n) => {
      s.moveTasks.push({ src: n, dest: this.dest, options: {} });
    });
  }
  preview() {
    return y(this.input).map((t) => ({
      input: t,
      output: this.dest,
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
    j(this.input, (n) => {
      s.copyTasks.push({ src: n, dest: this.dest, options: {} });
    });
  }
  preview() {
    return y(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function yt(e, t, s = {}) {
  return new wt(e, t, s);
}
class wt {
  constructor(t, s, n = {}) {
    this.input = t, this.dest = s, this.options = n;
  }
  config(t, s) {
    j(this.input, (n) => {
      s.linkTasks.push({ src: n, dest: this.dest, options: this.options });
    });
  }
  preview() {
    return y(this.input).map((t) => ({
      input: t,
      output: this.dest,
      extra: {}
    }));
  }
}
function vt(e) {
  return new U(e);
}
function kt(e) {
  return new U(e, !0);
}
class U {
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
let F;
function bt(e) {
  return F = e, q = F?.verbose ? F?.verbose > 0 : !1, e;
}
let q = !1;
const J = process.env.NODE_ENV === "production", Ct = !J;
function O() {
  return process.platform === "win32";
}
function K(e, t = 8) {
  let s = nt.createHash("sha1").update(e).digest("hex");
  return t && t > 0 && (s = s.substring(0, t)), s;
}
function C(e, t, s) {
  const n = [];
  e = x(e, s.outDir), t = x(t, s.outDir);
  const o = Nt(e), i = X(e) ? G.globSync(e.replace(/\\/g, "/"), s.globOptions) : [e];
  for (let r of i) {
    let c, a = t;
    St(t) ? (c = a, a = a + g(o, r)) : c = T(a), d.ensureDirSync(c), n.push(s.handler(r, a));
  }
  return n;
}
function Ft(e, t, s) {
  const n = [];
  for (const { src: o, dest: i, options: r } of e) {
    const c = C(
      o,
      i,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Moving file from ${g(t, a)} to ${g(t, u)}`), d.move(a, u, { overwrite: !0 })),
        globOptions: { onlyFiles: !0 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function Tt(e, t, s) {
  const n = [];
  for (const { src: o, dest: i, options: r } of e) {
    const c = C(
      o,
      i,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Copy file from ${g(t, a)} to ${g(t, u)}`), d.copy(a, u, { overwrite: !0 })),
        globOptions: { onlyFiles: !0 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function Pt(e, t, s) {
  const n = [];
  for (const { src: o, dest: i, options: r } of e) {
    const c = C(
      o,
      i,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Link file from ${g(t, a)} to ${g(t, u)}`), Q(a, u, r?.force ?? !1)),
        globOptions: { onlyFiles: !1 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function At(e, t) {
  const s = [];
  t = t.replace(/\\/g, "/");
  for (let n of e) {
    n = x(n, t), n = p(n);
    const o = X(n) ? G.globSync(n.replace(/\\/g, "/"), { onlyFiles: !1 }) : [n], i = p(t + "/upload").replace(/\\/g, "/");
    for (let r of o) {
      if (r.replace(/\\/g, "/").startsWith(i))
        throw new Error("Refuse to delete `upload/*` folder.");
      s.push(d.remove(r));
    }
  }
  return Promise.all(s);
}
async function Ot(e, t) {
  const s = C(
    e,
    t,
    {
      outDir: process.cwd(),
      handler: async (n, o) => d.copy(n, o, { overwrite: !0 }),
      globOptions: { onlyFiles: !0 }
    }
  );
  await Promise.all(s);
}
async function xt(e, t) {
  const s = C(
    e,
    t,
    {
      outDir: process.cwd(),
      handler: async (n, o) => d.move(n, o, { overwrite: !0 }),
      globOptions: { onlyFiles: !0 }
    }
  );
  await Promise.all(s);
}
async function Q(e, t, s = !1) {
  return O() && !d.lstatSync(e).isFile() ? d.ensureSymlink(e, t, "junction") : O() && d.lstatSync(e).isFile() && s ? d.ensureLink(e, t) : d.ensureSymlink(e, t);
}
function St(e) {
  return e.endsWith("/") || e.endsWith("\\");
}
function Nt(e) {
  const t = ["*", "?", "[", "]"], s = [...e].findIndex((n) => t.includes(n));
  return s === -1 ? T(e) : T(e.slice(0, s + 1));
}
function X(e) {
  return ["*", "?", "[", "]"].some((s) => e.includes(s));
}
function x(e, t) {
  return e.startsWith(".") ? e = p(e) : D(e) || (e = t + "/" + e), e;
}
function Y(e, t) {
  return e = L(e), t ||= ot(4).toString("hex"), t + "-" + K(e);
}
const Dt = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  callback: vt,
  callbackAfterBuild: kt,
  copy: mt,
  copyGlob: Ot,
  css: ut,
  fileToId: Y,
  isDev: Ct,
  isProd: J,
  get isVerbose() {
    return q;
  },
  isWindows: O,
  js: ft,
  link: yt,
  move: dt,
  moveGlob: xt,
  get params() {
    return F;
  },
  shortHash: K,
  symlink: Q
}, Symbol.toStringTag, { value: "Module" }));
class W {
  constructor(t, s) {
    this.input = t, this.group = s, this.id = W.toFileId(t, s), this.input = L(t);
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
    return (t.endsWith("/") || t.endsWith("\\")) && (t += N(this.input).name + s), t;
  }
  static toFileId(t, s) {
    return Y(t, s);
  }
}
function jt(e, ...t) {
  if (!t.length)
    return e;
  for (const s of t)
    s && (typeof s == "function" ? e = s(e) ?? e : e = w(e, s));
  return e;
}
function Wt(e, t = 10) {
  console.log(it(e, { depth: t, colors: !0 }));
}
class E {
  constructor(t, s, n) {
    this.config = t, this.env = s, this.fusionOptions = n, this.config = w(this.config, {
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
  cleans = [];
  tasks = /* @__PURE__ */ new Map();
  merge(t) {
    return typeof t == "function" ? (this.config = t(this.config) ?? this.config, this) : (this.config = w(this.config, t), this);
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
          const i = o(t);
          if (i)
            return i;
        }
        return `${this.getChunkDir()}/[name]-[hash].js`;
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
        if (!D(n))
          return n;
      }
    }
  }
  ensurePath(t, s = {}) {
    return B(this.config, t) == null && _(this.config, t, s), this;
  }
  get(t) {
    return B(this.config, t);
  }
  set(t, s) {
    return _(this.config, t, s), this;
  }
  addTask(t, s) {
    const n = new W(t, s);
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
    return g(process.cwd(), t);
  }
  debug() {
    Wt(this.config);
  }
}
function $t(e) {
  return e ??= process.argv, e.slice(2).join(" ").split(" -- ").slice(1).join(" -- ").trim().split(" ").filter((t) => t !== "");
}
function Bt(e) {
  const t = rt();
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
    const n = (await at({
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
    V(n.path, o);
    const i = new M(n.path, void 0);
    return i.filename = n.path, i.paths = M._nodeModulePaths(T(n.path)), i._compile(o, n.path), P(i.exports);
  } else {
    const s = await import(t);
    return P(s);
  }
}
function P(e) {
  return e = { ...e }, e.__esModule && delete e.__esModule, e;
}
async function $(e, t = !1) {
  return e = await e, !t && Array.isArray(e) ? (await Promise.all(e.map((n) => $(n, !0)))).flat() : z(typeof e == "function" ? await e() : await e, e?.name);
}
async function z(e, t) {
  if (!Array.isArray(e))
    return [await e];
  const s = await Promise.all(e), n = [];
  for (const o of s)
    Array.isArray(o) ? n.push(...o) : n.push(o);
  return n;
}
function Mt(e, t) {
  const s = It(e, t);
  if (!s)
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  return s;
}
function It(e, t) {
  let s = t?.config;
  return s ? (D(s) || (s = p(e, s)), k(s) ? {
    path: s,
    // get filename from file path
    filename: s.split("/").pop() || "",
    type: zt(s),
    ts: Gt(s)
  } : null) : Et(e);
}
function Et(e) {
  let t = p(e, "fusionfile.js");
  return k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "commonjs",
    ts: !1
  } : (t = p(e, "fusionfile.mjs"), k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !1
  } : (t = p(e, "fusionfile.ts"), k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : (t = p(e, "fusionfile.mts"), k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : null)));
}
function zt(e) {
  let t = "unknown";
  return e.endsWith(".cjs") ? t = "commonjs" : (e.endsWith(".mjs") || e.endsWith(".ts") || e.endsWith(".mts")) && (t = "module"), t;
}
function Gt(e) {
  return e.endsWith(".ts") || e.endsWith(".mts");
}
async function Lt(e) {
  const t = Object.keys(e);
  t.sort((o, i) => o === "default" ? -1 : i === "default" ? 1 : o.localeCompare(i));
  const s = [];
  for (const o of t) {
    const i = e[o];
    s.push(await Z(o, i));
  }
  const n = lt({
    label: b.magenta("Available Tasks"),
    nodes: s
  });
  console.log(n);
}
async function Z(e, t) {
  const s = [];
  t = y(await t);
  for (let n of t) {
    const o = await $(n, !0);
    for (const i of o)
      typeof i == "function" ? s.push(
        await Z(i.name, i)
      ) : s.push(...await Rt(i));
  }
  return {
    label: b.cyan(e),
    nodes: s
  };
}
async function Rt(e) {
  const t = await e.preview();
  return Promise.all(t.map((s) => Vt(s)));
}
async function Vt(e) {
  const t = [], { input: s, output: n, extra: o } = e, i = b.yellow(s);
  t.push(`Input: ${i}`);
  const r = b.green(n);
  return t.push(`Output: ${r}`), t.join(" - ");
}
function Ht(e, t) {
  e = R(e), e.length === 0 && e.push("default");
  const s = {};
  for (const n of e)
    if (t[n])
      s[n] = t[n];
    else
      throw new Error(`Task "${b.cyan(n)}" not found in fusion config.`);
  return s;
}
async function Ut(e) {
  const t = {}, s = {};
  for (const n in e) {
    const o = e[n];
    s[n] = await S(n, o, t);
  }
  return s;
}
async function S(e, t, s) {
  const n = [];
  if (Array.isArray(t))
    for (const o in t) {
      const i = t[o];
      n.push(...await S(o, i, s));
    }
  else if (typeof t == "function") {
    if (e = t.name || e, s[e])
      return [];
    s[e] = t;
    const o = await $(t, !0);
    if (Array.isArray(o))
      for (const i in o) {
        const r = o[i];
        n.push(...await S(i, r, s));
      }
  } else
    n.push(await t);
  return n;
}
let f = Bt($t(process.argv));
bt(f);
let l;
const qt = f._, tt = [];
function Jt(e = {}, t) {
  let s, n, o = !1;
  const i = Kt(e);
  return typeof t == "string" || Array.isArray(t) && t.length > 0 ? f._ = y(t) : f._ = qt, f = jt(f, i.cliParams), [
    {
      name: "fusion",
      configResolved(r) {
        n = r, s = r.logger, r.plugins.push(...tt);
        for (const c of r.plugins)
          "buildConfig" in c && c.buildConfig?.(l);
      },
      async config(r, c) {
        let a;
        r.root ? a = p(r.root) : a = f.cwd || process.cwd(), delete r.root, process.chdir(a), l = new E(r, c, i);
        let u;
        if (typeof i.fusionfile == "string" || !i.fusionfile) {
          f.config ??= i.fusionfile;
          const m = Mt(a, f);
          u = await _t(m);
        } else typeof i.fusionfile == "function" ? u = P(await i.fusionfile()) : u = P(i.fusionfile);
        if (f.list) {
          await Lt(u);
          return;
        }
        const h = Ht([...f._], u), v = await Ut(h);
        for (const m in v) {
          const et = v[m];
          for (const st of et)
            await st.config(m, l);
        }
        return l.merge(E.globalOverrideConfig), l.merge(l.overrideConfig), l.config;
      },
      outputOptions(r) {
        if (n.build.emptyOutDir) {
          const c = n.build.outDir, a = p(c, "upload");
          if (k(a))
            throw new Error(
              `The output directory: "${c}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
            );
        }
      },
      async buildStart(r) {
        l.cleans.length > 0 && await At(l.cleans, n.build.outDir || process.cwd());
      },
      // Server
      configureServer(r) {
        r.httpServer?.once("listening", () => {
          const c = r.config.server.https ? "https" : "http", a = r.httpServer?.address(), u = a && typeof a != "string" ? a.address : "localhost", h = a && typeof a != "string" ? a.port : 80, v = `${c}://${u}:${h}/`, m = p(
            r.config.root,
            i.cliParams?.serverFile ?? "tmp/vite-server"
          );
          V(p(r.config.root, m), v), o || (process.on("exit", () => {
            I.existsSync(m) && I.rmSync(m);
          }), process.on("SIGINT", () => process.exit()), process.on("SIGTERM", () => process.exit()), process.on("SIGHUP", () => process.exit()), o = !0);
        });
      }
    },
    {
      name: "fusion:pre-handles",
      enforce: "pre",
      async resolveId(r, c, a) {
        for (const u of l.resolveIdCallbacks) {
          if (typeof u != "function")
            continue;
          const h = await u.call(this, r, c, a);
          if (h)
            return h;
        }
        if (r.startsWith("hidden:"))
          return r;
      },
      async load(r, c) {
        for (const a of l.loadCallbacks) {
          if (typeof a != "function")
            continue;
          const u = await a.call(this, r, c);
          if (u)
            return u;
        }
        if (r.startsWith("hidden:"))
          return "";
      }
    },
    {
      name: "fusion:post-handles",
      generateBundle(r, c) {
        for (const [a, u] of Object.entries(c))
          u.type === "chunk" && u.facadeModuleId?.startsWith("hidden:") && delete c[a];
      },
      async writeBundle(r, c) {
        const a = n.build.outDir || process.cwd();
        await Ft(l.moveTasks, a, s), await Tt(l.copyTasks, a, s), await Pt(l.linkTasks, a, s);
        for (const u of l.postBuildCallbacks)
          await u();
        for (const [u, h] of l.tasks)
          for (const v of h.postCallbacks)
            await v();
      }
    }
  ];
}
function Kt(e) {
  return typeof e == "string" ? {
    fusionfile: e
  } : typeof e == "function" ? {
    fusionfile: e
  } : e;
}
function Qt(e) {
  e(l);
}
function Xt(e) {
  if (e === null) {
    l.overrideConfig = {};
    return;
  }
  l.overrideConfig = w(l.overrideConfig, e);
}
function Yt(e) {
  l.overrideConfig = w(l.overrideConfig, {
    build: {
      outDir: e
    }
  });
}
function Zt(e) {
  l.fusionOptions.chunkDir = e;
}
function te(e, t) {
  l.overrideConfig = w(l.overrideConfig, {
    resolve: {
      alias: {
        [e]: t
      }
    }
  });
}
function ee(e, t) {
  const s = {};
  t && (s[e] = t), l.overrideConfig = w(l.overrideConfig, {
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
function se(...e) {
  tt.push(...e);
}
function ne(...e) {
  l.addCleans(...e), l.cleans = R(l.cleans);
}
const ve = {
  ...Dt,
  useFusion: Jt,
  configureBuilder: Qt,
  mergeViteConfig: Xt,
  outDir: Yt,
  chunkDir: Zt,
  alias: te,
  external: ee,
  plugin: se,
  clean: ne,
  params: f
};
export {
  te as alias,
  l as builder,
  vt as callback,
  kt as callbackAfterBuild,
  Zt as chunkDir,
  ne as clean,
  Qt as configureBuilder,
  mt as copy,
  Ot as copyGlob,
  ut as css,
  ve as default,
  ee as external,
  Y as fileToId,
  Ct as isDev,
  J as isProd,
  q as isVerbose,
  O as isWindows,
  ft as js,
  yt as link,
  Xt as mergeViteConfig,
  dt as move,
  xt as moveGlob,
  Yt as outDir,
  F as params,
  se as plugin,
  K as shortHash,
  Q as symlink,
  Jt as useFusion
};
//# sourceMappingURL=index.js.map
