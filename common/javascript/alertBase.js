/**
 * Base function to show an inline alert.
 *
 * Positioning:
 * posX / posY define the lower right corner of the alert box.  
 * If both are null, the alert is automatically centered in the viewport.
 *
 * Parameters:
 * @param {string}        contentHtml             HTML content rendered inside the alert window.
 * @param {string|null}   caption                 Optional caption text rendered in the header bar.
 * @param {boolean}       enableCornerClose       If true, renders the close corner and enables closing via ESC (no callbacks).
 * @param {boolean}       isModal                 If true, shows a modal overlay and prevents clicking outside.
 * @param {string}        type                    Visual type preset ('info', 'success', 'warning', 'danger') or a custom CSS color value (e.g. '#666666', 'rgb(0,0,0)').
 * @param {number|null}   posX                    X coordinate for positioning the alert.
 * @param {number|null}   posY                    Y coordinate for positioning the alert.
 * @param {string|null}   corner                  Corner to anchor the alert to ('UL', 'UR', 'BL', 'BR', null for centering).
 * @param {string}        refreshId
 * @param {number|null}   autoHideSeconds         Optional timeout to auto-close the alert.
 * @param {string|null}   mainButtonLabel         Caption for the primary button (default: "Schliessen").
 * @param {function|null} mainButtonCallback      Callback executed after closing via the primary button.
 * @param {string|null}   secondaryButtonLabel    Optional caption for a secondary button.
 * @param {function|null} secondaryButtonCallback Callback executed after clicking the secondary button.
 *
 * Example 1 (simple usage with positioning):
 *     alertBase(
 *         msg,
 *         "Information",
 *         true,
 *         true,                // modal behavior
 *         "info",              // type
 *         event.pageX,         // posX (lower right corner)
 *         event.pageY - 15,    // posY
 *         'BR'                 // Bottom right
 *     );
 *
 * Example 2 (two buttons with callback handlers):
 *     alertBase(
 *         "<b>Are you sure?</b><br>This action cannot be undone.",
 *         "Confirmation",
 *         false,
 *         true,                 // modal
 *         "warning",
 *         null, null, null,     // centered
 *         null,                 // no auto hide
 *         "OK",                 // main button caption
 *         function() {          // main button callback
 *             console.log("Primary button clicked");
 *         },
 *         "Cancel",             // secondary button caption
 *         function() {          // secondary button callback
 *             console.log("Secondary button clicked");
 *         }
 *     );
 */
