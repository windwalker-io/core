import { getGlobBaseFromPattern as M, callback as T, css as q, js as V, shortHash as B, callbackAfterBuild as D, copyGlob as I, symlink as W } from "@windwalker-io/fusion-next";
import S from "is-glob";
import J from "micromatch";
import $, { relative as j, normalize as P, resolve as h } from "node:path";
import w from "node:fs";
import v from "fast-glob";
import { randomBytes as R } from "node:crypto";
import { createRequire as G } from "node:module";
import a from "fs-extra";
import { parse as x } from "node-html-parser";
function b(t) {
  return w.existsSync(t) ? JSON.parse(w.readFileSync(t, "utf8")) : null;
}
function z(t) {
  return S(F(t));
}
function F(t) {
  return t.replace(/(\/\*|\/\*\*?|\*\*\/\*?)$/, "");
}
const E = process.platform === "win32" ? "\\" : "/";
function he(t, e = E) {
  return t.endsWith(e) ? t : t + e;
}
function C(t) {
  let e = [];
  for (const s of t)
    e = [
      ...e,
      ...H(s)
    ];
  return e;
}
function H(t) {
  return v.globSync(t).map((e) => (e = e.replace(/\\/g, "/"), {
    fullpath: e,
    relativePath: j(M(t), e).replace(/\\/g, "/")
  }));
}
function ye(t = "") {
  const e = $.resolve(process.cwd(), "composer.json"), s = b(e), r = Object.keys(s.require || {}).concat(Object.keys(s["require-dev"] || {})).map((o) => `vendor/${o}/composer.json`).map((o) => b(o)).filter((o) => o?.extra?.windwalker != null).map((o) => o?.extra?.windwalker?.modules?.map((n) => `vendor/${o.name}/${n}/${t}`) || []).flat();
  return [...new Set(r)];
}
function Q(t = "", e = 16) {
  let s = R(e).toString("hex");
  return t && (s = t + s), s;
}
function ge(t, e) {
  return G(t).resolve(e);
}
function A(t) {
  const e = t.indexOf("?");
  return e !== -1 ? t.substring(0, e) : t;
}
function $e(t) {
  return T((e, s) => {
    const r = N(t);
    return _(s, r), O(s, Object.keys(t)), null;
  });
}
function N(t) {
  const e = {};
  for (const s in t)
    z(s) || (e[s] = t[s]);
  return e;
}
function O(t, e) {
  const s = Q("hidden:clone-asset-") + ".js";
  t.addTask(s), t.resolveIdCallbacks.push((r) => {
    if (r === s)
      return s;
  }), t.loadCallbacks.push((r) => {
    if (r === s)
      return `import.meta.glob(${e.map((n) => n.replace(/\\/g, "/")).map((n) => n.startsWith("./") || !n.startsWith("/") ? `/${n}` : n).map((n) => `'${n}'`).join(", ")});
`;
  });
}
function _(t, e) {
  t.assetFileNamesCallbacks.push((s) => {
    const r = s.originalFileName;
    for (const o in e)
      if (U(r, o))
        return P(e[o] + j(F(o), r)).replace(/\\/g, "/");
  });
}
function U(t, e) {
  return S(e) ? J.isMatch(t, e) : t.startsWith(e);
}
function be(t) {
  return {
    name: "core:global-assets",
    buildConfig(e) {
      const s = t.clone || {};
      let r = t.reposition || {};
      r = { ...r, ...N(s) }, _(e, r);
      const o = Object.keys(s);
      o.length > 0 && O(e, o);
    }
  };
}
function ke(t, e) {
  return t ??= h("node_modules/systemjs/dist/system.min.js"), {
    name: "core:inject-systemjs",
    async generateBundle(s, r) {
      if (s.format !== "system")
        return;
      const o = a.readFileSync(
        h(t),
        "utf-8"
      );
      for (const n of Object.values(r))
        e && !e(n) || n.type === "chunk" && n.isEntry && n.fileName.endsWith(".js") && (n.code = o + `
` + n.code);
    }
  };
}
function ve() {
  return {
    name: "core:systemjs-css-fix",
    async generateBundle(t, e) {
      if (t.format === "system") {
        for (const [s, r] of Object.entries(e))
          if (s.endsWith(".css") && "code" in r) {
            const o = /__vite_style__\.textContent\s*=\s*"([\s\S]*?)";/, n = r.code.match(o);
            n && n[1] && (r.code = n[1].replace(/\\"/g, '"').replace(/\\n/g, `
`).replace(/\\t/g, "	").replace(/\\\\/g, "\\").replace(/\/\*\$vite\$:\d+\*\/$/, ""));
          }
      }
    }
  };
}
function we(t, e) {
  return new K(q(t, e));
}
class K {
  constructor(e, s = [], r = []) {
    this.processor = e, this.bladePatterns = s, this.cssPatterns = r;
  }
  parseBlades(...e) {
    return this.bladePatterns = this.bladePatterns.concat(e.flat()), this;
  }
  mergeCss(...e) {
    return this.cssPatterns = this.cssPatterns.concat(e.flat()), this;
  }
  config(e, s) {
    const r = this.processor.config(e, s);
    for (const o of r)
      s.loadCallbacks.push((n, u) => {
        const f = A(n);
        if (P(f) === h(o.input)) {
          const c = v.globSync(
            this.cssPatterns.map((p) => h(p)).map((p) => p.replace(/\\/g, "/"))
          ).map((p) => `@import "${p}";`).concat(X(this.bladePatterns)).join(`
`);
          let l = a.readFileSync(f, "utf-8");
          return l += `

${c}
`, l;
        }
      });
  }
  preview() {
    return [];
  }
}
function X(t) {
  return v.globSync(t).map((s) => {
    const r = a.readFileSync(s, "utf8");
    return x(r).querySelectorAll("style[type],script[type]").filter(
      (n) => ["text/scss", "text/css"].includes(n.getAttribute("type") || "")
    ).map((n) => {
      const u = n.getAttribute("data-scope");
      return u ? `${u} {
          ${n.innerHTML}
        }` : n.innerHTML;
    });
  }).filter((s) => s.length > 0).flat();
}
function Se(t, e, s = {}) {
  return new Y(V(t, e), s);
}
class Y {
  constructor(e, s = {}) {
    this.processor = e, this.options = s;
  }
  scriptPatterns = [];
  bladePatterns = [];
  stagePrefix = "";
  config(e, s) {
    this.processor.config(e, s);
    const r = this.options.tmpPath ?? h("./tmp/fusion/jsmodules/").replace(/\\/g, "/"), o = this.options.cleanTmp ?? !0, n = "js/" + this.stagePrefix + "/app.js", u = "resources/assets/src/" + this.stagePrefix + "/app.js", f = s.addTask(n);
    o && s.postBuildCallbacks.push(() => {
      a.removeSync(r);
    }), s.merge({
      resolve: {
        alias: {
          //
        }
      }
    }), s.entryFileNamesCallbacks.push((i) => {
      if (i.facadeModuleId === u)
        return n;
    }), s.resolveIdCallbacks.push((i) => {
      if (i === f.input)
        return u;
    }), s.loadCallbacks.push((i, c) => {
      if (A(i), i === u) {
        const l = C(this.scriptPatterns);
        let p = `{
`;
        for (const m of l) {
          let g = m.fullpath;
          if (g.endsWith(".d.ts"))
            continue;
          let d = m.relativePath.replace(/assets\//, "").toLowerCase();
          g = h(g).replace(/\\/g, "/"), d = d.substring(0, d.lastIndexOf(".")) + ".js", this.stagePrefix && (d = this.stagePrefix + "/" + d), p += `'${d}': () => import('${g}'),
`;
        }
        const y = Z(this.bladePatterns);
        a.ensureDirSync(r);
        for (const m of y) {
          let g = m.as;
          const d = r + "/" + m.path.replace(/\\|\//g, "_") + "-" + B(m.code) + ".ts";
          a.writeFileSync(d, m.code), p += `'inline:${g}': () => import('${d}'),
`;
        }
        return p += "}", `
import { App } from '${h("./vendor/windwalker/core/assets/core/src/next/app.ts").replace(/\\/g, "/")}';

const app = new App();
app.registerRoutes(${p});

export default app;
  `;
      }
    });
  }
  preview() {
    return [];
  }
  mergeScripts(...e) {
    return this.scriptPatterns = this.scriptPatterns.concat(e.flat()), this;
  }
  parseBlades(...e) {
    return this.bladePatterns = this.bladePatterns.concat(e.flat()), this;
  }
  stage(e) {
    return this.stagePrefix = e, this;
  }
}
function Z(t) {
  return C(Array.isArray(t) ? t : [t]).map((s) => {
    const r = a.readFileSync(s.fullpath, "utf8");
    return x(r).querySelectorAll("script[lang][data-as]").filter(
      (n) => ["ts", "typescript"].includes(n.getAttribute("lang") || "")
    ).map((n) => ({
      as: n.getAttribute("data-as") || "",
      file: s.relativePath,
      path: s.relativePath.replace(/.blade.php$/, ""),
      code: n.innerHTML
    })).filter((n) => n.code.trim() !== "");
  }).flat();
}
function je(t = [], e = "www/assets/vendor") {
  return D(() => ee(t, e));
}
async function ee(t = [], e = "www/assets/vendor") {
  const s = e;
  let r = t;
  const o = process.env.INSTALL_VENDOR === "hard" ? "Copy" : "Link";
  console.log(""), a.existsSync(s) || a.mkdirSync(s);
  const n = a.readdirSync(s, { withFileTypes: !0 }).filter((i) => i.isDirectory()).map((i) => $.join(s, i.name));
  n.unshift(s), n.forEach((i) => {
    L(i);
  });
  const u = se().map((i) => `vendor/${i}/composer.json`).map((i) => b(i)).filter((i) => i?.extra?.windwalker != null);
  r = te(u).concat(r), r = [...new Set(r)];
  for (const i of r)
    a.existsSync(`node_modules/${i}/`) && (console.log(`[${o} NPM] node_modules/${i}/ => ${s}/${i}/`), k(`node_modules/${i}/`, `${s}/${i}/`));
  for (const i of u) {
    const c = i.name;
    let l = i?.extra?.windwalker?.assets?.link;
    l && (l.endsWith("/") || (l += "/"), a.existsSync(`vendor/${c}/${l}`) && (console.log(`[${o} Composer] vendor/${c}/${l} => ${s}/${c}/`), k(`vendor/${c}/${l}`, `${s}/${c}/`)));
  }
  const f = "resources/assets/vendor/";
  if (a.existsSync(f)) {
    const i = a.readdirSync(f);
    for (const c of i)
      if (c.startsWith("@")) {
        const l = a.readdirSync(f + c);
        for (const p of l) {
          const y = c + "/" + p;
          console.log(`[${o} Local] resources/assets/vendor/${y}/ => ${s}/${y}/`), k(f + y + "/", `${s}/${y}/`);
        }
      } else
        console.log(`[${o} Local] resources/assets/vendor/${c}/ => ${s}/${c}/`), k(f + c, `${s}/${c}/`);
  }
}
async function k(t, e) {
  process.env.INSTALL_VENDOR === "hard" ? await I(t + "/**/*", e) : await W(t, e);
}
function te(t = []) {
  const e = $.resolve(process.cwd(), "package.json"), s = b(e);
  let r = Object.keys(s.devDependencies || {}).concat(Object.keys(s.dependencies || {})).map((n) => `node_modules/${n}/package.json`).map((n) => b(n)).filter((n) => n?.windwalker != null).map((n) => n?.windwalker.vendors || []).flat();
  const o = t.map((n) => [
    ...n?.extra?.windwalker?.asset_vendors || [],
    ...n?.extra?.windwalker?.assets?.exposes || [],
    ...Object.keys(n?.extra?.windwalker?.assets?.vendors || {})
  ]).flat();
  return [...new Set(r.concat(o))];
}
function se() {
  const t = $.resolve(process.cwd(), "composer.json"), e = b(t);
  return [
    ...new Set(
      Object.keys(e.require || {}).concat(Object.keys(e["require-dev"] || {}))
    )
  ];
}
function L(t) {
  if (!a.existsSync(t))
    return;
  const e = a.readdirSync(t, { withFileTypes: !0 });
  for (const s of e)
    s.isSymbolicLink() || s.isFile() ? a.unlinkSync($.join(t, s.name)) : s.isDirectory() && L($.join(t, s.name));
  a.rmdirSync(t);
}
export {
  $e as cloneAssets,
  z as containsMiddleGlob,
  we as cssModulize,
  he as ensureDirPath,
  C as findFilesFromGlobArray,
  ye as findModules,
  be as globalAssets,
  ke as injectSystemJS,
  je as installVendors,
  Se as jsModulize,
  b as loadJson,
  F as removeLastGlob,
  ge as resolveModuleRealpath,
  A as stripUrlQuery,
  ve as systemCSSFix,
  Q as uniqId
};
