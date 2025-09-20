System.register(["./debugger.js", "./KeyValueTable-COrrk3Bq.js", "./DefaultLayout-DDERhuZO.js"], (function(exports, module) {
  "use strict";
  var _export_sfc, createBlock, openBlock, withCtx, ref, $http, resolveComponent, createElementBlock, createCommentVNode, createBaseVNode, createVNode, Teleport, withModifiers, createTextVNode, KeyValueTable, _sfc_main$1;
  return {
    setters: [(module2) => {
      _export_sfc = module2._;
      createBlock = module2.k;
      openBlock = module2.b;
      withCtx = module2.l;
      ref = module2.a;
      $http = module2.$;
      resolveComponent = module2.f;
      createElementBlock = module2.c;
      createCommentVNode = module2.h;
      createBaseVNode = module2.d;
      createVNode = module2.g;
      Teleport = module2.T;
      withModifiers = module2.w;
      createTextVNode = module2.i;
    }, (module2) => {
      KeyValueTable = module2.K;
    }, (module2) => {
      _sfc_main$1 = module2._;
    }],
    execute: (function() {
      const _sfc_main = {
        name: "Request",
        components: { KeyValueTable, DefaultLayout: _sfc_main$1 },
        async beforeRouteEnter(to, from, next) {
          next(async (vm) => {
            const res = await $http.get("ajax/data?path=http");
            vm.data = res.data.data;
          });
        },
        async beforeRouteUpdate(to, from, next) {
          const res = await $http.get("ajax/data?path=http");
          this.data = res.data.data;
        },
        setup() {
          const data = ref(null);
          const root = ref(null);
          function goto(section) {
            const sec = root.value.querySelector(`.l-section--${section}`);
            if (sec) {
              window.scrollTo({
                top: sec.offsetTop - 100,
                behavior: "smooth"
              });
            }
          }
          return {
            data,
            root,
            goto
          };
        }
      };
      const _hoisted_1 = {
        key: 0,
        ref: "root",
        class: "p-4"
      };
      const _hoisted_2 = { class: "l-section l-section--get mt-5" };
      const _hoisted_3 = { class: "l-section l-section--body mt-5" };
      const _hoisted_4 = { class: "l-section l-section--files mt-5" };
      const _hoisted_5 = { class: "l-section l-section--session mt-5" };
      const _hoisted_6 = { class: "l-section l-section--cookies mt-5" };
      const _hoisted_7 = { class: "l-section l-section--server mt-5" };
      const _hoisted_8 = { class: "l-section l-section--env mt-5" };
      const _hoisted_9 = { class: "nav flex-column ps-4 small" };
      const _hoisted_10 = { class: "nav-item" };
      const _hoisted_11 = { class: "nav-item" };
      const _hoisted_12 = { class: "nav-item" };
      const _hoisted_13 = { class: "nav-item" };
      const _hoisted_14 = { class: "nav-item" };
      const _hoisted_15 = { class: "nav-item" };
      const _hoisted_16 = { class: "nav-item" };
      function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
        const _component_KeyValueTable = resolveComponent("KeyValueTable");
        const _component_DefaultLayout = resolveComponent("DefaultLayout");
        return openBlock(), createBlock(_component_DefaultLayout, null, {
          title: withCtx(() => [..._cache[7] || (_cache[7] = [
            createTextVNode(" Request ", -1)
          ])]),
          default: withCtx(() => [
            $setup.data ? (openBlock(), createElementBlock("div", _hoisted_1, [
              createBaseVNode("section", _hoisted_2, [
                _cache[8] || (_cache[8] = createBaseVNode("h3", null, "GET Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.request.query
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_3, [
                _cache[9] || (_cache[9] = createBaseVNode("h3", null, "Body Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.request.body
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_4, [
                _cache[10] || (_cache[10] = createBaseVNode("h3", null, "FILES Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.request.files
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_5, [
                _cache[11] || (_cache[11] = createBaseVNode("h3", null, "Session Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.session
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_6, [
                _cache[12] || (_cache[12] = createBaseVNode("h3", null, "Cookies Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.cookies || $setup.data.request.cookies
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_7, [
                _cache[13] || (_cache[13] = createBaseVNode("h3", null, "SERVER Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.request.server
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_8, [
                _cache[14] || (_cache[14] = createBaseVNode("h3", null, "ENV Variables", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.request.env
                }, null, 8, ["data"])
              ])
            ], 512)) : createCommentVNode("", true),
            (openBlock(), createBlock(Teleport, { to: ".nav-item--request" }, [
              createBaseVNode("ul", _hoisted_9, [
                createBaseVNode("li", _hoisted_10, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[0] || (_cache[0] = withModifiers(($event) => $setup.goto("get"), ["prevent"]))
                  }, " GET Variables ")
                ]),
                createBaseVNode("li", _hoisted_11, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[1] || (_cache[1] = withModifiers(($event) => $setup.goto("body"), ["prevent"]))
                  }, " Body Variables ")
                ]),
                createBaseVNode("li", _hoisted_12, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[2] || (_cache[2] = withModifiers(($event) => $setup.goto("files"), ["prevent"]))
                  }, " Files Variables ")
                ]),
                createBaseVNode("li", _hoisted_13, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[3] || (_cache[3] = withModifiers(($event) => $setup.goto("session"), ["prevent"]))
                  }, " Session Variables ")
                ]),
                createBaseVNode("li", _hoisted_14, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[4] || (_cache[4] = withModifiers(($event) => $setup.goto("cookies"), ["prevent"]))
                  }, " Cookies Variables ")
                ]),
                createBaseVNode("li", _hoisted_15, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[5] || (_cache[5] = withModifiers(($event) => $setup.goto("server"), ["prevent"]))
                  }, " SERVER Variables ")
                ]),
                createBaseVNode("li", _hoisted_16, [
                  createBaseVNode("a", {
                    href: "#",
                    class: "nav-link",
                    onClick: _cache[6] || (_cache[6] = withModifiers(($event) => $setup.goto("env"), ["prevent"]))
                  }, " ENV Variables ")
                ])
              ])
            ]))
          ]),
          _: 1
        });
      }
      const Request = exports("default", /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]));
    })
  };
}));
