(window.webpackJsonp=window.webpackJsonp||[]).push([[3],{51:function(t,e,r){"use strict";r.r(e);var a=r(2),n=r(0),c=Object(a.K)("data-v-7345bbba");Object(a.A)("data-v-7345bbba");var u=Object(a.j)(" System "),o={class:"p-4"},s=Object(a.k)("h4",null,"Windwalker",-1),b={class:"table w-full border"},l=Object(a.k)("th",{style:{width:"25%"},class:"border-right"},"Framework Version",-1),i=Object(a.k)("th",{class:"border-right"},"Core Version",-1),d=Object(a.k)("th",{class:"border-right"},"PHP Version",-1),j=Object(a.k)("hr",null,null,-1),O={class:"mt-4"},f=Object(a.k)("h4",null,"Config",-1),v={class:"bg-gray-200 p-3 rounded-sm text-sm"};Object(a.y)();var k=c((function(t,e,r,k,p,h){var g=Object(a.D)("default-layout");return Object(a.x)(),Object(a.f)(g,null,{title:c((function(){return[u]})),default:c((function(){return[Object(a.k)("div",o,[Object(a.k)("div",null,[s,Object(a.k)("table",b,[Object(a.k)("tr",null,[l,Object(a.k)("td",null,Object(n.K)(k.data.framework_version),1)]),Object(a.k)("tr",null,[i,Object(a.k)("td",null,Object(n.K)(k.data.core_version),1)]),Object(a.k)("tr",null,[d,Object(a.k)("td",null,Object(n.K)(k.data.php_version),1)])])]),j,Object(a.k)("div",O,[f,Object(a.k)("pre",v,Object(n.K)(JSON.stringify(k.data.config,null,2)),1)])])]})),_:1})})),p=r(1),h=r(58),g=r(8);function m(t,e,r,a,n,c,u){try{var o=t[c](u),s=o.value}catch(t){return void r(t)}o.done?e(s):Promise.resolve(s).then(a,n)}function y(t){return function(){var e=this,r=arguments;return new Promise((function(a,n){var c=t.apply(e,r);function u(t){m(c,a,n,u,o,"next",t)}function o(t){m(c,a,n,u,o,"throw",t)}u(void 0)}))}}var w={name:"System",components:{DefaultLayout:h.a},beforeRouteEnter:function(t,e,r){return y(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:r(function(){var t=y(regeneratorRuntime.mark((function t(e){var r;return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,g.a.get("ajax/data?path=system");case 2:r=t.sent,e.data=r.data.data;case 4:case"end":return t.stop()}}),t)})));return function(e){return t.apply(this,arguments)}}());case 1:case"end":return t.stop()}}),t)})))()},setup:function(){return{data:Object(p.k)({})}}};w.render=k,w.__scopeId="data-v-7345bbba";e.default=w},58:function(t,e,r){"use strict";var a=r(2),n=r(0),c=Object(a.K)("data-v-10154a94");Object(a.A)("data-v-10154a94");var u={key:0},o={class:"p-6 bg-gray-300"},s={class:"text-xl font-bold text-gray-800"},b={class:"text-gray-600"};Object(a.y)();var l=c((function(t,e,r,c,l,i){return c.currentData?(Object(a.x)(),Object(a.f)("div",u,[Object(a.k)("div",o,[Object(a.k)("h1",s,[Object(a.C)(t.$slots,"title",{},void 0,!0)]),Object(a.k)("div",b,[Object(a.j)(Object(n.K)(c.currentId)+" / ",1),Object(a.k)("a",{target:"_blank",class:"text-gray-600",href:c.currentData.url},Object(n.K)(c.currentData.url),9,["href"])])]),Object(a.C)(t.$slots,"default",{},void 0,!0)])):Object(a.g)("",!0)})),i=r(4),d={name:"DefaultLayout",setup:function(){return{currentId:i.b,currentData:i.a}}};d.render=l,d.__scopeId="data-v-10154a94";e.a=d}}]);
//# sourceMappingURL=3.js.map