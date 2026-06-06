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


getpost_ifset(array('IDCust', 'id', 'cid', 'activated', 'verify', 'callback', 'warning_threshold', 'say_dialednumber', 'say_rateinitial', 'say_timetotalk', 'say_balance_after_call', 'cli_replace', 'cli_localreplace', 'cli_otherreplace'));

$HD_Form = new FormHandler("cc_callerid", gettext("CallerID"));

$HD_Form -> FG_DEBUG = 0;

$HD_Form ->FG_LIST_ADDING_BUTTON1 = true;
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_callerid.php?form_action=ask-add&section=".$_SESSION["menu_section"];
$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add CallerID");
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/page_white_add.png" ;

$HD_Form -> FG_OTHER_BUTTON3 = true;
$HD_Form -> FG_OTHER_BUTTON3_LINK = "A2B_entity_logrefill.php?form_action=ask-add&card_id=|col_orig1|";
$HD_Form -> FG_OTHER_BUTTON3_IMG = Images_Path . "/brick_add.png";
$HD_Form -> FG_OTHER_BUTTON3_ALT = gettext('ADD REFILL');


$actived_list = Constants::getActivationTrueFalseList();
$yesno = Constants::getYesNoList();

$HD_Form -> AddViewElement(gettext("CALLERID"), "cid", "15%", "center", "sort");
$HD_Form -> AddViewElement(gettext("VERIFY"), "verify", "5%", "center", "SORT");
$HD_Form -> AddViewElement(gettext("PHONE NUMBER"), "phonenumber", "15%", "center", "sort", "26");
$HD_Form -> AddViewElement(gettext("CALLBACK"), "callback", "10%", "center", "SORT", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("ACCOUNT NUMBER"), "id_cc_card", "12%", "center", "sort", "", "lie_link", "cc_card", "username, id", "id='%id'", "%1", "", "A2B_entity_card.php");
$HD_Form -> AddViewElement(gettext("CUSTOMER NAME"), "id_cc_card", "20%", "center", "sort", "", "lie_link", "cc_card", "lastname, id, firstname", "id='%id'", "%1 %3", "", "A2B_entity_card.php");
$HD_Form -> AddViewElement(gettext("D I S A"), "activated", "10%", "center", "sort", "", "list", $actived_list);

$HD_Form -> FieldViewElement ("cid, IF(verify=0,'".gettext("No")."','<font color=green>".gettext("Yes")."</font>'), phonenumber, callback, id_cc_card, id_cc_card, cc_callerid.activated");


$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO ").strtoupper($HD_Form->FG_INSTANCE_NAME).gettext(" CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 10;
$HD_Form -> FG_VIEW_TABLE_WITDH = '75%';

$HD_Form -> FG_ADDITION = true;
$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_CAPCHA_POPUP = '|col0| | |col5|';

if ($form_action=="ask-add") {
	$warning_threshold	='-2';
	$say_rateinitial	= $say_balance_after_call = $verify = $say_dialednumber = $say_timetotalk = $cli_replace = $cli_localreplace = $cli_otherreplace = $blacklist = '0';
}
// Possible value to notify the user
$instance_config_table = new Table("cc_config", "id, config_value");
$QUERY = " config_key = 'values_notifications' ";
$return = null;
$return = $instance_config_table -> Get_list($DBHandle, $QUERY, 0);
$values = explode(":",$return[0]["config_value"]);
$limits_notify = array();
$idx =0;
$limits_notify [$idx] = array(gettext("NEVER").'&nbsp;', -2);
foreach ($values as $val) {
	$idx++;
	$val_display = round($val,2);
	$limits_notify [$idx] = array($val_display,$val);
}

$HD_Form -> AddEditElement (gettext("CALLERID"),
			"cid",
			'$value',
			"INPUT",
			"size=20 maxlength=30",
			"",
			gettext("Insert the CallerID"),
			"", "", "", "", "", "", "", "");

$HD_Form -> AddEditElement (gettext("D I S A"),
			"activated",
			't',
			"RADIOBUTTON",
			"",
			"",
			gettext("Choose if you want to activate this CallerID"),
			"", "", "", gettext("Yes").":t, - ".gettext("No").":f", "", "", "",
			gettext("Direct Inward System Access... for incoming calls from this number"));

