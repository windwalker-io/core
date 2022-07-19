/*! For license information please see 659.js.LICENSE.txt */
"use strict";(self.webpackChunk_windwalker_io_core=self.webpackChunk_windwalker_io_core||[]).push([[659],{644:(t,e,n)=>{n.d(e,{Z:()=>w});var r=n(1525),a=n(3630),s={key:0},u={class:"p-3",style:{"background-color":"var(--bs-gray-200)"}},i={class:"text-xl font-bold text-gray-800"},o=(0,r.Uk)(),c={class:"text-muted"},l=["href"],d=(0,r.Uk)(),f=n(4545);const p={name:"DefaultLayout",setup:function(){return{currentId:f.p,currentData:f.I}}},w=(0,n(4181).Z)(p,[["render",function(t,e,n,f,p,w){return f.currentData?((0,r.wg)(),(0,r.iD)("div",s,[(0,r._)("div",u,[(0,r._)("h1",i,[(0,r.WI)(t.$slots,"title")]),o,(0,r._)("div",c,[(0,r.Uk)((0,a.zw)(f.currentId)+" / ",1),(0,r._)("a",{target:"_blank",class:"text-gray-600",href:f.currentData.url},(0,a.zw)(f.currentData.url),9,l)])]),d,(0,r.WI)(t.$slots,"default")])):(0,r.kq)("",!0)}]])},8659:(t,e,n)=>{n.r(e),n.d(e,{default:()=>Lt});var r=n(1525),a=n(3630),s=(0,r.Uk)("\r\n      Database\r\n    "),u=(0,r.Uk)(),i={key:0,class:"p-4"},o=(0,r._)("h4",null,"Queries",-1),c=(0,r.Uk)(),l={class:"my-3"},d=(0,r.Uk)("\r\n          Count: "),f={class:"bg-blue-300 text-blue-600 px-2 py-1 rounded-sm text-sm"},p=(0,r.Uk)("\r\n          -\r\n          Time: "),w={class:"bg-blue-300 text-blue-600 px-2 py-1 rounded-sm text-sm"},m=(0,r.Uk)("\r\n          -\r\n          Memory: "),v={class:"bg-blue-300 text-blue-600 px-2 py-1 rounded-sm text-sm"},b=(0,r.Uk)(),y={class:"mt-5"},h={class:"mb-4"},_=n(5556),k=["id"],g={class:"card-body"},x={class:"d-flex align-items-center justify-content-between mb-4"},U={class:"text-gray-700 font-semibold text-2xl tracking-wide mb-2"},C=(0,r.Uk)(),z={class:"text-muted"},D=[(0,r._)("svg",{style:{height:"14px",display:"inline"},"aria-hidden":"true",focusable:"false","data-prefix":"fas","data-icon":"clipboard",class:"svg-inline--fa fa-clipboard fa-w-12",role:"img",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 384 512"},[(0,r._)("path",{fill:"currentColor",d:"M336 64h-53.88C268.9 26.8 233.7 0 192 0S115.1 26.8 101.9 64H48C21.5 64 0 85.48 0 112v352C0 490.5 21.5 512 48 512h288c26.5 0 48-21.48 48-48v-352C384 85.48 362.5 64 336 64zM192 64c17.67 0 32 14.33 32 32c0 17.67-14.33 32-32 32S160 113.7 160 96C160 78.33 174.3 64 192 64zM272 224h-160C103.2 224 96 216.8 96 208C96 199.2 103.2 192 112 192h160C280.8 192 288 199.2 288 208S280.8 224 272 224z"})],-1),(0,r.Uk)(),(0,r._)("span",{class:""},"Copy",-1)],M=(0,r.Uk)(),R=(0,r._)("button",{type:"button",class:"btn btn-primary btn-sm"},[(0,r._)("svg",{style:{height:"14px"},"aria-hidden":"true",focusable:"false","data-prefix":"fas","data-icon":"link",class:"svg-inline--fa fa-link fa-w-20",role:"img",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 640 512"},[(0,r._)("path",{fill:"currentColor",d:"M598.6 41.41C570.1 13.8 534.8 0 498.6 0s-72.36 13.8-99.96 41.41l-43.36 43.36c15.11 8.012 29.47 17.58 41.91 30.02c3.146 3.146 5.898 6.518 8.742 9.838l37.96-37.96C458.5 72.05 477.1 64 498.6 64c20.67 0 40.1 8.047 54.71 22.66c14.61 14.61 22.66 34.04 22.66 54.71s-8.049 40.1-22.66 54.71l-133.3 133.3C405.5 343.1 386 352 365.4 352s-40.1-8.048-54.71-22.66C296 314.7 287.1 295.3 287.1 274.6s8.047-40.1 22.66-54.71L314.2 216.4C312.1 212.5 309.9 208.5 306.7 205.3C298.1 196.7 286.8 192 274.6 192c-11.93 0-23.1 4.664-31.61 12.97c-30.71 53.96-23.63 123.6 22.39 169.6C293 402.2 329.2 416 365.4 416c36.18 0 72.36-13.8 99.96-41.41L598.6 241.3c28.45-28.45 42.24-66.01 41.37-103.3C639.1 102.1 625.4 68.16 598.6 41.41zM234 387.4L196.1 425.3C181.5 439.1 162 448 141.4 448c-20.67 0-40.1-8.047-54.71-22.66c-14.61-14.61-22.66-34.04-22.66-54.71s8.049-40.1 22.66-54.71l133.3-133.3C234.5 168 253.1 160 274.6 160s40.1 8.048 54.71 22.66c14.62 14.61 22.66 34.04 22.66 54.71s-8.047 40.1-22.66 54.71L325.8 295.6c2.094 3.939 4.219 7.895 7.465 11.15C341.9 315.3 353.3 320 365.4 320c11.93 0 23.1-4.664 31.61-12.97c30.71-53.96 23.63-123.6-22.39-169.6C346.1 109.8 310.8 96 274.6 96C238.4 96 202.3 109.8 174.7 137.4L41.41 270.7c-27.6 27.6-41.41 63.78-41.41 99.96c-.0001 36.18 13.8 72.36 41.41 99.97C69.01 498.2 105.2 512 141.4 512c36.18 0 72.36-13.8 99.96-41.41l43.36-43.36c-15.11-8.012-29.47-17.58-41.91-30.02C239.6 394.1 236.9 390.7 234 387.4z"})])],-1),q=(0,r.Uk)(),L=[(0,r._)("svg",{style:{height:"14px"},"aria-hidden":"true",focusable:"false","data-prefix":"fas","data-icon":"arrow-rotate-right",class:"svg-inline--fa fa-arrow-rotate-right fa-w-16",role:"img",xmlns:"http://www.w3.org/2000/svg",viewBox:"0 0 512 512"},[(0,r._)("path",{fill:"currentColor",d:"M496 48V192c0 17.69-14.31 32-32 32H320c-17.69 0-32-14.31-32-32s14.31-32 32-32h63.39c-29.97-39.7-77.25-63.78-127.6-63.78C167.7 96.22 96 167.9 96 256s71.69 159.8 159.8 159.8c34.88 0 68.03-11.03 95.88-31.94c14.22-10.53 34.22-7.75 44.81 6.375c10.59 14.16 7.75 34.22-6.375 44.81c-39.03 29.28-85.36 44.86-134.2 44.86C132.5 479.9 32 379.4 32 256s100.5-223.9 223.9-223.9c69.15 0 134 32.47 176.1 86.12V48c0-17.69 14.31-32 32-32S496 30.31 496 48z"})],-1)],T=(0,r.Uk)(),Z={class:""},H=["innerHTML"],I=(0,r.Uk)(),j={class:"py-4"},B=(0,r.Uk)("\r\n        Query Time:\r\n        "),K={class:"badge bg-secondary"},P=(0,r.Uk)("\r\n        Memory:\r\n        "),Q={class:"badge bg-secondary"},S=(0,r.Uk)("\r\n        Return Rows\r\n        "),W={class:"badge bg-secondary rounded-pill"},E=(0,r.Uk)(),F={key:0},V={class:"table"},Y=(0,r._)("thead",null,[(0,r._)("tr",null,[(0,r._)("th",{class:""},"\r\n            ID\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Select Type\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Table\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Type\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Possible Keys\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Key\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Key Length\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Reference\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Rows\r\n          "),(0,r.Uk)(),(0,r._)("th",{class:""},"\r\n            Extra\r\n          ")])],-1),$=(0,r.Uk)(),N={class:"bg-white"},O={class:""},A=(0,r.Uk)(),G={class:""},J=(0,r.Uk)(),X={class:""},tt=(0,r.Uk)(),et={class:""},nt=(0,r.Uk)(),rt={class:"text-wrap"},at={style:{"word-break":"break-all"}},st=(0,r.Uk)(),ut={class:""},it=(0,r.Uk)(),ot={class:""},ct=(0,r.Uk)(),lt={class:""},dt=(0,r.Uk)(),ft={class:""},pt=(0,r.Uk)(),wt={class:""},mt=n(715),vt=n(8601),bt=n(5518);function yt(t,e,n,r,a,s,u){try{var i=t[s](u),o=i.value}catch(t){return void n(t)}i.done?e(o):Promise.resolve(o).then(r,a)}function ht(t){return function(){var e=this,n=arguments;return new Promise((function(r,a){var s=t.apply(e,n);function u(t){yt(s,r,a,u,i,"next",t)}function i(t){yt(s,r,a,u,i,"throw",t)}u(void 0)}))}}function _t(){return kt.apply(this,arguments)}function kt(){return kt=ht(regeneratorRuntime.mark((function t(){var e,n,r,a=arguments;return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return e=a.length>0&&void 0!==a[0]?a[0]:null,t.next=3,bt.Z.get("ajax/last");case 3:return n=t.sent,r="",r=e?e+="/"+n.data.data:"/system/"+n.data.data,t.abrupt("return",vt.Z.push(r));case 7:case"end":return t.stop()}}),t)}))),kt.apply(this,arguments)}const gt={name:"query-info",props:{i:Number,item:Object},setup:function(t){return{formatQuery:xt,round:Ut,goToLast:_t,copy:function(){navigator.clipboard.writeText(t.item.debug_query)}}}};function xt(t){return(0,mt.WU)(t).replace(/\n/,"<br>")}function Ut(t){return Math.round(100*t)/100}var Ct=n(4181);const zt=(0,Ct.Z)(gt,[["render",function(t,e,n,s,u,i){return(0,r.wg)(),(0,r.iD)("div",{id:"query-".concat(n.i),class:"card rounded-3 border-0 shadow overflow-hidden mx-auto"},[(0,r._)("div",g,[(0,r._)("div",x,[(0,r._)("div",null,[(0,r._)("h2",U,"\r\n            Query: "+(0,a.zw)(n.i),1)]),C,(0,r._)("div",z,[(0,r._)("button",{type:"button",class:"btn btn-outline-primary btn-sm",onClick:e[0]||(e[0]=function(){return s.copy&&s.copy.apply(s,arguments)})},D),M,R,q,(0,r._)("button",{type:"button",class:"btn btn-success btn-sm",onClick:e[1]||(e[1]=function(t){return s.goToLast("/db")})},L)])]),T,(0,r._)("div",Z,[(0,r._)("pre",{style:{"word-break":"break-all","white-space":"pre-wrap"},class:"bg-light p-4",innerHTML:n.item.debug_query},null,8,H)]),I,(0,r._)("div",j,[B,(0,r._)("span",K,(0,a.zw)(s.round(1e3*n.item.time))+"ms\r\n        ",1),P,(0,r._)("span",Q,(0,a.zw)(s.round(n.item.memory/1024/1024))+"MB\r\n        ",1),S,(0,r._)("span",W,(0,a.zw)(n.item.count),1)])]),E,n.item.explain?((0,r.wg)(),(0,r.iD)("div",F,[(0,r._)("table",V,[Y,$,(0,r._)("tbody",N,[((0,r.wg)(!0),(0,r.iD)(r.HY,null,(0,r.Ko)(n.item.explain,(function(t){return(0,r.wg)(),(0,r.iD)("tr",null,[(0,r._)("td",O,(0,a.zw)(t.id),1),A,(0,r._)("td",G,(0,a.zw)(t.select_type),1),J,(0,r._)("td",X,(0,a.zw)(t.table),1),tt,(0,r._)("td",et,(0,a.zw)(t.type),1),nt,(0,r._)("td",rt,[(0,r._)("div",at,(0,a.zw)(t.possible_keys),1)]),st,(0,r._)("td",ut,(0,a.zw)(t.key),1),it,(0,r._)("td",ot,(0,a.zw)(t.key_len),1),ct,(0,r._)("td",lt,(0,a.zw)(t.ref),1),dt,(0,r._)("td",ft,(0,a.zw)(t.rows),1),pt,(0,r._)("td",wt,(0,a.zw)(t.Extra),1)])})),256))])])])):(0,r.kq)("",!0)],8,k)}]]);function Dt(t,e,n,r,a,s,u){try{var i=t[s](u),o=i.value}catch(t){return void n(t)}i.done?e(o):Promise.resolve(o).then(r,a)}function Mt(t){return function(){var e=this,n=arguments;return new Promise((function(r,a){var s=t.apply(e,n);function u(t){Dt(s,r,a,u,i,"next",t)}function i(t){Dt(s,r,a,u,i,"throw",t)}u(void 0)}))}}const Rt={name:"Database",components:{DefaultLayout:n(644).Z,QueryInfo:zt},beforeRouteEnter:function(t,e,n){return Mt(regeneratorRuntime.mark((function t(){return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:n(function(){var t=Mt(regeneratorRuntime.mark((function t(e){var n;return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,bt.Z.get("ajax/data?path=db");case 2:n=t.sent,e.data=n.data.data;case 4:case"end":return t.stop()}}),t)})));return function(e){return t.apply(this,arguments)}}());case 1:case"end":return t.stop()}}),t)})))()},beforeRouteUpdate:function(t,e,n){var r=this;return Mt(regeneratorRuntime.mark((function t(){var e;return regeneratorRuntime.wrap((function(t){for(;;)switch(t.prev=t.next){case 0:return t.next=2,bt.Z.get("ajax/data?path=db");case 2:e=t.sent,r.data=e.data.data;case 4:case"end":return t.stop()}}),t)})))()},setup:function(){var t=(0,_.iH)(null),e=(0,r.Fl)((function(){var e,n;return 1e3*(null===(e=t.value)||void 0===e||null===(n=e.queries)||void 0===n?void 0:n.reduce((function(t,e){return t+e.time}),0))})),n=(0,r.Fl)((function(){var e,n;return(null===(e=t.value)||void 0===e||null===(n=e.queries)||void 0===n?void 0:n.reduce((function(t,e){return t+e.memory}),0))/1024/1024}));return{data:t,totalTime:e,totalMemory:n,round:qt}}};function qt(t){return Math.round(100*t)/100}const Lt=(0,Ct.Z)(Rt,[["render",function(t,e,n,_,k,g){var x=(0,r.up)("query-info"),U=(0,r.up)("default-layout");return(0,r.wg)(),(0,r.j4)(U,null,{title:(0,r.w5)((function(){return[s]})),default:(0,r.w5)((function(){var t,e;return[u,_.data?((0,r.wg)(),(0,r.iD)("div",i,[(0,r._)("div",null,[o,c,(0,r._)("div",l,[d,(0,r._)("span",f,(0,a.zw)(null===(t=_.data)||void 0===t||null===(e=t.queries)||void 0===e?void 0:e.length),1),p,(0,r._)("span",w,(0,a.zw)(_.round(_.totalTime))+"ms",1),m,(0,r._)("span",v,(0,a.zw)(_.round(_.totalMemory))+"MB",1)])]),b,(0,r._)("div",y,[((0,r.wg)(!0),(0,r.iD)(r.HY,null,(0,r.Ko)(_.data.queries,(function(t,e){return(0,r.wg)(),(0,r.iD)("div",h,[(0,r.Wm)(x,{item:t,i:e+1},null,8,["item","i"])])})),256))])])):(0,r.kq)("",!0)]})),_:1})}]])}}]);
//# sourceMappingURL=659.js.map