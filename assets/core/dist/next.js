import { getGlobBaseFromPattern as q, callback as T, css as V, js as B, shortHash as D, callbackAfterBuild as W, copyGlob as I, symlink as J } from "@windwalker-io/fusion-next";
import j from "is-glob";
import G from "micromatch";
import $, { relative as P, normalize as w, resolve as p } from "node:path";
import S from "node:fs";
import v from "fast-glob";
import { randomBytes as R } from "node:crypto";
import { createRequire as z } from "node:module";
import a from "fs-extra";
import { parse as x } from "node-html-parser";
function k(t) {
  return S.existsSync(t) ? JSON.parse(S.readFileSync(t, "utf8")) : null;
}
function E(t) {
  return j(F(t));
}
function F(t) {
  return t.replace(/(\/\*|\/\*\*?|\*\*\/\*?)$/, "");
}
const H = process.platform === "win32" ? "\\" : "/";
function he(t, e = H) {
  return t.endsWith(e) ? t : t + e;
}
function C(t) {
  let e = [];
  for (const s of t)
    e = [
      ...e,
      ...Q(s)
    ];
  return e;
}
function Q(t) {
  return v.globSync(t).map((e) => (e = e.replace(/\\/g, "/"), {
    fullpath: e,
    relativePath: P(q(t), e).replace(/\\/g, "/")
  }));
}
function ye(t = "") {
  const e = $.resolve(process.cwd(), "composer.json"), s = k(e), r = Object.keys(s.require || {}).concat(Object.keys(s["require-dev"] || {})).map((o) => `vendor/${o}/composer.json`).map((o) => k(o)).filter((o) => o?.extra?.windwalker != null).map((o) => o?.extra?.windwalker?.modules?.map((n) => `vendor/${o.name}/${n}/${t}`) || []).flat();
  return [...new Set(r)];
}
function U(t = "", e = 16) {
  let s = R(e).toString("hex");
  return t && (s = t + s), s;
}
function ge(t, e) {
  return z(t).resolve(e);
}
function A(t) {
  const e = t.indexOf("?");
  return e !== -1 ? t.substring(0, e) : t;
}
function $e(t) {
  return T((e, s) => {
    const r = O(t);
    return N(s, r), L(s, Object.keys(t)), null;
  });
}
function O(t) {
  const e = {};
  for (const s in t)
    E(s) || (e[s] = t[s]);
  return e;
}
function L(t, e) {
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
      if (K(r, o))
        return w(e[o] + P(F(o), r)).replace(/\\/g, "/");
  });
}
function K(t, e) {
  return j(e) ? G.isMatch(t, e) : t.startsWith(e);
}
function ke(t) {
  return {
    name: "ww:assets",
    buildConfig(e) {
      const s = t.clone || {};
      let r = t.reposition || {};
      r = { ...r, ...O(s) }, N(e, r);
      const o = Object.keys(s);
      o.length > 0 && L(e, o);
    }
  };
}
function be(t, e) {
  return t ??= p("node_modules/systemjs/dist/system.min.js"), {
    name: "inject-systemjs",
    async generateBundle(s, r) {
      if (s.format !== "system")
        return;
      const o = a.readFileSync(
        p(t),
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
    name: "systemjs.css.fix",
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
function ve(t, e) {
  return new X(V(t, e));
}
class X {
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
      s.loadCallbacks.push((n, m) => {
        const d = A(n);
        if (w(d) === p(o.input)) {
          const c = v.globSync(
            this.cssPatterns.map((u) => p(u)).map((u) => u.replace(/\\/g, "/"))
          ).map((u) => `@import "${u}";`).concat(Y(this.bladePatterns)).join(`
`);
          let l = a.readFileSync(d, "utf-8");
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
function Y(t) {
  return v.globSync(t).map((s) => {
    const r = a.readFileSync(s, "utf8");
    return x(r).querySelectorAll("style[type],script[type]").filter(
      (n) => ["text/scss", "text/css"].includes(n.getAttribute("type") || "")
    ).map((n) => {
      const m = n.getAttribute("data-scope");
      return m ? `${m} {
          ${n.innerHTML}
        }` : n.innerHTML;
    });
  }).filter((s) => s.length > 0).flat();
}
function Se(t, e, s = {}) {
  return new Z(B(t, e), s);
}
class Z {
  constructor(e, s = {}) {
    this.processor = e, this.options = s;
  }
  scriptPatterns = [];
  bladePatterns = [];
  stagePrefix = "";
  config(e, s) {
    const o = this.processor.config(e, s)[0], n = this.options.tmpPath ?? p("./tmp/fusion/jsmodules/").replace(/\\/g, "/");
    (this.options.cleanTmp ?? !0) && s.postBuildCallbacks.push(() => {
      a.removeSync(n);
    }), s.merge({
      resolve: {
        alias: {
          "@main": o.input
        }
      }
    }), s.loadCallbacks.push((d, i) => {
      const c = A(d);
      if (w(c) === p(o.input)) {
        const l = C(this.scriptPatterns);
        let u = `{
`;
        for (const h of l) {
          let g = h.fullpath;
          if (g.endsWith(".d.ts"))
            continue;
          let f = h.relativePath.replace(/assets\//, "").toLowerCase();
          g = p(g).replace(/\\/g, "/"), f = f.substring(0, f.lastIndexOf(".")) + ".js", this.stagePrefix && (f = this.stagePrefix + "/" + f), u += `'${f}': () => import('${g}'),
`;
        }
        const y = ee(this.bladePatterns);
        a.ensureDirSync(n);
        for (const h of y) {
          let g = h.as;
          const f = n + "/" + h.path.replace(/\\|\//g, "_") + "-" + D(h.code) + ".ts";
          a.writeFileSync(f, h.code), u += `'inline:${g}': () => import('${f}'),
`;
        }
        u += "}";
        const M = `
import { CoreLoader } from '${p("./vendor/windwalker/core/assets/core/src/loader/core-loader.ts").replace(/\\/g, "/")}';

const loader = new CoreLoader();
loader.register(${u});

export { loader };
  `;
        return a.readFileSync(c, "utf-8") + `

` + M;
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
function ee(t) {
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
  return W(() => te(t, e));
}
async function te(t = [], e = "www/assets/vendor") {
  const s = e;
  let r = t;
  const o = process.env.INSTALL_VENDOR === "hard" ? "Copy" : "Link";
  console.log(""), a.existsSync(s) || a.mkdirSync(s);
  const n = a.readdirSync(s, { withFileTypes: !0 }).filter((i) => i.isDirectory()).map((i) => $.join(s, i.name));
  n.unshift(s), n.forEach((i) => {
    _(i);
  });
  const m = ne().map((i) => `vendor/${i}/composer.json`).map((i) => k(i)).filter((i) => i?.extra?.windwalker != null);
  r = se(m).concat(r), r = [...new Set(r)];
  for (const i of r)
    a.existsSync(`node_modules/${i}/`) && (console.log(`[${o} NPM] node_modules/${i}/ => ${s}/${i}/`), b(`node_modules/${i}/`, `${s}/${i}/`));
  for (const i of m) {
    const c = i.name;
    let l = i?.extra?.windwalker?.assets?.link;
    l && (l.endsWith("/") || (l += "/"), a.existsSync(`vendor/${c}/${l}`) && (console.log(`[${o} Composer] vendor/${c}/${l} => ${s}/${c}/`), b(`vendor/${c}/${l}`, `${s}/${c}/`)));
  }
  const d = "resources/assets/vendor/";
  if (a.existsSync(d)) {
    const i = a.readdirSync(d);
    for (const c of i)
      if (c.startsWith("@")) {
        const l = a.readdirSync(d + c);
        for (const u of l) {
          const y = c + "/" + u;
          console.log(`[${o} Local] resources/assets/vendor/${y}/ => ${s}/${y}/`), b(d + y + "/", `${s}/${y}/`);
        }
      } else
        console.log(`[${o} Local] resources/assets/vendor/${c}/ => ${s}/${c}/`), b(d + c, `${s}/${c}/`);
  }
}
async function b(t, e) {
  process.env.INSTALL_VENDOR === "hard" ? await I(t + "/**/*", e) : await J(t, e);
}
function se(t = []) {
  const e = $.resolve(process.cwd(), "package.json"), s = k(e);
  let r = Object.keys(s.devDependencies || {}).concat(Object.keys(s.dependencies || {})).map((n) => `node_modules/${n}/package.json`).map((n) => k(n)).filter((n) => n?.windwalker != null).map((n) => n?.windwalker.vendors || []).flat();
  const o = t.map((n) => [
    ...n?.extra?.windwalker?.asset_vendors || [],
    ...n?.extra?.windwalker?.assets?.exposes || [],
    ...Object.keys(n?.extra?.windwalker?.assets?.vendors || {})
  ]).flat();
  return [...new Set(r.concat(o))];
}
function ne() {
  const t = $.resolve(process.cwd(), "composer.json"), e = k(t);
  return [
    ...new Set(
      Object.keys(e.require || {}).concat(Object.keys(e["require-dev"] || {}))
    )
  ];
}
function _(t) {
  if (!a.existsSync(t))
    return;
  const e = a.readdirSync(t, { withFileTypes: !0 });
  for (const s of e)
    s.isSymbolicLink() || s.isFile() ? a.unlinkSync($.join(t, s.name)) : s.isDirectory() && _($.join(t, s.name));
  a.rmdirSync(t);
}
export {
  $e as cloneAssets,
  E as containsMiddleGlob,
  ve as cssModulize,
  he as ensureDirPath,
  C as findFilesFromGlobArray,
  ye as findModules,
  be as injectSystemJS,
  je as installVendors,
  Se as jsModulize,
  k as loadJson,
  F as removeLastGlob,
  ge as resolveModuleRealpath,
  A as stripUrlQuery,
  we as systemCSSFix,
  U as uniqId,
  ke as windwalkerAssets
};
