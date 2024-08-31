import Kint from './kint.js';

export default class Microtime {
    #window;

    constructor(kint) {
        if (!(kint instanceof Kint)) {
            throw new Error('Invalid argument to Microtime.constructor()');
        }

        this.#window = kint.window;

        kint.runOnInit(this.#setupMicrotimes.bind(this));
    }

    #setupMicrotimes() {
        const sums = {};

        const elements = this.#window.document.querySelectorAll('[data-kint-microtime-group]');
        for (const elem of elements) {
            const el = elem.querySelector('.kint-microtime-lap');

            if (!el) {
                continue;
            }

            const group = elem.dataset.kintMicrotimeGroup;
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
