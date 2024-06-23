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

$json_data = $_POST["json_data"];
if (isset($json_data)) {
    $json_data = stripslashes($json_data);
    extract(json_decode($json_data,true,512));
} else {
    getpost_ifset(array('id','form_action','ivrname','repeats','waitsecsfordigits'));
}

$id_cc_card = $_SESSION["card_id"];

$HD_Form = new FormHandler("cc_ivr", gettext("IVR"));

$HD_Form -> FG_TABLE_ID = 'cc_ivr.id';
$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "ivrname";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

$HD_Form ->FG_LIST_ADDING_BUTTON1 = false;

$HD_Form -> AddViewElement(gettext("IVR Name"), "ivrname", "25%", "center", "sort");
$HD_Form -> AddViewElement(gettext("REPEATS"), "repeats", "25%", "center", "sort");
$HD_Form -> AddViewElement(gettext("WAIT FOR DIGITS"), "waitsecsfordigits", "25%", "center", "sort");

$HD_Form -> FieldViewElement ("ivrname,repeats,waitsecsfordigits");

$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO ".strtoupper($HD_Form->FG_INSTANCE_NAME)." CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 24;
$HD_Form -> FG_OTHER_BUTTON1 = false;
$HD_Form -> FG_VIEW_TABLE_WITDH = '70%';

if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
    $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
$HD_Form -> FG_TABLE_CLAUSE.="id_cc_card='".$_SESSION["card_id"]."'";

$HD_Form -> FG_DELETION_CLAUSE = "id='$id' AND id_cc_card='{$_SESSION['card_id']}'";

$HD_Form -> FG_EDITION = false;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_ADDITION = false;

$HD_Form -> FG_FK_DELETE_ALLOWED = true;
$HD_Form -> FG_FK_DELETE_CONFIRM = false;
$HD_Form -> FG_FK_DELETE_BUTTON_IF_DEPENDENT = false;
$HD_Form -> FG_FK_WARNONLY = false;
$HD_Form -> FG_FK_TABLENAMES = array("cc_ivr_sounds", "cc_ivr_destinations");
$HD_Form -> FG_FK_EDITION_CLAUSE = array("id_cc_ivr_dest IN (SELECT id FROM cc_ivr_destinations WHERE id_cc_ivr='%id') OR id_cc_ivr", "id_cc_ivr");

$HD_Form -> FG_OTHER_BUTTON1 = true;
//$HD_Form -> FG_OTHER_BUTTON1_CONDITION = '"|col5|" != ""';
$HD_Form -> FG_OTHER_BUTTON1_LINK= $PHP_SELF."?section=15&form_action=list&id=|param|";
$HD_Form -> FG_OTHER_BUTTON1_IMG = Images_Path . "/edit.png";

$HD_Form -> AddEditElement (gettext("IVR Name"),
					"ivrname",
					"",
					"INPUT",
					"size=40 maxlength=40",
					null,null,null,null,null,null,null,null);
$HD_Form -> AddEditElement (gettext("REPEATS"),
					"repeats",
					"",
					"INPUT",
					"size=40 maxlength=30",
					null,null,null,null,null,null,null,null);
$HD_Form -> AddEditElement (gettext("WAIT FOR DIGITS"),
					"waitsecsfordigits",
					"",
					"INPUT",
					"size=40 maxlength=30",
					null,null,null,null,null,null,null,null);

$HD_Form -> FieldEditElement ('ivrname, repeats, waitsecsfordigits');
/**
$HD_Form->FG_QUERY_ADITION_HIDDEN_FIELDS = 'id_cc_card';
$HD_Form->FG_QUERY_ADITION_HIDDEN_VALUE = $_SESSION["card_id"];
$HD_Form->FG_ADITION_HIDDEN_PARAM		= $HD_Form->FG_EDITION_HIDDEN_PARAM		= 'filterprefix,current_page';
$HD_Form->FG_ADITION_HIDDEN_PARAM_VALUE 	= $HD_Form->FG_EDITION_HIDDEN_PARAM_VALUE	= $filterprefix.",".$current_page;

$HD_Form -> FG_FILTER_APPLY = false;
$HD_Form -> FG_FILTERFIELD = 'ivrname';
$HD_Form -> FG_FILTERFIELDNAME = gettext('IVR Name');
$HD_Form -> FG_FILTER_FORM_ACTION = 'list';

if (isset($filterprefix)  &&  (strlen($filterprefix)>0)){
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="ivrname LIKE '$filterprefix%'";
}

$HD_Form -> FG_INTRO_TEXT_ADITION = '';

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Click 'Confirm Data' to continue");
**/
$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $PHP_SELF."?id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $PHP_SELF."?id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $PHP_SELF."?id=";
