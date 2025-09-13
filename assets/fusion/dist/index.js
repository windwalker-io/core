import { normalize as M, dirname as L } from "node:path";
import { merge as P, cloneDeep as O } from "lodash-es";
import D from "autoprefixer";
import { resolve as b } from "path";
import { mergeConfig as E } from "vite";
import I from "@vitejs/plugin-vue";
const B = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  get MinifyOptions() {
    return f;
  },
  get css() {
    return j;
  },
  get isDev() {
    return S;
  },
  get isProd() {
    return N;
  },
  get isVerbose() {
    return p;
  },
  get js() {
    return V;
  },
  get vue() {
    return T;
  }
}, Symbol.toStringTag, { value: "Module" }));
var f = /* @__PURE__ */ ((e) => (e.NONE = "none", e.SAME_FILE = "same_file", e.SEPARATE_FILE = "separate_file", e))(f || {});
function A(e) {
  return Array.isArray(e) ? e : [e];
}
function F(e, t) {
  return Array.isArray(e) ? e.map(t) : t(e);
}
function h(e, t = {}) {
  return e = F(e, (r) => (typeof r == "string" && (r.endsWith("/") ? r = {
    dir: r,
    ...t
  } : r = {
    dir: L(r),
    // Get file name with node library, consider Windows
    entryFileNames: M(r).replace(/\\/g, "/").split("/").pop(),
    ...t
  }), r)), A(e);
}
function u(e, ...t) {
  if (e ??= {}, !t.length)
    return e;
  for (const r of t)
    r && (typeof r == "function" ? e = r(e) ?? e : e = P(e, r));
  return e;
}
function _(e) {
  if (e = O(e), e.file) {
    const t = e.file.split("."), r = t.pop();
    e.file = `${t.join(".")}.min.${r}`;
  } else if (e.dir && typeof e.entryFileNames == "string") {
    const t = e.entryFileNames.split("."), r = t.pop();
    e.entryFileNames = `${t.join(".")}.min.${r}`;
  }
  return e;
}
function c(e, t) {
  return u(
    {
      entry: e
    },
    t
  );
}
function o(e, t, r = [], n) {
  return u(
    {
      resolve: {},
      build: {
        lib: e,
        rollupOptions: {
          output: t
        },
        emptyOutDir: !1,
        target: "esnext"
      },
      plugins: r
    },
    n
  );
}
async function j(e, t, r = {}) {
  r.verbose ??= p;
  let n = h(t, { format: "es" });
  const i = [];
  for (const s of n) {
    const l = v(
      e,
      n,
      r,
      (a) => (a.build.minify = r.minify === f.SAME_FILE ? "esbuild" : !1, a.build.cssMinify = r.minify === f.SAME_FILE ? "esbuild" : !1, a)
    );
    if (i.push(u(l, r?.vite)), r?.minify === f.SEPARATE_FILE) {
      const a = _(s), m = v(
        e,
        a,
        r,
        (d) => (d.build.minify = "esbuild", d.build.cssMinify = "esbuild", d)
      );
      i.push(u(m, r?.vite));
    }
  }
  return i;
}
function v(e, t, r, n) {
  t = O(t);
  const i = o(
    void 0,
    t,
    [],
    (s) => {
      s.build.rollupOptions.input = e, s.build.emptyOutDir = r.clean ?? !1;
      for (const l of A(s.build.rollupOptions.output))
        l.assetFileNames = String(l.entryFileNames), delete l.entryFileNames;
      return s.build.cssCodeSplit = !0, s.css = {
        // modules: {
        //   scopeBehaviour: 'global', // 或是 'global'
        // },
        transformer: "postcss",
        postcss: u(
          {
            plugins: [
              D({ overrideBrowserslist: r.browserslist })
            ]
          },
          r.postcss
        )
      }, s;
    }
  );
  return u(
    i,
    n,
    r.vite
  );
}
async function V(e, t, r = {}) {
  return x(
    t,
    r,
    (n, i) => i ? o(
      c(e),
      n,
      [],
      (s) => y(s, r)
    ) : o(
      c(e),
      n,
      [],
      (s) => y(s, r)
    )
  );
}
function x(e, t, r) {
  t.verbose ??= p;
  const n = h(e, { format: t?.format || "es" });
  for (const l of n)
    l.format === "umd" && (l.name = t?.umdName);
  const i = [], s = r(n, !1);
  if (i.push(u(s, t.vite)), t?.minify === f.SEPARATE_FILE) {
    const l = n.map((m) => _(m)), a = r(l, !0);
    i.push(u(a, t?.vite));
  }
  return i;
}
function y(e, t) {
  const r = u(
    {
      target: t?.target || "esnext"
    },
    t?.esbuild
  );
  if (e.build.minify = t?.minify === f.SAME_FILE ? "esbuild" : !1, e.build.emptyOutDir = t.clean || !1, e.build.target = t.target || "esnext", e.esbuild = r, e = w(e, t.externals), t.path)
    if (e = E(e, { resolve: { alias: {} } }), typeof t.path == "string")
      e.resolve.alias = {
        "@": b(t.path)
      };
    else {
      const n = {};
      for (const i in t.path)
        n[i] = b(t.path[i]);
      e.resolve.alias = n;
    }
  return e;
}
function w(e, t) {
  if (!t)
    return e;
  if (e = E(e, { build: { rollupOptions: { external: [] } } }), !Array.isArray(e.build.rollupOptions.external))
    throw new Error("Only array externals are supported now.");
  for (const r in t)
    e.build.rollupOptions.external.includes(r) || e.build.rollupOptions.external.push(r);
  return e.build.rollupOptions.output = F(e.build.rollupOptions.output, (r) => (r.globals = {
    ...r.globals,
    ...t
  }, r)), e;
}
async function T(e, t, r = {}) {
  return x(
    t,
    r,
    (n, i) => o(
      c(e),
      n,
      [
        I()
      ],
      (s) => (s = y(s, r), s.build.sourcemap = S ? "inline" : !1, s)
    )
  );
}
let p = !1;
const N = process.env.NODE_ENV === "production", S = !N;
function W(e = {}) {
  return {
    name: "fusion",
    config(t, r) {
      console.log(r);
    }
  };
}
export {
  f as MinifyOptions,
  j as css,
  B as default,
  S as isDev,
  N as isProd,
  p as isVerbose,
  V as js,
  W as useFusion,
  T as vue
};
//# sourceMappingURL=index.js.map
