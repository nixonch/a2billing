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

function create_help($text,$balance=null,$limit=null) {
	global $r;
	$help = '
	<div class="toggle_show2hide">
	<div class="tohide" style="display:visible;">
	<div class="msg_info"';
	if ($balance != null) $help .= ' style="padding:0px 10px 28px 50px;"';
	$help .= '>
	<table border="0" style="max-width:97%;" cellspacing="1" cellpadding="2" align="left" height="50px">
	<tr>';
	if ($balance != null) {
	    $help .= '
	    <td align="right" nowrap><font class="fontstyle_001">' . gettext("Your balance") . ':</font></td>
	    <td align="right" nowrap><font class="fontstyle_00' . (($limit)?5:'1" style="font-weight:bold;') . '">' . $balance . '</font></td>
	    <td ROWSPAN='.$r.' width=2%></td>
	    <td ROWSPAN='.$r.' width=100% valign="bottom" align="center">' . $text . '</td>
	</tr>
	<tr heigth="5px">
	    <td';
	    if ($limit && $limit != -1) $help .= ' align="right" nowrap><font class="fontstyle_001">' . gettext("Credit limit") . ':</font';
	    else $help .= ' height="5px"';
	    $help .= '></td>
	    <td';
	    if ($limit && $limit != -1) $help .= ' align="right" nowrap><font class="fontstyle_001" style="font-weight:bold;">' . $limit . '</font';
	    else $help .= ' height="5px"';
	    $help .= '></td>';
	    if ($r == 3) $help .= '
	</tr>
	<tr>
	    <td COLSPAN=2 align="center" valign="bottom">
		<form action="checkout_payment.php" method="post">
		<input type="submit" class="form_input_button" value="' . gettext("BUY CREDIT") . '">
		</form>
	    </td>';
	} else $help .= '<td>' . $text . '</td>';
	$help .= '
	</tr>
	</table><br/><br/>
	<a href="#" target="_self" class="hide_help" style="float:right;"><img class="toggle_show2hide" src="' . Images_Path . '/toggle_hide2show_on.png" onmouseover="this.style.cursor=\'hand\';" HEIGHT="16"> </a>
	</div></div></div>
	';
	return $help;

}

$inst_table = new Table();

$QUERY = "SELECT creditlimit, credit, currency, credit_notification, paypal, users_perms FROM cc_card LEFT JOIN cc_card_group ON cc_card_group.id = id_group WHERE username = '" . ((isset($_SESSION["pr_login"]))?$_SESSION["pr_login"]:"") . "' AND uipass = '" . ((isset($_SESSION["pr_password"]))?$_SESSION["pr_password"]:"") . "'";

$DBHandle = DbConnect();

$customer_res = $inst_table -> SQLExec($DBHandle, $QUERY);
if (is_array($customer_res) && count($customer_res)>0) {
    $customer_info = $customer_res[0];
} else {
    $customer_info = array(0,0,BASE_CURRENCY,0,0,0);
}
$currencies_list = get_currencies();
$user_paypal = $customer_info[4];

$two_currency = false;
if (!isset ($currencies_list[strtoupper($customer_info[2])][2]) || !is_numeric($currencies_list[strtoupper($customer_info[2])][2])) {
	$mycur = 1;
} else {
	$mycur = $currencies_list[strtoupper($customer_info[2])][2];
	$display_currency = strtoupper($customer_info[2]);
	if (strtoupper($customer_info[2]) != strtoupper(BASE_CURRENCY))
		$two_currency = true;
}

$credit_cur = $customer_info[1] / $mycur;
//$credit_cur = round($credit_cur, 5).' '.gettext($customer_info[2]);
$credit_cur = display_refill_money($credit_cur,gettext($customer_info[2]),false);
if ($credit_cur < 0) {
	$limit_cur = $customer_info[0] / $mycur;
	if ($limit_cur) $limit_cur = round($limit_cur, 2).' '.gettext($customer_info[2]);
} elseif (($customer_info[3] != -1 && ($customer_info[1]-$customer_info[3]) <= 0) || $customer_info[1] <=0) $limit_cur = -1;
    else $limit_cur = 0;

