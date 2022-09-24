/*! For license information please see chunk-vendor-694.js.LICENSE.txt */
"use strict";(self.webpackChunk_windwalker_io_core=self.webpackChunk_windwalker_io_core||[]).push([[694],{8694:(t,e,r)=>{r.r(e),r.d(e,{default:()=>at});var n=r(1525),o=r(1456),a=(0,n.Uk)("\r\n      Request\r\n    "),i=(0,n.Uk)(),c={key:0,ref:"root",class:"p-4"},s={class:"l-section l-section--get mt-5"},u=(0,n._)("h3",null,"GET Variables",-1),l=(0,n.Uk)(),f=(0,n.Uk)(),h={class:"l-section l-section--body mt-5"},p=(0,n._)("h3",null,"Body Variables",-1),v=(0,n.Uk)(),d=(0,n.Uk)(),y={class:"l-section l-section--files mt-5"},m=(0,n._)("h3",null,"FILES Variables",-1),g=(0,n.Uk)(),w=(0,n.Uk)(),k={class:"l-section l-section--session mt-5"},b=(0,n._)("h3",null,"Session Variables",-1),_=(0,n.Uk)(),x=(0,n.Uk)(),E={class:"l-section l-section--cookies mt-5"},L=(0,n._)("h3",null,"Cookies Variables",-1),U=(0,n.Uk)(),V=(0,n.Uk)(),S={class:"l-section l-section--server mt-5"},j=(0,n._)("h3",null,"SERVER Variables",-1),q=(0,n.Uk)(),O=(0,n.Uk)(),C={class:"l-section l-section--env mt-5"},T=(0,n._)("h3",null,"ENV Variables",-1),G=(0,n.Uk)(),N=(0,n.Uk)(),R={class:"nav flex-column ps-4 small"},P={class:"nav-item"},F=(0,n.Uk)(),M={class:"nav-item"},W=(0,n.Uk)(),Z={class:"nav-item"},I=(0,n.Uk)(),D={class:"nav-item"},A=(0,n.Uk)(),B={class:"nav-item"},H=(0,n.Uk)(),K={class:"nav-item"},Y=(0,n.Uk)(),z={class:"nav-item"},J=r(5556),Q=r(5955),X=r(6072),$=r(5518);function tt(t){return tt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},tt(t)}function et(){et=function(){return t};var t={},e=Object.prototype,r=e.hasOwnProperty,n="function"==typeof Symbol?Symbol:{},o=n.iterator||"@@iterator",a=n.asyncIterator||"@@asyncIterator",i=n.toStringTag||"@@toStringTag";function c(t,e,r){return Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}),t[e]}try{c({},"")}catch(t){c=function(t,e,r){return t[e]=r}}function s(t,e,r,n){var o=e&&e.prototype instanceof f?e:f,a=Object.create(o.prototype),i=new x(n||[]);return a._invoke=function(t,e,r){var n="suspendedStart";return function(o,a){if("executing"===n)throw new Error("Generator is already running");if("completed"===n){if("throw"===o)throw a;return{value:void 0,done:!0}}for(r.method=o,r.arg=a;;){var i=r.delegate;if(i){var c=k(i,r);if(c){if(c===l)continue;return c}}if("next"===r.method)r.sent=r._sent=r.arg;else if("throw"===r.method){if("suspendedStart"===n)throw n="completed",r.arg;r.dispatchException(r.arg)}else"return"===r.method&&r.abrupt("return",r.arg);n="executing";var s=u(t,e,r);if("normal"===s.type){if(n=r.done?"completed":"suspendedYield",s.arg===l)continue;return{value:s.arg,done:r.done}}"throw"===s.type&&(n="completed",r.method="throw",r.arg=s.arg)}}}(t,r,i),a}function u(t,e,r){try{return{type:"normal",arg:t.call(e,r)}}catch(t){return{type:"throw",arg:t}}}t.wrap=s;var l={};function f(){}function h(){}function p(){}var v={};c(v,o,(function(){return this}));var d=Object.getPrototypeOf,y=d&&d(d(E([])));y&&y!==e&&r.call(y,o)&&(v=y);var m=p.prototype=f.prototype=Object.create(v);function g(t){["next","throw","return"].forEach((function(e){c(t,e,(function(t){return this._invoke(e,t)}))}))}function w(t,e){function n(o,a,i,c){var s=u(t[o],t,a);if("throw"!==s.type){var l=s.arg,f=l.value;return f&&"object"==tt(f)&&r.call(f,"__await")?e.resolve(f.__await).then((function(t){n("next",t,i,c)}),(function(t){n("throw",t,i,c)})):e.resolve(f).then((function(t){l.value=t,i(l)}),(function(t){return n("throw",t,i,c)}))}c(s.arg)}var o;this._invoke=function(t,r){function a(){return new e((function(e,o){n(t,r,e,o)}))}return o=o?o.then(a,a):a()}}function k(t,e){var r=t.iterator[e.method];if(void 0===r){if(e.delegate=null,"throw"===e.method){if(t.iterator.return&&(e.method="return",e.arg=void 0,k(t,e),"throw"===e.method))return l;e.method="throw",e.arg=new TypeError("The iterator does not provide a 'throw' method")}return l}var n=u(r,t.iterator,e.arg);if("throw"===n.type)return e.method="throw",e.arg=n.arg,e.delegate=null,l;var o=n.arg;return o?o.done?(e[t.resultName]=o.value,e.next=t.nextLoc,"return"!==e.method&&(e.method="next",e.arg=void 0),e.delegate=null,l):o:(e.method="throw",e.arg=new TypeError("iterator result is not an object"),e.delegate=null,l)}function b(t){var e={tryLoc:t[0]};1 in t&&(e.catchLoc=t[1]),2 in t&&(e.finallyLoc=t[2],e.afterLoc=t[3]),this.tryEntries.push(e)}function _(t){var e=t.completion||{};e.type="normal",delete e.arg,t.completion=e}function x(t){this.tryEntries=[{tryLoc:"root"}],t.forEach(b,this),this.reset(!0)}function E(t){if(t){var e=t[o];if(e)return e.call(t);if("function"==typeof t.next)return t;if(!isNaN(t.length)){var n=-1,a=function e(){for(;++n<t.length;)if(r.call(t,n))return e.value=t[n],e.done=!1,e;return e.value=void 0,e.done=!0,e};return a.next=a}}return{next:L}}function L(){return{value:void 0,done:!0}}return h.prototype=p,c(m,"constructor",p),c(p,"constructor",h),h.displayName=c(p,i,"GeneratorFunction"),t.isGeneratorFunction=function(t){var e="function"==typeof t&&t.constructor;return!!e&&(e===h||"GeneratorFunction"===(e.displayName||e.name))},t.mark=function(t){return Object.setPrototypeOf?Object.setPrototypeOf(t,p):(t.__proto__=p,c(t,i,"GeneratorFunction")),t.prototype=Object.create(m),t},t.awrap=function(t){return{__await:t}},g(w.prototype),c(w.prototype,a,(function(){return this})),t.AsyncIterator=w,t.async=function(e,r,n,o,a){void 0===a&&(a=Promise);var i=new w(s(e,r,n,o),a);return t.isGeneratorFunction(r)?i:i.next().then((function(t){return t.done?t.value:i.next()}))},g(m),c(m,i,"Generator"),c(m,o,(function(){return this})),c(m,"toString",(function(){return"[object Generator]"})),t.keys=function(t){var e=[];for(var r in t)e.push(r);return e.reverse(),function r(){for(;e.length;){var n=e.pop();if(n in t)return r.value=n,r.done=!1,r}return r.done=!0,r}},t.values=E,x.prototype={constructor:x,reset:function(t){if(this.prev=0,this.next=0,this.sent=this._sent=void 0,this.done=!1,this.delegate=null,this.method="next",this.arg=void 0,this.tryEntries.forEach(_),!t)for(var e in this)"t"===e.charAt(0)&&r.call(this,e)&&!isNaN(+e.slice(1))&&(this[e]=void 0)},stop:function(){this.done=!0;var t=this.tryEntries[0].completion;if("throw"===t.type)throw t.arg;return this.rval},dispatchException:function(t){if(this.done)throw t;var e=this;function n(r,n){return i.type="throw",i.arg=t,e.next=r,n&&(e.method="next",e.arg=void 0),!!n}for(var o=this.tryEntries.length-1;o>=0;--o){var a=this.tryEntries[o],i=a.completion;if("root"===a.tryLoc)return n("end");if(a.tryLoc<=this.prev){var c=r.call(a,"catchLoc"),s=r.call(a,"finallyLoc");if(c&&s){if(this.prev<a.catchLoc)return n(a.catchLoc,!0);if(this.prev<a.finallyLoc)return n(a.finallyLoc)}else if(c){if(this.prev<a.catchLoc)return n(a.catchLoc,!0)}else{if(!s)throw new Error("try statement without catch or finally");if(this.prev<a.finallyLoc)return n(a.finallyLoc)}}}},abrupt:function(t,e){for(var n=this.tryEntries.length-1;n>=0;--n){var o=this.tryEntries[n];if(o.tryLoc<=this.prev&&r.call(o,"finallyLoc")&&this.prev<o.finallyLoc){var a=o;break}}a&&("break"===t||"continue"===t)&&a.tryLoc<=e&&e<=a.finallyLoc&&(a=null);var i=a?a.completion:{};return i.type=t,i.arg=e,a?(this.method="next",this.next=a.finallyLoc,l):this.complete(i)},complete:function(t,e){if("throw"===t.type)throw t.arg;return"break"===t.type||"continue"===t.type?this.next=t.arg:"return"===t.type?(this.rval=this.arg=t.arg,this.method="return",this.next="end"):"normal"===t.type&&e&&(this.next=e),l},finish:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.finallyLoc===t)return this.complete(r.completion,r.afterLoc),_(r),l}},catch:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var r=this.tryEntries[e];if(r.tryLoc===t){var n=r.completion;if("throw"===n.type){var o=n.arg;_(r)}return o}}throw new Error("illegal catch attempt")},delegateYield:function(t,e,r){return this.delegate={iterator:E(t),resultName:e,nextLoc:r},"next"===this.method&&(this.arg=void 0),l}},t}function rt(t,e,r,n,o,a,i){try{var c=t[a](i),s=c.value}catch(t){return void r(t)}c.done?e(s):Promise.resolve(s).then(n,o)}function nt(t){return function(){var e=this,r=arguments;return new Promise((function(n,o){var a=t.apply(e,r);function i(t){rt(a,n,o,i,c,"next",t)}function c(t){rt(a,n,o,i,c,"throw",t)}i(void 0)}))}}const ot={name:"Request",components:{KeyValueTable:Q.Z,DefaultLayout:X.Z},beforeRouteEnter:function(t,e,r){return nt(et().mark((function t(){return et().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:r(function(){var t=nt(et().mark((function t(e){var r;return et().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,$.Z.get("ajax/data?path=http");case 2:r=t.sent,e.data=r.data.data;case 4:case"end":return t.stop()}}),t)})));return function(e){return t.apply(this,arguments)}}());case 1:case"end":return t.stop()}}),t)})))()},beforeRouteUpdate:function(t,e,r){var n=this;return nt(et().mark((function t(){var e;return et().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,$.Z.get("ajax/data?path=http");case 2:e=t.sent,n.data=e.data.data;case 4:case"end":return t.stop()}}),t)})))()},setup:function(){var t=(0,J.iH)(null),e=(0,J.iH)(null);return{data:t,root:e,goto:function(t){var r=e.value.querySelector(".l-section--".concat(t));r&&window.scrollTo({top:r.offsetTop-100,behavior:"smooth"})}}}},at=(0,r(4181).Z)(ot,[["render",function(t,e,r,J,Q,X){var $=(0,n.up)("KeyValueTable"),tt=(0,n.up)("DefaultLayout");return(0,n.wg)(),(0,n.j4)(tt,null,{title:(0,n.w5)((function(){return[a]})),default:(0,n.w5)((function(){return[i,J.data?((0,n.wg)(),(0,n.iD)("div",c,[(0,n._)("section",s,[u,l,(0,n.Wm)($,{data:J.data.request.query},null,8,["data"])]),f,(0,n._)("section",h,[p,v,(0,n.Wm)($,{data:J.data.request.body},null,8,["data"])]),d,(0,n._)("section",y,[m,g,(0,n.Wm)($,{data:J.data.request.files},null,8,["data"])]),w,(0,n._)("section",k,[b,_,(0,n.Wm)($,{data:J.data.session},null,8,["data"])]),x,(0,n._)("section",E,[L,U,(0,n.Wm)($,{data:J.data.cookies||J.data.request.cookies},null,8,["data"])]),V,(0,n._)("section",S,[j,q,(0,n.Wm)($,{data:J.data.request.server},null,8,["data"])]),O,(0,n._)("section",C,[T,G,(0,n.Wm)($,{data:J.data.request.env},null,8,["data"])])],512)):(0,n.kq)("",!0),N,((0,n.wg)(),(0,n.j4)(n.lR,{to:".nav-item--request"},[(0,n._)("ul",R,[(0,n._)("li",P,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[0]||(e[0]=(0,o.iM)((function(t){return J.goto("get")}),["prevent"]))},"\r\n            GET Variables\r\n          ")]),F,(0,n._)("li",M,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[1]||(e[1]=(0,o.iM)((function(t){return J.goto("body")}),["prevent"]))},"\r\n            Body Variables\r\n          ")]),W,(0,n._)("li",Z,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[2]||(e[2]=(0,o.iM)((function(t){return J.goto("files")}),["prevent"]))},"\r\n            Files Variables\r\n          ")]),I,(0,n._)("li",D,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[3]||(e[3]=(0,o.iM)((function(t){return J.goto("session")}),["prevent"]))},"\r\n            Session Variables\r\n          ")]),A,(0,n._)("li",B,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[4]||(e[4]=(0,o.iM)((function(t){return J.goto("cookies")}),["prevent"]))},"\r\n            Cookies Variables\r\n          ")]),H,(0,n._)("li",K,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[5]||(e[5]=(0,o.iM)((function(t){return J.goto("server")}),["prevent"]))},"\r\n            SERVER Variables\r\n          ")]),Y,(0,n._)("li",z,[(0,n._)("a",{href:"#",class:"nav-link",onClick:e[6]||(e[6]=(0,o.iM)((function(t){return J.goto("env")}),["prevent"]))},"\r\n            ENV Variables\r\n          ")])])]))]})),_:1})}]])}}]);
//# sourceMappingURL=694.js.map