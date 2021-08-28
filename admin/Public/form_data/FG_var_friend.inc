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



getpost_ifset(array('id_cc_card', 'cardnumber', 'id', 'name', 'amaflags', 'callgroup', 'namedcallgroup', 'namedpickupgroup', 'accountcode', 'callerid', 'canreinvite', 'context', 
'defaultip', 'dtmfmode', 'videosupport', 'fromuser', 'fromdomain', 'host', 'insecure', 'language', 'mailbox', 'md5secret', 'directmedia', 'nat', 'permit', 'deny', 'mask', 
'pickupgroup', 'port', 'qualify', 'warning_threshold', 'say_dialednumber', 'say_rateinitial', 'say_timetotalk', 'say_balance_after_call', 'restrictcid', 'rtptimeout', 
'rtpholdtimeout', 'secret', 'type', 'username', 'disallow', 'allow', 'musiconhold', 'regseconds', 'ipaddr', 'regexten','friend_id', 'callbackextension', 'callcounter', 'call-limit', 'external'));

getpost_ifset(array('defaultuser', 'subscribemwi', 'vmexten', 'cid_number', 'callingpres', 'usereqphone', 'incominglimit', 'subscribecontext', 'musicclass', 
'allowtransfer', 'autoframing', 'maxcallbitrate', 'outboundproxy', 'regserver', 'rtpkeepalive', 'dbsecret ', 'regcontext ', 'sourceaddress ', 'mohinterpret', 'cbe',
'mohsuggest ', 'inkeys', 'outkey', 'sendani', 'fullname', 'maxauthreq', 'encryption', 'ignorecryptolifetime', 'transport', 't38pt_udptl', 'forceencryption', 'transfer', 'jitterbuffer', 
'forcejitterbuffer', 'codecpriority', 'qualifysmoothing', 'qualifyfreqok ', 'qualifyfreqnotok ', 'timezone', 'adsi', 'filterprefix', 'filterprefix2', 'current_page'));

if ($atmenu=='sip') {
	$HD_Form = new FormHandler("cc_sip_buddies", gettext("SIP Config"));
} else {
	$HD_Form = new FormHandler("cc_iax_buddies", gettext("IAX Config"));
}


$HD_Form -> FG_DEBUG = 0;
$typepaid_list = Constants::getPaidTypeList();
$actived_list =  Constants::getActivationTrueFalseList();
$yesno = Constants::getYesNoList();

$HD_Form -> FG_FK_DELETE_CONFIRM = true;
$HD_Form -> FG_FK_DELETE_ALLOWED = true;
$HD_Form -> FG_FK_TABLENAMES = array('cc_voicemail_users');
$HD_Form -> FG_FK_EDITION_CLAUSE = array(strtolower($atmenu)."_buddy_id");
// DEFINE IF WE WANT TO DELETE THE CARD OR NOT
$HD_Form -> FG_FK_WARNONLY = (DELETE_FK_CARD == true) ? false: true;

if (DELETE_FK_CARD) {
        // WE WILL DELETE THE FK
        $HD_Form -> FG_FK_DELETE_MESSAGE = gettext("You are going as well to remove VoiceMail account attached to this SIP/IAX account! Please confirm that you really want to remove this SIP/IAX account?");
} else {
        // WE JUST NOTIFY
        $HD_Form -> FG_FK_DELETE_MESSAGE = gettext("You are going to remove SIP/IAX account that have VoiceMail account attached to it! Please confirm that you really want to remove this SIP/IAX account?");
}

$handle = DbConnect();
$instance_table = new Table();

$QUERY = "SHOW COLUMNS FROM cc_sip_buddies";
$result = $instance_table -> SQLExec ($handle, $QUERY);
$values_list = array();
foreach ($result as $one) {
	if (stripos($one[1], 'enum') !== false) {
		sscanf($one[1], "enum(%[^)])",$elem);
		$elem = explode("!", str_replace("'","",str_replace("','","'!'",$elem)));
		$value_elem = array();
		foreach ($elem as $key) array_push ($value_elem, array ($key, $key));
		array_unshift ($value_elem, array_shift($value_elem), array (null, null));
		$values_list[$one[0]] = $value_elem;
		unset($value_elem);
	}
}

if ($atmenu == 'sip' && $form_action=="ask-edit" && isset($id)) {
	$cbe_table = new Table('cc_sip_buddies','callbackextension');
	$cbe_list = $cbe_table -> Get_list ($handle, "id='$id'", null, null, null, null);
	$cbe = (is_array($cbe_list) && count($cbe_list)>0 && !is_null($cbe_list[0][0])) ? 1 : 0;
}

