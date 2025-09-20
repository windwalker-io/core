System.register(["./DefaultLayout-DDERhuZO.js", "./debugger.js"], (function(exports, module) {
  "use strict";
  var _sfc_main$1, _export_sfc, createBlock, openBlock, withCtx, ref, $http, resolveComponent, createBaseVNode, toDisplayString, createElementBlock, Fragment, renderList, createTextVNode;
  return {
    setters: [(module2) => {
      _sfc_main$1 = module2._;
    }, (module2) => {
      _export_sfc = module2._;
      createBlock = module2.k;
      openBlock = module2.b;
      withCtx = module2.l;
      ref = module2.a;
      $http = module2.$;
      resolveComponent = module2.f;
      createBaseVNode = module2.d;
      toDisplayString = module2.t;
      createElementBlock = module2.c;
      Fragment = module2.F;
      renderList = module2.e;
      createTextVNode = module2.i;
    }],
    execute: (function() {
      const _sfc_main = {
        name: "System",
        components: { DefaultLayout: _sfc_main$1 },
        async beforeRouteEnter(to, from, next) {
          next(async (vm) => {
            const res = await $http.get("ajax/data?path=system");
            vm.data = res.data.data;
          });
        },
        async beforeRouteUpdate(to, from, next) {
          const res = await $http.get("ajax/data?path=system");
          this.data = res.data.data;
        },
        setup() {
          const data = ref({});
          return {
            data
          };
        }
      };
      const _hoisted_1 = { class: "p-4" };
      const _hoisted_2 = { class: "table table-bordered" };
      const _hoisted_3 = { class: "mt-5" };
      const _hoisted_4 = { class: "table table-bordered" };
      const _hoisted_5 = {
        style: { "width": "20%" },
        class: "text-nowrap"
      };
      const _hoisted_6 = { class: "mt-5" };
      const _hoisted_7 = { class: "border rounded p-3" };
      function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
        const _component_DefaultLayout = resolveComponent("DefaultLayout");
        return openBlock(), createBlock(_component_DefaultLayout, null, {
          title: withCtx(() => [..._cache[0] || (_cache[0] = [
            createTextVNode(" System ", -1)
          ])]),
          default: withCtx(() => [
            createBaseVNode("div", _hoisted_1, [
              createBaseVNode("div", null, [
                _cache[4] || (_cache[4] = createBaseVNode("h4", null, "Windwalker", -1)),
                createBaseVNode("table", _hoisted_2, [
                  createBaseVNode("tbody", null, [
                    createBaseVNode("tr", null, [
                      _cache[1] || (_cache[1] = createBaseVNode("th", {
                        style: { "width": "25%" },
                        class: ""
                      }, "Framework Version", -1)),
                      createBaseVNode("td", null, toDisplayString($setup.data.framework_version), 1)
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[2] || (_cache[2] = createBaseVNode("th", { class: "" }, "Core Version", -1)),
                      createBaseVNode("td", null, toDisplayString($setup.data.core_version), 1)
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[3] || (_cache[3] = createBaseVNode("th", { class: "border-right" }, "PHP Version", -1)),
                      createBaseVNode("td", null, toDisplayString($setup.data.php_version), 1)
                    ])
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_3, [
                _cache[6] || (_cache[6] = createBaseVNode("h4", null, "Debug Messages", -1)),
                createBaseVNode("table", _hoisted_4, [
                  _cache[5] || (_cache[5] = createBaseVNode("thead", null, [
                    createBaseVNode("tr", null, [
                      createBaseVNode("th", null, "Type"),
                      createBaseVNode("th", null, "Message")
                    ])
                  ], -1)),
                  createBaseVNode("tbody", null, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($setup.data.messages, (msgs, type) => {
                      return openBlock(), createElementBlock(Fragment, { key: msgs }, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList(msgs, (msg) => {
                          return openBlock(), createElementBlock("tr", { key: msg }, [
                            createBaseVNode("td", _hoisted_5, toDisplayString(type), 1),
                            createBaseVNode("td", null, toDisplayString(msg), 1)
                          ]);
                        }), 128))
                      ], 64);
                    }), 128))
                  ])
                ])
              ]),
              createBaseVNode("div", _hoisted_6, [
                _cache[7] || (_cache[7] = createBaseVNode("h4", null, "Config", -1)),
                createBaseVNode("pre", _hoisted_7, [
                  createBaseVNode("code", null, toDisplayString(JSON.stringify($setup.data.config, null, 2)), 1)
                ])
              ])
            ])
          ]),
          _: 1
        });
      }
      const System2 = exports("default", /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]));
    })
  };
}));
