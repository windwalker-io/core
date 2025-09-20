System.register(["./debugger.js"], (function(exports, module) {
  "use strict";
  var $http, router, createElementBlock, createCommentVNode, unref, currentData, openBlock, createBaseVNode, renderSlot, createVNode, createTextVNode, withCtx, resolveComponent, useRoute, toDisplayString, currentId;
  return {
    setters: [(module2) => {
      $http = module2.$;
      router = module2.j;
      createElementBlock = module2.c;
      createCommentVNode = module2.h;
      unref = module2.u;
      currentData = module2.y;
      openBlock = module2.b;
      createBaseVNode = module2.d;
      renderSlot = module2.q;
      createVNode = module2.g;
      createTextVNode = module2.i;
      withCtx = module2.l;
      resolveComponent = module2.f;
      useRoute = module2.z;
      toDisplayString = module2.t;
      currentId = module2.A;
    }],
    execute: (function() {
      exports("g", goToLast);
      async function goToLast(currentRoute = void 0) {
        const res = await $http.get("ajax/last");
        let route = "";
        if (currentRoute) {
          route = currentRoute + "/" + res.data.data;
        } else {
          route = "/system/" + res.data.data;
        }
        return router.push(route);
      }
      const _hoisted_1 = { key: 0 };
      const _hoisted_2 = {
        class: "p-3 mt-3 mx-4 rounded-3",
        style: { "background-color": "var(--bs-gray-800)" }
      };
      const _hoisted_3 = { class: "text-xl font-bold text-gray-800" };
      const _hoisted_4 = { class: "text-muted" };
      const _hoisted_5 = ["href"];
      const _sfc_main = exports("_", {
        __name: "DefaultLayout",
        setup(__props) {
          const route = useRoute();
          return (_ctx, _cache) => {
            const _component_fa_icon = resolveComponent("fa-icon");
            const _component_router_link = resolveComponent("router-link");
            return unref(currentData) ? (openBlock(), createElementBlock("div", _hoisted_1, [
              createBaseVNode("div", _hoisted_2, [
                createBaseVNode("h1", _hoisted_3, [
                  renderSlot(_ctx.$slots, "title")
                ]),
                createBaseVNode("div", _hoisted_4, [
                  createVNode(_component_router_link, {
                    to: "/",
                    class: "btn btn-sm btn-primary"
                  }, {
                    default: withCtx(() => [
                      createVNode(_component_fa_icon, { icon: "fa-solid fa-list" })
                    ]),
                    _: 1
                  }),
                  createBaseVNode("button", {
                    type: "button",
                    onClick: _cache[0] || (_cache[0] = ($event) => unref(goToLast)("/" + unref(route).name)),
                    class: "btn btn-sm btn-success"
                  }, [
                    createVNode(_component_fa_icon, { icon: "fa-solid fa-arrows-rotate" })
                  ]),
                  createTextVNode(" / " + toDisplayString(unref(currentId)) + " / ", 1),
                  createBaseVNode("a", {
                    target: "_blank",
                    class: "text-gray-600",
                    href: unref(currentData).url
                  }, toDisplayString(unref(currentData).url), 9, _hoisted_5)
                ])
              ]),
              renderSlot(_ctx.$slots, "default")
            ])) : createCommentVNode("", true);
          };
        }
      });
    })
  };
}));
