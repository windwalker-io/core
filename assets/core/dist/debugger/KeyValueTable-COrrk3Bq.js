System.register(["./debugger.js"], (function(exports, module) {
  "use strict";
  var _export_sfc, createElementBlock, openBlock, createBaseVNode, Fragment, renderList, toDisplayString;
  return {
    setters: [(module2) => {
      _export_sfc = module2._;
      createElementBlock = module2.c;
      openBlock = module2.b;
      createBaseVNode = module2.d;
      Fragment = module2.F;
      renderList = module2.e;
      toDisplayString = module2.t;
    }],
    execute: (function() {
      const _sfc_main = {
        name: "KeyValueTable",
        props: {
          data: Object
        },
        setup() {
          function displayData(v) {
            if (typeof v === "object" || Array.isArray(v)) {
              return JSON.stringify(v, null, 2);
            }
            return v;
          }
          return {
            displayData
          };
        }
      };
      const _hoisted_1 = { class: "table table-bordered" };
      const _hoisted_2 = { style: { "width": "20%", "word-break": "break-all" } };
      const _hoisted_3 = { style: { "width": "80%", "max-width": "800px" } };
      const _hoisted_4 = {
        style: { "word-break": "break-all" },
        class: "m-0"
      };
      function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
        return openBlock(), createElementBlock("table", _hoisted_1, [
          _cache[0] || (_cache[0] = createBaseVNode("thead", null, [
            createBaseVNode("tr", null, [
              createBaseVNode("th", {
                class: "",
                style: { "width": "20%", "min-width": "150px" }
              }, " Key "),
              createBaseVNode("th", null, " Value ")
            ])
          ], -1)),
          createBaseVNode("tbody", null, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($props.data, (value, key) => {
              return openBlock(), createElementBlock("tr", { key }, [
                createBaseVNode("td", _hoisted_2, toDisplayString(key), 1),
                createBaseVNode("td", _hoisted_3, [
                  createBaseVNode("pre", _hoisted_4, toDisplayString($setup.displayData(value)), 1)
                ])
              ]);
            }), 128))
          ])
        ]);
      }
      const KeyValueTable = exports("K", /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]));
    })
  };
}));
