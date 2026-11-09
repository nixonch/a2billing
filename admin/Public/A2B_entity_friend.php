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
include ("./form_data/FG_var_friend.inc");
include ("../lib/admin.smarty.php");

if (! has_rights (ACX_CUSTOMER)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");	   
	die();
}

$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();

if ($atmenu == 'sip' && (!isset($form_action) || $form_action == 'list')) {
	// The ID and delete title keep their positions; the final value identifies internal extensions.
	$extension_column = $HD_Form->FG_NB_TABLE_COL + 2;
	$HD_Form->FG_EDITION_CONDITION = '|col'.$extension_column.'| != 1';
	$HD_Form->FG_OTHER_BUTTON1 = true;
	$HD_Form->FG_OTHER_BUTTON1_CONDITION = '|col'.$extension_column.'| == 1';
	$HD_Form->FG_OTHER_BUTTON1_IMG = Images_Path_Main.'/'.$HD_Form->FG_EDITION_IMG;
	$HD_Form->FG_OTHER_BUTTON1_ALT = $HD_Form->FG_EDIT_ALT;
	$edit_context = $HD_Form->getProcessed();
	$extension_params = array('form_action' => 'ask-edit', 'popup_select' => 4, 'atmenu' => 'sip', 'section' => $_SESSION['menu_section']);
	foreach (array('current_page', 'filterprefix', 'filterprefix2', 'order', 'sens', 'friend_id') as $name) {
		if (isset($edit_context[$name]) && is_scalar($edit_context[$name])) $extension_params[$name] = $edit_context[$name];
	}
	$extension_url = 'url:'.$PHP_SELF.'?'.http_build_query($extension_params).'&id=';
	$extension_onclick = 'alertBase('.json_encode($extension_url).' + \'|param|\', '.json_encode(gettext('Edit Extension')).",true,false,'#445760',event.pageX-55,event.pageY-100,'UR',null,null,null,null,null,refreshInsideCallback); return false;";
	$HD_Form->FG_OTHER_BUTTON1_LINK = 'javascript:;" onclick="'.htmlspecialchars($extension_onclick, ENT_QUOTES, 'UTF-8');
}

