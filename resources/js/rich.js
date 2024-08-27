import { dedupeElement, elementIsInDom, selectText, offsetTop } from './utils.js';
import Kint from './kint.js';
import Search from './search.js';
import Table from './table.js';

export default class Rich {
    #kint;
    #style;
    #folder;

    constructor(kint) {
        if (!(kint instanceof Kint)) {
            throw new Error('Invalid argument to Rich.constructor()');
        }

        this.#kint = kint;
        this.#kint.runOnInit(this.#dedupeRichStyleAndSetupFolder.bind(this));

        const ki = new KeyInput(this, kint);
        new MouseInput(this, kint.window, ki);
    }

    #dedupeRichStyleAndSetupFolder() {
        const document = this.#kint.window.document;

        if (!elementIsInDom(this.#style)) {
            this.#style = document.querySelector('style.kint-rich-style');
        }

        if (this.#style) {
            dedupeElement(this.#style);
        }

        if (!elementIsInDom(this.#folder)) {
            this.#folder = document.querySelector('.kint-rich.kint-folder');
        }

        if (this.#folder) {
            dedupeElement(this.#folder);

            const container = this.#folder.querySelector('dd.kint-foldout');

            for (const elem of document.querySelectorAll('.kint-rich.kint-file')) {
                if (elem.parentNode !== container) {
                    container.appendChild(elem);
                }
            }

            this.#folder.classList.add('kint-show');
        }
    }

    get folder() {
        if (!elementIsInDom(this.#folder)) {
            this.#folder = this.#kint.window.document.querySelector('.kint-rich.kint-folder');

            if (this.#folder) {
                dedupeElement(this.#folder);
            }
        }
        return this.#folder;
    }

    isFolderOpen() {
        const foldout = this.#folder?.querySelector('dd.kint-foldout');

        if (!foldout) {
            return undefined;
        }

        return foldout.previousSibling.classList.contains('kint-show');
    }

    static getChildContainer(parent) {
        let element = parent.nextElementSibling;
        while (element && !element.matches('dd')) {
            element = element.nextElementSibling;
        }

        return element;
    }

    static toggle(parent, show) {
        const childContainer = Rich.getChildContainer(parent);

        if (!childContainer) {
            return;
        }

        show = parent.classList.toggle('kint-show', show);
        Rich.#toggleCascade(childContainer, show);
    }

    static switchTab(tab) {
        tab.parentNode
            .getElementsByClassName('kint-active-tab')[0]
            .classList.remove('kint-active-tab');
        tab.classList.add('kint-active-tab');

        // take the index of clicked title tab and make the same n-th content tab visible
        let el = tab;
        let index = 0;
        while ((el = el.previousElementSibling)) {
            index++;
        }

        const tabContents = tab.parentNode.nextSibling.children;
        for (let i = tabContents.length; i--; ) {
            if (i === index) {
                tabContents[i].classList.add('kint-show');
                Rich.#toggleCascade(tabContents[i], true);
            } else {
                tabContents[i].classList.remove('kint-show');
            }
        }
    }

    static toggleChildren(parent, show) {
        const childContainer = Rich.getChildContainer(parent);

        if (!childContainer) {
            return;
        }

        if (show === undefined) {
            show = parent.classList.contains('kint-show');
        }

        const nodes = childContainer.getElementsByClassName('kint-parent');
        for (const node of nodes) {
            node.classList.toggle('kint-show', show);
        }
    }

    static toggleAccessPath(parent, show) {
        const ap = parent.querySelector('.access-path');
        if (ap?.classList.toggle('kint-show', show)) {
            selectText(ap);
        }
    }

    static #toggleCascade(childContainer, show) {
        if (
            childContainer.children.length === 2 &&
            childContainer.lastElementChild.matches('ul.kint-tab-contents')
        ) {
            for (const li of childContainer.lastElementChild.children) {
                if (li.matches('li.kint-show')) {
                    childContainer = li;
                }
            }
        }

        if (
            childContainer.children.length === 1 &&
            childContainer.firstElementChild.matches('dl')
        ) {
            const parent = childContainer?.firstElementChild?.firstElementChild;

            // Parent is checked for a class list in case of empty <pre>
            if (parent?.classList?.contains('kint-parent')) {
                Rich.toggle(parent, show);
            }
        }
    }
}

class MouseInput {
    #rich;
    #keyInput;
    #window;
    #lastClickTimer = null;
    #lastClickTarget = null;
    #lastClickCount = 0;

    constructor(rich, window, keyInput) {
        this.#rich = rich;
        this.#keyInput = keyInput;
        this.#window = window;
        this.#window.addEventListener('click', this.#clickHandler.bind(this), true);
    }

    #renewClickTimeout() {
        clearTimeout(this.#lastClickTimer);
        this.#lastClickTimer = setTimeout(this.#resetClickTimeout.bind(this), 250);
    }

    #resetClickTimeout() {
        clearTimeout(this.#lastClickTimer);
        this.#lastClickTimer = null;
        this.#lastClickTarget = null;
        this.#lastClickCount = 0;
    }

    #handleMultiClicks() {
        const target = this.#lastClickTarget;

        if (!target.matches('.kint-parent > nav')) {
            return;
        }

        const parent = target.parentNode;

        if (this.#lastClickCount === 1) {
            Rich.toggleChildren(parent);
            this.#keyInput.onTreeChanged();

            this.#renewClickTimeout();
            this.#lastClickCount = 2;
        } else if (this.#lastClickCount === 2) {
            this.#resetClickTimeout();

            const show = parent.classList.contains('kint-show');

            const foldout = this.#rich.folder?.querySelector('.kint-parent');
            const nodes = this.#window.document.getElementsByClassName('kint-parent');
            for (const node of nodes) {
                if (node !== foldout) {
                    node.classList.toggle('kint-show', show);
                }
            }

            this.#keyInput.onTreeChanged();
            // When unfolding the entire page you frequently end up somewhere
            // else. By scrolling to the thing you opened it's much easier to
            // see where you are
            this.#keyInput.scrollToFocus();
        }

        return;
    }

    #clickHandler(e) {
        // Double/triple click to open children/all
        if (this.#lastClickCount) {
            this.#handleMultiClicks();
            return;
        }

        const target = e.target;

        // Ignore clicks outside kint
        if (!target.closest('.kint-rich')) {
            return;
        }

        // auto-select name of variable
        if (target.tagName === 'DFN') {
            selectText(target);
        }

        // Sort tables
        if (target.tagName === 'TH') {
            if (!e.ctrlKey) {
                Table.sort(target.closest('table'), target.cellIndex);
            }
            return;
        }

        const parent = target.closest('.kint-parent');

        if (target.tagName === 'LI' && target.parentNode.className === 'kint-tabs') {
            // switch tabs
            if (target.className !== 'kint-active-tab') {
                const cursor = target.closest('dl')?.querySelector('.kint-parent > nav') ?? target;
                this.#keyInput.setCursor(cursor);
                Rich.switchTab(target);
                this.#keyInput.onTreeChanged();
            }

            return;
        }

        if (target.tagName === 'NAV') {
            // handle clicks on the nav
            if (target.parentNode.tagName === 'FOOTER') {
                this.#keyInput.setCursor(target);
                target.parentNode.classList.toggle('kint-show');
            } else if (parent) {
                // ensure double/triple click has different behaviour, see above
                Rich.toggle(parent);
                this.#keyInput.setCursor(target);
                this.#keyInput.onTreeChanged();
                this.#renewClickTimeout();
                this.#lastClickCount = 1;
                this.#lastClickTarget = target;
            }
        } else if (target.classList.contains('kint-access-path-trigger')) {
            // Access path
            const bar = target.closest('.kint-rich dt');
            if (bar) {
                Rich.toggleAccessPath(bar);
            }
        } else if (target.classList.contains('kint-search-trigger')) {
            // Search box
            if (parent) {
                Search.toggleSearchBox(parent);
            }
        } else if (target.classList.contains('kint-search')) {
            // Do nothing if you click the search input
        } else if (target.tagName === 'PRE' && e.detail === 3) {
            // Triple click pre to select it all
            selectText(target);
        } else if (target.closest('.kint-source') && e.detail === 3) {
            // Tripleclick source code to select it all
            selectText(target.closest('.kint-source'));
        } else if (target.classList.contains('access-path')) {
            // Select the access path
            selectText(target);
        } else if (target.tagName !== 'A') {
            // If it's not a link at this point, we're probably clicking the bar, so toggle it
            if (parent) {
                Rich.toggle(parent);
                this.#keyInput.setCursor(parent.querySelector('nav'));
                this.#keyInput.onTreeChanged();
            }
        }
    }
}

const key_a = 65;
const key_d = 68;
const key_h = 72;
const key_j = 74;
const key_k = 75;
const key_l = 76;
const key_s = 83;
const key_tab = 9;
const key_enter = 13;
const key_esc = 27;
const key_space = 32;
const key_left = 37;
const key_up = 38;
const key_right = 39;
const key_down = 40;

class KeyInput {
    #targets = []; // all visible toggle navs
    #target = 0; // currently selected nav
    #active = false;
    #rich;
    #window;

    constructor(rich, kint) {
        this.#rich = rich;
        this.#window = kint.window;

        this.#window.addEventListener('keydown', this.#handlePress.bind(this), true);

        // Recalc tree on init so you don't have to leave
        // keyboard control to fix the target list
        kint.runOnInit(this.onTreeChanged.bind(this));
    }

    scrollToFocus() {
        const el = this.#targets[this.#target];
        if (!el) {
            return;
        }

        const folder = this.#rich.folder;
        if (el === folder?.querySelector('.kint-parent > nav')) {
            return;
        }

        const top = offsetTop(el);
        if (this.#rich.isFolderOpen()) {
            const container = folder.querySelector('dd.kint-foldout');
            container.scrollTo(0, top - container.clientHeight / 2);
        } else {
            this.#window.scrollTo(0, top - this.#window.innerHeight / 2);
        }
    }

    onTreeChanged() {
        const selected = this.#targets[this.#target];

        this.#targets = [];

        const folder = this.#rich.folder;
        const folderNav = folder?.querySelector('.kint-parent > nav');

        let container = this.#window.document;
        if (this.#rich.isFolderOpen()) {
            container = folder;
            this.#targets.push(folderNav);
        }

        for (const el of container.querySelectorAll(
            '.kint-rich nav, .kint-tabs>li:not(.kint-active-tab)'
        )) {
            // Don't add hidden targets (Inside tabs, inside folder)
            //
            // In my tests using selectors instead of offsetParent makes this
            // process go much faster, but the reflow still has to happen and
            // all you're doing is pushing it further down the road. Using
            // offsetParent forces the reflow to happen here for the first time
            // but that's ok because it has to happen anyway (And also for
            // scrollToFocus) so you might as well get it over with now and
            // simplify the code.
            if (el.offsetParent !== null && el !== folderNav) {
                this.#targets.push(el);
            }
        }

        if (folderNav && !this.#rich.isFolderOpen()) {
            this.#targets.push(folderNav);
        }

        if (this.#targets.length === 0) {
            this.#active = false;
            this.#onCursorChanged();
            return;
        }

        if (selected && this.#targets.indexOf(selected) !== -1) {
            this.#target = this.#targets.indexOf(selected);
        } else {
            // We leave the index as is if we can't find the element any more
            // because at this point we're probably looking at a tab and we're
            // fine with it switching to a neighbor when activated
            this.#onCursorChanged();
        }
    }

    setCursor(target) {
        // Refuse to set cursor outside of folder if folder is open
        if (this.#rich.isFolderOpen() && !this.#rich.folder.contains(target)) {
            return false;
        }

        let i = this.#targets.indexOf(target);

        // If we're still loading, refresh the tree to ensure the cursor is set
        if (i === -1 && this.#window.document.readyState !== 'complete') {
            this.onTreeChanged();
            i = this.#targets.indexOf(target);
        }

        if (i !== -1) {
            if (i !== this.#target) {
                this.#target = i;
                this.#onCursorChanged();
                return true;
            } else {
                this.#targets[i]?.classList.remove('kint-weak-focus');
            }
        }

        return false;
    }

    #moveCursor(diff) {
        if (this.#targets.length === 0) {
            this.#target = 0;
            return null;
        }

        this.#target += diff;

        while (this.#target < 0) {
            this.#target += this.#targets.length;
        }
        while (this.#target >= this.#targets.length) {
            this.#target -= this.#targets.length;
        }

        this.#onCursorChanged();

        return this.#target;
    }

    #onCursorChanged() {
        const prevElement = this.#window.document.querySelector('.kint-focused');
        if (prevElement) {
            prevElement.classList.remove('kint-focused');
            prevElement.classList.remove('kint-weak-focus');
        }

        if (this.#active) {
            this.#targets[this.#target]?.classList.add('kint-focused');
        }
    }

