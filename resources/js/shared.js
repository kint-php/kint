if (typeof window.kintShared === 'undefined') {
    window.kintShared = (function() {
        'use strict';

        var kintShared = {
            dedupe: function(selector, keep) {
                [].forEach.call(document.querySelectorAll(selector), function(elem) {
                    if (!keep || !keep.ownerDocument.contains(keep)) {
                        keep = elem;
                    }

                    if (elem !== keep) {
                        elem.parentNode.removeChild(elem);
                    }
                });

                return keep;
            },

            runOnce: function(cb) {
                if (document.readyState === 'complete') {
                    cb();
                } else {
                    window.addEventListener('load', cb);
                }
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
