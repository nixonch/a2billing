function MM_openBrWindow(theURL, winName, features) {
    const params = {};
    features.split(',').forEach(pair => {
        const [key, value] = pair.split('=').map(s => s.trim());
        if (key && value) params[key] = value;
    });

    const totalWidth = parseInt(params.width) || 600;
    const totalHeight = parseInt(params.height) || 400;
    const left = (window.innerWidth - totalWidth) / 2;
    const top = (window.innerHeight - totalHeight) / 2;

    const headerHeight = 30;
    const iframeHeight = totalHeight - headerHeight;

    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = 0;
    overlay.style.left = 0;
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
    overlay.style.zIndex = 9999;

    const modal = document.createElement('div');
    modal.style.position = 'absolute';
    modal.style.top = top + 'px';
    modal.style.left = left + 'px';
    modal.style.width = totalWidth + 'px';
    modal.style.height = totalHeight + 'px';
    modal.style.borderRadius = '8px';
    modal.style.overflow = 'hidden';
    modal.style.boxShadow = '0 4px 16px rgba(0,0,0,0.3)';
    modal.style.display = 'flex';
    modal.style.flexDirection = 'column';

    // Прозрачный отступ сверху
    const header = document.createElement('div');
    header.style.height = headerHeight + 'px';
    header.style.background = 'rgba(255, 255, 255, 0.8)';
    header.style.position = 'relative';

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '✖';
    closeBtn.style.position = 'absolute';
    closeBtn.style.top = '5px';
    closeBtn.style.right = '10px';
    closeBtn.style.zIndex = '10000';
    closeBtn.style.background = 'transparent';
    closeBtn.style.border = 'none';
    closeBtn.style.fontSize = '16px';
    closeBtn.style.cursor = 'pointer';
    closeBtn.onclick = () => document.body.removeChild(overlay);

    header.appendChild(closeBtn);

    const iframe = document.createElement('iframe');
    iframe.src = theURL;
    iframe.style.width = '100%';
    iframe.style.height = iframeHeight + 'px';
    iframe.style.border = 'none';
    iframe.style.flexGrow = '1';

    modal.appendChild(header);
    modal.appendChild(iframe);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // === Сделать окно перетаскиваемым по заголовку ===
    let isDragging = false;
    let offsetX = 0, offsetY = 0;

    header.style.cursor = 'move';

    header.addEventListener('mousedown', function(e) {
        isDragging = true;
        offsetX = e.clientX - modal.offsetLeft;
        offsetY = e.clientY - modal.offsetTop;
        document.body.style.userSelect = 'none'; // отключить выделение текста
    });

    document.addEventListener('mousemove', function(e) {
        if (isDragging) {
            modal.style.left = (e.clientX - offsetX) + 'px';
            modal.style.top = (e.clientY - offsetY) + 'px';
        }
    });

    document.addEventListener('mouseup', function() {
        isDragging = false;
        document.body.style.userSelect = '';
    });

    return null;
}



function old_MM_openBrWindow(theURL,winName,features) {
	return window.open(theURL,winName,features);
}
