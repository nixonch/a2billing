<!DOCTYPE html>
{php} header('X-Frame-Options: SAMEORIGIN'); {/php}
<html class="no-js" lang="en">
<head>
    <link rel="shortcut icon" href="{$FAVICONPATH}"/>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{$SECONDARY_TITLE} | {$CCMAINTITLE}</title>
    <link rel="stylesheet" href="templates/default/css/style-nav.css">
    <link rel="stylesheet" href="templates/default/css/left-nav-style.css">
</head>
<body class="bodygradient">
<input type="checkbox" id="navi-toggle" hidden>
<nav class="navi">
  <label for="navi-toggle" class="navi-toggle" onclick=""></label>
  <ul>
    <li><h4>Main</h4></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}/">Sign-in/up</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}terms">Terms & Conditions</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}policy">Privacy Policy</a></li>
    <li><h4>Knowledge base</h4></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}AsteriskWebRTC">WebRTC setup in Asterisk</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}APICheckers">Own Checking Web Service</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}how-to-CRM">Integration with CRM</a></li>
  </ul>
</nav>
<div class="container" onClick="if ($('#navi-toggle').prop('checked') && event.target.tagName!='A') document.getElementById('navi-toggle').checked=false">
  <div class="footer_icons">
    <div style="text-align:center">
{php}
    $DBHandle = DbConnect();
    $instance_table = new Table();
    $QUERY = "SELECT configuration_key FROM cc_configuration where configuration_key in ('MODULE_PAYMENT_AUTHORIZENET_STATUS','MODULE_PAYMENT_PAYPAL_BASIC_STATUS','MODULE_PAYMENT_MONEYBOOKERS_STATUS','MODULE_PAYMENT_WORLDPAY_STATUS','MODULE_PAYMENT_PLUGNPAY_STATUS','MODULE_PAYMENT_WM_STATUS') AND configuration_value='True'";
    $payment_methods = $instance_table->SQLExec($DBHandle, $QUERY);
    $QUERY = "SELECT configuration_value FROM cc_configuration where configuration_key='MODULE_PAYMENT_WM_WMID'";
    $wmid = $instance_table->SQLExec($DBHandle, $QUERY);
    $show_logo = '';
    for ($index=0; $index<sizeof($payment_methods); $index++) {
	if ($payment_methods[$index][0] == "MODULE_PAYMENT_MONEYBOOKERS_STATUS") {
	    $show_logo .= '<a href="https://www.moneybookers.com/app/?rid=811621" target="_blank"><img src="' . KICON_PATH . '/moneybookers.gif" alt="Moneybookers"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PLUGNPAY_STATUS") {
	    $show_logo .= '<a href="http://www.plugnpay.com/" target="_blank"><img src="' . KICON_PATH . '/plugnpay.png" alt="plugnpay.com"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS") {
	    $show_logo .= '<a href="http://www.web.money/" target="_blank"><img src="' . KICON_PATH . '/webmoney_virified.png" alt="WebMoney"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PAYPAL_BASIC_STATUS") {
	    $show_logo .= '<a href="https://www.paypal.com/en/mrb/pal=PGSJEXAEXKTBU" target="_blank"><img src="' . KICON_PATH . '/payments_paypal.gif" alt="Paypal"/></a>';
	}
    }
//    $show_logo .= '<a href="http://www.gnu.org/licenses/agpl.html" target="_blank"><img src="' . KICON_PATH . '/agplv3-155x51.png" alt="AGPLv3"/></a>';
    echo $show_logo;
{/php}

    </div>
  </div>
  <aside class="ads_right">
  </aside>
