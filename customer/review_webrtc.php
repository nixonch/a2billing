<?php

$disable_load_conf = true;

include ("lib/customer.defines.php");

getpost_ifset(array ('error', 'action', 'review', 'email', 'answer', 'news', 'i', 'e', 'a', 'm'));

function sendForgot($error,$forgotString) {
	header("Content-type: text/xml");
	echo "<response><error>$error</error><forgotString><![CDATA[$forgotString]]></forgotString></response>";
	die();
}

$zippostcode = '';
//      -= Need to install GeoIP http://ua2.php.net/manual/en/geoip.setup.php =-
if (function_exists('geoip_db_avail') && (geoip_db_avail(GEOIP_REGION_EDITION_REV0) || geoip_db_avail(GEOIP_REGION_EDITION_REV1))) {
    $countryregion = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
    $countryname = $countryregion['country_name'];
    $city	 = $countryregion['city'];
//  $zippostcode = "value='".$countryregion['postal_code']."'"; // ZIP/POSTAL CODE
} else {
    $countryname = '';
    $city	 = '';
}

$uuid   = (isset($i) && $i!='' && $i!='null' && $i!='undefined') ? preg_replace('/[^-\da-zA-Z]/', '', $i) : '';
$cookie = (isset($_COOKIE['SIPSESSION']) && $_COOKIE['SIPSESSION']!='') ? preg_replace('/[^-\da-zA-Z]/', '', $_COOKIE['SIPSESSION']) : '';

