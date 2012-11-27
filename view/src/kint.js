// ==ClosureCompiler==
// @output_file_name default.js
// @compilation_level ADVANCED_OPTIMIZATIONS
// @js_externs var kintExpandOnLoad
// @js_externs var kint
// ==/ClosureCompiler==

if ( typeof kint === 'undefined' ) {
	if ( typeof kintExpandOnLoad === 'undefined' ) {
		kintExpandOnLoad = {};
	}


	kint = {
		visiblePluses : [],
		currentPlus   : -1,

		selectText : function( element ) {
			var selection = window.getSelection(),
				range = document.createRange();

			range.selectNodeContents(element);
			selection.removeAllRanges();
			selection.addRange(range);
		},

		addClass : function( ele, className ) {
			if ( typeof className === 'undefined' ) {
				className = 'kint-minus';
			}

			kint.removeClass(ele).className += (" " + className);
		},

		removeClass : function( ele, className ) {
			if ( typeof className === 'undefined' ) {
				className = 'kint-minus';
			}

			ele.className = ele.className.replace(
				new RegExp('(\\s|^)' + className + '(\\s|$)'), ' '
			);
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

			kint.fetchVisiblePluses();
		},

		toggleAll : function( element ) {
			var elements = document.getElementsByClassName('kint-parent'),
				i = elements.length,
				visible = kint.next(element.parentNode).style.display === 'inline';

			while ( i-- ) {
				kint.toggle(elements[i], visible);
			}
		},
		hasClass  : function( target, className ) {
			var r = new RegExp('\\b' + className + '\\b');
			return r.test(target.className);
		},

		fetchVisiblePluses : function() {
			kint.visiblePluses = [];
			Array.prototype.slice.call(document.querySelectorAll('.kint-plus'), 0)
				.forEach(
				function( el ) {
					if ( el.offsetWidth !== 0 || el.offsetHeight !== 0 ) {
						kint.visiblePluses.push(el)
					}
				}
			);
		}
	};

	window.addEventListener("click", function( e ) {
		var target = e.target;

		var nodeName = target.nodeName.toLowerCase();

		if ( nodeName === 'dfn' ) {
			kint.selectText(target);
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


		if ( kint.hasClass(target, '_kint-collapse') ) {
			setTimeout(function() {
				var timer = parseInt(target.kintTimer, 10);
				if ( timer > 0 ) {
					target.kintTimer--;
				} else {
					kint.toggleChildren(target.parentNode); // <dt>
				}
			}, 300);
			e.stopPropagation();
		} else if ( kint.hasClass(target, 'kint-parent') ) {
			kint.toggle(target)
		} else if ( kint.hasClass(target, 'kint-ide-link') ) { // add ajax call to contact editor but prevent link default action
			e.preventDefault();
			var ajax = new XMLHttpRequest();
			ajax.open('GET', target.href);
			ajax.send(null);
		}
		e.preventDefault();
		return false;
	}, false);

	window.addEventListener("dblclick", function( e ) {
		var target = e.target;
		if ( kint.hasClass(target, '_kint-collapse') ) {
			target.kintTimer = 2;
			kint.toggleAll(target);
			e.stopPropagation();
		}
	}, false);

	window.addEventListener("load", function( e ) {
		for ( var selector in kintExpandOnLoad ) {
			Array.prototype.slice.call(document.querySelectorAll('.' + selector + '>dl>dt'), 0)
				.forEach(
				function( el ) {
					kint.toggleChildren(el);
				}
			);
		}

		kint.fetchVisiblePluses();
	}, false);

	window.onkeydown = function( e ) {
		var keyCode = e.keyCode;
		var shiftKey = e.shiftKey;
		var i = kint.currentPlus;
		var focusedClass = 'kint-focused';


		if ( keyCode === 68 ) { // 'd'
			var prevElemet = document.querySelector('.' + focusedClass);
			if ( prevElemet ) {
				kint.removeClass(prevElemet, focusedClass)
			}

			if ( shiftKey ) {
				if ( --i < 0 ) {
					i = kint.visiblePluses.length - 1;
				}
			} else {
				if ( ++i >= kint.visiblePluses.length ) {
					i = 0;
				}
			}
			kint.addClass(kint.visiblePluses[i], focusedClass);

			// only scroll to view if it's not visible to not be annoying
			var node = kint.visiblePluses[i];
			var top = 0;
			var scroll = 0;
			if ( node.offsetParent ) {
				do {
					top += node.offsetTop;
					scroll += node.offsetParent ? node.offsetParent.scrollTop : 0;
				} while ( node = node.offsetParent );
			}

			if ( document.elementFromPoint(kint.visiblePluses[i].offsetLeft, top - scroll) !== kint.visiblePluses[i] ) {
				kint.visiblePluses[i].scrollIntoView();
			}
			kint.currentPlus = i;
			return false;

		}

		if ( i !== -1 && (keyCode === 32 || keyCode === 13) ) { // space or enter
			kint.toggle(kint.visiblePluses[i].parentNode);
			return false;
		}
	};
}

// debug purposes only, removed in minified source
function clg( i ) {
	if ( !window.console )return;
	var l = arguments.length, o = 0;
	while ( o < l )console.log(arguments[o++])
}
