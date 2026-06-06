var prevel,
    Audio1 = $("#sound1")[0],
    Audio2 = $("#sound2")[0];

//$(document).ready(function(){
//	Audio2.bind('contextmenu',function() { return false; });
//});

Audio1.addEventListener("ended",function(){prevel.src="./templates/default/images/control_play.png";},true);
Audio2.addEventListener("playing",function(){if(prevel){Audio1.pause();prevel.src="./templates/default/images/control_play.png";}},true);

function GreetPlay(el, soundpath)
{
//	var el = document.getElementById(e);
	if (prevel != el || Audio1.paused)
	{
	    Audio2.pause();
	    Audio1.pause();
	    if (prevel) {
		if (prevel != el) Audio1.currentTime = 0;
		prevel.src = "./templates/default/images/flv.gif";
	    }
	    el.src = "./templates/default/images/control_pause.png";
	    if (prevel != el) Audio1.src = soundpath;
	    prevel = el;
	    Audio1.play();
	} else {
	    Audio1.pause();
	    Audio1.currentTime = 0;
	    el.src = "./templates/default/images/control_play.png";
	}
	return;
}

function setsrcaudio() {
    Audio2.src = soundpath2 + document.theForm.langlocale.value + '&voicename=' + document.theForm.voicename.value + '&speakingRate=' + document.theForm.speakingRate.value + '&play=1' + '&greettext=' + document.theForm.greettext.value;
    Audio2.currentTime = 0;
}

function main_change() {
    var
	val	   = document.theForm.langlocale.value,
	selector   = '#voicename option[main-value="' + val + '"]';

    $('#voicename option').removeAttr('selected').hide();
    $(selector).show();
    if (val==langfirst) { selector = '#voicename option[value="' + voicefirst + '"]'; }
    $(selector + ':first').attr('selected', 'selected');
    setsrcaudio();
}

$(function() {
    $('#langlocale').change(main_change);
    main_change();
});

function keytoDownNumber(e,id_el,pointAlert)
{
	if (e.keyCode!=13) {
		var key = (typeof e.charCode == 'undefined' ? e.keyCode : e.charCode);
		if (e.ctrlKey || e.altKey || key==32 || key==95 || (key>47 && key<58) || (key>64 && key<91) || (key>96 && key<123) || key==0)  {
			document.getElementById(id_el).style.color = "blue";
			return true;
		} else if (key==46) {
			alert(pointAlert);
			return false;
		}
		else  return false;
	}
	else  return true;
}

function keytoDownAny(e,id_el)
{
	if (e.keyCode!=13) {
		var key = (typeof e.charCode == 'undefined' ? e.keyCode : e.charCode);
		if (e.ctrlKey || e.altKey || key>=32 || key==0) {
			document.getElementById(id_el).style.color = "blue";
			return true;
		} else {
			return false;
		}
	}
	else  return true;
}

function openURL(theLINK, emptytextAlert, emptynameAlert, playOrParams)
{
    var form = document.theForm;

    var langlocale   = form.langlocale.value,
	voicename    = form.voicename.value,
	gender       = this.voicename.options[this.voicename.selectedIndex].getAttribute('second-value'),
	greettext    = form.greettext.value,
	greetname    = form.greetname.value,
	speakingRate = form.speakingRate.value;

	if (greettext === '' && emptytextAlert) {
	    alert(emptytextAlert);
	    return false;
	}
	if (greetname === '' && emptynameAlert) {
	    alert(emptynameAlert);
	    return false;
	}

    var params = new URLSearchParams();
	params.set('langlocale', langlocale);
	params.set('voicename', voicename);
	params.set('gender', gender);
	params.set('speakingRate', speakingRate);
	params.set('greetname', greetname);
	params.set('greettext', greettext);

    if (playOrParams !== undefined && playOrParams !== null) {
        if (typeof playOrParams === 'object') {
            Object.keys(playOrParams).forEach(function(key) {
                var val = playOrParams[key];

                if (val === undefined || val === null) { return; }

                if (Array.isArray(val)) {
                    val.forEach(function(item) {
                        if (item === undefined || item === null) { return; }
                        params.append(key, String(item));
                    });
                } else {
                    params.set(key, String(val));
                }
            });
        } else {
            params.set('play', String(playOrParams));
        }
    }

    self.location.href = theLINK + '?' + params.toString();
    return false;
}

function openURLAjax(theLINK, emptytextAlert, emptynameAlert, play, refreshTarget)
{
    var form = document.theForm;

    var langlocale   = form.langlocale.value,
	voicename    = form.voicename.value,
	gender       = this.voicename.options[this.voicename.selectedIndex].getAttribute('second-value'),
	greettext    = form.greettext.value,
	greetname    = form.greetname.value,
	speakingRate = form.speakingRate.value;

    if (greettext === '' && emptytextAlert) {
        alert(emptytextAlert);
        return false;
    }
    if (greetname === '' && emptynameAlert) {
        alert(emptynameAlert);
        return false;
    }

    var url = new URL(theLINK, window.location.href);

    var merged = new URLSearchParams(window.location.search);

    for (const [k, v] of url.searchParams.entries()) {
	merged.set(k, v);
    }

    merged.set('langlocale', langlocale);
    merged.set('voicename', voicename);
    merged.set('gender', gender);
    merged.set('play', String(play));
    merged.set('speakingRate', speakingRate);
    merged.set('greetname', greetname);
    merged.set('greettext', greettext);

    url.search = merged.toString();
    var link = url.toString();

    var id = refreshTarget?.id ?? null;
    var className = refreshTarget?.class ?? null;

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
                newPart = tmp.querySelector(`#main-content`);
                if (newPart) { oldPart = document.getElementById('main-content'); }
            }

            if (newPart && oldPart) {
                oldPart.innerHTML = newPart.innerHTML;
            }
        })
        .catch(err => {
            console.error('openURLAjax fetch failed', err);
        });
    return false;
}
