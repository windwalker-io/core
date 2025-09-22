import { getGlobBaseFromPattern as V, callback as q, css as T, js as B, shortHash as R, plugin as W, callbackAfterBuild as E, copyGlob as J, symlink as G } from "@windwalker-io/fusion-next";
import P from "is-glob";
import z from "micromatch";
import b, { relative as F, normalize as w, resolve as h } from "node:path";
import j from "node:fs";
import v from "fast-glob";
import { randomBytes as H } from "node:crypto";
import { createRequire as K } from "node:module";
import c from "fs-extra";
import { parse as A } from "node-html-parser";
function k(t) {
  return j.existsSync(t) ? JSON.parse(j.readFileSync(t, "utf8")) : null;
}
function Q(t) {
  return P(C(t));
}
function C(t) {
  return t.replace(/(\/\*|\/\*\*?|\*\*\/\*?)$/, "");
}
const U = process.platform === "win32" ? "\\" : "/";
function $e(t, e = U) {
  return t.endsWith(e) ? t : t + e;
}
function O(t) {
  let e = [];
  for (const s of t)
    e = [
      ...e,
      ...X(s)
    ];
  return e;
}
function X(t) {
  return v.globSync(t).map((e) => (e = e.replace(/\\/g, "/"), {
    fullpath: e,
    relativePath: F(V(t), e).replace(/\\/g, "/")
  }));
}
function be(t = "") {
  const e = b.resolve(process.cwd(), "composer.json"), s = k(e), r = Object.keys(s.require || {}).concat(Object.keys(s["require-dev"] || {})).map((o) => `vendor/${o}/composer.json`).map((o) => k(o)).filter((o) => o?.extra?.windwalker != null).map((o) => o?.extra?.windwalker?.modules?.map((n) => `vendor/${o.name}/${n}/${t}`) || []).flat();
  return [...new Set(r)];
}
function Y(t = "", e = 16) {
  let s = H(e).toString("hex");
  return t && (s = t + s), s;
}
function ke(t, e) {
  return K(t).resolve(e);
}
function N(t) {
  const e = t.indexOf("?");
  return e !== -1 ? t.substring(0, e) : t;
}
function Se(t) {
  return q((e, s) => {
    const r = _(t);
    return L(s, r), I(s, Object.keys(t)), null;
  });
}
function _(t) {
  const e = {};
  for (const s in t)
    Q(s) || (e[s] = t[s]);
  return e;
}
function I(t, e) {
  const s = Y("hidden:clone-asset-") + ".js";
  t.addTask(s), t.resolveIdCallbacks.push((r) => {
    if (r === s)
      return s;
  }), t.loadCallbacks.push((r) => {
    if (r === s)
      return `import.meta.glob(${e.map((n) => n.replace(/\\/g, "/")).map((n) => n.startsWith("./") || !n.startsWith("/") ? `/${n}` : n).map((n) => `'${n}'`).join(", ")});
`;
  });
}
function L(t, e) {
  t.assetFileNamesCallbacks.push((s) => {
    const r = s.originalFileName;
    for (const o in e)
      if (Z(r, o))
        return w(e[o] + F(C(o), r)).replace(/\\/g, "/");
  });
}
function Z(t, e) {
  return P(e) ? z.isMatch(t, e) : t.startsWith(e);
}
function we(t) {
  return {
    name: "core:global-assets",
    buildConfig(e) {
      const s = t.clone || {};
      let r = t.reposition || {};
      r = { ...r, ..._(s) }, L(e, r);
      const o = Object.keys(s);
      o.length > 0 && I(e, o);
    }
  };
}
function ve(t, e) {
  return t ??= h("node_modules/systemjs/dist/system.min.js"), {
    name: "core:inject-systemjs",
    async generateBundle(s, r) {
      if (s.format !== "system")
        return;
      const o = c.readFileSync(
        h(t),
        "utf-8"
      );
      for (const n of Object.values(r))
        e && !e(n) || n.type === "chunk" && n.isEntry && n.fileName.endsWith(".js") && (n.code = o + `
` + n.code);
    }
  };
}
function xe() {
  return {
    name: "core:systemjs-css-fix",
    async generateBundle(t, e) {
      if (t.format === "system") {
        for (const [s, r] of Object.entries(e))
          if (s.endsWith(".css") && "code" in r) {
            const o = /__vite_style__\.textContent\s*=\s*"([\s\S]*?)";/;
            let n = r.code.match(o);
            if (!n) {
              const d = /\.textContent\s*=\s*`([\s\S]*?)`/;
              n = r.code.match(d);
            }
            n && n[1] && (r.code = n[1].replace(/\\"/g, '"').replace(/\\n/g, `
`).replace(/\\t/g, "	").replace(/\\\\/g, "\\").replace(/\/\*\$vite\$:\d+\*\/$/, ""));
          }
      }
    }
  };
}
function je(t, e) {
  return new ee(T(t, e));
}
class ee {
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
        const l = N(n);
        if (w(l) === h(o.input)) {
          const a = v.globSync(
            this.cssPatterns.map((f) => h(f)).map((f) => f.replace(/\\/g, "/"))
          ).map((f) => `@import "${f}";`).concat(te(this.bladePatterns)).join(`
`);
          let u = c.readFileSync(l, "utf-8");
          return u += `

${a}
`, u;
        }
      });
  }
  preview() {
    return [];
  }
}
function te(t) {
  return v.globSync(t).map((s) => {
    const r = c.readFileSync(s, "utf8");
    return A(r).querySelectorAll("style[type],script[type]").filter(
      (n) => ["text/scss", "text/css"].includes(n.getAttribute("type") || "")
    ).map((n) => {
      const d = n.getAttribute("data-scope");
      return d ? `${d} {
          ${n.innerHTML}
        }` : n.innerHTML;
    });
  }).filter((s) => s.length > 0).flat();
}
function Pe(t, e, s = {}) {
  return new se(B(t, e), s);
}
class se {
  constructor(e, s = {}) {
    this.processor = e, this.options = s;
  }
  scriptPatterns = [];
  bladePatterns = [];
  stagePrefix = "";
  config(e, s) {
    const o = this.processor.config(e, s)[0], n = this.options.tmpPath ?? h("./tmp/fusion/jsmodules/").replace(/\\/g, "/");
    (this.options.cleanTmp ?? !0) && s.postBuildCallbacks.push((l, i) => {
      c.removeSync(n);
    }), this.ignoreMainImport(o), s.resolveIdCallbacks.push((l) => {
      if (l === "@main")
        return { id: l, external: !0 };
    }), s.loadCallbacks.push((l, i) => {
      const a = N(l);
      if (w(a) === h(o.input)) {
        const u = O(this.scriptPatterns);
        let f = `{
`;
        for (const m of u) {
          let $ = m.fullpath;
          if ($.endsWith(".d.ts"))
            continue;
          let p = m.relativePath.replace(/assets\//, "").toLowerCase();
          $ = h($).replace(/\\/g, "/"), p = p.substring(0, p.lastIndexOf(".")) + ".js", this.stagePrefix && (p = this.stagePrefix + "/" + p), f += `'${p}': () => import('${$}'),
`;
        }
        const y = ne(this.bladePatterns), g = [];
        c.ensureDirSync(n);
        for (const m of y) {
          let $ = m.as;
          const p = n + "/" + m.path.replace(/\\|\//g, "_") + "-" + R(m.code) + ".ts";
          (!c.existsSync(p) || c.readFileSync(p, "utf8") !== m.code) && c.writeFileSync(p, m.code), f += `'inline:${$}': () => import('${p}'),
`;
          const x = h(m.file.fullpath).replace(/\\/g, "/");
          g.includes(x) || g.push(x);
        }
        f += "}";
        const D = `
import { App } from '${h("./vendor/windwalker/core/assets/core/src/next/app.ts").replace(/\\/g, "/")}';

const app = new App();
app.registerRoutes(${f});

export default app;
  `;
        return s.watches.push(...g), c.readFileSync(a, "utf-8") + `

` + D;
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
    W({
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
function ne(t) {
  return O(Array.isArray(t) ? t : [t]).map((s) => {
    const r = c.readFileSync(s.fullpath, "utf8");
    return A(r).querySelectorAll("script[lang][id]").filter(
      (n) => ["ts", "typescript"].includes(n.getAttribute("lang") || "")
    ).map((n) => ({
      as: n.getAttribute("id") || "",
      file: s,
      path: s.relativePath.replace(/.blade.php$/, ""),
      code: n.innerHTML
    })).filter((n) => n.code.trim() !== "");
  }).flat();
}
function Fe(t = [], e = "www/assets/vendor") {
  return E(() => re(t, e));
}
async function re(t = [], e = "www/assets/vendor") {
  const s = e;
  let r = t;
  const o = process.env.INSTALL_VENDOR === "hard" ? "Copy" : "Link";
  console.log(""), c.existsSync(s) || c.mkdirSync(s);
  const n = c.readdirSync(s, { withFileTypes: !0 }).filter((i) => i.isDirectory()).map((i) => b.join(s, i.name));
  n.unshift(s), n.forEach((i) => {
    M(i);
  });
  const d = ie().map((i) => `vendor/${i}/composer.json`).map((i) => k(i)).filter((i) => i?.extra?.windwalker != null);
  r = oe(d).concat(r), r = [...new Set(r)];
  for (const i of r) {
    const a = `node_modules/${i}/`;
    c.existsSync(a) && (console.log(`[${o} NPM] node_modules/${i}/ => ${s}/${i}/`), S(a, `${s}/${i}/`));
  }
  for (const i of d) {
    const a = i.name;
    let u = i?.extra?.windwalker?.assets?.link;
    u && (u.endsWith("/") || (u += "/"), c.existsSync(`vendor/${a}/${u}`) && (console.log(`[${o} Composer] vendor/${a}/${u} => ${s}/${a}/`), S(`vendor/${a}/${u}`, `${s}/${a}/`)));
  }
  const l = "resources/assets/vendor/";
  if (c.existsSync(l)) {
    const i = c.readdirSync(l);
    for (const a of i)
      if (a.startsWith("@")) {
        const u = c.readdirSync(l + a);
        for (const f of u) {
          const y = a + "/" + f, g = l + y + "/";
          c.existsSync(g) && (console.log(`[${o} Local] resources/assets/vendor/${y}/ => ${s}/${y}/`), S(g, `${s}/${y}/`));
        }
      } else {
        let u = l + a;
        c.existsSync(u) && (console.log(`[${o} Local] resources/assets/vendor/${a}/ => ${s}/${a}/`), S(u, `${s}/${a}/`));
      }
  }
}
async function S(t, e) {
  process.env.INSTALL_VENDOR === "hard" ? await J(t + "/**/*", e) : await G(t, e);
}
function oe(t = []) {
  const e = b.resolve(process.cwd(), "package.json"), s = k(e);
  let r = Object.keys(s.devDependencies || {}).concat(Object.keys(s.dependencies || {})).map((n) => `node_modules/${n}/package.json`).map((n) => k(n)).filter((n) => n?.windwalker != null).map((n) => n?.windwalker.vendors || []).flat();
  const o = t.map((n) => [
    ...n?.extra?.windwalker?.asset_vendors || [],
    ...n?.extra?.windwalker?.assets?.exposes || [],
    ...Object.keys(n?.extra?.windwalker?.assets?.vendors || {})
  ]).flat();
  return [...new Set(r.concat(o))];
}
function ie() {
  const t = b.resolve(process.cwd(), "composer.json"), e = k(t);
  return [
    ...new Set(
      Object.keys(e.require || {}).concat(Object.keys(e["require-dev"] || {}))
    )
  ];
}
function M(t) {
  if (!c.existsSync(t))
    return;
  const e = c.readdirSync(t, { withFileTypes: !0 });
  for (const s of e)
    s.isSymbolicLink() || s.isFile() ? c.unlinkSync(b.join(t, s.name)) : s.isDirectory() && M(b.join(t, s.name));
  c.rmdirSync(t);
}
export {
  Se as cloneAssets,
  Q as containsMiddleGlob,
  je as cssModulize,
  $e as ensureDirPath,
  O as findFilesFromGlobArray,
  be as findModules,
  we as globalAssets,
  ve as injectSystemJS,
  Fe as installVendors,
  Pe as jsModulize,
  k as loadJson,
  C as removeLastGlob,
  ke as resolveModuleRealpath,
  N as stripUrlQuery,
  xe as systemCSSFix,
  Y as uniqId
};
