import{c as f}from"./createLucideIcon-DWfFhRjK.js";import{r as a}from"./app-B20to3n0.js";/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=[["line",{x1:"10",x2:"14",y1:"2",y2:"2",key:"14vaq8"}],["line",{x1:"12",x2:"15",y1:"14",y2:"11",key:"17fdiu"}],["circle",{cx:"12",cy:"14",r:"8",key:"1e1u0o"}]],w=f("Timer",y);/**
 * @license lucide-react v0.475.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const m=[["path",{d:"M6 9H4.5a2.5 2.5 0 0 1 0-5H6",key:"17hqa7"}],["path",{d:"M18 9h1.5a2.5 2.5 0 0 0 0-5H18",key:"lmptdp"}],["path",{d:"M4 22h16",key:"57wxv0"}],["path",{d:"M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22",key:"1nw9bq"}],["path",{d:"M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22",key:"1np0yb"}],["path",{d:"M18 2H6v7a6 6 0 0 0 12 0V2Z",key:"u46fv3"}]],x=f("Trophy",m);function M(c,r){const[e,p]=a.useState(r),[l,i]=a.useState(r.secondsRemaining),u=a.useRef(0),d=["scheduled","live","paused"].includes(e.status),s=a.useCallback(async()=>{try{const t=await fetch(c,{headers:{Accept:"application/json"},credentials:"same-origin"});if(!t.ok)return;const n=await t.json();u.current=Date.parse(n.serverTime)-Date.now(),p(n)}catch{}},[c]);return a.useEffect(()=>{if(!d)return;const t=setInterval(s,2500);return()=>clearInterval(t)},[d,s]),a.useEffect(()=>{const t=window.Echo;if(!t)return;const n=`auction.${e.id}`,o=t.channel(`presence-${n}`);return["bid.placed","auction.started","auction.paused","auction.resumed","auction.closed","auction.cancelled"].forEach(h=>o.listen(`.${h}`,s)),()=>t.leave(`presence-${n}`)},[e.id,s]),a.useEffect(()=>{const t=()=>{if(e.status!=="live"||!e.endsAt){i(0);return}const o=Date.now()+u.current;i(Math.max(0,Math.round((Date.parse(e.endsAt)-o)/1e3)))};t();const n=setInterval(t,1e3);return()=>clearInterval(n)},[e.status,e.endsAt]),{state:e,remaining:l,refresh:s}}function $(c){const r=Math.floor(c/60),e=c%60;return`${r}:${String(e).padStart(2,"0")}`}export{w as T,x as a,$ as f,M as u};
