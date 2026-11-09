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

include ("lib/customer.defines.php");
include ("lib/customer.module.access.php");
include ("./lib/Form/Class.FormHandler.inc.php");
include ("lib/customer.smarty.php");

if (true || !has_rights(ACX_SIP_IAX)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

getpost_ifset(array('atmenu','startnumber','quantity'));

if ($atmenu == "") $atmenu = "SIP";

if ($atmenu == "SIP")
    do {
	$QUERY = "SELECT extmin, extmax, extquantity, id, language FROM cc_card WHERE username = '" . $_SESSION["pr_login"] . "' AND uipass = '" . $_SESSION["pr_password"] . "'";
	$DBHandle_max = DbConnect();
	$resmax = $DBHandle_max->Execute($QUERY);
	if ($resmax) {
		$row = $resmax->fetchRow();
		$extmin 	= $row[0];
		$extmax 	= $row[1];
		$extquantity	= $row[2];
		$cardid 	= $row[3];
		$language	= $row[4];
		$extstart=1+$extmin;
	}
	$QUERY = "SELECT regexten, id FROM cc_sip_buddies WHERE id_cc_card = $cardid AND external = 0 ORDER BY regexten";
	$inst_table = new Table();
	$resmax = $inst_table->SQLExec($DBHandle_max, $QUERY);
	if ($resmax) {
		$extstart = (is_numeric($resmax[0][0]) && $extmin <= $resmax[0][0] && $resmax[0][0] <= $extmax) ? floor($resmax[0][0]/100)*100+1 : $extstart;
		$extlen = strlen($extstart);
		$exten_include = array();
		for ($k=0;$k<count($resmax);$k++) {
			$extquantity--;
			if (is_numeric($resmax[$k][0]) && $resmax[$k][0] == $extstart)	$extstart++;
			$exten_include[$resmax[$k][1]] = $resmax[$k][0];
		}
	}
	if ($form_action == "addexten") {
		unset($form_action);
		if ( !is_numeric($quantity) || !is_numeric($startnumber) || $quantity == '0' || startnumber == '0' || $quantity > $extquantity || $startnumber < $extmin || $extmax < $startnumber + $quantity -1 )
			break;
		$extcreated = gen_friends($cardid,$startnumber,$quantity,$extmin,$extmax,$DBHandle_max,$A2B,$language);
		if (!USE_REALTIME) {
//			include_once ("./lib/Form/Class.Realtime.php");
			$instance_realtime = new Realtime();
			$instance_realtime -> create_trunk_config_file ('sip');
		}
		if (RELOAD_ASTERISK_IF_SIPIAX_CREATED) {
			require_once ("./lib/phpagi/phpagi-asmanager.php");
			$as = new AGI_AsteriskManager();
			$res =@  $as->connect(MANAGER_HOST,MANAGER_USERNAME,MANAGER_SECRET);
			if ($res) {
				$res = $as->Command('sip reload');
				$as->disconnect();
			} else echo "Error : Manager Connection";
		}
		continue;
	}
	break;
} while (true);

include ("./form_data/FG_var_did_info.inc");
$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();

$HD_Form -> FG_EDITION_LINK	= $_SERVER['PHP_SELF']."?form_action=ask-edit&atmenu=$atmenu&id=";
$HD_Form -> FG_DELETION_LINK	= $_SERVER['PHP_SELF']."?form_action=ask-delete&atmenu=$atmenu&id=";

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if ($form_action == "addexten") unset($form_action);
if (!isset ($form_action)) $form_action = "list"; //ask-add
if (!isset ($action)) $action = $form_action;

$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
echo $CC_help_sipiax_info;

if ($form_action == "list") {
?>
<br/>
<center>
<table width="40%" border="0" align="center" cellpadding="0" cellspacing="1">
	<tr>
	  <td  class="bgcolor_021">
	  <table width="100%" border="0" cellspacing="1" cellpadding="0">
		  <tr>
			<td width="100%" bgcolor="#FFFFFF" class="fontstyle_006">&nbsp;<?php echo gettext("CARD")?></td>
			<td bgcolor="#FFFFFF" class="fontstyle_006"><?php echo $_SESSION["pr_login"]?></td>
		  </tr>
		  <tr>
			<td bgcolor="#FFFFFF" class="fontstyle_006">&nbsp;<?php echo gettext("CONFIGURATION TYPE")?> </td>
			<td bgcolor="#FFFFFF" class="fontstyle_006" align="right">
			    <form name="form1" method="post" action="">
				<select name="atmenu" id="col_configtype" onChange="window.document.form1.elements['PMChange'].value='Change';window.document.form1.submit();">
				<option value="IAX"<?php if($atmenu == "IAX")echo " selected"?>>IAX</option>
				<option value="SIP"<?php if($atmenu == "SIP")echo " selected"?>>SIP</option>
				</select>
				<input name="PMChange" type="hidden" id="PMChange">
			    </form>
			</td>
		  </tr>
	  </table></td>
	</tr>
</table>
</center>
<?php
}
if (isset($extcreated)) echo 'You have just created '.count($extcreated).' extensions';
if ($atmenu == "SIP" && $form_action == "list" && false) {
?>
<script language="JavaScript" src="./javascript/sipiax.js"></script>
<br/>
<div class="toggle_hide2show">
<center>
<a href="#" target="_self" class="toggle_menu"><img class="toggle_hide2show" src="<?php echo Images_Path; ?>/toggle_hide2show.png" onmouseover="this.style.cursor='hand';" HEIGHT="16"> <font class="fontstyle_002"><?php echo gettext("GENERATE EXTENSIONS");?> </font></a>
<div class="tohide" style="display:none;">
	<br/>
	<form NAME="theForm">
	<table class="infoBoxGeneration" border="0" cellspacing="9" cellpadding="0" align="center">
		<tr>
		    <td align="left" valign="bottom" nowrap="nowrap">
			<font style="font-weight:bold;"><?php echo gettext("Create extensions from");?>:</font><br/><sup>[<?php echo gettext("from");?> <?php echo $extmin;?> <?php echo gettext("to");?> <?php echo $extmax;?>]</sup>
		    </td><td nowrap="nowrap">
			&nbsp;<input class="form_input_text" name="startnumber" onfocus="clear_textbox();" size="10" maxlength="<?php echo $extlen;?>" value="<?php echo $extstart;?>">
		    </td>
		    <td align="justify" style="padding: 0 15px" rowspan="100%">
			<center><h3>Информация</h3></center>
			Задайте количество внутренних линий в Вашей АТС, и с какого номера они будут начинаться.
			Все внутренние линии обозначаются тремя цифрами и, например, если номер 703 уже существует и заказать 5 номеров начиная с 701, то будут созданы номера 701, 702, 704, 705, 706.
			Не обязательно создавать номера «с запасом», Вы всегда можете увеличить их количество.
		    </td>
		</tr>
		<tr><td align="left" valign="top">
			<font style="font-weight:bold;"><?php echo gettext("Quantity");?>:</font>
		    </td><td valign="top" nowrap="nowrap">
			&nbsp<input class="form_input_text" name="quantity" onfocus="clear_textbox2();" size="10" maxlength="3" value="1"><br/>
			&nbsp;<sup><em><?php echo gettext("Extensions left");?>:&nbsp;<?php echo $extquantity;?></em></sup>
		    </td>
		</tr>
		<tr>
		    <td align="right" valign="center">
			<input class="form_input_button" type="button" VALUE="<?php echo gettext(" CREATE ");?>" onClick="openURL('<?php echo $_SERVER['PHP_SELF']?>?form_action=addexten&startnumber=',<?php echo $extquantity;?>,'Вы привысили лимит на <?php echo $extquantity;?> номеров.\nМаксимальное количество <?php echo $row[2];?>.\nУ Вас уже создано <?php echo $row[2]-$extquantity;?> номеров.\nУменьшите количество новых номеров.','<?php echo gettext("Both fields must be numeric\\nand greater than zero");?>',<?php echo $extmin;?>,<?php echo $extmax;?>)">
		    </td>
		    <td></td>
		</tr>
	</table>
	</form>
</div>
</center>
</div>
<?php
}


// #### TOP SECTION PAGE
$HD_Form->create_toppage($form_action);

$HD_Form->create_form($form_action, $list, $id = null);

// #### FOOTER SECTION
$smarty->display('footer.tpl');
