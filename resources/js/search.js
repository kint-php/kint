import { debounce } from './utils.js';
import Rich from './rich.js';

export default class Search {
    static #hookedInputs = new Set();

    static toggleSearchBox(parent, show) {
        const input = parent.querySelector('.kint-search');
        const root = parent.parentNode;

        if (!input) {
            return;
        }

        if (input.classList.toggle('kint-show', show)) {
            input.focus();
            input.select();

            if (!Search.#hookedInputs.has(input)) {
                // If it's a really big dump (eg. symfony container without
                // depth limit) up the debounce time or it'll be unworkable
                const subcount = root.querySelectorAll('dl').length;
                let time = 200;
                if (subcount > 10000) {
                    time = 700;
                }

                input.addEventListener('keyup', debounce(Search.#search.bind(null, input), time));
                Search.#hookedInputs.add(input);
            }

            Search.#search(input);
        } else {
            root.classList.remove('kint-search-root');
        }
    }

    static #search(input) {
        const root = input.closest('.kint-parent')?.parentNode;

        if (!root) {
            return;
        }

        if (input.classList.contains('kint-show') && input.value.length) {
            const lastSearch = root.dataset.lastSearch;
            root.classList.add('kint-search-root');

            if (lastSearch !== input.value) {
                root.dataset.lastSearch = input.value;

                root.classList.remove('kint-search-match');
                for (const element of root.querySelectorAll('.kint-search-match')) {
                    element.classList.remove('kint-search-match');
                }

                Search.#findMatches(root, input.value.toUpperCase());
            }
        } else {
            root.classList.remove('kint-search-root');
        }
    }

    static #findMatches(container, term) {
        const cleaned = container.cloneNode(true);
        for (const ap of cleaned.querySelectorAll('.access-path')) {
            ap.remove();
        }

        if (!cleaned.textContent.toUpperCase().includes(term)) {
            return;
        }

        container.classList.add('kint-search-match');

        let parent = container.firstElementChild;
        while (parent && parent.tagName !== 'DT') {
            parent = parent.nextElementSibling;
        }

        if (!parent) {
            return;
        }

        const childContainer = Rich.getChildContainer(parent);
        if (!childContainer) {
            return;
        }

        let tabs;
        let tabContents;

        for (const child of childContainer.children) {
            if (child.tagName === 'DL') {
                Search.#findMatches(child, term);
            } else if (child.tagName === 'UL') {
                if (child.classList.contains('kint-tabs')) {
                    tabs = child.children;
                } else if (child.classList.contains('kint-tab-contents')) {
                    tabContents = child.children;
                }
            }
        }

        if (!tabs || tabs.length !== tabContents?.length) {
            return;
        }

        for (let index = tabs.length; index--; ) {
            let matchedTab = false;
            let matchedTabContents = false;

            if (tabs[index].textContent.toUpperCase().includes(term)) {
                matchedTab = true;
            }

            const cleaned = tabContents[index].cloneNode(true);
            for (const ap of cleaned.querySelectorAll('.access-path')) {
                ap.remove();
            }

            if (cleaned.textContent.toUpperCase().includes(term)) {
                matchedTab = true;
                matchedTabContents = true;
            }

            if (matchedTab) {
                tabs[index].classList.add('kint-search-match');
            }

            if (matchedTabContents) {
                for (const child of tabContents[index].children) {
                    if (child.tagName === 'DL') {
                        Search.#findMatches(child, term);
                    }
                }
            }
        }
    }
}
