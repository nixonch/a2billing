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
	getpost_ifset(array('id', 'tariffname', 'startingdate', 'expirationdate', 'description', 'dnidprefix', 'calleridprefix', 'id_trunk', 'id_seller', 'sellerdialprefix', 'tariff_lcr'));
}

$HD_Form = new FormHandler("cc_tariffplan","Ratecard");

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = " tariffname";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "DESC";


$HD_Form ->FG_LIST_ADDING_BUTTON1 = true;
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_tariffplan.php?form_action=ask-add&atmenu=tariffplan&section=".$_SESSION["menu_section"];
$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add RateCard");
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/table_multiple.png" ;

// Dependent Tables
$HD_Form -> FG_FK_DELETE_ALLOWED = true;
$HD_Form -> FG_FK_DELETE_CONFIRM = true;
$HD_Form -> FG_FK_WARNONLY = false;
$HD_Form -> FG_FK_TABLENAMES = array("cc_ratecard", "cc_sheduler_ratecard");
$HD_Form -> FG_FK_EDITION_CLAUSE = array("idtariffplan", "id_tariffplan");
$HD_Form -> FG_FK_DELETE_MESSAGE = gettext("You are going as well to remove all the schedules and rates attached to this tariffplan! Please confirm that you really want to remove this tariffplan and those rates?");

