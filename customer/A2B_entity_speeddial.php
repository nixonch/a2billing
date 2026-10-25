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
include ("./form_data/FG_var_speeddial.inc");
include ("./lib/customer.smarty.php");

if (!has_rights(ACX_SPEED_DIAL)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

getpost_ifset(array ('destination',	'choose_speeddial',	'name'));

$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();
$HD_Form->FG_EDITION_LINK = "url:".$PHP_SELF."?form_action=ask-edit&popup_select=4&atmenu=$atmenu&current_page=$current_page&filterprefix=$filterprefix&order=$order&sens=$sens&id=";

// ADD SPEED DIAL
if (strlen($destination) > 0 && is_numeric($choose_speeddial)) {

	$FG_SPEEDDIAL_TABLE = "cc_speeddial";
	$FG_SPEEDDIAL_FIELDS = "speeddial";
	$instance_sub_table = new Table($FG_SPEEDDIAL_TABLE, $FG_SPEEDDIAL_FIELDS);
	
	$QUERY = "INSERT INTO cc_speeddial (id_cc_card, phone, name, speeddial) VALUES ('" . $_SESSION["card_id"] . "', '" . $destination . "', '" . $name . "', '" . $choose_speeddial . "')";
	
	$result = $instance_sub_table->SQLExec($HD_Form->DBHandle, $QUERY, 0);
	if ($result) {
		// Reload the list without replaying the assignment on refresh.
		$processed = $HD_Form->getProcessed();
		$list_params = array('form_action' => 'list');
		foreach (array('current_page', 'order', 'sens', 'filterprefix', 'atmenu', 'stitle', 'section', 'popup_select', 'popup_formname', 'popup_fieldname') as $param) {
			if (isset($processed[$param])) {
				$list_params[$param] = $processed[$param];
			}
		}
		Header("Location: " . $PHP_SELF . "?" . http_build_query($list_params));
		die();
	}
}

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if (!isset ($form_action))
	$form_action = "list";

if (!isset ($action))
	$action = $form_action;

$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

if ($popup_select == 4 && $form_action == 'edit' && $HD_Form->VALID_SQL_REG_EXP && $HD_Form->RESULT_QUERY) {
?>
<table width="95%" align="center">
<tr height="80px">
<td align="center"><b><?php echo gettext("Information has successfully been updated"); ?></b></td>
</tr>
</table>
<div align="center">
    <input id="speeddial-close" class="form_input_button" type="button" value="<?php echo htmlspecialchars(gettext("Close Window"), ENT_QUOTES, 'UTF-8'); ?>" onclick="window.parent.postMessage({action:'closeAlert'},'*');">
</div>
<script type="text/javascript">
(function () {
    window.parent.postMessage({action:'popupResult',payload:{type:'refreshInside'}},'*');
    var button = document.getElementById('speeddial-close');
    var label = <?php echo json_encode(gettext("Close Window"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var seconds = 10;
    button.focus();
    button.value = label + ' (' + seconds + ')';
    var timer = setInterval(function () {
        seconds--;
        button.value = label + ' (' + seconds + ')';
        if (seconds <= 0) {
            clearInterval(timer);
            button.click();
        }
    }, 1000);
}());
</script>
<?php
    $smarty->display('footer.tpl');
    exit;
}

// #### HELP SECTION
if ($form_action == 'list') {
	echo $CC_help_speeddial;
}

if ($form_action == "list") {
	// My code for Creating two functionalities in a page
	$HD_Form->create_toppage("ask-add");
	
 	if (isset($update_msg) && strlen($update_msg)>0) 
 		echo $update_msg; 	
?>
	  </center>	
	  <center><font class="error_message"><?php echo gettext("Enter the number which you wish to assign to the code here"); ?></font></center>
	  <center>
	   <table align="center" class="speeddial_table1">
		<form name="theForm" action="<?php  $_SERVER["PHP_SELF"]?>">
		<tr class="bgcolor_001">
		<td align="left" valign="bottom">
		<font class="fontstyle_002"> <?php echo gettext("Speed Dial code");?> : </font><select NAME="choose_speeddial" class="form_input_select">
					<?php					 
				  	 foreach ($speeddial_list as $recordset) { 						 
					?>
						<option class=input value='<?php echo $recordset[1]?>' ><?php echo $recordset[1]?> </option>                        
					<?php 	 
					 }
					?>
				</select>
		</td>
		<td align="left" valign="top">
				<font class="fontstyle_002"><?php echo gettext("Destination");?> :</font>
				<input class="form_input_text" name="destination" size="15" maxlength="60" >
				- <font class="fontstyle_002"><?php echo gettext("Name");?> :</font>
				<input class="form_input_text" name="name" size="15" maxlength="40" >
			</td>	
			<td align="center" valign="middle"> 
						<input class="form_input_button"  value="<?php echo gettext("ASSIGN NUMBER TO SPEEDDIAL");?>"  type="submit">
		</td>
        </tr>
	</form>
      </table>
	  </center>
	  <br>
	<?php
    // END END END My code for Creating two functionalities in a page
}

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

// The shared view only provides this refresh target for a nonempty list.
$empty_speeddial_list = $form_action == 'list' && empty($list);
if ($empty_speeddial_list) {
	echo '<div id="main-list">';
}
$HD_Form -> create_form ($form_action, $list, $id=null) ;
if ($empty_speeddial_list) {
	echo '</div>';
}

// #### FOOTER SECTION
$smarty->display('footer.tpl');