    #handlePress(e) {
        if (this.#active && e.keyCode === key_esc && e.target.matches('.kint-search')) {
            e.target.blur();
            this.#onCursorChanged();
            return;
        }

        // do nothing if alt/ctrl key is pressed or if we're actually typing somewhere
        if (e.target !== this.#window.document.body || e.altKey || e.ctrlKey) {
            return;
        }

        // Activation logic
        if (e.keyCode === key_d) {
            // Toggles navigation on/off
            if (this.#active) {
                this.#active = false;
            } else {
                this.#active = true;
                this.onTreeChanged();

                if (this.#targets.length === 0) {
                    this.#active = false;
                    return;
                }

                this.scrollToFocus();
            }

            this.#onCursorChanged();
            e.preventDefault();
            return;
        } else if (e.keyCode === key_esc) {
            if (this.#active) {
                this.#active = false;
                this.#onCursorChanged();
                e.preventDefault();
            }
            return;
        } else if (!this.#active) {
            return;
        }

        e.preventDefault();

        const target = this.#targets[this.#target];

        // If something detaches all the nodes we need to regenerate the tree
        if (!elementIsInDom(target)) {
            this.onTreeChanged();
        }

        // Basic movement logic
        if ([key_tab, key_up, key_k, key_down, key_j].includes(e.keyCode)) {
            if (e.keyCode === key_tab) {
                // Moves up/down depending on shift key
                this.#moveCursor(e.shiftKey ? -1 : 1);
            } else if (e.keyCode === key_up || e.keyCode === key_k) {
                // Moves up
                this.#moveCursor(-1);
            } else if (e.keyCode === key_down || e.keyCode === key_j) {
                // Moves down
                this.#moveCursor(1);
            }

            this.scrollToFocus();
            return;
        }

