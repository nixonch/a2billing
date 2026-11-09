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

getpost_ifset(array ('payment_error'));


$currencies_list = get_currencies();
$two_currency = false;

if ( !isset($currencies_list[mb_strtoupper($_SESSION['currency'])][2]) || !is_numeric($currencies_list[mb_strtoupper($_SESSION['currency'])][2]) ) {
	$mycur = 1;
} else {
	$mycur = $currencies_list[mb_strtoupper($_SESSION['currency'])][2];
	$display_currency = mb_strtoupper($_SESSION['currency']);
	if (mb_strtoupper($_SESSION['currency'])!=mb_strtoupper(BASE_CURRENCY))
	    $two_currency=true;
}


$HD_Form = new FormHandler("cc_payment_methods","payment_method");

getpost_ifset(array('item_id','item_type'));
$DBHandle =DbConnect();
$HD_Form -> setDBHandler ($DBHandle);
$HD_Form -> init();



$static_amount = false;
$amount=0;
if($item_type == "invoice" && is_numeric($item_id)){
	$table_invoice = new Table("cc_invoice", "status, paid_status");
	$clause_invoice = "id = ".$item_id;
	$result= $table_invoice -> Get_list($DBHandle,$clause_invoice);
	if(is_array($result) && $result[0]['status']==1 && $result[0]['paid_status']==0 ){
		$table_invoice_item = new Table("cc_invoice_item","COALESCE(SUM(price*(1+(vat/100))),0)");
		$clause_invoice_item = "id_invoice = ".$item_id;
		$result= $table_invoice_item -> Get_list($DBHandle,$clause_invoice_item);
		$amount = $result[0][0];
		$amount = ceil($amount*100)/100;
		$static_amount = true;
	} else{
		Header ("Location: userinfo");
		die;
	}
}



$inst_table = new Table();

$QUERY = "SELECT credit, status FROM cc_card WHERE username = '" . $_SESSION["pr_login"] ."' AND uipass = '" . $_SESSION["pr_password"] . "'";

$customer_info = $inst_table -> SQLExec($DBHandle, $QUERY);

if (!$customer_info || !is_array($customer_info)) {
    echo gettext("Error loading your account information!");
    exit ();
}

if ($customer_info[0][1] != "1" && $customer_info[0][1] != "8") {
    Header("HTTP/1.0 401 Unauthorized");
    Header("Location: PP_error?c=accessdenied");
    die();
}

$credit_cur = $customer_info[0][0] / $mycur;
$credit_cur = round($credit_cur, 3);




// #### HEADER SECTION
$smarty->display( 'main.tpl');

$HD_Form -> create_toppage ($form_action);


$payment_modules = new payment;

?>
<script language="javascript">
function checkamount()
{
 	if (document.checkout_amount.amount == "")
	{
		alert('Please enter some amount.');
		return false;
	}
	return true;
}
</script>
<script language="javascript"><!--
var selected;

function selectRowEffect(object, buttonSelect) {
	if (!selected) {
		if (document.getElementById) {
			selected = document.getElementById('defaultSelected');
		} else {
			selected = document.all['defaultSelected'];
		}
	}

	if (selected) selected.className = 'moduleRow';
	object.className = 'moduleRowSelected';
	selected = object;

	// one button is not an array
	if (document.checkout_payment.payment[0]) {
		document.checkout_payment.payment[buttonSelect].checked=true;
	} else {
		document.checkout_payment.payment.checked=true;
	}
}

function rowOverEffect(object) {
	if (object.className == 'moduleRow') object.className = 'moduleRowOver';
}

function rowOutEffect(object) {
	if (object.className == 'moduleRowOver') object.className = 'moduleRow';
}
//--></script>

<?php echo $payment_modules->javascript_validation(); ?>
<center>
<?php
	if ($A2B->config["epayment_method"]['enable'] && $ACXPAYMENT_HISTORY >0 && sizeof($payment_methods)>1)
	    echo $PAYMENT_METHOD;
	else
// #### HELP SECTION
	    echo $CC_help_empty;
?>
<br>


    <table width="80%" cellspacing="0" cellpadding="2" align=center>
<?php
    $selection = $payment_modules->selection();
?>





	<table class="infoBox" width="85%" cellspacing="0" cellpadding="2" align=center>
	<tr>
	<td align=center>
	<font class="fontstyle_002"><?php echo gettext("BALANCE REMAINING");?> :</font><font class="fontstyle_007"> <?php echo $credit_cur.' '.gettext($_SESSION['currency']); ?> </font>
	</td>
	</tr>
	</table><br><br>

