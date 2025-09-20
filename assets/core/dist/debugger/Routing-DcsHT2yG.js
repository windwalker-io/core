System.register(["./KeyValueTable-COrrk3Bq.js", "./DefaultLayout-DDERhuZO.js", "./debugger.js"], (function(exports, module) {
  "use strict";
  var KeyValueTable, _sfc_main$1, _export_sfc, createBlock, openBlock, withCtx, ref, $http, resolveComponent, createElementBlock, createCommentVNode, createBaseVNode, toDisplayString, createTextVNode, Fragment, renderList, createVNode, normalizeClass;
  return {
    setters: [(module2) => {
      KeyValueTable = module2.K;
    }, (module2) => {
      _sfc_main$1 = module2._;
    }, (module2) => {
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
      toDisplayString = module2.t;
      createTextVNode = module2.i;
      Fragment = module2.F;
      renderList = module2.e;
      createVNode = module2.g;
      normalizeClass = module2.n;
    }],
    execute: (function() {
      const _sfc_main = {
        name: "Routing",
        components: { KeyValueTable, DefaultLayout: _sfc_main$1 },
        async beforeRouteEnter(to, from, next) {
          next(async (vm2) => {
            const params = new URLSearchParams();
            params.set("path[request]", "http::request");
            params.set("path[uri]", "http::systemUri");
            params.set("path[routing]", "routing");
            const res = await $http.get("ajax/data?" + params.toString());
            vm2.data = res.data.data;
          });
        },
        async beforeRouteUpdate(to, from, next) {
          const params = new URLSearchParams();
          params.set("path[request]", "http::request");
          params.set("path[uri]", "http::systemUri");
          params.set("path[routing]", "routing");
          const res = await $http.get("ajax/data?" + params.toString());
          vm.data = res.data.data;
        },
        setup() {
          const data = ref(null);
          function getHandler(handlers) {
            if (handlers["*"]) {
              return getCallable(handlers["*"]);
            }
            return getCallable(Object.values(handlers)[0]);
          }
          function getCallable(callable) {
            if (!callable) {
              return "-";
            }
            if (typeof callable === "string") {
              return callable;
            }
            return callable.join("::") + "()";
          }
          return {
            data,
            getHandler
          };
        }
      };
      const _hoisted_1 = {
        key: 0,
        class: "p-4"
      };
      const _hoisted_2 = { class: "l-section l-section--info" };
      const _hoisted_3 = { class: "table table-bordered" };
      const _hoisted_4 = { class: "m-0" };
      const _hoisted_5 = { class: "m-0" };
      const _hoisted_6 = {
        key: 0,
        class: "ps-3 mt-2"
      };
      const _hoisted_7 = { class: "m-0" };
      const _hoisted_8 = { class: "m-0" };
      const _hoisted_9 = { class: "mb-1" };
      const _hoisted_10 = { class: "l-section l-section--uri mt-5" };
      const _hoisted_11 = { class: "l-section l-section--routes mt-5" };
      const _hoisted_12 = { class: "table table-dark table-bordered" };
      const _hoisted_13 = { style: { "max-width": "400px" } };
      const _hoisted_14 = { style: { "overflow-x": "auto" } };
      const _hoisted_15 = {
        key: 0,
        class: ""
      };
      const _hoisted_16 = { class: "m-0" };
      const _hoisted_17 = { key: 1 };
      function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
        const _component_KeyValueTable = resolveComponent("KeyValueTable");
        const _component_DefaultLayout = resolveComponent("DefaultLayout");
        return openBlock(), createBlock(_component_DefaultLayout, null, {
          title: withCtx(() => [..._cache[0] || (_cache[0] = [
            createTextVNode(" Routing ", -1)
          ])]),
          default: withCtx(() => [
            $setup.data ? (openBlock(), createElementBlock("div", _hoisted_1, [
              createBaseVNode("section", _hoisted_2, [
                _cache[8] || (_cache[8] = createBaseVNode("h3", { class: "mb-3" }, "Route Information", -1)),
                createBaseVNode("table", _hoisted_3, [
                  createBaseVNode("tbody", null, [
                    createBaseVNode("tr", null, [
                      _cache[1] || (_cache[1] = createBaseVNode("th", { style: { "width": "20%" } }, " Request Method ", -1)),
                      createBaseVNode("td", null, toDisplayString($setup.data.request.method), 1)
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[2] || (_cache[2] = createBaseVNode("th", null, " Total Routes ", -1)),
                      createBaseVNode("td", null, toDisplayString(Object.keys($setup.data.routing.routes).length), 1)
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[3] || (_cache[3] = createBaseVNode("th", null, " Matched Route ", -1)),
                      createBaseVNode("td", null, [
                        createBaseVNode("pre", _hoisted_4, toDisplayString($setup.data.routing.matched?.name), 1)
                      ])
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[5] || (_cache[5] = createBaseVNode("th", null, " Controller / View ", -1)),
                      createBaseVNode("td", null, [
                        createBaseVNode("pre", _hoisted_5, toDisplayString($setup.data.routing.controller), 1),
                        $setup.data.routing.matched?.options?.vars?.view ? (openBlock(), createElementBlock("div", _hoisted_6, [
                          createBaseVNode("pre", _hoisted_7, [
                            _cache[4] || (_cache[4] = createBaseVNode("strong", null, "View:", -1)),
                            createTextVNode(" " + toDisplayString($setup.data.routing.matched?.options?.vars?.view), 1)
                          ])
                        ])) : createCommentVNode("", true)
                      ])
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[6] || (_cache[6] = createBaseVNode("th", null, " Handler ", -1)),
                      createBaseVNode("td", null, [
                        createBaseVNode("pre", _hoisted_8, toDisplayString(JSON.stringify($setup.data.routing.matched?.options?.handlers, null, 2)), 1)
                      ])
                    ]),
                    createBaseVNode("tr", null, [
                      _cache[7] || (_cache[7] = createBaseVNode("th", null, " Middlewares ", -1)),
                      createBaseVNode("td", null, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList($setup.data.routing?.matched?.options?.middlewares, (middleware) => {
                          return openBlock(), createElementBlock("div", null, [
                            createBaseVNode("pre", _hoisted_9, toDisplayString(middleware), 1)
                          ]);
                        }), 256))
                      ])
                    ])
                  ])
                ])
              ]),
              createBaseVNode("section", _hoisted_10, [
                _cache[9] || (_cache[9] = createBaseVNode("h3", { class: "mb-3" }, "Uri Information", -1)),
                createVNode(_component_KeyValueTable, {
                  data: $setup.data.uri
                }, null, 8, ["data"])
              ]),
              createBaseVNode("section", _hoisted_11, [
                _cache[11] || (_cache[11] = createBaseVNode("h3", { class: "mb-3" }, "Routes", -1)),
                createBaseVNode("table", _hoisted_12, [
                  _cache[10] || (_cache[10] = createBaseVNode("thead", null, [
                    createBaseVNode("tr", null, [
                      createBaseVNode("th", null, " Route Name "),
                      createBaseVNode("th", null, " Pattern "),
                      createBaseVNode("th", null, " Methods "),
                      createBaseVNode("th", null, " Controller / View ")
                    ])
                  ], -1)),
                  createBaseVNode("tbody", null, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList($setup.data.routing.routes, (route, name) => {
                      return openBlock(), createElementBlock("tr", {
                        class: normalizeClass({ "table-primary": $setup.data.routing.matched?.name === route.name })
                      }, [
                        createBaseVNode("td", null, [
                          createBaseVNode("code", null, toDisplayString(route.name), 1)
                        ]),
                        createBaseVNode("td", null, [
                          createBaseVNode("code", null, toDisplayString(route.options.pattern), 1)
                        ]),
                        createBaseVNode("td", null, toDisplayString(route.options?.method?.join("|") || "Any"), 1),
                        createBaseVNode("td", _hoisted_13, [
                          createBaseVNode("div", _hoisted_14, [
                            route?.options?.vars?.view ? (openBlock(), createElementBlock("div", _hoisted_15, [
                              createBaseVNode("pre", _hoisted_16, toDisplayString(route?.options?.vars?.view), 1)
                            ])) : (openBlock(), createElementBlock("div", _hoisted_17, [
                              createBaseVNode("pre", null, toDisplayString($setup.getHandler(route.options.handlers || {})), 1)
                            ]))
                          ])
                        ])
                      ], 2);
                    }), 256))
                  ])
                ])
              ])
            ])) : createCommentVNode("", true)
          ]),
          _: 1
        });
      }
      const Routing = exports("default", /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]));
    })
  };
}));
