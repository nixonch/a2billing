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


include ("./lib/customer.defines.php");
include ("./lib/customer.module.access.php");
include ("./lib/Form/Class.FormHandler.inc.php");
include ("./lib/epayment/includes/configure.php");
include ("./lib/epayment/classes/payment.php");
include ("./lib/epayment/classes/order.php");
include ("./lib/epayment/classes/currencies.php");
include ("./lib/epayment/includes/general.php");
include ("./lib/epayment/includes/html_output.php");
include ("./lib/epayment/includes/loadconfiguration.php");
include ("./lib/customer.smarty.php");


if (! has_rights (ACX_ACCESS)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error?c=accessdenied");
	die();
}

$vat=$_SESSION["vat"];

getpost_ifset(array('amount','payment','authorizenet_cc_expires_year','authorizenet_cc_owner','authorizenet_cc_expires_month','authorizenet_cc_number','authorizenet_cc_expires_year','wm_purse_type'));
// PLUGNPAY
getpost_ifset(array('credit_card_type', 'plugnpay_cc_owner', 'plugnpay_cc_number', 'plugnpay_cc_expires_month', 'plugnpay_cc_expires_year', 'cvv'));
//Iridium
getpost_ifset(array('CardName', 'CardNumber', 'ExpiryDateMonth', 'ExpiryDateYear', 'CV2'));
// Invoice
getpost_ifset(array('item_id','item_type'));

$two_currency = false;
$currencies_list = get_currencies();

//Test value:
if (!isset($item_id) || is_null($item_id) || $item_id == "") {
	$item_id = 0;
}
if (!isset($item_type) || is_null($item_type)) {
	$item_type = '';
}

$HD_Form = new FormHandler("cc_payment_methods","payment_method");

$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();
$_SESSION["p_module"] = $payment;
$_SESSION["p_amount"] = 3;

$payment_modules = new payment($payment);
if (!isset($wm_purse_type)) {
	$paycur = 1;
	$getcur = mb_strtoupper(BASE_CURRENCY);
} else {
    if (is_array($payment_modules->modules)) {
	$getcur = $payment_modules->get_CurrentCurrency();
    }
}

if (!is_numeric($amount) || ((strcasecmp("paypal",$payment)==0 && ($amount < 10 || $amount > 1000)) || $amount < 1 || ($getcur == "UAH" && $payment == "webmoneycreditcard" && $amount > 9950))) {
	Header ("Location: checkout_payment");
	die();
}

switch($getcur)
{
	case 'USD': $paypalfixfee = 0.3; break;
	case 'EUR': $paypalfixfee = 0.35; break;
	case 'RUB': $paypalfixfee = 10; break;
	case 'THB': $paypalfixfee = 11; break;
	default   : $paypalfixfee = 0.3; break;
}
$paycur = $currencies_list[$getcur][2];
$vat_amount= $amount*$vat/100;
$amount_paypal = ($amount+$vat_amount+$paypalfixfee)/0.946;      // 0.946 / 0.953 / 0.956
switch ($payment) {
    case "paypal":
	$mc_fee = round(($amount_paypal)*0.054,2)+$paypalfixfee; // 0.054 / 0.047 / 0.044
	break;
    default:
	$mc_fee = 0;
}
$total_amount = $amount+$vat_amount+$mc_fee;

$paymentTable = new Table();
$time_stamp = time();
$amount_string = sprintf("%.3F", $total_amount);

$order = new order($amount_string);

if (mb_strtoupper($payment)=='PLUGNPAY') {
	$QUERY_FIELDS = "cardid, amount, vat, paymentmethod, cc_owner, cc_number, cc_expires, creationdate, cvv, credit_card_type, currency , item_id , item_type";
	$QUERY_VALUES = "'".$_SESSION["card_id"]."','$amount_string','".$_SESSION["vat"]."','$payment','$plugnpay_cc_owner','".substr($plugnpay_cc_number,0,4)."XXXXXXXXXXXX','".$plugnpay_cc_expires_month."-".$plugnpay_cc_expires_year."',FROM_UNIXTIME($time_stamp),'$cvv','$credit_card_type','".BASE_CURRENCY."','$item_id','$item_type'";
} elseif (mb_strtoupper($payment)=='IRIDIUM') {
	$QUERY_FIELDS = "cardid, amount, vat, paymentmethod, cc_owner, cc_number, cc_expires, creationdate, currency, item_id, item_type";
	$QUERY_VALUES = "'".$_SESSION["card_id"]."','$amount_string','".$_SESSION["vat"]."','$payment','$CardName','".substr($CardNumber,0,4)."XXXXXXXXXXXX','".$ExpiryDateMonth."-".$ExpiryDateYear."',FROM_UNIXTIME($time_stamp),'".BASE_CURRENCY."','$item_id','$item_type'";
} else {
	$QUERY_FIELDS = "cardid, amount, vat, paymentmethod, cc_owner, cc_number, cc_expires, creationdate, currency, item_id, item_type";
    $QUERY_VALUES = "'".$_SESSION["card_id"]."','$amount_string','".$_SESSION["vat"]."','$payment','$authorizenet_cc_owner','".substr($authorizenet_cc_number,0,4)."XXXXXXXXXXXX','".$authorizenet_cc_expires_month."-".$authorizenet_cc_expires_year."',FROM_UNIXTIME($time_stamp),'".$getcur."','$item_id','$item_type'";
}