$HD_Form -> AddEditElement (gettext("BLACKLIST"),
			"blacklist",
			$blacklist,
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "",
			gettext("Choose 'Yes' if you want to place this CallerID to Black List. This will break any autorization attempts from this CallerID."));

$HD_Form -> AddEditElement (gettext("VERIFY"),
			"verify",
			$verify,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $yesno, "%1", "",
			gettext("Choose 'Yes' if this CallerID verified"));

$HD_Form -> AddEditElement (gettext("ID CARD"),
			"id_cc_card",
			"$value",
			"POPUPVALUE",
			"size=30 maxlength=50",
			"4",
			gettext("Insert the Id CardNumber"),
			"", "", "", "", "", "A2B_entity_card.php?popup_select=1&",
			", 'CardNumberSelection','width=550,height=580,top=20,left=100,scrollbars=1'",
			gettext("Define the card number ID to use."));

$HD_Form -> AddEditElement (gettext("CALLBACK"),
			"callback",
			$callback,
			"SELECT",
			"",
			"",
			gettext("Choose if you want to callback to this CallerID"),
			"list", "", "", "", $yesno, "%1", "",
			gettext("Choose 'Yes' if you want to callback to this CallerID"));

$destlist = array();
if (isset($form_action) && $form_action=="ask-edit") {
	$QUERYpop = "SELECT DISTINCT calledstation
		     FROM cc_call
		     WHERE starttime > NOW() - INTERVAL 1 YEAR
			AND card_caller = (SELECT id_cc_card FROM cc_callerid WHERE id=$id)
			AND src LIKE (SELECT cid FROM cc_callerid WHERE id=$id LIMIT 1)
			AND sessiontime > 90
			AND CHAR_LENGTH(calledstation) > 6
		     ORDER BY id DESC";
	$resmax = $DBHandle->Execute($QUERYpop);
	if ($resmax) {
	    foreach ($resmax as $val)
	    $destlist[] = array($val[0],$val[0]);
	}
	$QUERYpop = "SELECT ivrname FROM cc_ivr WHERE id_cc_card=(SELECT id_cc_card FROM cc_callerid WHERE id=$id) ORDER BY ivrname";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array($val[0],$val[0]);
        }
	$QUERYpop = "SELECT `name` FROM cc_queues WHERE id_cc_card=(SELECT id_cc_card FROM cc_callerid WHERE id=$id) ORDER BY `name`";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array("QUEUE ".$val[0],"QUEUE ".$val[0]);
        }
	$QUERYpop = "SELECT regexten FROM cc_sip_buddies WHERE id_cc_card=(SELECT id_cc_card FROM cc_callerid WHERE id=$id) AND external=0 AND regexten>0 ORDER BY regexten";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array($val[0],$val[0]);
        }
/*
        $QUERYpop = "SELECT cid FROM cc_callerid WHERE id_cc_card=(SELECT id_cc_card FROM cc_callerid WHERE id=$id) AND blacklist=0 ORDER BY cid";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array($val[0],$val[0]);
        }
*/
        $instance_table = new Table("cc_callerid", "phonenumber");
        $list = $instance_table -> Get_list($DBHandle, "id = $id", 0);
        if (is_array($list) && count($list)>0) {
            $destination = $list[0][0];
            $selecteddest = ($destination && array_search($destination,array_column($destlist,0))===false) ? "<OPTION value=\"$destination\">".$destination."</OPTION>" : NULL;
        } else $selecteddest = NULL;
} else $selecteddest = NULL;
$HD_Form -> AddEditElement (gettext("DESTINATION TO FORWARD"),
			"phonenumber",
			'$value',
			($form_action=='ask-delete')?"INPUT":"DATALIST",
			"size=50 maxlength=100 style=\"width: 250px;\" autocomplete=\"off\"",
			"",
			gettext("Insert the phone"),
			"list", "", "", "", $destlist, "%1", "", gettext("Enter the destination for immediatly dial instead prompt to dial. There are the phonenumber, Queue or IVR name you wish to send call. Or select destination from the drop-down list.<br>Requires \"D I S A\" on and \"BLACKLIST\" off. After one attempt to redirect the call, the call will be dropped."), NULL, $selecteddest);

