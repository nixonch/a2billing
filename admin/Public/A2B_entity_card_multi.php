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


include ("../lib/admin.defines.php");
include ("../lib/admin.module.access.php");
include ("../lib/Form/Class.FormHandler.inc.php");
include ("./form_data/FG_var_card.inc");
include ("../lib/admin.smarty.php");

if (!has_rights(ACX_CUSTOMER)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}


getpost_ifset(array('nb_to_create', 'creditlimit', 'cardnum', 'addcredit', 'choose_tariff', 'gen_id', 'cardnum', 'choose_simultaccess',
	'choose_currency', 'choose_typepaid', 'creditlimit', 'enableexpire', 'expirationdate', 'expiredays', 'runservice', 'sip', 'iax',
	'cardnumberlenght_list', 'tag', 'id_group', 'discount', 'id_seria', 'id_didgroup', 'vat', 'id_country', 'mytimezone', 'language',
	'warning_threshold', 'say_dialednumber', 'say_rateinitial', 'say_timetotalk', 'say_balance_after_call', 'digit_password'));


$HD_Form->FG_FILTER_SEARCH_FORM = false;
$HD_Form->FG_EDITION = false;
$HD_Form->FG_DELETION = false;
$HD_Form->FG_OTHER_BUTTON1 = false;
$HD_Form->FG_OTHER_BUTTON2 = false;
$HD_Form->FG_FILTER_APPLY = false;
$HD_Form->FG_LIST_ADDING_BUTTON1 = false;
$HD_Form->FG_LIST_ADDING_BUTTON2 = false;

$HD_Form->setDBHandler(DbConnect());

$nb_error = 0;
$msg_error = '';
$group_error = false;
$tariff_error = false;
$credit_error = false;
$number_error = false;
$expdate_error = false;
$expday_error = false;