$HD_Form -> AddViewElement(gettext("CARDHOLDER"), "id_cc_card", "17%", "center", "sort", "50", "lie", "cc_card", "CONCAT_WS(' ',lastname,firstname,IF(company_name='','',CONCAT('(',company_name,')'))) AS customer", "id='%id'", "%1");
$HD_Form -> AddViewElement(gettext("ACCOUNTCODE"), "accountcode", "12%", "center", "sort", "", "", "", "", "", "", "linktocustomer");
$HD_Form -> AddViewElement(gettext("NAME"), "name", "6%", "center", "sort");
if ($atmenu=='sip') {
	$HD_Form -> AddViewElement(gettext("REGEXTEN"), "regexten", "6%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("DEFAULTUSER"), "defaultuser", "6%", "center", "sort");
	$un = 'regexten, defaultuser';
} else {
	$HD_Form -> AddViewElement(gettext("USERNAME"), "username", "6%", "center", "sort");
	$un = 'username';
}
$HD_Form -> AddViewElement(gettext("SECRET"), "secret", "6%", "center", "sort");
$HD_Form -> AddViewElement(gettext("CALLERID"), "callerid", "24%", "center", "sort");
$HD_Form -> AddViewElement(gettext("CONTEXT"), "context", "9%", "center", "sort");
$HD_Form -> AddViewElement("<font color=#00ff00>IP</font> / ".gettext("DEFAULTIP"), "curip", "20%", "center", "sort");


$HD_Form ->FG_LIST_ADDING_BUTTON1 = true;
$HD_Form ->FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_friend.php?form_action=ask-add&atmenu=$atmenu&section=".$_SESSION["menu_section"];

if ($atmenu=='sip') {
	$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add SIP Config");
	$HD_Form -> FieldViewElement ("id_cc_card, accountcode, name, $un, secret, callerid, context, IF(port>0 AND UNIX_TIMESTAMP()<regseconds,concat('<font color=green>',ipaddr,':',port,'</font> ',IF(lastms=-1,'UNREACHABLE',IF(lastms>0,concat('(',lastms,' ms)'),'')),'<br>',useragent),defaultip) AS curip");
} else {
	$HD_Form ->FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add IAX Config");
	$HD_Form -> FieldViewElement ("id_cc_card, accountcode, name, $un, secret, callerid, context, IF(port>0 AND UNIX_TIMESTAMP()<regseconds,concat('<font color=green>',ipaddr,':',port,'</font> ',IF(qualifyfreqok=-1,'UNREACHABLE',IF(qualifyfreqok>0,concat('(',qualifyfreqok,' ms)'),''))),defaultip) AS curip");
}
$HD_Form ->FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/telephone_add.png" ;





$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO ".strtoupper($HD_Form->FG_INSTANCE_NAME)." CREATED!"); 
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> CV_FILTER_ABOVE_TABLE_PARAM = "?friend_id=";
$HD_Form -> FG_LIMITE_DISPLAY = 24;

if (isset($friend_id)  &&  (strlen($friend_id)>0)) {
	if ($friend_id!='all') {
		if (strlen($HD_Form ->FG_TABLE_CLAUSE)>0) $HD_Form ->FG_TABLE_CLAUSE.=" AND ";
		$HD_Form ->FG_TABLE_CLAUSE.="name='$friend_id'";
	}
}



$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_ADDITION = true;

if ($form_action=="ask-add") {
	$card_gen = gen_card($HD_Form->FG_TABLE_NAME);
	$maxi = "value='".$card_gen."'";
	$acc = "value='Select ID CARD first'";
	$pass =  MDP_STRING(10);
	$pass_secret = "value='".$pass."'";
} else {
$maxi=$acc='';
}


if ($form_action=="ask-add" || $form_action == "add_sip" || $form_action == "add_iax") {
	$AMAFLAGS_default= "value='".$A2B->config["peer_friend"]['amaflag']."'";
	$CANREINVITE_default= "value=''";
	$DISALLOW_default= "value='all'";
	$ALLOW_default= "value='".$A2B->config["peer_friend"]['allow']."'";
	$HOST_default= "value='".$A2B->config["peer_friend"]['host']."'";
	$CONTEXT_default= "value='".$A2B->config["peer_friend"]['context']."'";
	$REGSECONDS_default= "value='0'";
	$CANCALLFORWARD_default= "value='yes'";
	$TRUNK_default= "value='yes'";
	$LANGUAGE_default= "value='".$A2B->config["global"]['base_language']."'";
	$RTPTIMEOUT_default= "value='60'";
	$RTPHOLDTIMEOUT_default= "value='300'";
	$RTPKEEPALIVE_default= "value='30'";
	$QUALIFY_default= "value='".$A2B->config["peer_friend"]['qualify']."'";
	$warning_threshold	='-2';
	$say_rateinitial = $external = $say_balance_after_call = $say_dialednumber = $say_timetotalk = '0';
	$type			= $A2B->config["peer_friend"]['type'];
	$directmedia		= 'update,nonat';
	$nat			= $A2B->config["peer_friend"]['nat'];
	$dtmfmode		= $A2B->config["peer_friend"]['dtmfmode'];
}

// Possible value to notify the user
$instance_config_table = new Table("cc_config", "id, config_value");
$QUERY = " config_key = 'values_notifications' ";
$return = null;
$return = $instance_config_table -> Get_list($DBHandle, $QUERY, 0);
$values = explode(":",$return[0]["config_value"]);
$limits_notify = array();
$idx = 0;
$limits_notify [$idx] = array(gettext("NEVER").'&nbsp;', -2);
foreach ($values as $val) {
	$idx++;
	$val_display = round($val,2);
	$limits_notify [$idx] = array($val_display,$val);
}

$HD_Form -> AddEditElement (gettext("ID CARD"),
						    "id_cc_card",
						    '',
						    "POPUPVALUE",
						    "readonly size=30 maxlength=50",
						    "4",
						    gettext("Insert the Id CardNumber"),
						    "", "", "", "", "",
						    "A2B_entity_card.php?popup_select=1&",
						    ", 'CardNumberSelection','width=590,height=550,top=100,left=100,scrollbars=1'",
						    gettext("Insert the card ID."));

$HD_Form -> AddEditElement (gettext("EXTERNAL"),
						    "external",
						    $external,
						    "SELECT",
						    "",
						    "",
						    "",
						    "list", "", "", "", $yesno, "%1", "", gettext("'Yes' for external line or 'No' for extension"));

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement (gettext("SAY BALANCE BEFORE CALL"),
						    "warning_threshold",
						    $warning_threshold,
						    "SELECT",
						    "",
						    "",
						    "",
						    "list", "", "", "", $limits_notify , "%1", "", gettext('currency : ').strtoupper(BASE_CURRENCY)." (".gettext("with the warning threshold").")", "", '<OPTION  value="-1">'.gettext("ALWAYS").'&nbsp;</OPTION>');

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
}

