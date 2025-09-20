import Y from "crypto";
import L from "fast-glob";
import d from "fs-extra";
import { randomBytes as Z } from "node:crypto";
import { basename as A, parse as S, normalize as z, resolve as f, relative as g, dirname as F, isAbsolute as N } from "node:path";
import { inspect as tt } from "node:util";
import { mergeConfig as w } from "vite";
import { get as $, set as B, uniq as R } from "lodash-es";
import et from "yargs";
import { build as st } from "esbuild";
import M from "module";
import { writeFileSync as H, existsSync as k } from "node:fs";
import nt from "archy";
import b from "chalk";
import I from "fs";
function y(e) {
  return Array.isArray(e) ? e : [e];
}
function D(e, t) {
  return Array.isArray(e) ? e.map(t) : t(e);
}
function V(e, t) {
  return e = y(e), e.map(t);
}
function Zt(e, t, s = {}) {
  return new it(e, t, s);
}
class it {
  constructor(t, s, n = {}) {
    this.input = t, this.output = s, this.options = n;
  }
  config(t, s) {
    return V(this.input, (n) => {
      const i = s.addTask(n, t);
      return s.assetFileNamesCallbacks.push((o) => {
        const r = o.names[0];
        if (r && A(r, ".css") === i.id)
          return this.output ? i.normalizeOutput(this.output, ".css") : S(n).name + ".css";
      }), i;
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
function te(e, t) {
  return new ot(e, t);
}
class ot {
  constructor(t, s) {
    this.input = t, this.output = s;
  }
  config(t, s) {
    return V(this.input, (n) => {
      const i = s.addTask(n, t);
      return s.entryFileNamesCallbacks.push((o) => {
        const r = o.name;
        if (r && r === i.id)
          return this.output ? i.normalizeOutput(this.output) : S(n).name + ".js";
      }), i;
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
function ee(e, t) {
  return new rt(e, t);
}
class rt {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    D(this.input, (n) => {
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
function se(e, t) {
  return new at(e, t);
}
class at {
  constructor(t, s) {
    this.input = t, this.dest = s;
  }
  config(t, s) {
    D(this.input, (n) => {
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
function ne(e, t, s = {}) {
  return new lt(e, t, s);
}
class lt {
  constructor(t, s, n = {}) {
    this.input = t, this.dest = s, this.options = n;
  }
  config(t, s) {
    D(this.input, (n) => {
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
function ie(e) {
  return new U(e);
}
function oe(e) {
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
let P;
function ut(e) {
  return P = e, ct = P?.verbose ? P?.verbose > 0 : !1, e;
}
let ct = !1;
const ft = process.env.NODE_ENV === "production", re = !ft;
function _() {
  return process.platform === "win32";
}
function pt(e, t = 8) {
  let s = Y.createHash("sha1").update(e).digest("hex");
  return t && t > 0 && (s = s.substring(0, t)), s;
}
function C(e, t, s) {
  const n = [];
  e = O(e, s.outDir), t = O(t, s.outDir);
  const i = vt(e), o = q(e) ? L.globSync(e.replace(/\\/g, "/"), s.globOptions) : [e];
  for (let r of o) {
    let c, a = t;
    wt(t) ? (c = a, a = a + g(i, r)) : c = F(a), d.ensureDirSync(c), n.push(s.handler(r, a));
  }
  return n;
}
function dt(e, t, s) {
  const n = [];
  for (const { src: i, dest: o, options: r } of e) {
    const c = C(
      i,
      o,
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
function ht(e, t, s) {
  const n = [];
  for (const { src: i, dest: o, options: r } of e) {
    const c = C(
      i,
      o,
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
function mt(e, t, s) {
  const n = [];
  for (const { src: i, dest: o, options: r } of e) {
    const c = C(
      i,
      o,
      {
        outDir: t,
        handler: async (a, u) => (s.info(`Link file from ${g(t, a)} to ${g(t, u)}`), yt(a, u, r?.force ?? !1)),
        globOptions: { onlyFiles: !1 }
      }
    );
    n.push(...c);
  }
  return Promise.all(n);
}
function gt(e, t) {
  const s = [];
  t = t.replace(/\\/g, "/");
  for (let n of e) {
    n = O(n, t), n = f(n);
    const i = q(n) ? L.globSync(n.replace(/\\/g, "/"), { onlyFiles: !1 }) : [n], o = f(t + "/upload").replace(/\\/g, "/");
    for (let r of i) {
      if (r.replace(/\\/g, "/").startsWith(o))
        throw new Error("Refuse to delete `upload/*` folder.");
      s.push(d.remove(r));
    }
  }
  return Promise.all(s);
}
async function ae(e, t) {
  const s = C(
    e,
    t,
    {
      outDir: process.cwd(),
      handler: async (n, i) => d.copy(n, i, { overwrite: !0 }),
      globOptions: { onlyFiles: !0 }
    }
  );
  await Promise.all(s);
}
async function le(e, t) {
  const s = C(
    e,
    t,
    {
      outDir: process.cwd(),
      handler: async (n, i) => d.move(n, i, { overwrite: !0 }),
      globOptions: { onlyFiles: !0 }
    }
  );
  await Promise.all(s);
}
async function yt(e, t, s = !1) {
  return _() && !d.lstatSync(e).isFile() ? d.ensureSymlink(e, t, "junction") : _() && d.lstatSync(e).isFile() && s ? d.ensureLink(e, t) : d.ensureSymlink(e, t);
}
function wt(e) {
  return e.endsWith("/") || e.endsWith("\\");
}
function vt(e) {
  const t = ["*", "?", "[", "]"], s = [...e].findIndex((n) => t.includes(n));
  return s === -1 ? F(e) : F(e.slice(0, s + 1));
}
function q(e) {
  return ["*", "?", "[", "]"].some((s) => e.includes(s));
}
function O(e, t) {
  return e.startsWith(".") ? e = f(e) : N(e) || (e = t + "/" + e), e;
}
function kt(e, t) {
  return e = z(e), t ||= Z(4).toString("hex"), t + "-" + pt(e);
}
class j {
  constructor(t, s) {
    this.input = t, this.group = s, this.id = j.toFileId(t, s), this.input = z(t);
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
    return (t.endsWith("/") || t.endsWith("\\")) && (t += S(this.input).name + s), t;
  }
  static toFileId(t, s) {
    return kt(t, s);
  }
}
function bt(e, ...t) {
  if (!t.length)
    return e;
  for (const s of t)
    s && (typeof s == "function" ? e = s(e) ?? e : e = w(e, s));
  return e;
}
function Ct(e, t = 10) {
  console.log(tt(e, { depth: t, colors: !0 }));
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
        for (const i of this.chunkFileNamesCallbacks) {
          const o = i(t);
          if (o)
            return o;
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
        if (!N(n))
          return n;
      }
    }
  }
  ensurePath(t, s = {}) {
    return $(this.config, t) == null && B(this.config, t, s), this;
  }
  get(t) {
    return $(this.config, t);
  }
  set(t, s) {
    return B(this.config, t, s), this;
  }
  addTask(t, s) {
    const n = new j(t, s);
    this.tasks.set(n.id, n);
    const i = this.config.build.rollupOptions.input;
    return i[n.id] = n.input, n;
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
    Ct(this.config);
  }
}
function Ft(e) {
  return e ??= process.argv, e.slice(2).join(" ").split(" -- ").slice(1).join(" -- ").trim().split(" ").filter((t) => t !== "");
}
function Tt(e) {
  const t = et();
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
async function Pt(e) {
  let t = e.path;
  if (process.platform === "win32") {
    const s = t.replace(/\\/g, "/");
    s.startsWith("file://") || (t = `file:///${s}`);
  }
  if (e.ts) {
    const n = (await st({
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
    H(n.path, i);
    const o = new M(n.path, void 0);
    return o.filename = n.path, o.paths = M._nodeModulePaths(F(n.path)), o._compile(i, n.path), T(o.exports);
  } else {
    const s = await import(t);
    return T(s);
  }
}
function T(e) {
  return e = { ...e }, e.__esModule && delete e.__esModule, e;
}
async function W(e, t = !1) {
  return e = await e, !t && Array.isArray(e) ? (await Promise.all(e.map((n) => W(n, !0)))).flat() : G(typeof e == "function" ? await e() : await e, e?.name);
}
async function G(e, t) {
  if (!Array.isArray(e))
    return [await e];
  const s = await Promise.all(e), n = [];
  for (const i of s)
    Array.isArray(i) ? n.push(...i) : n.push(i);
  return n;
}
function At(e, t) {
  const s = Ot(e, t);
  if (!s)
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  return s;
}
function Ot(e, t) {
  let s = t?.config;
  return s ? (N(s) || (s = f(e, s)), k(s) ? {
    path: s,
    // get filename from file path
    filename: s.split("/").pop() || "",
    type: St(s),
    ts: Nt(s)
  } : null) : xt(e);
}
function xt(e) {
  let t = f(e, "fusionfile.js");
  return k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "commonjs",
    ts: !1
  } : (t = f(e, "fusionfile.mjs"), k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !1
  } : (t = f(e, "fusionfile.ts"), k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : (t = f(e, "fusionfile.mts"), k(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : null)));
}
function St(e) {
  let t = "unknown";
  return e.endsWith(".cjs") ? t = "commonjs" : (e.endsWith(".mjs") || e.endsWith(".ts") || e.endsWith(".mts")) && (t = "module"), t;
}
function Nt(e) {
  return e.endsWith(".ts") || e.endsWith(".mts");
}
async function Dt(e) {
  const t = Object.keys(e);
  t.sort((i, o) => i === "default" ? -1 : o === "default" ? 1 : i.localeCompare(o));
  const s = [];
  for (const i of t) {
    const o = e[i];
    s.push(await J(i, o));
  }
  const n = nt({
    label: b.magenta("Available Tasks"),
    nodes: s
  });
  console.log(n);
}
async function J(e, t) {
  const s = [];
  t = y(await t);
  for (let n of t) {
    const i = await W(n, !0);
    for (const o of i)
      typeof o == "function" ? s.push(
        await J(o.name, o)
      ) : s.push(...await jt(o));
  }
  return {
    label: b.cyan(e),
    nodes: s
  };
}
async function jt(e) {
  const t = await e.preview();
  return Promise.all(t.map((s) => Wt(s)));
}
async function Wt(e) {
  const t = [], { input: s, output: n, extra: i } = e, o = b.yellow(s);
  t.push(`Input: ${o}`);
  const r = b.green(n);
  return t.push(`Output: ${r}`), t.join(" - ");
}
function $t(e, t) {
  e = R(e), e.length === 0 && e.push("default");
  const s = {};
  for (const n of e)
    if (t[n])
      s[n] = t[n];
    else
      throw new Error(`Task "${b.cyan(n)}" not found in fusion config.`);
  return s;
}
async function Bt(e) {
  const t = {}, s = {};
  for (const n in e) {
    const i = e[n];
    s[n] = await x(n, i, t);
  }
  return s;
}
async function x(e, t, s) {
  const n = [];
  if (Array.isArray(t))
    for (const i in t) {
      const o = t[i];
      n.push(...await x(i, o, s));
    }
  else if (typeof t == "function") {
    if (e = t.name || e, s[e])
      return [];
    s[e] = t;
    const i = await W(t, !0);
    if (Array.isArray(i))
      for (const o in i) {
        const r = i[o];
        n.push(...await x(o, r, s));
      }
  } else
    n.push(await t);
  return n;
}
let p = Tt(Ft(process.argv));
ut(p);
let l;
const Mt = p._, K = [];
function ue(e = {}, t) {
  let s, n, i = !1;
  const o = It(e);
  return typeof t == "string" || Array.isArray(t) && t.length > 0 ? p._ = y(t) : p._ = Mt, p = bt(p, o.cliParams), [
    {
      name: "fusion",
      configResolved(r) {
        n = r, s = r.logger, r.plugins.push(...K);
        for (const c of r.plugins)
          "buildConfig" in c && c.buildConfig?.(l);
      },
      async config(r, c) {
        let a;
        r.root ? a = f(r.root) : a = p.cwd || process.cwd(), delete r.root, process.chdir(a), l = new E(r, c, o);
        let u;
        if (typeof o.fusionfile == "string" || !o.fusionfile) {
          p.config ??= o.fusionfile;
          const m = At(a, p);
          u = await Pt(m);
        } else typeof o.fusionfile == "function" ? u = T(await o.fusionfile()) : u = T(o.fusionfile);
        if (p.list) {
          await Dt(u);
          return;
        }
        const h = $t([...p._], u), v = await Bt(h);
        for (const m in v) {
          const Q = v[m];
          for (const X of Q)
            await X.config(m, l);
        }
        return l.merge(E.globalOverrideConfig), l.merge(l.overrideConfig), l.config;
      },
      outputOptions(r) {
        if (n.build.emptyOutDir) {
          const c = n.build.outDir, a = f(c, "upload");
          if (k(a))
            throw new Error(
              `The output directory: "${c}" contains an "upload" folder, please move this folder away or set an different fusion outDir.`
            );
        }
      },
      async buildStart(r) {
        l.cleans.length > 0 && await gt(l.cleans, n.build.outDir || process.cwd());
      },
      // Server
      configureServer(r) {
        r.httpServer?.once("listening", () => {
          const c = r.config.server.https ? "https" : "http", a = r.httpServer?.address(), u = a && typeof a != "string" ? a.address : "localhost", h = a && typeof a != "string" ? a.port : 80, v = `${c}://${u}:${h}/`, m = f(
            r.config.root,
            o.cliParams?.serverFile ?? "tmp/vite-server"
          );
          H(f(r.config.root, m), v), i || (process.on("exit", () => {
            I.existsSync(m) && I.rmSync(m);
          }), process.on("SIGINT", () => process.exit()), process.on("SIGTERM", () => process.exit()), process.on("SIGHUP", () => process.exit()), i = !0);
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
        await dt(l.moveTasks, a, s), await ht(l.copyTasks, a, s), await mt(l.linkTasks, a, s);
        for (const u of l.postBuildCallbacks)
          await u();
        for (const [u, h] of l.tasks)
          for (const v of h.postCallbacks)
            await v();
      }
    }
  ];
}
function It(e) {
  return typeof e == "string" ? {
    fusionfile: e
  } : typeof e == "function" ? {
    fusionfile: e
  } : e;
}
function ce(e) {
  e(l);
}
function fe(e) {
  if (e === null) {
    l.overrideConfig = {};
    return;
  }
  l.overrideConfig = w(l.overrideConfig, e);
}
function pe(e) {
  l.overrideConfig = w(l.overrideConfig, {
    build: {
      outDir: e
    }
  });
}
function de(e) {
  l.fusionOptions.chunkDir = e;
}
function he(e, t) {
  l.overrideConfig = w(l.overrideConfig, {
    resolve: {
      alias: {
        [e]: t
      }
    }
  });
}
function me(e, t) {
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
function ge(...e) {
  K.push(...e);
}
function ye(...e) {
  l.addCleans(...e), l.cleans = R(l.cleans);
}
export {
  he as alias,
  l as builder,
  ie as callback,
  oe as callbackAfterBuild,
  de as chunkDir,
  ye as clean,
  ce as configureBuilder,
  se as copy,
  ae as copyGlob,
  Zt as css,
  me as external,
  kt as fileToId,
  re as isDev,
  ft as isProd,
  ct as isVerbose,
  _ as isWindows,
  te as js,
  ne as link,
  fe as mergeViteConfig,
  ee as move,
  le as moveGlob,
  pe as outDir,
  P as params,
  ge as plugin,
  pt as shortHash,
  yt as symlink,
  ue as useFusion
};
//# sourceMappingURL=index.js.map