$transaction_no = $paymentTable->Add_table ($HD_Form -> DBHandle, $QUERY_VALUES, $QUERY_FIELDS, 'cc_epayment_log', 'id');

$key = securitykey(EPAYMENT_TRANSACTION_KEY, $time_stamp."^".$transaction_no."^".$amount_string."^".$_SESSION["card_id"]."^".$item_id."^".$item_type);
write_log(LOGFILE_EPAYMENT, basename(__FILE__).' line:'.__LINE__." ---- EPAYMENT_TRANSACTION_KEY=".EPAYMENT_TRANSACTION_KEY."/".$time_stamp."^".$transaction_no."^".$amount_string."^".$_SESSION["card_id"]."^".$item_id."^".$item_type);
if (empty($transaction_no)) {
	exit(gettext("No Transaction ID found"));
}

$HD_Form -> create_toppage ($form_action);

if (!isset($currencies_list[mb_strtoupper($_SESSION['currency'])][2]) || !is_numeric($currencies_list[mb_strtoupper($_SESSION['currency'])][2])) {
	$mycur = 1;
} else {
	$mycur = $currencies_list[mb_strtoupper($_SESSION['currency'])][2]/$paycur;
	if ($payment == 'webmoney') {
		$getcur = $wm_purse_type;
		$two_currency=true;
	} elseif ($getcur!=mb_strtoupper($_SESSION['currency'])) $two_currency=true;
}

if (is_array($payment_modules->modules)) {
	$payment_modules->pre_confirmation_check();
}

// #### HEADER SECTION
$smarty->display( 'main.tpl');


if (isset($$payment->form_action_url)) {
    $form_action_url = $$payment->form_action_url;
} else {
    $form_action_url = tep_href_link("checkout_process", '', 'SSL');
}

echo tep_draw_form('checkout_confirmation', $form_action_url, 'post', null, $payment);

if (is_array($payment_modules->modules)) {
    echo $payment_modules->process_button($transaction_no, $key);
}
?>

<br><br>
<center>
<table width=85% align=center class="infoBox">
<tr height="15">
    <td colspan=3 class="infoBoxHeading" align=left>&nbsp;<font color=green><?php echo gettext("Please confirm your order")?></font></td>
</tr>
<tr>
    <td colspan=3>&nbsp;</td>
</tr>
<tr>
    <td align=center align=left><?php echo $SPOT[$payment];?>&nbsp;</td>
    <td><div align="right"><?php echo gettext("Payment Method");?>: &nbsp;</div></td>
    <td width=50% align="left"><?php echo mb_strtoupper(str_replace('creditcard','',$payment))?></td>
</tr>
<?php if(strcasecmp("invoice",$item_type)!=0){?>
<tr>
    <td COLSPAN=2 align=right><?php echo gettext("Amount")?>: &nbsp;</td>
    <td align=left>
    <?php
     echo round($amount,2)." ".$getcur;
     if($two_currency){
					echo " = ".round($amount/$mycur,3)." ".mb_strtoupper($_SESSION['currency']);	
	 }	
     ?> </td>
</tr>
<tr>
    <td COLSPAN=2 align=right><?php echo gettext("VAT")."(".$vat."%)"?>: &nbsp;</td>
    <td align=left>
    <?php
     echo round($vat_amount,2)." ".$getcur;
     if($two_currency && $vat_amount){
					echo " = ".round($vat_amount/$mycur,2)." ".mb_strtoupper($_SESSION['currency']);	
	 }	
     ?> </td>
</tr>
<?php } ?>
<?php if($mc_fee){?>
<tr>
    <td COLSPAN=2 align=right><?php echo gettext("PayPal comission fee")?>: &nbsp;</td>
    <td align=left>
    <?php
     echo $mc_fee." ".$getcur;
     $amount += $mc_fee;
     ?> </td>
</tr>
<?php } ?>
<tr>
    <td COLSPAN=2 align=right><?php echo gettext("Total Amount Incl. VAT")?>: &nbsp;</td>
    <th align=left><font color=green>
    <?php echo round($total_amount,2)." ".$getcur;
    ?></font>
    </th>
</tr>
<tr>
    <td COLSPAN=3>&nbsp;</td>
</tr>
<tr height="25">
   <td colspan=2 align=right class="main"><b><?php echo gettext("Please click button to confirm your order")?></b>: &nbsp;</td>
   <td halign=left>
   <input type="image" src="<?php echo Images_Path;?>/button_confirm_order.gif" alt="Confirm Order" border="0" align=left title="Confirm Order">
   &nbsp;</td>
</tr>
</table>
<br>
</form>
</center>


<?php

// #### FOOTER SECTION
$smarty->display( 'footer.tpl');

