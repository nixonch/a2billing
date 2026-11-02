<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,   
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 * 
 * @copyright   Copyright (C) 2004-2009 - Star2billing S.L. 
 * @author      Belaid Arezqui <areski@gmail.com>
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 * @package     A2Billing
 *
 * Software License Agreement (GNU Affero General Public License)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * 
**/

getpost_ifset(array('id','subscriber_signup', 'username', 'useralias', 'uipass', 'credit', 'tariff', 'activated', 'simultaccess', 'currency', 'typepaid', 'creditlimit', 'language', 'lastname',
	'firstname', 'email', 'address', 'city', 'state', 'id_timezone', 'country', 'zipcode', 'phone', 'fax', 'inuse', 'cid', 'runservice', 'firstusedate', 'expirationdate', 'enableexpire',
	'expiredays','company_name' , 'company_website' , 'vat_rn' , 'vat', 'traffic' , 'traffic_target','captchacode','groupid', 'key', 'id_diller', 'margin', 'paypal', 'id_group', 'fullname', 'types'));

function sendForgot($error,$forgotString) {
    header("Content-type: text/xml");
    echo "<response><error>$error</error><forgotString><![CDATA[$forgotString]]></forgotString></response>";
    die();
}
$callPlan_idlist = isset($A2B->config["signup"]['callplan_id_list']) ? $A2B->config["signup"]['callplan_id_list'] : NULL;
$group_idlist = isset($A2B->config["signup"]['group_id_list']) ? $A2B->config["signup"]['group_id_list'] : NULL;
if (isset($form_action) && $form_action=='add' && isset($types) && $types=='short') {
    if (!isset ($_SESSION["date_forgot"]) || (time() - $_SESSION["date_forgot"]) > 20) {
	$_SESSION["date_forgot"] = time();
    } else {
	sendForgot(9,gettext("Please wait 1 minute before making any other request for the signup!"));
    }
    $fullname = @trim($fullname);
    $full_name = explode(' ',$fullname);
    if ($full_name===false || count($full_name)!=2 || strlen($fullname)<4 || strpos($fullname,' ')<1) {
	sendForgot(7,gettext("Please provide your First and Last name"));
    }
    list($firstname,$lastname) = $full_name;
    $_POST["lastname"] = $lastname;
    $_POST["firstname"] = $firstname;
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if ($email===false) {
	sendForgot(7,gettext("Please provide your valid email address"));
    }
    $_SESSION["language_code"] = $_POST["language"] = $language = LANG;

    list($tariff) = explode(',',$callPlan_idlist);
    list($id_group) = explode(',',$group_idlist);
    $currency = BASE_CURRENCY;
    $quicksignup = true;
} else {
    $quicksignup = false;
}
//put parameter in session
$HD_Form = new FormHandler("cc_card", "User");
$HD_Form->FG_DEBUG = 0;

$HD_Form->FG_TABLE_DEFAULT_SENS = "ASC";
$HD_Form->FG_FILTER_SEARCH_SESSION_NAME = 'entity_card_selection';
$HD_Form->FG_ADDITION = true;

$HD_Form->setDBHandler(DbConnect());

if (!empty ($key)) {
	$_SESSION["key"] = $key;
}

if (empty($language)) $_POST["language"] = $language = LANG;

if (has_rights (ACX_DISTRIBUTION) && (isset($id_diller) && $id_diller > 0)) {
	if (!empty($_SESSION["dillertariffs"]))  {
		$callPlan_idlist = $_SESSION["dillertariffs"];
	}
	if (!empty($_SESSION["dillergroups"]))  {
		$group_idlist = $_SESSION["dillergroups"];
	}
}

if (strlen($callPlan_idlist) == 0) {
	exit ("No Call Plan Defined.");
}
$call_table = new Table("cc_tariffgroup", "tariffgroupname,id");
$callPlan_list = $call_table->Get_list($HD_Form->DBHandle, "id in ($callPlan_idlist)", "FIELD(id,".$callPlan_idlist.")", "ASC");

