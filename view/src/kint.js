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
			var parent = kint.next(element),
				plus = element.getElementsByClassName('_kint-collapse')[0];

			if ( typeof hide == 'undefined' ) {
				hide = parent.style.display == 'inline'
			}

			if ( hide ) {
				parent.style.display = 'none';
				kint.removeClass(plus);
			} else {
				parent.style.display = 'inline';
				kint.addClass(plus);
			}
		},

		toggleAll : function( element ) {
			var elements = document.getElementsByClassName('kint-parent'),
				i = elements.length,
				visible = kint.next(element.parentNode).style.display == 'inline';


			while ( i-- ) {
				kint.toggle(elements[i], visible)
			}

		},

		toggleTrace : function( el, className ) {
			var nel = el.parentNode.parentNode.getElementsByClassName(className)[0];
			nel.style.display = nel.style.display == 'block' ? 'none' : 'block';
		}

	};


	window.addEventListener("load", function() {
		var grandparents = document.getElementsByClassName('kint'),
			i = grandparents.length;

		while ( i-- ) {
			grandparents[i].addEventListener("mousedown", function( e ) {
				handle(e)
			}, true);
		}

		function handle( e ) {
			var target = e.target;

			switch ( e.type ) {
				case 'mousedown':
					if ( target.className === '_kint-collapse' ) {
						console.log(target)
						setTimeout(function() {
							var timer = parseInt(e.target.kintTimer, 10);
							if ( timer > 0 ) {
								e.target.kintTimer--;
							} else {
								kint.toggleChildren(e.target.parentNode); // let's hope this is <dt>
							}
						}, 300);
						e.stopPropagation();
					} else {
						if ( target.className === 'kint-parent' ) {
							kint.toggle(target)
						}
					}
					break;
				case 'dblclick':
					if ( target.className === '_kint-collapse' ) {
						target.kintTimer = 2;
						kint.toggleAll(target);
						e.stopPropagation();
					}
					break;
				case 'mouseup':
					if ( target.nodeName === 'var' || target.nodeName === 'dfn' ) {
						kint.selectText(target);
					}
					break;
				case 'click':
					if ( target.className === 'kint-args-parent' ) {
						kint.toggleTrace(target, 'kint-args');
						e.preventDefault();
					} else {
						if ( target.className === 'kint-source-parent' ) {
							kint.toggleTrace(target, 'kint-source');
							e.preventDefault();
						} else {
							if ( target.className === 'kint-object-parent' ) {
								kint.toggleTrace(target, 'kint-object');
								e.preventDefault();
							}
						}
					}
			}
		}


		// add ajax call to contact editor but prevent link default action
		parents = document.getElementsByClassName('kint-ide-link');
		j = parents.length;
		while ( j-- ) {
			parents[j].addEventListener("click", function( e ) {
				e.preventDefault();
				var ajax = new XMLHttpRequest();
				ajax.open('GET', this.href);
				ajax.send(null);
				return false;
			}, false);
		}


	}, false);
}