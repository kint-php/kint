if (typeof window.kintPlain === 'undefined') {
    window.kintPlain = (function() {
        'use strict';

        window.addEventListener('load', function() {
            window.kintShared.dedupe(['style.kint-plain-style', 'script.kint-plain-script']);
        });

        return {};
    })();
}
