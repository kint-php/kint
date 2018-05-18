if (typeof window.kintShared === 'undefined') {
    window.kintShared = (function() {
        'use strict';

        var kintShared = {
            dedupe: function(selectors) {
                selectors.forEach(function(selector) {
                    var found = false;

                    [].forEach.call(document.querySelectorAll(selector), function(elem) {
                        if (found) {
                            elem.parentNode.removeChild(elem);
                        } else {
                            found = true;
                        }
                    });
                });
            },
        };

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

        return kintShared;
    })();
}
