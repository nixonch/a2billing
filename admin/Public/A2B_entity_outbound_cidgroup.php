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
include ("./form_data/FG_var_outbound_cidgroup.php");
include ("../lib/admin.smarty.php");

if (!has_rights(ACX_OUTBOUNDCID)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

$HD_Form->setDBHandler(DbConnect());

$HD_Form->init();

$HD_Form->FG_EDITION_LINK = "url:".$PHP_SELF."?form_action=ask-edit&popup_select=4&atmenu=$atmenu&current_page=$current_page&filterprefix=$filterprefix&order=$order&sens=$sens&id=";

if (!isset ($form_action))
	$form_action = "list"; //ask-add
if (!isset ($action))
	$action = $form_action;

if ($message == "editsuccess") {
?>
<table width="95%" align="center">
<tr height="80px">
<td align="center"><b><?php echo gettext("Information has successfully been updated")?></b></td>
</tr>
</table>
<form runat="server">
    <div>
        <input id="button1" onclick="window.parent.postMessage({action:'closeAlert'},'*')" class="form_input_button" type="button" value="" />
    </div>
</form>
<br>
<br>
</center>
<script type="text/javascript">
    window.parent.postMessage({action:'popupResult',payload:{type:'refreshInside'}},'*');
    objbutton=document.getElementById('button1');
    objbutton.focus();
    timeleft=11;
    function buttontimer(){
        timeleft--;
        if(timeleft==0) {
                objbutton.click();
        }
        objbutton.value = '<?= gettext("Close Window") ?> ('+timeleft+')';
    }
    buttontimer();
    setInterval(function() {buttontimer()}, 1000);
</script>
<?php
} else {

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($form_action == 'list') {
    if (!$popup_select) echo $CC_help_list_cidgroup;
} else if ($popup_select != 4) {
    echo $CC_help_edit_cidgroup;
}

// #### TOP SECTION PAGE
$HD_Form->create_toppage($form_action);


$HD_Form->create_form($form_action, $list, $id = null);

// #### FOOTER SECTION
if (!$popup_select) {
    $smarty->display('footer.tpl');
}
}
