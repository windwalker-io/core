import { getGlobBaseFromPattern as M, callback as D, css as V, js as q, shortHash as T, plugin as B, callbackAfterBuild as R, copyGlob as W, symlink as E } from "@windwalker-io/fusion-next";
import j from "is-glob";
import J from "micromatch";
import $, { relative as x, normalize as v, resolve as m } from "node:path";
import S from "node:fs";
import w from "fast-glob";
import { randomBytes as G } from "node:crypto";
import { createRequire as z } from "node:module";
import a from "fs-extra";
import { parse as P } from "node-html-parser";
function b(t) {
  return S.existsSync(t) ? JSON.parse(S.readFileSync(t, "utf8")) : null;
}
function H(t) {
  return j(F(t));
}
function F(t) {
  return t.replace(/(\/\*|\/\*\*?|\*\*\/\*?)$/, "");
}
const K = process.platform === "win32" ? "\\" : "/";
function ye(t, e = K) {
  return t.endsWith(e) ? t : t + e;
}
function A(t) {
  let e = [];
  for (const s of t)
    e = [
      ...e,
      ...Q(s)
    ];
  return e;
}
function Q(t) {
  return w.globSync(t).map((e) => (e = e.replace(/\\/g, "/"), {
    fullpath: e,
    relativePath: x(M(t), e).replace(/\\/g, "/")
  }));
}
function ge(t = "") {
  const e = $.resolve(process.cwd(), "composer.json"), s = b(e), r = Object.keys(s.require || {}).concat(Object.keys(s["require-dev"] || {})).map((o) => `vendor/${o}/composer.json`).map((o) => b(o)).filter((o) => o?.extra?.windwalker != null).map((o) => o?.extra?.windwalker?.modules?.map((n) => `vendor/${o.name}/${n}/${t}`) || []).flat();
  return [...new Set(r)];
}
function U(t = "", e = 16) {
  let s = G(e).toString("hex");
  return t && (s = t + s), s;
}
function $e(t, e) {
  return z(t).resolve(e);
}
function C(t) {
  const e = t.indexOf("?");
  return e !== -1 ? t.substring(0, e) : t;
}
function be(t) {
  return D((e, s) => {
    const r = O(t);
    return N(s, r), _(s, Object.keys(t)), null;
  });
}
function O(t) {
  const e = {};
  for (const s in t)
    H(s) || (e[s] = t[s]);
  return e;
}
function _(t, e) {
  const s = U("hidden:clone-asset-") + ".js";
  t.addTask(s), t.resolveIdCallbacks.push((r) => {
    if (r === s)
      return s;
  }), t.loadCallbacks.push((r) => {
    if (r === s)
      return `import.meta.glob(${e.map((n) => n.replace(/\\/g, "/")).map((n) => n.startsWith("./") || !n.startsWith("/") ? `/${n}` : n).map((n) => `'${n}'`).join(", ")});
`;
  });
}
function N(t, e) {
  t.assetFileNamesCallbacks.push((s) => {
    const r = s.originalFileName;
    for (const o in e)
      if (X(r, o))
        return v(e[o] + x(F(o), r)).replace(/\\/g, "/");
  });
}
function X(t, e) {
  return j(e) ? J.isMatch(t, e) : t.startsWith(e);
}
function ke(t) {
  return {
    name: "core:global-assets",
    buildConfig(e) {
      const s = t.clone || {};
      let r = t.reposition || {};
      r = { ...r, ...O(s) }, N(e, r);
      const o = Object.keys(s);
      o.length > 0 && _(e, o);
    }
  };
}
function ve(t, e) {
  return t ??= m("node_modules/systemjs/dist/system.min.js"), {
    name: "core:inject-systemjs",
    async generateBundle(s, r) {
      if (s.format !== "system")
        return;
      const o = a.readFileSync(
        m(t),
        "utf-8"
      );
      for (const n of Object.values(r))
        e && !e(n) || n.type === "chunk" && n.isEntry && n.fileName.endsWith(".js") && (n.code = o + `
` + n.code);
    }
  };
}
function we() {
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
function Se(t, e) {
  return new Y(V(t, e));
}
class Y {
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
      s.loadCallbacks.push((n, d) => {
        const l = C(n);
        if (v(l) === m(o.input)) {
          const c = w.globSync(
            this.cssPatterns.map((p) => m(p)).map((p) => p.replace(/\\/g, "/"))
          ).map((p) => `@import "${p}";`).concat(Z(this.bladePatterns)).join(`
`);
          let u = a.readFileSync(l, "utf-8");
          return u += `

${c}
`, u;
        }
      });
  }
  preview() {
    return [];
  }
}
function Z(t) {
  return w.globSync(t).map((s) => {
    const r = a.readFileSync(s, "utf8");
    return P(r).querySelectorAll("style[type],script[type]").filter(
      (n) => ["text/scss", "text/css"].includes(n.getAttribute("type") || "")
    ).map((n) => {
      const d = n.getAttribute("data-scope");
      return d ? `${d} {
          ${n.innerHTML}
        }` : n.innerHTML;
    });
  }).filter((s) => s.length > 0).flat();
}
function je(t, e, s = {}) {
  return new ee(q(t, e), s);
}
class ee {
  constructor(e, s = {}) {
    this.processor = e, this.options = s;
  }
  scriptPatterns = [];
  bladePatterns = [];
  stagePrefix = "";
  config(e, s) {
    const o = this.processor.config(e, s)[0], n = this.options.tmpPath ?? m("./tmp/fusion/jsmodules/").replace(/\\/g, "/");
    (this.options.cleanTmp ?? !0) && s.postBuildCallbacks.push(() => {
      a.removeSync(n);
    }), this.ignoreMainImport(o), s.resolveIdCallbacks.push((l) => {
      if (l === "@main")
        return { id: l, external: !0 };
    }), s.loadCallbacks.push((l, i) => {
      const c = C(l);
      if (v(c) === m(o.input)) {
        const u = A(this.scriptPatterns);
        let p = `{
`;
        for (const h of u) {
          let g = h.fullpath;
          if (g.endsWith(".d.ts"))
            continue;
          let f = h.relativePath.replace(/assets\//, "").toLowerCase();
          g = m(g).replace(/\\/g, "/"), f = f.substring(0, f.lastIndexOf(".")) + ".js", this.stagePrefix && (f = this.stagePrefix + "/" + f), p += `'${f}': () => import('${g}'),
`;
        }
        const y = te(this.bladePatterns);
        a.ensureDirSync(n);
        for (const h of y) {
          let g = h.as;
          const f = n + "/" + h.path.replace(/\\|\//g, "_") + "-" + T(h.code) + ".ts";
          a.writeFileSync(f, h.code), p += `'inline:${g}': () => import('${f}'),
`;
        }
        p += "}";
        const L = `
import { App } from '${m("./vendor/windwalker/core/assets/core/src/next/app.ts").replace(/\\/g, "/")}';

const app = new App();
app.registerRoutes(${p});

export default app;
  `;
        return a.readFileSync(c, "utf-8") + `

` + L;
      }
    });
  }
  /**
   * @see https://github.com/vitejs/vite/issues/6393#issuecomment-1006819717
   * @see https://stackoverflow.com/questions/76259677/vite-dev-server-throws-error-when-resolving-external-path-from-importmap
   */
  ignoreMainImport(e) {
    const s = "/@id/", r = ["@main"], o = new RegExp(
      `${s}(${r.join("|")})`,
      "g"
    );
    B({
      name: "keep-main-external-" + e.id,
      transform(n) {
        return o.test(n) ? n.replace(o, (d, l) => l) : n;
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
function te(t) {
  return A(Array.isArray(t) ? t : [t]).map((s) => {
    const r = a.readFileSync(s.fullpath, "utf8");
    return P(r).querySelectorAll("script[lang][data-as]").filter(
      (n) => ["ts", "typescript"].includes(n.getAttribute("lang") || "")
    ).map((n) => ({
      as: n.getAttribute("data-as") || "",
      file: s.relativePath,
      path: s.relativePath.replace(/.blade.php$/, ""),
      code: n.innerHTML
    })).filter((n) => n.code.trim() !== "");
  }).flat();
}
function xe(t = [], e = "www/assets/vendor") {
  return R(() => se(t, e));
}
async function se(t = [], e = "www/assets/vendor") {
  const s = e;
  let r = t;
  const o = process.env.INSTALL_VENDOR === "hard" ? "Copy" : "Link";
  console.log(""), a.existsSync(s) || a.mkdirSync(s);
  const n = a.readdirSync(s, { withFileTypes: !0 }).filter((i) => i.isDirectory()).map((i) => $.join(s, i.name));
  n.unshift(s), n.forEach((i) => {
    I(i);
  });
  const d = re().map((i) => `vendor/${i}/composer.json`).map((i) => b(i)).filter((i) => i?.extra?.windwalker != null);
  r = ne(d).concat(r), r = [...new Set(r)];
  for (const i of r)
    a.existsSync(`node_modules/${i}/`) && (console.log(`[${o} NPM] node_modules/${i}/ => ${s}/${i}/`), k(`node_modules/${i}/`, `${s}/${i}/`));
  for (const i of d) {
    const c = i.name;
    let u = i?.extra?.windwalker?.assets?.link;
    u && (u.endsWith("/") || (u += "/"), a.existsSync(`vendor/${c}/${u}`) && (console.log(`[${o} Composer] vendor/${c}/${u} => ${s}/${c}/`), k(`vendor/${c}/${u}`, `${s}/${c}/`)));
  }
  const l = "resources/assets/vendor/";
  if (a.existsSync(l)) {
    const i = a.readdirSync(l);
    for (const c of i)
      if (c.startsWith("@")) {
        const u = a.readdirSync(l + c);
        for (const p of u) {
          const y = c + "/" + p;
          console.log(`[${o} Local] resources/assets/vendor/${y}/ => ${s}/${y}/`), k(l + y + "/", `${s}/${y}/`);
        }
      } else
        console.log(`[${o} Local] resources/assets/vendor/${c}/ => ${s}/${c}/`), k(l + c, `${s}/${c}/`);
  }
}
async function k(t, e) {
  process.env.INSTALL_VENDOR === "hard" ? await W(t + "/**/*", e) : await E(t, e);
}
function ne(t = []) {
  const e = $.resolve(process.cwd(), "package.json"), s = b(e);
  let r = Object.keys(s.devDependencies || {}).concat(Object.keys(s.dependencies || {})).map((n) => `node_modules/${n}/package.json`).map((n) => b(n)).filter((n) => n?.windwalker != null).map((n) => n?.windwalker.vendors || []).flat();
  const o = t.map((n) => [
    ...n?.extra?.windwalker?.asset_vendors || [],
    ...n?.extra?.windwalker?.assets?.exposes || [],
    ...Object.keys(n?.extra?.windwalker?.assets?.vendors || {})
  ]).flat();
  return [...new Set(r.concat(o))];
}
function re() {
  const t = $.resolve(process.cwd(), "composer.json"), e = b(t);
  return [
    ...new Set(
      Object.keys(e.require || {}).concat(Object.keys(e["require-dev"] || {}))
    )
  ];
}
function I(t) {
  if (!a.existsSync(t))
    return;
  const e = a.readdirSync(t, { withFileTypes: !0 });
  for (const s of e)
    s.isSymbolicLink() || s.isFile() ? a.unlinkSync($.join(t, s.name)) : s.isDirectory() && I($.join(t, s.name));
  a.rmdirSync(t);
}
export {
  be as cloneAssets,
  H as containsMiddleGlob,
  Se as cssModulize,
  ye as ensureDirPath,
  A as findFilesFromGlobArray,
  ge as findModules,
  ke as globalAssets,
  ve as injectSystemJS,
  xe as installVendors,
  je as jsModulize,
  b as loadJson,
  F as removeLastGlob,
  $e as resolveModuleRealpath,
  C as stripUrlQuery,
  we as systemCSSFix,
  U as uniqId
};
