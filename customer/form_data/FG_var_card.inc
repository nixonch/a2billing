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



getpost_ifset(array('id', 'company_name', 'lastname', 'firstname', 'language', 'email', 'address','city', 'state', 'areaprefix', 'citylength', 'country', 'removeaddprefix', 'addprefixinternational', 'zipcode', 'id_timezone', 'phone', 'fax', 'message', 'voicemail_activated', 'monitor', 'recalldays', 'recalltime'));

$HD_Form = new FormHandler("cc_card","Card");

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_ID="id";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";
$HD_Form -> FG_HTML_TABLE_WIDTH = "60%";
$HD_Form -> FG_EDITION_CLAUSE = " id = ".$_SESSION["card_id"];

$HD_Form -> FG_INTRO_TEXT_EDITION = '';

$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_ADDITION = false;

$HD_Form -> AddEditElement (gettext("COMPANY NAME"),
			   "company_name",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=50",
			   "0",
			   gettext("Insert Company name"),
			   "", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("FIRSTNAME"),
			   "firstname",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=50",
			   "11",
			   gettext("Insert the firstname of the customers"),
			   "", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("LASTNAME"),
			   "lastname",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=50",
			   "11",
			   gettext("Insert the lastname of the customers"),
			   "", "", "", "", "", "", "NO", "");

$language_list = Constants::getLanguagesList();
$HD_Form->AddEditElement(gettext("LANGUAGE"),
			    "language",
			    "$language",
			    "SELECT",
			    "", "",
			    "", "list", "", "", "", $language_list, "%1", "", "");

$HD_Form -> AddEditElement (gettext("TIMEZONE"),
			   "id_timezone",
			   "",
			   "SELECT",
			   "", "", "",
			   "SQL",
			   "cc_timezone",
			   "gmtzone, id",
			   "", "", "%1", "", "", "", "" );

$HD_Form -> AddEditElement (gettext("PHONE NUMBER"),
			   "phone",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=20",
			   "7",
			   gettext("Insert the phone number of the customers"),
			   "", "", "", "", "", "", "", "");

$HD_Form -> AddEditElement (gettext("FAX NUMBER"),
			   "fax",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=20",
			   "7",
			   gettext("Insert the fax number of the customers"),
			   "", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("ADDRESS"),
			   "address",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=100",
			   "0",
			   gettext("Insert the address of the customers"),
			   "", "", "", "", "", "", "NO", "");


$HD_Form -> AddEditElement (gettext("CITY"),
			   "city",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=40",
			   "0",
			   gettext("Insert the city"),
			   "", "", "", "", "", "", "NO", "");


$HD_Form -> AddEditElement (gettext("STATE/PROVINCE"),
			   "state",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=40",
			   "11",
			   gettext("Insert the state"),
			   "", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("ZIP/POSTAL CODE"),
			   "zipcode",
			   '$value',
			   "INPUT",
			   "size=40 maxlength=20",
			   "0",
			   gettext("Insert the zipcode"),
			   "", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("COUNTRY"),
			   "country",
			   "",
			   "SELECT",
			   "", "", "",
			   "sql",
			   "cc_country",
			   "countryname, countrycode, countryprefix",
			   "", "", "%1 +%3", "", "", "", '' );

$HD_Form -> AddEditElement(gettext("AREA CODE"),
                            "areaprefix",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=6",
                            "22",
                            "",
                            "", "", "", "", "", "", "NO-NULL", gettext("Set prefix of the city by default (i.e. 30 for Berlin)"));

$HD_Form -> AddEditElement(gettext("CITY LENGTH"),
                            "citylength",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=1",
                            "23",
                            "",
                            "", "", "", "", "", "", "NO-NULL", gettext("Set length of city numbers"));

$HD_Form -> AddEditElement(gettext("REMOVE+ADD PREFIX"),
                            "removeaddprefix",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=30",
                            "31",
                            "",
                            "", "", "", "", "", "", "NO", gettext("Set for display CallerID on internal phones which prefix you want to remove and then add with comma separated (i.e. \"38044,,38,\")"));

$HD_Form -> AddEditElement(gettext("ADD INTERNATIONAL PREFIX"),
                            "addprefixinternational",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=3",
                            "",
                            "",
                            "", "", "", "", "", "", "NO", gettext("Set for display on internal phones which prefix you want see before international CallerID (i.e. \"+\")"));

$HD_Form -> AddEditElement (gettext("RECALL DAYS"),
			   "recalldays",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=20",
			   "4",
			   "",
			   "" , "", "", "", "", "" , "NO", gettext("Define number of days ago for use back phone number by callers you have called in past this days"));
			
$HD_Form -> AddEditElement (gettext("RECALL TIME"),
			   "recalltime",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=4 readonly",
			   "4",
			   "",
			   "" , "", "", "", "", "", "NO", gettext("Max duration to recall in seconds. Set '-1' for paritet calls."));

if (ACT_VOICEMAIL && $ACXVOICEMAIL) {
	$HD_Form -> AddEditElement (gettext("ACTIVE VOICEMAIL SERVICE"),
				   "voicemail_activated",
				   '0',
				   "RADIOBUTTON",
				   "",
				   "",
				   gettext("Customer can choose to enable the voicemail for this card"),
				   "" , "", "", "Yes :1, - No:0", "", "" , "", gettext("You can enable the voicemail"));

	$add_fields_voicemail = ', voicemail_activated';
} else	$add_fields_voicemail = '';

if ($ACXSEERECORDING){
$HD_Form -> AddEditElement (gettext("FORWARD TO EMAIL"),
                           "speech2mail",
                           '$value',
                           "INPUT",
                           "size=70 maxlength=70",
                           "1",
                           "",
                           "", "", "", "", "", "", "NO", gettext("Insert the email to forwarding the conversations"),
			   null, null, gettext("Surveillance Section"));

$HD_Form -> AddEditElement (gettext("SEND TEXT"),
			   "send_text",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   "",
			   "", "", "", gettext("Yes")." :1, - ".gettext("No").":0", "", "" , "", gettext("Define if you want to recognize the conversations and to send like regular text to email above"));

$HD_Form -> AddEditElement (gettext("SEND SOUND"),
			   "send_sound",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   "",
			   "", "", "", gettext("Yes")." :1, - ".gettext("No").":0", "", "" , "", gettext("Define if you want to forwarding recorded conversations to email above"));

$HD_Form -> AddEditElement (gettext("STORAGE"),
			   "monitor",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   gettext("Enable to monitor the call (to record all the conversations) value : YES - NO"),
			   "", "", "", gettext("Yes")." :1, - ".gettext("No").":0", "", "" , "", gettext("Define if you want to record and store all the conversations"));

	$add_fields_monitor = ', speech2mail, send_text, send_sound, monitor';
} else	$add_fields_monitor = '';

$HD_Form -> FieldEditElement ('company_name, firstname, lastname, language, id_timezone, phone, fax, address, city, state, zipcode, country, areaprefix, citylength, removeaddprefix, addprefixinternational, recalldays, recalltime'.$add_fields_voicemail.$add_fields_monitor);

$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily update your information clicking on the button.");

$HD_Form -> FG_INTRO_TEXT_ADITION = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your record has been updated.")."<br>";

$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?message=success";
