const	c_play = "./templates/default/images/control_play.png",
	c_pause = "./templates/default/images/control_pause.png",
	flv = "./templates/default/images/flv.gif",
	URL = window.location.pathname+'?download=file&file=';

var	prevel,nextel,file,
	curAudio = sound1,
	idTimeout = null;

$('audio').bind('contextmenu',function(e){return false;});

function playnext(nextAudio)
{
    if (nextel) {
	if (prevel && prevel != nextel) {
	    prevel.src = flv;
	}
	nextel.src = c_pause;
	var el = prevel = nextel;
	nextel = null;
	curAudio = nextAudio;
	idTimeout = setTimeout(() => { curAudio.play()
	    .then(() => {
		if (file.getAttribute('name')=="playsoundcallee") return;
		nextAudio = (curAudio==sound2)?sound1:sound2;
		el = el.closest('.item');
		while ((el = el.nextElementSibling)) {
		    file = el.querySelector('input');
		    if (file && file.value=='') {
			file = null;
			continue;
		    }
		    nextAudio.src = URL+file.value;
		    nextel = el.querySelector('img');
		    if (nextel) break;
		}})
	    .catch(error => {
		prevel.src = c_play;
		setTimeout(() => {alert(error.name+' in '+file.value+': '+error.message)},10);
	    });
	},(file.getAttribute('name')=="playsoundcallee")? 0 : prevel.closest('.soundFlex').querySelector('select').value*1000);
    } else {
	prevel.src = c_play;
	idTimeout = setTimeout(()=>{prevel.src=flv},5000);
    }
}

function Visi(el){el.nextElementSibling.style.visibility=(el.value)?'visible':'hidden'}

function GreetPlay(el)
{
    if (idTimeout) clearTimeout(idTimeout);
    idTimeout = null;
    if (prevel != el || curAudio.paused)
    {
	curAudio.pause();
	file = el.parentElement.previousElementSibling;
	var src = URL+file.value;
	if (curAudio.getAttribute("src")!=src) curAudio.src = src;
	nextel = el;
	playnext(curAudio);
    } else {
	curAudio.pause();
	el.src = c_play;
    }
}
