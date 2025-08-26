import f from "chalk";
import { build as _, defineConfig as S } from "vite";
import { existsSync as y } from "node:fs";
import { normalize as O, dirname as N, isAbsolute as P, resolve as p } from "node:path";
import C from "archy";
import { cloneDeep as b, uniq as $ } from "lodash-es";
import L from "yargs";
import { hideBin as M } from "yargs/helpers";
import { fileURLToPath as I } from "node:url";
import W from "path";
import { rimraf as x } from "rimraf";
import R from "rollup-plugin-esbuild";
var c = /* @__PURE__ */ ((e) => (e.NONE = "none", e.SAME_FILE = "same_file", e.SEPARATE_FILE = "separate_file", e))(c || {});
function v(e) {
  return Array.isArray(e) ? e : [e];
}
function k(e, n) {
  return Array.isArray(e) ? e.map(n) : n(e);
}
function E(e, n = {}) {
  return e = k(e, (t) => (typeof t == "string" && (t.endsWith("/") ? t = {
    dir: t,
    ...n
  } : t = {
    dir: N(t),
    // Get file name with node library, consider Windows
    entryFileNames: O(t).replace(/\\/g, "/").split("/").pop(),
    ...n
  }), t)), v(e);
}
function u(e, ...n) {
  if (e ??= {}, !n.length)
    return e;
  for (const t of n)
    t && (typeof t == "function" ? e = t(e) ?? e : e = { ...e, ...t });
  return e;
}
function F(e) {
  if (e = b(e), e.file) {
    const n = e.file.split("."), t = n.pop();
    e.file = `${n.join(".")}.min.${t}`;
  } else if (e.dir && typeof e.entryFileNames == "string") {
    const n = e.entryFileNames.split("."), t = n.pop();
    e.entryFileNames = `${n.join(".")}.min.${t}`;
  }
  return e;
}
function D(e, n, t) {
  return u(
    {
      build: {
        lib: e,
        rollupOptions: {
          output: n
        },
        emptyOutDir: !1
      }
    },
    t
  );
}
async function B(e, n, t = {}) {
  t.verbose ??= j;
  let r = E(n, { format: "es" });
  const i = [];
  for (const s of r) {
    const o = A(
      e,
      r,
      t,
      (l) => (l.build.minify = t.minify === c.SAME_FILE ? "esbuild" : !1, l.build.cssMinify = t.minify === c.SAME_FILE ? "esbuild" : !1, l)
    );
    if (i.push(u(o, t?.vite)), t?.minify === c.SEPARATE_FILE) {
      const l = F(s), a = A(
        e,
        l,
        t,
        (m) => (m.build.minify = "esbuild", m.build.cssMinify = "esbuild", m)
      );
      i.push(u(a, t?.vite));
    }
  }
  return i;
}
function A(e, n, t, r) {
  n = b(n);
  const i = D(
    void 0,
    n,
    (s) => {
      s.build.rollupOptions.input = e;
      for (const o of v(s.build.rollupOptions.output))
        o.assetFileNames = String(o.entryFileNames), delete o.entryFileNames;
      return s.build.cssCodeSplit = !0, s.css = {
        modules: {
          scopeBehaviour: "global"
          // 或是 'global'
        },
        transformer: "postcss"
      }, s.plugins = [
        {
          name: "drop-vite-facade-css",
          generateBundle(o, l) {
            for (const [a, m] of Object.entries(l))
              m.type === "asset" && a === "__plaecholder__.min.css" && delete l[a];
          }
        }
      ], s;
    }
  );
  return u(
    i,
    r,
    t.vite
  );
}
function z(e, n) {
  const t = /* @__PURE__ */ new Set();
  return {
    name: "clean-output",
    outputOptions(r) {
      if (e === !1)
        return r;
      const i = r.dir ? r.dir : r.file ? W.dirname(r.file) : null;
      i && t.add(i);
    },
    async generateBundle(r) {
      if (e === !1)
        return;
      const i = t.values().map(async (s) => {
        if (n && console.log(`Clean: ${f.yellow(s)}`), typeof e == "function")
          return e(s, r);
        if (s)
          return x(s);
      });
      await Promise.all(i);
    }
  };
}
async function U(e, n, t = {}) {
  function r(i) {
    return [
      z(t.clean || !1, t.verbose),
      R(
        u(
          {
            target: t?.target || "esnext",
            tsconfig: t?.tsconfig ?? "./tsconfig.json"
          },
          i
        )
      )
    ];
  }
  return V(
    n,
    t,
    (i, s) => s ? {
      input: e,
      output: i,
      plugins: r({
        minify: !0,
        sourceMap: !0
      })
    } : {
      input: e,
      output: i,
      plugins: r({
        minify: t?.minify === c.SAME_FILE,
        sourceMap: t?.minify === c.SAME_FILE
      })
    }
  );
}
function V(e, n, t) {
  n.verbose ??= j;
  const r = E(e, { format: n?.format || "es" });
  for (const o of r)
    o.format === "umd" && (o.name = n?.umdName);
  const i = [], s = t(r, !1);
  if (i.push(u(s, n.vite)), n?.minify === c.SEPARATE_FILE) {
    const o = r.map((a) => F(a)), l = t(o, !0);
    i.push(u(l, n?.vite));
  }
  return i;
}
const q = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  MinifyOptions: c,
  css: B,
  js: U
}, Symbol.toStringTag, { value: "Module" }));
async function G(e) {
  for (const n in e) {
    const t = e[n];
    console.log(`▶️ - ${f.cyan(n)} Start...`);
    for (const r of t)
      await _(S(r));
    console.log(`✅ - ${f.cyan(n)} completed.`);
  }
}
async function J(e) {
  let n = e.path;
  if (process.platform === "win32") {
    const r = n.replace(/\\/g, "/");
    r.startsWith("file://") || (n = `file:///${r}`);
  }
  return { ...await import(n) };
}
async function h(e, n = !1) {
  return !n && Array.isArray(e) ? (await Promise.all(e.map((r) => h(r, !0)))).flat() : w(typeof e == "function" ? await e() : await e, e?.name);
}
async function w(e, n) {
  if (!Array.isArray(e))
    return [await e];
  const t = await Promise.all(e), r = [];
  for (const i of t)
    Array.isArray(i) ? r.push(...i) : r.push(i);
  return r;
}
function H(e, n) {
  const t = K(e, n);
  if (!t)
    throw new Error("No config file found. Please create a fusionfile.js or fusionfile.ts in the root directory.");
  return t;
}
function K(e, n) {
  let t = n?.config;
  return t ? (P(t) || (t = p(e, t)), y(t) ? {
    path: t,
    // get filename from file path
    filename: t.split("/").pop() || "",
    type: X(t),
    ts: Y(t)
  } : null) : Q(e);
}
function Q(e) {
  let n = p(e, "fusionfile.js");
  return y(n) ? {
    path: n,
    // get filename from file path
    filename: n.split("/").pop() || "",
    type: "commonjs",
    ts: !1
  } : (n = p(e, "fusionfile.mjs"), y(n) ? {
    path: n,
    // get filename from file path
    filename: n.split("/").pop() || "",
    type: "module",
    ts: !1
  } : (n = p(e, "fusionfile.ts"), y(n) ? {
    path: n,
    // get filename from file path
    filename: n.split("/").pop() || "",
    type: "module",
    ts: !0
  } : (n = p(e, "fusionfile.mts"), y(n) ? {
    path: n,
    // get filename from file path
    filename: n.split("/").pop() || "",
    type: "module",
    ts: !0
  } : null)));
}
function X(e) {
  let n = "unknown";
  return e.endsWith(".cjs") ? n = "commonjs" : (e.endsWith(".mjs") || e.endsWith(".ts") || e.endsWith(".mts")) && (n = "module"), n;
}
function Y(e) {
  return e.endsWith(".ts") || e.endsWith(".mts");
}
async function Z(e) {
  const n = Object.keys(e);
  n.sort((i, s) => i === "default" ? -1 : s === "default" ? 1 : i.localeCompare(s));
  const t = [];
  for (const i of n) {
    const s = e[i], o = await h(s, !0);
    t.push(await T(i, o));
  }
  const r = C({
    label: f.magenta("Available Tasks"),
    nodes: t
  });
  console.log(r);
}
async function T(e, n) {
  const t = [];
  Array.isArray(n) || (n = [n]);
  for (const r of n)
    if (typeof r == "function") {
      let i = await h(r, !0);
      t.push(
        await T(r.name, i)
      );
    } else
      t.push(ee(r));
  return {
    label: f.cyan(e),
    nodes: t
  };
}
function ee(e, n = 4) {
  const t = [], r = e.build?.lib;
  if (r && r.entry) {
    const s = r.entry;
    let o = "";
    typeof s == "string" ? o = f.yellow(s) : Array.isArray(s) ? o = f.yellow(s.join(", ")) : typeof s == "object" && (o = f.yellow(Object.values(s).join(", "))), t.push(`Input: ${o}`);
  }
  const i = e.build?.rollupOptions?.output;
  return i && (Array.isArray(i) ? i : [i]).forEach((o, l) => {
    let a = "";
    o.file ? a = f.green(o.file) : o.dir && (a = f.green(o.dir)), t.push(`Output[${l}]: ${a}`);
  }), t.join(" - ");
}
function ne(e, n) {
  e = $(e), e.length === 0 && e.push("default");
  const t = {};
  for (const r of e)
    if (n[r])
      t[r] = n[r];
    else
      throw new Error(`Task "${f.cyan(r)}" not found in fusion config.`);
  return t;
}
async function te(e) {
  const n = {}, t = {};
  for (const r in e) {
    const i = e[r];
    t[r] = await g(r, i, n);
  }
  return t;
}
async function g(e, n, t) {
  const r = [];
  if (Array.isArray(n))
    for (const i in n) {
      const s = n[i];
      r.push(...await g(i, s, t));
    }
  else if (typeof n == "function") {
    if (e = n.name || e, t[e])
      return [];
    t[e] = n;
    const i = await h(n, !0);
    if (Array.isArray(i))
      for (const s in i) {
        const o = i[s];
        r.push(...await g(s, o, t));
      }
  } else
    r.push(await n);
  return r;
}
function re() {
  const e = L();
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
  }), e.option("verbose", {
    alias: "v",
    type: "count",
    description: "Increase verbosity of output. Use multiple times for more verbosity."
  }), e.parseSync(M(process.argv));
}
async function ie(e) {
  try {
    await se(e);
  } catch (n) {
    if (n instanceof Error) {
      if (e.verbose && e.verbose > 0)
        throw n;
      console.error(n), process.exit(1);
    } else
      throw n;
  }
}
async function se(e) {
  let n = e?.cwd, t;
  n ? (t = n = p(n), process.chdir(n)) : t = process.cwd();
  const r = H(t, e), i = await J(r);
  if (e.list) {
    await Z(i);
    return;
  }
  const s = ne([...e._], i), o = await te(s);
  await G(o);
}
let d;
const oe = process.argv[1] && I(import.meta.url) === process.argv[1], we = {
  ...q,
  params: d
};
oe && (d = re(), ie(d));
const j = d?.verbose ? d?.verbose > 0 : !1;
export {
  c as MinifyOptions,
  B as css,
  we as default,
  j as isVerbose,
  U as js
};
//# sourceMappingURL=index.js.map