if (!is_array($callPlan_list) || count($callPlan_list) <= 0) {
	exit (gettext("No Call Plan id are defined"));
}
$flag = true;
if ($tariff != "" || $tariff != null) {
	$callPlan_count = count($callPlan_list);
	for ($i = 0; $i < $callPlan_count; $i++) {
		if ($callPlan_list[$i][1] == trim($tariff, "\'")) {
			$flag = false;
		}
	}
	if ($flag) {
		exit ("Invalid Call Plan Id");
	}
}
if (strlen($group_idlist) == 0) {
	exit ("No Groups Defined.");
}
$call_table = new Table("cc_card_group", "name,id");
$group_list = $call_table->Get_list($HD_Form->DBHandle, "id in ($group_idlist)", "FIELD(id,".$group_idlist.")", "ASC");

if (!is_array($group_list) || count($group_list) <= 0) {
	exit (gettext("No Group id are defined"));
}
$flag = true;
if ($id_group != "" || $id_group != null) {
	$group_count = count($group_list);
	for ($i = 0; $i < $group_count; $i++) {
		if ($group_list[$i][1] == trim($id_group, "\'")) {
			$flag = false;
		}
	}
	if ($flag) {
		exit ("Invalid Group Id");
	}
}

//check subscriber

$maxi = $maxi2 = $maxi3 = $maxi4 = '';
//	-= Need to install GeoIP http://ua2.php.net/manual/en/geoip.setup.php =-
if (($form_action == 'ask-add' || $form_action == 'add') && (geoip_db_avail(GEOIP_REGION_EDITION_REV0) || geoip_db_avail(GEOIP_REGION_EDITION_REV1))) {
	$countryregion = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
	if (!isset($zipcode)) $maxi4 = "value='".$countryregion['postal_code']."'";
} else {
	$country = $region = "";
}

$country_city_list = array (array('Jeru'  ,'Israel' ),
			    array('Berlin','Germany')
			    );
$town = "";
foreach ($country_city_list as $cur_value) {
	if ($cur_value[1]==$countryregion['country_name'])
	    $town = $cur_value[0];
}

//end check subscriber

$currency_list = array ();
$currency_list_r = array ();
$indcur = 0;

$currencies_list = get_currencies();
$yesno_list = array(array(gettext('yes').'&nbsp','1'),array(gettext('no').'&nbsp','0'));

foreach ($currencies_list as $key => $cur_value) {
	$currency_list[$key] = array (
		$cur_value[1] . ' (' . $cur_value[2] . ')',
		$key
	);
	$currency_list_r[$key] = array (
		$key,
		$cur_value[1]
	);
	$currency_list_key[$key][0] = $key;
}

$HD_Form->CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form->CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form->CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form->FG_LIMITE_DISPLAY = 10;
if (!($popup_select >= 1))
	$HD_Form->FG_EXPORT_CSV = true;
if (!($popup_select >= 1))
	$HD_Form->FG_EXPORT_XML = true;

if (($popup_select >= 1))
	$HD_Form->FG_LIMITE_DISPLAY = 7;

$HD_Form->CV_FOLLOWPARAMETERS = "&popup_select=" . $popup_select . "&popup_formname=" . $popup_formname . "&popup_fieldname=" . $popup_fieldname;

if (isset ($form_action) && $form_action == "add") {
	$array_card_generated = gen_card_with_alias();
	$maxi = $array_card_generated[0];
	$maxi2 = $array_card_generated[1];
	$pass = MDP_STRING(10);
	$loginkey = MDP_STRING(20);
}