/********************************* ADD SIP EXTENSIONS ***********************************/
if ($atmenu == 'sip' && ($form_action == 'ask-add-extension' || $form_action == 'addexten')) {
	getpost_ifset(array('card_id', 'startnumber', 'quantity'));
	$DBHandle = $HD_Form->DBHandle;
	$defaults = get_sip_extension_defaults($card_id, $DBHandle);
	if ($defaults === false) {
		Header("HTTP/1.0 401 Unauthorized");
		Header("Location: PP_error.php?c=accessdenied");
		die();
	}
	$extmin = $defaults['extmin'];
	$extmax = $defaults['extmax'];
	$extquantity = $defaults['extquantity'];
	if ($form_action == 'addexten') {
		if ( !is_numeric($quantity) || !is_numeric($startnumber) || $quantity == '0' || $startnumber == '0' || $quantity > $extquantity || $startnumber < $extmin || $extmax < $startnumber + $quantity -1 ) {
			$form_action = 'ask-add-extension';
		} else {
			$extcreated = gen_friends($card_id, $startnumber, $quantity, $defaults['extmin'], $defaults['extmax'], $DBHandle, $A2B);
			if (count($extcreated) > 0) {
				$log = new Logger();
				$log->insertLog_Update($_SESSION['admin_id'], 1, "SIP EXTENSION" . (($quantity > 1) ? "S" : "") . " ADDED", implode(',', $extcreated), 'cc_sip_buddies', $_SERVER['REMOTE_ADDR'], 'A2B_entity_friend.php', '', 0);
				unset($log);
				// The SIP list remembers its effective page size, including the ALL option.
				$session_limit = $HD_Form->FG_TABLE_NAME . '-displaylimit';
				$page_size = $HD_Form->FG_LIMITE_DISPLAY;
				if (isset($_SESSION[$session_limit]) && is_numeric($_SESSION[$session_limit])) {
					$page_size = $_SESSION[$session_limit];
				}
				$sip_table = new Table('cc_sip_buddies', 'id');
				$sip_count = $sip_table->Get_list_count($DBHandle);
				// Start with a clean list URL so previous filters cannot hide the new rows.
				$list_parameters = array(
					'form_action' => 'list',
					'current_page' => max(0, ceil($sip_count / $page_size) - 1),
					'atmenu' => 'sip', 'section' => $_SESSION['menu_section'],
					'order' => 'id', 'sens' => 'ASC'
				);
				$HD_Form->finish_popup_add(true, htmlspecialchars(gettext('Created'), ENT_QUOTES, 'UTF-8') . ': ' . count($extcreated),
					array('link' => $_SERVER['PHP_SELF'], 'listParameters' => $list_parameters));
			}
		}
	}
	$extension_url = $_SERVER['PHP_SELF'] . '?' . http_build_query(array(
		'form_action' => 'addexten', 'atmenu' => 'sip', 'section' => $_SESSION['menu_section'],
		'popup_select' => 4, 'card_id' => $card_id
	)) . '&startnumber=';
	$smarty->display('main.tpl');
?>
<script src="./javascript/sipiax.js"></script>
<br>
<form name="theForm" style="padding: 0 15px;">
	<table class="bar-status" width="100%" border="0" align="center" cellpadding="4" cellspacing="1">
		<tr>
			<td nowrap="nowrap">
				<strong><?php echo gettext("Create extensions from"); ?>:</strong><br/>
				<sup>[<?php echo gettext("from") . ' ' . $extmin . ' ' . gettext("to") . ' ' . $extmax; ?>]</sup>
			</td>
			<td><input class="form_input_text" name="startnumber" oninput="allowOnlyDigits(this);" size="10" maxlength="<?php echo $defaults['extlen']; ?>" value="<?php echo $defaults['extstart']; ?>"></td>
		</tr>
		<tr>
			<td><strong><?php echo gettext("Quantity"); ?>:</strong></td>
			<td>
				<input class="form_input_text" name="quantity" oninput="allowOnlyDigits(this);" size="10" maxlength="3" value="<?php echo $defaults['quantity']; ?>"><br/>
				<sup><em><?php echo gettext("Extensions left") . ': ' . $extquantity; ?></em></sup>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="right">
				<input class="form_input_button" type="button" value="<?php echo gettext("Create"); ?>" onclick="openURL('<?php echo htmlspecialchars($extension_url, ENT_QUOTES, 'UTF-8'); ?>',<?php echo $extquantity; ?>,'Вы превысили лимит на <?php echo $extquantity; ?> номеров.\nМаксимальное количество <?php echo $defaults['extlimit']; ?>.\nУ Вас уже создано <?php echo $defaults['extlimit'] - $extquantity; ?> номеров.\nУменьшите количество новых номеров.','<?php echo gettext("Both fields must be numeric\\nand greater than zero"); ?>',<?php echo $extmin; ?>,<?php echo $extmax; ?>)">
			</td>
		</tr>
	</table>
</form>
<?php
	$smarty->display('footer.tpl');
	exit;
}

$HD_Form -> FG_EDITION_LINK	= $_SERVER['PHP_SELF']."?form_action=ask-edit&atmenu=$atmenu&current_page=$current_page&filterprefix=$filterprefix&filterprefix2=$filterprefix2&id=";
$HD_Form -> FG_DELETION_LINK	= "url:".$_SERVER['PHP_SELF']."?popup_select=4&form_action=ask-delete&atmenu=$atmenu&current_page=$current_page&filterprefix=$filterprefix&filterprefix2=$filterprefix2&id=";

/********************************* ADD SIP / IAX FRIEND ***********************************/
getpost_ifset(array("id_cc_card", "cardnumber", "useralias"));


