if ( typeof jQuery !== 'undefined' ) {
	// this code is written in vanilla JS to future-proof it if we want to add other adapters like YUI
	document.body.insertAdjacentHTML(
		'beforeend',
		'<div id="kint-backdrop">Ajax output</div><div id="kint-modular"></div>'
			+ '<div id="kint-modular-placeholder">[Debug output]</div>'
	);

	var kintModular = document.getElementById("kint-modular");
	var kintPlaceholder = document.getElementById("kint-modular-placeholder");
	var kintBackdrop = document.getElementById("kint-backdrop");
	var bodyStyle = window.getComputedStyle(document.body);
	bodyStyle = {bg : bodyStyle.backgroundColor, clr : bodyStyle.color};

	var toggleModular = function( show ) {
		var hide = show ? 'none' : 'block';

		if ( show ) {
			// disregard body styles set by some skins
			document.body.style.backgroundColor = bodyStyle.bg;
			document.body.style.color = bodyStyle.clr;

			show = 'block';
			window.addEventListener('mousedown', kintCloseListener)
		} else {
			show = 'none';
			window.removeEventListener('mousedown', kintCloseListener)
		}

		kintModular.style.display = show;
		kintBackdrop.style.display = show;

		kintPlaceholder.style.display = hide;
	};

	var kintCloseListener = function( e ) {
		var el = e.target;

		// are we clicking on the kint modular?
		for ( ; ; ) {
			if ( !el || el === kintModular ) break;
			el = el.parentNode;
		}

		if ( !el ) toggleModular();

		return false;
	};


	var scriptsInitialized = false;
	jQuery(document)['ajaxComplete'](function( e, xhr, settings ) {
		// todo display called url, post, timestamp
		var data = xhr.getResponseHeader('kint')
			, header
			, returnedScript;

		if ( !data ) return;

		for ( var i = 1; ; i++ ) {
			header = xhr.getResponseHeader('kint' + '-' + i);
			if ( !header ) break;
			data += header;
		}

		kintModular.insertAdjacentHTML('beforeend', decodeURIComponent(data));

		// exec script block from returned html once
		if ( !scriptsInitialized ) {
			returnedScript = kintModular.querySelectorAll(".-kint-js");
			if ( returnedScript.length ) {

				scriptsInitialized = true;

				var script = document.createElement("script");
				script.appendChild(document.createTextNode(returnedScript[0].innerHTML));

				document.body.appendChild(script);
			}
		}

		toggleModular(true);
	});

	kintPlaceholder.addEventListener("click", toggleModular)
}