$language_list = Constants::getLanguagesList();
if (LANGUAGE_ENABLE || $quicksignup)  {
$HD_Form->AddEditElement(gettext("LANGUAGE")." *",
		"language",
		"$language",
		"SELECT",
		"", "",
		"", "list", "", "", "", $language_list, "%1", "", "");
$language_editFG = "language,";
}
if (CURRENCY_ENABLE)  {
$HD_Form->AddEditElement(gettext("CURRENCY")." *",
		"currency",
		"$currency",
		"SELECT",
		"", "",
		"", "list", "", "", "", $currency_list, "%1", "", "");
$currency_editFG = "currency,";
}
$comp_zero = $comp_date_plus = '';
if (isset ($form_action) && $form_action == "ask-add") {
	$begin_date = date("Y");
	$begin_date_plus = date("Y") + 10;
	$end_date = date("-m-d H:i:s");
	$comp_date = "value='" . $begin_date . $end_date . "'";
	$comp_date_plus = "value='" . $begin_date_plus . $end_date . "'";
	$paypal = $_SESSION["paypal"];
}
if (LASTNAME_ENABLE)  {
$HD_Form->AddEditElement(gettext("LASTNAME")." *",
		"lastname",
		'$value',
		"INPUT",
		"style=\"width:260px\" maxlength=50", "11",
		gettext("Insert your lastname"), "", "", "", "", "", "", "", "");
$lastname_editFG = "lastname,";
}
if (FIRSTNAME_ENABLE)  {
$HD_Form->AddEditElement(gettext("FIRSTNAME")." *",
		"firstname",
		'$value',
		"INPUT",
		"style=\"width:260px\" maxlength=50", "11",
		gettext("Insert your firstname"), "", "", "", "", "", "", "", "");
$firstname_editFG = "firstname,";
}

if (COMP_ENABLE && !$quicksignup)  {
$HD_Form->AddEditElement(gettext("COMPANY NAME"),
		"company_name",
		'$value',
		"INPUT",
		"style=\"width:260px\" maxlength=50", "",
		gettext("Insert Company name of this customer"), "", "", "", "", "", "", "NO", "");
$company_name_editFG = "company_name,";
}
$group_editFG = "";
if (strpos($group_idlist, ',') !== false && !($subscriber_signup>0)) {
	$HD_Form->AddEditElement(gettext("CUSTOMER GROUP"),
		"id_group",
		"$id_group",
		"SELECT",
		"", "",
		"", "list", "", "", "", $group_list, "%1", "", "");
$group_editFG = "id_group,";
}

if (strpos($callPlan_idlist, ',') !== false && !($subscriber_signup>0)) {
	$HD_Form->AddEditElement(gettext("CALL PLAN"),
								"tariff",
								"$tariff",
								"SELECT",
								"", "",
								"", "list", "", "", "", $callPlan_list, "%1", "", "");
$group_editFG .= "tariff,";
}

if (has_rights (ACX_DISTRIBUTION) || (isset($id_diller) && $id_diller > 0))  {
	$HD_Form -> AddEditElement (gettext("MARGIN").", %%",
								"margin",
								'$value',
								"INPUT",
								"style=\"width:260px\" maxlength=3",
								"27", "(0..100)%",
								"", "", "", "", "", "", "NO", "");
$group_editFG .= "margin,";

}

$HD_Form->AddEditElement(gettext("EMAIL")." *",
		"email",
		'$value',
		"INPUT",
		"style=\"width:260px\" maxlength=70", "1",
		gettext("Insert your email"), "", "", "", "", "", "", "", "");

if (ADDRESS_ENABLE && !$quicksignup)  {
$HD_Form->AddEditElement(gettext("ADDRESS")." *",
		"address",
		'$value',
		"INPUT",
		"style=\"width:260px\" maxlength=100", "0",
		gettext("Insert your address"), "", "", "", "", "", "", "", "");
$address_editFG = ",address";
}

if (CITY_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("CITY")." *",
								"city",
								'$value',
								"INPUT",
								"style=\"width:260px\" maxlength=40", "0",
								gettext("Insert your city"), "", "", "", "", "", "", "", "");
	$city_editFG = ",city";
}

if (STATE_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("STATE/PROVINCE"),
								"state",
								'$value',
								"INPUT",
								"style=\"width:260px\" maxlength=40", "11",
								gettext("Insert your state"), "", "", "", "", "", "", "NO", "");
	$state_editFG = ",state";
}

if (COUNTRY_ENABLE)  {
	if (isset($form_action) && $form_action == 'ask-add') {
		$country = $countryregion['country_code3'];
		if ($country=="") {
			$country = 'USA';
		}
	}
	$HD_Form->AddEditElement(gettext("COUNTRY")." *",
								"country",
								"$country",
								"SELECT", "style=\"max-width: 262px\"", "", "",
								"sql",
								"cc_country", "countryname, countrycode", 
								"", "", "%1", "", "", "", '');
	$country_editFG = ",country";
}