$HD_Form -> AddEditElement ("NAME",
						   "name",
						   "$value",
						   "INPUT",
						    "size=40 $maxi maxlength=40",
						   "9",
						   gettext("Insert the name"),
						   "", "", "", "", "", "", "", "", null, null, gettext("Device configuration as ").$atmenu.".conf");


$HD_Form -> AddEditElement (gettext("ACCOUNTCODE"),
                           "accountcode",
                           '',
                           "INPUT",
                           "size=20 $acc maxlength=50",
                           "",
                           gettext("Insert the accountcode"),
                           "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("REGEXTEN"),
                           "regexten",
                           '',
                           "INPUT",
                           "size=20 maxlength=20",
                           "",
                           gettext("Insert the regexten"),
                           "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("CALLERID"),
                           "callerid",
                           '',
                           "INPUT",
                           "size=40 maxlength=60",
                           "",
                           gettext("Insert the callerid"),
                           "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("CID NUMBER"),
                           "cid_number",
                           '',
                           "INPUT",
                           "size=40 maxlength=40",
                           "",
                           gettext("Insert the cid number"),
                           "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("AMAFLAGS"),
			   "amaflags",
			   '',
			   "INPUT",
			   "size=10 maxlength=7 $AMAFLAGS_default",
			   "",
			   gettext("Insert the amaflags"),
			   "", "", "", "", "", "", "NO-NULL", "(default : billing)" );


$HD_Form -> AddEditElement ("TYPE",
                           "type",
                           $type,
                           "SELECT",
                           "",
                           "",
                           gettext("Insert the type"),
                           "list", "", "", "", $values_list['type'], "%1", "NO-NULL" );

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement (gettext("DEFAULTUSER"),
                           "defaultuser",
                           '',
                           "INPUT",
                           "size=50 maxlength=40 $maxi",
                           "",
                           "",
                           "" , "", "", "", "", "", "NO-NULL" );
}

$HD_Form -> AddEditElement ("SECRET",
                           "secret",
                           '',
                           "INPUT",
                           "size=40 maxlength=40 $pass_secret",
                           "",
                           gettext("Insert the secret"),
                           "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("QUALIFY"),
                           "qualify",
                           '',
                           "INPUT",
                           "size=8 maxlength=7 $QUALIFY_default",
                           "",
                           gettext("Insert the qualify"),
                           "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("DEFAULTIP"),
			   "defaultip",
			   '',
			   "INPUT",
			   "size=30 maxlength=21",
			   "",
			   gettext("Insert the defaultip"),
			   "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("DENY"),
			   "deny",
			   '',
			   "INPUT",
			   "size=60 maxlength=50",
			   "",
			   gettext("Insert the deny"),
			   "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement (gettext("PERMIT"),
			   "permit",
			   '',
			   "INPUT",
			   "size=60 maxlength=50",
			   "",
			   gettext("Insert the permit"),
			   "", "", "", "", "", "", "NO-NULL" );

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement (gettext("DTMFMODE"),
                           "dtmfmode",
                           $dtmfmode,
                           "SELECT",
                           "",
                           "",
                           gettext("Select the dtmfmode"),
                           "list", "", "", "", $values_list['dtmfmode'], "%1", "NO-NULL" );
}

