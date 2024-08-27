import { dedupeElement, elementIsInDom } from './utils.js';
import Kint from './kint.js';

export default class Plain {
    #window;
    #style;

    constructor(kint) {
        if (!(kint instanceof Kint)) {
            throw new Error('Invalid argument to Plain.constructor()');
        }

        this.#window = kint.window;
        kint.runOnInit(this.#dedupePlainStyle.bind(this));
    }

    #dedupePlainStyle() {
        if (!elementIsInDom(this.#style)) {
            this.#style = this.#window.document.querySelector('style.kint-plain-style');
        }

        if (this.#style) {
            dedupeElement(this.#style);
        }
    }
}