var alertTimer = null;
var alertCloseHandler = null;
var alertKeyDownHandler = null;
var alertAnchorPageLeft = null;
var alertAnchorPageTop  = null;
function alertBase(
    contentHtml,
    caption = null,
    enableCornerClose = true,
    isModal = true,
    type = 'info',
    posX = null,
    posY = null,
    corner = null,
    refreshId = 'main-list',
    autoHideSeconds = null,
    mainButtonLabel = null,
    mainButtonCallback = null,
    secondaryButtonLabel = null,
    secondaryButtonCallback = null
)
{
	var initialPositionSetted = false;
	var suppressNextOutsideClose = false;
	var alertBox = document.getElementById('inline-alert');
	if (!alertBox) {
	    alertBox = document.createElement('div');
	    alertBox.id = 'inline-alert';
	    document.body.appendChild(alertBox);
	}
	if (contentHtml.indexOf('url:') === 0) {
	    var contentUrl = contentHtml.substring(4);
	    contentHtml =
		'<iframe ' +
		'src="' + contentUrl.replace(/"/g, '&quot;') + '" ' +
		'id="alert-iframe" ' +
		'scrolling="auto" ' +
		'loading="lazy"' +
		'></iframe>';
	}
	function clamp(value, min, max) {
	    if (value < min) return min;
	    if (value > max) return max;
	    return value;
	}
	function renderFromAnchor() {
	    var shiftXY = 35;
	    var fixedLeft = alertAnchorPageLeft - window.pageXOffset;
	    var fixedTop  = alertAnchorPageTop  - window.pageYOffset;
	    var viewportWidth  = document.documentElement.clientWidth  || window.innerWidth;
	    var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
	    var minLeft = shiftXY - alertBox.offsetWidth;
	    var maxLeft = viewportWidth - shiftXY;
	    var minTop = 50 - alertBox.offsetHeight;
	    var maxTop = viewportHeight - shiftXY;
	    if (maxLeft < minLeft) { maxLeft = minLeft; }
	    if (maxTop < minTop)   { maxTop  = minTop;  }
	    alertBox.style.left = clamp(fixedLeft, minLeft, maxLeft) + 'px';
	    alertBox.style.top  = clamp(fixedTop,  minTop,  maxTop)  + 'px';
	}
	function applyInitialPosition() {
	    var boxWidth  = alertBox.offsetWidth;
	    var boxHeight = alertBox.offsetHeight;
	    var viewportWidth  = document.documentElement.clientWidth  || window.innerWidth;
	    var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
	    if (corner) {
		switch(corner) {
		    case 'UL':
			alertAnchorPageLeft = posX || 0;
			alertAnchorPageTop  = posY ?? window.pageYOffset + Math.round((viewportHeight - boxHeight) / 2);
			break;
		    case 'UR':
			alertAnchorPageLeft = (posX || viewportWidth) - boxWidth;
			alertAnchorPageTop  = posY ?? window.pageYOffset + Math.round((viewportHeight - boxHeight) / 2);
			break;
		    case 'BL':
			alertAnchorPageLeft = posX || 0;
			alertAnchorPageTop  = (posY || viewportHeight) - boxHeight;
			break;
		    case 'BR':
			alertAnchorPageLeft = (posX || viewportWidth) - boxWidth;
			alertAnchorPageTop  = (posY || viewportHeight) - boxHeight;
			break;
		    default:
			alertAnchorPageLeft = window.pageXOffset + Math.round((viewportWidth  - boxWidth)  / 2);
			alertAnchorPageTop  = window.pageYOffset + Math.round((viewportHeight - boxHeight) / 2);
			break;
		}
	    } else {
		alertAnchorPageLeft = window.pageXOffset + Math.round((viewportWidth  - boxWidth)  / 2);
		alertAnchorPageTop  = window.pageYOffset + Math.round((viewportHeight - boxHeight) / 2);
	    }
	    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
	    if (alertAnchorPageTop + boxHeight > scrollTop + viewportHeight) {
		alertAnchorPageTop = scrollTop + viewportHeight - boxHeight;
	    }
	    if (alertAnchorPageTop < scrollTop) {
		alertAnchorPageTop = scrollTop;
	    }
	    renderFromAnchor();
	    alertBox.style.display = "block";
	    initialPositionSetted = true;
	}
	if (alertTimer) {
	    clearTimeout(alertTimer);
	    alertTimer = null;
	}
	var overlay = document.getElementById('inline-alert-overlay');
	if (!overlay) {
	    overlay = document.createElement('div');
	    overlay.id = 'inline-alert-overlay';
	    document.body.appendChild(overlay);
	}
	if (isModal) {
	    overlay.style.display = 'block';
	    overlay.style.pointerEvents = 'auto';
	} else {
	    overlay.style.display = 'none';
	    overlay.style.pointerEvents = 'none';
	}
	var headerColor;
	switch (type) {
	    case 'info':  headerColor = '#1e3a83'; break;
	    case 'danger':  headerColor = '#dc3545'; break;
	    case 'success': headerColor = '#28a745'; break;
	    case 'warning': headerColor = '#ff9900'; break;
	    default: headerColor = type; break;
	}
	alertBox.style.borderColor = headerColor;
	var captionHtml = '';
	if (caption || enableCornerClose) {
	    captionHtml =
		'<div id="inline-alert-headerbar" style="background:' + headerColor + ';">' +
		    (caption ?? '&nbsp;') +
		    (enableCornerClose
			? '<div id="inline-alert-closecorner">' +
				'<div id="inline-alert-closex">&#10005;</div>' +
			  '</div>'
			: ''
		    ) +
		'</div>';
	}
	var buttonsHtml = (mainButtonLabel)
	    ? '<div id="inline-alert-buttons">' +
		'<button type="button" id="inline-alert-main" style="background:' + headerColor + '; border:1px solid ' + headerColor + ';">' +
		    mainButtonLabel +
		'</button>' +
		(secondaryButtonLabel
		    ? '<button type="button" id="inline-alert-secondary">' +
			secondaryButtonLabel +
		      '</button>'
		    : ''
		) +
	    '</div>'
	    : '';
	var contentStyle = (buttonsHtml)
	    ? ((contentUrl) ? ' style="padding-bottom: 40px;"' : ' style="padding: 15px 15px 55px 15px;"')
	    : ((contentUrl) ? '' : ' style="padding: 15px;"');
	alertBox.innerHTML =
	    captionHtml +
	    '<div id="inline-alert-content"' + contentStyle + '>' + contentHtml + '</div>' +
	    buttonsHtml;
	var closeCorner = document.getElementById('inline-alert-closecorner');
	if (closeCorner) {
	    alertKeyDownHandler = function (e) {
		if (e.key === 'Escape' || e.key === 'Esc') {
		    e.preventDefault();
		    closeAlert();
		}
	    };
	    window.addEventListener('keydown', alertKeyDownHandler, true);
	    closeCorner.onclick = function (e) {
		e.preventDefault();
		e.stopPropagation();
		closeAlert();
	    };
	    closeCorner.onmousedown = function (e) {
		if (!isModal) {
		    suppressNextOutsideClose = true;
		}
		e.preventDefault();
		e.stopPropagation();
	    };
	}
	var iframe = document.getElementById('alert-iframe');
	if (iframe) {
	    iframe.onload = function () {
		try {
		    if (alertKeyDownHandler && !initialPositionSetted) {
			iframe.contentWindow.addEventListener('keydown', alertKeyDownHandler, true);
		    }
		    var viewportWidth  = document.documentElement.clientWidth  || window.innerWidth;
		    var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
		    alertBox.style.width = '';
		    iframe.style.height = '';
		    var doc = iframe.contentDocument || iframe.contentWindow.document;
		    var body = doc.body;
		    var html = doc.documentElement;
		    var contentWidth = Math.min(Math.max(body.scrollWidth, html.scrollWidth), viewportWidth);
		    var contentHeight = Math.min(Math.max(body.scrollHeight, html.scrollHeight), viewportHeight - 39);
		    iframe.style.height = contentHeight + 'px';
		    alertBox.style.width = contentWidth + 30 + 'px';
		    iframe.style.width = '100%';
		    if (initialPositionSetted) {
			var rect = alertBox.getBoundingClientRect();
			if (viewportHeight < rect.bottom) {
			    alertAnchorPageTop -= rect.bottom - viewportHeight;
			    renderFromAnchor();
			} else if (rect.top < 0) {
			    alertAnchorPageTop -= rect.top;
			    renderFromAnchor();
			}
		    }
		    try {
			doc.addEventListener('click', function (ev) {
			    var t = ev.target;
			    var clickable = t && t.closest ? t.closest('a,button,input,select,textarea,label,[role="button"]') : null;
			    if (clickable) { return; }
			    var r = alertBox.getBoundingClientRect();
			    var x = Math.max(1, Math.min((document.documentElement.clientWidth || window.innerWidth) - 1, Math.round(r.left + 10)));
			    var y = Math.max(1, Math.min((document.documentElement.clientHeight || window.innerHeight) - 1, Math.round(r.top + 10)));
			    try {
				alertBox.dispatchEvent(new MouseEvent('click', {
				    bubbles: true,
				    cancelable: true,
				    view: window,
				    clientX: x,
				    clientY: y
				}));
			    } catch (e) {}
			}, true);
		    } catch (e) {}
		} catch (e) {}
		if (!initialPositionSetted) {
		    applyInitialPosition();
		}
	    };
	    if (closeCorner) {
		alertBox.setAttribute('tabindex', '-1');
		setTimeout(function () {
		    try {
		        alertBox.focus({ preventScroll: true });
		    } catch (e) {
		        alertBox.focus();
		    }
		}, 0);
		alertBox.addEventListener('mousedown', function (e) {
		    var target = e.target;
		    if (target.tagName && target.tagName.toLowerCase() === 'iframe') {
		        return;
		    }
		    try {
		        alertBox.focus({ preventScroll: true });
		    } catch (e) {
		        alertBox.focus();
		    }
		});
	    }
/**
 * Handles incoming messages for specific actions to be performed on the parent page, received from the iframe.
 * Listens for the `message` event and processes commands such as closing the alert or updating content on the parent page.
 *
 * Example usage:
 *
 * // 1. Send a message to close the alert:
 * window.parent.postMessage({ action: 'closeAlert' }, '*');
 *
 * // 2. Send a message to refresh content on the parent page:
 * window.parent.postMessage({ action: 'refreshInside', class: 'listview', id: 'curelement', payload: 'new-content-url' }, '*');
 *
 * The 'refreshInside' action is used when the alert contains an iframe loaded with a URL using `url:` in `contentHtml`. 
 * It allows dynamically updating parts of the content on the parent page (not inside the alert) without reloading the entire parent page.
 *
 * `id`: The ID of the element on the parent page whose content needs to be updated. This should be an element on the page that opened the alert.
 * - For example, if the parent page has a section with `id="curelement"`, the content of that section will be updated.
 *
 * `class`: The class of the element on the parent page whose content needs to be updated.
 * - If both `id` and `class` are specified, the element with the specified `id` will be found inside the element with the specified `class`, including the class tag itself.
 * - If only `class` is specified, the first element with that class will be found.
 *
 * `payload`: This is the URL from which new content will be loaded to update the element on the parent page.
 * - **payload must be a URL**, from which a request will be made to fetch and load new content, which will update the content of the element with the specified `id` or `class`.
 * - If `payload` is not provided, the current URL of the parent page will be used.
 *
 * Example:
 *
 * 1. Suppose we load a page in the iframe inside the alert, using `url:`:
 *    ```javascript
 *    alertBase('url:https://example.com/page.html','Details',true,true,'#666666');
 *    ```
 *    This will load the page from `https://example.com/page.html` into the iframe inside the alert.
 *
 * 2. Later, you want to dynamically update content on the parent page by sending a message:
 *    ```javascript
 *    window.parent.postMessage({
 *        action: 'refreshInside',
 *        id: 'curelement', // ID of the element on the parent page
 *        class: 'listview', // Class of the element on the parent page
 *        payload: 'new-content-url' // New content URL
 *    }, '*');
 *    ```
 *    This will update the content of the element with `id="curelement"` inside the element with class `listview` on the parent page with the new content from the URL `new-content-url`.
 *    If `payload` is not provided, the current URL of the parent page will be used.
 *
 * @param {MessageEvent} e - The message event object containing the data.
 * @param {Object} e.data - The data sent with the message.
 * @param {string} e.data.action - The action to be performed: 'closeAlert' or 'refreshInside'.
 * @param {string} e.data.id - The ID of the element on the parent page whose content needs to be updated (used with 'refreshInside').
 * @param {string} e.data.class - The class of the element on the parent page whose content needs to be updated (used with 'refreshInside').
 * @param {string} e.data.payload - The URL to load new content and update the content on the parent page (used with 'refreshInside').
 */
	    var alertMessageHandler = function (e) {
		switch (e.data?.action) {
		    case 'closeAlert': closeAlert(); break;
		    case 'refreshInside':
			var id = e.data.id ?? null;
			var className = e.data.class ?? null;
			var link = e.data.payload ?? location.href;
			fetch(link, { credentials: 'same-origin' })
			    .then(r => r.text())
			    .then(html => {
				var tmp = document.createElement('div');
				tmp.innerHTML = html;
				var newPart = null;
				var oldPart = null;
				if (id && className) {
				    newPart = tmp.querySelector(`.${className} #${id}`);
				    if (newPart) { oldPart = document.querySelector(`.${className} #${id}`); }
				} else if (id) {
				    newPart = tmp.querySelector(`#${id}`);
				    if (newPart) { oldPart = document.getElementById(id); }
				} else if (className) {
				    newPart = tmp.querySelector(`.${className}`);
				    if (newPart) { oldPart = document.querySelector(`.${className}`); }
				} else {
				    newPart = tmp.querySelector(`#${refreshId}`);
				    if (!newPart) {
					refreshId = 'main-content';
					newPart = tmp.querySelector(`#${refreshId}`);
				    }
				    if (newPart) { oldPart = document.getElementById(refreshId); }
				}
				if (newPart && oldPart) { oldPart.innerHTML = newPart.innerHTML; }
			    });
			break;
		}
	    };
	    window.addEventListener('message', alertMessageHandler, false);
	} else {
	    alertBox.style.width = alertBox.offsetWidth + 'px';
	    applyInitialPosition();
	}
	window.addEventListener('resize', renderFromAnchor);
	window.addEventListener('scroll', renderFromAnchor);
	var headerBar = document.getElementById('inline-alert-headerbar');
	if (headerBar) {
	    headerBar.onmousedown = function (e) {
		var closeCorner = document.getElementById('inline-alert-closecorner');
		if (closeCorner && closeCorner.contains(e.target)) {
		    return;
		}
		e.preventDefault();
		var iframe = document.getElementById('alert-iframe');
		var oldIframePointerEvents = null;
		if (iframe) {
		    oldIframePointerEvents = iframe.style.pointerEvents;
		    iframe.style.pointerEvents = 'none';
		}
		var rect = alertBox.getBoundingClientRect();
		var offsetX = e.clientX - rect.left;
		var offsetY = e.clientY - rect.top;
		var dragMouseMoveHandler = function (moveEvent) {
		    suppressNextOutsideClose = true;
		    var fixedLeft = (moveEvent.clientX - offsetX);
		    var fixedTop  = (moveEvent.clientY - offsetY);
		    alertAnchorPageLeft = fixedLeft + window.pageXOffset;
		    alertAnchorPageTop  = fixedTop  + window.pageYOffset;
		    renderFromAnchor();
		};
		var dragMouseUpHandler = function () {
		    document.removeEventListener('mousemove', dragMouseMoveHandler);
		    document.removeEventListener('mouseup', dragMouseUpHandler);
		    if (iframe) {
			iframe.style.pointerEvents = oldIframePointerEvents || '';
		    }
		};
		document.addEventListener('mousemove', dragMouseMoveHandler);
		document.addEventListener('mouseup', dragMouseUpHandler);
	    };
	}
	function removeAllInstruments() {
	    if (alertTimer !== null) {
		clearTimeout(alertTimer);
		alertTimer = null;
	    }
	    window.removeEventListener('resize', renderFromAnchor);
	    window.removeEventListener('scroll', renderFromAnchor);
	    if (alertMessageHandler) {
		window.removeEventListener('message', alertMessageHandler, false);
		alertMessageHandler = null;
	    }
	    if (alertCloseHandler) {
		document.removeEventListener('click', alertCloseHandler, true);
		alertCloseHandler = null;
	    }
	    if (alertKeyDownHandler) {
		window.removeEventListener('keydown', alertKeyDownHandler, true);
		alertKeyDownHandler = null;
	    }
	}
	function closeAlert() {
	    alertBox.style.opacity = '0';
	    removeAllInstruments();
	    setTimeout(function () {
		if (alertBox && alertBox.parentNode) {
		    alertBox.parentNode.removeChild(alertBox);
		}
		if (overlay) {
		    overlay.style.display = 'none';
		    if (overlay.parentNode) {
			overlay.parentNode.removeChild(overlay);
		    }
		}
	    }, 150);
	}
	var mainButton = document.getElementById('inline-alert-main');
	if (mainButton) {
	    mainButton.onclick = function () {
		closeAlert();
		if (typeof mainButtonCallback === 'function') {
		    try {
			mainButtonCallback();
		    } catch (e) {}
		}
	    };
	}
	var secondaryButton = document.getElementById('inline-alert-secondary');
	if (secondaryButton) {
	    secondaryButton.onclick = function () {
		closeAlert();
		if (typeof secondaryButtonCallback === 'function') {
		    try {
			secondaryButtonCallback();
		    } catch (e) {}
		}
	    };
	}
	setTimeout(function () {
	    alertBox.style.opacity = '1';
	}, 0);
	if (typeof autoHideSeconds === 'number' && autoHideSeconds > 0) {
	    alertTimer = setTimeout(closeAlert, autoHideSeconds * 1000);
	}
	var alertCloseHandler = function(e) {
	    if (suppressNextOutsideClose) {
		suppressNextOutsideClose = false;
		return;
	    }
	    if (e.clientX < 0 || e.clientX > viewportWidth || e.clientY < 0 || e.clientY > viewportHeight) {
		return;
	    }
	    var el = e.target;
	    var inAlert = alertBox.contains(el);
	    var actionEl = null;
	    if (inAlert && el.closest) {
		actionEl = el.closest(
		    '#inline-alert-closecorner,' +
		    '#inline-alert-buttons button,' +
		    'a,button,input,select,textarea,label,[role="button"]'
		);
	    }
	    if (actionEl) {
		return;
	    }
	    var rect = alertBox.getBoundingClientRect();
	    var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
	    var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
	    if (alertBox.contains(el) && (rect.left < 0 || rect.top < 0 || rect.right > viewportWidth || rect.bottom > viewportHeight)) {
		alertAnchorPageLeft = Math.max(0, Math.min(rect.left, viewportWidth - alertBox.offsetWidth));
		alertAnchorPageTop = Math.max(0, Math.min(rect.top, viewportHeight - alertBox.offsetHeight));
		renderFromAnchor();
	    }
	    if (!isModal) {
		var clickable = el.closest ? el.closest('a,button') : null;
		if (!clickable) { clickable = el; }
		var onclickAttr = clickable.getAttribute ? (clickable.getAttribute('onclick') || '') : '';
		var hasAlertInOnclick = onclickAttr.toLowerCase().indexOf('alert') !== -1;
		if (!alertBox.contains(el)) {
		    if (!hasAlertInOnclick) {
			closeAlert();
		    } else {
			removeAllInstruments();
		    }
		}
	    }
	};
	document.addEventListener('click', alertCloseHandler, true);
}
