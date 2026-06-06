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



getpost_ifset(array('id', 'cbe'));

$atmenu = ($atmenu=='IAX')?$atmenu:'SIP';
if (!isset($form_action) || $form_action=="list"){
	$HD_Form = new FormHandler("cc_".strtolower($atmenu)."_buddies LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id LEFT JOIN ( SELECT concat_id, foreignvoipconf FROM cc_card_concat WHERE concat_card_id = ".$_SESSION["card_id"]." ) AS v ON bb.concat_id = v.concat_id", gettext($atmenu . " Config"));
}else {
	$HD_Form = new FormHandler("cc_".strtolower($atmenu)."_buddies", gettext($atmenu . " Config"));
}

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_ID = "cc_".strtolower($atmenu)."_buddies.id";
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "server DESC, regexten";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

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


//$HD_Form -> FG__DELETION_LINK = $_SERVER['PHP_SELF']."?form_action=ask-delete&atmenu=$atmenu&regexten=|$FN_COLUMN_REGEXTEN|&id=";

$values_list['nat'] = array(array(gettext('yes'),'force_rport,comedia'),array(gettext('no'),'no'),array(null,null));
$values_list['encryption'] = array(array(gettext('yes'),'yes'),array(gettext('no'),'no'),array(null,null));
$values_list['avpf'] = array(array(gettext('no'),'no'),array(gettext('yes'),'yes'));
$yesno = Constants::getYesNoList();
// Possible value to notify the user
$instance_config_table = new Table("cc_config", "id, config_value");
$QUERY = " config_key = 'values_notifications' ";
//$return = null;
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

if ($atmenu == 'sip' && $form_action=="ask-edit" && isset($id)) {
	$cbe_table = new Table('cc_sip_buddies','callbackextension');
	$cbe_list = $cbe_table -> Get_list ($DBHandle, "id='$id'", null, null, null, null);
	if (is_array($cbe_list) && count($cbe_list)>0) {
		$cbe = (!is_null($cbe_list[0][0])) ? 1 : 0;
	}
}

$HD_Form -> AddViewElement(gettext("EXTENSION"), "regexten", "10%", "center", "sort", "25");
$HD_Form -> AddViewElement(gettext("CALLERID NAME"), "callerid", "22%", "center", "sort");
$HD_Form -> AddViewElement(gettext("LANGUAGE"), "language", "5%", "center", "sort");
$HD_Form -> AddViewElement(gettext("HOST"), "host", "25%", "center", "sort");
/**
if ($atmenu!='IAX') {
    $HD_Form -> AddViewElement(gettext("CALLGROUP"), "callgroup", "5%", "center", "sort");
    $HD_Form -> AddViewElement(gettext("PICKUPGROUP"), "pickupgroup", "5%", "center", "sort");
}
**/
$HD_Form -> AddViewElement($atmenu . " URI", "server", "11%", "center", "sort");
$HD_Form -> AddViewElement(gettext("REGNAME"), "name", "8%", "center", "sort");
$HD_Form -> AddViewElement(gettext("SECRET"), "secret", "8%", "center", "sort");

//$HD_Form -> FieldViewElement ("IF(regexten='',name,regexten) AS regexten, callerid, language, host, ".(($atmenu!='IAX')?"callgroup, pickupgroup, ":"")."'".SIP_IAX_INFO_HOST."', name, secret ");
//$HD_Form -> FieldViewElement ("IF(regexten='',name,regexten) AS regexten, callerid, IF(id_cc_card = ".$_SESSION["card_id"].",language,NULL) AS language, IF(id_cc_card = ".$_SESSION["card_id"].",IF(port>0 AND UNIX_TIMESTAMP()<regseconds,concat('<font color=green>',ipaddr,':',port,'</font>'),host),IF(port>0 AND UNIX_TIMESTAMP()<regseconds,'<font color=green>ONLINE</font>','offline')) AS host, IF(id_cc_card = ".$_SESSION["card_id"].",'".SIP_IAX_INFO_HOST."',NULL) AS server, IF(id_cc_card = ".$_SESSION["card_id"].",name,NULL) AS name, IF(id_cc_card = ".$_SESSION["card_id"].",secret,NULL) AS secret ");
$HD_Form -> FieldViewElement ("IF(regexten='',name,regexten) AS regexten, callerid, IF(id_cc_card = ".$_SESSION["card_id"].",language,NULL) AS language, IF(id_cc_card = ".$_SESSION["card_id"]." OR v.foreignvoipconf, IF(port>0 AND UNIX_TIMESTAMP()<regseconds,concat('<font color=green>',ipaddr,':',port,'</font> ',IF(lastms=-1,'UNREACHABLE',IF(lastms>0,concat('(',lastms,' ms)'),'')),'<br>',useragent),host),IF(port>0 AND UNIX_TIMESTAMP()<regseconds,'<font color=green>ONLINE</font>','offline')) AS host, IF(id_cc_card = ".$_SESSION["card_id"].",'".SIP_IAX_INFO_HOST."',NULL) AS server, name, IF(id_cc_card = ".$_SESSION["card_id"].",secret,NULL) AS secret ");
//$HD_Form -> FieldViewElement ("IF(regexten='',name,regexten) AS regexten, callerid, IF(id_cc_card = ".$_SESSION["card_id"].",language,NULL) AS language, IF(port>0 AND UNIX_TIMESTAMP()<regseconds,concat('<font color=green>',ipaddr,':',port,'</font>'),host) AS host, IF(id_cc_card = ".$_SESSION["card_id"].",'".SIP_IAX_INFO_HOST."',NULL) AS server, IF(id_cc_card = ".$_SESSION["card_id"].",name,NULL) AS name, IF(id_cc_card = ".$_SESSION["card_id"].",secret,NULL) AS secret ");

