var lasttimeout = null;
var warningIDup = null;
var warningIDin = null;
var warningIDot = null;

$("#ui_language").change(function () {
    self.location.href= "?ui_language="+$("#ui_language option:selected").val();
});

function opback() {
	$(".frameback").toggleClass("lgrad lgrad2");
	$(".frame").toggleClass("opback");
	$(".ads_right").toggleClass("opback");
	$(".footer_icons").toggleClass("opback");
	$("main").toggleClass("opback");
	$("body").toggleClass("bodygradient");
}

$(function() {
    $(".btn_in").click(function() {
	$(".forms").removeClass("forms-right");
	$(".forms").removeClass("forms-left");
	$(".signin-active").removeClass("signin-inactive");
	$(".signup-inactive").removeClass("signup-active");
	$(".signup-inactive a").css('color','rgba(255,255,255,.3)');
	scrnum = 2;
    });
});

$(function() {
    $(".btn_up").click(function() {
	if(typeof r_email !== 'undefined' && validateEmail(pr_login.value) && r_email.value=='')
	    $('#r_email').val(pr_login.value);
	$(".forms").removeClass("forms-right");
	$(".forms").addClass("forms-left");
	$(".signup-inactive a").removeAttr('style');
	$(".signup-inactive").addClass("signup-active");
	$(".signin-active").addClass("signin-inactive");
	scrnum = 3;
    });
});

$(function() {
    $(".forgot a").click(function() {
	if(validateEmail(pr_login.value) && pr_email.value=='')
	    $('#pr_email').val(pr_login.value);
	$(".forms").addClass("forms-right");
	$(".signin-active").addClass("signin-inactive");
	$(".signup-inactive").removeClass("signup-active");
	if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i.test(navigator.userAgent)){}else{$('#pr_email').focus();}
	scrnum = 1;
    });
});

$('.select-styling').mouseenter(function () {
    $("input:focus,select:focus").blur();
});

$('.nav').mouseover(function () {
    $('.nav select:focus').blur();
});

$('body').on('click', '.countryselect', function () {
    $('#countrylist').css('display','none');
    $('#pr_country').val($(this).html());
    $('#pr_country').attr('dataval',$(this).attr('dataval'));
    $('.select-styling').mouseover();
    $('.select-styling').mouseleave();
    $("input").blur();
    setTimeout(function(){
	$('#countrylist').removeAttr('style');
    },350);
});
$('body').on('click', '.zoneselect', function () {
    $('#zonelist').css('display','none');
    $('#timezone').val($(this).html());
    $('#timezone').attr('dataval',$(this).attr('dataval'));
    $('.select-styling').mouseover();
    $('.select-styling').mouseleave();
    $("input").blur();
    setTimeout(function(){
	$('#zonelist').removeAttr('style');
    },350);
});

function validateEmail(email) {
  return /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/.test(email);
}

function validatePhone(phone) {
  var numb = phone.replace(/[\+\-\. _]/g, '');
  return /^\d{10,}$/.test(numb);
}

$('#pr_email').keydown(function(e){
    if(e.keyCode == 13) {
            $('.btn-forgot').click();
            return false;
    }
    return true;
});