if ($action == "generate") {
	if (!is_numeric($nb_to_create) || $nb_to_create < 1) {
		$nb_error++;
		$number_error = true;
		$msg_error = gettext("- Choose the number of customers that you want generate!");
	}
	if (!is_numeric($choose_tariff) || $choose_tariff < 1) {
		$nb_error++;
		$tariff_error = true;
		if (!empty ($msg_error))
			$msg_error .= "<br/>";
		$msg_error .= gettext("- Choose a CALL PLAN for the customers!");
	}
	if (!is_numeric($id_group) || $id_group < 1) {
		$nb_error++;
		$group_error = true;
		if (!empty ($msg_error))
			$msg_error .= "<br/>";
		$msg_error .= gettext("- Choose a GROUP for the customers!");
	}
	if (!is_numeric($addcredit) || $addcredit < 0) {
		$nb_error++;
		$credit_error = true;
		if (!empty ($msg_error))
			$msg_error .= "<br/>";
		$msg_error .= gettext("- Choose a BALANCE (initial amount)  equal or higher than 0 for the customers!");
	}
	if (!is_numeric($expiredays) || $expiredays < 0) {
		$nb_error++;
		$expday_error = true;
		if (!empty ($msg_error))
			$msg_error .= "<br/>";
		$msg_error .= gettext("- Choose an EXPIRATIONS DAYS  equal or higher than 0 for the customers!");
	}
	if (empty ($expirationdate) || strtotime($expirationdate) === false) {
		$nb_error++;
		$expdate_error = true;
		if (!empty ($msg_error))
			$msg_error .= "<br/>";
		$msg_error .= gettext("- EXPIRATION DAY inserted is invalid, it must respect the date format YYYY-MM-DD HH:MM:SS (time is optional) !");
	}
	if ($language == "0") {
		$nb_error++;
		$language_error = true;
		if (!empty ($msg_error))
			$msg_error .= "<br/>";
		$msg_error .= gettext("- Choose a Language for the customers!");
	}
	if (end($A2B -> cardnumber_range) != $cardnumberlenght_list && $digit_password) {
		$digit_password = 0;
	}
	if ($choose_typepaid == "0") {
		$creditlimit = 0;
	}
	if ($warning_threshold != "-1") {
		$warning_threshold = "-2";
	}
}
$nbcard = $nb_to_create;
if ($nbcard > 0 && $action == "generate" && $nb_error == 0) {

	check_demo_mode();

	$instance_realtime = new Realtime();

	$FG_ADITION_SECOND_ADD_TABLE = "cc_card";
	$FG_ADITION_SECOND_ADD_FIELDS = "username, useralias, credit, tariff, activated, lastname, firstname, email, address, city, state, country, zipcode, phone, simultaccess, currency, typepaid, " .
			"creditlimit, enableexpire, expirationdate, expiredays, uipass, runservice, tag,id_group, discount, id_seria, id_didgroup, sip_buddy, iax_buddy, vat, language";

	if (DB_TYPE != "postgres") {
		$FG_ADITION_SECOND_ADD_FIELDS .= ",creationdate ";
	}
	
	$instance_sub_table = new Table($FG_ADITION_SECOND_ADD_TABLE, $FG_ADITION_SECOND_ADD_FIELDS);
	
	$gen_id = time();
	$_SESSION["IDfilter"] = $gen_id;
	
	$sip_buddy = $iax_buddy = 0;
	if (isset ($sip) && $sip == 1)
	    $sip_buddy = 1;
	if (isset ($iax) && $iax == 1)
	    $iax_buddy = 1;

	$creditlimit = is_numeric($creditlimit) ? $creditlimit : 0;
	//initialize refill parameter
	$description_refill = gettext("CREATION CARD REFILL");
	$field_insert_refill = " credit, card_id, description";
	$instance_refill_table = new Table("cc_logrefill", $field_insert_refill);

	for ($k = 0; $k < $nbcard; $k++) {
		$arr_card_alias = gen_card_with_alias($FG_ADITION_SECOND_ADD_TABLE . ", cc_voucher", 0, $cardnumberlenght_list);
		$accountnumber = $arr_card_alias[0];
		$useralias = $arr_card_alias[1];
		if (!is_numeric($addcredit))
			$addcredit = 0;
		$passui_secret = $digit_password==1 ? $accountnumber : MDP_STRING(10);

		$FG_ADITION_SECOND_ADD_VALUE = "'$accountnumber', '$useralias', '$addcredit', '$choose_tariff', 't', '$gen_id', '', '', '', '', '', '$id_country', '', '', $choose_simultaccess, '$choose_currency', " .
					"$choose_typepaid, $creditlimit, $enableexpire, '$expirationdate', $expiredays, '$passui_secret', '$runservice', '$tag', '$id_group', '$discount', '$id_seria', " .
					"'$id_didgroup', $sip_buddy, $iax_buddy, '$vat', '$language'";

		if (DB_TYPE != "postgres")
			$FG_ADITION_SECOND_ADD_VALUE .= ", now() ";
		

		$id_cc_card = $instance_sub_table->Add_table($HD_Form->DBHandle, $FG_ADITION_SECOND_ADD_VALUE, null, null, $HD_Form->FG_TABLE_ID);
		//create refill for each cards

		if ($addcredit > 0) {
			$value_insert_refill = "'$addcredit', '$id_cc_card', '$description_refill' ";
			$instance_refill_table->Add_table($HD_Form->DBHandle, $value_insert_refill, null, null);
		}

		$instance_realtime -> insert_voip_config ($sip, $iax, $id_cc_card, $accountnumber, $passui_secret, $useralias, $language, $warning_threshold, $say_dialednumber, $say_rateinitial, $say_timetotalk, $say_balance_after_call);
	}
	
	// Save Sip accounts to file
	if (isset ($sip)) {
		$instance_realtime -> create_trunk_config_file ('sip');
	}
//	if (isset ($sip) || isset ($iax)) {
//		if (RELOAD_ASTERISK_IF_SIPIAX_CREATED) {
//			self::create_sipiax_friends_reload();
//		} else {
//			self::create_sipiax_friends();
//		}
//	}
	// Save IAX accounts to file
	if (isset ($iax)) {
		$instance_realtime -> create_trunk_config_file ('iax');
	}
}
if (!isset ($_SESSION["IDfilter"]))
	$_SESSION["IDfilter"] = 'NODEFINED';

$HD_Form->FG_TABLE_CLAUSE = " lastname='" . $_SESSION["IDfilter"] . "'";

// END GENERATE CARDS

$HD_Form->init();

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if (!isset ($form_action))
	$form_action = "list"; //ask-add
if (!isset ($action))
	$action = $form_action;

$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($form_action == "list" || $popup_select < 2)
    echo $CC_help_generate_customer;

