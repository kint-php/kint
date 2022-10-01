if (typeof window.kintRich === 'undefined') {
    window.kintRich = (function () {
        'use strict';

        var kintRich = {
            selectText: function (element) {
                var selection = window.getSelection();
                var range = document.createRange();

                range.selectNodeContents(element);
                selection.removeAllRanges();
                selection.addRange(range);
            },

            toggle: function (element, show) {
                var parent = kintRich.getChildren(element);

                if (!parent) {
                    return;
                }

                element.classList.toggle('kint-show', show);

                if (parent.childNodes.length === 1) {
                    parent = parent.childNodes[0].childNodes[0]; // reuse variable cause I can

                    // Parent is checked for a class list in case of empty <pre>
                    if (parent && parent.classList && parent.classList.contains('kint-parent')) {
                        kintRich.toggle(parent, show);
                    }
                }
            },

            toggleChildren: function (element, show) {
                var parent = kintRich.getChildren(element);

                if (!parent) {
                    return;
                }

                var nodes = parent.getElementsByClassName('kint-parent');
                var i = nodes.length;

                if (typeof show === 'undefined') {
                    show = element.classList.contains('kint-show');
                }

                while (i--) {
                    kintRich.toggle(nodes[i], show);
                }
            },

            switchTab: function (target) {
                var lis;
                var el = target.previousSibling;
                var index = 0;

                target.parentNode
                    .getElementsByClassName('kint-active-tab')[0]
                    .classList.remove('kint-active-tab');
                target.classList.add('kint-active-tab');

                // take the index of clicked title tab and make the same n-th content tab visible
                while (el) {
                    if (el.nodeType === 1) {
                        index++;
                    }

                    el = el.previousSibling;
                }

                lis = target.parentNode.nextSibling.childNodes;

                for (var i = 0; i < lis.length; i++) {
                    if (i === index) {
                        lis[i].classList.add('kint-show');

                        if (lis[i].childNodes.length === 1) {
                            el = lis[i].childNodes[0].childNodes[0];

                            if (el && el.classList && el.classList.contains('kint-parent')) {
                                kintRich.toggle(el, true);
                            }
                        }
                    } else {
                        lis[i].classList.remove('kint-show');
                    }
                }
            },

            // Shitty people using shitty regex fuck up the JS when they see <head> or <meta charset>
            // so we have to write this obfuscation to make sure it works after minification
            mktag: function (contents) {
                return '<' + contents + '>';
            },

            openInNewWindow: function (kintContainer) {
                var newWindow = window.open();

                if (newWindow) {
                    newWindow.document.open();
                    newWindow.document.write(
                        kintRich.mktag('html') +
                            kintRich.mktag('head') +
                            kintRich.mktag('title') +
                            'Kint (' +
                            new Date().toISOString() +
                            ')' +
                            kintRich.mktag('/title') +
                            kintRich.mktag('meta charset="utf-8"') +
                            /**
                             * `tag.getAttribute('nonce')` doesn't work for security reasons.
                             * See https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/nonce#accessing_nonces_and_nonce_hiding.
                             */
                            kintRich.mktag(
                                'script class="kint-rich-script" nonce="' +
                                    kintRich.script.nonce +
                                    '"'
                            ) +
                            kintRich.script.innerHTML +
                            kintRich.mktag('/script') +
                            /**
                             * `tag.getAttribute('nonce')` doesn't work for security reasons.
                             * See https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/nonce#accessing_nonces_and_nonce_hiding.
                             */
                            kintRich.mktag(
                                'style class="kint-rich-style" nonce="' + kintRich.style.nonce + '"'
                            ) +
                            kintRich.style.innerHTML +
                            kintRich.mktag('/style') +
                            kintRich.mktag('/head') +
                            kintRich.mktag('body') +
                            '<input class="kint-note-input" placeholder="Take some notes!">' +
                            '<div class="kint-rich">' +
                            kintContainer.parentNode.outerHTML +
                            '</div>' +
                            kintRich.mktag('/body')
                    );
                    newWindow.document.close();
                }
            },

            sortTable: function (table, column) {
                var tbody = table.tBodies[0];

                [].slice
                    .call(table.tBodies[0].rows)
                    .sort(function (a, b) {
                        a = a.cells[column].textContent.trim().toLocaleLowerCase();
                        b = b.cells[column].textContent.trim().toLocaleLowerCase();

                        // In lieu of natsort we just sort all numbers before strings
                        if (!isNaN(a) && !isNaN(b)) {
                            a = parseFloat(a);
                            b = parseFloat(b);
                        } else if (isNaN(a) && !isNaN(b)) {
                            return 1;
                        } else if (isNaN(b) && !isNaN(a)) {
                            return -1;
                        }

                        if (a < b) {
                            return -1;
                        }
                        if (a > b) {
                            return 1;
                        }

                        return 0;
                    })
                    .forEach(function (el) {
                        tbody.appendChild(el);
                    });
            },

            showAccessPath: function (target) {
                var nodes = target.childNodes;

                for (var i = 0; i < nodes.length; i++) {
                    if (nodes[i].classList && nodes[i].classList.contains('access-path')) {
                        nodes[i].classList.toggle('kint-show');
                        if (nodes[i].classList.contains('kint-show')) {
                            kintRich.selectText(nodes[i]);
                        }
                        return;
                    }
                }
            },

            showSearchBox: function (target) {
                var input = target.querySelector('.kint-search');

                if (!input) {
                    return;
                }

                input.classList.toggle('kint-show');
                if (input.classList.contains('kint-show')) {
                    target.classList.add('kint-show');
                    input.focus();
                    input.select();
                    kintRich.search(target.parentNode, input.value);
                } else {
                    target.parentNode.classList.remove('kint-search-root');
                }
            },

            search: function (el, input) {
                el.querySelectorAll('.kint-search-match').forEach(function (match) {
                    match.classList.remove('kint-search-match');
                });
                el.classList.remove('kint-search-match');

                el.classList.toggle('kint-search-root', input.length);
                if (input.length) {
                    kintRich.findMatches(el, input);
                }
            },

            findMatches: function (el, term) {
                var cleaned = el.cloneNode(true);
                cleaned.querySelectorAll('.access-path').forEach(function (e) {
                    e.remove();
                });

                if (cleaned.textContent.toUpperCase().indexOf(term.toUpperCase()) == -1) {
                    return;
                }

                el.classList.add('kint-search-match');

                var node;

                for (var index in el.childNodes) {
                    if (el.childNodes[index].tagName == 'DD') {
                        node = el.childNodes[index];
                        break;
                    }
                }

                if (!node) {
                    return;
                }

                var tabs;
                var tabcontents;

                [].forEach.call(node.childNodes, function (node) {
                    if (node.tagName == 'DL') {
                        kintRich.findMatches(node, term);
                    } else if (node.tagName == 'UL') {
                        if (node.classList.contains('kint-tabs')) {
                            tabs = node.childNodes;
                        } else if (node.classList.contains('kint-tab-contents')) {
                            tabcontents = node.childNodes;
                        }
                    }
                });

                if (!tabs || !tabcontents || tabs.length != tabcontents.length) {
                    return;
                }

                for (var index = 0; index < tabs.length; index++) {
                    var matched = false;

                    if (tabs[index].textContent.toUpperCase().indexOf(term.toUpperCase()) != -1) {
                        matched = true;
                    } else {
                        cleaned = tabcontents[index].cloneNode(true);
                        cleaned.querySelectorAll('.access-path').forEach(function (e) {
                            e.remove();
                        });

                        if (cleaned.textContent.toUpperCase().indexOf(term.toUpperCase()) != -1) {
                            matched = true;
                        }
                    }

                    if (matched) {
                        tabs[index].classList.add('kint-search-match');

                        [].forEach.call(tabcontents[index].childNodes, function (node) {
                            if (node.tagName == 'DL') {
                                kintRich.findMatches(node, term);
                            }
                        });
                    }
                }
            },

            getParentByClass: function (el, className) {
                for (;;) {
                    el = el.parentNode;
                    if (!el || !el.classList || el.classList.contains(className)) {
                        break;
                    }
                }

                return el;
            },

            getParentHeader: function (el, allowChildren) {
                var nodeName = el.nodeName.toLowerCase();

                while (
                    nodeName !== 'dd' &&
                    nodeName !== 'dt' &&
                    kintRich.getParentByClass(el, 'kint-rich')
                ) {
                    el = el.parentNode;
                    nodeName = el.nodeName.toLowerCase();
                }

                if (!kintRich.getParentByClass(el, 'kint-rich')) {
                    return null;
                }

                if (nodeName === 'dd' && allowChildren) {
                    el = el.previousElementSibling;
                }

                if (
                    el &&
                    el.nodeName.toLowerCase() === 'dt' &&
                    el.classList.contains('kint-parent')
                ) {
                    return el;
                }
            },

            getChildren: function (element) {
                do {
                    element = element.nextElementSibling;
                } while (element && element.nodeName.toLowerCase() !== 'dd');
                return element;
            },

            isFolderOpen: function () {
                if (!kintRich.folder || !kintRich.folder.querySelector('dd.kint-foldout')) {
                    return undefined;
                }

                return kintRich.folder
                    .querySelector('dd.kint-foldout')
                    .previousSibling.classList.contains('kint-show');
            },

            initLoad: function () {
                kintRich.style = window.kintShared.dedupe('style.kint-rich-style', kintRich.style);
                kintRich.script = window.kintShared.dedupe(
                    'script.kint-rich-script',
                    kintRich.script
                );
                kintRich.folder = window.kintShared.dedupe(
                    '.kint-rich.kint-folder',
                    kintRich.folder
                );

                var searchboxes = document.querySelectorAll('input.kint-search');

                [].forEach.call(searchboxes, function (input) {
                    var timeout = null;
                    var value = null;

                    var searchfunc = function (e) {
                        window.clearTimeout(timeout);

                        if (input.value === value) {
                            return;
                        }

                        timeout = window.setTimeout(function () {
                            value = input.value;
                            kintRich.search(input.parentNode.parentNode, value);
                        }, 500);
                    };

                    input.removeEventListener('keyup', searchfunc);
                    input.addEventListener('keyup', searchfunc);
                });

                if (!kintRich.folder) {
                    return;
                }

                var container = kintRich.folder.querySelector('dd');

                // Add kint dumps to folder
                [].forEach.call(document.querySelectorAll('.kint-rich.kint-file'), function (elem) {
                    if (elem.parentNode === kintRich.folder) {
                        return;
                    }

                    container.appendChild(elem);
                });

                document.body.appendChild(kintRich.folder);

                kintRich.folder.classList.add('kint-show');
            },

            keyboardNav: {
                targets: [], // all visible toggle carets
                target: 0, // currently selected caret
                active: false,

                fetchTargets: function () {
                    var selected = kintRich.keyboardNav.targets[kintRich.keyboardNav.target];

                    kintRich.keyboardNav.targets = [];

                    document
                        .querySelectorAll('.kint-rich nav, .kint-tabs>li:not(.kint-active-tab)')
                        .forEach(function (el) {
                            // Don't add targets outside of folder if folder is open
                            if (kintRich.isFolderOpen() && !kintRich.folder.contains(el)) {
                                return;
                            }

                            // Don't add hidden targets (Inside tabs, inside folder)
                            if (el.offsetWidth !== 0 || el.offsetHeight !== 0) {
                                kintRich.keyboardNav.targets.push(el);
                            }
                        });

                    if (selected && kintRich.keyboardNav.targets.indexOf(selected) !== -1) {
                        kintRich.keyboardNav.target =
                            kintRich.keyboardNav.targets.indexOf(selected);
                    }
                },

                sync: function (noscroll) {
                    var prevElement = document.querySelector('.kint-focused');
                    if (prevElement) {
                        prevElement.classList.remove('kint-focused');
                    }

                    if (kintRich.keyboardNav.active) {
                        var el = kintRich.keyboardNav.targets[kintRich.keyboardNav.target];
                        el.classList.add('kint-focused');

                        // Generally speaking keyboard navigation should result
                        // in a scroll and mouse navigation shouldn't
                        if (!noscroll) {
                            kintRich.keyboardNav.scroll(el);
                        }
                    }
                },

                scroll: function (el) {
                    if (el === kintRich.folder.querySelector('dt > nav')) {
                        return;
                    }

                    var offsetTop = function (el) {
                        return el.offsetTop + (el.offsetParent ? offsetTop(el.offsetParent) : 0);
                    };

                    var top = offsetTop(el);

                    if (kintRich.isFolderOpen()) {
                        var container = kintRich.folder.querySelector('dd.kint-foldout');
                        container.scrollTo(0, top - container.clientHeight / 2);
                    } else {
                        window.scrollTo(0, top - window.innerHeight / 2);
                    }
                },

                moveCursor: function (diff) {
                    kintRich.keyboardNav.target += diff;

                    while (kintRich.keyboardNav.target < 0) {
                        kintRich.keyboardNav.target += kintRich.keyboardNav.targets.length;
                    }
                    while (kintRich.keyboardNav.target >= kintRich.keyboardNav.targets.length) {
                        kintRich.keyboardNav.target -= kintRich.keyboardNav.targets.length;
                    }

                    kintRich.keyboardNav.sync();
                },

                setCursor: function (elem) {
                    // Refuse to set cursor outside of folder if folder is open
                    if (kintRich.isFolderOpen() && !kintRich.folder.contains(elem)) {
                        return false;
                    }

                    kintRich.keyboardNav.fetchTargets();

                    for (var i = 0; i < kintRich.keyboardNav.targets.length; i++) {
                        if (elem === kintRich.keyboardNav.targets[i]) {
                            kintRich.keyboardNav.target = i;
                            return true;
                        }
                    }

                    return false;
                },
            },

            mouseNav: {
                lastClickTarget: null,
                lastClickTimer: null,
                lastClickCount: 0,

                renewLastClick: function () {
                    window.clearTimeout(kintRich.mouseNav.lastClickTimer);
                    kintRich.mouseNav.lastClickTimer = window.setTimeout(function () {
                        kintRich.mouseNav.lastClickTarget = null;
                        kintRich.mouseNav.lastClickTimer = null;
                        kintRich.mouseNav.lastClickCount = 0;
                    }, 250);
                },
            },

            style: null,
            script: null,
            folder: null,
        };

        window.addEventListener(
            'click',
            function (e) {
                var target = e.target;

                // Double/triple click to open children/all
                if (
                    kintRich.mouseNav.lastClickTarget &&
                    kintRich.mouseNav.lastClickTimer &&
                    kintRich.mouseNav.lastClickCount
                ) {
                    target = kintRich.mouseNav.lastClickTarget;

                    if (kintRich.mouseNav.lastClickCount === 1) {
                        kintRich.toggleChildren(target.parentNode);
                        kintRich.keyboardNav.setCursor(target);
                        kintRich.keyboardNav.sync(true);
                        kintRich.mouseNav.lastClickCount++;

                        kintRich.mouseNav.renewLastClick();
                    } else {
                        // Toggle all on page
                        var show = target.parentNode.classList.contains('kint-show');
                        var elements = document.getElementsByClassName('kint-parent');
                        var i = elements.length;

                        while (i--) {
                            kintRich.toggle(elements[i], show);
                        }

                        kintRich.keyboardNav.setCursor(target);
                        kintRich.keyboardNav.sync(true);
                        kintRich.keyboardNav.scroll(target);

                        window.clearTimeout(kintRich.mouseNav.lastClickTimer);
                        kintRich.mouseNav.lastClickTarget = null;
                        kintRich.mouseNav.lastClickTarget = null;
                        kintRich.mouseNav.lastClickCount = 0;
                    }

                    return;
                }

                // Ignore clicks outside kint
                if (!kintRich.getParentByClass(target, 'kint-rich')) {
                    return;
                }

                var nodeName = target.nodeName.toLowerCase();

                // auto-select name of variable
                if (nodeName === 'dfn') {
                    kintRich.selectText(target);
                }

                // Sort tables
                if (nodeName === 'th') {
                    if (!e.ctrlKey) {
                        kintRich.sortTable(
                            target.parentNode.parentNode.parentNode,
                            target.cellIndex
                        );
                    }
                    return;
                }

                // Ensure the nav is selected for keyboard
                target = kintRich.getParentHeader(target);
                if (target) {
                    kintRich.keyboardNav.setCursor(target.querySelector('nav'));
                    kintRich.keyboardNav.sync(true);
                }

                target = e.target;

                if (nodeName === 'li' && target.parentNode.className === 'kint-tabs') {
                    // switch tabs
                    if (target.className !== 'kint-active-tab') {
                        kintRich.switchTab(target);
                    }
                    target = kintRich.getParentHeader(target, true);
                    if (target) {
                        kintRich.keyboardNav.setCursor(target.querySelector('nav'));
                        kintRich.keyboardNav.sync(true);
                    }
                } else if (nodeName === 'nav') {
                    // handle clicks on the navigation caret
                    if (target.parentNode.nodeName.toLowerCase() === 'footer') {
                        kintRich.keyboardNav.setCursor(target);
                        kintRich.keyboardNav.sync(true);
                        target = target.parentNode;
                        target.classList.toggle('kint-show');
                    } else {
                        // ensure double/triple click has different behaviour, see above
                        kintRich.toggle(target.parentNode);
                        kintRich.keyboardNav.fetchTargets();
                        kintRich.mouseNav.lastClickCount = 1;
                        kintRich.mouseNav.lastClickTarget = target;
                        kintRich.mouseNav.renewLastClick();
                    }
                } else if (target.classList.contains('kint-popup-trigger')) {
                    // Popup
                    var kintContainer = target.parentNode;
                    if (kintContainer.nodeName.toLowerCase() === 'footer') {
                        kintContainer = kintContainer.previousSibling;
                    } else {
                        while (kintContainer && !kintContainer.classList.contains('kint-parent')) {
                            kintContainer = kintContainer.parentNode;
                        }
                    }

                    kintRich.openInNewWindow(kintContainer);
                } else if (target.classList.contains('kint-access-path-trigger')) {
                    // Access path
                    kintRich.showAccessPath(target.parentNode);
                } else if (target.classList.contains('kint-search-trigger')) {
                    // Search box
                    kintRich.showSearchBox(target.parentNode);
                } else if (target.classList.contains('kint-search')) {
                    // Do nothing if you click the search input
                } else if (nodeName === 'pre' && e.detail === 3) {
                    // Triple click pre to select it all
                    kintRich.selectText(target);
                } else if (kintRich.getParentByClass(target, 'kint-source') && e.detail === 3) {
                    // Tripleclick source code to select it all
                    kintRich.selectText(kintRich.getParentByClass(target, 'kint-source'));
                } else if (target.classList.contains('access-path')) {
                    // Select the access path
                    kintRich.selectText(target);
                } else if (nodeName !== 'a') {
                    // If it's not a link at this point, we're probably clicking the bar, so toggle it
                    target = kintRich.getParentHeader(target);
                    if (target) {
                        kintRich.toggle(target);
                        kintRich.keyboardNav.fetchTargets();
                    }
                }
            },
            true
        );

        // keyboard navigation
        window.addEventListener(
            'keydown',
            function (e) {
                const key_a = 65;
                const key_d = 68;
                const key_h = 72;
                const key_j = 74;
                const key_k = 75;
                const key_l = 76;
                const key_tab = 9;
                const key_enter = 13;
                const key_esc = 27;
                const key_space = 32;
                const key_left = 37;
                const key_up = 38;
                const key_right = 39;
                const key_down = 40;

                // do nothing if alt/ctrl key is pressed or if we're actually typing somewhere
                if (e.target !== document.body || e.altKey || e.ctrlKey) {
                    return;
                }

                if (e.keyCode === key_d) {
                    // Toggles navigation on/off
                    if (kintRich.keyboardNav.active) {
                        kintRich.keyboardNav.active = false;
                    } else {
                        kintRich.keyboardNav.active = true;
                        kintRich.keyboardNav.fetchTargets();

                        if (kintRich.keyboardNav.targets.length === 0) {
                            kintRich.keyboardNav.active = false;
                            return;
                        }
                    }

                    kintRich.keyboardNav.sync();
                    e.preventDefault();
                    return;
                } else if (!kintRich.keyboardNav.active) {
                    return;
                } else if (e.keyCode === key_tab) {
                    // Moves up/down depending on shift key
                    kintRich.keyboardNav.moveCursor(e.shiftKey ? -1 : 1);
                    e.preventDefault();
                    return;
                } else if (e.keyCode === key_up || e.keyCode === key_k) {
                    // Moves up
                    kintRich.keyboardNav.moveCursor(-1);
                    e.preventDefault();
                    return;
                } else if (e.keyCode === key_down || e.keyCode === key_j) {
                    // Moves down
                    kintRich.keyboardNav.moveCursor(1);
                    e.preventDefault();
                    return;
                }

                var kintNode = kintRich.keyboardNav.targets[kintRich.keyboardNav.target];
                if (kintNode.nodeName.toLowerCase() === 'li') {
                    // When we're on a tab
                    if (e.keyCode === key_space || e.keyCode === key_enter) {
                        // Toggle to it
                        kintRich.switchTab(kintNode);
                        kintRich.keyboardNav.fetchTargets();
                        kintRich.keyboardNav.sync();
                        e.preventDefault();
                        return;
                    } else if (e.keyCode === key_right || e.keyCode === key_l) {
                        // Move right
                        kintRich.keyboardNav.moveCursor(1);
                        e.preventDefault();
                        return;
                    } else if (e.keyCode === key_left || e.keyCode === key_h) {
                        // Move left
                        kintRich.keyboardNav.moveCursor(-1);
                        e.preventDefault();
                        return;
                    }
                }

                // simple dump
                kintNode = kintNode.parentNode;

                if (e.keyCode === key_a) {
                    // Toggles access path on/off
                    kintRich.showAccessPath(kintNode);
                    e.preventDefault();
                    return;
                } else if (
                    kintNode.nodeName.toLowerCase() === 'footer' &&
                    kintNode.parentNode.classList.contains('kint-rich')
                ) {
                    // Minitrace needs special class handling
                    if (e.keyCode === key_space || e.keyCode === key_enter) {
                        kintNode.classList.toggle('kint-show');
                        e.preventDefault();
                    } else if (e.keyCode === key_left || e.keyCode === key_h) {
                        kintNode.classList.remove('kint-show');
                        e.preventDefault();
                    } else if (e.keyCode === key_right || e.keyCode === key_l) {
                        kintNode.classList.add('kint-show');
                        e.preventDefault();
                    }
                    return;
                } else if (e.keyCode === key_space || e.keyCode === key_enter) {
                    // Toggle for normal nav
                    kintRich.toggle(kintNode);
                    kintRich.keyboardNav.fetchTargets();
                    e.preventDefault();
                    return;
                } else if (
                    e.keyCode === key_right ||
                    e.keyCode === key_l ||
                    e.keyCode === key_left ||
                    e.keyCode === key_h
                ) {
                    // Left hides, right opens
                    var show = e.keyCode === key_right || e.keyCode === key_l;

                    // If the target is open
                    if (kintNode.classList.contains('kint-show')) {
                        // Expand/collapse all children first
                        kintRich.toggleChildren(kintNode, show);
                    } else if (!show) {
                        // If the target is already closed and we press left,
                        // then traverse to parent and close it instead
                        var parent = kintRich.getParentHeader(kintNode.parentNode.parentNode, true);

                        if (parent) {
                            kintNode = parent;
                            kintRich.keyboardNav.setCursor(kintNode.querySelector('nav'));
                            kintRich.keyboardNav.sync();
                        }
                    }

                    kintRich.toggle(kintNode, show);

                    kintRich.keyboardNav.fetchTargets();
                    e.preventDefault();
                    return;
                }
            },
            true
        );

        return kintRich;
    })();
}

window.kintShared.runOnce(window.kintRich.initLoad);