$DBHandle = DbConnect();
$param_update = $letter = $mailing = '';
$reply = $fornews = '';
if (isset ($action)) {
	if (!isset ($_SESSION["date_forgot"]) || (time() - $_SESSION["date_forgot"]) > 30) {
		$_SESSION["date_forgot"] = time();
	} else {
		sendForgot(9,gettext("Please wait 1 minute before making any other request!"));
	}
	if ($action == "review") {
	    if (isset ($email) || isset ($review)) {
		if ($email) {
		    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
		    if ($email===false) {
			sendForgot(7,gettext("Please provide your valid email address"));
		    }
		}
		if ((strlen($review) > 0 || strlen($email) > 0) && is_numeric($news) && is_numeric($answer)) {
		    $table = new Table('cc_feedback', "operation, country, city, fromip, fromagent, cookie, letter, email, reply, notify");
		    $result = $table -> SQLExec ($DBHandle, "SELECT id, removetime FROM cc_feedback WHERE cookie LIKE '$cookie' ORDER BY id DESC LIMIT 1", 1);
		    if ($result) {
			$idf = $result[0][0];
			$param_update = ($result[0][1]>0) ? "operation='ReviewExt', " : "operation='ReviewSiteUpdate', ";
			$param_update.= "letter='$review', email='$email', reply='$answer', notify='$news', updatetime=CURRENT_TIMESTAMP";
			$table -> Update_table($DBHandle, $param_update, "id='$idf'");
		    } else {
			$values = "'ReviewSite', '".$countryname."', '".$city."', '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['HTTP_USER_AGENT']."', '".$cookie."', '$review', '$email', '$answer', '$news'";
			$id_feedback = $table->Add_table($DBHandle, $values, null, null, "id");
		    }
		}
		sendForgot(5,'<font color="green">'.gettext('Your information was successfully received.').' '.(($answer&&$email)?gettext('Wait for answer, please.'):gettext('Thank you so much!')).'</font>');
	    }
	} elseif ($action == "info") {
		$table = new Table('cc_feedback');
		$result = $table -> SQLExec ($DBHandle, "SELECT id FROM cc_feedback WHERE uuid LIKE '$uuid' AND (removetime = 0 OR extension = '') ORDER BY id DESC LIMIT 1", 1);
		if (!$result) {
		    $result = $table -> SQLExec ($DBHandle, "SELECT id FROM cc_feedback WHERE fromip LIKE '{$_SERVER['REMOTE_ADDR']}' AND (removetime = 0 OR extension = '') ORDER BY id DESC LIMIT 1", 1);
		}
		if ($result) {
		    $idf = $result[0][0];
		    if ($cookie) $param_update = "cookie='$cookie', ";
		    if ($uuid)	 $param_update.= "uuid='$uuid', ";
		    $param_update .= "operation='SetConf', country='$countryname', city='$city', fromip='{$_SERVER['REMOTE_ADDR']}', fromagent='{$_SERVER['HTTP_USER_AGENT']}', extension='$e', apilink='$a', messtext='$m'";
		    $table -> Update_table($DBHandle, $param_update, "id='$idf'");
		}
		die();
	} elseif ($action == "install" || $action == "update") {
		$table = new Table('cc_feedback', "operation, country, city, fromip, fromagent, uuid, cookie");
		$result = $table -> SQLExec ($DBHandle, "SELECT id FROM cc_feedback WHERE uuid LIKE '$uuid' AND removetime = 0 ORDER BY id DESC LIMIT 1", 1);
		if (!$result) {
		    if ($uuid=='') $uuid = 'undefined';
		    $values = "'$action', '".$countryname."', '".$city."', '".$_SERVER['REMOTE_ADDR']."', '".$_SERVER['HTTP_USER_AGENT']."', '".$uuid."', '".$cookie."'";
		    $id_feedback = $table->Add_table($DBHandle, $values, null, null, "id");
		} else {
		    $idf = $result[0][0];
		    $param_update = "operation='Update', country='$countryname', city='$city', fromip='{$_SERVER['REMOTE_ADDR']}', fromagent='{$_SERVER['HTTP_USER_AGENT']}', cookie='$cookie', updatetime=CURRENT_TIMESTAMP";
		    $table -> Update_table($DBHandle, $param_update, "id='$idf'");
		}
		die();
	} else {
		sendForgot(7,gettext('Invalid Action'));
	}
} elseif ($uuid || $cookie) {
	$table = new Table('cc_feedback');
	$result = false;
	if ($uuid) {
	    $result = $table -> SQLExec ($DBHandle, "SELECT id FROM cc_feedback WHERE uuid LIKE '$uuid' AND removetime = 0 ORDER BY id DESC LIMIT 1", 1);
	    if ($result && $cookie) $param_update = "cookie='$cookie', ";
	}
	if ($result && $uuid) {
	    $idf = $result[0][0];
	    $param_update .= "operation='Remove', country='$countryname', city='$city', fromip='{$_SERVER['REMOTE_ADDR']}', fromagent='{$_SERVER['HTTP_USER_AGENT']}', removetime=CURRENT_TIMESTAMP";
	    $table -> Update_table($DBHandle, $param_update, "id='$idf'");
	    Header("Location: review_webrtc");
	    exit();
	} else {
		$result = $table -> SQLExec ($DBHandle, "SELECT * FROM (SELECT letter, email, reply, notify, removetime FROM cc_feedback WHERE cookie LIKE '$cookie' ORDER BY id DESC LIMIT 1) aa WHERE letter<>'' OR email<>'' OR removetime>0", 1);
		if ($result) {
		    list($letter,$mailing,$ans,$new) = $result[0];
		    if ($ans) $reply   = 'checked';
		    if ($new) $fornews = 'checked';
		} else {
		    $cookie = '';
		}
	}
}

include ("lib/customer.module.access.php");
include ("lib/customer.smarty.php");


$smarty -> assign("COK", $cookie);
$smarty -> assign("REVIEW", $letter);
$smarty -> assign("EMAIL", $mailing);
$smarty -> assign("ANSWER", $reply);
$smarty -> assign("FORNEWS", $fornews);
$smarty -> assign("WEBPHONE", "https://chromewebstore.google.com/detail/webrtc-sip-phone-with-cli/pofkcckikkdhiieipefhkaelgnbdkcib");

$smarty -> assign("SECONDARY_TITLE", "Review");

$smarty -> display('slidebar.tpl');
$smarty -> display('review_webrtc.tpl');
$smarty -> display('slidebarfooter.tpl');
