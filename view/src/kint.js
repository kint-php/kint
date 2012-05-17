if ( typeof kint === 'undefined' ) {
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
			return ele
		},

		next : function( element ) {
			do {
				element = element.nextElementSibling;
			} while ( element.nodeName.toLowerCase() != 'dd' );

			return element;
		},

		toggleChildren : function( element ) {
			var parent = kint.next(element),
				nodes = parent.getElementsByClassName('kint-parent'),
				i = nodes.length,
				visible = parent.style.display == 'inline';

			while ( i-- ) {
				kint.toggle(nodes[i], visible)
			}
			kint.toggle(element, visible)

		},

		toggle : function( element, hide ) {
			var parent, plus;
			if ( element.href ) { // trace elements
				parent = element.parentNode.parentNode.getElementsByClassName('kint-' + element.href.replace(/[^#]+#/, ''))[0];
				plus = element;
			} else {
				parent = kint.next(element);
				plus = element.getElementsByClassName('_kint-collapse')[0];
			}


			if ( typeof hide == 'undefined' ) {
				hide = parent.style.display == 'inline'
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
				visible = kint.next(element.parentNode).style.display == 'inline';

			while ( i-- ) {
				kint.toggle(elements[i], visible)
			}

		}
	};


	window.addEventListener("load", function() {
		var parents = document.getElementsByClassName('kint'), i = parents.length;
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
	}, false);


	function handle( e ) {
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
		} else if ( nodeName === 'span' || nodeName === 'var' ) { // stupid workaround for misc elements
			target = target.parentNode;                           // to not stop event from further propagating
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
			ajax.open('GET', this.href);
			ajax.send(null);
		}
		e.preventDefault();
		return false;
	}
}

function clg( i ) {
	if ( !window.console )return;
	var l = arguments.length, o = 0;
	while ( o < l )console.log(arguments[o++])
}