if (ZIPCODE_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("ZIP/POSTAL CODE")." *",
								"zipcode",
								'$value',
								"INPUT",
								"style=\"width:260px\" maxlength=20 $maxi4","0",
								gettext("Insert your zipcode"), "", "", "", "", "", "", "", "");
	$zipcode_editFG = ",zipcode";
}

if (TIMEZONE_ENABLE)  {
	if (isset($form_action)) {
//	-= Need to install GeoIP http://ua2.php.net/manual/en/geoip.setup.php =-
		$timezone_list = get_timezones();
		$one_select = false;
		if (function_exists('geoip_time_zone_by_country_and_region') && ($form_action == 'ask-add' || $form_action == 'add')) {
			if ($countryregion===false) {
				$country = $region = "";
			} else {
				$country = $countryregion['country_code'];
				$region = $countryregion['region'];
			}
			if ($region == "") {
				if ($country == "") $country = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
				$region = '01';
			}
			if ($country == "") {
				$country = 'US';
				$region = 'CA';
			}
			try {
				$UserDateTimeZone	= new \DateTimeZone(geoip_time_zone_by_country_and_region($country,$region));
			} catch (\Exception $e) {
				$UserDateTimeZone	= new \DateTimeZone('UTC');
			}
			$zonename		= $UserDateTimeZone->getName();
//			$UserDateTime		= new DateTime(null, $UserDateTimeZone);
//			$servergmt		= $UserDateTimeZone->getOffset($UserDateTime);
			$UserDateTime		= new DateTime('2019-12-14', $UserDateTimeZone);
			$servergmt		= $UserDateTime->getOffset();
		} else $servergmt = SERVER_GMT;
		foreach ($timezone_list as $key => $cur_value) {
			$timezone_list[$key] = array (
				$cur_value[2],
				$key
			);
			if ($form_action == 'ask-add' || $form_action == 'add') {
				if (in_array($servergmt, $cur_value) && !$one_select) {
					$cur_id_timezone = $key.";".$zonename;
					if ($town=="" || strpos($cur_value[2], $town) !== false) {
						$timezone_list[$key][1] = $cur_id_timezone;
						if ($zonename != "") $timezone_list[$key][0] = substr_replace($cur_value[2],") ".$zonename,strpos($cur_value[2],')'));
						if (!isset($id_timezone) || $key == $id_timezone)
							$id_timezone = $cur_id_timezone;
						$one_select = true;
					}
				}
			}
		}
	}
	$HD_Form->AddEditElement(gettext("TIMEZONE")." *",
								"id_timezone",
								"$id_timezone",
								"SELECT",
								"style=\"max-width:262px\"", "", "",
								"list", "", "", "",
								$timezone_list,
								"%1", "", "");
	$id_timezone_editFG = ",id_timezone";
}

if (PHONE_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("PHONE NUMBER")." *",
								"phone",
								'$value',
								"INPUT", "style=\"width:260px\" maxlength=20",
								"7", "", "", "", "", "", "", "", "", "");
	$phone_editFG = ",phone";
}

if (FAX_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("FAXNUMBER"), 
								"fax", 
								'$value', 
								"INPUT", "style=\"width:260px\" maxlength=20", 
								"7", gettext("Insert your fax number"), "", "", "", "", "", "", "NO");
	$fax_editFG = ",fax";
}

if (COMP_WEB_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("COMPANY WEBSITE"), 
								"company_website",
								'$value',
								"INPUT", "style=\"width:260px\" maxlength=60",
								"8", gettext("Insert the company website of this customer"), "", "", "", "", "", "", "NO", "");
	$company_website_editFG = ",company_website";
}

if (VAT_RN_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("VAT REGISTRATION NUMBER "), 
								"vat_rn", 
								'$value', 
								"INPUT", "style=\"width:260px\" maxlength=40",
								"", gettext("Insert the VAT registration number of this customer"), "", "", "", "", "", "", "NO", "");
	$VAT_RN_editFG = ",vat_rn";

	$HD_Form -> AddEditElement (gettext("VAT").", %%",
								"vat",
								'$value',
								"INPUT",
								"style=\"width:260px\" maxlength=6",
								"12", "%%",
								"", "", "", "", "", "", "NO", "");
	$VAT_editFG = ",vat";
}

