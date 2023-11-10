(self.webpackChunksrc_debugger_debugger_js=self.webpackChunksrc_debugger_debugger_js||[]).push([[100],{9665:function(t){t.exports=function(){"use strict";var t=6e4,e=36e5,n="millisecond",r="second",s="minute",i="hour",a="day",u="week",c="month",o="quarter",l="year",d="date",f="Invalid Date",h=/^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/,$=/\[([^\]]+)]|Y{1,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g,g={name:"en",weekdays:"Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),months:"January_February_March_April_May_June_July_August_September_October_November_December".split("_"),ordinal:function(t){var e=["th","st","nd","rd"],n=t%100;return"["+t+(e[(n-20)%10]||e[n]||e[0])+"]"}},m=function(t,e,n){var r=String(t);return!r||r.length>=e?t:""+Array(e+1-r.length).join(n)+t},_={s:m,z:function(t){var e=-t.utcOffset(),n=Math.abs(e),r=Math.floor(n/60),s=n%60;return(e<=0?"+":"-")+m(r,2,"0")+":"+m(s,2,"0")},m:function t(e,n){if(e.date()<n.date())return-t(n,e);var r=12*(n.year()-e.year())+(n.month()-e.month()),s=e.clone().add(r,c),i=n-s<0,a=e.clone().add(r+(i?-1:1),c);return+(-(r+(n-s)/(i?s-a:a-s))||0)},a:function(t){return t<0?Math.ceil(t)||0:Math.floor(t)},p:function(t){return{M:c,y:l,w:u,d:a,D:d,h:i,m:s,s:r,ms:n,Q:o}[t]||String(t||"").toLowerCase().replace(/s$/,"")},u:function(t){return void 0===t}},v="en",y={};y[v]=g;var M="$isDayjsObject",b=function(t){return t instanceof w||!(!t||!t[M])},D=function t(e,n,r){var s;if(!e)return v;if("string"==typeof e){var i=e.toLowerCase();y[i]&&(s=i),n&&(y[i]=n,s=i);var a=e.split("-");if(!s&&a.length>1)return t(a[0])}else{var u=e.name;y[u]=e,s=u}return!r&&s&&(v=s),s||!r&&v},k=function(t,e){if(b(t))return t.clone();var n="object"==typeof e?e:{};return n.date=t,n.args=arguments,new w(n)},p=_;p.l=D,p.i=b,p.w=function(t,e){return k(t,{locale:e.$L,utc:e.$u,x:e.$x,$offset:e.$offset})};var w=function(){function g(t){this.$L=D(t.locale,null,!0),this.parse(t),this.$x=this.$x||t.x||{},this[M]=!0}var m=g.prototype;return m.parse=function(t){this.$d=function(t){var e=t.date,n=t.utc;if(null===e)return new Date(NaN);if(p.u(e))return new Date;if(e instanceof Date)return new Date(e);if("string"==typeof e&&!/Z$/i.test(e)){var r=e.match(h);if(r){var s=r[2]-1||0,i=(r[7]||"0").substring(0,3);return n?new Date(Date.UTC(r[1],s,r[3]||1,r[4]||0,r[5]||0,r[6]||0,i)):new Date(r[1],s,r[3]||1,r[4]||0,r[5]||0,r[6]||0,i)}}return new Date(e)}(t),this.init()},m.init=function(){var t=this.$d;this.$y=t.getFullYear(),this.$M=t.getMonth(),this.$D=t.getDate(),this.$W=t.getDay(),this.$H=t.getHours(),this.$m=t.getMinutes(),this.$s=t.getSeconds(),this.$ms=t.getMilliseconds()},m.$utils=function(){return p},m.isValid=function(){return!(this.$d.toString()===f)},m.isSame=function(t,e){var n=k(t);return this.startOf(e)<=n&&n<=this.endOf(e)},m.isAfter=function(t,e){return k(t)<this.startOf(e)},m.isBefore=function(t,e){return this.endOf(e)<k(t)},m.$g=function(t,e,n){return p.u(t)?this[e]:this.set(n,t)},m.unix=function(){return Math.floor(this.valueOf()/1e3)},m.valueOf=function(){return this.$d.getTime()},m.startOf=function(t,e){var n=this,o=!!p.u(e)||e,f=p.p(t),h=function(t,e){var r=p.w(n.$u?Date.UTC(n.$y,e,t):new Date(n.$y,e,t),n);return o?r:r.endOf(a)},$=function(t,e){return p.w(n.toDate()[t].apply(n.toDate("s"),(o?[0,0,0,0]:[23,59,59,999]).slice(e)),n)},g=this.$W,m=this.$M,_=this.$D,v="set"+(this.$u?"UTC":"");switch(f){case l:return o?h(1,0):h(31,11);case c:return o?h(1,m):h(0,m+1);case u:var y=this.$locale().weekStart||0,M=(g<y?g+7:g)-y;return h(o?_-M:_+(6-M),m);case a:case d:return $(v+"Hours",0);case i:return $(v+"Minutes",1);case s:return $(v+"Seconds",2);case r:return $(v+"Milliseconds",3);default:return this.clone()}},m.endOf=function(t){return this.startOf(t,!1)},m.$set=function(t,e){var u,o=p.p(t),f="set"+(this.$u?"UTC":""),h=(u={},u[a]=f+"Date",u[d]=f+"Date",u[c]=f+"Month",u[l]=f+"FullYear",u[i]=f+"Hours",u[s]=f+"Minutes",u[r]=f+"Seconds",u[n]=f+"Milliseconds",u)[o],$=o===a?this.$D+(e-this.$W):e;if(o===c||o===l){var g=this.clone().set(d,1);g.$d[h]($),g.init(),this.$d=g.set(d,Math.min(this.$D,g.daysInMonth())).$d}else h&&this.$d[h]($);return this.init(),this},m.set=function(t,e){return this.clone().$set(t,e)},m.get=function(t){return this[p.p(t)]()},m.add=function(n,o){var d,f=this;n=Number(n);var h=p.p(o),$=function(t){var e=k(f);return p.w(e.date(e.date()+Math.round(t*n)),f)};if(h===c)return this.set(c,this.$M+n);if(h===l)return this.set(l,this.$y+n);if(h===a)return $(1);if(h===u)return $(7);var g=(d={},d[s]=t,d[i]=e,d[r]=1e3,d)[h]||1,m=this.$d.getTime()+n*g;return p.w(m,this)},m.subtract=function(t,e){return this.add(-1*t,e)},m.format=function(t){var e=this,n=this.$locale();if(!this.isValid())return n.invalidDate||f;var r=t||"YYYY-MM-DDTHH:mm:ssZ",s=p.z(this),i=this.$H,a=this.$m,u=this.$M,c=n.weekdays,o=n.months,l=n.meridiem,d=function(t,n,s,i){return t&&(t[n]||t(e,r))||s[n].slice(0,i)},h=function(t){return p.s(i%12||12,t,"0")},g=l||function(t,e,n){var r=t<12?"AM":"PM";return n?r.toLowerCase():r};return r.replace($,(function(t,r){return r||function(t){switch(t){case"YY":return String(e.$y).slice(-2);case"YYYY":return p.s(e.$y,4,"0");case"M":return u+1;case"MM":return p.s(u+1,2,"0");case"MMM":return d(n.monthsShort,u,o,3);case"MMMM":return d(o,u);case"D":return e.$D;case"DD":return p.s(e.$D,2,"0");case"d":return String(e.$W);case"dd":return d(n.weekdaysMin,e.$W,c,2);case"ddd":return d(n.weekdaysShort,e.$W,c,3);case"dddd":return c[e.$W];case"H":return String(i);case"HH":return p.s(i,2,"0");case"h":return h(1);case"hh":return h(2);case"a":return g(i,a,!0);case"A":return g(i,a,!1);case"m":return String(a);case"mm":return p.s(a,2,"0");case"s":return String(e.$s);case"ss":return p.s(e.$s,2,"0");case"SSS":return p.s(e.$ms,3,"0");case"Z":return s}return null}(t)||s.replace(":","")}))},m.utcOffset=function(){return 15*-Math.round(this.$d.getTimezoneOffset()/15)},m.diff=function(n,d,f){var h,$=this,g=p.p(d),m=k(n),_=(m.utcOffset()-this.utcOffset())*t,v=this-m,y=function(){return p.m($,m)};switch(g){case l:h=y()/12;break;case c:h=y();break;case o:h=y()/3;break;case u:h=(v-_)/6048e5;break;case a:h=(v-_)/864e5;break;case i:h=v/e;break;case s:h=v/t;break;case r:h=v/1e3;break;default:h=v}return f?h:p.a(h)},m.daysInMonth=function(){return this.endOf(c).$D},m.$locale=function(){return y[this.$L]},m.locale=function(t,e){if(!t)return this.$L;var n=this.clone(),r=D(t,e,!0);return r&&(n.$L=r),n},m.clone=function(){return p.w(this.$d,this)},m.toDate=function(){return new Date(this.valueOf())},m.toJSON=function(){return this.isValid()?this.toISOString():null},m.toISOString=function(){return this.$d.toISOString()},m.toString=function(){return this.$d.toUTCString()},g}(),S=w.prototype;return k.prototype=S,[["$ms",n],["$s",r],["$m",s],["$H",i],["$W",a],["$M",c],["$y",l],["$D",d]].forEach((function(t){S[t[1]]=function(e){return this.$g(e,t[0],t[1])}})),k.extend=function(t,e){return t.$i||(t(e,w,k),t.$i=!0),k},k.locale=D,k.isDayjs=b,k.unix=function(t){return k(1e3*t)},k.en=y[v],k.Ls=y,k.p={},k}()},2100:(t,e,n)=>{"use strict";n.r(e),n.d(e,{default:()=>y});var r=n(1525),s=n(1456),i=n(3630),a=n(672),u=n(9665),c=n(8601),o=n(363),l=(n(4545),n(7297));const d={class:"p-4"},f={class:"table table-striped table-bordered"},h=(0,r._)("thead",{class:"table-dark"},[(0,r._)("tr",null,[(0,r._)("th",null,"\r\n          ID\r\n        "),(0,r.Uk)(),(0,r._)("th",null,"\r\n          See\r\n        "),(0,r.Uk)(),(0,r._)("th",null,"\r\n          IP\r\n        "),(0,r.Uk)(),(0,r._)("th",null,"\r\n          Method\r\n        "),(0,r.Uk)(),(0,r._)("th",null,"\r\n          URL\r\n        "),(0,r.Uk)(),(0,r._)("th",null,"\r\n          Time\r\n        "),(0,r.Uk)(),(0,r._)("th",null,"\r\n          Info\r\n        ")])],-1),$=["onClick"],g=["onClick"],m={key:0,class:"badge bg-danger"},_={style:{"word-break":"break-all"}},v=["href"],y={__name:"Dashboard",setup(t){(0,a.qj)({items:[]});const e=(0,a.iH)([]);function n(t){c.Z.push("/system/"+t)}return(0,r.bv)((async()=>{const t=await o.Z.get("ajax/history");e.value=t.data.data})),(t,c)=>{const o=(0,r.up)("fa-icon");return(0,r.wg)(),(0,r.iD)("div",d,[(0,r._)("table",f,[h,(0,r.Uk)(),(0,r._)("tbody",null,[((0,r.wg)(!0),(0,r.iD)(r.HY,null,(0,r.Ko)(e.value,(t=>{var e,c,d;return(0,r.wg)(),(0,r.iD)("tr",null,[(0,r._)("td",null,[(0,r._)("a",{href:"#",onClick:(0,s.iM)((e=>n(t.id)),["prevent"]),class:""},(0,i.zw)(t.id),9,$)]),(0,r.Uk)(),(0,r._)("td",null,[(0,r._)("button",{class:"btn btn-primary btn-sm",type:"button",onClick:e=>n(t.id)},[(0,r.Wm)(o,{icon:"fa-solid fa-eye"})],8,g)]),(0,r.Uk)(),(0,r._)("td",null,(0,i.zw)(t.ip),1),(0,r.Uk)(),(0,r._)("td",null,[(0,r._)("div",null,(0,i.zw)(t.method),1),(0,r.Uk)(),(0,r._)("div",null,[t.ajax?((0,r.wg)(),(0,r.iD)("span",m,"\r\n              AJAX | API\r\n            ")):(0,r.kq)("",!0)])]),(0,r.Uk)(),(0,r._)("td",_,[(0,r._)("a",{href:t.url,target:"_blank",class:"link-secondary"},[(0,r.Uk)((0,i.zw)(t.url)+" ",1),(0,r.Wm)(o,{class:"small",icon:"fa-solid fa-external-link"})],8,v)]),(0,r.Uk)(),(0,r._)("td",null,(0,i.zw)((d=t.time,u.unix(d).format("YYYY-MM-DD HH:mm:ssZ"))),1),(0,r.Uk)(),(0,r._)("td",null,[(0,r._)("span",{class:(0,i.C_)(["badge",`bg-${(0,a.SU)(l.e)((null===(e=t.response)||void 0===e?void 0:e.status)||0)}`])},(0,i.zw)(null===(c=t.response)||void 0===c?void 0:c.status),3)])])})),256))])])])}}}},7297:(t,e,n)=>{"use strict";function r(t,e){return t>2*e?"danger":t>1.5*e?"warning":t<e/2?"success":"info"}function s(t){return t>=300&&t<400?"info":t>=400&&t<500?"warning":t>=200&&t<300?"success":"danger"}n.d(e,{G:()=>r,e:()=>s})}}]);
//# sourceMappingURL=100.js.map