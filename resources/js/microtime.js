import Kint from './kint.js';

export default class Microtime {
    constructor(kint) {
        if (!(kint instanceof Kint)) {
            throw new Error('Invalid argument to Plain.constructor()');
        }

        // If we do microtime stuff for ajax loads the groups
        // from the runs will conflate and we'll get bad numbers
        kint.runOnLoad(Microtime.#setupMicrotimes.bind(null, kint.window));
    }

    static #setupMicrotimes(window) {
        const sums = {};

        const elements = window.document.querySelectorAll('[data-kint-microtime-group]');
        for (const elem of elements) {
            const el = elem.querySelector('.kint-microtime-lap');

            if (!el) {
                continue;
            }

            const group = elem.getAttribute('data-kint-microtime-group');
            const lap = parseFloat(el.textContent);
            const avg = parseFloat(elem.querySelector('.kint-microtime-avg').textContent);

            sums[group] ??= {
                min: lap,
                max: lap,
                avg: avg,
            };

            if (sums[group].min > lap) {
                sums[group].min = lap;
            }
            if (sums[group].max < lap) {
                sums[group].max = lap;
            }
            sums[group].avg = avg;
        }

        for (const elem of elements) {
            const el = elem.querySelector('.kint-microtime-lap');

            if (!el) {
                continue;
            }

            const lap = parseFloat(el.textContent);
            const group = sums[elem.dataset.kintMicrotimeGroup];

            elem.querySelector('.kint-microtime-avg').textContent = group.avg;

            if (lap === group.min && lap === group.max) {
                continue; // Only one result, no need to color
            }

            elem.classList.add('kint-microtime-js');

            if (lap > group.avg) {
                const ratio = (lap - group.avg) / (group.max - group.avg);
                el.style.background = 'hsl(' + (40 - 40 * ratio) + ', 100%, 65%)';
            } else {
                let ratio = 0;
                if (group.avg !== group.min) {
                    ratio = (group.avg - lap) / (group.avg - group.min);
                }
                el.style.background = 'hsl(' + (40 + 80 * ratio) + ', 100%, 65%)';
            }
        }
    }
}