$HD_Form -> AddViewElement(gettext("ID"), "id", "4%", "center", "sort");
$HD_Form -> AddViewElement(gettext("TARIFFNAME"), "tariffname", "18%", "left", "sort");
$HD_Form -> AddViewElement(gettext("START DATE"), "startingdate", "13%", "center", "sort", "16", "", "", "", "", "", "display_dateformat");
$HD_Form -> AddViewElement(gettext("EXPIRY DATE"), "expirationdate", "13%", "center", "sort", "16", "", "", "", "", "", "display_dateformat");
$HD_Form -> AddViewElement(gettext("TRUNK"), "id_trunk", "11%", "center", "sort", "15", "lie", "cc_trunk", "trunkcode", "id_trunk='%id'", "%1");
$HD_Form -> AddViewElement(gettext("MINUTES USED"), "secondusedreal", "9%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
$HD_Form -> AddViewElement(gettext("DNID PREFIX"), "dnidprefix", "8%", "center", "SORT", "30");
$HD_Form -> AddViewElement(gettext("CID PREFIX"), "calleridprefix", "8%", "center", "SORT", "30");

$HD_Form -> FieldViewElement ('id, tariffname, startingdate, expirationdate, id_trunk, secondusedreal, dnidprefix, calleridprefix');

$HD_Form -> FG_ACTION_SIZE_COLUMN='12%';
$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO")." ".strtoupper($HD_Form->FG_INSTANCE_NAME)." ".gettext("CREATED!");  
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;


$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_ADDITION = true;


$HD_Form -> AddEditElement (gettext("TARIFFNAME"),
			   "tariffname",		
			   '$value',	
			   "INPUT",	
			   "size=40 maxlength=30",
			   "3",	
			   gettext("Insert the tariff name "),
			   "" , "", "",	"", "", "", "",	"");

if ($form_action=="ask-add"){	
	$begin_date = date("Y");
//	$begin_date_plus = date("Y")+20;
	$begin_date_plus = "2036";
	$end_date = date("-m-d H:i:s");
	$comp_date = "value='".$begin_date.$end_date."'";
	$comp_date_plus = "value='".$begin_date_plus.$end_date."'";
}

$HD_Form -> AddEditElement (gettext("START DATE"),
			   "startingdate",		
			   '$value',	
			   "INPUT",	
			   "size=40 maxlength=40  $comp_date",	
			   "10",	
			   gettext("Insert the starting date"), 
			   "" , "", "",	"", "", "", "res_display_dateformat", gettext("Please use the format YYYY-MM-DD HH:MM:SS. For instance, '2004-12-31 00:00:00'"));

$HD_Form -> AddEditElement (gettext("EXPIRY DATE"),
			   "expirationdate",		
			   '$value',	
			   "INPUT",	
			   "size=40 maxlength=40  $comp_date_plus",	
			   "10",	
			   gettext("Insert the expiration date"),
			   "" , "", "",	"", "", "", "res_display_dateformat", gettext("Format YYYY-MM-DD HH:MM:SS. For instance, '2004-12-31 00:00:00'"));

if ($form_action<>"ask-add") {
$HD_Form -> AddEditElement(gettext("SCHEDULE"),
			   "shedule",
			   '$value',
			   "POPUPDAYTIME",
			   "", "", "", "",
			   "cc_sheduler_ratecard",
			   "weekdays, TIME_TO_SEC(timefrom) timefrom, TIME_TO_SEC(timetill) timetill",
			   "id_tariffplan='%id'", "'%id','%1',SEC_TO_TIME('%2'),SEC_TO_TIME('%3')", "", "", "", ":", "`id_tariffplan`,`weekdays`,`timefrom`,`timetill`");
}

$HD_Form -> AddEditElement (gettext("DESCRIPTION"),
			   "description",
			   '',	
			   "TEXTAREA",	
			   "cols=50 rows=4",	
			   "",	
			   gettext("Insert the description"),
			   "", "", "", "", "", "", "NO-NULL", "");

if ($form_action=="ask-add"){	
	$val_dnidprefix = "value='all'";
	$val_calleridprefix = "value='all'";
}

$HD_Form -> AddEditElement (gettext("DNID PREFIX"),
			   "dnidprefix",		
			   '$value',	
			   "INPUT",	
			   "size=20 $val_dnidprefix maxlength=20",	
			   "14",	
			   gettext("Insert the dnid prefix"),
			   "" , "", "",	"", "" , "", "", gettext("Set the DNID rules to choose the ratecard 'dnidprefix', by default, matches all DNID. For instance, Set the DNIDPrefix  to 900540540 to choose this ratecard when the DNID is 900540540"));


$HD_Form -> AddEditElement (gettext("CALLERID PREFIX"),
			   "calleridprefix",		
			   '$value',	
			   "INPUT",	
			   "size=20 $val_calleridprefix maxlength=20",	
			   "14",	
			   gettext("Insert the dnid prefix"),
			   "" , "", "",	"", "" , "", "", gettext("Set the CallerID rules to choose the ratecard 'calleridprefix', by default, matches all callerID. For instance, Set the calleridprefix to 900540540 to choose this ratecard when the CallerID is 900540540."));

$HD_Form -> AddEditElement (gettext("TRUNK"),
			   "id_trunk",
			   '$value',
			   "SELECT",
			   "", "", "",
			   "sql",
			   "cc_trunk",
			   "trunkcode, id_trunk",
			   "", "", "%1", "", gettext("You should define default Trunk for this RateCard"), "", '<OPTION  value="-1" selected>NOT DEFINED</OPTION>');

$HD_Form -> AddEditElement (gettext("SELLER ID"),
			   "id_seller",
			   '',
			   "POPUPVALUE",
			   "size=30 maxlength=50",
			   "4",
			   gettext("Insert the Id Customer ID"),
			   "", "", "", "", "",
			   "A2B_entity_card.php?popup_select=1&",
			   ", 'CardNumberSelection','width=550,height=550,top=100,left=100,scrollbars=1'" ,
			   gettext("This Customer will be credited a fee for calls over the RateCard"));

$HD_Form -> AddEditElement (gettext("ADD PREFIX"),
			    "sellerdialprefix",
			    "$value",
			    "INPUT",
			    "size=30 maxlength=20",
			    "",
			    gettext("Insert Terminator trunk prefix"),
			    "", "", "", "", "", "", "NO-NULL", gettext("Add a prefix to the dialled digits for identify on Terminator side"));

$HD_Form -> AddEditElement (gettext("LCR BEHAVIOR"),
			    "tariff_lcr",
			    '0',
			    "RADIOBUTTON",
			    "",
			    "",
			    "",
			    "" , "", "" , "Yes:1, - No:0", "", "", "", gettext("Expiry ratecars ignore for LCR selecting. Old behavior is 'Yes'."));


$HD_Form -> FieldEditElement ('tariffname, startingdate, expirationdate, id_seller, description, dnidprefix, calleridprefix, id_trunk, id_seller, sellerdialprefix, tariff_lcr');

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("Modify the properties of the rate card.");
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

$HD_Form -> FG_INTRO_TEXT_ADITION = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ". $HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted.")." <br>";

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT =  gettext("Setup those values to create the new")." ".$HD_Form->FG_INSTANCE_NAME;

$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