if ((isset($id_cc_card) && is_numeric($id_cc_card)) && ($form_action == "add_sip" || $form_action == "add_iax")) {
	
	$HD_Form -> FG_GO_LINK_AFTER_ACTION = "A2B_entity_card.php?atmenu=card&stitle=Customers_Card&id=";

	if ($form_action == "add_sip") { 
		$friend_param_update=" sip_buddy='1' ";
		if(!USE_REALTIME){
			$key = "sip_changed";
		}
	} else {
		$friend_param_update=" iax_buddy='1' ";
		if(!USE_REALTIME) {
			$key = "iax_changed";
		}
	}
	
	if(!USE_REALTIME) {
		$who= Notification::$ADMIN;$who_id=$_SESSION['admin_id']; 
		NotificationsDAO::AddNotification($key,Notification::$HIGH,$who,$who_id);
	}
	
	$instance_table_friend = new Table('cc_card');
	$instance_table_friend -> Update_table ($HD_Form -> DBHandle, $friend_param_update, "id='$id_cc_card'", $func_table = null);
	
	
	if ( $form_action == "add_sip" )	$TABLE_BUDDY = 'cc_sip_buddies';
	else 	$TABLE_BUDDY = 'cc_iax_buddies';
	
	$instance_table_friend = new Table($TABLE_BUDDY,'*');	
	$list_friend = $instance_table_friend -> Get_list ($HD_Form -> DBHandle, "id_cc_card='$id_cc_card'", null, null, null, null);
	
	if (is_array($list_friend) && count($list_friend)>0){ Header ("Location: ".$HD_Form->FG_GO_LINK_AFTER_ACTION); exit();}

	$form_action = "add";
	
	$_POST['accountcode'] = $_POST['username']= $_POST['name']= $_POST['cardnumber'] = $cardnumber;
	$_POST['allow'] = FRIEND_ALLOW;
	$_POST['context'] = FRIEND_CONTEXT;
	$_POST['nat'] = FRIEND_NAT;
	$_POST['amaflags'] = FRIEND_AMAFLAGS;
	$_POST['regexten'] = $cardnumber;
	$_POST['id_cc_card'] = $id_cc_card;
	$_POST['callerid'] = $useralias;
	$_POST['qualify'] = FRIEND_QUALIFY;
	$_POST['host'] = FRIEND_HOST;   
	$_POST['dtmfmode'] = FRIEND_DTMFMODE;
	$_POST['secret'] = MDP_NUMERIC(10);
	
	// for the getProcessed var
	$HD_Form->_vars = array_merge((array)$_GET, (array)$_POST);
}

if ($id!="" || !is_null($id)) {
	$HD_Form -> FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form -> FG_EDITION_CLAUSE);
}


if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

if(!USE_REALTIME) {
	// CHECK THE ACTION AND SET THE IS_SIP_IAX_CHANGE IF WE ADD/EDIT/REMOVE A RECORD
	if ( $form_action == "add" || $form_action == "edit" || $form_action == "delete" ){
		if ($atmenu=='sip') {
			$key = "sip_changed";
	  	} else {
	  		$key = "iax_changed";
	  	}
		if ($_SESSION["user_type"]=="ADMIN") {
			$who = Notification::$ADMIN;
			$id = $_SESSION['admin_id'];
		} else {
			$who = Notification::$UNKNOWN;
			$id = -1;
		}
		NotificationsDAO::AddNotification($key,Notification::$HIGH,$who,$id);
	}
}

$list = $HD_Form -> perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

