import { dedupeElement, elementIsInDom, selectText, offsetTop } from './utils.js';
import Kint from './kint.js';
import Search from './search.js';
import Table from './table.js';

// Terminology:
// Dump:            .kint-rich
// Root:            .kint-rich > dl
// Bar:             .kint-rich dt
// Parent:          .kint-rich dt.kint-parent
// childContainer:  .kint-rich dt.kint-parent ~ dd
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

        if (document.querySelector('.kint-rich.kint-file')) {
            this.setupFolder(document);

            const container = this.#folder.querySelector('dd.kint-foldout');

            const files = Array.from(document.querySelectorAll('.kint-rich.kint-file'));
            for (const file of files) {
                if (file.parentNode !== container) {
                    container.appendChild(file);
                }
            }

            this.#folder.classList.add('kint-show');
        }
    }

    addToFolder(target) {
        const dump = target.closest('.kint-rich');

        if (!dump) {
            throw new Error('Bad addToFolder');
        }

        const document = this.#kint.window.document;

        this.setupFolder(document);

        if (this.folder.contains(target)) {
            throw new Error('Bad addToFolder');
        }

        const container = this.#folder.querySelector('dd.kint-foldout');
        const parent = target.closest('.kint-parent, .kint-rich');
        const triggers = Array.from(dump.querySelectorAll('.kint-folder-trigger'));

        if (dump === parent || dump.querySelectorAll('.kint-rich > dl').length === 1) {
            for (const trigger of triggers) {
                trigger.remove();
            }
            dump.classList.add('kint-file');
            container.insertBefore(dump, container.firstChild);
        } else {
            const wrap = document.createElement('div');
            wrap.classList.add('kint-rich');
            wrap.classList.add('kint-file');
            wrap.appendChild(parent.closest('.kint-rich > dl'));

            const footer = dump.lastElementChild;
            if (footer.matches('.kint-rich > footer')) {
                wrap.appendChild(footer.cloneNode(true));
            }

            for (const trigger of triggers) {
                trigger.remove();
            }

            container.insertBefore(wrap, container.firstChild);
        }

        Rich.toggle(this.#folder.querySelector('.kint-parent'), true);
    }

    setupFolder(document) {
        if (!this.#folder) {
            const template = document.createElement('template');
            template.innerHTML =
                '<div class="kint-rich kint-folder"><dl><dt class="kint-parent"><nav></nav>Kint</dt><dd class="kint-foldout"></dd></dl></div>';
            this.#folder = template.content.firstChild;
            document.body.appendChild(this.#folder);
        } else if (!elementIsInDom(this.#folder)) {
            this.#folder = document.querySelector('.kint-rich.kint-folder');
        }
    }

    get folder() {
        if (!elementIsInDom(this.#folder)) {
            this.#folder = this.#kint.window.document.querySelector('.kint-rich.kint-folder');
        }

        if (this.#folder) {
            dedupeElement(this.#folder);
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

        const nodes = Array.from(childContainer.getElementsByClassName('kint-parent'));
        for (const node of nodes) {
            node.classList.toggle('kint-show', show);
        }
    }

    static toggleAccessPath(bar, show) {
        const ap = bar.querySelector('.access-path');
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
            const parent = childContainer.firstElementChild.firstElementChild;

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
            const nodes = Array.from(this.#window.document.getElementsByClassName('kint-parent'));
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

        if (target.tagName === 'LI' && target.parentNode.className === 'kint-tabs') {
            // switch tabs
            if (target.className !== 'kint-active-tab') {
                const cursor = target.closest('dl')?.querySelector('.kint-parent > nav') ?? target;
                Rich.switchTab(target);
                this.#keyInput.onTreeChanged();
                this.#keyInput.setCursor(cursor);
            }

            return;
        }

        const bar = target.closest('dt');

        if (target.tagName === 'NAV') {
            if (target.parentNode.tagName === 'FOOTER') {
                // handle clicks on the nav
                this.#keyInput.setCursor(target);
                target.parentNode.classList.toggle('kint-show');
            } else if (bar?.classList.contains('kint-parent')) {
                // ensure double/triple click has different behaviour, see #handleMultiClicks
                Rich.toggle(bar);
                this.#keyInput.onTreeChanged();
                this.#keyInput.setCursor(target);
                this.#renewClickTimeout();
                this.#lastClickCount = 1;
                this.#lastClickTarget = target;
            }
        } else if (target.classList.contains('kint-access-path-trigger')) {
            // Access path
            if (bar) {
                Rich.toggleAccessPath(bar);
            }
        } else if (target.classList.contains('kint-search-trigger')) {
            // Search box
            if (bar?.matches('.kint-rich > dl > dt.kint-parent')) {
                Search.toggleSearchBox(bar);
            }
        } else if (target.classList.contains('kint-folder-trigger')) {
            // Folder trigger
            if (bar?.matches('.kint-rich > dl > dt.kint-parent')) {
                this.#rich.addToFolder(target);
                this.#keyInput.onTreeChanged();
                this.#keyInput.setCursor(bar.querySelector('nav'));
                this.#keyInput.scrollToFocus();
            } else if (target.parentNode.tagName === 'FOOTER') {
                const firstNav = target
                    .closest('.kint-rich')
                    .querySelector('.kint-parent > nav, .kint-rich > footer > nav');
                this.#rich.addToFolder(target);
                this.#keyInput.onTreeChanged();
                this.#keyInput.setCursor(firstNav);
                this.#keyInput.scrollToFocus();
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
            if (bar?.classList.contains('kint-parent')) {
                Rich.toggle(bar);
                this.#keyInput.onTreeChanged();
                this.#keyInput.setCursor(bar.querySelector('nav'));
            }
        }
    }
}

const key_a = 65;
const key_d = 68;
const key_f = 70;
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
const key_targets_query =
    '.kint-rich .kint-parent > nav, .kint-rich > footer > nav, .kint-rich .kint-tabs > li:not(.kint-active-tab)';

// Call order to maintain consistency should be:
//
// onTreeChanged will try to keep the currently selected element targeted,
// by looking up the current target from its list and finding it again after
// rebuilding the tree. If it can't find it it will leave the target as the
// one at the current index, which should keep things running consistently if
// there was a dom disconnection before rebuilding it.
//
// setCursor will call onTreeChanged if the target can't be found in the list.
// If you call it directly after onTreeChanged this should basically never
// happen, but there are some cases where you want to set the cursor without
// changing the tree. I say "some" but the only real example is clicking the
// footer nav, since any other interaction will neccesarily change the tree
// and the key handler already checks if the element is in the dom.
//
// scrollToFocus does what it says on the box, but make sure the tree is up to
// date and the cursor is set (Since the focus it scrolls to is the cursor)
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

        const targets = Array.from(container.querySelectorAll(key_targets_query));
        for (const target of targets) {
            // Don't add hidden targets (Inside tabs, inside folder)
            //
            // In my tests using selectors instead of offsetParent makes this
            // process go much faster, but the reflow still has to happen and
            // all you're doing is pushing it further down the road. Using
            // offsetParent forces the reflow to happen here for the first time
            // but that's ok because it has to happen anyway (And also for
            // scrollToFocus) so you might as well get it over with now and
            // simplify the code.
            if (target.offsetParent !== null && target !== folderNav) {
                this.#targets.push(target);
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

        // Refuse to set cursor to invalid target
        if (!target.matches(key_targets_query)) {
            return false;
        }

        let i = this.#targets.indexOf(target);

        // This can happen if the target list is disconnected after init
        // and the user gets to setCursor via clicking on a minitrace
        if (i === -1) {
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
        } else {
            console.error('setCursor failed to find target in list', target);
            console.info('Please report this as a bug in Kint at https://github.com/kint-php/kint');
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

    #gotoNextHighestParent(parent) {
        const highTarget = parent
            .closest('.kint-rich .kint-parent ~ dd')
            ?.parentNode.querySelector('.kint-parent > nav');
        if (highTarget) {
            this.setCursor(highTarget);
            this.scrollToFocus();
        }
    }

    #handlePress(e) {
        if (e.keyCode === key_esc && e.target.matches('.kint-search')) {
            e.target.blur();

            if (this.#active) {
                this.#onCursorChanged();
            }
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

        // If something detaches all the nodes we need to regenerate the tree
        if (!elementIsInDom(this.#targets[this.#target])) {
            this.onTreeChanged();
        }

        const target = this.#targets[this.#target];

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

        if (target.parentNode.tagName === 'FOOTER' && target.closest('.kint-rich')) {
            // Minitrace needs special class handling
            if (e.keyCode === key_space || e.keyCode === key_enter) {
                target.parentNode.classList.toggle('kint-show');
            } else if (e.keyCode === key_left || e.keyCode === key_h) {
                if (target.parentNode.classList.contains('kint-show')) {
                    target.parentNode.classList.remove('kint-show');
                } else {
                    // If it's already closed find the next highest parent
                    this.#gotoNextHighestParent(target.closest('.kint-rich'));
                    return;
                }
            } else if (e.keyCode === key_right || e.keyCode === key_l) {
                target.parentNode.classList.add('kint-show');
            } else if (e.keyCode === key_f) {
                if (!this.#rich.isFolderOpen() && target.matches('.kint-rich > footer > nav')) {
                    const firstNav = target
                        .closest('.kint-rich')
                        .querySelector('.kint-parent > nav, .kint-rich > footer > nav');
                    this.#rich.addToFolder(target);
                    this.onTreeChanged();
                    this.setCursor(firstNav);
                    this.scrollToFocus();
                }
            }
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

        if (e.keyCode === key_f) {
            if (
                !this.#rich.isFolderOpen() &&
                parent.matches('.kint-rich:not(.kint-folder) > dl > .kint-parent')
            ) {
                this.#rich.addToFolder(target);
                this.onTreeChanged();
                this.setCursor(target);
                this.scrollToFocus();
            }
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

        if (e.keyCode === key_space || e.keyCode === key_enter) {
            // Toggle for normal nav
            Rich.toggle(parent);
            this.onTreeChanged();
            return;
        }

        if ([key_right, key_l, key_left, key_h].includes(e.keyCode)) {
            const isOpen = parent.classList.contains('kint-show');

            // Left hides, right opens
            if (e.keyCode === key_right || e.keyCode === key_l) {
                // If the target is open
                if (isOpen) {
                    // Expand/collapse all children first
                    Rich.toggleChildren(parent, true);
                }
                // Otherwise just expand the current one
                Rich.toggle(parent, true);
                this.onTreeChanged();
                return;
            } else {
                if (isOpen) {
                    // Close the target and all children
                    Rich.toggleChildren(parent, false);
                    Rich.toggle(parent, false);
                    this.onTreeChanged();
                    return;
                } else {
                    // If it's already closed find the next highest parent
                    this.#gotoNextHighestParent(parent);
                    return;
                }
            }
        }
    }
}