$instance_table_tariff = new Table("cc_tariffgroup", "id, tariffgroupname");
$FG_TABLE_CLAUSE = "";
$list_tariff = $instance_table_tariff->Get_list($HD_Form->DBHandle, $FG_TABLE_CLAUSE, "tariffgroupname", "ASC", null, null, null, null);
$nb_tariff = count($list_tariff);
$instance_table_group = new Table("cc_card_group", " id, name ");
$list_group = $instance_table_group->Get_list($HD_Form->DBHandle, $FG_TABLE_CLAUSE, "name", "ASC", null, null, null, null);

$instance_table_agent = new Table("cc_agent", " id, login ");
$list_agent = $instance_table_agent->Get_list($HD_Form->DBHandle, $FG_TABLE_CLAUSE, "login", "ASC", null, null, null, null);

$instance_table_seria = new Table("cc_card_seria", " id, name ");
$list_seria = $instance_table_seria->Get_list($HD_Form->DBHandle, $FG_TABLE_CLAUSE, "name", "ASC", null, null, null, null);

$instance_table_didgroup = new Table("cc_didgroup", " id, didgroupname ");
$list_didgroup = $instance_table_didgroup->Get_list($HD_Form->DBHandle, $FG_TABLE_CLAUSE, "didgroupname", "ASC", null, null, null, null);

$instance_table_country = new Table("cc_country", " countrycode, countryname ");
$list_country = $instance_table_country->Get_list($HD_Form->DBHandle, $FG_TABLE_CLAUSE, "countryname", "ASC", null, null, null, null);

$list_language = Constants::getLanguagesList();

// FORM FOR THE GENERATION
?>
<script type="text/javascript">
<!--
  function showDigpass(el1,el2,maxlen) {
    cel2 = document.getElementById(el2);
    if(el1.options[el1.selectedIndex].value==maxlen) {
	cel2.style.display = 'inline';
//	cel2.style.color = '#E5E5E5';
//	$(cel2).animate({ color: "#000666"});
    } else {
	cel2.style.display = 'none';
//	$(cel2).animate({ width: 'hide', opacity: 'hide' }, function(){ $(this).css({display:"none"}); });
    }
  }
  function onoff() {
//    if(document.getElementById("ddd").style.display=='block') {}
    if($(this).is(':checked')) {
	$('#ddd').animate({ height: 'show', opacity: 'show' }, function(){ $(this).css({display:"block"}); });
    } else {
	$('#ddd').animate({ height: 'hide', opacity: 'hide' }, function(){ $(this).css({display:"none"}); });
    }
  }
