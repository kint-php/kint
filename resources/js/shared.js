if (typeof window.kintSharedInitialized === 'undefined') {
    window.kintSharedInitialized = 1;

    window.addEventListener('click', function(e) {
        'use strict';

        // add ajax call to contact editor but prevent link default action
        if (e.target.classList.contains('kint-ide-link')) {
            var ajax = new XMLHttpRequest();
            ajax.open('GET', e.target.href);
            ajax.send(null);
            e.preventDefault();
        }
    });
}
