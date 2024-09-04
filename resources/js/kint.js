import { buildClassSelector } from './utils.js';
import Rich from './rich.js';
import Plain from './plain.js';
import Microtime from './microtime.js';

const constructor_key = Symbol();

export default class Kint {
    static #instance = null;

    #window;
    #inits = [];
    #scriptSelectors = new Set();

    static init(window) {
        Kint.#instance ??= new Kint(window, constructor_key);
        Kint.#instance.#removeThisScript();
        Kint.#instance.runOnLoad(Kint.#dispatchInits);

        return Kint.#instance;
    }

    get window() {
        return this.#window;
    }

    constructor(window, key) {
        if (constructor_key !== key) {
            throw new Error('Kint constructor is private. Use Kint.init()');
        }

        if (!(window instanceof Window)) {
            throw new Error('Invalid argument to Kint.init()');
        }

        this.#window = window;
        this.runOnInit(this.#removeAllScripts.bind(this));

        // Do we want to store submodules? That would be a circular reference...
        new Plain(this);
        new Rich(this);
        new Microtime(this);
    }

    runOnLoad(cb) {
        if (this.#window.document.readyState === 'complete') {
            try {
                cb();
            } catch {
                // Ignore failures
            }
        } else {
            this.#window.addEventListener('load', cb);
        }
    }

    runOnInit(cb) {
        this.#inits.push(cb);
    }

    #removeThisScript() {
        if (this.#window.document.currentScript) {
            this.#scriptSelectors.add(buildClassSelector(window.document.currentScript));
            window.document.currentScript.remove();
        }
    }

    #removeAllScripts() {
        for (const selector of this.#scriptSelectors.keys()) {
            for (const elem of this.#window.document.querySelectorAll(selector)) {
                elem.remove();
            }
        }
    }

    static #dispatchInits() {
        for (const init of Kint.#instance.#inits) {
            init();
        }
    }
}
