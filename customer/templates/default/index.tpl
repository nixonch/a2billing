<!DOCTYPE html>
{php} header('X-Frame-Options: SAMEORIGIN'); {/php}
<html>
<head>
    <link rel="shortcut icon" href="{$FAVICONPATH}"/>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Sign in or Register | {$CCMAINTITLE}</title>
    <link rel="stylesheet" href="templates/default/css/index.css" type="text/css">
</head>
<body class="bodygradient">
<script>
var emptyemail = "{php} echo gettext("You must enter an email address!"){/php}";
var emptylogin = "{php} echo gettext("AUTHENTICATION REFUSED :<br>please check your login/password!");{/php}";
var noservice = "{php} echo gettext("Service temporally not available.<br>Try again later.");{/php}";
</script>
  <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700' rel='stylesheet' type='text/css'>

<div class="container">
  <div class="frameback lgrad">
  </div>
  <div class="frame">
    <div class="nav">
      <ul class="links">
        <li class="signin-active"><a class="btn_in">{php} echo gettext("Sign in");{/php}</a></li>
{php}if(SIGNUPENABLE){{/php}
        <li class="signup-inactive"><a class="btn_up" style="color: rgba(255,255,255,.3)">{php} echo gettext("Sign up");{/php}</a></li>
{php}}{/php}
      </ul>
      <select name="ui_language" id="ui_language">
                        <option value="english" {php} if(LANGUAGE=="english") echo "selected";{/php} >English</option>
                        <option value="ukrainian" {php} if(LANGUAGE=="ukrainian") echo "selected";{/php} >Українська</option>
                        <option value="russian" {php} if(LANGUAGE=="russian") echo "selected";{/php} >Русский</option>
                        <option value="german" {php} if(LANGUAGE=="german") echo "selected";{/php} >Deutsch</option>
{if (false)}
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/es.gif);" value="spanish" {php} if(LANGUAGE=="spanish") echo "selected";{/php} >Spanish</option>Român
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/fr.gif);" value="french" {php} if(LANGUAGE=="french") echo "selected";{/php} >French</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/fi.gif);" value="finnish" {php} if(LANGUAGE=="finnish") echo "selected";{/php} >Finnish</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/pt.gif);" value="portuguese" {php} if(LANGUAGE=="portuguese") echo "selected";{/php} >Portuguese</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/br.gif);" value="brazilian" {php} if(LANGUAGE=="brazilian") echo "selected";{/php}>Brazilian</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/it.gif);" value="italian" {php} if(LANGUAGE=="italian") echo "selected";{/php} >Italian</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/cn.gif);" value="chinese" {php} if(LANGUAGE=="chinese") echo "selected";{/php} >Chinese</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/ro.gif);" value="romanian" {php} if(LANGUAGE=="romanian") echo "selected";{/php} >Romanian</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/pl.gif);" value="polish" {php} if(LANGUAGE=="polish") echo "selected";{/php} >Polish</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/tr.gif);" value="turkish" {php} if(LANGUAGE=="turkish") echo "selected";{/php} >Turkish</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/pk.gif);" value="urdu" {php} if(LANGUAGE=="urdu") echo "selected";{/php} >Urdu</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/gr.gif);" value="greek" {php} if(LANGUAGE=="greek") echo "selected";{/php} >Greek</option>
                        <option style="background-image:url(templates/{$SKIN_NAME}/images/flags/id.gif);" value="indonesian" {php} if(LANGUAGE=="indonesian") echo "selected";{/php} >Indonesian</option>
{/if}
      </select>
    </div>
    <div ontouchstart="turn_start(event);" ontouchmove="turn_page(event);">

      <div class="forms">
	<form class="form-forgot" method="post" name="formforgot">
          <div class="login-title" id="warningforgot"></div>
          <label for="pr_email">{php} echo gettext("E-Mail");{/php}</label>
          <input class="form-styling" type="email" name="pr_email" id="pr_email"/>
          <a class="btn-submit btn-forgot">{php} echo gettext("Get password");{/php}</a>
	</form>
	<form class="form-signin" action="userinfo" method="post" name="formsignin">
          <div class="login-title" id="warningsignin"></div>
          <label for="pr_login">{php} echo gettext("Login / E-mail");{/php}</label>
          <input class="form-styling" type="text" name="pr_login" id="pr_login"/>
          <label for="pr_password">{php} echo gettext("Password");{/php}</label>
          <input class="form-styling" type="password" name="pr_password" id="pr_password"/>
          <input type="checkbox" id="checkbox" class="checkbox"/>
          <label for="checkbox"><span class="ui"></span>{php} echo gettext("Keep me signed in");{/php}</label>
          <a class="btn-submit btn-signin">{php} echo gettext("Login");{/php}</a>
          <div class="forgot">
            <a>{php} echo gettext("Forgot your password?");{/php}</a>
          </div>
	</form>
{php}if(SIGNUPENABLE){{/php}
	<form class="form-signup" action="" method="post" name="formsignup">
          <input type="hidden" name="country" id="country">
          <input type="hidden" name="id_timezone" id="id_timezone">
          <div class="login-title" id="warningsignup"></div>
          <label for="fullname">{php} echo gettext("Full name");{/php}</label>
          <input class="form-styling" type="text" name="fullname" id="fullname"/>
          <label for="r_email">{php} echo gettext("E-Mail");{/php}</label>
          <input class="form-styling" type="email" name="r_email" id="r_email"/>
          <label for="pr_country">{php} echo gettext("COUNTRY");{/php}</label>
          <div class="select-styling">
{php}
		global $countrycode;
		$country = $countrycode;
		$DBHandle_max = DbConnect();
		$instance_table = new Table("cc_country");
		$QUERY = "SELECT countrycode, countryname, countryprefix FROM cc_country";
		$countrylist = $instance_table -> SQLExec ($DBHandle_max, $QUERY);
		if ($countrylist) {
		    foreach ($countrylist AS $value) {
			if ($value[0]==$countrycode) {
			    $country = $value[1].($value[2]!="0"?' +'.$value[2]:"");
			    break;
			}
		    }
		}
{/php}
	    <input class="form-styling" type="text" value="{php} echo $country;{/php}" dataval = "{php} echo $countrycode;{/php}" name="pr_country" id="pr_country" onfocus="this.blur()" onclick="this.blur()" autocomplete="beseder"/>
	    <ul id="countrylist">
{php}
		if ($countrylist) {
		    foreach ($countrylist AS $value) {
			echo '<li><a href="#" class="countryselect" dataval="'.$value[0].'">'.$value[1].($value[2]!="0"?' +'.$value[2]:"").'</a></li>'.PHP_EOL;
		    }
		}
{/php}
	    </ul>
          </div>
          <label for="timezone">{php} echo gettext("TIMEZONE");{/php}</label>
          <div class="select-styling">
	    <input class="form-styling" type="text" value="{$curzonename}" dataval = "{$curzonecode}" name="timezone" id="timezone" onfocus="this.blur()" onclick="this.blur()"/>
	    <ul id="zonelist">
{php}
		global $timezone_list;
		if ($timezone_list) {
		    foreach ($timezone_list AS $value) {
			echo '<li><a href="#" class="zoneselect" dataval="'.$value[1].'">'.$value[0].'</a></li>'.PHP_EOL;
		    }
		}
{/php}
	    </ul>
          </div>
	  <label style="text-transform:none;padding-bottom:0">{php}echo gettext('By creating this account, you agree to our <a href="terms">Terms</a> and <a href="policy">Privacy Policy</a>.'){/php}</label>
	  <a class="btn-submit btn-signup">{php} echo gettext("Agree and Create Account");{/php}</a>
	</form>
{php}}{/php}
      </div>
{if (false)}
      <div class="success">
              <svg width="270" height="270" xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60" id="check" ng-class="checked ? 'checked' : ''">
                 <path fill="#ffffff" d="M40.61,23.03L26.67,36.97L13.495,23.788c-1.146-1.147-1.359-2.936-0.504-4.314
                  c3.894-6.28,11.169-10.243,19.283-9.348c9.258,1.021,16.694,8.542,17.622,17.81c1.232,12.295-8.683,22.607-20.849,22.042
                  c-9.9-0.46-18.128-8.344-18.972-18.218c-0.292-3.416,0.276-6.673,1.51-9.578" />
                <div class="successtext">
                   <p> Thanks for signing up! Check your email for confirmation.</p>
                </div>
      </div>
{/if}
    </div>
  </div>
  <div>
  <a id="refresh" value="Refresh" onClick="opback()">
    <svg class="refreshicon"   version="1.1" id="Capa_1"  xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
         width="25px" height="25px" viewBox="0 0 322.447 322.447" style="enable-background:new 0 0 322.447 322.447;"
         xml:space="preserve">
         <path  d="M321.832,230.327c-2.133-6.565-9.184-10.154-15.75-8.025l-16.254,5.281C299.785,206.991,305,184.347,305,161.224
                c0-84.089-68.41-152.5-152.5-152.5C68.411,8.724,0,77.135,0,161.224s68.411,152.5,152.5,152.5c6.903,0,12.5-5.597,12.5-12.5
                c0-6.902-5.597-12.5-12.5-12.5c-70.304,0-127.5-57.195-127.5-127.5c0-70.304,57.196-127.5,127.5-127.5
                c70.305,0,127.5,57.196,127.5,127.5c0,19.372-4.371,38.337-12.723,55.568l-5.553-17.096c-2.133-6.564-9.186-10.156-15.75-8.025
                c-6.566,2.134-10.16,9.186-8.027,15.751l14.74,45.368c1.715,5.283,6.615,8.642,11.885,8.642c1.279,0,2.582-0.198,3.865-0.614
                l45.369-14.738C320.371,243.946,323.965,236.895,321.832,230.327z"/>
    </svg>
  </a>
  </div>
  <div class="footer">
{if (false)}<div style="color: #BD2A15; font-size: 11px; text-align:center;">{$COPYRIGHT}</div>{/if}
{php}
    $DBHandle = DbConnect();
    $instance_table = new Table();
    $QUERY = "SELECT configuration_key FROM cc_configuration where configuration_key in ('MODULE_PAYMENT_AUTHORIZENET_STATUS','MODULE_PAYMENT_PAYPAL_BASIC_STATUS','MODULE_PAYMENT_MONEYBOOKERS_STATUS','MODULE_PAYMENT_WORLDPAY_STATUS','MODULE_PAYMENT_PLUGNPAY_STATUS','MODULE_PAYMENT_WM_STATUS') AND configuration_value='True'";
    $payment_methods = $instance_table->SQLExec($DBHandle, $QUERY);
    $QUERY = "SELECT configuration_value FROM cc_configuration where configuration_key='MODULE_PAYMENT_WM_WMID'";
    $wmid = $instance_table->SQLExec($DBHandle, $QUERY);
    $show_logo = '';
    for ($index = 0; $index < sizeof($payment_methods); $index++) {
	if ($payment_methods[$index][0] == "MODULE_PAYMENT_PAYPAL_BASIC_STATUS") {
	    $show_logo .= '<a href="https://www.paypal.com/en/mrb/pal=PGSJEXAEXKTBU" target="_blank"><img src="' . KICON_PATH . '/payments_paypal.gif" alt="Paypal"/></a>' . ' &nbsp; ';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_MONEYBOOKERS_STATUS") {
	    $show_logo .= '<a href="https://www.moneybookers.com/app/?rid=811621" target="_blank"><img src="' . KICON_PATH . '/moneybookers.gif" alt="Moneybookers"/></a>' . ' &nbsp; ';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PLUGNPAY_STATUS") {
	    $show_logo .= '<a href="http://www.plugnpay.com/" target="_blank"><img src="' . KICON_PATH . '/plugnpay.png" alt="plugnpay.com"/></a>' . ' &nbsp; ';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS") {
	    $show_logo .= '<a href="http://www.wmtransfer.com/" target="_blank"><img src="' . KICON_PATH . '/webmoney_virified.png" alt="WebMoney"/></a>' . ' &nbsp; ' . '<a href="https://passport.webmoney.ru/asp/certview.asp?wmid='.$wmid[0][0].'" target="_blank"><img src="' . KICON_PATH . '/v_blue_on_transp_en.png" alt="Check the seller\'s WMID" border="0" /></a>';
	}
    }

