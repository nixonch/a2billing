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



getpost_ifset(array('id'));

$atmenu = ($atmenu=='IAX')?$atmenu:'SIP';
$HD_Form = new FormHandler("cc_".strtolower($atmenu)."_buddies", gettext($atmenu . " Config"));

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "did";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

//$HD_Form -> FG__DELETION_LINK = $_SERVER['PHP_SELF']."?form_action=ask-delete&atmenu=$atmenu&regexten=|$FN_COLUMN_REGEXTEN|&id=";

$values_list['nat'] = array(array(gettext('yes'),'yes'),array(gettext('no'),'no'),array(null,null));

$HD_Form -> AddViewElement(gettext("DID"), "did", "15%", "center", "sort");
$HD_Form -> AddViewElement(gettext("REGNAME"), "name", "10%", "center", "sort");
$HD_Form -> AddViewElement(gettext("LANGUAGE"), "language", "5%", "center", "sort");
$HD_Form -> AddViewElement(gettext("FROMUSER"), "fromuser", "15%", "center", "sort");
$HD_Form -> AddViewElement(gettext("HOST"), "host", "15%", "center", "sort");
/**
if ($atmenu!='IAX') {
    $HD_Form -> AddViewElement(gettext("CALLGROUP"), "callgroup", "5%", "center", "sort");
    $HD_Form -> AddViewElement(gettext("PICKUPGROUP"), "pickupgroup", "5%", "center", "sort");
}
**/
//$HD_Form -> AddViewElement($atmenu . " URI", "server", "15%", "center", "sort");
$HD_Form -> AddViewElement(gettext("DEFAULTUSER"), "defaultuser", "15%", "center", "sort");
$HD_Form -> AddViewElement(gettext("SECRET"), "secret", "15%", "center", "sort");

//$HD_Form -> FieldViewElement ("IF(regexten='',name,regexten) AS regexten, fromuser, language, host, ".(($atmenu!='IAX')?"callgroup, pickupgroup, ":"")."name, secret ");
$HD_Form -> FieldViewElement ("IFNULL(callbackextension,IFNULL(fromuser,defaultuser)) AS did, name, language, fromuser, IF(port>0,concat('<font color=green>',ipaddr,':',port,'</font>'),host), defaultuser, secret ");

$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO ".strtoupper($HD_Form->FG_INSTANCE_NAME)." CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 24;
$HD_Form -> FG_OTHER_BUTTON1 = false;

if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
    $HD_Form -> FG_TABLE_CLAUSE.=" AND ";

$HD_Form -> FG_TABLE_CLAUSE.="id_cc_card =".$_SESSION["card_id"]." AND external = 1";

if (strlen($HD_Form -> FG_EDITION_CLAUSE)>0)
    $HD_Form -> FG_EDITION_CLAUSE.=" AND ";

$HD_Form -> FG_EDITION_CLAUSE.="id_cc_card =".$_SESSION["card_id"]." AND external = 1";

$HD_Form -> FG_EDITION = false;
$HD_Form -> FG_DELETION = false;
$HD_Form -> FG_ADDITION = false;
$HD_Form -> FG_VIEW_TABLE_WITDH = '70%';

$HD_Form -> AddEditElement (gettext("EXTENSION "),
					"regexten",
					$exten_include,
					"INPUT",
					"size=50 maxlength=5",
					regex_range($extmin,$extmax,$exten_include),
					"Enter right number from range below exclude afore existing numbers",
					"", "", "", "", "", "", "", gettext("Extension number from ").$extmin.gettext(" to ").$extmax);

$HD_Form -> AddEditElement (gettext("REGISTRATION NAME"),
					"name",
					"",
					"INPUT",
					"size=50 readonly",
					"",
					"",
					"", "", "", "", "", "", "", gettext("Use for information only"));

$HD_Form -> AddEditElement (gettext("SECRET"),
					"secret",
					"",
					"INPUT",
					"size=50 maxlength=40",
					"",
					"",
					"", "", "", "", "", "", "", gettext("Password"));

$HD_Form -> AddEditElement (gettext("CALLERID NAME"),
					"callerid",
					"",
					"INPUT",
					"size=50 maxlength=40",
					"",
					"",
					"", "", "", "", "", "", "", gettext("Allows you to set the Name of the caller"));

$HD_Form -> AddEditElement (gettext("NAT"),
					"nat",
					"",
					"SELECT",
					"",
					"",
					"",
					"list", "", "", "", $values_list['nat'], "%1", "", gettext("Is this extension behind the nat?"));

$field_list_edit = 'regexten, name, secret, callerid, nat';

$HD_Form -> FieldEditElement ($field_list_edit);

$HD_Form -> FG_FILTER_APPLY = false;
$HD_Form -> FG_FILTERFIELD = 'name';
$HD_Form -> FG_FILTERFIELDNAME = gettext('NAME');
$HD_Form -> FG_FILTER_FORM_ACTION = 'list';


if (isset($filterprefix)  &&  (strlen($filterprefix)>0)){
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="name like '$filterprefix%'";
}

$HD_Form -> FG_INTRO_TEXT_EDITION= '';
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." EXTENSION, ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new").$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

$HD_Form -> FG_INTRO_TEXT_ADITION = '';

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Click 'Confirm Data' to continue");

$HD_Form->FG_ADDITIONAL_FUNCTION_AFTER_DELETE = $HD_Form->FG_ADDITIONAL_FUNCTION_AFTER_EDITION = 'processing_sipiax_generate';

//$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=$atmenu&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=$atmenu&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF']."?atmenu=$atmenu&stitle=Document&wh=AC&id=";
