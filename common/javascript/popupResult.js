var sendValueCallback = function (payload) {
    if (payload.type !== 'sendValue') { return false; }

    if (!payload.formname || !payload.fieldname) { return false; }
    var form = document.forms[payload.formname];
    if (!form) { return false; }
    var field = form.elements[payload.fieldname];
    if (!field) { return false; }
    field.value = payload.selvalue;
    if (payload.othervalue !== null && payload.othervalue !== undefined) {
	if (form.elements.accountcode) {
	    form.elements.accountcode.value = payload.othervalue;
	} else if (document.getElementById('tzc')) {
	    document.getElementById('tzc').innerHTML = payload.othervalue;
	    if (document.getElementById('dest')) {
		document.getElementById('dest').disabled = false;
	    }
	}
    }
    return true;
};

var refreshInsideCallback = function (payload) {
    if (payload.type !== 'refreshInside') { return false; }

    var id = payload.id ?? null;
    var className = payload.className ?? null;
var link = payload.link ?? location.href;
var removeParams = [
    'popup_select',
    'add_callerid',
    'idcust',
];

try {
    var linkUrl = new URL(link, location.origin);
    link = linkUrl.pathname + linkUrl.hash;
} catch (err) {}

var formData = new FormData();

var addFormData = function (prefix, value) {
    if (value !== null && typeof value === 'object') {
	for (var key in value) {
	    if (Object.prototype.hasOwnProperty.call(value, key)) {
		addFormData(prefix ? prefix + '[' + key + ']' : key, value[key]);
	    }
	}
    } else {
	formData.append(prefix, value);
    }
};

if (typeof refreshProcessed === 'object' && refreshProcessed !== null) {
    for (var key in refreshProcessed) {
	if (
	    Object.prototype.hasOwnProperty.call(refreshProcessed, key)
	    && removeParams.indexOf(key) === -1
	) {
	    addFormData(key, refreshProcessed[key]);
	}
    }
}

fetch(link, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
})
	.then(r => r.text())
	.then(html => {
	    var tmp = document.createElement('div');
	    tmp.innerHTML = html;

	    var newPart = null;
	    var oldPart = null;

	    if (id && className) {
		newPart = tmp.querySelector(`.${className} #${id}`);
		if (newPart) {
		    oldPart = document.querySelector(`.${className} #${id}`);
		}
	    } else if (id) {
		newPart = tmp.querySelector(`#${id}`);
		if (newPart) {
		    oldPart = document.getElementById(id);
		}
	    } else if (className) {
		newPart = tmp.querySelector(`.${className}`);
		if (newPart) {
		    oldPart = document.querySelector(`.${className}`);
		}
	    } else {
		var refreshId = 'main-list';
		newPart = tmp.querySelector(`#${refreshId}`);
		if (newPart) {
		    oldPart = document.getElementById(refreshId);
		}
	    }

	    if (newPart && oldPart) { oldPart.innerHTML = newPart.innerHTML; }
	});

    // Keep alert open, same as the old refreshInside case
    return false;
};