$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO ".$atmenu." CONFIG FOUNDED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 24;
$HD_Form -> FG_OTHER_BUTTON1 = false;

if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
    $HD_Form -> FG_TABLE_CLAUSE.=" AND ";

$HD_Form -> FG_TABLE_CLAUSE.="(id_cc_card = ".$_SESSION["card_id"]." OR v.concat_id IS NOT NULL) AND external = 0";
/**
if (strlen($HD_Form -> FG_EDITION_CLAUSE)>0)
    $HD_Form -> FG_EDITION_CLAUSE.=" AND ";

$HD_Form -> FG_DELETION_CLAUSE = "id_cc_card = ".$_SESSION["card_id"]." AND external = 0";

$HD_Form -> FG_EDITION_CLAUSE .= $HD_Form -> FG_DELETION_CLAUSE;
**/
$HD_Form -> FG_EDITION_CONDITION = '"|col6|" != ""';
$HD_Form -> FG_DELETION_CONDITION = $HD_Form -> FG_EDITION_CONDITION;
//$HD_Form -> FG_DELETION_CONDITION = '"|col6|" != "" && "|col5|" != "{$_SESSION["user_alias"]}"';

$HD_Form -> FG_CAPCHA_POPUP = '|col0| | |col1|';
$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_ADDITION = false;
$HD_Form -> FG_VIEW_TABLE_WITDH = '80%';

$HD_Form -> AddEditElement (gettext("EXTENSION "),
					"regexten",
					$exten_include,
					"INPUT",
					"size=50 maxlength=$extlen",
					regex_range($extmin,$extmax,$exten_include),
					gettext("Enter right number from range below exclude afore existing numbers"),
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
					"size=50 maxlength=20",
					"26",
					gettext("THE PASSWORD SHOULD CONSIST OF NUMBERS AND LETTERS"),
					"", "", "", "", "", "", "", gettext("Between 8-20 characters, include Latin letters and numbers"));

$HD_Form -> AddEditElement (gettext("CALLERID NAME"),
					"callerid",
					"",
					"INPUT",
					"size=50 maxlength=40",
					"",
					"",
					"", "", "", "", "", "", "NO-NULL", gettext("Allows you to set the Name of the caller"));

$HD_Form -> AddEditElement (gettext("WebRTC"),
                                        "avpf",
                                        $avpf,
                                        "SELECT",
                                        "",
                                        "",
                                        "",
                                        "list", "", "", "", $values_list['avpf'], "%1", "NO-NULL", gettext("Is this extension for WebRTC tech? (like <a href=\"https://chrome.google.com/webstore/detail/webrtc-sip-phone-with-cli/pofkcckikkdhiieipefhkaelgnbdkcib\">WebRTC SIP Phone</a> for web browser)"));

$HD_Form -> AddEditElement (gettext("NAT"),
					"nat",
					"",
					"SELECT",
					"",
					"",
					"",
					"list", "", "", "", $values_list['nat'], "%1", "NO-NULL", gettext("Is this extension behind the nat?"));

$HD_Form -> AddEditElement (gettext("ENCRYPTION"),
					"encryption",
					"",
					"SELECT",
					"",
					"",
					"",
					"list", "", "", "", $values_list['encryption'], "%1", "NO-NULL", gettext("Whether to offer SRTP encrypted media on calls to you?"));

$HD_Form -> AddEditElement (gettext("SAY BALANCE AFTER AUTH"),
                                        "warning_threshold",
                                        '',
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
                                        '0',
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
                                        '0',
                                        "SELECT",
                                        "",
                                        "",
                                        "",
                                        "list", "", "", "", $yesno, "%1");

$field_list_edit = 'regexten, name, secret, callerid, avpf, nat, encryption, warning_threshold, say_dialednumber, say_rateinitial, say_timetotalk, say_balance_after_call';

$HD_Form -> FieldEditElement ($field_list_edit);

$HD_Form -> FG_ADITION_HIDDEN_PARAM		= $HD_Form -> FG_EDITION_HIDDEN_PARAM		= 'filterprefix, cbe';
$HD_Form -> FG_ADITION_HIDDEN_PARAM_VALUE	= $HD_Form -> FG_EDITION_HIDDEN_PARAM_VALUE	= $filterprefix.",".$cbe;

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