$('body').on('click', '.btn-forgot', function () {
	var warning = $("#warningforgot");
	var vemail = validateEmail(pr_email.value);
	var vphone = validatePhone(pr_email.value);
	if(pr_email.value=="" || (!vemail && !vphone))
	{
	    clearTimeout(warningIDot);
	    warning.html(emptyemail);
	    warningIDot = setTimeout(function(){
		warning.html('');
	    },5000);
	    return false;
	}
	if (!vemail && vphone) pr_email.value = pr_email.value.replace(/[\+\-\. _]/g, '');
	if (lasttimeout) return false;
	clearTimeout(warningIDot);
	$(".btn-submit").css({'background-color':'#666666','color':'#999999'});
	$(".nav").addClass("nav-up");
	warning.html('');
	var url = location.origin+location.pathname;
	url = url.replace(".php","");
	url = url.replace("/index","");
	url = url.replace(/^\/+|\/+$/g, '');
	url += '/index';
	var body = 'action=email&pr_email='+pr_email.value;
	var xhttp = new XMLHttpRequest();
	xhttp.open("POST",url,true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.timeout = 5000;
	xhttp.onreadystatechange = function() {
	    if (xhttp.readyState == 4)
	    if (xhttp.status == 200) {
		var error = xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('error')[0].childNodes[0].nodeValue;
		var answer = xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('forgotString')[0].childNodes[0].nodeValue;
		if (error==5) {
		    $('.form-forgot').html('<div class="login-title"></div><div class="login-title">'+answer+'</div>');
		    lasttimeout = setTimeout(function(){
			$(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
			$(".nav").removeClass("nav-up");
			lasttimeout = null;
		    },4000);
		} else {
		    warning.html(answer);
		    lasttimeout = setTimeout(function(){
			$(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
			$(".nav").removeClass("nav-up");
			lasttimeout = null;
			warningIDot = setTimeout(function(){
			    warning.html('');
			},4000);
		    },4000);
		}
	    } else {
		warning.html(noservice);
		lasttimeout = setTimeout(function(){
		    warning.html('');
		    $(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
		    $(".nav").removeClass("nav-up");
		    lasttimeout = null;
		},4000);
	    }
	};
	xhttp.send(body);
	return false;
});

$(function() {
    $(".btn-signup").click(function() {
	var warning = $("#warningsignup");
	if(r_email.value=="" || !validateEmail(r_email.value))
	{
	    clearTimeout(warningIDup);
	    warning.html(emptyemail);
	    warningIDup = setTimeout(function(){
		warning.html('');
	    },5000);
	    return false;
	}
	if (lasttimeout) return false;
	clearTimeout(warningIDup);
	$(".btn-submit").css({'background-color':'#666666','color':'#999999'});
	$(".nav").addClass("nav-up");
	warning.html('');
	$('#country').val($('#pr_country').attr('dataval'));
	$('#id_timezone').val($('#timezone').attr('dataval'));
	var url = location.origin+location.pathname;
	url = url.replace(".php","");
	url = url.replace("/index","");
	url = url.replace(/^\/+|\/+$/g, '');
	url += '/signup';
	var body = 'form_action=add&types=short&email='+r_email.value+'&fullname='+fullname.value+'&country='+country.value+'&id_timezone='+id_timezone.value;
	var xhttp = new XMLHttpRequest();
	xhttp.open("POST",url,true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.timeout = 5000;
	xhttp.onreadystatechange = function() {
	  if (xhttp.readyState == 4)
	    if (xhttp.status == 200) {
		var error = xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('error')[0].childNodes[0].nodeValue;
		var answer= xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('forgotString')[0].childNodes[0].nodeValue;
		if (error==5) {
		    $('.form-signup').html('<div class="login-title"></div><div class="login-title">'+answer+'</div>');
		    lasttimeout = setTimeout(function(){
			$(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
			$(".nav").removeClass("nav-up");
			lasttimeout = null;
		    },4000);
		    $('#pr_login').val(xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('login')[0].childNodes[0].nodeValue);
		    $('#pr_password').val(xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('pass')[0].childNodes[0].nodeValue);
		} else {
		    warning.html(answer);
		    lasttimeout = setTimeout(function(){
			$(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
			$(".nav").removeClass("nav-up");
			lasttimeout = null;
			warningIDup = setTimeout(function(){
			    warning.html('');
			},4000);
		    },4000);
		}
	    } else {
		warning.html(noservice);
		lasttimeout = setTimeout(function(){
		    $(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
		    $(".nav").removeClass("nav-up");
		    lasttimeout = null;
		    warning.html('');
		},4000);
	    }
	};
	xhttp.send(body);
	return false;
    });
});

$('#pr_login,#pr_password').keydown(function(e){
    if(e.keyCode == 13) {
            $('.btn-signin').click();
            return false;
    }
    return true;
});

$('body').on('click', '.btn-signin', function () {
	var warning = $("#warningsignin");
	if(pr_password.value.length<6 || pr_login.value.length<6)
	{
	    warning.html(emptylogin);
	    warningIDin = setTimeout(function(){
		warning.html('');
	    },9000);
	    return false;
	}
	if (lasttimeout) return false;
	clearTimeout(warningIDin);
	$(".btn-submit").css({'background-color':'#666666','color':'#999999'});
	$(".nav").addClass("nav-up");
	warning.html('');
	var url = location.origin+location.pathname;
	url = url.replace(".php","");
	url = url.replace("/index","");
	url = url.replace(/^\/+|\/+$/g, '');
	url += '/userinfo';
	var body = 'done=submit_sig&pr_login='+pr_login.value+'&pr_password='+pr_password.value;
	var xhttp = new XMLHttpRequest();
	xhttp.open("POST",url,true);
	xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xhttp.timeout = 5000;
	xhttp.onreadystatechange = function() {
	  if (xhttp.readyState == 4)
	    if (xhttp.status == 200) {
		var errorForgot = xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('error')[0].childNodes[0].nodeValue;
		var answerForgot = xhttp.responseXML.getElementsByTagName('response')[0].getElementsByTagName('signinString')[0].childNodes[0].nodeValue;
		if (errorForgot==0) {
		    location.assign(url);
		    return false;
		} else {
		    warning.html(answerForgot);
		    lasttimeout = setTimeout(function(){
			$(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
			$(".nav").removeClass("nav-up");
			lasttimeout = null;
			warningIDin = setTimeout(function(){
			    warning.html('');
			},4000);
		    },4000);
		}
	    } else {
		warning.html(noservice);
		lasttimeout = setTimeout(function(){
		    warning.html('');
		    $(".btn-submit").css({'background-color':'#1059FF','color':'#FFFFFF'});
		    $(".nav").removeClass("nav-up");
		    lasttimeout = null;
		},5000);
	    }
	};
	xhttp.send(body);
	return false;
});

var touch_position; // Координата нажатия
var scrnum = 2;

function turn_start(event) {
    // При начальном нажатии получить координаты
    touch_position = event.touches[0].pageX;
}
function turn_page(event) {
    // При движении нажатия отслеживать направление движения
    if (touch_position==null) { return false; }
    var tmp_move = touch_position-event.touches[0].pageX;
    touch_position = null;
    // Сдвиг достаточный?
    if (Math.abs(tmp_move)<10) { return false; }
    if (tmp_move<0) {
        // Листаем вправо
	scrnum--;
    }
    else {
        // Листаем влево
	scrnum++;
    }
    if (scrnum<=1) $(".forgot a").click();
    else if (scrnum==2) $(".btn_in").click();
    else if (scrnum>=3) $(".btn_up").click();
    return false;
}
