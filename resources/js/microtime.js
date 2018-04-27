if (typeof window.kintMicrotimeInitialized === 'undefined') {
    window.kintMicrotimeInitialized = 1;
    window.addEventListener('load', function() {
        'use strict';

        var sums = {};
        var microtimes = Array.prototype.slice.call(
            document.querySelectorAll('[data-kint-microtime-group]'),
            0
        );

        microtimes.forEach(function(el) {
            if (!el.querySelector('.kint-microtime-lap')) {
                return;
            }

            var group = el.getAttribute('data-kint-microtime-group');
            var lap = parseFloat(el.querySelector('.kint-microtime-lap').innerHTML);
            var avg = parseFloat(el.querySelector('.kint-microtime-avg').innerHTML);

            if (typeof sums[group] === 'undefined') {
                sums[group] = {};
            }
            if (typeof sums[group].min === 'undefined' || sums[group].min > lap) {
                sums[group].min = lap;
            }
            if (typeof sums[group].max === 'undefined' || sums[group].max < lap) {
                sums[group].max = lap;
            }
            sums[group].avg = avg;
        });

        microtimes.forEach(function(microtime) {
            var el = microtime.querySelector('.kint-microtime-lap');

            if (el === null) {
                return;
            }

            var value = parseFloat(el.textContent);
            var group = microtime.dataset.kintMicrotimeGroup;
            var avg = sums[group].avg;
            var max = sums[group].max;
            var min = sums[group].min;
            var ratio;

            microtime.querySelector('.kint-microtime-avg').textContent = avg;

            if (value === avg && value === min && value === max) {
                return; // Only one result, no need to color
            }

            if (value > avg) {
                ratio = (value - avg) / (max - avg);
                el.style.background = 'hsl(' + (40 - 40 * ratio) + ', 100%, 65%)';
            } else {
                if (avg === min) {
                    ratio = 0;
                } else {
                    ratio = (avg - value) / (avg - min);
                }
                el.style.background = 'hsl(' + (40 + 80 * ratio) + ', 100%, 65%)';
            }
        });
    });
}