$HD_Form -> AddEditElement ("DISALLOW",
                           "disallow",
                           '',
                           "INPUT",
                           "size=50 maxlength=40 $DISALLOW_default",
                           "",
                           gettext("Insert the disallow"),
                           "", "", "", "", "", "", "NO-NULL", gettext("need to disallow=all before we can use allow. ( default : all )"));


$HD_Form -> AddEditElement ("ALLOW",
                           "allow",
                           '',
                           "INPUT",
                           "size=50 maxlength=40 $ALLOW_default",
                           "",
                           gettext("Insert the allow"),
                           "", "", "", "", "", "", "NO-NULL", gettext("Set allow codecs separated by a comma, e.g. gsm,alaw,ulaw ( default : ulaw,alaw,gsm,g729)"));

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement ("VIDEOSUPPORT",
						   "videosupport",
						   $videosupport,
						   "SELECT",
						   "",
						   "",
						   "",
						   "list", "", "", "", $values_list['videosupport'], "%1", "NO-NULL" );
}

$HD_Form -> AddEditElement ("HOST",
						   "host",
						   '',
						   "INPUT",
						   "size=40 maxlength=31 $HOST_default",
						   "",
						   gettext("Insert the host"),
						   "", "", "", "", "", "", "NO-NULL", gettext("Use dynamic or set an IP ( default : dynamic )"));


$HD_Form -> AddEditElement ("PORT",
						   "port",
						   '',
						   "INPUT",
						   "size=10 maxlength=5",
						   "",
						   gettext("Insert the port"),
						   "", "", "", "", "", "", "NO-NULL" );


$HD_Form -> AddEditElement ("CONTEXT",
						   "context",
						   '',
						   "INPUT",
						   "size=40 maxlength=40 $CONTEXT_default",
						   "",
						   gettext("Insert the context"),
						   "", "", "", "", "", "", "NO-NULL", gettext("set the context for this user (default : a2billing )"));


$HD_Form -> AddEditElement ("LANGUAGE",
						   "language",
						   '',
						   "INPUT",
						   "size=10 maxlength=4 $LANGUAGE_default",
						   "",
						   gettext("Insert the language"),
						   "", "", "", "", "", "", "NO-NULL" );

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement (gettext("SUBSCRIBECONTEXT"),
						   "subscribecontext",
						   '',
						   "INPUT",
						   "size=40 maxlength=40",
						   "",
						   "",
						   "", "", "", "", "", "", "NO-NULL" );


	$HD_Form -> AddEditElement (gettext("MAILBOX"),
						   "mailbox",
						   '',
						   "INPUT",
						   "size=60 maxlength=50",
						   "",
						   gettext("Insert the mailbox"),
						   "" , "", "", "", "", "", "NO-NULL" );


	$HD_Form -> AddEditElement (gettext("FROMUSER"),
						   "fromuser",
						   '',
						   "INPUT",
						   "size=40 maxlength=40",
						   "",
						   gettext("Insert the fromuser"),
						   "" , "", "", "", "", "", "NO-NULL" );


	$HD_Form -> AddEditElement (gettext("FROMDOMAIN"),
						   "fromdomain",
						   '',
						   "INPUT",
						   "size=40 maxlength=40",
						   "",
						   gettext("Insert the fromdomain"),
						   "" , "", "", "", "", "", "NO-NULL" );


	$HD_Form -> AddEditElement (gettext("INSECURE"),
						   "insecure",
						   '',
						   "INPUT",
						   "size=20 maxlength=30",
						   "",
						   gettext("Insert the insecure"),
						   "" , "", "", "", "", "", "NO-NULL" );
}

/**
$HD_Form -> AddEditElement (gettext("REGSECONDS"),	
						   "regseconds",		
						   '4',	
						   "INPUT",	
						   "size=50 maxlength=40 $REGSECONDS_default",	
						   "",	
						   gettext("Insert the regseconds"),
						   "", "", "", "", "", "", "NO-NULL" );

$HD_Form -> AddEditElement (gettext("IPADDR"),
						   "ipaddr",		
						   '',	
						   "INPUT",	
						   "size=20 maxlength=15",	
						   "",	
						   gettext("Insert the ipaddr"),
						   "", "", "", "", "", "", "NO-NULL" );

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement (gettext("REGSERVER"),
						   "regserver",
						   '',
						   "INPUT",
						   "size=20 maxlength=20",
						   "",
						   "",
						   "", "", "", "", "", "", "NO-NULL" );
}
**/