$HD_Form -> AddEditElement (gettext("SAY BALANCE AFTER AUTH"),
			"warning_threshold",
			$warning_threshold,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $limits_notify , "%1", "", gettext('currency : ').strtoupper(BASE_CURRENCY)." (".gettext("with the warning threshold").")", "", '<OPTION  value="-1" selected>'.gettext("ALWAYS").'&nbsp;</OPTION>');

$HD_Form -> AddEditElement (gettext("SAY THE DIALED NUMBER"),
                        "say_dialednumber",
                        $say_dialednumber,
                        "SELECT",
                        "",
                        "",
                        "",
                        "list", "", "", "", $yesno, "%1");

$HD_Form -> AddEditElement (gettext("SAY RATE BEFORE CALL"),
			"say_rateinitial",
			$say_rateinitial,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $yesno, "%1");

$HD_Form -> AddEditElement (gettext("SAY HIGHEST POSSIBLE DURATION"),
                        "say_timetotalk",
			$say_timetotalk,
                        "SELECT",
                        "",
                        "",
                        "",
                        "list", "", "", "", $yesno, "%1");

$HD_Form -> AddEditElement (gettext("SAY BALANCE AFTER CALL"),
			"say_balance_after_call",
			$say_balance_after_call,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $yesno, "%1");

$HD_Form -> AddEditElement (gettext("CONSPIRATION"),
			"cli_replace",
			$cli_replace,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $yesno, "%1", "",
			gettext("Choose 'Yes' if you want hide CLI and replace its in a general manner for calls from this CallerID. Choose 'No' if you want set a veto for replacement CLI for calls from this CallerID. Choose 'Local' to replace this CallerID when calling to countries for which there exist a local number(s) for replacement."),
			"", '<OPTION  value="2">'.gettext("Local").'&nbsp;</OPTION>',
			gettext("If this CallerID number was verified you can use the remaining options..."));

$HD_Form -> AddEditElement (gettext("LOCAL REPLACE"),
			"cli_localreplace",
			$cli_localreplace,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $yesno, "%1", "",
			gettext("Present this CallerID, when calls are made to a country relevant to this CallerID."),
			null, null, gettext("Settings for general Caller Line Identity (CLI)"));

$HD_Form -> AddEditElement (gettext("OTHER REPLACE"),
			"cli_otherreplace",
			$cli_otherreplace,
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $yesno, "%1", "",
			gettext("Present this CallerID, when calls are made to countries not relevant to this CallerID."));

$HD_Form -> AddEditElement (gettext("REPLACE BY PREFIX"),
			"cli_prefixreplace",
			"$value",
			"INPUT",
			"size=30 maxlength=30 ",
			"28",
			"",
			"", "", "", "", "", "", "NO", gettext("Present this CallerID, when calls are made to countries from listed prefixes. Insert the replaceable prefixes separated by comma, e.g. for UK,IT,RU,UA are '44,39,7,380'."));

$HD_Form -> FieldEditElement ('cid, activated, blacklist, verify, id_cc_card, callback, phonenumber, warning_threshold, say_dialednumber, say_rateinitial, say_timetotalk, say_balance_after_call, cli_replace, cli_localreplace, cli_otherreplace, cli_prefixreplace');

// Set the filter variables
$HD_Form -> FG_FILTER_APPLY = true;
$HD_Form -> FG_FILTERFIELD = 'callerid';
$HD_Form -> FG_FILTERFIELDNAME = gettext('CALLERID');

if (isset($filterprefix)  &&  (strlen($filterprefix)>0)){
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="cid like '$filterprefix%'";
}

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("Modify via the form below, the properties of the callerID");
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME."<br>".gettext("Fill the following fields and confirm by clicking on the button add.");


$HD_Form -> FG_OTHER_BUTTON2 = true;
$HD_Form -> FG_OTHER_BUTTON2_LINK="A2B_entity_payment.php?stitle=Payment_add&form_action=ask-add&card_id=|col_orig1|";
$HD_Form -> FG_OTHER_BUTTON2_IMG = Images_Path . "/money.png";
$HD_Form -> FG_OTHER_BUTTON2_ALT = gettext('ADD PAYMENT');



$HD_Form -> FG_INTRO_TEXT_ADITION = gettext("Add a ".$HD_Form->FG_INSTANCE_NAME." now.");
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted.")."<br>";


$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Click Confirm Data to apply your changes");



$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
