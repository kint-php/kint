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

            each: function (selector, callback) {
                Array.prototype.slice
                    .call(document.querySelectorAll(selector), 0)
                    .forEach(callback);
            },

            hasClass: function (target, className) {
                if (!target.classList) {
                    return false;
                }

                if (typeof className === 'undefined') {
                    className = 'kint-show';
                }
                return target.classList.contains(className);
            },

            addClass: function (target, className) {
                if (typeof className === 'undefined') {
                    className = 'kint-show';
                }
                target.classList.add(className);
            },

            removeClass: function (target, className) {
                if (typeof className === 'undefined') {
                    className = 'kint-show';
                }
                target.classList.remove(className);
                return target;
            },

            toggle: function (element, hide) {
                var parent = kintRich.getChildren(element);

                if (!parent) {
                    return;
                }

                if (typeof hide === 'undefined') {
                    hide = kintRich.hasClass(element);
                }

                if (hide) {
                    kintRich.removeClass(element);
                } else {
                    kintRich.addClass(element);
                }

                if (parent.childNodes.length === 1) {
                    parent = parent.childNodes[0].childNodes[0]; // reuse variable cause I can

                    // parent is checked in case of empty <pre> when array("\n") is dumped
                    if (parent && kintRich.hasClass(parent, 'kint-parent')) {
                        kintRich.toggle(parent, hide);
                    }
                }
            },

            toggleChildren: function (element, hide) {
                var parent = kintRich.getChildren(element);

                if (!parent) {
                    return;
                }

                var nodes = parent.getElementsByClassName('kint-parent');
                var i = nodes.length;

                if (typeof hide === 'undefined') {
                    hide = !kintRich.hasClass(element);
                }

                while (i--) {
                    kintRich.toggle(nodes[i], hide);
                }
            },

            toggleAll: function (caret) {
                var elements = document.getElementsByClassName('kint-parent');
                var i = elements.length;
                var visible = !kintRich.hasClass(caret.parentNode);

                while (i--) {
                    kintRich.toggle(elements[i], visible);
                }
            },

            switchTab: function (target) {
                var lis;
                var el = target.previousSibling;
                var index = 0;

                kintRich.removeClass(
                    target.parentNode.getElementsByClassName('kint-active-tab')[0],
                    'kint-active-tab'
                );
                kintRich.addClass(target, 'kint-active-tab');

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
                        kintRich.addClass(lis[i]);

                        if (lis[i].childNodes.length === 1) {
                            el = lis[i].childNodes[0].childNodes[0];

                            if (el && kintRich.hasClass(el, 'kint-parent')) {
                                kintRich.toggle(el, false);
                            }
                        }
                    } else {
                        kintRich.removeClass(lis[i]);
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
                            document.getElementsByClassName('kint-rich-script')[0].outerHTML +
                            document.getElementsByClassName('kint-rich-style')[0].outerHTML +
                            kintRich.mktag('/head') +
                            kintRich.mktag('body') +
                            '<input style="width: 100%" placeholder="Take some notes!">' +
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
                    if (kintRich.hasClass(nodes[i], 'access-path')) {
                        if (kintRich.hasClass(nodes[i], 'kint-show')) {
                            kintRich.removeClass(nodes[i]);
                        } else {
                            kintRich.addClass(nodes[i]);
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

                if (kintRich.hasClass(input)) {
                    kintRich.removeClass(input);
                    kintRich.removeClass(target.parentNode, 'kint-search-root');
                } else {
                    kintRich.addClass(target);
                    kintRich.addClass(input);
                    input.focus();
                    input.select();
                    kintRich.search(target.parentNode, input.value);
                }
            },

            search: function (el, input) {
                var oldmatches = el.querySelectorAll('.kint-search-match');

                [].forEach.call(oldmatches, function (match) {
                    kintRich.removeClass(match, 'kint-search-match');
                });
                kintRich.removeClass(el, 'kint-search-match');

                if (input.length) {
                    kintRich.addClass(el, 'kint-search-root');
                    kintRich.findMatches(el, input);
                } else {
                    kintRich.removeClass(el, 'kint-search-root');
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

                kintRich.addClass(el, 'kint-search-match');

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
                        if (kintRich.hasClass(node, 'kint-tabs')) {
                            tabs = node.childNodes;
                        } else if (kintRich.hasClass(node, 'kint-tab-contents')) {
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
                        kintRich.addClass(tabs[index], 'kint-search-match');

                        [].forEach.call(tabcontents[index].childNodes, function (node) {
                            if (node.tagName == 'DL') {
                                kintRich.findMatches(node, term);
                            }
                        });
                    }
                }
            },

            getParentByClass: function (el, className) {
                if (typeof className === 'undefined') {
                    className = 'kint-rich';
                }

                for (;;) {
                    el = el.parentNode;
                    if (!el || kintRich.hasClass(el, className)) {
                        break;
                    }
                }

                return el;
            },

            getParentHeader: function (el, allowChildren) {
                var nodeName = el.nodeName.toLowerCase();

                while (nodeName !== 'dd' && nodeName !== 'dt' && kintRich.getParentByClass(el)) {
                    el = el.parentNode;
                    nodeName = el.nodeName.toLowerCase();
                }

                if (!kintRich.getParentByClass(el)) {
                    return null;
                }

                if (nodeName === 'dd' && allowChildren) {
                    el = el.previousElementSibling;
                }

                if (
                    el &&
                    el.nodeName.toLowerCase() === 'dt' &&
                    kintRich.hasClass(el, 'kint-parent')
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

                return kintRich.hasClass(
                    kintRich.folder.querySelector('dd.kint-foldout').previousSibling
                );
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

                kintRich.addClass(kintRich.folder);
            },

            keyboardNav: {
                targets: [], // all visible toggle carets
                target: 0, // currently selected caret
                active: false,

                fetchTargets: function () {
                    var selected = kintRich.keyboardNav.targets[kintRich.keyboardNav.target];

                    kintRich.keyboardNav.targets = [];
                    kintRich.each(
                        '.kint-rich nav, .kint-tabs>li:not(.kint-active-tab)',
                        function (el) {
                            // Don't add targets outside of folder if folder is open
                            if (kintRich.isFolderOpen() && !kintRich.folder.contains(el)) {
                                return;
                            }

                            // Don't add hidden targets (Inside tabs, inside folder)
                            if (el.offsetWidth !== 0 || el.offsetHeight !== 0) {
                                kintRich.keyboardNav.targets.push(el);
                            }
                        }
                    );

                    if (selected && kintRich.keyboardNav.targets.indexOf(selected) !== -1) {
                        kintRich.keyboardNav.target =
                            kintRich.keyboardNav.targets.indexOf(selected);
                    }
                },

                sync: function (noscroll) {
                    var prevElement = document.querySelector('.kint-focused');
                    if (prevElement) {
                        kintRich.removeClass(prevElement, 'kint-focused');
                    }

                    if (kintRich.keyboardNav.active) {
                        var el = kintRich.keyboardNav.targets[kintRich.keyboardNav.target];
                        kintRich.addClass(el, 'kint-focused');

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
                var nodeName = target.nodeName.toLowerCase();

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
                        kintRich.toggleAll(target);
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

                if (!kintRich.getParentByClass(target)) {
                    return;
                }

                // auto-select name of variable
                if (nodeName === 'dfn') {
                    kintRich.selectText(target);
                } else if (nodeName === 'th') {
                    if (!e.ctrlKey) {
                        kintRich.sortTable(
                            target.parentNode.parentNode.parentNode,
                            target.cellIndex
                        );
                    }
                    return;
                }

                // Ensure the header at least is selected
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
                        if (kintRich.hasClass(target)) {
                            kintRich.removeClass(target);
                        } else {
                            kintRich.addClass(target);
                        }
                    } else {
                        // ensure double/triple click has different behaviour, see below
                        kintRich.toggle(target.parentNode);
                        kintRich.keyboardNav.fetchTargets();
                        kintRich.mouseNav.lastClickCount = 1;
                        kintRich.mouseNav.lastClickTarget = target;
                        kintRich.mouseNav.renewLastClick();
                    }
                } else if (kintRich.hasClass(target, 'kint-popup-trigger')) {
                    var kintContainer = target.parentNode;
                    if (kintContainer.nodeName.toLowerCase() === 'footer') {
                        kintContainer = kintContainer.previousSibling;
                    } else {
                        while (kintContainer && !kintRich.hasClass(kintContainer, 'kint-parent')) {
                            kintContainer = kintContainer.parentNode;
                        }
                    }

                    kintRich.openInNewWindow(kintContainer);
                } else if (kintRich.hasClass(target, 'kint-access-path-trigger')) {
                    kintRich.showAccessPath(target.parentNode);
                } else if (kintRich.hasClass(target, 'kint-search-trigger')) {
                    kintRich.showSearchBox(target.parentNode);
                } else if (kintRich.hasClass(target, 'kint-search')) {
                } else if (nodeName === 'pre' && e.detail === 3) {
                    // triple click pre to select it all
                    kintRich.selectText(target);
                } else if (kintRich.getParentByClass(target, 'kint-source') && e.detail === 3) {
                    kintRich.selectText(kintRich.getParentByClass(target, 'kint-source'));
                } else if (kintRich.hasClass(target, 'access-path')) {
                    kintRich.selectText(target);
                } else if (nodeName !== 'a') {
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
                // do nothing if alt/ctrl key is pressed or if we're actually typing somewhere
                if (e.target !== document.body || e.altKey || e.ctrlKey) {
                    return;
                }

                if (e.keyCode === 68) {
                    // 'd' : toggles navigation on/off
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
                } else if (e.keyCode === 9) {
                    // TAB : moves up/down depending on shift key
                    kintRich.keyboardNav.moveCursor(e.shiftKey ? -1 : 1);
                    e.preventDefault();
                    return;
                } else if (e.keyCode === 38 || e.keyCode === 75) {
                    // ARROW UP : moves up
                    kintRich.keyboardNav.moveCursor(-1);
                    e.preventDefault();
                    return;
                } else if (e.keyCode === 40 || e.keyCode === 74) {
                    // ARROW DOWN : down
                    kintRich.keyboardNav.moveCursor(1);
                    e.preventDefault();
                    return;
                }

                var kintNode = kintRich.keyboardNav.targets[kintRich.keyboardNav.target];
                if (kintNode.nodeName.toLowerCase() === 'li') {
                    // we're on a trace tab
                    if (e.keyCode === 32 || e.keyCode === 13) {
                        // SPACE/ENTER
                        kintRich.switchTab(kintNode);
                        kintRich.keyboardNav.fetchTargets();
                        kintRich.keyboardNav.sync();
                        e.preventDefault();
                        return;
                    } else if (e.keyCode === 39 || e.keyCode === 76) {
                        // arrows
                        kintRich.keyboardNav.moveCursor(1);
                        e.preventDefault();
                        return;
                    } else if (e.keyCode === 37 || e.keyCode === 72) {
                        kintRich.keyboardNav.moveCursor(-1);
                        e.preventDefault();
                        return;
                    }
                }

                // simple dump
                kintNode = kintNode.parentNode;

                if (e.keyCode === 65) {
                    // 'a' : toggles access path on/off
                    kintRich.showAccessPath(kintNode);
                    e.preventDefault();
                    return;
                } else if (
                    kintNode.nodeName.toLowerCase() === 'footer' &&
                    kintRich.hasClass(kintNode.parentNode, 'kint-rich')
                ) {
                    // Minitrace needs special class handling
                    if (e.keyCode === 32 || e.keyCode === 13) {
                        if (kintRich.hasClass(kintNode)) {
                            kintRich.removeClass(kintNode);
                        } else {
                            kintRich.addClass(kintNode);
                        }
                        e.preventDefault();
                    } else if (e.keyCode === 37 || e.keyCode === 72) {
                        kintRich.removeClass(kintNode);
                        e.preventDefault();
                    } else if (e.keyCode === 39 || e.keyCode === 76) {
                        kintRich.addClass(kintNode);
                        e.preventDefault();
                    }
                    return;
                } else if (e.keyCode === 32 || e.keyCode === 13) {
                    // SPACE/ENTER : toggles
                    kintRich.toggle(kintNode);
                    kintRich.keyboardNav.fetchTargets();
                    e.preventDefault();
                    return;
                } else if (
                    e.keyCode === 39 ||
                    e.keyCode === 76 ||
                    e.keyCode === 37 ||
                    e.keyCode === 72
                ) {
                    // ARROW LEFT/RIGHT : respectively hides/shows and traverses
                    var hide = e.keyCode === 37 || e.keyCode === 72;

                    if (kintRich.hasClass(kintNode)) {
                        // expand/collapse all children if immediate ones are showing
                        kintRich.toggleChildren(kintNode, hide);
                        kintRich.toggle(kintNode, hide);
                    } else {
                        // traverse to parent and THEN hide
                        if (hide) {
                            var parent = kintRich.getParentHeader(
                                kintNode.parentNode.parentNode,
                                true
                            );

                            if (parent) {
                                kintNode = parent;
                                kintRich.keyboardNav.setCursor(kintNode.querySelector('nav'));
                                kintRich.keyboardNav.sync();
                            }
                        }

                        kintRich.toggle(kintNode, hide);
                    }

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