//    $show_logo .= ' <a href="http://www.gnu.org/licenses/agpl.html" target="_blank"><img src="' . KICON_PATH . '/agplv3-155x51.png" alt="AGPLv3"/></a> &nbsp; ';
{/php}

    <table style="width:100%;margin:0 auto;" align="center">
    <tr><td valign="top" align="center" class="tableBodyRight">{php} echo $show_logo;{/php}
    </td></tr></table>
  </div>
</div>
{php}if(!isset($_COOKIE["cookiescript"])) {{/php}
<div id="cookiescript_container">
    <div id="cookiescript_wrapper">
	<span id="cookiescript_header">{php} echo gettext("This website uses cookies"){/php}</span>
{php}if(LANGUAGE=="russian"){{/php}
	&nbsp;&nbsp;&nbsp;Информируем, что на этом сайте используются cookie-файлы. Cookie-файлы используются для выполнения идентификации пользователя и накапливания данных о посещении сайта. Продолжая пользоваться этим веб-сайтом, Вы соглашаетесь на сбор и использование данных cookie-файлов на Вашем устройстве. Свое согласие Вы в любой момент можете отозвать, удалив сохраненные cookie-файлы.
{php}}elseif(LANGUAGE=="ukrainian"){{/php}
	&nbsp;&nbsp;&nbsp;Інформуємо, що на цьому сайті використовуються cookie-файли. Cookie-файли використовуються для виконання ідентифікації користувача і накопичення даних про відвідування сайту. Продовжуючи користуватися цим веб-сайтом, Ви погоджуєтесь на збір і використання даних cookie-файлів на Вашому пристрої. Свою згоду Ви в будь-який момент можете відкликати, видаливши збережені cookie-файли.
{php}}else{{/php}
	&nbsp;&nbsp;&nbsp;We use cookies to ensure you have the best browsing experience on our website. By using our site, you acknowledge that you have read and understood our <a href="policy">Privacy Policy</a>.
{php}}{/php}<br>
	<div id="cookiescript_buttons">
	    <div id="cookiescript_accept" onClick="closeCookieScript();">{php} echo gettext("Got it!"){/php}</div>
	</div>
    </div>
</div>
<script>
function closeCookieScript() {
    $("#cookiescript_container").remove();
    var expiryDate = new Date();
    expiryDate.setMonth(expiryDate.getMonth() + 3);
    document.cookie = "cookiescript=set;expires="+expiryDate.toGMTString()+";path=/;";
}
</script>
{php}}{/php}
<script src="./javascript/jquery/jquery-1.7.2.min.js"></script>
<script src="./javascript/index.js"></script>
{if ($error >= 5)}
<script language="JavaScript">
$(document).ready(function() {
$(".forgot a").click();
});
</script>
{/if}
</body>
</html>