        // Tab logic
        if (
            target.tagName === 'LI' &&
            [key_space, key_enter, key_right, key_l, key_left, key_h].includes(e.keyCode)
        ) {
            // When we're on a tab
            if (e.keyCode === key_space || e.keyCode === key_enter) {
                // Toggle to it
                Rich.switchTab(target);
                this.onTreeChanged();
            } else if (e.keyCode === key_right || e.keyCode === key_l) {
                // Move right
                this.#moveCursor(1);
            } else if (e.keyCode === key_left || e.keyCode === key_h) {
                // Move left
                this.#moveCursor(-1);
            }

            this.scrollToFocus();
            return;
        }

        const parent = target.closest('.kint-parent');
        if (!parent) {
            return;
        }

        if (e.keyCode === key_a) {
            // Toggles access path on/off
            Rich.toggleAccessPath(parent);
            return;
        }

        if (e.keyCode === key_s) {
            // Enables search
            const topParent = parent
                .closest('.kint-rich > dl')
                ?.querySelector('.kint-search')
                ?.closest('.kint-parent');

            if (topParent) {
                target.classList.add('kint-weak-focus');
                Search.toggleSearchBox(topParent, true);
                return;
            }
        }

        if (target.parentNode.tagName === 'FOOTER' && target.closest('.kint-rich')) {
            // Minitrace needs special class handling
            if (e.keyCode === key_space || e.keyCode === key_enter) {
                target.parentNode.classList.toggle('kint-show');
            } else if (e.keyCode === key_left || e.keyCode === key_h) {
                target.parentNode.classList.remove('kint-show');
            } else if (e.keyCode === key_right || e.keyCode === key_l) {
                target.parentNode.classList.add('kint-show');
            }
            return;
        }

        if (e.keyCode === key_space || e.keyCode === key_enter) {
            // Toggle for normal nav
            Rich.toggle(parent);
            this.onTreeChanged();
            return;
        }

        if ([key_right, key_l, key_left, key_h].includes(e.keyCode)) {
            // Left hides, right opens
            const show = e.keyCode === key_right || e.keyCode === key_l;
            const isOpen = parent.classList.contains('kint-show');

            if (show) {
                // If the target is open
                if (isOpen) {
                    // Expand/collapse all children first
                    Rich.toggleChildren(parent, show);
                }
                // Otherwise just expand the current one
                Rich.toggle(parent, show);
                this.onTreeChanged();
                return;
            } else {
                if (isOpen) {
                    // Close the target and all children
                    Rich.toggleChildren(parent, show);
                    Rich.toggle(parent, show);
                    this.onTreeChanged();
                    return;
                } else {
                    // If it's already closed find the next highest parent
                    const highTarget = parent
                        .closest('.kint-rich .kint-parent ~ dd')
                        ?.parentNode.querySelector('.kint-parent > nav');
                    if (highTarget) {
                        this.setCursor(highTarget);
                        this.scrollToFocus();
                    }
                    return;
                }
            }
        }
    }
}