if (TRAFFIC_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("TRAFFIC PER MONTH"), 
								"traffic", 
								'$value', 
								"INPUT", "style=\"width:260px\" maxlength=20", 
								"4", gettext("Insert the traffic per month for this customer"), "", "", "", "", "", "", "NO", "");
	$traffic_editFG = ",traffic";
}

if (TRAFFIC_TARGET_ENABLE && !$quicksignup)  {
	$HD_Form->AddEditElement(gettext("TARGET TRAFFIC"),
								"traffic_target",
								'',
								"TEXTAREA", "cols=45 rows=4",
								"", gettext("Insert the target traffic description"), "", "", "", "", "", "", "", "");
	$traffic_target_editFG = ",traffic_target";
}

if (has_rights (ACX_DISTRIBUTION) || (isset($id_diller) && $id_diller > 0))  {
	$HD_Form -> AddEditElement (gettext("SUBSCRIBER"),
								"id_diller",
								'$value',
								"INPUT",
								'style=\"width:260px\" maxlength=20 readonly style="color: gray;"',
								"6",
								"", "", "", "", "", "", "", "", "");
	$subscriber_editFG = ",id_diller";
    if ($_SESSION["paypal"]) {
	$HD_Form -> AddEditElement (gettext("PAYPAL PAYMENT"),
								"paypal",
								$paypal,
								"SELECT",
								"",
								"",
								"",
								"list", "", "", "", $yesno_list, "%1", "", gettext("Apply PayPal payment to this customer"));
	$subscriber_editFG .= ",paypal";
    }
} else {
    $HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS = "id_diller, ";
    $HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE = "0, ";
    if (CAPTCHA_ENABLE && !$quicksignup) {

	$HD_Form->AddEditElement(gettext("VERIFICATION"),
								"captchacode",
								'$value',
								"CAPTCHAIMAGE",
								"size=10 maxlength=6",
								"19",
								gettext("Insert code from above picture"), "", "", "", "", "", "", "", "", CAPTCHA_ENABLE);
	$captcha = ",captchacode";
    }
}

$HD_Form->FieldEditElement( $language_editFG . $currency_editFG . $lastname_editFG . $firstname_editFG . $company_name_editFG . $group_editFG . 'email' . $address_editFG .
			    $city_editFG . $state_editFG . $country_editFG . $zipcode_editFG . $id_timezone_editFG . $phone_editFG . $fax_editFG .
			    $company_website_editFG . $VAT_RN_editFG . $VAT_editFG . $traffic_editFG . $traffic_target_editFG . $subscriber_editFG . $captcha );

if (!isset ($myusername))
	$myusername = '';

//Code here for Getting Data from configuration files

if (!$A2B->config["signup"]['expirationdate']) {
	$comp_date_plus = '';
	$begin_date = date("Y");
	$begin_date_plus = date("Y") + 10;
	$end_date = date("-m-d H:i:s");
	$comp_date_plus = $begin_date_plus . $end_date;
} else {
	$comp_date_plus = $A2B->config["signup"]['expirationdate'];
}

$mytariff = '';
$mycredit = '';

$iax_account		= $A2B->config["signup"]['iax_account'] ? 1 : 0;
$sip_account		= $A2B->config["signup"]['sip_account'] ? 1 : 0;
$myexpiredays		= $A2B->config["signup"]['expiredays'];
$myexpirationdate	= $comp_date_plus;
$mytypepaid		= $A2B->config["signup"]['typepaid'];
$myenableexpire 	= $A2B->config["signup"]['enableexpire'];
$myrunservice		= $A2B->config["signup"]['runservice'];
$mysimultaccess 	= $A2B->config["signup"]['simultaccess'];
$mycredit		= $A2B->config["signup"]['credit'];

$myactivated		= $A2B->config["signup"]['activated'] ? 't' : 'f';
$mycreditlimit		= $A2B->config["signup"]['creditlimit'];
$amaflag		= $A2B->config["signup"]['amaflag'];
$context		= $A2B->config["signup"]['context'];

