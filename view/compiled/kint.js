(function(){var e=!1;
if("undefined"===typeof kintInitialized){kintInitialized=1;var f=[],g=-1,h=function(a){var b=window.getSelection(),c=document.createRange();c.selectNodeContents(a);b.removeAllRanges();b.addRange(c)},k=function(a){Array.prototype.slice.call(document.querySelectorAll(".kint nav, .kint-tabs>li:not(.kint-active-tab)"),0).forEach(a)},l=function(a,b){if(!a.classList)return e;"undefined"===typeof b&&(b="kint-show");return a.classList.contains(b)},m=function(a,b){"undefined"===typeof b&&(b="kint-show");a.classList.add(b)},
n=function(a,b){"undefined"===typeof b&&(b="kint-show");a.classList.remove(b)},p=function(a){do a=a.nextElementSibling;while("dd"!==a.nodeName.toLowerCase());return a},q=function(a,b){var c=p(a);"undefined"===typeof b&&(b=l(a));b?n(a):m(a);1===c.childNodes.length&&(c=c.childNodes[0].childNodes[0])&&l(c,"kint-parent")&&q(c,b)},s=function(a,b){var c=p(a).getElementsByClassName("kint-parent"),d=c.length;for("undefined"===typeof b&&(b=l(a));d--;)q(c[d],b);q(a,b)},t=function(a){var b=a,c=0;a.parentNode.getElementsByClassName("kint-active-tab")[0].className=
"";for(a.className="kint-active-tab";b=b.previousSibling;)1===b.nodeType&&c++;a=a.parentNode.nextSibling.childNodes;for(var d=0;d<a.length;d++)d===c?(a[d].style.display="block",1===a[d].childNodes.length&&(b=a[d].childNodes[0].childNodes[0],l(b,"kint-parent")&&q(b,e))):a[d].style.display="none"},u=function(a){for(;!(a=a.parentNode,!a||l(a,"kint")););return!!a},v=function(){f=[];k(function(a){(0!==a.offsetWidth||0!==a.offsetHeight)&&f.push(a)})},w=function(a){var b;if(b=window.open())b.document.open(),
b.document.write("<html><head><title>Kint ("+(new Date).toISOString()+')</title><meta charset="utf-8">'+document.getElementsByClassName("-kint-js")[0].outerHTML+document.getElementsByClassName("-kint-css")[0].outerHTML+'</head><body><input style="width: 100%" placeholder="Take some notes!"><div class="kint">'+a.parentNode.outerHTML+"</div></body>"),b.document.close()},x=function(a,b){function c(a){var c=1===b?a.replace(/^#/,""):a;if(isNaN(c))return a.trim().toLocaleLowerCase();c=parseFloat(c);return isNaN(c)?
a.trim():c}var d=a.tBodies[0];[].slice.call(a.tBodies[0].rows).sort(function(a,d){a=c(a.cells[b].textContent);d=c(d.cells[b].textContent);return a<d?-1:a>d?1:0}).forEach(function(a){d.appendChild(a)})},y=function(a){var b=document.querySelector(".kint-focused");b&&n(b,"kint-focused");if(-1!==a){b=f[a];m(b,"kint-focused");var c=function(a){return a.offsetTop+(a.offsetParent?c(a.offsetParent):0)};window.scrollTo(0,c(b)-window.innerHeight/2)}g=a},z=function(a,b){a?0>--b&&(b=f.length-1):++b>=f.length&&
(b=0);y(b);return e};window.addEventListener("click",function(a){var b=a.target,c=b.nodeName.toLowerCase();if(u(b)){if("dfn"===c)h(b),b=b.parentNode;else if("var"===c)b=b.parentNode,c=b.nodeName.toLowerCase();else{if("th"===c)return a.ctrlKey||x(b.parentNode.parentNode.parentNode,b.cellIndex),e;l(b,"access-path")&&h(b)}if("li"===c&&"kint-tabs"===b.parentNode.className)return"kint-active-tab"!==b.className&&(t(b),-1!==g&&v()),e;if("nav"===c)return"footer"===b.parentNode.nodeName.toLowerCase()?(b=b.parentNode,
l(b)?n(b):m(b)):setTimeout(function(){0<parseInt(b.a,10)?b.a--:(s(b.parentNode),-1!==g&&v())},300),a.stopPropagation(),e;if(l(b,"kint-parent"))return q(b),-1!==g&&v(),e;if(l(b,"kint-ide-link"))return a.preventDefault(),a=new XMLHttpRequest,a.open("GET",b.href),a.send(null),e;if(l(b,"kint-popup-trigger")){a=b.parentNode;if("footer"===a.nodeName.toLowerCase())a=a.previousSibling;else for(;a&&!l(a,"kint-parent");)a=a.parentNode;w(a)}else if(l(b,"kint-access-path-trigger")){a=b.parentNode.childNodes;
for(c=0;c<a.length;c++)if(l(a[c],"access-path"))return l(a[c],"kint-show")?n(a[c]):m(a[c]),e}else"pre"===c&&3===a.detail&&h(b)}},e);window.addEventListener("dblclick",function(a){var b=a.target;if(u(b)&&"nav"===b.nodeName.toLowerCase()){b.a=2;for(var c=document.getElementsByClassName("kint-parent"),d=c.length,b=l(b.parentNode);d--;)q(c[d],b);-1!==g&&v();a.stopPropagation()}},e);window.onkeydown=function(a){if(!(a.target!==document.body||a.altKey||a.ctrlKey)){var b=a.keyCode,c=a.shiftKey;a=g;if(68===
b){if(-1===a)return v(),z(e,a);y(-1);return e}if(-1!==a){if(9===b)return z(c,a);if(38===b)return z(!0,a);if(40===b)return z(e,a);c=f[a];if("li"===c.nodeName.toLowerCase()){if(32===b||13===b)return t(c),v(),z(!0,a);if(39===b)return z(e,a);if(37===b)return z(!0,a)}c=c.parentNode;if(32===b||13===b)return q(c),v(),e;if(39===b||37===b){var d=l(c),b=37===b;if(d)s(c,b);else{if(b){do c=c.parentNode;while(c&&"dd"!==c.nodeName.toLowerCase());if(c){c=c.previousElementSibling;a=-1;for(d=c.querySelector("nav");d!==
f[++a];);y(a)}else c=f[a].parentNode}q(c,b)}v();return e}}}};window.addEventListener("load",function(){var a=Array.prototype.slice.call(document.querySelectorAll(".kint-microtime"),0);a.forEach(function(b){var c=parseFloat(b.innerHTML),d=Infinity,r=-Infinity;a.forEach(function(a){a=parseFloat(a.innerHTML);d>a&&(d=a);r<a&&(r=a)});b.style.background="hsl("+Math.round(120*(1-(c-d)/(r-d)))+",60%,70%)"})})};})()
