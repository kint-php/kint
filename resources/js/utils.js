export function dedupeElement(element) {
    if (!(element instanceof Element)) {
        throw new Error('Invalid argument to dedupeElement()');
    }

    const document = element.ownerDocument;
    const selector = buildClassSelector(element);

    for (const elem of document.querySelectorAll(selector)) {
        if (element !== elem) {
            elem.parentNode.removeChild(elem);
        }
    }
}

// Why would we need this? Because if someone naively rewrites the dom
// with something like `body.innerHTML += x` all our node references
// will be detached and we'll need to reaquire them somehow
export function elementIsInDom(element) {
    if (!(element instanceof Element)) {
        return false;
    }

    return element.ownerDocument.contains(element);
}

export function buildClassSelector(element) {
    if (!(element instanceof Element)) {
        throw new Error('Invalid argument to buildClassSelector()');
    }

    return [element.nodeName, ...element.classList].join('.');
}

export function selectText(element) {
    if (!(element instanceof Element)) {
        throw new Error('Invalid argument to selectText()');
    }

    const document = element.ownerDocument;
    const selection = document.getSelection();
    const range = document.createRange();

    range.selectNodeContents(element);
    selection.removeAllRanges();
    selection.addRange(range);
}

export function debounce(cb, time) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);

        timeout = setTimeout(function () {
            cb(...args);
        }, time);
    };
}

export function offsetTop(element) {
    if (!(element instanceof Element)) {
        throw new Error('Invalid argument to offsetTop()');
    }

    return element.offsetTop + (element.offsetParent ? offsetTop(element.offsetParent) : 0);
}
