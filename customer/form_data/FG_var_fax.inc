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



getpost_ifset(array('IDCust', 'id_cc_card', 'ext_num', 'localstationid', 'email', 'notify_email', 'id', 'extlen'));


$HD_Form = new FormHandler("cc_fax", gettext("FAX"));

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "ext_num";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

//$actived_list = Constants::getActivationTrueFalseList();
$yesno = Constants::getYesNoList();

$HD_Form -> AddViewElement(gettext("FAX EXTENSION"), "ext_num", "30%", "center", "sort");
$HD_Form -> AddViewElement(gettext("STORE ON SERVER"), "store", "24%", "center", "sort", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("FORWARD TO EMAIL"), "email", "21%", "center", "sort");
$HD_Form -> AddViewElement(gettext("PERMITTED FORWARDING"), "notify_email", "25%", "center", "sort", "", "list", $yesno);

$HD_Form -> FieldViewElement ('ext_num, store, email, notify_email');

$HD_Form -> CV_TITLE_TEXT = gettext("FAX extensions list");
$HD_Form -> CV_NO_FIELDS  = gettext("There are no FAX IDs created");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 10;
$HD_Form -> FG_VIEW_TABLE_WITDH = '70%';


// My Code for Where Cluase
if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
    $HD_Form -> FG_TABLE_CLAUSE.=" AND ";

$HD_Form -> FG_TABLE_CLAUSE.="id_cc_card =".$_SESSION["card_id"];

if (strlen($HD_Form -> FG_EDITION_CLAUSE)>0)
    $HD_Form -> FG_EDITION_CLAUSE.=" AND ";

$HD_Form -> FG_EDITION_CLAUSE.="id_cc_card =".$_SESSION["card_id"];

$HD_Form -> FG_CAPCHA_POPUP = '|col0|';
$HD_Form -> FG_ADDITION = true;
$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_OTHER_BUTTON1 = false;


$HD_Form -> AddEditElement (gettext("FAX EXTENSION"),
			   "ext_num",
			   $exten_include,
			   "INPUT",
			   "size=20 maxlength=$extlen",
			   regex_range($extmin,$extmax,$exten_include),
			   gettext("Enter right number from range below exclude afore existing numbers"),
			   "", "", "", "", "", "", "", gettext("Extension number from ").$extmin.gettext(" to ").$extmax);

$HD_Form -> AddEditElement (gettext("LOCAL STATION ID"),
			   "localstationid",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=20",
			   "",
			   gettext("Insert number to identify itself to the remote end"),
			   "", "", "", "", "", "", "", gettext("Insert number to identify itself to the remote end"));
/**
$HD_Form -> AddEditElement (gettext("LOCAL HEADER INFO"),
			   "localheaderinfo",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=50",
			   "",
			   gettext("Insert string to generate a header line on each page"),
			   "", "", "", "", "", "", "", gettext("Insert string to generate a header line on each page"));
**/
$HD_Form -> AddEditElement (gettext("STORE ON SERVER"),
			   "store",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   gettext("Choose 'Yes' if you want keep copy on server"),
			   "", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "", gettext("Choose 'Yes' if you want keep copy on server"));

$HD_Form -> AddEditElement (gettext("PERMITTED FORWARDING"),
			   "notify_email",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   gettext("Choose 'Yes' if you want to receive faxes to email"),
			   "", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "", gettext("Choose 'Yes' if you want to receive faxes to email"));

$HD_Form -> AddEditElement (gettext("THE EMAIL FOR FORWARDING"),
			   "email",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=70",
			   "1",
			   gettext("Insert the email to forwarding of faxes"),
			   "", "", "", "", "", "", "NO", "");

$HD_Form -> FieldEditElement ('ext_num, localstationid, store, notify_email, email');

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("You can modify, through the following form, the different properties of your")." ".$HD_Form->FG_INSTANCE_NAME;
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

//$HD_Form -> FG_OTHER_BUTTON1_LINK="A2B_entity_card?form_action=ask-edit&stitle=Customers_Card&id=";
//$HD_Form -> FG_OTHER_BUTTON1_IMG = Images_Path."/icon-card.png";

$HD_Form -> FG_INTRO_TEXT_ADITION = gettext("You can add new virtual fax extension below to allow you to receive faxes.");
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted.")."<br>";

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path."/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Made your change to edit this fax extension.");


$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
