(function () {
    'use strict';

    // Keep upstream styles/scripts isolated while using the page's own scrolling.
    var frame = document.getElementById('phpsysinfo');
    if (!frame) return;

    var body = null;
    var observer = null;
    var pending = false;

    function resize() {
        pending = false;
        if (!body) return;
        // Unlike scrollHeight, the body's natural height can also shrink after
        // collapsing a section, switching languages or changing the window width.
        var style = frame.contentWindow.getComputedStyle(body);
        var height = Math.ceil(body.getBoundingClientRect().height +
            (parseFloat(style.marginTop) || 0) + (parseFloat(style.marginBottom) || 0));
        height = Math.max(1, height);
        if (frame.style.height !== height + 'px') frame.style.height = height + 'px';
    }

    function schedule() {
        if (!pending) {
            pending = true;
            window.requestAnimationFrame(resize);
        }
    }

    function connect() {
        if (observer) observer.disconnect();
        var doc = frame.contentDocument;
        body = doc && doc.body;
        if (!body) return;

        var style = doc.createElement('style');
        style.textContent = 'html,body{height:auto!important;min-height:0!important;' +
            'overflow:hidden!important;background:transparent!important;}';
        doc.head.appendChild(style);

        if (window.ResizeObserver) {
            observer = new ResizeObserver(schedule);
            observer.observe(body);
        } else if (window.MutationObserver) {
            observer = new MutationObserver(schedule);
            observer.observe(body, {childList: true, subtree: true, attributes: true, characterData: true});
        }
        doc.addEventListener('load', schedule, true);
        schedule();
    }

    frame.addEventListener('load', connect);
    window.addEventListener('resize', schedule);
    if (frame.contentDocument && frame.contentDocument.readyState === 'complete') connect();
}());
