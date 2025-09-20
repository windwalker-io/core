System.register(["./debugger.js", "./DefaultLayout-DDERhuZO.js"], (function(exports, module) {
  "use strict";
  var _export_sfc, createElementBlock, openBlock, createBaseVNode, Fragment, renderList, createCommentVNode, toDisplayString, ref, withAsyncContext, createBlock, withCtx, $http, createVNode, createTextVNode, _sfc_main$2;
  return {
    setters: [(module2) => {
      _export_sfc = module2._;
      createElementBlock = module2.c;
      openBlock = module2.b;
      createBaseVNode = module2.d;
      Fragment = module2.F;
      renderList = module2.e;
      createCommentVNode = module2.h;
      toDisplayString = module2.t;
      ref = module2.a;
      withAsyncContext = module2.p;
      createBlock = module2.k;
      withCtx = module2.l;
      $http = module2.$;
      createVNode = module2.g;
      createTextVNode = module2.i;
    }, (module2) => {
      _sfc_main$2 = module2._;
    }],
    execute: (function() {
      const _sfc_main$1 = {
        name: "EventListenersTable",
        props: {
          events: Object
        },
        setup() {
          let lastEvent = null;
          function isFirstRow(event) {
            const isFirst = event !== lastEvent;
            lastEvent = event;
            return isFirst;
          }
          return {
            isFirstRow
          };
        }
      };
      const _hoisted_1$1 = { class: "table table-bordered" };
      const _hoisted_2$1 = ["rowspan"];
      const _hoisted_3$1 = { key: 1 };
      function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
        return openBlock(), createElementBlock("table", _hoisted_1$1, [
          _cache[2] || (_cache[2] = createBaseVNode("thead", null, [
            createBaseVNode("tr", null, [
              createBaseVNode("th", null, " Event Name "),
              createBaseVNode("th", null, " Times "),
              createBaseVNode("th", null, " Listener ")
            ])
          ], -1)),
          createBaseVNode("tbody", null, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($props.events, (listeners, event) => {
              return openBlock(), createElementBlock(Fragment, { key: event }, [
                Object.keys(listeners).length > 0 ? (openBlock(true), createElementBlock(Fragment, { key: 0 }, renderList(listeners, (count, listener) => {
                  return openBlock(), createElementBlock("tr", null, [
                    $setup.isFirstRow(event) ? (openBlock(), createElementBlock("td", {
                      key: 0,
                      rowspan: Object.keys(listeners).length
                    }, [
                      createBaseVNode("pre", null, toDisplayString(event), 1)
                    ], 8, _hoisted_2$1)) : createCommentVNode("", true),
                    createBaseVNode("td", null, toDisplayString(count), 1),
                    createBaseVNode("td", null, [
                      createBaseVNode("pre", null, toDisplayString(listener), 1)
                    ])
                  ]);
                }), 256)) : (openBlock(), createElementBlock("tr", _hoisted_3$1, [
                  createBaseVNode("td", null, [
                    createBaseVNode("pre", null, toDisplayString(event), 1)
                  ]),
                  _cache[0] || (_cache[0] = createBaseVNode("td", null, " - ", -1)),
                  _cache[1] || (_cache[1] = createBaseVNode("td", null, " - ", -1))
                ]))
              ], 64);
            }), 128))
          ])
        ]);
      }
      const EventListenersTable = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render]]);
      const _hoisted_1 = {
        key: 0,
        class: "p-4"
      };
      const _hoisted_2 = { class: "l-section l-section--triggered" };
      const _hoisted_3 = { class: "l-section l-section--untriggered mt-5" };
      const _sfc_main = exports("default", {
        __name: "Events",
        async setup(__props) {
          let __temp, __restore;
          const data = ref(null);
          const res = ([__temp, __restore] = withAsyncContext(() => $http.get("ajax/data?path=events")), __temp = await __temp, __restore(), __temp);
          data.value = res.data.data;
          return (_ctx, _cache) => {
            return openBlock(), createBlock(_sfc_main$2, null, {
              title: withCtx(() => [..._cache[0] || (_cache[0] = [
                createTextVNode(" Events ", -1)
              ])]),
              default: withCtx(() => [
                data.value ? (openBlock(), createElementBlock("div", _hoisted_1, [
                  createBaseVNode("section", _hoisted_2, [
                    _cache[1] || (_cache[1] = createBaseVNode("h3", null, "Event Triggered", -1)),
                    createVNode(EventListenersTable, {
                      events: data.value.invoked
                    }, null, 8, ["events"])
                  ]),
                  createBaseVNode("section", _hoisted_3, [
                    _cache[2] || (_cache[2] = createBaseVNode("h3", null, "Event Not Triggered (But has Listeners)", -1)),
                    createVNode(EventListenersTable, {
                      events: data.value.uninvoked
                    }, null, 8, ["events"])
                  ])
                ])) : createCommentVNode("", true)
              ]),
              _: 1
            });
          };
        }
      });
    })
  };
}));
