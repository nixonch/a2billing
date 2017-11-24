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
include ("./form_data/FG_var_callerid.inc");
include ("lib/customer.smarty.php");

getpost_ifset(array('add_callerid'));

if ($atmenub == "")	$atmenub = "ALL";

$HD_Form -> setDBHandler (DbConnect());

if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

if (is_numeric($idcust) && $idcust != $_SESSION['card_id'] && (array_search($form_action, array("ask-edit", "ask-delete")) !== false || array_search($action, array("edit", "delete")) !== false)) {
	$table_diller_security = new Table("cc_callerid LEFT JOIN cc_card ON cc_card.id=id_cc_card", "cc_callerid.id");
	$clause_diller_security = "id_cc_card=$idcust AND cc_card.id_diller = {$_SESSION['card_id']}";
	$result_security = $table_diller_security -> Table_count ($HD_Form -> DBHandle, $clause_diller_security);
} else	$result_security = true;

if (!has_rights(ACX_CALLER_ID) || !$result_security) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}

// ADD SPEED DIAL
if (strlen($add_callerid)>0) {
	$instance_sub_table = new Table("cc_callerid", "*");
	$QUERY = "SELECT id FROM cc_card WHERE id = $idcard OR id_diller = $idcard";
	$result = $instance_sub_table -> SQLExec ($HD_Form -> DBHandle, $QUERY);
	if ($result) {
		$clause_limit = "id_cc_card = $idcard";
		$result = $instance_sub_table -> Table_count ($HD_Form -> DBHandle, $clause_limit);
	} else {
		Header ("HTTP/1.0 401 Unauthorized");
		Header ("Location: PP_error.php?c=accessdenied");
		die();
	}
// CHECK IF THE AMOUNT OF CALLERID IS LESS THAN THE LIMIT
	if ($result < $A2B->config["webcustomerui"]['limit_callerid']) {
		$QUERY = "INSERT INTO cc_callerid (id_cc_card, cid) VALUES ($idcard, '".$add_callerid."')";
		$result = $instance_sub_table -> SQLExec ($HD_Form -> DBHandle, $QUERY, 0);
		$log = new Logger();
		$log -> insertLog_Update($_SESSION["card_id"], 2, "СALLERID IS ADDED", $add_callerid, 'cc_callerid', $_SERVER['REMOTE_ADDR'], 'A2B_entity_callerid.php', '', 2);
		$log = null;
		$_SESSION["last_page"] = "A2B_entity_callerid.php";
	}
}

$HD_Form -> init();

$HD_Form -> FG_EDITION_LINK = $_SERVER['PHP_SELF']."?form_action=ask-edit&popup_select=$popup_select&idcust=$idcust&id=";
$HD_Form -> FG_DELETION_LINK = $_SERVER['PHP_SELF']."?form_action=ask-delete&popup_select=$popup_select&idcust=$idcust&id=";

// My Code for Where Cluase
if (strlen($HD_Form -> FG_EDITION_CLAUSE)>0)
	$HD_Form -> FG_EDITION_CLAUSE .= " AND ";
$HD_Form -> FG_EDITION_CLAUSE .= "id_cc_card = ".$idcard;
//if ($form_action == "ask-delete" || $form_action == "delete") {
//	$HD_Form -> FG_EDITION_CLAUSE .= " AND verify = 0";
//}
if ($id!="" || !is_null($id)){
	$HD_Form -> FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form -> FG_EDITION_CLAUSE);
}

$list = $HD_Form -> perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

?>
<center>
<?php
if (has_rights(ACX_DISTRIBUTION) && ($popup_select>=1)) {
	$instance_table_card = new Table('cc_card');
	$QUERY = "SELECT lastname, firstname FROM cc_card WHERE id = $idcust AND id_diller = " . $_SESSION["card_id"];
	$resmax = $instance_table_card -> SQLExec ($HD_Form -> DBHandle, $QUERY, 1);
	if ($resmax) {
		echo "<u>".$resmax[0][0]." ".$resmax[0][1]."</u><br>";
	} else exit();
}

if ($form_action == "list") {
?>
<center>
<table border="0" align="center" cellpadding="0" cellspacing="1">
	<tr>
	  <td  class="bgcolor_021">
	  <table width="100%" border="0" cellspacing="1" cellpadding="0">
		  <tr>
			<td bgcolor="#FFFFFF" class="fontstyle_006">&nbsp;<?php echo gettext("LIST TYPE")?>&nbsp;</td>
			<td bgcolor="#FFFFFF" class="fontstyle_006" align="right">
			    <form name="form1" method="post" action="">
				<select name="atmenub" id="col_configtype" onChange="window.document.form1.elements['PMChange'].value='Change';window.document.form1.submit();">
				<option value="WHITE"<?php if($atmenub == "WHITE") echo " selected"?>>WHITELIST&nbsp;</option>
				<option value="BLACK"<?php if($atmenub == "BLACK") echo " selected"?>>BLACKLIST&nbsp;</option>
				<option value="ALL"<?php if($atmenub == "ALL") echo " selected"?>>ALL&nbsp;</option>
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
    // My code for Creating two functionalities in a page
    $HD_Form -> create_toppage ("ask-add");
    if (isset($update_msg) && strlen($update_msg)>0) echo $update_msg;

    $count_cid = is_array($list) ? sizeof($list) : 0;
    if ($count_cid < $A2B->config["webcustomerui"]['limit_callerid']) {

?>
	   <table align="center"  border="0" width="55%" class="bgcolor_006">
		<form name="theForm" action="<?php  $_SERVER["PHP_SELF"]?>">
		<input name="popup_select" type=hidden value="<?php echo $popup_select?>">
		<input name="idcust" type=hidden value="<?php echo $idcust?>">
		<tr class="bgcolor_001" >

		<td align="center" valign="top">
				<?php //echo gettext("CALLER ID :");?>
				+<input class="form_input_text" name="add_callerid" size="15" maxlength="60">
			</td>
			<td align="center" valign="middle">
						<input class="form_input_button"  value="<?php echo gettext("ADD NEW CALLERID"); ?>"  type="submit">
		</td>
        </tr>
		</form>
      </table>
	<?php
	} else {
	?><table align="center"  border="0" width="70%" class="bgcolor_006">
		<tr class="bgcolor_001" >
			<td align="center" valign="middle">
				<b><i> <?php  echo gettext("You are not allowed to add more CallerID.");
				echo "<br/>";
				echo gettext("Remove one if you are willing to use an other CallerID.");?> </i> </b>
				<br/>
				<?php echo gettext("Max CallerId");?> &nbsp;:&nbsp; <?php echo $A2B->config["webcustomerui"]['limit_callerid'] ?>
			</td>
		</tr>
	  </table>
	<?php
	}
    // END END END My code for Creating two functionalities in a page
}
?>
</center>
<?php

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

$HD_Form -> create_form ($form_action, $list, $id=null) ;

// #### FOOTER SECTION
$smarty->display( 'footer.tpl');
