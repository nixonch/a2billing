<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 *
 * @copyright   Copyright (C) 2004-2012 - Star2billing S.L.
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

getpost_ifset(array('id', 'uniqueid', 'customer_id', 'mailbox', 'password', 'context', 'stamp'));

//if (empty($uniqueid)) { $uniqueid = $id; }
$HD_Form = new FormHandler("cc_voicemail_users","voicemail");
//echo $id."-".$uniqueid;
$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "uniqueid";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

$HD_Form -> FG_TABLE_ID = 'uniqueid';

$HD_Form ->FG_LIST_ADDING_BUTTON1 = true;
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_voicemail.php?form_action=ask-add&section=".$_SESSION["menu_section"];
$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add voicemail user");
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/world_add.png" ;

$HD_Form -> AddViewElement(gettext("ID"), "uniqueid", "3%", "center", "sort");
$HD_Form -> AddViewElement(gettext("CUSTOMER"), "customer_id", "10%", "center\" nowrap \"", "sort", "", "lie_link", "cc_card", "lastname, id, firstname, IF(company_name='','',CONCAT(IF(LENGTH(CONCAT(lastname,firstname))=0,'','<br/>('),company_name,IF(LENGTH(CONCAT(lastname,firstname))=0,'',')')))", "id='%id'", "%1 %3 %4", "", "A2B_card_info.php");
$HD_Form -> AddViewElement(gettext("MAILBOX"), "mailbox", "10%", "center", "sort", "15");
$HD_Form -> AddViewElement(gettext("PASSWORD"), "password", "10%", "center", "sort", "15");
$HD_Form -> AddViewElement(gettext("CONTEXT"), "context", "10%", "center", "sort", "15");
$HD_Form -> AddViewElement(gettext("CALLERID"), "callerid", "10%", "center", "sort", "", "lie", "cc_sip_buddies", "callerid, id", "id='%id'", "%1", "", "");
$HD_Form -> AddViewElement(gettext("CREATION DATE"), "stamp", "10%", "center", "sort", "16", "", "", "", "", "", "display_dateformat");


$HD_Form -> FieldViewElement ('uniqueid, customer_id, mailbox, password, context, sip_buddy_id AS callerid, stamp');

$HD_Form -> FG_ACTION_SIZE_COLUMN = '15%';


$HD_Form -> CV_NO_FIELDS  = gettext("NO VOICEMAIL USERS HAVE BEEN CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;

$HD_Form -> FG_CAPCHA_POPUP = '|col2| | |col1|';
$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_ADDITION = true;

if ($form_action=="ask-add") {
	$acc = "value='Select ID CARD first'";
} else {
	$acc = "";
}

if ($form_action!='ask-edit' && $form_action!='edit') {
	$HD_Form -> AddEditElement (gettext("CUSTOMER ID"),
		"customer_id",
		'',
		"POPUPVALUE",
		"readonly size=50 maxlength=50",
		"4",
		gettext("Insert the Id CardNumber"),
		"", "", "", "", "",
		"A2B_entity_card.php?popup_select=1&",
		", 'CardNumberSelection','width=590,height=550,top=100,left=100,scrollbars=1'" ,
		gettext("Insert the card ID."));
}

$HD_Form -> AddEditElement (gettext("MAILBOX"),
		"mailbox",
		'$value',
		"INPUT",
		"readonly size=50 maxlength=11",
		"",
		gettext("Insert mailbox number"),
		"", "", "", "", "", "", "NO-NULL");

$HD_Form -> AddEditElement (gettext("PASSWORD"),
		"password",
		'$value',
		"INPUT",
		"size=50 maxlength=30",
		"",
		gettext("Insert password"),
		"", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("CONTEXT"),
		"accountcode",
		'',
		"INPUT",
		"size=50 $acc maxlength=40 readonly",
		"",
		gettext("Insert the accountcode"),
		"", "", "", "", "", "", "NO-NULL");

$HD_Form -> AddEditElement (gettext("EMAIL"),
		"email",
		'$value',
		"INPUT",
		"size=50 maxlength=70",
		"1",
		gettext("Insert email address"),
		"", "", "", "", "", "", "NO", "");

if ($form_action!='ask-edit' && $form_action!='edit') {
	$HD_Form -> FieldEditElement ('customer_id, mailbox, password, context, email');
} else {
	$HD_Form -> FieldEditElement ('mailbox, password, context, email');
	$HD_Form -> FG_EDITION_CLAUSE = " uniqueid='%id'";
}

// Set the filter variables
$HD_Form -> FG_FILTER_APPLY = true;
$HD_Form -> FG_FILTERFIELD = 'mailbox';
$HD_Form -> FG_FILTERFIELDNAME = 'mailbox';

if (isset($filterprefix)  &&  (strlen($filterprefix)>0)) {
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE .= "cc_voicemail_users.mailbox like '$filterprefix%'";
}

$HD_Form->FG_INSTANCE_NAME = 'voicemail user';
$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("You can modify, through the following form, the different properties of your ".$HD_Form->FG_INSTANCE_NAME);
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME."<br>".gettext("Fill the following fields and confirm by clicking on the button add.");


$HD_Form -> FG_INTRO_TEXT_ADITION = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted. <br>");


$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Click 'Confirm Data' to continue");


$HD_Form -> FG_ADDITIONAL_FUNCTION_AFTER_ADD = 'add_voicemail_mailbox';
//$HD_Form -> FG_ADDITIONAL_FUNCTION_AFTER_DELETE = 'did_destination_del';


$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_URL)."?id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_URL)."?id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = filter_input(INPUT_SERVER, 'PHP_SELF', FILTER_SANITIZE_URL)."?id=";
