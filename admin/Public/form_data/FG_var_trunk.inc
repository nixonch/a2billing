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



getpost_ifset(array('id', 'trunkcode', 'trunkprefix', 'removeprefix', 'providertech', 'providerip', 'addparameter', 'dialprefixmain', 'failover_trunk', 'id_provider', 'inuse', 'maxuse', 'status', 'if_max_use', 'cid_handover', 'outbound_cidgroup_id', 'attract', 'message',
	'dialprefixa', 'failover_trunka', 'secondusedcarrier',  'startdatea', 'stopdatea', 'billblockseca', 'perioda', 'periodexpirya', 'timelefta', 'maxsecperperioda', 'periodcounta', 'lastcallstoptimea',
	'dialprefixb', 'failover_trunkb', 'secondusedratecard', 'startdateb', 'stopdateb', 'billblocksecb', 'periodb', 'periodexpiryb', 'timeleftb', 'maxsecperperiodb', 'periodcountb', 'lastcallstoptimeb'));

$DBHandle  = DbConnect();
$instance_table = new Table();

$HD_Form = new FormHandler("cc_trunk",gettext("trunk"));

$HD_Form ->FG_LIST_ADDING_BUTTON1 = true;
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_trunk.php?form_action=ask-add&section=".$_SESSION["menu_section"];
$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add Trunk");
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/add.png" ;


$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_ID = "id_trunk";
$HD_Form -> FG_TABLE_DEFAULT_ORDER = " trunkcode";
$HD_Form -> FG_EDITION_CLAUSE = " id_trunk='%id' ";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

$actived_list = array();
$actived_list["1"] = array( gettext("Active"), "1");
$actived_list["0"] = array( gettext("Inactive"), "0");

$trunk_actived_list = array();
$trunk_actived_list["0"] = array( gettext("0"), "Inactive");
$trunk_actived_list["1"] = array( gettext("1"), "Active");

$provider_list_query = "select * from cc_provider";
$res_provider_list  = $instance_table->SQLExec ($DBHandle, $provider_list_query);
if (is_array($res_provider_list)) {
    foreach($res_provider_list as $val){
	    $provider_list_result[] = array(gettext($val['id']),$val['provider_name']);
    }
}

$maxuse_behaviour_list = array();
$maxuse_behaviour_list["0"] = array( gettext("Use failover trunk"), "0");
$maxuse_behaviour_list["1"] = array( gettext("Use next trunk"), "1");
//$maxuse_behaviour_list["2"] = array( gettext("Use between current and failover trunk randomly"), "2");

// Dependent Tables
$HD_Form -> FG_FK_DELETE_ALLOWED = true;
$HD_Form -> FG_FK_DELETE_CONFIRM = true;
$HD_Form -> FG_FK_WARNONLY = true;
$HD_Form -> FG_FK_TABLENAMES = array("cc_trunk", "cc_trunk", "cc_trunk", "cc_ratecard", "cc_trunk_rand", "cc_tariffplan");
$HD_Form -> FG_FK_EDITION_CLAUSE = array("failover_trunk", "failover_trunka", "failover_trunkb", "id_trunk", "trunk_id = '%id' OR trunk_dependa = '%id' OR trunk_dependb", "id_trunk");

//$HD_Form -> FG_FK_DELETE_MESSAGE = gettext("You have some rates attached to this Trunk! Please comfirm that you really want to remove this Trunk? ");
$HD_Form -> FG_FK_DELETE_MESSAGE = gettext("There are some rates attached to this Trunk and/or there are cross-links with other Trunks!<br>Please confirm that you really want to remove this Trunk and cross-links?");