<?php
    if (isset($payment_error) && is_object(${$payment_error}) && ($error = ${$payment_error}->get_error())) {
        write_log(LOGFILE_EPAYMENT, basename(__FILE__).' line:'.__LINE__." ERROR ".$error['title']." ".$error['error']);
?>
  <table class="infoBox" width="85%" cellspacing="0" cellpadding="2" align=center>
      <tr>
        <td ><table border="0" width="100%" cellspacing="0" cellpadding="2">
          <tr>
            <td class="main" ><b><?php echo tep_output_string_protected(gettext($error['title'])); ?></b></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table border="0" width="100%" cellspacing="1" cellpadding="2" class="infoBoxNotice">
          <tr class="infoBoxNoticeContents">
            <td><table border="0" width="100%" cellspacing="0" cellpadding="2">
              <tr>
                <td><?php echo tep_draw_separator('clear.gif', '10', '1'); ?></td>
                <td class="main" width="100%" valign="top"><?php echo tep_output_string(gettext($error['error'])); ?></td>
                <td><?php echo tep_draw_separator('clear.gif', '10', '1'); ?></td>
              </tr>
            </table></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><?php echo tep_draw_separator('clear.gif', '100%', '10'); ?></td>
      </tr>
  </table>
  <br><br>

<?php
    }
?>
  <table class="infoBox" width="85%" cellspacing="0" cellpadding="2" align=center rules=rows>
<?php
    if ($A2B->config["epayment_method"]['enable'] && $ACXPAYMENT_HISTORY >0) {
    $radio_buttons = 0;
    $form_action_url = tep_href_link("checkout_confirmation", '', 'SSL');
    for ($i=0, $n=sizeof($selection); $i<$n; $i++) {
    echo tep_draw_form('checkout_amount'.$i, $form_action_url, 'post', 'onsubmit="checkamount()"');
?>
<tr>
  <td><table width="100%" border="0" cellspacing="0" cellpadding="2">
	<input name="item_id" type=hidden value="<?php echo $item_id?>">
	<input name="item_type" type=hidden value="<?php echo $item_type?>">
	<input name="payment" type=hidden value="<?php echo $selection[$i]['id']?>">
    <tr height="50px">
      <td width="150px" align=center>
	<?php echo $SPOT[$selection[$i]['id']];?>
      </td>
      <td class="main" colspan="2" align=left>
	<font class="fontstyle_002"><?php echo gettext("Refill");?> :</font>
	<input class="form_input_text" name="amount" size="10" maxlength="10">
<?php
        if (isset($selection[$i]['error'])) {
?>
	<class="main"><?php echo $selection[$i]['error']; ?>
<?php
        } elseif (isset($selection[$i]['fields']) && is_array($selection[$i]['fields'])) {
          if (sizeof($selection[$i]['fields'])>1){?>
	<input name="wm_purse_type" type=hidden value="<?php echo mb_strtoupper(BASE_CURRENCY)?>">
      <?php echo mb_strtoupper(BASE_CURRENCY)?>
      </td>
      <td style="padding-right:18px;" align=right>
	<input type="submit" class="form_input_button" value=" <?php echo '>> '.gettext("Continue")?> " alt="Continue"  title="Continue">
<?php	  }
	if (sizeof($selection[$i]['fields'])==1) {
	  echo $selection[$i]['fields'][0]['field'];?>
      </td>
      <td style="padding-right:18px;" align=right>
	<input type="submit" class="form_input_button" value=" <?php echo '>> '.gettext("Continue")?> " alt="Continue"  title="Continue">
      &nbsp;</td>
<?php	}
       else {?>
      &nbsp;</td>
    </tr>
    <td width="150px"></td><td colspan="100%"><table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>
<?php
       for ($j=0, $n2=sizeof($selection[$i]['fields']); $j<$n2; $j++) {
?>
    <tr>
      <td class="main" width="144px" align=left><?php echo $selection[$i]['fields'][$j]['title']?></td>
      <td class="main" align=left><?php echo $selection[$i]['fields'][$j]['field']?></td>
    </tr>
<?php
       }
?>
    </td></tr></table></td>
<?php
       }
    } else {
?>
	<input name="wm_purse_type" type=hidden value="<?php echo mb_strtoupper(BASE_CURRENCY)?>">
	<?php echo mb_strtoupper(BASE_CURRENCY)?>
      </td>
      <td style="padding-right:18px;" align=right>
	<input type="submit" class="form_input_button" value=" <?php echo '>> '.gettext("Continue")?> " alt="Continue"  title="Continue">
      &nbsp;</td>
    </tr>
<?php
    }
?>
</table>
</td></tr>
	</form>
<?php
    $radio_buttons++;
  }}
?>
	</table>

<?php

// #### FOOTER SECTION
$smarty->display( 'footer.tpl');