$r = ($A2B->config["epayment_method"]['enable'] && (16 & $customer_info[5])) ? 3 : 2 ;

if (SHOW_HELP) {

	$CC_help_webphone = create_help(gettext("From here, you can use the web based screen phone. You need microphone and speakers on your PC."),$credit_cur,$limit_cur);

	$CC_help_balance_customer = create_help(gettext("All calls are listed below. Search by month, day or status. Additionally, you can check the rate and price."),$credit_cur,$limit_cur);

	$CC_help_fax_customer = create_help(gettext("All faxes are listed below. Search by month, day or status. Additionally, you can download have been received faxes in pdf format."),$credit_cur,$limit_cur);

	$CC_help_support = create_help(gettext("On this page, you can open a support ticket and consult the status of your existing ticket."),$credit_cur,$limit_cur);
	
	$CC_help_card = create_help(gettext("Personal information.") . '<br>' . gettext("You can update your personal information here."),$credit_cur,$limit_cur);

	$CC_help_notification = create_help(gettext("Notification settings.") . '<br>' . gettext("You can update your notification settings here."),$credit_cur,$limit_cur);
	
	$CC_help_simulator_rateengine = create_help(gettext("Simulate the calling process to discover the cost per minute of a call, and the number of minutes you can call that number with your current credit."),$credit_cur,$limit_cur);

	$CC_help_sipiax_info = create_help(gettext("Configuration information for SIP and IAX Client. You can simply copy and paste it in your configuration files and do necessary modifications."),$credit_cur,$limit_cur);

	$CC_help_password_change = create_help(gettext("On this page you will be able to change your password, You have to enter the New Password and Confirm it."),$credit_cur,$limit_cur);

	$CC_help_ratecard = create_help(gettext("View Rates<br>Rates are subjected to change without notice"),$credit_cur,$limit_cur);

	$CC_help_view_payment = create_help(gettext("Payment history - Record of payments made."),$credit_cur,$limit_cur);
	
	$CC_help_voicemail = create_help(gettext("Voicemail - The section below allows you to see all your voicemail, listen to them and move them into other folders."),$credit_cur,$limit_cur);

	$CC_help_list_voucher = create_help(gettext("Enter your voucher number to top up your card."),$credit_cur,$limit_cur);

	$CC_help_campaign = create_help(gettext("This section will allow you to create and edit campaign. ") .
					gettext("Predictive dialer will browse all the phone numbers from the campaign and perform outgoing calls."),$credit_cur,$limit_cur);

	$CC_help_phonelist = create_help(gettext("Phonelist are all the phone numbers attached to a campaign. You can add, remove and edit the phone numbers."),$credit_cur,$limit_cur);

	$CC_help_view_invoice = create_help(gettext("Invoice history - The section below allows you to see and pay the invoices that you have to pay."),$credit_cur,$limit_cur);

	$CC_help_view_receipt = create_help(gettext("Receipt history - The section below allows you to see the receipt that you received. you can see in them the summary of some withdrawal"),$credit_cur,$limit_cur);

	$CC_help_phonebook = create_help(gettext("Phonebook are set of phone numbers. You can add, remove and edit the phonebook. You can also associate phonebook to a campaign in the Campaign section"),$credit_cur,$limit_cur);

	$CC_help_list_did = create_help(gettext("Select the country below where you would like a DID, select a DID from the list and enter the destination you would like to assign it to."),$credit_cur,$limit_cur);

	$CC_help_release_did = create_help(gettext("After confirmation, the release of the did will be done immediately and you will not be monthly charged any more."),$credit_cur,$limit_cur);

	$CC_help_speeddial = create_help(gettext("Map single digit to your most dialed numbers."),$credit_cur,$limit_cur);

	$CC_help_callback = create_help(gettext("Callback : Enter your phone number and the phone number you wish to call."),$credit_cur,$limit_cur);

	$CC_help_money_situation = create_help(gettext("This screen shows refills and payments made against each account, along with the current credit on each card. The initial amount of credit applied to the card is not included. The amount owing is calculated by subtracting payments from refills."),$credit_cur,$limit_cur);

	$CC_help_view_refill = create_help(gettext("Customers Refill history - The section below allows you to see customers refill"),$credit_cur,$limit_cur);

	$CC_help_surveillance = create_help(gettext("Set the phone number of your recording device and the segment length for audio or video surveillance."),$credit_cur,$limit_cur);

	$CC_help_ringuplist = create_help(gettext("<b>List of periodic call numbers.</b><br>In the 'LAST ACTIVITY' column, blue marks an idle periodic call, and green marks an active one.<br>Click 'NUMBER' to open call statistics for this number for the current month."),$credit_cur,$limit_cur);

	$CC_help_ringupedit = create_help(gettext("Set the event schedule for periodic dialing, the pause between events, the number of calls each time, and the maximum call duration limit."),$credit_cur,$limit_cur);

	$CC_help_log_viewer_info = create_help(gettext("The system log helps you track all events on your application. Log levels are the Importance Levels for the events - 1 is lowest level and 3 is highest level. 1 is used for Login, Logout and Page Visit. 2 is used for Add, Import, Export. 3 is for Update and Delete."),$credit_cur,$limit_cur);

	$CC_help_ivr = create_help(gettext("Create your own voice menus and use them when setting up INCOMING CALLS.<br>Before that, create voice phrases or upload your own in the VOICE GREETINGS section."),$credit_cur,$limit_cur);

	$CC_help_caller_id = create_help(gettext("You can add your home phone or cellphone number below to allow you to call in, be automatically recognized, and use your telephony services without having to enter your PIN."),$credit_cur,$limit_cur);

	$CC_help_empty = create_help("",$credit_cur,$limit_cur);

} //ENDIF SHOW_HELP

