/*! For license information please see chunk-vendor-548.js.LICENSE.txt */
"use strict";(self.webpackChunk_windwalker_io_core=self.webpackChunk_windwalker_io_core||[]).push([[548],{6072:(t,e,r)=>{r.d(e,{Z:()=>m});var n=r(1525),o=r(3630),i={key:0},a={class:"p-3",style:{"background-color":"var(--bs-gray-200)"}},u={class:"text-xl font-bold text-gray-800"},c=(0,n.Uk)(),s={class:"text-muted"},l=(0,n.Uk)(),f=["href"],h=(0,n.Uk)(),d=r(457),p=r(5054),y=r(4545);const v={name:"DefaultLayout",setup:function(){var t=(0,d.yj)();return{currentId:y.p,currentData:y.I,route:t,goToLast:p.D}}},m=(0,r(4181).Z)(v,[["render",function(t,e,r,d,p,y){var v=(0,n.up)("fa-icon"),m=(0,n.up)("router-link");return d.currentData?((0,n.wg)(),(0,n.iD)("div",i,[(0,n._)("div",a,[(0,n._)("h1",u,[(0,n.WI)(t.$slots,"title")]),c,(0,n._)("div",s,[(0,n.Wm)(m,{to:"/",class:"btn btn-sm btn-primary"},{default:(0,n.w5)((function(){return[(0,n.Wm)(v,{icon:"fa-solid fa-list"})]})),_:1}),l,(0,n._)("button",{type:"button",onClick:e[0]||(e[0]=function(t){return d.goToLast("/"+d.route.name)}),class:"btn btn-sm btn-success"},[(0,n.Wm)(v,{icon:"fa-solid fa-arrows-rotate"})]),(0,n.Uk)("\r\n      /\r\n      "+(0,o.zw)(d.currentId)+"\r\n      / ",1),(0,n._)("a",{target:"_blank",class:"text-gray-600",href:d.currentData.url},(0,o.zw)(d.currentData.url),9,f)])]),h,(0,n.WI)(t.$slots,"default")])):(0,n.kq)("",!0)}]])},7548:(t,e,r)=>{r.r(e),r.d(e,{default:()=>H});var n=r(1525),o=r(3630),i=(0,n.Uk)("\r\n    Timeline\r\n  "),a=(0,n.Uk)(),u={key:0,class:"p-4"},c={class:"l-section l-section--system"},s=(0,n._)("h3",null,"System Timeline",-1),l=(0,n.Uk)(),f=(0,n.Uk)(),h={class:"l-section l-section--profilers mt-5"},d={class:"nav nav-pills",id:"profilers-tab",role:"tablist"},p={class:"nav-item",role:"presentation"},y=["data-bs-target"],v=(0,n.Uk)(),m={class:"tab-content mt-4",id:"myTabContent"},g=["id"],w={class:""},b=r(5556),x={class:"table table-bordered"},_=(0,n._)("thead",null,[(0,n._)("tr",null,[(0,n._)("th",null,"Label"),(0,n.Uk)(),(0,n._)("th",{class:"text-end"},"Total Time"),(0,n.Uk)(),(0,n._)("th",{class:"text-end"},"Time"),(0,n.Uk)(),(0,n._)("th",{class:"text-end"},"Total Memory"),(0,n.Uk)(),(0,n._)("th",{class:"text-end"},"Memory")])],-1),L=(0,n.Uk)(),k=(0,n.Uk)(),E={class:"text-end"},T={class:"badge bg-secondary"},O=(0,n.Uk)(),j={key:0,class:"text-end"},S=(0,n.Uk)(),D={class:"text-end"},U={class:"badge bg-secondary"},G=(0,n.Uk)(),C={key:1,class:"text-end"},N=r(7297);const P={name:"TimelineTable",props:{items:Array},setup:function(){var t=0,e=0,r=0,n=0;return{getTimeOffset:function(r){return e=r-t,t=r,e},getCurrentTimeOffset:function(){return e},getMemoryOffset:function(t){return n=t-r,r=t,n},getCurrentMemoryOffset:function(){return n},stateColor:N.G,bytesToMB:function(t){return t/1024/1024},round:function(t){return Math.round(1e4*t)/1e4}}}};var M=r(4181);const I=(0,M.Z)(P,[["render",function(t,e,r,i,a,u){return(0,n.wg)(),(0,n.iD)("div",null,[(0,n._)("table",x,[_,L,(0,n._)("tbody",null,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(r.items,(function(t){return(0,n.wg)(),(0,n.iD)("tr",null,[(0,n._)("td",null,(0,o.zw)(t.label),1),k,(0,n._)("td",E,[(0,n._)("span",T,(0,o.zw)(i.round(t.endTime))+"ms\r\n        ",1)]),O,(i.getTimeOffset(t.endTime),(0,n.wg)(),(0,n.iD)("td",j,[(0,n._)("span",{class:(0,o.C_)(["badge","bg-"+i.stateColor(i.getCurrentTimeOffset(),50)])},(0,o.zw)(i.round(i.getCurrentTimeOffset()))+"ms\r\n        ",3)])),S,(0,n._)("td",D,[(0,n._)("span",U,(0,o.zw)(i.round(i.bytesToMB(t.memory)))+"MB\r\n        ",1)]),G,(i.getMemoryOffset(t.memory),(0,n.wg)(),(0,n.iD)("td",C,[(0,n._)("span",{class:(0,o.C_)(["badge","bg-"+i.stateColor(i.bytesToMB(i.getCurrentMemoryOffset()),2)])},(0,o.zw)(i.round(i.bytesToMB(i.getCurrentMemoryOffset())))+"MB\r\n        ",3)]))])})),256))])])])}]]);var F=r(6072),Z=r(5518);function z(t){return z="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},z(t)}function W(){W=function(){return t};var t={},e=Object.prototype,r=e.hasOwnProperty,n="function"==typeof Symbol?Symbol:{},o=n.iterator||"@@iterator",i=n.asyncIterator||"@@asyncIterator",a=n.toStringTag||"@@toStringTag";function u(t,e,r){return Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}),t[e]}try{u({},"")}catch(t){u=function(t,e,r){return t[e]=r}}function c(t,e,r,n){var o=e&&e.prototype instanceof f?e:f,i=Object.create(o.prototype),a=new L(n||[]);return i._invoke=function(t,e,r){var n="suspendedStart";return function(o,i){if("executing"===n)throw new Error("Generator is already running");if("completed"===n){if("throw"===o)throw i;return{value:void 0,done:!0}}for(r.method=o,r.arg=i;;){var a=r.delegate;if(a){var u=b(a,r);if(u){if(u===l)continue;return u}}if("next"===r.method)r.sent=r._sent=r.arg;else if("throw"===r.method){if("suspendedStart"===n)throw n="completed",r.arg;r.dispatchException(r.arg)}else"return"===r.method&&r.abrupt("return",r.arg);n="executing";var c=s(t,e,r);if("normal"===c.type){if(n=r.done?"completed":"suspendedYield",c.arg===l)continue;return{value:c.arg,done:r.done}}"throw"===c.type&&(n="completed",r.method="throw",r.arg=c.arg)}}}(t,r,a),i}function s(t,e,r){try{return{type:"normal",arg:t.call(e,r)}}catch(t){return{type:"throw",arg:t}}}t.wrap=c;var l={};function f(){}function h(){}function d(){}var p={};u(p,o,(function(){return this}));var y=Object.getPrototypeOf,v=y&&y(y(k([])));v&&v!==e&&r.call(v,o)&&(p=v);var m=d.prototype=f.prototype=Object.create(p);function g(t){["next","throw","return"].forEach((function(e){u(t,e,(function(t){return this._invoke(e,t)}))}))}function w(t,e){function n(o,i,a,u){var c=s(t[o],t,i);if("throw"!==c.type){var l=c.arg,f=l.value;return f&&"object"==z(f)&&r.call(f,"__await")?e.resolve(f.__await).then((function(t){n("next",t,a,u)}),(function(t){n("throw",t,a,u)})):e.resolve(f).then((function(t){l.value=t,a(l)}),(function(t){return n("throw",t,a,u)}))}u(c.arg)}var o;this._invoke=function(t,r){function i(){return new e((function(e,o){n(t,r,e,o)}))}return o=o?o.then(i,i):i()}}function b(t,e){var r=t.iterator[e.method];if(void 0===r){if(e.delegate=null,"throw"===e.method){if(t.iterator.return&&(e.method="return",e.arg=void 0,b(t,e),"throw"===e.method))return l;e.method="throw",e.arg=new TypeError("The iterator does not provide a 'throw' method")}return l}var n=s(r,t.iterator,e.arg);if("throw"===n.type)return e.method="throw",e.arg=n.arg,e.delegate=null,l;var o=n.arg;return o?o.done?(e[t.resultName]=o.value,e.next=t.nextLoc,"return"!==e.method&&(e.method="next",e.arg=void 0),e.delegate=null,l):o:(e.method="throw",e.arg=new TypeError("iterator result is not an object"),e.delegate=null,l)}function x(t){var e={tryLoc:t[0]};1 in t&&(e.catchLoc=t[1]),2 in t&&(e.finallyLoc=t[2],e.afterLoc=t[3]),this.tryEntries.push(e)}function _(t){var e=t.completion||{};e.type="normal",delete e.arg,t.completion=e}function L(t){this.tryEntries=[{tryLoc:"root"}],t.forEach(x,this),this.reset(!0)}function k(t){if(t){var e=t[o];if(e)return e.call(t);if("function"==typeof t.next)return t;if(!isNaN(t.length)){var n=-1,i=function e(){for(;++n<t.length;)if(r.call(t,n))return e.value=t[n],e.done=!1,e;return e.value=void 0,e.done=!0,e};return i.next=i}}return{next:E}}function E(){return{value:void 0,done:!0}}return h.prototype=d,u(m,"constructor",d),u(d,"constructor",h),h.displayName=u(d,a,"GeneratorFunction"),t.isGeneratorFunction=function(t){var e="function"==typeof t&&t.constructor;return!!e&&(e===h||"GeneratorFunction"===(e.displayName||e.name))},t.mark=function(t){return Object.setPrototypeOf?Object.setPrototypeOf(t,d):(t.__proto__=d,u(t,a,"GeneratorFunction")),t.prototype=Object.create(m),t},t.awrap=function(t){return{__await:t}},g(w.prototype),u(w.prototype,i,(function(){return this})),t.AsyncIterator=w,t.async=function(e,r,n,o,i){void 0===i&&(i=Promise);var a=new w(c(e,r,n,o),i);return t.isGeneratorFunction(r)?a:a.next().then((function(t){return t.done?t.value:a.next()}))},g(m),u(m,a,"Generator"),u(m,o,(function(){return this})),u(m,"toString",(function(){return"[object Generator]"})),t.keys=function(t){var e=[];for(var r in t)e.push(r);return e.reverse(),function r(){for(;e.length;){var n=e.pop();if(n in t)return r.value=n,r.done=!1,r}return r.done=!0,r}},t.values=k,L.prototype={constructor:L,reset:function(t){if(this.prev=0,this.next=0,this.sent=this._sent=void 0,this.done=!1,this.delegate=null,this.method="next",this.arg=void 0,this.tryEntries.forEach(_),!t)for(var e in this)"t"===e.charAt(0)&&r.call(this,e)&&!isNaN(+e.slice(1))&&(this[e]=void 0)},stop:function(){this.done=!0;var t=this.tryEntries[0].completion;if("throw"===t.type)throw t.arg;return this.rval},dispatchException:function(t){if(this.done)throw t;var e=this;function n(r,n){return a.type="throw",a.arg=t,e.next=r,n&&(e.method="next",e.arg=void 0),!!n}for(var o=this.tryEntries.length-1;o>=0;--o){var i=this.tryEntries[o],a=i.completion;if("root"===i.tryLoc)return n("end");if(i.tryLoc<=this.prev){var u=r.call(i,"catchLoc"),c=r.call(i,"finallyLoc");if(u&&c){if(this.prev<i.catchLoc)return n(i.catchLoc,!0);if(this.prev<i.finallyLoc)return n(i.finallyLoc)}else if(u){if(this.prev<i.catchLoc)return n(i.catchLoc,!0)}else{if(!c)throw new Error("try statement without catch or finally");if(this.prev<i.finallyLoc)return n(i.finallyLoc)}}}},abrupt:function(t,e){for(var n=this.tryEntries.length-1;n>=0;--n){var o=this.tryEntries[n];if(o.tryLoc<=this.prev&&r.call(o,"finallyLoc")&&this.prev<o.finallyLoc){var i=o;break}}i&&("break"===t||"continue"===t)&&i.tryLoc<=e&&e<=i.finallyLoc&&(i=null);var a=i?i.completion:{};return a.type=t,a.arg=e,i?(this.method="next",this.next=i.finallyLoc,l):this.complete(a)},complete:function(t,e){if("throw"===t.type)throw t.arg;return"break"===t.type||"continue"===t.type?this.next=t.arg:"return"===t.type?(this.rval=this.arg=t.arg,this.method="return",this.next="end"):"normal"===t.type&&e&&(this.next=e),l},finish:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.finallyLoc===t)return this.complete(r.completion,r.afterLoc),_(r),l}},catch:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.tryLoc===t){var n=r.completion;if("throw"===n.type){var o=n.arg;_(r)}return o}}throw new Error("illegal catch attempt")},delegateYield:function(t,e,r){return this.delegate={iterator:k(t),resultName:e,nextLoc:r},"next"===this.method&&(this.arg=void 0),l}},t}function Y(t,e,r,n,o,i,a){try{var u=t[i](a),c=u.value}catch(t){return void r(t)}u.done?e(c):Promise.resolve(c).then(n,o)}function B(t){return function(){var e=this,r=arguments;return new Promise((function(n,o){var i=t.apply(e,r);function a(t){Y(i,n,o,a,u,"next",t)}function u(t){Y(i,n,o,a,u,"throw",t)}a(void 0)}))}}const A={name:"Timeline",components:{TimelineTable:I,DefaultLayout:F.Z},beforeRouteEnter:function(t,e,r){return B(W().mark((function t(){return W().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:r(function(){var t=B(W().mark((function t(e){var r;return W().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,Z.Z.get("ajax/data?path=profiler");case 2:r=t.sent,e.data=r.data.data;case 4:case"end":return t.stop()}}),t)})));return function(e){return t.apply(this,arguments)}}());case 1:case"end":return t.stop()}}),t)})))()},beforeRouteUpdate:function(t,e,r){var n=this;return B(W().mark((function t(){var e;return W().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,Z.Z.get("ajax/data?path=profiler");case 2:e=t.sent,n.data=e.data.data;case 4:case"end":return t.stop()}}),t)})))()},setup:function(){var t=(0,b.iH)(null),e=(0,n.Fl)((function(){return Object.keys(t.value)})),r=(0,n.Fl)((function(){return t.value&&t.value.main.items.filter((function(t){return-1!==(t.tags||[]).indexOf("system")}))||[]}));return{data:t,instances:e,systemItems:r}}},H=(0,M.Z)(A,[["render",function(t,e,r,b,x,_){var L=(0,n.up)("TimelineTable"),k=(0,n.up)("DefaultLayout");return(0,n.wg)(),(0,n.j4)(k,null,{title:(0,n.w5)((function(){return[i]})),default:(0,n.w5)((function(){return[a,b.data?((0,n.wg)(),(0,n.iD)("div",u,[(0,n._)("section",c,[s,l,(0,n.Wm)(L,{items:b.systemItems},null,8,["items"])]),f,(0,n._)("section",h,[(0,n._)("ul",d,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(b.instances,(function(t,e){return(0,n.wg)(),(0,n.iD)("li",p,[(0,n._)("button",{class:"nav-link active",id:"home-tab","data-bs-toggle":"tab","data-bs-target":"tab-".concat(t),type:"button",role:"tab","aria-selected":"true"},(0,o.zw)(t),9,y)])})),256))]),v,(0,n._)("div",m,[((0,n.wg)(!0),(0,n.iD)(n.HY,null,(0,n.Ko)(b.instances,(function(t,e){return(0,n.wg)(),(0,n.iD)("div",{class:(0,o.C_)(["tab-pane fade",[0===e?"show active":""]]),id:"tab-".concat(t),role:"tabpanel",tabindex:"0"},[(0,n._)("div",w,[(0,n.Wm)(L,{items:b.data[t].items},null,8,["items"])])],10,g)})),256))])])])):(0,n.kq)("",!0)]})),_:1})}]])},5054:(t,e,r)=>{r.d(e,{D:()=>s});var n=r(8601),o=r(5518);function i(t){return i="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},i(t)}function a(){a=function(){return t};var t={},e=Object.prototype,r=e.hasOwnProperty,n="function"==typeof Symbol?Symbol:{},o=n.iterator||"@@iterator",u=n.asyncIterator||"@@asyncIterator",c=n.toStringTag||"@@toStringTag";function s(t,e,r){return Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}),t[e]}try{s({},"")}catch(t){s=function(t,e,r){return t[e]=r}}function l(t,e,r,n){var o=e&&e.prototype instanceof d?e:d,i=Object.create(o.prototype),a=new E(n||[]);return i._invoke=function(t,e,r){var n="suspendedStart";return function(o,i){if("executing"===n)throw new Error("Generator is already running");if("completed"===n){if("throw"===o)throw i;return{value:void 0,done:!0}}for(r.method=o,r.arg=i;;){var a=r.delegate;if(a){var u=_(a,r);if(u){if(u===h)continue;return u}}if("next"===r.method)r.sent=r._sent=r.arg;else if("throw"===r.method){if("suspendedStart"===n)throw n="completed",r.arg;r.dispatchException(r.arg)}else"return"===r.method&&r.abrupt("return",r.arg);n="executing";var c=f(t,e,r);if("normal"===c.type){if(n=r.done?"completed":"suspendedYield",c.arg===h)continue;return{value:c.arg,done:r.done}}"throw"===c.type&&(n="completed",r.method="throw",r.arg=c.arg)}}}(t,r,a),i}function f(t,e,r){try{return{type:"normal",arg:t.call(e,r)}}catch(t){return{type:"throw",arg:t}}}t.wrap=l;var h={};function d(){}function p(){}function y(){}var v={};s(v,o,(function(){return this}));var m=Object.getPrototypeOf,g=m&&m(m(T([])));g&&g!==e&&r.call(g,o)&&(v=g);var w=y.prototype=d.prototype=Object.create(v);function b(t){["next","throw","return"].forEach((function(e){s(t,e,(function(t){return this._invoke(e,t)}))}))}function x(t,e){function n(o,a,u,c){var s=f(t[o],t,a);if("throw"!==s.type){var l=s.arg,h=l.value;return h&&"object"==i(h)&&r.call(h,"__await")?e.resolve(h.__await).then((function(t){n("next",t,u,c)}),(function(t){n("throw",t,u,c)})):e.resolve(h).then((function(t){l.value=t,u(l)}),(function(t){return n("throw",t,u,c)}))}c(s.arg)}var o;this._invoke=function(t,r){function i(){return new e((function(e,o){n(t,r,e,o)}))}return o=o?o.then(i,i):i()}}function _(t,e){var r=t.iterator[e.method];if(void 0===r){if(e.delegate=null,"throw"===e.method){if(t.iterator.return&&(e.method="return",e.arg=void 0,_(t,e),"throw"===e.method))return h;e.method="throw",e.arg=new TypeError("The iterator does not provide a 'throw' method")}return h}var n=f(r,t.iterator,e.arg);if("throw"===n.type)return e.method="throw",e.arg=n.arg,e.delegate=null,h;var o=n.arg;return o?o.done?(e[t.resultName]=o.value,e.next=t.nextLoc,"return"!==e.method&&(e.method="next",e.arg=void 0),e.delegate=null,h):o:(e.method="throw",e.arg=new TypeError("iterator result is not an object"),e.delegate=null,h)}function L(t){var e={tryLoc:t[0]};1 in t&&(e.catchLoc=t[1]),2 in t&&(e.finallyLoc=t[2],e.afterLoc=t[3]),this.tryEntries.push(e)}function k(t){var e=t.completion||{};e.type="normal",delete e.arg,t.completion=e}function E(t){this.tryEntries=[{tryLoc:"root"}],t.forEach(L,this),this.reset(!0)}function T(t){if(t){var e=t[o];if(e)return e.call(t);if("function"==typeof t.next)return t;if(!isNaN(t.length)){var n=-1,i=function e(){for(;++n<t.length;)if(r.call(t,n))return e.value=t[n],e.done=!1,e;return e.value=void 0,e.done=!0,e};return i.next=i}}return{next:O}}function O(){return{value:void 0,done:!0}}return p.prototype=y,s(w,"constructor",y),s(y,"constructor",p),p.displayName=s(y,c,"GeneratorFunction"),t.isGeneratorFunction=function(t){var e="function"==typeof t&&t.constructor;return!!e&&(e===p||"GeneratorFunction"===(e.displayName||e.name))},t.mark=function(t){return Object.setPrototypeOf?Object.setPrototypeOf(t,y):(t.__proto__=y,s(t,c,"GeneratorFunction")),t.prototype=Object.create(w),t},t.awrap=function(t){return{__await:t}},b(x.prototype),s(x.prototype,u,(function(){return this})),t.AsyncIterator=x,t.async=function(e,r,n,o,i){void 0===i&&(i=Promise);var a=new x(l(e,r,n,o),i);return t.isGeneratorFunction(r)?a:a.next().then((function(t){return t.done?t.value:a.next()}))},b(w),s(w,c,"Generator"),s(w,o,(function(){return this})),s(w,"toString",(function(){return"[object Generator]"})),t.keys=function(t){var e=[];for(var r in t)e.push(r);return e.reverse(),function r(){for(;e.length;){var n=e.pop();if(n in t)return r.value=n,r.done=!1,r}return r.done=!0,r}},t.values=T,E.prototype={constructor:E,reset:function(t){if(this.prev=0,this.next=0,this.sent=this._sent=void 0,this.done=!1,this.delegate=null,this.method="next",this.arg=void 0,this.tryEntries.forEach(k),!t)for(var e in this)"t"===e.charAt(0)&&r.call(this,e)&&!isNaN(+e.slice(1))&&(this[e]=void 0)},stop:function(){this.done=!0;var t=this.tryEntries[0].completion;if("throw"===t.type)throw t.arg;return this.rval},dispatchException:function(t){if(this.done)throw t;var e=this;function n(r,n){return a.type="throw",a.arg=t,e.next=r,n&&(e.method="next",e.arg=void 0),!!n}for(var o=this.tryEntries.length-1;o>=0;--o){var i=this.tryEntries[o],a=i.completion;if("root"===i.tryLoc)return n("end");if(i.tryLoc<=this.prev){var u=r.call(i,"catchLoc"),c=r.call(i,"finallyLoc");if(u&&c){if(this.prev<i.catchLoc)return n(i.catchLoc,!0);if(this.prev<i.finallyLoc)return n(i.finallyLoc)}else if(u){if(this.prev<i.catchLoc)return n(i.catchLoc,!0)}else{if(!c)throw new Error("try statement without catch or finally");if(this.prev<i.finallyLoc)return n(i.finallyLoc)}}}},abrupt:function(t,e){for(var n=this.tryEntries.length-1;n>=0;--n){var o=this.tryEntries[n];if(o.tryLoc<=this.prev&&r.call(o,"finallyLoc")&&this.prev<o.finallyLoc){var i=o;break}}i&&("break"===t||"continue"===t)&&i.tryLoc<=e&&e<=i.finallyLoc&&(i=null);var a=i?i.completion:{};return a.type=t,a.arg=e,i?(this.method="next",this.next=i.finallyLoc,h):this.complete(a)},complete:function(t,e){if("throw"===t.type)throw t.arg;return"break"===t.type||"continue"===t.type?this.next=t.arg:"return"===t.type?(this.rval=this.arg=t.arg,this.method="return",this.next="end"):"normal"===t.type&&e&&(this.next=e),h},finish:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.finallyLoc===t)return this.complete(r.completion,r.afterLoc),k(r),h}},catch:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.tryLoc===t){var n=r.completion;if("throw"===n.type){var o=n.arg;k(r)}return o}}throw new Error("illegal catch attempt")},delegateYield:function(t,e,r){return this.delegate={iterator:T(t),resultName:e,nextLoc:r},"next"===this.method&&(this.arg=void 0),h}},t}function u(t,e,r,n,o,i,a){try{var u=t[i](a),c=u.value}catch(t){return void r(t)}u.done?e(c):Promise.resolve(c).then(n,o)}function c(t){return function(){var e=this,r=arguments;return new Promise((function(n,o){var i=t.apply(e,r);function a(t){u(i,n,o,a,c,"next",t)}function c(t){u(i,n,o,a,c,"throw",t)}a(void 0)}))}}function s(){return l.apply(this,arguments)}function l(){return l=c(a().mark((function t(){var e,r,i,u=arguments;return a().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return e=u.length>0&&void 0!==u[0]?u[0]:null,t.next=3,o.Z.get("ajax/last");case 3:return r=t.sent,i="",i=e?e+="/"+r.data.data:"/system/"+r.data.data,t.abrupt("return",n.Z.push(i));case 7:case"end":return t.stop()}}),t)}))),l.apply(this,arguments)}},7297:(t,e,r)=>{function n(t,e){return t>2*e?"danger":t>1.5*e?"warning":t<e/2?"success":"info"}r.d(e,{G:()=>n})}}]);
//# sourceMappingURL=548.js.map