if (has_rights (ACX_DISTRIBUTION))
	$status = 1;
elseif ($A2B->config["signup"]['activated']) {
	// Status : 1 - Active
	$status = $A2B->config["signup"]['activatedbyuser'] ? '1' : '3';
} else {
	// Status : 2 - New
	$status = $A2B->config["signup"]['activatedbyuser'] ? '2' : '3';
}

$HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS .= "iax_buddy, sip_buddy, expiredays, expirationdate, typepaid, enableexpire, runservice, simultaccess, credit, username, useralias, uipass, loginkey, status";
$HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE .= "$iax_account, $sip_account, $myexpiredays, $myexpirationdate, $mytypepaid, $myenableexpire, $myrunservice, $mysimultaccess, $mycredit, $maxi, $maxi2, $pass, $loginkey, $status";

$HD_Form->FG_QUERY_EXTRA_HIDDED = array (
	$maxi,
	$maxi2,
	$pass,
	$loginkey
);

if (!is_numeric($subscriber_signup)) {
	if (strpos($callPlan_idlist, ',') === false) {
		$HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS .= ", tariff";
		$HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE .= ", $callPlan_idlist";
	}
	if (strpos($group_idlist, ',') === false) {
		$HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS .= ", id_group";
		$HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE .= ", $group_idlist";
	}
}

$HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS .= ", dillertariffs, dillergroups";
$HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE .= ", $callPlan_idlist, $group_idlist";

// If Service - find the callplan associate
if (is_numeric($subscriber_signup)) {

	$table_subscribe = new Table("cc_subscription_signup", "id_callplan");
	$data = $table_subscribe->Get_list($HD_Form->DBHandle, "id='" . $subscriber_signup . "'");
	if (is_array($data)) {
		$id_callplan = $data[0]['id_callplan'];
		$HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS .= ", tariff";
		$HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE .= ", $id_callplan";
	}
	
	// HIDDEN VALUE
	$HD_Form->FG_ADITION_HIDDEN_PARAM = 'subscriber_signup';
	$HD_Form->FG_ADITION_HIDDEN_PARAM_VALUE = "$subscriber_signup";
}

$HD_Form->FG_FILTER_APPLY = false;
$HD_Form->FG_FILTERFIELD = 'cardnumber';
$HD_Form->FG_FILTERFIELDNAME = 'cardnumber';
$HD_Form->FG_FILTER_FORM_ACTION = 'list';

$HD_Form->FG_INTRO_TEXT_EDITION = gettext("You can modify, through the following form, the different properties of your " . $HD_Form->FG_INSTANCE_NAME);
$HD_Form->FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want to remove this " . $HD_Form->FG_INSTANCE_NAME . ", click on the delete button.");
$HD_Form->FG_INTRO_TEXT_ADD = gettext("you can add easily a new " . $HD_Form->FG_INSTANCE_NAME . ".<br>Fill the following fields and confirm by clicking on the button add.");

$HD_Form->FG_INTRO_TEXT_ADITION = '';
$HD_Form->FG_TEXT_ADITION_CONFIRMATION = "<font>" . gettext("Thanks for registering with us.<br>A email with information about your account has been sent to ") . $_POST["email"] . "<br><br><b>" . gettext("Your card number is ") . $maxi . "</b><br><b>" . gettext("Your login is ") . $maxi2 . "</b><br><b>" . gettext("Your password is ") . $pass . "</b></font>";

$HD_Form->FG_BUTTON_EDITION_SRC = $HD_Form->FG_BUTTON_ADITION_SRC = Images_Path . "/cormfirmboton.gif";
$HD_Form->FG_ADD_PAGE_CONFIRM_BUTTON = gettext("SIGNUP");

$HD_Form->FG_ADDITIONAL_FUNCTION_BEFORE_ADD = 'convert_username_to_id';
$HD_Form->FG_ADDITIONAL_FUNCTION_AFTER_ADD = 'processing_card_signup';

$HD_Form->FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF'] . "?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form->FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF'] . "?atmenu=document&stitle=Document&wh=AC&id=";
