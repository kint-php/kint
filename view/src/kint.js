// ==ClosureCompiler==
// @output_file_name default.js
// @compilation_level ADVANCED_OPTIMIZATIONS
// @js_externs var kintExpandOnLoad
// @js_externs var kint
// ==/ClosureCompiler==

if ( typeof kint === 'undefined' ) {
	var kintExpandOnLoad = kintExpandOnLoad || [];
	var kint = {
		selectText : function( element ) {
			var selection = window.getSelection(),
				range = document.createRange();

			range.selectNodeContents(element);
			selection.removeAllRanges();
			selection.addRange(range);
		},

		addClass : function( ele ) {
			kint.removeClass(ele).className += " kint-minus";
		},

		removeClass : function( ele ) {
			ele.className = ele.className.replace(/(\s|^)kint-minus(\s|$)/, ' ');
			return ele;
		},

		next : function( element ) {
			do {
				element = element.nextElementSibling;
			} while ( element.nodeName.toLowerCase() !== 'dd' );

			return element;
		},

		toggleChildren : function( element ) {
			var parent = kint.next(element),
				nodes = parent.getElementsByClassName('kint-parent'),
				i = nodes.length,
				visible = parent.style.display === 'inline';

			while ( i-- ) {
				kint.toggle(nodes[i], visible);
			}
			kint.toggle(element, visible);
		},

		toggle : function( element, hide ) {
			var parent, plus;
			parent = kint.next(element);
			plus = element.getElementsByClassName('_kint-collapse')[0];

			if ( typeof hide === 'undefined' ) {
				hide = parent.style.display === 'inline';
			}

			if ( hide ) {
				parent.style.display = 'none';
				if ( plus ) kint.removeClass(plus);
			} else {
				parent.style.display = 'inline';
				if ( plus ) kint.addClass(plus);
			}
		},

		toggleAll : function( element ) {
			var elements = document.getElementsByClassName('kint-parent'),
				i = elements.length,
				visible = kint.next(element.parentNode).style.display === 'inline';

			while ( i-- ) {
				kint.toggle(elements[i], visible);
			}

		}
	};

	window.addEventListener("load", function() {
		var parents = document.getElementsByClassName('kint'),
			i = parents.length;

		while ( i-- ) {
			parents[i].addEventListener("click", handle, false);
		}


		parents = document.getElementsByClassName('_kint-collapse');
		i = parents.length;
		while ( i-- ) {
			parents[i].addEventListener("dblclick", function( e ) {
				this.kintTimer = 2;
				kint.toggleAll(this);
				e.stopPropagation();
			}, false);
		}

		for ( var selector in kintExpandOnLoad ) {
			Array.prototype.slice.call(
				document.querySelectorAll('.' + selector + '>dl>dt'),
				0
			).forEach(function( el ) {kint.toggleChildren(el);});
		}
	}, false);


	var handle = function( e ) {
		var target = e.target;
		var nodeName = target.nodeName.toLowerCase();
		var hasClass = function( className ) {
			var r = new RegExp('\\b' + className + '\\b');
			return r.test(target.className);
		};


		if ( nodeName === 'dfn' ) {
			kint.selectText(target);
			e.stopPropagation();
			target = target.parentNode;
		} else if ( nodeName === 'var' ) { // stupid workaround for misc elements
			target = target.parentNode;    // to not stop event from further propagating
			nodeName = target.nodeName.toLowerCase()
		}

		if ( nodeName === 'li' && target.parentNode.className === 'kint-tabs' && target.className !== 'kint-active-tab' ) {
			var lis = target.parentNode.getElementsByTagName('li'),
				l = lis.length,
				index = 0,
				elem = target,
				i, o = 0;

			while ( l-- ) lis[l].className = '';
			target.className = 'kint-active-tab';

			while ( elem = elem.previousSibling ) elem.nodeType === 1 && index++;

			lis = target.parentNode.nextSibling.childNodes;
			l = lis.length;


			for ( i = 0; i < l; i++ ) {
				if ( lis[i].nodeName.toLowerCase() !== 'li' ) continue;

				if ( o++ === index ) {
					lis[i].style.display = 'block';
				} else {
					lis[i].style.display = 'none';
				}
			}
			target.className = 'kint-active-tab';


			return false;
		}


		if ( hasClass('_kint-collapse') ) {
			setTimeout(function() {
				var timer = parseInt(target.kintTimer, 10);
				if ( timer > 0 ) {
					target.kintTimer--;
				} else {
					kint.toggleChildren(target.parentNode); // <dt>
				}
			}, 300);
			e.stopPropagation();
		} else if ( hasClass('kint-parent') ) {
			kint.toggle(target)
		} else if ( hasClass('kint-ide-link') ) { // add ajax call to contact editor but prevent link default action
			e.preventDefault();
			var ajax = new XMLHttpRequest();
			ajax.open('GET', target.href);
			ajax.send(null);
		}
		e.preventDefault();
		return false;
	}
}

// debug purposes only, removed in minified source
function clg( i ) {
	if ( !window.console )return;
	var l = arguments.length, o = 0;
	while ( o < l )console.log(arguments[o++])
}