if ($popup_select) {
	$HD_Form -> AddViewElement(gettext("ID"), "id_trunk", "7%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("LABEL"), "trunkcode", "15%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("PROVIDER"), "id_provider" ,"10%", "center", "sort", "15", "lie", "cc_provider", "provider_name", "id='%id'", "%1");

	$HD_Form -> FieldViewElement ('id_trunk, trunkcode, id_provider');
} else {
	$HD_Form -> AddViewElement(gettext("LABEL"), "trunkcode", "14%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("ADD PREFIX"), "trunkprefix", "6%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("REMOVE PREFIX"), "removeprefix", "6%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("TECH"), "providertech", "6%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("IP/HOST"), "providerip" , "14%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("PROVIDER"), "id_provider" ,"9%", "center", "sort", "15", "lie", "cc_provider", "provider_name", "id='%id'", "%1");
	$HD_Form -> AddViewElement(gettext("MINUTES USED"), "secondusedreal", "9%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
	$HD_Form -> AddViewElement(gettext("LEFT_1"), "left1", "4%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
	$HD_Form -> AddViewElement(gettext("LEFT_2"), "left2", "4%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
	$HD_Form -> AddViewElement(gettext("STATUS"), "status", "7%", "center", "sort", "", "list", $actived_list);
	$HD_Form -> AddViewElement(gettext("INUSE"), "inuse" , "2%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("MAXUSE"), "maxuse" , "3%", "center", "sort");

	$HD_Form -> FieldViewElement ("trunkcode, trunkprefix, removeprefix, providertech, providerip, id_provider, secondusedreal, IF(dialprefixa='',0,maxsecperperioda-IF(periodexpirya<now(),0,periodcounta)) left1, IF(dialprefixb='',0,maxsecperperiodb-IF(periodexpiryb<now(),0,periodcountb)) left2, status, inuse, maxuse");
}

$HD_Form -> FG_FILTER_SEARCH_FORM = true;
$HD_Form -> FG_FILTER_SEARCH_TOP_TEXT = gettext("Search");
$HD_Form -> AddSearchElement_C1(gettext("IP/HOST"),'providerip','provideriptype');
$HD_Form -> FG_FILTER_SEARCH_FORM_SELECT_TEXT = '';
$HD_Form -> AddSearchElement_Select(gettext("SELECT PROVIDER"), null, null, null, null, null, "id_provider", 0, $provider_list_result);
$HD_Form -> AddSearchElement_Select(gettext("SELECT STATUS"), null, null, null, null, null, "status", 0, $trunk_actived_list);

$HD_Form -> FG_ACTION_SIZE_COLUMN = '15%';
$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO ".strtoupper($HD_Form->FG_INSTANCE_NAME)." CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;


if ($popup_select && $popup_select<4) {
	$HD_Form -> FG_LIMITE_DISPLAY = 10;
	$HD_Form -> CV_FOLLOWPARAMETERS = "&popup_select=" . $popup_select . "&popup_formname=" . $popup_formname . "&popup_fieldname=" . $popup_fieldname;

	$HD_Form -> FG_OTHER_BUTTON1 = true;
	$HD_Form -> FG_OTHER_BUTTON1_ALT = '<font color="red">&lt;select&gt;</font>';
	$HD_Form -> FG_OTHER_BUTTON1_IMG = '';

	//echo $popup_select;
	if ($popup_select == 1)
		$HD_Form -> FG_OTHER_BUTTON1_LINK = "javascript:sendValue('|param|');";
	elseif ($popup_select == 2)
		$HD_Form -> FG_OTHER_BUTTON1_LINK = "javascript:sendValue('|col0|');";
} else {
	$HD_Form -> FG_LIMITE_DISPLAY = 20;
	$HD_Form -> FG_EDITION = true;
	$HD_Form -> FG_CAPCHA_POPUP = '|col0|';
	$HD_Form -> FG_DELETION = true;
	$HD_Form -> FG_ADDITION = true;
}

if (isset($form_action) && $form_action=="ask-add"){
	$def_value_inuse = "value='0'";
	$def_value_maxuse = "value='-1'";
	$def_maxsecperperiod = "value='-1'";
	$def_timeleft = "value='60'";
	$def_billblocksec = "value='1'";
	$def_period = "value='31'";
	$begin_date = date("Y");
//	$begin_date_plus = date("Y")+15;
	$begin_date_plus = "2036";
	$end_date = date("-m-d H:i:s");
	$comp_date = "value='".$begin_date.$end_date."'";
	$comp_date_plus = "value='".$begin_date_plus.$end_date."'";
//	$tomorrow_date = "value='".date("Y-m-d 00:00:00",mktime(0, 0, 0, date("m"), date("d")+1, date("Y")))."'";
	$nextmonth_date = "value='".date("Y-m-d 00:00:00",mktime(0, 0, 0, date("m")==12?1:date("m")+1, 1, date("Y")+(int)(date("m")/12)))."'";
} else {
	$def_value_inuse = '';
	$def_value_maxuse = '';
	$def_maxsecperperiod = '';
	$def_timeleft = '';
	$def_billblocksec = '';
	$def_period = '';
	$nextmonth_date = '';
}

$HD_Form -> AddEditElement (gettext("VOIP-PROVIDER"),
	"id_provider",
	'$value',
	"SELECT",
	"", "", "",
	"sql",
	"cc_provider",
	"provider_name, id",
	"", "", "%1","", "", '', '<OPTION  value="-1" selected>NOT DEFINED</OPTION>');
	
$HD_Form -> AddEditElement (gettext("LABEL"),
	"trunkcode",
	"$value",
	"INPUT",
	"size=45 maxlength=40",
	"3",
	gettext("Insert the trunkcode"),
	"" , "", "", "", "" , "" , "", gettext("Unique and friendly name for the trunk"));

$HD_Form -> AddEditElement (gettext("ADD PREFIX"),
	"trunkprefix",
	"$value",
	"INPUT",
	"size=30 maxlength=20",
	"",
	gettext("Insert the trunkprefix"),
	"" , "", "", "", "" , "" , "", gettext("Add a prefix to the dialled digits."));
	
$HD_Form -> AddEditElement (gettext("REMOVE PREFIX"),
	"removeprefix",
	"$value",
	"INPUT",
	"size=30 maxlength=30",
	"25",
	gettext("Insert the removeprefix"),
	"", "", "", "", "", "" , "NO", gettext("Remove prefix from the dialed digits."));
	
$HD_Form -> AddEditElement (gettext("PROVIDER TECH"),
	"providertech",
	"$value",
	"INPUT",
	"size=20 maxlength=15",
	"0",
	gettext("Insert the providertech"),
	"", "", "", "", "", "" , "", gettext("Technology used on the trunk (SIP,IAX2,ZAP,H323)"));
	
$HD_Form -> AddEditElement (gettext("PROVIDER IP"),
	"providerip",
	"$value",
	"INPUT",
	"size=80 maxlength=140",
	"9",
	gettext("Insert the providerip"),
	"" , "", "", "", "" , "" , "", gettext("Set the IP or URL of the VoIP provider. Alternatively, put in the name of a previously defined trunk in Asterisk or FreePBX. (MyVoiPTrunk, ZAP4G etc.) You can use the following tags to as variables: %dialingnumber%, %cardnumber%. ie g2/1644787890wwwwwwwwww%dialingnumber%"));

$HD_Form -> AddEditElement (gettext("ADDITIONAL PARAMETER"),
	"addparameter",
	"$value",
	"INPUT",
	"size=60 maxlength=100",
	"",
	gettext("Insert Additional parameters"),
	"" , "", "", "", "" , "" , "", gettext("Define any additional parameters that will be used when running the Dial Command in Asterisk. Use the following tags as variables  %dialingnumber%, %cardnumber%. ie 'D(ww%cardnumber%wwwwwwwwww%dialingnumber%)'"));

$HD_Form -> AddEditElement(gettext("DIALPREFIX CONDITION"),
	"dialprefixmain",
	'$value',
	"INPUT",
	"size=50 maxlength=80",
	"",
	gettext("Insert the dialing prefix, such as for UK '44' or for US '1' "),
	"", "", "", "", "", "", "",
	gettext("Destination prefix condition for FAILOVER TRUNK with respect to REMOVE PREFIX. This will overrule for Destinations Limited if defined. Leave empty to use FAILOVER TRUNK without condition."));

$HD_Form -> AddEditElement (gettext("FAILOVER TRUNK"),
	"failover_trunk",
	'$value',
	"SELECT",
	"", "", "",
	"sql",
	"cc_trunk",
	"trunkcode, id_trunk",
	"id_trunk!='%id'", "", "%1", "", gettext("You can define another trunk in case of failover!"), "", '<OPTION  value="-1" selected>NOT DEFINED</OPTION>');

$HD_Form -> AddEditElement (gettext("CURRENT CONNECTIONS"),
	"inuse",
	"",
	"INPUT",
	"size=30 $def_value_inuse maxlength=30",
	"4",
	gettext("Number of current connections"),
	"" , "", "", "", "", "", "", gettext("Updated to show the number of channels currently in use on this trunk.If there are no channels in use, and the system shows that there are, manually reset this field back to zero.") );

$HD_Form -> AddEditElement (gettext("MAXIMUM CONNECTIONS"),
	"maxuse",
	"",
	"INPUT",
	"size=30 $def_value_maxuse maxlength=30",
	"29",
	gettext("Number of maximum simultaneous connections"),
	"" , "", "", "", "", "", "", gettext("The maximum number of channels available to this trunk. Set to -1 to have an unlimited number of channels") );

$HD_Form -> AddEditElement ("IFMAXUSED",
	"if_max_use",
	'$value',
	"SELECT",
	"",
	"",
	"",
	"list" , "", "", "", $maxuse_behaviour_list, "%1" , "", gettext("Specifies which trunk to use when the maximum number of connections is reached and no once of Destinations Limited was used."));

$HD_Form -> AddEditElement ("STATUS",
	"status",
	'$value',
	"SELECT",
	"",
	"",
	"",
	"list" , "", "", "", $actived_list, "%1" , "", gettext("Define if this trunk is active or not"));

$HD_Form -> AddEditElement (gettext("cid_handover"),
	"cid_handover",
	'0',
	"RADIOBUTTON",
	"",
	"",
	"",
	"" , "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "", gettext("Choose 'Yes' if you want to handover customer`s CallerID (not extensions)"));

$HD_Form -> AddEditElement(gettext("CIDGroup"),
	"outbound_cidgroup_id",
	'$value',
	"SELECT",
	"", "", "",
	"sql",
	"cc_outbound_cid_group",
	"group_name, id",
	"", "", "%1", "", gettext("Set the Outgoing CID Group to use for this trunk, or RATECARD PRESET to use rate card presetted."), '', '<OPTION  value="-1" selected>'.gettext("RATECARD PRESET").'</OPTION>');

$HD_Form -> AddEditElement (gettext("TIMEOUT"),
	"wrapuptime",
	"$value",
	"INPUT",
	"size=30 maxlength=20",
	"24",
	gettext("Insert the timeout"),
	"" , "", "", "", "" , "" , "", gettext("Add a timeout range before next call in seconds, eg. '400,600' or '600'"));

$HD_Form -> AddEditElement (gettext("ATTRACTING DAYS"),
	"attract",
	"",
	"INPUT",
	"size=30 $def_value_inuse maxlength=3",
	"4",
	gettext("Number of last days"),
	"" , "", "", "", "", "", "", gettext("Attract choice of trunk calls to the numbers that have called this trunk, for this number of days past") );

$HD_Form -> AddEditElement(gettext("DIALPREFIX")." A",
	"dialprefixa",
	'$value',
	"INPUT",
	"size=50 maxlength=80",
	"",
	gettext("Insert the dialing prefix, such as for UK '44' or for US '1' "),
	"", "", "", "", "", "", "",
	gettext("Add destination prefix like for RateCard with respect to REMOVE PREFIX. Leave empty to disable all rule criterions."),
	null, null, gettext("First Destination Limited"));

$HD_Form -> AddEditElement (gettext("FAILOVER TRUNK")." A",
	"failover_trunka",
	'$value',
	"SELECT",
	"", "", "",
	"sql",
	"cc_trunk",
	"trunkcode, id_trunk",
	"id_trunk!='%id'", "", "%1", "", gettext("You can define another trunk in case of failover for currend dialprefix"), '', '<OPTION  value="-1" selected>NOT DEFINED</OPTION>');

$DBHandle_max  = DbConnect();
$numrow = 0;
$resmax = $DBHandle_max -> Execute("SELECT * FROM cc_trunk");
if ( $resmax )
	$numrow = $resmax -> RecordCount( );

if ($numrow>0) {
	$HD_Form -> AddEditElement (gettext("RANDOM TRUNKS")." A",
		"cc_trunk_rand_a",
		'$value',
		"SELECT",
		'size="15" multiple="multiple"',
		"",
		"",
		"sql",
		"cc_trunk_rand_a",
		"trunk_dependa, trunk_ida, trunkpercentagea",
		"", "", "%1 : (%2)", "", "", "cc_trunk_rand:trunk_dependa:cc_trunk LEFT JOIN cc_trunk_rand ON id_trunk=cc_trunk_rand.trunk_dependa:DISTINCT trunkcode, id_trunk, cc_trunk_rand.trunkpercentage:cc_trunk_rand.trunk_id = %id ORDER BY trunkcode:trunk_id:%2 - (%1)::::::%:3::id_trunk NOT IN (%2) GROUP BY id_trunk ORDER BY trunkcode:trunkpercentage");
}

$HD_Form -> AddEditElement(gettext("START DATE")." A",
	"startdatea",
	'$value',
	"INPUT",
	"size=40 maxlength=40 $comp_date",
	"10",
	gettext("Insert the starting date"),
	"", "", "", "", "", "", "", gettext("Define the period start when this rule is active. Format YYYY-MM-DD HH:MM:SS. For instance, '2010-12-31 00:00:00'."),
	null, "res_display_dateformat");

$HD_Form -> AddEditElement(gettext("EXPIRY DATE")." A",
	"stopdatea",
	'$value',
	"INPUT",
	"size=40 maxlength=40 $comp_date_plus",
	"10",
	gettext("Insert the stop date"),
	"", "", "", "", "", "NO-NULL", "", gettext("Define the period end when this rule is active. Format YYYY-MM-DD HH:MM:SS. For instance, '2010-12-31 00:00:00'."),
	null, "res_display_dateformat");

$HD_Form -> AddEditElement(gettext("BILLING BLOCK")." A",
	"billblockseca",
	'$value',
	"INPUT",
	"size=30 $def_billblocksec maxlength=20",
	"30",
	gettext("Insert the billing block"),
	"", "", "", "", "", "", "", gettext("Set the billing increment in seconds"));

$HD_Form -> AddEditElement(gettext("PERIOD")." A",
	"perioda",
	'$value',
	"INPUT",
	"size=30 $def_period maxlength=20",
	"4",
	gettext("Insert the period")." A",
	"", "", "", "", "", "", "", gettext("Define period in days. Set '0' if you want define EXPIRY PERIOD only. Set '31' for per month period."));

$HD_Form -> AddEditElement(gettext("EXPIRY PERIOD")." A",
	"periodexpirya",
	'$value',
	"INPUT",
	"size=40 maxlength=40 $nextmonth_date",
	"10",
	gettext("Correct the expiry period"),
	"", "", "", "", "", "NO-NULL", "", gettext("You can correct current period expiry. Format YYYY-MM-DD HH:MM:SS. This will auto increment in future."),
	null, "res_display_dateformat");

$HD_Form -> AddEditElement (gettext("MIN TIME LEFT FOR CALL")." A",
	"timelefta",
	"",
	"INPUT",
	"size=30 $def_timeleft maxlength=30",
	"4",
	gettext("Number of time left in seconds"),
	"" , "", "", "", "", "", "", gettext("Goto selected random trunks if time left less than seconds defined."));

$HD_Form -> AddEditElement(gettext("MAXIMUM SECONDS PER PERIOD")." A",
	"maxsecperperioda",
	"",
	"INPUT",
	"size=30 $def_maxsecperperiod maxlength=30",
	"29",
	gettext("Number of maximum seconds per period"),
	"" , "", "", "", "", "", "", gettext("Maximum q-ty of seconds allowed for current dial-prefix. Set '-1' for unlimited."));

if ($form_action<>"ask-add") {
	$HD_Form -> AddEditElement (gettext("USED SECONDS PER PERIOD")." A",
		"periodcounta",
		"$value",
		"INPUT",
		"size=30 maxlength=20",
		"",
		gettext("period count"),
		"" , "", "", "", "" , "" , "", gettext("Indicate for used seconds per period"));

	$HD_Form -> AddEditElement (gettext("LAST CALL STOPPED")." A",
		"lastcallstoptimea",
		"",
		"INPUT",
		"size=40 maxlength=40 readonly",
		"",
		gettext("last call stop date"),
		"" , "" , "", "", "", "", "NO-NULL", "", "", "res_display_dateformat");
}
$HD_Form -> AddEditElement(gettext("DIALPREFIX")." B",
	"dialprefixb",
	'$value',
	"INPUT",
	"size=50 maxlength=80",
	"",
	gettext("Insert the dialing prefix, such as for UK '44' or for US '1' "),
	"", "", "", "", "", "", "",
	gettext("Add destination prefix like for RateCard with respect to REMOVE PREFIX. Leave empty to disable all rule criterions."),
	null, null, gettext("Second Destination Limited"));

$HD_Form -> AddEditElement (gettext("FAILOVER TRUNK")." B",
	"failover_trunkb",
	'$value',
	"SELECT",
	"", "", "",
	"sql",
	"cc_trunk",
	"trunkcode, id_trunk",
	"id_trunk!='%id'", "", "%1", "", gettext("You can define another trunk in case of failover for currend dialprefix"), '', '<OPTION  value="-1" selected>NOT DEFINED</OPTION>');

if ($numrow>0) {
	$HD_Form -> AddEditElement (gettext("RANDOM TRUNKS")." B",
		"cc_trunk_rand_b",
		'$value',
		"SELECT",
		'size="15" multiple="multiple"',
		"",
		"",
		"sql",
		"cc_trunk_rand_b",
		"trunk_dependb, trunk_idb, trunkpercentageb",
		"", "", "%1 : (%2)", "", "", "cc_trunk_rand:trunk_dependb:cc_trunk LEFT JOIN cc_trunk_rand ON id_trunk=cc_trunk_rand.trunk_dependb:DISTINCT trunkcode, id_trunk, cc_trunk_rand.trunkpercentage:cc_trunk_rand.trunk_id = %id ORDER BY trunkcode:trunk_id:%2 - (%1)::::::%:3::id_trunk NOT IN (%2) GROUP BY id_trunk ORDER BY trunkcode:trunkpercentage");
}

$HD_Form -> AddEditElement(gettext("START DATE")." B",
	"startdateb",
	'$value',
	"INPUT",
	"size=40 maxlength=40 $comp_date",
	"10",
	gettext("Insert the starting date"),
	"" , "", "", "", "", "", "" , gettext("Define the period start when this rule is active. Format YYYY-MM-DD HH:MM:SS. For instance, '2010-12-31 00:00:00'."),
	null, "res_display_dateformat");

$HD_Form -> AddEditElement(gettext("EXPIRY DATE")." B",
	"stopdateb",
	'$value',
	"INPUT",
	"size=40 maxlength=40 $comp_date_plus",
	"10",
	gettext("Insert the stop date"),
	"", "", "", "", "", "NO-NULL", "", gettext("Define the period end when this rule is active. Format YYYY-MM-DD HH:MM:SS. For instance, '2010-12-31 00:00:00'."),
	null, "res_display_dateformat");

$HD_Form -> AddEditElement(gettext("BILLING BLOCK")." B",
	"billblocksecb",
	'$value',
	"INPUT",
	"size=30 $def_billblocksec maxlength=20",
	"30",
	gettext("Insert the billing block"),
	"" , "", "", "", "", "", "NO", gettext("Set the billing increment in seconds"));

$HD_Form -> AddEditElement(gettext("PERIOD")." B",
	"periodb",
	'$value',
	"INPUT",
	"size=30 $def_period maxlength=20",
	"4",
	gettext("Insert the period")." B",
	"" , "", "", "", "", "" , "NO", gettext("Define period in days. Set '0' if you want define EXPIRY PERIOD only. Set '31' for per month period."));

$HD_Form -> AddEditElement(gettext("EXPIRY PERIOD")." B",
	"periodexpiryb",
	'$value',
	"INPUT",
	"size=40 maxlength=40 $nextmonth_date",
	"10",
	gettext("Correct the expiry period"),
	"", "", "", "", "", "NO-NULL", "", gettext("You can correct current period expiry. Format YYYY-MM-DD HH:MM:SS. This will auto increment in future."),
	null, "res_display_dateformat");

$HD_Form -> AddEditElement (gettext("MIN TIME LEFT FOR CALL")." B",
	"timeleftb",
	"",
	"INPUT",
	"size=30 $def_timeleft maxlength=30",
	"4",
	gettext("Number of time left in seconds"),
	"" , "", "", "", "", "", "", gettext("Goto selected random trunks if time left less than seconds defined."));

$HD_Form -> AddEditElement (gettext("MAXIMUM SECONDS PER PERIOD")." B",
	"maxsecperperiodb",
	"",
	"INPUT",
	"size=30 $def_maxsecperperiod maxlength=30",
	"29",
	gettext("Number of maximum seconds per period"),
	"" , "", "", "", "", "", "", gettext("Maximum q-ty of seconds allowed for current dial-prefix. Set '-1' for unlimited."));

if ($form_action<>"ask-add") {
	$HD_Form -> AddEditElement (gettext("USED SECONDS PER PERIOD")." B",
		"periodcountb",
		"$value",
		"INPUT",
		"size=30 maxlength=20",
		"",
		gettext("period count"),
		"" , "", "", "", "" , "" , "", gettext("Indicate for used seconds per period"));

	$HD_Form -> AddEditElement (gettext("LAST CALL STOPPED")." B",
		"lastcallstoptimeb",
		"",
		"INPUT",
		"size=40 maxlength=40 readonly",
		"",
		gettext("last call stop date"),
		"" , "" , "", "", "", "", "NO-NULL", "", "", "res_display_dateformat");
}

$HD_Form -> FieldEditElement ('id_provider, trunkcode, trunkprefix, removeprefix, providertech, providerip, addparameter, dialprefixmain, failover_trunk, inuse, maxuse, if_max_use, status, cid_handover, outbound_cidgroup_id, wrapuptime, attract,
	dialprefixa, failover_trunka, secondusedcarrier,  startdatea, stopdatea, billblockseca, perioda, periodexpirya, timelefta, maxsecperperioda, periodcounta, lastcallstoptimea,
	dialprefixb, failover_trunkb, secondusedratecard, startdateb, stopdateb, billblocksecb, periodb, periodexpiryb, timeleftb, maxsecperperiodb, periodcountb, lastcallstoptimeb');

$HD_Form -> FG_QUERY_EDITION_HIDDEN_FIELDS = "wrapnexttime";
$HD_Form -> FG_QUERY_EDITION_HIDDEN_VALUE = date("Y-m-d H:i:s");

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("Modify the properties of the trunk below.");
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

$HD_Form -> FG_INTRO_TEXT_ADITION = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted.")."<br>";

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = 'Fill correctly the fields to add a new '.$HD_Form->FG_INSTANCE_NAME;

//$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&id=";