if (!isset ($disable_load_conf) || !($disable_load_conf)) {

	$DBHandle = DbConnect();
	$instance_table = new Table();
	$QUERY = "SELECT configuration_key FROM cc_configuration where configuration_key in ('MODULE_PAYMENT_AUTHORIZENET_STATUS','MODULE_PAYMENT_PAYPAL_BASIC_STATUS','MODULE_PAYMENT_MONEYBOOKERS_STATUS','MODULE_PAYMENT_WORLDPAY_STATUS','MODULE_PAYMENT_PLUGNPAY_STATUS','MODULE_PAYMENT_WM_STATUS','MODULE_PAYMENT_WM_STATUS_10') AND configuration_value='True'";
	$payment_methods = $instance_table->SQLExec($DBHandle, $QUERY);
	$show_logo = '';
	$SPOT['paypal'] = '<a href="https://www.paypal.com/ru/mrb/pal=PGSJEXAEXKTBU" target="_blank"><img src="' . KICON_PATH . '/paypal_logo.gif" alt="Paypal"/></a>';
	$SPOT['moneybookers'] = '<a href="https://www.moneybookers.com/app/?rid=811621" target="_blank"><img src="' . KICON_PATH . '/moneybookers.gif" alt="Moneybookers"/></a>';
//	$SPOT['authorizenet'] = '<a href="http://authorize.net/" target="_blank"><img src="'.KICON_PATH.'/authorize.gif" alt="Authorize.net"/></a>';
	$SPOT['authorizenet'] = '';
//	$SPOT['worldpay'] = '<a href="http://www.worldpay.com/" target="_blank"><img src="'.KICON_PATH.'/worldpay.gif" alt="worldpay.com"/></a>';
	$SPOT['worldpay'] = '';
	$SPOT['plugnpay'] = '<a href="http://www.plugnpay.com/" target="_blank"><img src="' . KICON_PATH . '/plugnpay.png" alt="plugnpay.com"/></a>';
	$SPOT['webmoney'] = '<a href="https://wiki.web.money/projects/webmoney/wiki/%D0%9E%D0%BF%D0%BB%D0%B0%D1%82%D0%B0_%D1%82%D0%BE%D0%B2%D0%B0%D1%80%D0%BE%D0%B2_%D0%B8_%D1%83%D1%81%D0%BB%D1%83%D0%B3_%D1%87%D0%B5%D1%80%D0%B5%D0%B7_%D1%81%D0%B5%D1%80%D0%B2%D0%B8%D1%81_%D0%9C%D0%B5%D1%80%D1%87%D0%B0%D0%BD%D1%82" target="_blank"><img src="' . KICON_PATH . '/webmoney.gif" alt="WebMoney"/></a>';
	$SPOT['webmoneycreditcard'] = '<a href="https://wiki.web.money/projects/webmoney/wiki/%D0%9F%D1%80%D0%B8%D0%B5%D0%BC_%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B5%D0%B9_%D1%81_%D0%B1%D0%B0%D0%BD%D0%BA%D0%BE%D0%B2%D1%81%D0%BA%D0%B8%D1%85_%D0%BA%D0%B0%D1%80%D1%82_%28U-%D0%BA%D0%BE%D1%88%D0%B5%D0%BB%D1%8C%D0%BA%D0%B8%29#%D0%A1-%D1%82%D0%BE%D1%87%D0%BA%D0%B8-%D0%B7%D1%80%D0%B5%D0%BD%D0%B8%D1%8F-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%BB%D1%8C%D1%89%D0%B8%D0%BA%D0%B0" target="_blank"><img src="' . KICON_PATH . '/webmoneycreditcard.gif" alt="WebMoney"/></a>';
	$PAYMENT_METHOD = '
	<table width="100%" align="center">
	<tr>
		<TD valign="top" align="center" class="tableBodyRight">
			' . $SPOT['paypal'] . '
			&nbsp;&nbsp; &nbsp;
			' . $SPOT['moneybookers'] . '
			&nbsp;&nbsp; &nbsp;
			' . $SPOT['authorizenet'] . '
			&nbsp;&nbsp; &nbsp;
			' . $SPOT['plugnpay'] . '
			&nbsp;&nbsp; &nbsp;
			' . $SPOT['worldpay'] . '
			&nbsp;&nbsp; &nbsp;
			' . $SPOT['webmoney'] . '
		</td>
	</tr>
	</table>';
	
	if ($payment_methods) for ($index = 0; $index < sizeof($payment_methods); $index++) {
		if ($payment_methods[$index][0] == "MODULE_PAYMENT_PAYPAL_BASIC_STATUS") {
			$show_logo .= $SPOT['paypal'] . ' &nbsp; ';
		}
//		elseif( $payment_methods[$index][0] == "MODULE_PAYMENT_AUTHORIZENET_STATUS") {
//			$show_logo .= $SPOT['authorizenet'] . ' &nbsp; ';
//		}
		elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_MONEYBOOKERS_STATUS") {
			$show_logo .= $SPOT['moneybookers'] . ' &nbsp; ';
		}
//		elseif( $payment_methods[$index][0] == "MODULE_PAYMENT_WORLDPAY_STATUS") {
//			$show_logo .= $SPOT['worldpay'] . ' &nbsp; ';
//		}
		elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PLUGNPAY_STATUS") {
			$show_logo .= $SPOT['plugnpay'] . ' &nbsp; ';
		}
		elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS") {
			$show_logo .= $SPOT['webmoney'] . ' &nbsp; ';
		}
//		elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS_10") {
//			$show_logo .= $SPOT['webmoney'] . ' &nbsp; ';
//		}
	}
	$PAYMENT_METHOD = '<table style="width:70%;margin:0 auto;" align="center" ><tr><TD valign="top" align="center" class="tableBodyRight">' . $show_logo . '</td></tr></table>';
}

$CALL_LABS = '
<table width="70%" align="center">
	<tr>
		<TD width="%75" valign="top" align="center" class="tableBodyRight" background="' . Images_Path . '/background_cells.gif" >
				Global VoIP termination (A-Z)  to over 400 worldwide destinations!<br>
				Visit Call-Labs at <a href="http://www.call-labs.com/" target="_blank">http://www.call-labs.com/</a><br/>
		</TD>
		<TD width="%25" valign="middle" align="center" class="tableBodyRight" background="' . Images_Path . '/background_cells.gif" >
				<a href="http://www.call-labs.com/" target="_blank"><img src="' . Images_Path . '/call-labs.com.png" alt="call-labs"/></a>
		</TD>
	</tr>
</table>';


