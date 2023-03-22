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

function openURL(theLINK,emptytextAlert,emptynameAlert,play)
{
var	langlocale   = document.theForm.langlocale.value,
	voicename    = document.theForm.voicename.value,
	gender       = this.voicename.options[this.voicename.selectedIndex].getAttribute('second-value'),
	greettext    = document.theForm.greettext.value,
	greetname    = document.theForm.greetname.value,
	speakingRate = document.theForm.speakingRate.value;

	if (greettext=='' && emptytextAlert){
		alert(emptytextAlert);
		return;
	}
	if (greetname=='' && emptynameAlert){
		alert(emptynameAlert);
		return;
	}

	goURL = langlocale + "&voicename=" + voicename + "&gender=" + gender + "&play=" + play + "&speakingRate=" + speakingRate + "&greetname=" + encodeURIComponent(greetname) + "&greettext=" + encodeURIComponent(greettext);
	
	self.location.href = theLINK + goURL;
	
	return false;
}
