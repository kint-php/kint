if (typeof window.kintPlain === 'undefined') {
    window.kintPlain = (function() {
        'use strict';

        var kintPlain = {
            initLoad: function() {
                kintPlain.style = window.kintShared.dedupe(
                    'style.kint-plain-style',
                    kintPlain.style
                );
                kintPlain.script = window.kintShared.dedupe(
                    'script.kint-plain-script',
                    kintPlain.script
                );
            },

            style: null,
            script: null,
        };

        return kintPlain;
    })();
}

window.kintShared.runOnce(window.kintPlain.initLoad);
