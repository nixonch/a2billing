(function () {
    try {
        var frame = window.frameElement;
        if (!frame || frame.id !== 'alert-iframe') return;
        var alertBox = frame.closest('#inline-alert');
        if (!alertBox) return;
        var header = alertBox.querySelector('#inline-alert-headerbar');
        var color = window.parent.getComputedStyle(header || alertBox)[header ? 'backgroundColor' : 'borderTopColor'];
        document.documentElement.style.setProperty('--alert-button-color', color);
        document.documentElement.classList.add('alert-base-popup');
    } catch (e) {
        // Other frames and standalone pages keep their normal form styles.
    }
}());
