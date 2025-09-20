System.register(["./utilities-BGafRX_t.js", "./debugger.js", "./DefaultLayout-DDERhuZO.js"], (function(exports, module) {
  "use strict";
  var stateColor, _export_sfc, createElementBlock, openBlock, createBaseVNode, Fragment, renderList, createCommentVNode, toDisplayString, normalizeClass, createBlock, withCtx, ref, computed, $http, resolveComponent, createVNode, createTextVNode, _sfc_main$2;
  return {
    setters: [(module2) => {
      stateColor = module2.s;
    }, (module2) => {
      _export_sfc = module2._;
      createElementBlock = module2.c;
      openBlock = module2.b;
      createBaseVNode = module2.d;
      Fragment = module2.F;
      renderList = module2.e;
      createCommentVNode = module2.h;
      toDisplayString = module2.t;
      normalizeClass = module2.n;
      createBlock = module2.k;
      withCtx = module2.l;
      ref = module2.a;
      computed = module2.m;
      $http = module2.$;
      resolveComponent = module2.f;
      createVNode = module2.g;
      createTextVNode = module2.i;
    }, (module2) => {
      _sfc_main$2 = module2._;
    }],
    execute: (function() {
      const _sfc_main$1 = {
        name: "TimelineTable",
        props: {
          items: Array
        },
        setup() {
          let lastTime = 0;
          let timeOffset = 0;
          function getTimeOffset(time) {
            timeOffset = time - lastTime;
            lastTime = time;
            return timeOffset;
          }
          function getCurrentTimeOffset() {
            return timeOffset;
          }
          let lastMemory = 0;
          let memoryOffset = 0;
          function getMemoryOffset(memory) {
            memoryOffset = memory - lastMemory;
            lastMemory = memory;
            return memoryOffset;
          }
          function getCurrentMemoryOffset() {
            return memoryOffset;
          }
          function bytesToMB(value) {
            return value / 1024 / 1024;
          }
          function round(num) {
            return Math.round(num * 1e4) / 1e4;
          }
          return {
            getTimeOffset,
            getCurrentTimeOffset,
            getMemoryOffset,
            getCurrentMemoryOffset,
            stateColor,
            bytesToMB,
            round
          };
        }
      };
      const _hoisted_1$1 = { class: "table table-bordered" };
      const _hoisted_2$1 = { class: "text-end" };
      const _hoisted_3$1 = { class: "badge bg-secondary" };
      const _hoisted_4$1 = {
        key: 0,
        class: "text-end"
      };
      const _hoisted_5$1 = { class: "text-end" };
      const _hoisted_6$1 = { class: "badge bg-secondary" };
      const _hoisted_7$1 = {
        key: 1,
        class: "text-end"
      };
      function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
        return openBlock(), createElementBlock("div", null, [
          createBaseVNode("table", _hoisted_1$1, [
            _cache[0] || (_cache[0] = createBaseVNode("thead", null, [
              createBaseVNode("tr", null, [
                createBaseVNode("th", null, "Label"),
                createBaseVNode("th", { class: "text-end" }, "Total Time"),
                createBaseVNode("th", { class: "text-end" }, "Time"),
                createBaseVNode("th", { class: "text-end" }, "Total Memory"),
                createBaseVNode("th", { class: "text-end" }, "Memory")
              ])
            ], -1)),
            createBaseVNode("tbody", null, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($props.items, (item) => {
                return openBlock(), createElementBlock("tr", null, [
                  createBaseVNode("td", null, toDisplayString(item.label), 1),
                  createBaseVNode("td", _hoisted_2$1, [
                    createBaseVNode("span", _hoisted_3$1, toDisplayString($setup.round(item.endTime)) + "ms ", 1)
                  ]),
                  $setup.getTimeOffset(item.endTime) || true ? (openBlock(), createElementBlock("td", _hoisted_4$1, [
                    createBaseVNode("span", {
                      class: normalizeClass(["badge", "bg-" + $setup.stateColor($setup.getCurrentTimeOffset(), 50)])
                    }, toDisplayString($setup.round($setup.getCurrentTimeOffset())) + "ms ", 3)
                  ])) : createCommentVNode("", true),
                  createBaseVNode("td", _hoisted_5$1, [
                    createBaseVNode("span", _hoisted_6$1, toDisplayString($setup.round($setup.bytesToMB(item.memory))) + "MB ", 1)
                  ]),
                  $setup.getMemoryOffset(item.memory) || true ? (openBlock(), createElementBlock("td", _hoisted_7$1, [
                    createBaseVNode("span", {
                      class: normalizeClass(["badge", "bg-" + $setup.stateColor($setup.bytesToMB($setup.getCurrentMemoryOffset()), 2)])
                    }, toDisplayString($setup.round($setup.bytesToMB($setup.getCurrentMemoryOffset()))) + "MB ", 3)
                  ])) : createCommentVNode("", true)
                ]);
              }), 256))
            ])
          ])
        ]);
      }
      const TimelineTable = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
      const _sfc_main = {
        name: "Timeline",
        components: { TimelineTable, DefaultLayout: _sfc_main$2 },
        async beforeRouteEnter(to, from, next) {
          next(async (vm) => {
            const res = await $http.get("ajax/data?path=profiler");
            vm.data = res.data.data;
          });
        },
        async beforeRouteUpdate(to, from, next) {
          const res = await $http.get("ajax/data?path=profiler");
          this.data = res.data.data;
        },
        setup() {
          const data = ref(null);
          const instances = computed(() => Object.keys(data.value));
          const systemItems = computed(() => {
            if (!data.value) {
              return [];
            }
            return data.value.main.items.filter((item) => {
              const tags = item.tags || [];
              return tags.indexOf("system") !== -1;
            }) || [];
          });
          return {
            data,
            instances,
            systemItems
          };
        }
      };
      const _hoisted_1 = {
        key: 0,
        class: "p-4"
      };
      const _hoisted_2 = { class: "l-section l-section--system" };
      const _hoisted_3 = { class: "l-section l-section--profilers mt-5" };
      const _hoisted_4 = {
        class: "nav nav-pills",
        id: "profilers-tab",
        role: "tablist"
      };
      const _hoisted_5 = {
        class: "nav-item",
        role: "presentation"
      };
      const _hoisted_6 = ["data-bs-target"];
      const _hoisted_7 = {
        class: "tab-content mt-4",
        id: "myTabContent"
      };
      const _hoisted_8 = ["id"];
      const _hoisted_9 = { class: "" };
      function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
        const _component_TimelineTable = resolveComponent("TimelineTable");
        const _component_DefaultLayout = resolveComponent("DefaultLayout");
        return openBlock(), createBlock(_component_DefaultLayout, null, {
          title: withCtx(() => [..._cache[0] || (_cache[0] = [
            createTextVNode(" Timeline ", -1)
          ])]),
          default: withCtx(() => [
            $setup.data ? (openBlock(), createElementBlock("div", _hoisted_1, [
              createBaseVNode("section", _hoisted_2, [
                _cache[1] || (_cache[1] = createBaseVNode("h3", null, "System Timeline", -1)),
                createVNode(_component_TimelineTable, { items: $setup.systemItems }, null, 8, ["items"])
              ]),
              createBaseVNode("section", _hoisted_3, [
                createBaseVNode("ul", _hoisted_4, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($setup.instances, (instance, i) => {
                    return openBlock(), createElementBlock("li", _hoisted_5, [
                      createBaseVNode("button", {
                        class: "nav-link active",
                        id: "home-tab",
                        "data-bs-toggle": "tab",
                        "data-bs-target": `tab-${instance}`,
                        type: "button",
                        role: "tab",
                        "aria-selected": "true"
                      }, toDisplayString(instance), 9, _hoisted_6)
                    ]);
                  }), 256))
                ]),
                createBaseVNode("div", _hoisted_7, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList($setup.instances, (instance, i) => {
                    return openBlock(), createElementBlock("div", {
                      class: normalizeClass(["tab-pane fade", [i === 0 ? "show active" : ""]]),
                      id: `tab-${instance}`,
                      role: "tabpanel",
                      tabindex: "0"
                    }, [
                      createBaseVNode("div", _hoisted_9, [
                        createVNode(_component_TimelineTable, {
                          items: $setup.data[instance].items
                        }, null, 8, ["items"])
                      ])
                    ], 10, _hoisted_8);
                  }), 256))
                ])
              ])
            ])) : createCommentVNode("", true)
          ]),
          _: 1
        });
      }
      const Timeline = exports("default", /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]));
    })
  };
}));
