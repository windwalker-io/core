"use strict";(self.webpackChunksrc_debugger_debugger_js=self.webpackChunksrc_debugger_debugger_js||[]).push([[153],{5956:(t,e,a)=>{a.d(e,{Z:()=>f});var s=a(1525),n=a(672),r=a(3630),l=a(457),o=a(5054),i=a(4545);const u={key:0},d={class:"p-3 mt-3 mx-4 rounded-3",style:{"background-color":"var(--bs-gray-800)"}},c={class:"text-xl font-bold text-gray-800"},m={class:"text-muted"},b=["href"],f={__name:"DefaultLayout",setup(t){const e=(0,l.yj)();return(t,a)=>{const l=(0,s.up)("fa-icon"),f=(0,s.up)("router-link");return(0,n.SU)(i.I)?((0,s.wg)(),(0,s.iD)("div",u,[(0,s._)("div",d,[(0,s._)("h1",c,[(0,s.WI)(t.$slots,"title")]),(0,s.Uk)(),(0,s._)("div",m,[(0,s.Wm)(f,{to:"/",class:"btn btn-sm btn-primary"},{default:(0,s.w5)((()=>[(0,s.Wm)(l,{icon:"fa-solid fa-list"})])),_:1}),(0,s.Uk)(),(0,s._)("button",{type:"button",onClick:a[0]||(a[0]=t=>(0,n.SU)(o.D)("/"+(0,n.SU)(e).name)),class:"btn btn-sm btn-success"},[(0,s.Wm)(l,{icon:"fa-solid fa-arrows-rotate"})]),(0,s.Uk)("\r\n      /\r\n      "+(0,r.zw)((0,n.SU)(i.p))+"\r\n      / ",1),(0,s._)("a",{target:"_blank",class:"text-gray-600",href:(0,n.SU)(i.I).url},(0,r.zw)((0,n.SU)(i.I).url),9,b)])]),(0,s.Uk)(),(0,s.WI)(t.$slots,"default")])):(0,s.kq)("",!0)}}}},7153:(t,e,a)=>{a.r(e),a.d(e,{default:()=>z});var s=a(1525),n=a(3630);const r={key:0,class:"p-4"},l={class:"l-section l-section--system"},o=(0,s._)("h3",null,"System Timeline",-1),i={class:"l-section l-section--profilers mt-5"},u={class:"nav nav-pills",id:"profilers-tab",role:"tablist"},d={class:"nav-item",role:"presentation"},c=["data-bs-target"],m={class:"tab-content mt-4",id:"myTabContent"},b=["id"],f={class:""};var g=a(672);const y={class:"table table-bordered"},_=(0,s._)("thead",null,[(0,s._)("tr",null,[(0,s._)("th",null,"Label"),(0,s.Uk)(),(0,s._)("th",{class:"text-end"},"Total Time"),(0,s.Uk)(),(0,s._)("th",{class:"text-end"},"Time"),(0,s.Uk)(),(0,s._)("th",{class:"text-end"},"Total Memory"),(0,s.Uk)(),(0,s._)("th",{class:"text-end"},"Memory")])],-1),p={class:"text-end"},k={class:"badge bg-secondary"},w={key:0,class:"text-end"},v={class:"text-end"},T={class:"badge bg-secondary"},U={key:1,class:"text-end"};var h=a(7297);const x={name:"TimelineTable",props:{items:Array},setup(){let t=0,e=0,a=0,s=0;return{getTimeOffset:function(a){return e=a-t,t=a,e},getCurrentTimeOffset:function(){return e},getMemoryOffset:function(t){return s=t-a,a=t,s},getCurrentMemoryOffset:function(){return s},stateColor:h.G,bytesToMB:function(t){return t/1024/1024},round:function(t){return Math.round(1e4*t)/1e4}}}};var C=a(4181);const D=(0,C.Z)(x,[["render",function(t,e,a,r,l,o){return(0,s.wg)(),(0,s.iD)("div",null,[(0,s._)("table",y,[_,(0,s.Uk)(),(0,s._)("tbody",null,[((0,s.wg)(!0),(0,s.iD)(s.HY,null,(0,s.Ko)(a.items,(t=>((0,s.wg)(),(0,s.iD)("tr",null,[(0,s._)("td",null,(0,n.zw)(t.label),1),(0,s.Uk)(),(0,s._)("td",p,[(0,s._)("span",k,(0,n.zw)(r.round(t.endTime))+"ms\r\n        ",1)]),(0,s.Uk)(),(r.getTimeOffset(t.endTime),(0,s.wg)(),(0,s.iD)("td",w,[(0,s._)("span",{class:(0,n.C_)(["badge","bg-"+r.stateColor(r.getCurrentTimeOffset(),50)])},(0,n.zw)(r.round(r.getCurrentTimeOffset()))+"ms\r\n        ",3)])),(0,s.Uk)(),(0,s._)("td",v,[(0,s._)("span",T,(0,n.zw)(r.round(r.bytesToMB(t.memory)))+"MB\r\n        ",1)]),(0,s.Uk)(),(r.getMemoryOffset(t.memory),(0,s.wg)(),(0,s.iD)("td",U,[(0,s._)("span",{class:(0,n.C_)(["badge","bg-"+r.stateColor(r.bytesToMB(r.getCurrentMemoryOffset()),2)])},(0,n.zw)(r.round(r.bytesToMB(r.getCurrentMemoryOffset())))+"MB\r\n        ",3)]))])))),256))])])])}]]);var M=a(5956),O=a(363);const j={name:"Timeline",components:{TimelineTable:D,DefaultLayout:M.Z},async beforeRouteEnter(t,e,a){a((async t=>{const e=await O.Z.get("ajax/data?path=profiler");t.data=e.data.data}))},async beforeRouteUpdate(t,e,a){const s=await O.Z.get("ajax/data?path=profiler");this.data=s.data.data},setup(){const t=(0,g.iH)(null),e=(0,s.Fl)((()=>Object.keys(t.value))),a=(0,s.Fl)((()=>t.value&&t.value.main.items.filter((t=>-1!==(t.tags||[]).indexOf("system")))||[]));return{data:t,instances:e,systemItems:a}}},z=(0,C.Z)(j,[["render",function(t,e,a,g,y,_){const p=(0,s.up)("TimelineTable"),k=(0,s.up)("DefaultLayout");return(0,s.wg)(),(0,s.j4)(k,null,{title:(0,s.w5)((()=>[(0,s.Uk)("\r\n    Timeline\r\n  ")])),default:(0,s.w5)((()=>[(0,s.Uk)(),g.data?((0,s.wg)(),(0,s.iD)("div",r,[(0,s._)("section",l,[o,(0,s.Uk)(),(0,s.Wm)(p,{items:g.systemItems},null,8,["items"])]),(0,s.Uk)(),(0,s._)("section",i,[(0,s._)("ul",u,[((0,s.wg)(!0),(0,s.iD)(s.HY,null,(0,s.Ko)(g.instances,((t,e)=>((0,s.wg)(),(0,s.iD)("li",d,[(0,s._)("button",{class:"nav-link active",id:"home-tab","data-bs-toggle":"tab","data-bs-target":`tab-${t}`,type:"button",role:"tab","aria-selected":"true"},(0,n.zw)(t),9,c)])))),256))]),(0,s.Uk)(),(0,s._)("div",m,[((0,s.wg)(!0),(0,s.iD)(s.HY,null,(0,s.Ko)(g.instances,((t,e)=>((0,s.wg)(),(0,s.iD)("div",{class:(0,n.C_)(["tab-pane fade",[0===e?"show active":""]]),id:`tab-${t}`,role:"tabpanel",tabindex:"0"},[(0,s._)("div",f,[(0,s.Wm)(p,{items:g.data[t].items},null,8,["items"])])],10,b)))),256))])])])):(0,s.kq)("",!0)])),_:1})}]])},5054:(t,e,a)=>{a.d(e,{D:()=>r});var s=a(8601),n=a(363);async function r(){let t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:void 0;const e=await n.Z.get("ajax/last");let a="";return a=t?t+"/"+e.data.data:"/system/"+e.data.data,s.Z.push(a)}},7297:(t,e,a)=>{function s(t,e){return t>2*e?"danger":t>1.5*e?"warning":t<e/2?"success":"info"}function n(t){return t>=300&&t<400?"info":t>=400&&t<500?"warning":t>=200&&t<300?"success":"danger"}a.d(e,{G:()=>s,e:()=>n})}}]);
//# sourceMappingURL=153.js.map