$HD_Form -> AddEditElement (gettext("AUTH"),
						   "auth",		
						   '',	
						   "INPUT",	
						   "size=50 maxlength=50",	
						   "",	
						   gettext("Insert the auth"),
						   "", "", "", "", "", "", "NO-NULL" );

if ($atmenu=='sip') {
	$HD_Form -> AddEditElement (gettext("CALLGROUP"),
						   "callgroup",
						   '',
						   "INPUT",
						   "size=10 maxlength=10",
						   "",
						   gettext("Insert the callgroup"),
						   "", "", "", "", "", "", "NO-NULL" );


	$HD_Form -> AddEditElement (gettext("PICKUPGROUP"),
						   "pickupgroup",
						   '',
						   "INPUT",
						   "size=15 maxlength=10",
						   "",
						   gettext("Insert the pickupgroup"),
						   "", "", "", "", "", "", "NO-NULL" );

	$HD_Form -> AddEditElement (gettext("NAMEDCALLGROUP"),
						   "namedcallgroup",
						   '',
						   "INPUT",
						   "size=40 maxlength=40",
						   "",
						   gettext("Insert the named callgroup"),
						   "", "", "", "", "", "", "NO-NULL" );


	$HD_Form -> AddEditElement (gettext("NAMEDPICKUPGROUP"),
						   "namedpickupgroup",
						   '',
						   "INPUT",
						   "size=40 maxlength=40",
						   "",
						   gettext("Insert the named pickupgroup"),
						   "", "", "", "", "", "", "NO-NULL" );
}

$HD_Form -> AddEditElement (gettext("MOHSUGGEST"),
						   "mohsuggest",		
						   '',	
						   "INPUT",	
						   "size=20 maxlength=20",	
						   "",	
						   gettext("Insert the mohsuggest"),
						   "", "", "", "", "", "", "NO-NULL" );

$HD_Form -> AddEditElement (gettext("SETVAR"),
						   "setvar",		
						   '',	
						   "INPUT",	
						   "size=60 maxlength=200",	
						   "",	
						   gettext("Insert the setvar"),
						   "", "", "", "", "", "", "NO-NULL" );

$HD_Form -> AddEditElement (gettext("AVPF"),
						   "avpf",
						   $avpf,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['avpf'], "%1", "NO-NULL", gettext("Inter-operability with media streams using the AVPF RTP profile. Typically used for WebRTC technology (e.g. <a href=\"https://chrome.google.com/webstore/detail/webrtc-sip-phone-with-cli/pofkcckikkdhiieipefhkaelgnbdkcib\">WebRTC SIP Phone</a> for web browser)."));

$HD_Form -> AddEditElement (gettext("FORCE_AVP"),
						   "force_avp",
						   $force_avp,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['force_avp'], "%1", "NO-NULL", gettext("Force 'RTP/AVP', 'RTP/AVPF', 'RTP/SAVP', and 'RTP/SAVPF' to be used for media streams when appropriate, even if a DTLS stream is present"));

$HD_Form -> AddEditElement (gettext("ICESUPPORT"),
						   "icesupport",
						   $icesupport,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['icesupport'], "%1", "NO-NULL", gettext("ICE/STUN/TURN usage"));

$HD_Form -> AddEditElement (gettext("dtlsenable"),
						   "dtlsenable",
						   $dtlsenable,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['dtlsenable'], "%1", "NO-NULL", gettext("Tell Asterisk to enable DTLS for this peer"));

$HD_Form -> AddEditElement (gettext("dtlsverify"),
						   "dtlsverify",
						   $dtlsverify,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['dtlsverify'], "%1", "NO-NULL", gettext("Tell Asterisk to not verify your DTLS certs"));

$HD_Form -> AddEditElement (gettext("dtlscertfile"),
						"dtlscertfile",
						'',
						"INPUT",
						"size=81 maxlength=80",
						"",
						"",
						"" , "", "", "", "", "", "NO-NULL", gettext("Tell Asterisk where your DTLS cert file is"));

$HD_Form -> AddEditElement (gettext("dtlsprivatekey"),
						"dtlsprivatekey",
						'',
						"INPUT",
						"size=81 maxlength=80",
						"",
						"",
						"" , "", "", "", "", "", "NO-NULL", gettext("Tell Asterisk where your DTLS private key is"));

$HD_Form -> AddEditElement (gettext("dtlscafile"),
						"dtlscafile",
						'',
						"INPUT",
						"size=81 maxlength=80",
						"",
						"",
						"" , "", "", "", "", "", "NO-NULL", gettext("Path to certificate authority certificate"));

$HD_Form -> AddEditElement (gettext("dtlssetup"),
						   "dtlssetup",
						   $dtlssetup,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['dtlssetup'], "%1", "NO-NULL", gettext("Tell Asterisk to use actpass SDP parameter when setting up DTLS"));

