import c from "chalk";
import { build as E, mergeConfig as _, defineConfig as T } from "vite";
import { existsSync as y } from "node:fs";
import { normalize as R, dirname as L, isAbsolute as x, resolve as d } from "node:path";
import $ from "archy";
import { cloneDeep as O, uniq as C } from "lodash-es";
import W from "yargs";
import { hideBin as D } from "yargs/helpers";
import { fileURLToPath as I } from "node:url";
import k from "autoprefixer";
var p = /* @__PURE__ */ ((e) => (e.NONE = "none", e.SAME_FILE = "same_file", e.SEPARATE_FILE = "separate_file", e))(p || {});
function S(e) {
  return Array.isArray(e) ? e : [e];
}
function M(e, t) {
  return Array.isArray(e) ? e.map(t) : t(e);
}
function F(e, t = {}) {
  return e = M(e, (r) => (typeof r == "string" && (r.endsWith("/") ? r = {
    dir: r,
    ...t
  } : r = {
    dir: L(r),
    // Get file name with node library, consider Windows
    entryFileNames: R(r).replace(/\\/g, "/").split("/").pop(),
    ...t
  }), r)), S(e);
}
function f(e, ...t) {
  if (e ??= {}, !t.length)
    return e;
  for (const r of t)
    r && (typeof r == "function" ? e = r(e) ?? e : e = { ...e, ...r });
  return e;
}
function j(e) {
  if (e = O(e), e.file) {
    const t = e.file.split("."), r = t.pop();
    e.file = `${t.join(".")}.min.${r}`;
  } else if (e.dir && typeof e.entryFileNames == "string") {
    const t = e.entryFileNames.split("."), r = t.pop();
    e.entryFileNames = `${t.join(".")}.min.${r}`;
  }
  return e;
}
function g(e, t) {
  return f(
    {
      entry: e
    },
    t
  );
}
function b(e, t, r) {
  return f(
    {
      build: {
        lib: e,
        rollupOptions: {
          output: t
        },
        emptyOutDir: !1,
        target: "esnext"
      }
    },
    r
  );
}
async function B(e, t, r = {}) {
  r.verbose ??= N;
  let s = F(t, { format: "es" });
  const n = [];
  for (const o of s) {
    const i = A(
      e,
      s,
      r,
      (l) => (l.build.minify = r.minify === p.SAME_FILE ? "esbuild" : !1, l.build.cssMinify = r.minify === p.SAME_FILE ? "esbuild" : !1, l)
    );
    if (n.push(f(i, r?.vite)), r?.minify === p.SEPARATE_FILE) {
      const l = j(o), a = A(
        e,
        l,
        r,
        (u) => (u.build.minify = "esbuild", u.build.cssMinify = "esbuild", u)
      );
      n.push(f(a, r?.vite));
    }
  }
  return n;
}
function A(e, t, r, s) {
  t = O(t);
  const n = b(
    void 0,
    t,
    (o) => {
      o.build.rollupOptions.input = e, o.build.emptyOutDir = r.clean ?? !1;
      for (const i of S(o.build.rollupOptions.output))
        i.assetFileNames = String(i.entryFileNames), delete i.entryFileNames;
      return o.build.cssCodeSplit = !0, o.css = {
        // modules: {
        //   scopeBehaviour: 'global', // 或是 'global'
        // },
        transformer: "postcss",
        postcss: f(
          {
            plugins: [
              k({ overrideBrowserslist: r.browserslist })
            ]
          },
          r.postcss
        )
      }, o;
    }
  );
  return f(
    n,
    s,
    r.vite
  );
}
async function U(e, t, r = {}) {
  const s = f(
    {
      target: r?.target || "esnext"
    },
    r?.esbuild
  );
  return z(
    t,
    r,
    (n, o) => o ? b(
      g(e),
      n,
      (i) => (i.build.minify = "esbuild", i.build.emptyOutDir = r.clean || !1, i.build.target = r.target || "esnext", i.esbuild = s, i)
    ) : b(
      g(e),
      n,
      (i) => (i.build.minify = r?.minify === p.SAME_FILE ? "esbuild" : !1, i.build.emptyOutDir = r.clean || !1, i.build.target = r.target || "esnext", i.esbuild = s, i)
    )
  );
}
function z(e, t, r) {
  t.verbose ??= N;
  const s = F(e, { format: t?.format || "es" });
  for (const i of s)
    i.format === "umd" && (i.name = t?.umdName);
  const n = [], o = r(s, !1);
  if (n.push(f(o, t.vite)), t?.minify === p.SEPARATE_FILE) {
    const i = s.map((a) => j(a)), l = r(i, !0);
    n.push(f(l, t?.vite));
  }
  return n;
}
const V = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  MinifyOptions: p,
  css: B,
  js: U
}, Symbol.toStringTag, { value: "Module" }));
async function G(e, t) {
  const r = [];
  for (const s in e) {
    const n = [], o = e[s];
    console.log(`▶️ - ${c.cyan(s)} Start...`);
    for (const l of o) {
      const a = E(T(l));
      t.series && await a, n.push(a);
    }
    const i = Promise.all(n).then(() => {
      console.log(`✅ - ${c.cyan(s)} completed.`);
    });
    t.series && await i, r.push(i);
  }
  await Promise.all(r);
}
async function q(e, t) {
  const r = [];
  for (const n in e) {
    const o = e[n];
    console.log(`▶️ - ${c.cyan(n)} Start...`);
    for (const i of o) {
      const l = E(
        _(
          T(i),
          {
            build: { watch: {} }
          }
        )
      );
      l.then((a) => {
        a.on("event", (u) => {
          switch (u.code) {
            case "START":
              console.log("→ Start Watching...");
              break;
            case "BUNDLE_START":
              console.log("→ Start Bundling...");
              break;
            case "BUNDLE_END":
              console.log(`✔ Bundled, uses ${u.duration}ms`), u.result?.close();
              break;
            case "END":
              console.log("Watching...");
              break;
            case "ERROR":
              console.error("✖ ERROR: ", u.error);
              break;
          }
        });
      }), r.push(l);
    }
  }
  const s = await Promise.all(r);
  process.on("SIGINT", async () => {
    for (const n of s)
      await n.close();
    console.log(`
🛑 STOP Watching...`), process.exit(0);
  });
}
async function J(e) {
  let t = e.path;
  if (process.platform === "win32") {
    const s = t.replace(/\\/g, "/");
    s.startsWith("file://") || (t = `file:///${s}`);
  }
  return { ...await import(t) };
}
async function h(e, t = !1) {
  return !t && Array.isArray(e) ? (await Promise.all(e.map((s) => h(s, !0)))).flat() : v(typeof e == "function" ? await e() : await e, e?.name);
}
async function v(e, t) {
  if (!Array.isArray(e))
    return [await e];
  const r = await Promise.all(e), s = [];
  for (const n of r)
    Array.isArray(n) ? s.push(...n) : s.push(n);
  return s;
}
function H(e, t) {
  const r = K(e, t);
  if (!r)
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  return r;
}
function K(e, t) {
  let r = t?.config;
  return r ? (x(r) || (r = d(e, r)), y(r) ? {
    path: r,
    // get filename from file path
    filename: r.split("/").pop() || "",
    type: X(r),
    ts: Y(r)
  } : null) : Q(e);
}
function Q(e) {
  let t = d(e, "fusionfile.js");
  return y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "commonjs",
    ts: !1
  } : (t = d(e, "fusionfile.mjs"), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !1
  } : (t = d(e, "fusionfile.ts"), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : (t = d(e, "fusionfile.mts"), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: "module",
    ts: !0
  } : null)));
}
function X(e) {
  let t = "unknown";
  return e.endsWith(".cjs") ? t = "commonjs" : (e.endsWith(".mjs") || e.endsWith(".ts") || e.endsWith(".mts")) && (t = "module"), t;
}
function Y(e) {
  return e.endsWith(".ts") || e.endsWith(".mts");
}
async function Z(e) {
  const t = Object.keys(e);
  t.sort((n, o) => n === "default" ? -1 : o === "default" ? 1 : n.localeCompare(o));
  const r = [];
  for (const n of t) {
    const o = e[n], i = await h(o, !0);
    r.push(await P(n, i));
  }
  const s = $({
    label: c.magenta("Available Tasks"),
    nodes: r
  });
  console.log(s);
}
async function P(e, t) {
  const r = [];
  Array.isArray(t) || (t = [t]);
  for (const s of t)
    if (typeof s == "function") {
      let n = await h(s, !0);
      r.push(
        await P(s.name, n)
      );
    } else
      r.push(ee(s));
  return {
    label: c.cyan(e),
    nodes: r
  };
}
function ee(e, t = 4) {
  const r = [], s = e.build?.lib;
  if (s && s.entry) {
    const o = s.entry;
    let i = "";
    typeof o == "string" ? i = c.yellow(o) : Array.isArray(o) ? i = c.yellow(o.join(", ")) : typeof o == "object" && (i = c.yellow(Object.values(o).join(", "))), r.push(`Input: ${i}`);
  }
  const n = e.build?.rollupOptions?.output;
  return n && (Array.isArray(n) ? n : [n]).forEach((i, l) => {
    let a = "";
    i.file ? a = c.green(i.file) : i.dir && (a = c.green(i.dir)), r.push(`Output[${l}]: ${a}`);
  }), r.join(" - ");
}
function te(e, t) {
  e = C(e), e.length === 0 && e.push("default");
  const r = {};
  for (const s of e)
    if (t[s])
      r[s] = t[s];
    else
      throw new Error(`Task "${c.cyan(s)}" not found in fusion config.`);
  return r;
}
async function re(e) {
  const t = {}, r = {};
  for (const s in e) {
    const n = e[s];
    r[s] = await w(s, n, t);
  }
  return r;
}
async function w(e, t, r) {
  const s = [];
  if (Array.isArray(t))
    for (const n in t) {
      const o = t[n];
      s.push(...await w(n, o, r));
    }
  else if (typeof t == "function") {
    if (e = t.name || e, r[e])
      return [];
    r[e] = t;
    const n = await h(t, !0);
    if (Array.isArray(n))
      for (const o in n) {
        const i = n[o];
        s.push(...await w(o, i, r));
      }
  } else
    s.push(await t);
  return s;
}
function se() {
  const e = W();
  return e.option("watch", {
    alias: "w",
    type: "boolean",
    description: "Watch files for changes and re-run the tasks"
  }), e.option("cwd", {
    type: "string",
    description: "Current working directory"
  }), e.option("list", {
    alias: "l",
    type: "boolean",
    description: "List all available tasks"
  }), e.option("config", {
    alias: "c",
    type: "string",
    description: "Path to config file"
  }), e.option("series", {
    alias: "s",
    type: "boolean",
    description: "Run tasks in series instead of parallel"
  }), e.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  }), e.parseSync(D(process.argv));
}
async function ne(e) {
  try {
    await ie(e);
  } catch (t) {
    if (t instanceof Error) {
      if (e.verbose && e.verbose > 0)
        throw t;
      console.error(t), process.exit(1);
    } else
      throw t;
  }
}
async function ie(e) {
  let t = e?.cwd, r;
  t ? (r = t = d(t), process.chdir(t)) : r = process.cwd();
  const s = H(r, e), n = await J(s);
  if (e.list) {
    await Z(n);
    return;
  }
  const o = te([...e._], n), i = await re(o);
  e.watch ? await q(i) : await G(i, e);
}
let m;
const oe = process.argv[1] && I(import.meta.url) === process.argv[1], be = {
  ...V,
  params: m
};
oe && (m = se(), ne(m));
const N = m?.verbose ? m?.verbose > 0 : !1;
export {
  p as MinifyOptions,
  B as css,
  be as default,
  N as isVerbose,
  U as js
};
//# sourceMappingURL=index.js.map
