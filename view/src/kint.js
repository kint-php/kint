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

			kint.removeClass(ele, className).className += (" " + className);
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

		toggleChildren : function( element, hide ) {
			var parent = kint.next(element),
				nodes = parent.getElementsByClassName('kint-parent'),
				i = nodes.length;

			if ( typeof hide === 'undefined' ) {
				hide = parent.style.display === 'inline';
			}

			while ( i-- ) {
				kint.toggle(nodes[i], hide);
			}
			kint.toggle(element, hide);
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

			if ( parent.childNodes.length === 1 ) {
				parent = parent.childNodes[0].childNodes[0]; // reuse var cause I can

				if ( kint.hasClass(parent, 'kint-parent') ) {
					kint.toggle(parent, hide)
				}
			} else if ( kint.currentPlus !== -1 ) {
				kint.fetchVisiblePluses();
			}
		},

		toggleAll : function( element ) {
			var elements = document.getElementsByClassName('kint-parent'),
				i = elements.length,
				visible = kint.next(element.parentNode).style.display === 'inline';

			while ( i-- ) {
				kint.toggle(elements[i], visible);
			}
		},

		toggleTrace : function( target ) {
			var lis, el = target, index = 0;

			kint.removeClass(target.parentNode.getElementsByClassName('kint-active-tab')[0], 'kint-active-tab');
			kint.addClass(target, 'kint-active-tab');

			// take the index of clicked title tab and make the same n-th content tab visible
			while ( el = el.previousSibling ) el.nodeType === 1 && index++;
			lis = target.parentNode.nextSibling.childNodes;
			for ( var i = 0; i < lis.length; i++ ) {
				if ( i === index ) {
					lis[i].style.display = 'block';

					if ( lis[i].childNodes.length === 1 ) {
						el = lis[i].childNodes[0].childNodes[0];

						if ( kint.hasClass(el, 'kint-parent') ) {
							kint.toggle(el, false)
						}
					}
				} else {
					lis[i].style.display = 'none';
				}
			}

			if ( kint.currentPlus !== -1 ) {
				kint.fetchVisiblePluses();
			}
		},

		hasClass : function( target, className ) {
			var r = new RegExp('\\b' + className + '\\b');
			return r.test(target.className);
		},

		fetchVisiblePluses : function() {
			kint.visiblePluses = [];
			Array.prototype.slice.call(document.querySelectorAll('.kint-plus, .kint-tabs>li:not(.kint-active-tab)'), 0)
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
			kint.toggleTrace(target);
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
			return false;
		} else if ( kint.hasClass(target, 'kint-parent') ) {
			kint.toggle(target);
			return false;
		} else if ( kint.hasClass(target, 'kint-ide-link') ) {
			e.preventDefault();
			var ajax = new XMLHttpRequest(); // add ajax call to contact editor but prevent link default action
			ajax.open('GET', target.href);
			ajax.send(null);
			return false;
		}
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
	}, false);

	window.onkeydown = function( e ) {
		var keyCode = e.keyCode;
		var shiftKey = e.shiftKey;
		var i = kint.currentPlus;
		var focusedClass = 'kint-focused';

		var cleanup = function( i ) {
			var prevElement = document.querySelector('.' + focusedClass);
			prevElement && kint.removeClass(prevElement, focusedClass);

			if ( i !== -1 ) {
				var el = kint.visiblePluses[i];
				kint.addClass(el, focusedClass);


				var offsetTop = function( el ) {
					return el.offsetTop + ( el.offsetParent ? offsetTop(el.offsetParent) : 0 );
				};

				var top = offsetTop(el) - (window.innerHeight / 2 );
				window.scrollTo(0, top);
			}

			kint.currentPlus = i;
		};

		var moveCursor = function( up ) {
			// todo make the first VISIBLE plus active

			if ( up ) {
				if ( --i < 0 ) {
					i = kint.visiblePluses.length - 1;
				}
			} else {
				if ( ++i >= kint.visiblePluses.length ) {
					i = 0;
				}
			}

			cleanup(i);
			return false;
		};


		if ( keyCode === 68 ) { // 'd' : toggles navigation on/off
			if ( i === -1 ) {
				kint.fetchVisiblePluses();
				return moveCursor(false);
			} else {
				cleanup(-1);
				return false;
			}
		} else {
			if ( i === -1 ) return;
			if ( keyCode === 9 ) { // TAB : moves up/down depending on shift key
				return moveCursor(shiftKey);
			} else if ( keyCode === 38 ) { // ARROW UP : moves up
				return moveCursor(true);
			} else if ( keyCode === 40 ) { // ARROW DOWN : down
				return moveCursor(false);
			} else {
				if ( i === -1 ) {
					return;
				}
			}
		}


		var kintNode = kint.visiblePluses[i];
		if ( kintNode.nodeName.toLowerCase() === 'li' ) { // we're on a trace tab
			if ( keyCode === 32 || keyCode === 13 ) { // SPACE/ENTER
				kint.toggleTrace(kintNode);
				return moveCursor(true);
			} else if ( keyCode === 39 ) { // arrows
				return moveCursor(false);
			} else if ( keyCode === 37 ) {
				return moveCursor(true);
			}
		}

		kintNode = kintNode.parentNode; // simple dump
		if ( keyCode === 32 || keyCode === 13 ) { // SPACE/ENTER : toggles
			kint.toggle(kintNode);
			return false;
		} else if ( keyCode === 39 || keyCode === 37 ) { // ARROW LEFT/RIGHT : respectively hides/shows and traverses
			var visible = kint.next(kintNode).style.display === 'inline';
			var hide = keyCode === 37;

			if ( visible ) {
				kint.toggleChildren(kintNode, hide); // expand/collapse all children if immediate ones are showing
			} else {
				if ( hide ) { // LEFT
					// traverse to parent and THEN hide
					do {kintNode = kintNode.parentNode} while ( kintNode && kintNode.nodeName.toLowerCase() !== 'dd' );

					if ( kintNode ) {
						kintNode = kintNode.previousElementSibling;

						i = -1;
						var parentPlus = kintNode.querySelector('.kint-plus');
						while ( parentPlus !== kint.visiblePluses[++i] ) {}
						cleanup(i)
					} else { // we are at root
						kintNode = kint.visiblePluses[i].parentNode;
					}
				}
				kint.toggle(kintNode, hide);
			}
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