$HD_Form -> AddEditElement (gettext("RTCP_MUX"),
						   "rtcp_mux",
						   $rtcp_mux,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['rtcp_mux'], "%1", "NO-NULL", gettext("Tell Asterisk to do RTCP mux"));

$HD_Form -> AddEditElement (gettext("ENCRYPTION"),
						   "encryption",
						   $encryption,
						   "SELECT",
						   "",
						   "",
						   "",
						    "list", "", "", "", $values_list['encryption'], "%1", "NO-NULL" );

if ($atmenu=='sip') {

	$HD_Form -> AddEditElement (gettext("IGNORECRYPTOLIFETIME"),
	                           "ignorecryptolifetime",
	                           $ignorecryptolifetime,
	                           "SELECT",
	                           "",
	                           "",
	                           "",
	                           "list", "", "", "", $values_list['ignorecryptolifetime'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("TRANSPORT"),
	                           "transport",
	                           $transport,
	                           "SELECT",
	                           "",
	                           "",
	                           "",
	                           "list", "", "", "", $values_list['transport'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("T38PT_UDPTL"),
	                           "t38pt_udptl",
	                           $t38pt_udptl,
	                           "SELECT",
	                           "",
	                           "",
	                           "",
	                           "list", "", "", "", $values_list['t38pt_udptl'], "%1", "NO-NULL" );
}

	$HD_Form -> AddEditElement (gettext("USERNAME"),
	                           "username",
	                           '',
	                           "INPUT",
	                           "size=50 maxlength=40",
	                           "",
	                           gettext("Insert the username"),
	                           "" , "", "", "", "", "", "NO-NULL", gettext("The 'USERNAME' field for sip peers has been deprecated in favor of the term 'DEFAULTUSER'") );

if ($atmenu=='sip') {

	$HD_Form -> AddEditElement (gettext("MD5SECRET"),
	                           "md5secret",
	                           '',
	                           "INPUT",
	                           "size=60 maxlength=50",
	                           "",
	                           gettext("Insert the md5secret"),
	                           "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("DIRECTMEDIA"),
	                           "directmedia",
	                           $directmedia,
	                           "SELECT",
	                           "",
	                           "",
	                           "",
	                           "list", "", "", "", $values_list['directmedia'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("NAT"),
	                           "nat",
	                           $nat,
	                           "SELECT",
	                           "",
	                           "",
	                           "",
	                           "list", "", "", "", $values_list['nat'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("CANREINVITE"),
	                           "canreinvite",
	                           '',
	                           "INPUT",
	                           "size=20 maxlength=10 $CANREINVITE_default",
	                           "",
	                           gettext("Insert the canreinvite"),
	                           "", "", "", "", "", "", "NO-NULL", gettext("canreinvite : yes | no ( default : yes )"));
	
	
	$HD_Form -> AddEditElement (gettext("MASK"),
							   "mask",		
							   '',	
							   "INPUT",	
							   "size=60 maxlength=50",	
							   "",	
							   gettext("Insert the mask"),
							   "" , "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("RESTRICTCID"),
							   "restrictcid",		
							   '',	
							   "INPUT",	
							   "size=7 maxlength=1",	
							   "",	
							   gettext("Insert the restrictcid"),
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("RTPKEEPALIVE"),
							   "rtpkeepalive",		
							   '',	
							   "INPUT",	
							   "size=15 maxlength=15 $RTPKEEPALIVE_default",
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("RTPTIMEOUT"),
							   "rtptimeout",		
							   '',	
							   "INPUT",	
							   "size=7 maxlength=3 $RTPTIMEOUT_default",
							   "",	
							   gettext("Insert the rtptimeout"),
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("RTPHOLDTIMEOUT"),
							   "rtpholdtimeout",		
							   '',	
							   "INPUT",	
							   "size=7 maxlength=3 $RTPHOLDTIMEOUT_default",
							   "",	
							   gettext("Insert the rtpholdtimeout"),
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("MUSICONHOLD"),
							   "musiconhold",		
							   '',	
							   "INPUT",	
							   "size=50 maxlength=40",	
							   "",	
							   gettext("Insert the musiconhold"),
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("ALLOWTRANSFER"),
							   "allowtransfer",
							   $allowtransfer,
							   "SELECT",
							   "",
							   "",
							   "",
							   "list", "", "", "", $values_list['allowtransfer'], "%1", "NO-NULL", gettext("allowtransfer = yes | no ( default : yes )") );
	
/**
	$HD_Form -> AddEditElement (gettext("CANCALLFORWARD"),
							   "cancallforward",		
							   '',	
							   "INPUT",	
							   "size=7 maxlength=3 $CANCALLFORWARD_default",	
							   "",	
							   gettext("Insert the cancallforward"),
							   "", "", "", "", "", "", "NO-NULL", gettext("cancallforward = yes | no ( default : yes )"));
**/
	
	$HD_Form -> AddEditElement (gettext("SUBSCRIBEMWI"),
							   "subscribemwi",
							   $subscribemwi,
							   "SELECT",
							   "",
							   "",
							   "",
							   "list", "", "", "", $values_list['subscribemwi'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("VMEXTEN"),
							   "vmexten",		
							   '',	
							   "INPUT",	
							   "size=20 maxlength=20",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("CALLINGPRES"),
							   "callingpres",
							   $callingpres,
							   "SELECT",
							   "",
							   "",
							   "",
							   "list", "", "", "", $values_list['callingpres'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("USEREQPHONE"),
							   "usereqphone",
							   $usereqphone,
							   "SELECT",
							   "",
							   "",
							   "",
							   "list", "", "", "", $values_list['usereqphone'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement ("CALLCOUNTER",
							   "callcounter",
							   $callcounter,
							   "SELECT",
							   "",
							   "",
							   "",
							   "list", "", "", "", $values_list['callcounter'], "%1", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement ("CALL-LIMIT",
							   "`call-limit`",
							   '',
							   "INPUT",
							   "size=20 maxlength=11",
							   "4",
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("MUSICCLASS"),
							   "musicclass",		
							   '',	
							   "INPUT",	
							   "size=20 maxlength=20",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("AUTOFRAMING"),
							   "autoframing",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("MAXCALLBITRATE"),
							   "maxcallbitrate",		
							   '',	
							   "INPUT",	
							   "size=15 maxlength=15",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );

	$HD_Form -> AddEditElement (gettext("OUTBOUNDPROXY"),
							   "outboundproxy",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );

	$HD_Form -> AddEditElement (gettext("CALLBACKEXTENSION"),
							   "callbackextension",
							   '',
							   "INPUT",
							   "size=16 maxlength=16",
							   "",
							   "",
							   "", "", "", "", "", "", "NO-NULL" );

} else {

	$HD_Form -> AddEditElement (gettext("FORCEENCRYPTION"),
							   "forceencryption",
							   '',
							   "INPUT",
							   "size=20 maxlength=20",
							   "",
							   "",
							   "", "", "", "", "", "", "NO-NULL" );

	$HD_Form -> AddEditElement (gettext("TRUNK"),
							   "trunk",		
							   '',	
							   "INPUT",
							   "size=7 maxlength=3 $TRUNK_default",	
							   "",	
							   gettext("Define the iax trunking"),
							   "", "", "", "", "", "", "NO-NULL", gettext("iax trunking = yes | no ( default : yes )"));
						   
	
	$HD_Form -> AddEditElement (gettext("DBSECRET"),
							   "dbsecret",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("REGCONTEXT"),
							   "regcontext",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("SOURCEADDRESS"),
							   "sourceaddress",		
							   '',	
							   "INPUT",	
							   "size=20 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	$HD_Form -> AddEditElement (gettext("MOHINTERPRET"),
							   "mohinterpret",		
							   '',	
							   "INPUT",	
							   "size=20 maxlength=20",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("INKEYS"),
							   "inkeys",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("OUTKEY"),
							   "outkey",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	$HD_Form -> AddEditElement (gettext("SENDANI"),
							   "sendani",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	$HD_Form -> AddEditElement (gettext("FULLNAME"),
							   "fullname",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("MAXAUTHREQ"),
							   "maxauthreq",		
							   '',	
							   "INPUT",	
							   "size=15 maxlength=15",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("TRANSFER"),
							   "transfer",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("JITTERBUFFER"),
							   "jitterbuffer",
							   '',
							   "INPUT",
							   "size=10 maxlength=10",
							   "",
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("FORCEJITTERBUFFER"),
							   "forcejitterbuffer",
							   '',
							   "INPUT",
							   "size=10 maxlength=10",
							   "",
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("CODECPRIORITY"),
							   "codecpriority",		
							   '',	
							   "INPUT",	
							   "size=40 maxlength=40",	
							   "",
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("QUALIFYSMOOTHING"),
							   "qualifysmoothing",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("QUALIFYFREQOK"),
							   "qualifyfreqok",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("QUALIFYFREQNOTOK"),
							   "qualifyfreqnotok",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("TIMEZONE"),
							   "timezone",		
							   '',	
							   "INPUT",	
							   "size=20 maxlength=20",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );
	
	
	$HD_Form -> AddEditElement (gettext("ADSI"),
							   "adsi",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL" );

	
	$HD_Form -> AddEditElement (gettext("REQUITE CALL TOKEN"),
							   "requirecalltoken",		
							   '',	
							   "INPUT",	
							   "size=15 maxlength=20",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL", gettext("No supported by Realtime") );
							   
	
	$HD_Form -> AddEditElement (gettext("MAX CALL NUMBERS"),
							   "maxcallnumbers",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL", gettext("No supported by Realtime") );
							   
	
	$HD_Form -> AddEditElement (gettext("MAX CALL NUMBERS NON VALIDATED"),
							   "maxcallnumbers_nonvalidated",		
							   '',	
							   "INPUT",	
							   "size=10 maxlength=10",	
							   "",	
							   "",
							   "", "", "", "", "", "", "NO-NULL", gettext("No supported by Realtime") );
}


if ($atmenu=='sip') {
	$field_list_edit = 'id_cc_card, external, warning_threshold, say_dialednumber, say_rateinitial, say_timetotalk, say_balance_after_call, name, accountcode, regexten, callerid, cid_number, amaflags, ' .
			'type, defaultuser, secret, qualify, defaultip, deny, permit, dtmfmode, disallow, allow, videosupport, host, port, context, language, subscribecontext, mailbox, fromuser, fromdomain, ' .
			'insecure, auth, callgroup, pickupgroup, namedcallgroup, namedpickupgroup, mohsuggest, setvar, ' .
			'avpf, force_avp, icesupport, dtlsenable, dtlsverify, dtlscertfile, dtlsprivatekey, dtlscafile, dtlssetup, rtcp_mux, encryption, ignorecryptolifetime, transport, t38pt_udptl, ' .
			'username, md5secret, directmedia, nat, canreinvite, mask, restrictcid, rtpkeepalive, rtptimeout, rtpholdtimeout, musiconhold, allowtransfer, subscribemwi, vmexten, callingpres, ' .
			'usereqphone, callcounter, `call-limit`, musicclass, autoframing, maxcallbitrate, outboundproxy, callbackextension';
} else {
	$field_list_edit = 'id_cc_card, external, name, accountcode, regexten, callerid, cid_number, amaflags, type, secret, qualify, defaultip, deny, permit, disallow, allow, host, port, context, language, ' .
			'auth, mohsuggest, setvar, avpf, force_avp, icesupport, dtlsenable, dtlsverify, dtlscertfile, dtlsprivatekey, dtlscafile, dtlssetup, rtcp_mux, encryption, username, forceencryption, trunk, ' .
			'dbsecret, regcontext, sourceaddress, mohinterpret, inkeys, outkey,  sendani, fullname,  maxauthreq, transfer, ' .
			'jitterbuffer, forcejitterbuffer, codecpriority, qualifysmoothing, qualifyfreqok, qualifyfreqnotok, timezone, adsi, requirecalltoken, maxcallnumbers, maxcallnumbers_nonvalidated';
}


$HD_Form -> FieldEditElement ($field_list_edit);

$HD_Form -> FG_ADITION_HIDDEN_PARAM		= $HD_Form -> FG_EDITION_HIDDEN_PARAM		= 'filterprefix, filterprefix2, cbe';
$HD_Form -> FG_ADITION_HIDDEN_PARAM_VALUE	= $HD_Form -> FG_EDITION_HIDDEN_PARAM_VALUE	= $filterprefix.",".$filterprefix2.",".$cbe;

//$HD_Form -> FG_SPLITABLE_FIELD = 'accountcode';

$HD_Form -> FG_INTRO_TEXT_EDITION= '';
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new").$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");


$HD_Form -> FG_INTRO_TEXT_ADITION = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted. <br>");


$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Click 'Confirm Data' to continue");

$HD_Form -> FG_ADDITIONAL_FUNCTION_AFTER_DELETE = $HD_Form->FG_ADDITIONAL_FUNCTION_AFTER_EDITION = 'processing_sipiax_generate_by_admin';

$HD_Form -> FG_FILTER_APPLY = true;
$HD_Form -> FG_FILTERFIELD = 'accountcode';
$HD_Form -> FG_FILTERFIELDNAME = 'accountcode';
$HD_Form -> FG_FILTER_FORM_ACTION = 'list';

if (isset($filterprefix)  &&  (strlen($filterprefix)>0)){
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="accountcode like '$filterprefix%'";
}

$HD_Form -> FG_FILTER_APPLY2 = true;
$HD_Form -> FG_FILTERFIELD2 = 'name';
$HD_Form -> FG_FILTERFIELDNAME2 = 'NAME';
$HD_Form -> FG_FILTER_FORM_ACTION2 = 'list';

if (isset($filterprefix2)  &&  (strlen($filterprefix2)>0)){
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE .= "name like '$filterprefix2%'";
}


$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $_SERVER['PHP_SELF']."?atmenu=$atmenu&stitle=Document&wh=AC&filterprefix=$filterprefix&filterprefix2=$filterprefix2&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $_SERVER['PHP_SELF']."?atmenu=$atmenu&stitle=Document&wh=AC&filterprefix=$filterprefix&filterprefix2=$filterprefix2&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $_SERVER['PHP_SELF']."?atmenu=$atmenu&stitle=Document&wh=AC&filterprefix=$filterprefix&filterprefix2=$filterprefix2&id=";
