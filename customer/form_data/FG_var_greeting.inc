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



getpost_ifset(array('IDCust', 'id_cc_card', 'lang_locale', 'voice_name', 'greet_text', 'greet_filename', 'id'));


$HD_Form = new FormHandler("cc_greeting_records", gettext("GREETING"));

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "updatetime";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "DESC";

//$actived_list = Constants::getActivationTrueFalseList();
//$yesno = Constants::getYesNoList();

$HD_Form ->FG_LIST_ADDING_BUTTON1 = true;
//$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_greeting.php?play=2&section=".$_SESSION["menu_section"];
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "javascript:;\" onClick=\"openURL('{$_SERVER['PHP_SELF']}?langlocale=','','','2&section={$_SESSION['menu_section']}');";
$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = gettext("Check/Update greetings list");
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/icon_refresh.gif" ;

$HD_Form ->FG_LIST_ADDING_BUTTON2 = true;
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK2 = "javascript:;\" onClick=\"MM_openBrWindow('CC_upload.php?popup_select=1','','scrollbars=no,resizable=yes,width=380,height=210,left=700,top=200,screenX=300,screenY=200')";
$HD_Form ->FG_LIST_ADDING_BUTTON_ALT2 = gettext("Upload my greeting");
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG2 = Images_Path ."/icon_upload.gif" ;

//$HD_Form -> AddViewElement(gettext("Language/Locale"), "lang_locale", "15%", "center", "sort");
$HD_Form -> AddViewElement(gettext("Voice"), "voice_name", "15%", "left\" nowrap \"", "sort");
$HD_Form -> AddViewElement(gettext("Greeting text"), "greet_text", "55%", "center", "sort");
$HD_Form -> AddViewElement(gettext("FileName"), "greet_filename", "15%", "left\" nowrap \"", "sort");

$HD_Form -> FieldViewElement ("CONCAT_WS(IF(LENGTH(gender),'/',''),voice_name,gender) voice_name, greet_text, greet_filename, id, no_browther_cache, lang_locale");

$HD_Form -> CV_TITLE_TEXT = gettext("Greetings list");
$HD_Form -> CV_NO_FIELDS  = gettext("There are no greetings created");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 15;
$HD_Form -> FG_VIEW_TABLE_WITDH = '80%';


if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
    $HD_Form -> FG_TABLE_CLAUSE.=" AND ";

$HD_Form -> FG_TABLE_CLAUSE.="id_cc_card =".$_SESSION["card_id"];

$HD_Form -> FG_ADDITION = false;
$HD_Form -> FG_EDITION = false;
$HD_Form -> FG_DELETION = true;

$HD_Form -> FG_FK_DELETE_ALLOWED = true;
$HD_Form -> FG_FK_DELETE_CONFIRM = true;
$HD_Form -> FG_FK_DELETE_BUTTON_IF_DEPENDENT = false;
$HD_Form -> FG_FK_WARNONLY = true;
$HD_Form -> FG_FK_TABLENAMES = array("cc_did_destination","cc_ivr_sounds","cc_ivr_destinations");
//$HD_Form -> FG_FK_EDITION_CLAUSE = array("INSTR(playsound, (SELECT SUBSTRING_INDEX(greet_filename, '.', 1) FROM cc_greeting_records WHERE id = '%id'))>0 AND '%id'");
$HD_Form -> FG_FK_EDITION_CLAUSE = array("SUBSTRING_INDEX(playsound, '.', 1) LIKE (SELECT SUBSTRING_INDEX(greet_filename, '.', 1) FROM cc_greeting_records WHERE id = '%id') AND '%id'",
					 "SUBSTRING_INDEX(playsound, '.', 1) LIKE (SELECT SUBSTRING_INDEX(greet_filename, '.', 1) FROM cc_greeting_records WHERE id = '%id') AND '%id'",
					 "SUBSTRING_INDEX(playsoundcallee, '.', 1) LIKE (SELECT SUBSTRING_INDEX(greet_filename, '.', 1) FROM cc_greeting_records WHERE id = '%id') AND '%id'");

$HD_Form -> FG_FK_DELETE_MESSAGE = gettext("There are some inbound DID destinations where this greeting message included.<br>Please edit the dependent destinations first.");

$HD_Form -> FG_OTHER_BUTTON1 = true;
$HD_Form -> FG_OTHER_BUTTON1_CONDITION = '"|col5|" != ""';
$HD_Form -> FG_OTHER_BUTTON2 = true;
$HD_Form -> FG_OTHER_BUTTON1_LINK= $_SERVER['PHP_SELF']."?form_action=list&id=|param|";
$HD_Form -> FG_OTHER_BUTTON2_LINK= "javascript:;";
$HD_Form -> FG_OTHER_BUTTON1_IMG = Images_Path . "/edit.png";
$HD_Form -> FG_OTHER_BUTTON2_IMG = Images_Path . "/flv.gif\" height=\"16\" onClick=\"GreetPlay(this,'".$_SERVER['PHP_SELF']."?download=file&file=|col2|&nbc=|col4|')";

//$HD_Form -> FG_OTHER_BUTTON1_LINK= "javascript:;\" onClick=\"document.getElementById('audio-155-1').play()";
//$HD_Form -> FG_OTHER_BUTTON1_IMG = Images_Path . '/flv.gif" height="16" onClick="GreetPlay(this)';

$HD_Form -> AddEditElement (gettext("FILENAME"),
                                        "greet_filename",
                                        "",
                                        "INPUT",
                                        "size=85 readonly",
                                        "",
                                        "",
                                        "", "", "", "", "", "", "", gettext("Use for information only"));

$HD_Form -> FieldEditElement ('greet_filename');

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("You can modify, through the following form, the different properties of your")." ".$HD_Form->FG_INSTANCE_NAME;
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want to remove this ").gettext($HD_Form->FG_INSTANCE_NAME).", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

$HD_Form -> FG_INTRO_TEXT_ADITION = gettext("You can upload, generate and edit greeting messages");
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted.")."<br>";

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path."/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Made your change to edit this fax extension.");

$HD_Form->FG_ADDITIONAL_FUNCTION_AFTER_DELETE = 'processing_greeting_erase';

$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF'];