-->
</script>
<div align="center" style="white-space: nowrap;">
<?php if(!empty($msg_error) && $nb_error>0 ){ ?>
	<div class="msg_error" style="width:70%;text-align:left;">
		<?php echo $msg_error ?>
	</div>
<?php } ?>
<form name="theForm" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
<table border="0" width="735px" align="center">
<tr>
	<td align="left" class="bgcolor_001">
	<ol class="form_list_ol">
	<li>
	 <?php echo gettext("Number of customers to create")?> : 
		<input class="form_input_text" name="nb_to_create" size="5" maxlength="5" value="<?php echo $nb_to_create; ?>" >
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png"/> <?php echo gettext("(an high value will load your system!)");?>
	</li>
	<li>
	<?php echo gettext("Length of card number :");?>
	<select name="cardnumberlenght_list" size="1" class="form_input_select" onChange="showDigpass(this,'digpass',<?php echo end($A2B -> cardnumber_range);?>)">
	<?php
	foreach ($A2B -> cardnumber_range as $value){
	?>
		<option value='<?php echo $value ?>' 
		<?php if ($value == $cardnumberlenght_list) echo "selected";
		?>> <?php echo $value." ".gettext("Digits");?>&nbsp;</option>
	<?php
	}
	?></select>&nbsp;&nbsp;
	<div style="position: relative; display:<?php if($cardnumberlenght_list==end($A2B -> cardnumber_range)) echo "inline"; else echo "none";?>" id="digpass">
	<?php echo gettext("Numeric password based on ").end($A2B -> cardnumber_range).gettext(" digits, same the card number");?> : <input class="form_input_checkbox" type="checkbox" name="digit_password" value="1" <?php if($digit_password==1) echo "checked" ?>>
	</div>
	</li>
	<li>
	<?php if($tariff_error) {
		echo "<font color=\"#C14430\">".gettext("Call plan")."</font>";
	} else	echo gettext("Call plan");
	?> : 
	<select NAME="choose_tariff" size="1" class="form_input_select" >
		<option value=''><?php echo gettext("Choose a Call Plan");?></option>
	<?php foreach ($list_tariff as $recordset){ ?>
		<option class=input value='<?php echo $recordset[0]?>' <?php if($recordset[0]==$choose_tariff) echo "selected"; ?> ><?php echo $recordset[1]?>&nbsp;</option>
	<?php } ?>
	</select>
	<?php if($tariff_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	</li>
	<li>
	<?php if($credit_error) {
		echo "<font color=\"#C14430\">".gettext("Initial amount of credit")."</font>";
	} else	echo gettext("Initial amount of credit");
	?> : <input class="form_input_text" value="<?php if(is_numeric($addcredit) && $addcredit>0) echo $addcredit; else echo 0;?>" name="addcredit" size="10" maxlength="10" >
	<?php if($credit_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	</li>
	<li>
	<?php echo gettext("Simultaneous access");?> : 
	<select NAME="choose_simultaccess" size="1" class="form_input_select" >
		<option value='0' <?php if($choose_simultaccess== 0 || empty($choose_simultaccess)) echo "selected"; ?>><?php echo gettext("INDIVIDUAL ACCESS");?>&nbsp;</option>
		<option value='1' <?php if($choose_simultaccess== 1) echo "selected"; ?>><?php echo gettext("SIMULTANEOUS ACCESS");?>&nbsp;</option>
	   </select>
	</li>
	<li>
	<?php echo gettext("Currency");?> :
	<select NAME="choose_currency" size="1" class="form_input_select" >
	<?php foreach($currencies_list as $key => $cur_value) { ?>
		<option value='<?php echo $key ?>' <?php if($choose_currency== $key) echo "selected"; ?>><?php echo $cur_value[1].' ('.$cur_value[2].')' ?>&nbsp;</option>
	<?php } ?>
	</select>
	</li>
	<li>
	<?php echo gettext("Card type");?> :
	<select NAME="choose_typepaid" size="1" class="form_input_select" onChange="showDigpass(this,'credlimit',1)">
		<option value='0' <?php if($choose_typepaid== 0 || empty($choose_typepaid)) echo "selected"; ?>><?php echo gettext("PREPAID CARD");?>&nbsp;</option>
		<option value='1' <?php if($choose_typepaid== 1) echo "selected"; ?>><?php echo gettext("POSTPAY CARD");?>&nbsp;</option>
	   </select>
	<div style="white-space: nowrap; display:<?php if($choose_typepaid==0) echo "none"; else echo "inline";?>" id="credlimit">
	&nbsp;&nbsp;<?php echo gettext("Credit Limit of postpay");?> : <input class="form_input_text" value="<?php if(is_numeric($creditlimit) && $creditlimit>0) echo $creditlimit; else echo 0;?>" name="creditlimit" size="10" maxlength="16" >
	</div>
	</li>
	<li>
   	<?php echo gettext("Enable expire");?>&nbsp;: 
	<select name="enableexpire" class="form_input_select" >
		<option value="0" <?php if($enableexpire== 0 || empty($enableexpire)) echo "selected"; ?>> <?php echo gettext("NO EXPIRATION");?>&nbsp;</option>
		<option value="1" <?php if($enableexpire== 1) echo "selected";?> > <?php echo gettext("EXPIRE DATE");?>&nbsp;</option>
		<option value="2" <?php if($enableexpire== 2) echo "selected";?> > <?php echo gettext("EXPIRE DAYS SINCE FIRST USE");?>&nbsp;</option>
		<option value="3" <?php if($enableexpire== 3) echo "selected"; ?> > <?php echo gettext("EXPIRE DAYS SINCE CREATION");?>&nbsp;</option>
	</select>
	<?php 
		$begin_date = date("Y");
		$begin_date_plus = date("Y")+10;	
		$end_date = date("-m-d H:i:s");
		$comp_date = "value='".$begin_date.$end_date."'";
		$comp_date_plus = "value='".$begin_date_plus.$end_date."'";
	?>
	</li>
	<li>
	<?php if($expdate_error) {
		echo "<font color=\"#C14430\">".gettext("Expiry Date")."</font>";
	} else	echo gettext("Expiry Date");
	?> : <input class="form_input_text"  name="expirationdate" size="19" maxlength="19" <?php if(!empty($expirationdate)) echo "value='$expirationdate'"; else echo $comp_date_plus;?> > <?php echo gettext("(Format YYYY-MM-DD HH:MM:SS)");?>
	<?php if($expdate_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>&nbsp;
	</li>
	<li>
	<?php if($expday_error) {
		echo "<font color=\"#C14430\">".gettext("Expiry days")."</font>";
	} else	echo gettext("Expiry days");
	?> : <input class="form_input_text"  name="expiredays" size="10" maxlength="6" value="<?php if(is_numeric($expiredays) && $expiredays>0) echo $expiredays; else echo 0;?>">
	<?php if($expday_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	</li>
	<li>
	<?php echo gettext("Run service");?> : 
	<?php echo gettext("Yes");?> <input class="form_input_checkbox" name="runservice" value="1" <?php if($runservice==1) echo "checked='checked'" ?> type="radio"> - <?php echo gettext("No");?> <input class="form_input_checkbox" name="runservice" value="0" <?php if($runservice==0 || empty($runservice) ) echo "checked='checked'" ?>  type="radio">
	</li>
	<li>
   <?php echo gettext("Create SIP/IAX Extension");?>&nbsp;: <?php echo gettext("SIP")?> <input class="form_input_checkbox" type="checkbox" name="sip" value="1" <?php if($sip==1) echo "checked" ?> onclick="onoff.call(this)"> <?php echo gettext("IAX")?> : <input class="form_input_checkbox" type="checkbox" name="iax" value="1" <?php if($iax==1 ) echo "checked ";?>disabled readonly title="Temporary out of service">
	</li>
	<div style="display:<?php if($sip==1) echo "block"; else echo "none";?>" id="ddd">
	<li>
   <?php echo gettext("Say balance before call");?>&nbsp;: <input class="form_input_checkbox" type="checkbox" name="warning_threshold" value="-1" <?php if($warning_threshold==-1) echo "checked" ?>>
	</li>
	<li>
   <?php echo gettext("Say the dialed number before call");?>&nbsp;: <input class="form_input_checkbox" type="checkbox" name="say_dialednumber" value="1" <?php if($say_dialednumber==1) echo "checked" ?>>
	</li>
	<li>
   <?php echo gettext("Say rate initial before call");?>&nbsp;: <input class="form_input_checkbox" type="checkbox" name="say_rateinitial" value="1" <?php if($say_rateinitial==1) echo "checked" ?>>
	</li>
	<li>
   <?php echo gettext("Say time to talk before call");?>&nbsp;: <input class="form_input_checkbox" type="checkbox" name="say_timetotalk" value="1" <?php if($say_timetotalk==1) echo "checked" ?>>
	</li>
	<li>
   <?php echo gettext("Say balance after call");?>&nbsp;: <input class="form_input_checkbox" type="checkbox" name="say_balance_after_call" value="1" <?php if($say_balance_after_call==1) echo "checked" ?>>
	</li>
	</div>
	<li>
	<?php echo gettext("Tag");?> : <input class="form_input_text"  name="tag" size="40" maxlength="40" <?php if(!empty($tag)) echo "value='$tag'"; ?> > 
	</li>
	<li>
	<?php if($group_error) {
		echo "<font color=\"#C14430\">".gettext("Customer group")."</font>";
	} else	echo gettext("Customer group");
	?> : 
	<select NAME="id_group" size="1" class="form_input_select" >
	<option value=''><?php echo gettext("Choose a group");?></option>
	<?php foreach ($list_group as $recordset){ ?>
		<option class=input value='<?php echo $recordset[0]?>' <?php if($recordset[0]==$id_group) echo "selected"; ?> ><?php echo $recordset[1]?>&nbsp;</option>
	<?php } ?>
	</select>
	<?php if($group_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	</li>
	<li>
	<?php echo gettext("Discount");?> :
	<select NAME="discount" size="1" class="form_input_select" >
	<option value='0'><?php echo gettext("NO DISCOUNT");?>&nbsp;</option>
	<?php for($i=1;$i<99;$i++){ ?>
		<option class=input value='<?php echo $i; ?>' <?php if($i==$discount) echo "selected"; ?> ><?php echo $i;?>%&nbsp;</option>
	<?php } ?>
	</select>
	</li>
	<li>
	<?php echo gettext("Serie");?> :
	<select NAME="id_seria" size="1" class="form_input_select" >
	<option value=''><?php echo gettext("Choose a Series");?>&nbsp;</option>
	<?php foreach ($list_seria as $recordset){ ?>
	<option class=input value='<?php echo $recordset[0]?>'  <?php if($recordset[0]==$id_seria) echo "selected"; ?>  ><?php echo $recordset[1]?>&nbsp;</option>
	<?php } ?>
	</select>
	</li>
	<li>
	<?php if($didgroup_error) {
		echo "<font color=\"#C14430\">".gettext("DID GROUP")."</font>";
	} else	echo gettext("DID GROUP");
	?> : 
	<select NAME="id_didgroup" size="1" class="form_input_select" >
	<option value='0'><?php echo gettext("Choose a DID Group");?>&nbsp;</option>
	<?php foreach ($list_didgroup as $recordset){ ?>
		<option class=input value='<?php echo $recordset[0]?>' <?php if($recordset[0]==$id_didgroup) echo "selected"; ?> ><?php echo $recordset[1]?>&nbsp;</option>
	<?php } ?>
	</select>
	<?php if($didgroup_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	</li>
	<li>
	<?php echo gettext("VAT");?> : <input class="form_input_text"  name="vat" size="10" maxlength="10" <?php if(!empty($vat)) echo "value='$vat'"; ?> > 
	</li>
	<li>
	<?php if($language_error) {
		echo "<font color=\"#C14430\">".gettext("Language")."</font>";
	} else	echo gettext("Language");
	?> : 
	<select NAME="language" size="1" class="form_input_select" >
	<option class=input value="0"><?php echo gettext("Choose a Language");?>&nbsp;</option>
	<?php foreach ($list_language as $recordset){ ?>
		<option class=input value='<?php echo $recordset[1]?>' <?php if($recordset[1]==$language) echo "selected"; ?> ><?php echo $recordset[0]?>&nbsp;</option>
	<?php } ?>
	</select>
	<?php if($language_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	</li>
	<li>
	<?php if($country_error) {
		echo "<font color=\"#C14430\">".gettext("Country")."</font>";
	} else	echo gettext("Country");
	?> : 
	<select NAME="id_country" size="1" class="form_input_select" >
	<option value='0'><?php echo gettext("Choose a country");?>&nbsp;</option>
	<?php foreach ($list_country as $recordset){ ?>
		<option class=input value='<?php echo $recordset[0]?>' <?php if($recordset[0]==$id_country) echo "selected"; ?> ><?php echo $recordset[1]?>&nbsp;</option>
	<?php } ?>
	</select>
	<?php if($country_error){ ?>
		<img style="vertical-align:middle;" src="<?php echo Images_Path;?>/exclamation.png" />
	<?php } ?>
	
	</ol>
	</td>
</tr>
<tr>
	<td align="right">
		<input name="action"  value="generate" type="hidden"/>
		<input class="form_input_button"  value=" GENERATE CUSTOMERS " type="submit"/>
	</td>
</tr>
</table>
</form>
</div>
</div>
<br>
<?php
// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

$HD_Form -> create_form ($form_action, $list, $id=null) ;


$_SESSION[$HD_Form->FG_EXPORT_SESSION_VAR]= "SELECT ".$HD_Form -> FG_EXPORT_FIELD_LIST." FROM $HD_Form->FG_TABLE_NAME";
if (strlen($HD_Form->FG_TABLE_CLAUSE)>1) 
	$_SESSION[$HD_Form->FG_EXPORT_SESSION_VAR] .= " WHERE $HD_Form->FG_TABLE_CLAUSE ";
if (!is_null ($HD_Form->FG_ORDER) && ($HD_Form->FG_ORDER!='') && !is_null ($HD_Form->FG_SENS) && ($HD_Form->FG_SENS!='')) 
	$_SESSION[$HD_Form->FG_EXPORT_SESSION_VAR].= " ORDER BY $HD_Form->FG_ORDER $HD_Form->FG_SENS";

// #### FOOTER SECTION
$smarty->display('footer.tpl');