if ($atmenu == 'sip' && $form_action == 'list') {
?>
<script type="text/javascript">
function addSipExtension(event) {
	var popupY = event.pageY - 100;
	alertBase('url:A2B_entity_card?popup_select=1', <?php echo json_encode(gettext("Card Number Selection")); ?>,
		true, false, '#445760', null, popupY, 'UL', null, null, null, null, null, function (payload) {
			if (payload.type !== 'sendValue') return false;
			var url = <?php echo json_encode($_SERVER['PHP_SELF'] . '?' . http_build_query(array(
				'form_action' => 'ask-add-extension', 'atmenu' => 'sip',
				'section' => $_SESSION['menu_section'], 'popup_select' => 4
			))); ?> + '&card_id=' + encodeURIComponent(payload.selvalue);
			// alertBase removes the previous window after its 150 ms closing animation.
			setTimeout(function () {
				alertBase('url:' + url, <?php echo json_encode(gettext("Add Extension")); ?>,
					true, false, '#445760', null, popupY, 'UL', null, null, null, null, null, refreshInsideCallback);
			}, 200);
			return true;
		});
}
</script>
<?php
}

// #### HELP SECTION
if ($form_action=='list') {

	echo $CC_help_sipfriend_list;

	if(!USE_REALTIME) {
	?>
		  <table width="<?php echo $HD_Form -> FG_HTML_TABLE_WIDTH?>" border="0" align="center" cellpadding="0" cellspacing="0" >
			<TR><TD  align="center"> <?php echo gettext("Link to Generate on SIP/IAX Friends")?> &nbsp;:&nbsp;
			</TD></TR>
			<TR><TD  align="center">
			<b><?php echo gettext("Realtime not active, you have to use the conf file for your system"); ?></b>
			</TD></TR>
			<TR><FORM NAME="sipfriend">
				<td height="31" style="padding-left: 5px; padding-right: 3px;" align="center" >
				<b>
				SIP : <input class="form_input_button"  TYPE="button" VALUE=" <?php echo gettext("GENERATE ADDITIONAL_A2BILLING_SIP.CONF"); ?> "
				onClick="self.location.href='./CC_generate_friend_file.php?atmenu=sipfriend';">
				IAX : <input class="form_input_button"  TYPE="button" VALUE=" <?php echo gettext("GENERATE ADDITIONAL_A2BILLING_IAX.CONF"); ?> "
				onClick="self.location.href='./CC_generate_friend_file.php?atmenu=iaxfriend';">
				</b></td></FORM>
			</TR>
		   </table>
		   <br/>
	<?php  
	} else { ?>
		<center><a href="<?php  echo "CC_generate_friend_file.php?action=reload";?>"><img src="<?php echo Images_Path;?>/icon_refresh.gif"/>
			<?php echo gettext("Reload Asterisk"); ?></a>
		</center>
	<?php
	}
?>
<div align="center">
<table width="40%" border="0" align="center" cellpadding="0" cellspacing="1">
	<tr>
	  <td  class="bgcolor_021">
	  <table width="100%" border="0" cellspacing="1" cellpadding="0">
	  	<form name="form1" method="post" action="">
		  <tr>
			<td bgcolor="#FFFFFF" class="fontstyle_006" width="100%">&nbsp;<?php echo gettext("CONFIGURATION TYPE")?> </td>
			<td bgcolor="#FFFFFF" class="fontstyle_006" align="center">
			   <select name="atmenu" id="col_configtype" onChange="window.document.form1.elements['PMChange'].value='Change';window.document.form1.submit();">
				 <option value="iax" <?php if($atmenu == "iax")echo "selected"?>><?php echo gettext("IAX")?></option>
				 <option value="sip" <?php if($atmenu == "sip")echo "selected"?>><?php echo gettext("SIP")?></option>
			   </select>
			  <input name="PMChange" type="hidden" id="PMChange">

			</td>
		  </tr>
		  </form>
	  </table></td>
	</tr>
</table>
</div>

<br/>
<?php
} else if ($popup_select < 2 || in_array($form_action, array('ask-edit', 'edit'))) {
	echo $CC_help_sipfriend_edit;
}

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);


$HD_Form -> create_form ($form_action, $list, $id=null) ;


// #### FOOTER SECTION
$smarty->display('footer.tpl');
