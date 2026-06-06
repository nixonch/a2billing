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


include ("lib/customer.defines.php");
include ("lib/customer.module.access.php");
include ("lib/customer.smarty.php");


if (! has_rights (ACX_CALL_HISTORY)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error?c=accessdenied");
	die();
}

getpost_ifset(array('id', 'posted', 'Period', 'frommonth', 'fromstatsmonth', 'tomonth', 'tostatsmonth', 'fromday', 'fromstatsday_sday', 'fromstatsmonth_sday', 'today', 'tostatsday_sday', 'tostatsmonth_sday', 'fromtime', 'totime', 'fromstatsday_hour', 'tostatsday_hour', 'fromstatsday_min', 'tostatsday_min', 'calleridtype', 'phonenumbertype', 'sourcetype', 'clidtype', 'channel', 'resulttype', 'stitle', 'atmenu', 'current_page', 'order', 'sens', 'callerid', 'phonenumber', 'clid', 'choose_currency', 'terminatecauseid', 'choose_calltype', 'download', 'file', 'choose_callowner', 'waitup1', 'waitup2', 'waitup1type', 'waitup2type'));

$cl = 2;
$cr = 3;
function bgcolor($c) {
	global $cl,$cr;
	if ($c) {
		$ret = $cr = $cr==3?5:3;
	} else {
		$ret = $cl = $cl==2?4:2;
	}
	echo "class=\"bgcolor_00{$ret}\"";
}

$customer = $_SESSION["card_id"];

$DBHandle_max = DbConnect();
$instance_table = new Table("cc_card","lastname, firstname, username, credit");

if (($download == "file") && $file && $ACXSEERECORDING && !$accdie) {

	if (strpos($file, '/') !== false) exit;

	$value_de = base64_decode ( $file );
	$parts = pathinfo($value_de);
	$value = $parts['filename'];
	$QUERY = "SELECT YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime), cc_card.username, cc_card.id FROM cc_call
".			"LEFT JOIN cc_card ON cc_card.id=card_id
".			"WHERE uniqueid LIKE '$value' AND (cc_card.id=$customer OR cc_card.id IN
".				"(SELECT aa.concat_card_id FROM cc_card_concat aa LEFT JOIN cc_card_concat bb ON bb.concat_id=aa.concat_id WHERE bb.concat_card_id=$customer AND bb.foreignrecords=1 AND aa.myrecords=1))
".			"ORDER BY cc_call.id DESC LIMIT 1";
	$DBHandle_max -> Execute("SET @@session.time_zone=@@global.time_zone");
	$result = $instance_table -> SQLExec ($DBHandle_max, $QUERY);
	if (is_array($result) && count($result)>0) {

	    $dl_full = $dl_path_name = MONITOR_PATH . "/" . $result[0][3] . "/" . $result[0][0] . "/" . $result[0][1] . "/" . $result[0][2] . "/" . $value_de;

	    if (! file_exists ( $dl_full )) {
		echo gettext ( "ERROR: Cannot download file " . $dl_name . ", it does not exist.<br>" );
		exit ();
	    }

	    $dl_name = $value_de;
	    if ($sens!=1 && $parts['extension']=='WAV') {
		$dl_path_name = "/tmp/".$value_de;
		$sox = "/usr/bin/sox ".$dl_full." -c 1 -r 8k -e signed-integer ".$dl_path_name;
		exec($sox);
	    }

	    header ( "Content-Type: audio/wav" );
	    header ( "Content-Disposition: attachment; filename=$dl_name" );
	    header ( "Content-Length: " . filesize ( $dl_path_name ) );
	    header ( "Accept-Ranges: bytes" );
	    header ( "Pragma: no-cache" );
	    header ( "Expires: 0" );
	    header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	    header ( "Content-transfer-encoding: binary" );

	    @readfile ($dl_path_name);
	    if ($sens!=1 && $parts['extension']=='WAV') unlink( $dl_path_name );
	}
	exit ();
}

$QUERY = "SELECT username, credit, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, lastuse, activated, status, currency, root_manager, foreignvoipconf, foreignlogs, concat_id, showcallstypedefault, foreignrecords
".		"FROM cc_card LEFT JOIN cc_card_concat ON concat_card_id=id
".		"WHERE username = '".$_SESSION["pr_login"]."' AND uipass = '".$_SESSION["pr_password"]."'";

$numrow = 0;
$resmax = $DBHandle_max -> Execute($QUERY);
if ($resmax)
	$numrow 	= $resmax -> RecordCount();

if ($numrow == 0) exit();
	$customer_info	= $resmax -> fetchRow();

if ($customer_info[14] != "1" && $customer_info[14] != "8") {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error?c=accessdenied");
	die();
}

$dialstatus_list= Constants::getDialStatusList();
$list_calltype	= Constants::getListCallType();
$calltype_list	= Constants::getCallTypeList();

if (!isset($choose_callowner)	|| !is_numeric($choose_callowner))	$choose_callowner	= $customer_info[20];// >=0 ? $customer_info[20] : 0;
if (!isset($current_page)	|| !is_numeric($current_page	))	$current_page		= 0;

if (!$customer_info[18]) {
	unset($calltype_list[1]);
	unset($calltype_list[2]);
	$concat_id = "";
	if ($choose_callowner == 1 || $choose_callowner == 2)		$choose_callowner	= 0;
} else {
	$concat_id = $customer_info[19];
	$resmax = $DBHandle_max -> Execute("SELECT CONCAT_WS(' ',lastname,firstname,IF(company_name='','',CONCAT('(',company_name,')'))) customer, -id FROM cc_card LEFT JOIN cc_card_concat ON concat_card_id=id WHERE concat_id=$concat_id AND id<>$customer");
	if ($resmax && $resmax -> RecordCount()) {
		foreach ($resmax as $val) {
			$calltype_list[] = array_intersect_key($val,array('0'=>0,'1'=>1));
		}
	}
}

if (!has_rights(ACX_DISTRIBUTION) || !isset($id) || !is_numeric($id))	{
	unset($calltype_list[3]);
	unset($calltype_list[4]);
	if ($choose_callowner == 3 || $choose_callowner == 4)		$choose_callowner	= 0;
}

if (0 <= $choose_callowner && $choose_callowner <= 2)	$instance_table -> Update_table($DBHandle_max, "showcallstypedefault = '$choose_callowner'", "username = '".$_SESSION["pr_login"]."' AND uipass = '".$_SESSION["pr_password"]."'");

$FG_DEBUG = 0;
// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#FFFFFF";
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#F2F8FF";

$DBHandle  = DbConnect();

$FG_TABLE_DEFAULT_ORDER = "t1.starttime";
$FG_TABLE_DEFAULT_SENS = "DESC";
$FG_ACTION_SIZE_COLUMN = '5%';

if ( is_null ($order) || is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}

if ($posted==1) {
	$SQLcmd = '';
	$SQLcmd = do_field($SQLcmd, 'src', 'source');
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src', false, 1);
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src_peername', true);
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src_exten', true, 2);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'calledstation', false, 1);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'calledexten', true);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'dnid', true, 2);
	if ($SQLcmd == "")	$tmpcmd = " WHERE ";
	else			$tmpcmd = " AND ";
	$wheretmp = "IF(ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))>0,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1)),0)";
	if (ctype_digit($waitup1)) {
		if ($waitup1type == "4") {
			$SQLcmd .= $tmpcmd . $waitup1 . " < " . $wheretmp;
			$tmpcmd = " AND ";
		} elseif ($waitup1type == "5") {
			$SQLcmd .= $tmpcmd . $waitup1 . " <= " . $wheretmp;
			$tmpcmd = " AND ";
		} elseif ($waitup1type == "1") {
			$SQLcmd .= $tmpcmd . $waitup1 . " = " . $wheretmp;
			$tmpcmd = " AND ";
		}
	} else $waitup1 = "";
	if (ctype_digit($waitup2)) {
		if ($waitup2type == "2") {
			$SQLcmd .= $tmpcmd . $wheretmp . " <= " . $waitup2;
		} elseif ($waitup2type == "3") {
			$SQLcmd .= $tmpcmd . $wheretmp . " < " . $waitup2;
		}
	} else $waitup2 = "";
}

$date_clause = '';

normalize_day_of_month($fromstatsday_sday, $fromstatsmonth_sday, 1);
normalize_day_of_month($tostatsday_sday, $tostatsmonth_sday, 1);
// Date Clause
if ($fromday && isset ( $fromstatsday_sday ) && isset ( $fromstatsmonth_sday )) {
	if ($fromtime) {
		$date_clause = " AND t1.starttime >= '$fromstatsmonth_sday-$fromstatsday_sday $fromstatsday_hour:$fromstatsday_min'";
	} else {
		$date_clause = " AND t1.starttime >= '$fromstatsmonth_sday-$fromstatsday_sday'";
	}
} else {
	$cc_yearmonth = sprintf ( "%04d-%02d-%02d", date ( "Y" ), date ( "n" ), date ( "d" ) );
	if ($fromtime) {
		$date_clause = " AND t1.starttime >= '$cc_yearmonth $fromstatsday_hour:$fromstatsday_min'";
	} else {
		$date_clause = " AND t1.starttime >= '$cc_yearmonth'";
	}
}
if ($today && isset ( $tostatsday_sday ) && isset ( $tostatsmonth_sday )) {
	if ($totime) {
		$date_clause .= " AND t1.starttime <= '$tostatsmonth_sday-" . sprintf ( "%02d", intval ( $tostatsday_sday )/*+1*/) . " $tostatsday_hour:$tostatsday_min:00'";
	} else {
		$date_clause .= " AND t1.starttime <= '$tostatsmonth_sday-" . sprintf ( "%02d", intval ( $tostatsday_sday )/*+1*/) . " 23:59:59'";
	}
} elseif ($totime) {
	$cc_yearmonth = sprintf ( "%04d-%02d-%02d", date ( "Y" ), date ( "n" ), date ( "d" ) );
	$date_clause .= " AND t1.starttime <= '$cc_yearmonth $tostatsday_hour:$tostatsday_min:59'";
}

if (strpos ( $SQLcmd, 'WHERE' ) > 0) {
	$FG_TABLE_CLAUSE = substr ( $SQLcmd, 6 ) . $date_clause;
} elseif (strpos ( $date_clause, 'AND' ) > 0) {
	$FG_TABLE_CLAUSE = substr ( $date_clause, 5 );
}

$calledsbquery = $sessbillquery = " IN ('$customer')";
$FG_TABLE_NAME = "cc_call t1 LEFT JOIN cc_did ON cc_did.id=t1.id_did";
$FG_HTML_TABLE_TITLE = " - ".gettext("Call Logs")." - ";
$accdie = false;
$FG_COL_QUERY_RECORDS = ", t1.uniqueid";
switch ($choose_callowner) {
	case 1: //I & MY UNION
		if ($customer_info[16] || $customer_info[18]) {
			$FG_TABLE_NAME .= " LEFT JOIN cc_card_concat bb ON bb.concat_card_id IN (t1.card_caller, t1.card_id, t1.card_called) ";
			
			$FG_TABLE_NAME .= "LEFT JOIN cc_card ON cc_card.id=bb.concat_card_id";
			$calledsbquery = " IN (SELECT concat_card_id FROM cc_card_concat WHERE concat_id=$concat_id)";
			if (strlen($FG_TABLE_CLAUSE)>0)		$FG_TABLE_CLAUSE.=" AND ";
			$FG_TABLE_CLAUSE.="bb.concat_id=$concat_id AND (bb.mylogs=1 OR bb.concat_card_id{$sessbillquery})";
//			$FG_COL_QUERY_RECORDS = ", IF((bb.myrecords AND {$customer_info[21]}) OR t1.card_id{$sessbillquery} OR card_caller{$sessbillquery},t1.uniqueid,'')";
			$FG_COL_QUERY_RECORDS = ", IF((bb.myrecords AND {$customer_info[21]}) OR '$customer' IN (t1.card_id, card_caller, card_called), t1.uniqueid, '')";
			$sessbillquery = " IN (bb.concat_card_id)";
		} else $accdie = true;
		break;
	case 2: //MY UNION
		if ($customer_info[16] || $customer_info[18]) {
			$FG_TABLE_NAME .= " LEFT JOIN cc_card_concat bb ON bb.concat_card_id IN (t1.card_caller, t1.card_id, t1.card_called) LEFT JOIN cc_card ON cc_card.id=bb.concat_card_id";
			$calledsbquery = " IN (SELECT concat_card_id FROM cc_card_concat WHERE concat_id=$concat_id)";
			if (strlen($FG_TABLE_CLAUSE)>0)		$FG_TABLE_CLAUSE.=" AND ";
			$FG_TABLE_CLAUSE.="bb.concat_card_id<>'$customer' AND bb.concat_id=$concat_id AND bb.mylogs=1";
			$FG_COL_QUERY_RECORDS = ", IF(bb.myrecords=1 AND {$customer_info[21]},t1.uniqueid,'')";
			$sessbillquery = " IN (bb.concat_card_id)";
		} else $accdie = true;
		break;
	case 3: //OTHER UNION
		$instance_table = new Table("cc_card_concat LEFT JOIN cc_card ON id=concat_card_id","concat_card_id");
		$resmax = $instance_table -> Table_count ($DBHandle_max, "concat_id='$id' AND id_diller='$customer'");
		if ($resmax) {
			$FG_TABLE_NAME .= " LEFT JOIN cc_card_concat ON concat_card_id=t1.card_id OR concat_card_id=t1.card_caller";
			if (strlen($FG_TABLE_CLAUSE)>0)		$FG_TABLE_CLAUSE.=" AND ";
			$FG_TABLE_CLAUSE.="concat_id='$id' AND id_diller='$customer'";
			$calledsbquery = $sessbillquery = " IN (concat_card_id)";
			unset($calltype_list[4]);
			$calltype_list[3][0] .= $id;
		} else $accdie = true;
		break;
	case 4: //CUSTOMER
		if (has_rights(ACX_DISTRIBUTION) && isset($id) && is_numeric($id)) {
			$resmax = $instance_table -> Get_list ($DBHandle_max, "id='$id' AND id_diller='$customer'");
			if ($resmax) {
				$customer = $id;
				if (strlen($FG_TABLE_CLAUSE)>0)		$FG_TABLE_CLAUSE.=" AND ";
				$FG_TABLE_CLAUSE.="(t1.card_id='$id' OR t1.card_caller='$id')";
				unset($calltype_list[3]);
				$FG_HTML_TABLE_TITLE .= '</SPAN></B></TD><TD align="right"><B><font color=blue>'.$resmax[0][0]." ".$resmax[0][1]."</font> (".$resmax[0][2].') <SPAN style="COLOR: green">'.$resmax[0][3]." ".BASE_CURRENCY;
				$calledsbquery = $sessbillquery = " IN ('$id')";
			} else $accdie = true;
			break;
		} else $id = '';
	default:
		if ($choose_callowner < 0) {
			$FG_TABLE_NAME .= " LEFT JOIN cc_card_concat bb ON bb.concat_card_id=t1.card_id OR bb.concat_card_id=t1.card_caller OR bb.concat_card_id=t1.card_called";
			if (strlen($FG_TABLE_CLAUSE)>0)		$FG_TABLE_CLAUSE.=" AND ";
			$FG_TABLE_CLAUSE.="bb.concat_card_id=-$choose_callowner AND bb.concat_id=$concat_id AND bb.mylogs";
			$FG_COL_QUERY_RECORDS = ", IF(bb.myrecords AND {$customer_info[21]},t1.uniqueid,'')";
			$sessbillquery = " IN (bb.concat_card_id)";
			$calledsbquery = " IN (SELECT concat_card_id FROM cc_card_concat WHERE concat_id=$concat_id)";
			break;
		} else $choose_callowner = 0;
	case 0: //MY
		if (strlen($FG_TABLE_CLAUSE)>0)		$FG_TABLE_CLAUSE.=" AND ";
		$FG_TABLE_CLAUSE .= "'$customer' IN (t1.card_id, t1.card_caller, t1.card_called)";
		$calledsbquery = $choose_callowner == 0 && $customer_info[19] ? " IN (SELECT concat_card_id FROM cc_card_concat WHERE concat_id=$customer_info[19])" : $sessbillquery;
		break;
}

if ($accdie)	{
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error?c=accessdenied");
	die();
}

if (!isset($choose_calltype)) $choose_calltype = -1;
elseif ($choose_calltype != - 1) {
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
	switch ($choose_calltype) {
		case 0: //INCOMING
//IceShock			$FG_TABLE_CLAUSE .= "t1.sipiax IN (0,2,3,5,7) AND t1.card_caller NOT$calledsbquery AND t1.src_exten IS NULL AND LENGTH(t1.src)!=3";
			$FG_TABLE_CLAUSE .= "t1.sipiax IN (0,2,3,5,7) AND t1.card_caller NOT$calledsbquery";
			break;
		case 1: //OUTGOING
			$FG_TABLE_CLAUSE .= "t1.calledexten IS NULL AND t1.id_did IS NULL";
			break;
		case 2: //INTERNAL
			$FG_TABLE_CLAUSE .= "t1.src=t1.src_peername AND t1.calledexten IS NOT NULL";
			break;
		case 3: //CALLBACK CALLS
//IceShock			$FG_TABLE_CLAUSE .= "t1.sipiax!=4 AND (t1.src_peername IS NULL AND t1.src_exten IS NULL AND ((t1.id_did IS NULL AND t1.dnid LIKE t1.calledstation) OR (t1.card_id=t1.card_caller AND real_sessiontime IS NULL)))";
			$FG_TABLE_CLAUSE .= "(t1.sipiax=4 OR (t1.src_peername IS NULL AND t1.src_exten IS NULL AND ((t1.id_did IS NULL AND t1.dnid LIKE t1.calledstation) OR (t1.card_id=t1.card_caller AND real_sessiontime IS NULL))))";
			break;
		case 4: //TRANSIT
			$FG_TABLE_CLAUSE .= "(t1.card_id=t1.card_caller OR (t1.id_did IS NOT NULL AND t1.src_peername IS NULL)) AND t1.destination>0 AND t1.src_peername IS NULL AND t1.src_exten IS NULL AND t1.calledexten IS NULL AND t1.dnid NOT LIKE t1.calledstation";
			break;
		case 5: //FREE
			$FG_TABLE_CLAUSE .= "t1.sessionbill=0";
			break;
		case 6: //SURCHARGE
			$FG_TABLE_CLAUSE .= "t1.sessionbill>0";
			break;
	}
}

if (!isset($terminatecauseid)) {
	$terminatecauseid="ANSWER";
}

//$p = ($ACXSEERECORDING) ? .95 : 1;
$p=1;
$didkey = 2;
$FG_TABLE_COL = array();
$FG_TABLE_COL[]=array (gettext("Date"), "starttime", "18%", "center", "SORT", "22", "", "", "", "", "", "");
if ($choose_callowner == 1 || $choose_callowner == 2) {
	$FG_TABLE_COL[]=array (gettext("CallHolder"), "CallHolder", "4%", "center nowrap", "SORT", "79");
	$cholder = ", CONCAT_WS(' ',lastname,firstname,IF(company_name='','',CONCAT('<br/>(',company_name,')'))) CallHolder";
	$didkey++;
} else	$cholder = "";
$costkey = $didkey+6;
$FG_TABLE_COL[]=array (gettext("CallerID"), "src", "18%", "center", "SORT");
$FG_TABLE_COL[]=array (gettext("Number"), "DID", "8%", "center", "SORT", "40");
$FG_TABLE_COL[]=array (gettext("PhoneNumber"), "calledstation", "12%", "center", "SORT", "50", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Destination"), "destination", "19%", "center", "SORT", "30", "lie", "cc_prefix", "destination", "prefix='%id'", "%1" );
$FG_TABLE_COL[]=array (gettext("Route"), "route", "2%", "center", "SORT", "10");
$FG_TABLE_COL[]=array (gettext ("WaitUp"), "waitup", "2%", "center", "SORT", "30", "", "", "", "", "", "display_minute" );
$FG_TABLE_COL[]=array (gettext("Duration"), "sessiontime", "8%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
if ($terminatecauseid!="ANSWER") {
    $FG_TABLE_COL[]=array ('<acronym title="'.gettext("Terminate Cause").'">'.gettext("TC").'</acronym>', "terminatecauseid", "2%", "center", "SORT", "", "list", $dialstatus_list);
    $tc = ', t1.terminatecauseid';
    $costkey++;
} else $tc = '';
$FG_TABLE_COL[]=array (gettext("Cost"), "sessionbill", "7%", "center nowrap", "SORT", "30", "", "", "", "", "", "display_2bill");

$FG_COL_QUERY = "t1.starttime starttime$cholder,
IF(t1.src_exten IS NULL, t1.src, IF(t1.card_caller$calledsbquery,IF(t1.src_exten=t1.src_peername,t1.src_exten,IF(t1.src_peername IS NULL,t1.src,CONCAT(t1.src_peername,' &lt;<font color='
	,IF(t1.src!=t1.src_peername AND t1.src_exten!=t1.src AND t1.src_exten NOT LIKE '%#%',CONCAT('red>',t1.src),CONCAT('green>',t1.src_exten)),'</font>&gt;'))),CONCAT(t1.src_peername
	,IF(t1.src_peername=t1.src,'',CONCAT(' &lt;<font color=red>',t1.src,'</font>&gt;'))))) AS src,
IF(iduser$calledsbquery AND t1.sipiax IN (2,3,5,7),t1.dnid,'') AS DID,
IF(t1.sipiax IN (2,3,7) AND t1.terminatecauseid<>1,'',IF(t1.card_called$calledsbquery, IF(t1.calledexten IS NOT NULL
	,IF(t1.calledexten=t1.calledstation, t1.calledexten, CONCAT(t1.calledstation,' &lt;<font color=green>',t1.calledexten,'</font>&gt;')), t1.calledstation)
	,IF(t1.card_id$calledsbquery,t1.calledstation,t1.dnid))) calledstation,
IF(t1.card_called$calledsbquery OR t1.card_id$calledsbquery,t1.destination,-1) AS destination,
id_ratecard AS route,
IF(ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))>3000,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))-3600,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))) AS waitup,
t1.sessiontime$tc,
IF(t1.card_id$calledsbquery, t1.sessionbill+margindillers, 0) sessionbill";

if ($ACXSEERECORDING && $terminatecauseid!="INCOMPLET" && !($popup_select>=1) && $choose_callowner != 4) {
	$FG_TABLE_COL [] = array ('<span class="liens">' . gettext("Audio") . "</span>", "uniqueid", "1%", "center nowrap", "", "30", "", "", "", "", "", "linkonmonitorfile");
	$FG_COL_QUERY .= $FG_COL_QUERY_RECORDS;
}

$FG_LIMITE_DISPLAY = 100;
$FG_EDITION = true;
$FG_HTML_TABLE_WIDTH = "98%";
$FG_ACTION_SIZE_COLUMN = "1%";

if ($terminatecauseid=="ANSWER") {
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE .= " AND ";
	$FG_TABLE_CLAUSE .= " (t1.terminatecauseid=1) ";
} elseif ($terminatecauseid=="INCOMPLET") {
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE .= " AND ";
	$FG_TABLE_CLAUSE .= " (t1.terminatecauseid<>1) ";
}
if (!isset($resulttype)) $resulttype="min";
//if ($_SESSION["card_id"]==6) echo htmlentities("SELECT ".$FG_COL_QUERY." FROM ".$FG_TABLE_NAME." WHERE ".$FG_TABLE_CLAUSE);
$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);

$cdid = ", IF(t1.card_id$calledsbquery AND t1.sipiax IN (2,3,5,7),t1.dnid,'') DID";
if (!$nodisplay) {
	$list = $instance_table -> Get_list ($DBHandle, $FG_TABLE_CLAUSE, $order, $sens, null, null, $FG_LIMITE_DISPLAY, $current_page*$FG_LIMITE_DISPLAY, "GROUP BY t1.id");
	if (is_array($list) && count($list)>0 && $order != "DID") {
		foreach ($list as $key => $var) {
			if ($var[$didkey]) {
				break;
			}
			if ($var == end($list)) {
				unset($FG_TABLE_COL[$didkey]);
				$cdid = "";
			}
		}
	}
}
$FG_NB_TABLE_COL = count($FG_TABLE_COL);
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;
if ($FG_DELETION || $FG_EDITION)	$FG_TOTAL_TABLE_COL++;

$et = $resulttype=="sec" ? "t1.sessiontime" : "sec_to_time(t1.sessiontime)";
$FG_EXPORT_QUERY = "t1.starttime Date
$cholder,
IF(t1.src_exten IS NULL, t1.src, IF(t1.card_caller$calledsbquery,IF(t1.src_exten=t1.src_peername,t1.src_exten,IF(t1.src_peername IS NULL,t1.src,CONCAT(t1.src_peername,'-'
	,IF(t1.src!=t1.src_peername AND t1.src_exten!=t1.src,t1.src,t1.src_exten),'-'))),CONCAT(t1.src_peername
	,IF(t1.src_peername=t1.src,'',CONCAT(' -',t1.src,'-'))))) CallerID
$cdid,
IF(t1.sipiax IN (2,3,7) AND t1.terminatecauseid<>1,'',IF(t1.card_called$calledsbquery, IF(t1.calledexten IS NOT NULL
	,IF(t1.calledexten=t1.calledstation, t1.calledexten, CONCAT(t1.calledstation,'-',t1.calledexten,'-')), t1.calledstation)
	,IF(t1.card_id$calledsbquery,t1.calledstation,t1.dnid))) PhoneNumber,
IF(t1.card_id$calledsbquery,t2.destination,'') Destination,
$et Duration,
ROUND(IF(t1.card_id$calledsbquery, t1.sessionbill+margindillers, 0),5) Cost";
//IceShock
//IF(ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))>0,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1)),0) WaitUp";

// EXPORT
$FG_EXPORT_SESSION_VAR = "pr_export_entity_call";

// Query Preparation for the Export Functionality
$_SESSION [$FG_EXPORT_SESSION_VAR] = "SELECT $FG_EXPORT_QUERY FROM $FG_TABLE_NAME LEFT JOIN cc_prefix t2 ON prefix = t1.destination WHERE $FG_TABLE_CLAUSE GROUP BY t1.id";

$QUERY = "SELECT day, SUM(sessiontime) calltime, SUM(cost) cost, COUNT(*) nbcall, SUM(GREATEST(WaitUp,0)) waitup FROM 
(SELECT DATE(t1.starttime) AS day, t1.sessiontime, IF(t1.card_id$calledsbquery,t1.sessionbill+margindillers,0) AS cost, 
IF(ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))>3000,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))-3600,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))) AS WaitUp FROM ".$FG_TABLE_NAME." WHERE ".$FG_TABLE_CLAUSE." GROUP by t1.id) tt GROUP BY day ORDER BY day"; //extract(DAY from calldate)
//echo $QUERY;
$mmax = $totalcall = $totalminutes = $totalcost = $totalwaitup = $nb_record = 0;
if (!$nodisplay) {
	$res = $DBHandle -> Execute($QUERY);
	if ($res) {
		$num = $res -> RecordCount();
		for($i=0;$i<$num;$i++) {
			$list_total_day [] = $res -> fetchRow();
		}
		foreach ($list_total_day as $data) {
			if ($mmax < $data[1])
				$mmax	 = $data[1];
			$totalcall	+= $data[3];
			$totalminutes	+= $data[1];
			$totalcost	+= $data[2];
			$totalwaitup	+= $data[4];
		}
		$nb_record = $totalcall;
	}
//	if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
//	$nb_record = $instance_table -> Table_count ($DBHandle, $FG_TABLE_CLAUSE);
//	if ($FG_DEBUG >= 1) var_dump ($list);
}

if ($totalcost == 0)	unset($FG_TABLE_COL[$costkey]);

if ($nb_record<=$FG_LIMITE_DISPLAY) {
	$nb_record_max=1;
} else {
	if ($nb_record % $FG_LIMITE_DISPLAY == 0) {
		$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY));
	} else {
		$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY)+1);
	}	
}


$smarty->display( 'main.tpl');

if (!($popup_select>=1)) {
	// #### HELP SECTION
	echo $CC_help_balance_customer;
} ?>

<!-- ** ** ** ** ** Part for the research ** ** ** ** ** -->
	<center>
	<FORM METHOD=POST ACTION="<?php echo $PHP_SELF?>">
	    <INPUT TYPE="hidden" NAME="s" value=1>
	    <INPUT TYPE="hidden" NAME="t" value=0>
	    <INPUT TYPE="hidden" NAME="sens" value="<?php echo $sens; ?>">
	    <INPUT TYPE="hidden" NAME="popup_select" value="<?php echo $popup_select; ?>">
<?php if ($choose_callowner == 4) {?>
	    <INPUT TYPE="hidden" NAME="choose_callowner" value=4>
<?php }?>
	    <INPUT TYPE="hidden" NAME="posted" value=1>
	    <INPUT TYPE="hidden" NAME="current_page" value=0>
	    <INPUT TYPE="hidden" NAME="id" value="<?php echo $id; ?>">
	    <INPUT TYPE="hidden" NAME="order" value="<?php echo $order; ?>">
	    <INPUT TYPE="hidden" NAME="terminatecauseid" value="<?php echo $terminatecauseid; ?>">
	    <table class="callhistory_maintable" align="center">
		<tr>
		    <td align="left" <?php bgcolor(0)?>>
			<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext ( "DATE" ); ?></font>
		    </td>
		    <td align="left" <?php bgcolor(1)?> colspan="7">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
			    <tr>
				<td class="fontstyle_searchoptions">&nbsp;
					<input type="checkbox" name="fromday" value="true"<?php
					if ($fromday) {
						?> checked<?php
					}
					?>>
				</td>
				<td class="fontstyle_searchoptions">
					<?php echo gettext ( "From" );?> :
				</td>
				<td class="fontstyle_searchoptions">
					<select name="fromstatsday_sday" class="form_input_select">
					<?php
					for($i = 1; $i <= 31; $i ++) {
						if ($fromstatsday_sday == sprintf ( "%02d", $i ))
							$selected = " selected";
						else
							$selected = "";
						echo "<option value=\"" . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . "</option>";
					}
					?> 
					</select>
					<select name="fromstatsmonth_sday" class="form_input_select">
					<?php
				$year_actual = date ( "Y" );
				$monthname = array (gettext ( "January" ), gettext ( "February" ), gettext ( "March" ), gettext ( "April" ), gettext ( "May" ), gettext ( "June" ), gettext ( "July" ), gettext ( "August" ), gettext ( "September" ), gettext ( "October" ), gettext ( "November" ), gettext ( "December" ) );
				for($i = $year_actual; $i >= $year_actual - 1; $i --) {
					if ($year_actual == $i) {
						$monthnumber = date ( "n" ) - 1; // Month number without lead 0.
					} else {
						$monthnumber = 11;
					}
					for($j = $monthnumber; $j >= 0; $j --) {
						$month_formated = sprintf ( "%02d", $j + 1 );
						if ($fromstatsmonth_sday == "$i-$month_formated")
							$selected = " selected";
						else
							$selected = "";
						echo "<OPTION value=\"$i-$month_formated\"$selected> $monthname[$j]-$i </option>";
					}
				}
					?> 
					</select>
				</td>
				<td class="fontstyle_searchoptions">
					<input type="checkbox" name="today" value="true"<?php
					if ($today) {
						?> checked <?php
					}
					?>>
				</td>
				<td class="fontstyle_searchoptions">
					<?php echo gettext ( "To" )?> :
				</td>
				<td class="fontstyle_searchoptions">
					<select name="tostatsday_sday" class="form_input_select">
					<?php
				for($i = 1; $i <= 31; $i ++) {
					if ($tostatsday_sday == sprintf ( "%02d", $i )) {
						$selected = " selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?> 
					</select> <select name="tostatsmonth_sday" class="form_input_select">
					<?php
				$year_actual = date ( "Y" );
				for($i = $year_actual; $i >= $year_actual - 1; $i --) {
					if ($year_actual == $i) {
						$monthnumber = date ( "n" ) - 1; // Month number without lead 0.
					} else {
						$monthnumber = 11;
					}
					for($j = $monthnumber; $j >= 0; $j --) {
						$month_formated = sprintf ( "%02d", $j + 1 );
						if ($tostatsmonth_sday == "$i-$month_formated")
							$selected = " selected";
						else
							$selected = "";
						echo "<OPTION value=\"$i-$month_formated\"$selected> $monthname[$j]-$i </option>";
					}
				}
				?> 
					</select>
				</td>
			    </tr>
			    <tr>
				<td class="fontstyle_searchoptions">&nbsp;
					<input type="checkbox" name="fromtime" value="true"<?php
					if ($fromtime) {
						?> checked <?php
					}
					?>>
				</td>
				<td class="fontstyle_searchoptions">
					<?php echo gettext ( "Time" )?> :
				</td>
				<td class="fontstyle_searchoptions">
					<select name="fromstatsday_hour" class="form_input_select">
					<?php
				for($i = 0; $i <= 23; $i ++) {
					if ($fromstatsday_hour == sprintf ( "%02d", $i )) {
						$selected = " selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?> 
					</select> : <select name="fromstatsday_min" class="form_input_select">
					<?php
				for($i = 0; $i < 60; $i = $i + 5) {
					if ($fromstatsday_min == sprintf ( "%02d", $i )) {
						$selected = " selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?> 
					</select>
				</td>
				<td class="fontstyle_searchoptions">
					<input type="checkbox" name="totime" value="true"<?php
					if ($totime) {
						?> checked <?php
					}
					?>>
				</td>
				<td class="fontstyle_searchoptions">
					<?php echo gettext ( "Time" )?> :
				</td>
				<td class="fontstyle_searchoptions">
					<select name="tostatsday_hour" class="form_input_select">
					<?php
				for($i = 0; $i <= 23; $i ++) {
					if ($tostatsday_hour == sprintf ( "%02d", $i )) {
						$selected = " selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?> 
					</select> : <select name="tostatsday_min" class="form_input_select">
					<?php
				for($i = 0; $i < 60; $i = $i + 5) {
					if ($tostatsday_min == sprintf ( "%02d", $i )) {
						$selected = " selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?> 
					</select>
				</td>
			    </tr>
			</table>
		    </td>
		</tr>
		<tr>
			<td align="left" <?php bgcolor(0)?> nowrap>
				<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("CALLERID");?>&nbsp;&nbsp;</font>
			</td>
			<td align="left" <?php bgcolor(1)?> colspan="7">
			<table width="100%" border="0" cellspacing="1" cellpadding="0">
			<tr><td class="fontstyle_searchoptions" nowrap>&nbsp;<INPUT TYPE="text" NAME="callerid" value="<?php echo $callerid?>" class="form_input_text">&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="1" <?php if((!isset($calleridtype))||($calleridtype==1)){?>checked<?php }?>><?php echo gettext("Exact");?>&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="2" <?php if($calleridtype==2){?>checked<?php }?>><?php echo gettext("Begins with")?>&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="3" <?php if($calleridtype==3){?>checked<?php }?>><?php echo gettext("Contains");?>&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="4" <?php if($calleridtype==4){?>checked<?php }?>><?php echo gettext("Ends with");?>&nbsp;</td>
			</tr></table></td>
		</tr>
		<tr>
			<td align="left" <?php bgcolor(0)?> nowrap>
				<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("PHONENUMBER");?>&nbsp;&nbsp;</font>
			</td>
			<td align="left" <?php bgcolor(1)?> colspan="7">
			<table width="100%" border="0" cellspacing="1" cellpadding="0">
			<tr><td class="fontstyle_searchoptions" nowrap>&nbsp;<INPUT TYPE="text" NAME="phonenumber" value="<?php echo $phonenumber?>" class="form_input_text">&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="1" <?php if((!isset($phonenumbertype))||($phonenumbertype==1)){?>checked<?php }?>><?php echo gettext("Exact");?>&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="2" <?php if($phonenumbertype==2){?>checked<?php }?>><?php echo gettext("Begins with")?>&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="3" <?php if($phonenumbertype==3){?>checked<?php }?>><?php echo gettext("Contains");?>&nbsp;</td>
			<td align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="4" <?php if($phonenumbertype==4){?>checked<?php }?>><?php echo gettext("Ends with");?>&nbsp;</td>
			</tr></table></td>
		</tr>		
		<!-- Select Calltype: -->
		<tr <?php bgcolor(1)?>>
			<td <?php bgcolor(0)?> align="left"><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("CALL TYPE"); ?>&nbsp;&nbsp;</font>
			</td>
			<td class="fontstyle_searchoptions" align="right" nowrap>
				&nbsp;<?php echo gettext("BY DIRECTION");?> :&nbsp;&nbsp;
			</td>
			<td colspan="2" nowrap>
				<select NAME="choose_calltype" size="1" class="form_input_select">
					<option value='-1' <?php if (($choose_calltype==-1) || (!isset($choose_calltype))){?>selected<?php } ?>><?php echo gettext('ALL CALLS'); ?></option>
<?php
					foreach($list_calltype as $key => $cur_value) { ?>
					<option value='<?php echo $cur_value[1] ?>' <?php if ($choose_calltype==$cur_value[1]){?>selected<?php } ?>><?php echo gettext($cur_value[0]); ?></option>
<?php					} ?>
				</select>&nbsp;&nbsp;
			</td>
			<td align="left" colspan="4" nowrap>
<?php			if (count($calltype_list) > 1 && $choose_callowner != 4) {?>
				<font class="fontstyle_searchoptions"><?php echo gettext("BY OWNER");?> :&nbsp;</font>
				<select NAME="choose_callowner" size="1" class="form_input_select" >
<?php
					foreach($calltype_list as $key => $cur_value) {?>
					<option value='<?php echo $cur_value[1] ?>'<?php if ($choose_callowner==$cur_value[1]){?> selected<?php } ?>><?php echo gettext($cur_value[0]); ?></option>
<?php					} ?>
				</select>&nbsp;
<?php			} ?>
			</td>
		</tr>
		<!-- Select Option : to show just the Answered Calls or all calls, Result type, currencies... -->
		<tr <?php bgcolor(1)?>>
			<td <?php bgcolor(0)?> align="left" rowspan="<?php if ($totalcost) echo 3; else echo 2;?>"><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("OPTIONS")?></font>
			</td>
			<td class="fontstyle_searchoptions" align="right" nowrap>
				&nbsp;&nbsp;<?php echo gettext("SHOW");?> :&nbsp;&nbsp;
			</td>
			<td>
				<input name="terminatecauseid" type="radio" value="ANSWER" <?php if((!isset($terminatecauseid))||($terminatecauseid=="ANSWER")){?>checked<?php }?> />
			</td>
			<td class="fontstyle_searchoptions" nowrap>
				&nbsp;<?php echo gettext("Answered Calls"); ?>&nbsp;&nbsp;
			</td>
			<td>
				<input name="terminatecauseid" type="radio" value="INCOMPLET" <?php if($terminatecauseid=="INCOMPLET"){?>checked<?php }?> />
			</td>
			<td class="fontstyle_searchoptions" nowrap>
				&nbsp;<?php echo gettext("Not Completed"); ?>&nbsp;&nbsp;
			</td>
			<td width="1%">
				<input name="terminatecauseid" type="radio" value="ALL" <?php if($terminatecauseid=="ALL"){?>checked<?php }?> />
			</td>
			<td class="fontstyle_searchoptions" nowrap width="100%" align="left">
				&nbsp;<?php echo gettext("All Calls");?> 
			</td>
		</tr>
		<tr <?php bgcolor(1)?>>
			<td class="fontstyle_searchoptions" align="right" nowrap>
				&nbsp;<?php echo gettext("RESULT");?> :&nbsp;&nbsp;
			</td>
			<td>
				<input type="radio" NAME="resulttype" value="min" <?php if(!isset($resulttype)||$resulttype=="min"){?>checked<?php }?> />
			</td>
			<td class="fontstyle_searchoptions">
				&nbsp;<?php echo gettext("Minutes");?> 
			</td>
			<td>
				<input type="radio" NAME="resulttype" value="sec" <?php if($resulttype=="sec"){?>checked<?php }?>/>
			</td>
			<td class="fontstyle_searchoptions" align="left" colspan="3">
				&nbsp;<?php echo gettext("Seconds");?> 
			</td>
		</tr>
<?php			if ($totalcost) {?>
		<tr <?php bgcolor(1)?>>
			<td class="fontstyle_searchoptions" align="right">
				&nbsp;<?php echo gettext("CURRENCY");?> :&nbsp;&nbsp;
			</td>
			<td class="fontstyle_searchoptions" align="left" colspan="6">
				<select NAME="choose_currency" size="1" class="form_input_select">
					<?php
					$currencies_list = get_currencies();
					if ($choose_currency == "")	$choose_currency = mb_strtoupper($customer_info[15]);
					foreach($currencies_list as $key => $cur_value) {
					?>
					<option value='<?php echo $key ?>' <?php if (($choose_currency==$key) || (!isset($choose_currency) && $key==mb_strtoupper(BASE_CURRENCY))){?>selected<?php } ?>><?php echo $cur_value[1].' ('.$cur_value[2].')' ?>
					</option>
					<?php 	} ?>
				</select>
			</td>
		</tr>
<?php			}
			if ($A2B->config["webui"]['waitup_sorting']) {?>
		<tr <?php bgcolor(1)?>>
			<td <?php bgcolor(0)?> align="left"><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("WAITUP").", ".gettext("sec")?>&nbsp;&nbsp;</font>
			</td>
			<td align="left">
			<table width="100%" border="0" cellspacing="1" cellpadding="0"><tr>
			    <td class="fontstyle_searchoptions" nowrap>&nbsp;<INPUT TYPE="text" NAME="waitup1" value="<?php echo $waitup1?>" class="form_input_text" size="6" maxlength="6"></td>
			</tr></table>
			</td>
			<td align="left" colspan="2">
			<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
			    <td align="left" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="waitup1type" value="4" <?php if($waitup1type==4){?>checked<?php }?>>&lt;</td>
			    <td align="left" class="fontstyle_searchoptions" nowrap>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" NAME="waitup1type" value="5" <?php if((!isset($waitup1type))||($waitup1type==5)){?>checked<?php }?>>&lt;=</td>
			</tr></table>
			</td>
			<td align="left" colspan="4">
			<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
			    <td align="left" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="waitup1type" value="1" <?php if($waitup1type==1){?>checked<?php }?>>=</td>
			    <td align="center" class="fontstyle_searchoptions" nowrap><b>(<?php echo gettext("WaitUp")?>)</b>&nbsp;&nbsp;&nbsp;</td>
			    <td align="left" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="waitup2type" value="2" <?php if((!isset($waitup2type))||($waitup2type==2)){?>checked<?php }?>>&lt;=&nbsp;&nbsp;</td>
			    <td align="left" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="waitup2type" value="3" <?php if($waitup2type==3){?>checked<?php }?>>&lt;&nbsp;&nbsp;&nbsp;</td>
			    <td align="left" class="fontstyle_searchoptions" nowrap>&nbsp;<INPUT TYPE="text" NAME="waitup2" value="<?php echo $waitup2?>" class="form_input_text" size="6" maxlength="6"></td>
			    <td>&nbsp;</td>
			    <td>&nbsp;</td>
			    <td>&nbsp;</td>
			    <td>&nbsp;</td>
			</tr></table>
			</td>
		</tr>
<?php			}?>
		<!-- Select Option : to show just the Answered Calls or all calls, Result type, currencies... -->
		<tr>
		    <td <?php bgcolor(0)?>>
		    </td>
		    <td <?php bgcolor(1)?> align="center" colspan="7">
			<input class="form_input_button" value="&nbsp;<?php echo gettext("Search");?>&nbsp;" type="submit">
		    </td>
		</tr>
	    </table>
	</FORM>
</center>


<!-- ** ** ** ** ** Part to display the CDR ** ** ** ** ** -->
<center><?php echo gettext("Number of Calls");?> : <?php if (is_array($list) && count($list)>0){
							    echo $nb_record;
							    if ($ACXSEERECORDING && $nb_record>0 && $terminatecauseid!="INCOMPLET" && !($popup_select>=1) && $choose_callowner != 4) {?>
								<br><audio id="sound1" preload="none" style="width: 60%;" controls controlsList="nodownload">Ваш браузер не поддерживает тег audio!</audio>
								<script language="JavaScript" src="./javascript/playaudio.js"></script><?php
							    }
							}else{echo "0";}?>
     <table width="<?php echo $FG_HTML_TABLE_WIDTH?>" border="0" align="center" cellpadding="0" cellspacing="0">
		<TR bgcolor="#ffffff"> 
          <TD class="callhistory_td11"> 
            <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR>
                  <TD><B><SPAN style="COLOR: #ffffff; FONT-SIZE: 11px"><?php echo $FG_HTML_TABLE_TITLE?></SPAN></B></TD>
                                    <TD align="right"><SPAN style="COLOR: #ffffff; FONT-SIZE: 11px"><B> 
                    <?php if ($current_page>0){?>
                    <img src="<?php echo Images_Path_Main ?>/fleche-g.gif" width="5" height="10"> <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page-1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&popup_select=$popup_select&id=$id&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&choose_currency=$choose_currency&choose_callowner=$choose_callowner&waitup1=$waitup1&waitup2=$waitup2&waitup1type=$waitup1type&waitup2type=$waitup2type";?>"> 
                    <?php echo gettext("PREVIOUS");?> </a> -
                    <?php }?>
                    <?php echo ($current_page+1);?> / <?php  echo $nb_record_max;?> 
                    <?php if ($current_page<$nb_record_max-1){?>
                    - <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page+1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&popup_select=$popup_select&id=$id&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&choose_currency=$choose_currency&choose_callowner=$choose_callowner&waitup1=$waitup1&waitup2=$waitup2&waitup1type=$waitup1type&waitup2type=$waitup2type";?>"> 
                    <?php echo gettext("NEXT");?> </a> <img src="<?php echo Images_Path_Main ?>/fleche-d.gif" width="5" height="10">
                    </B></SPAN> 
                    <?php }?>
                  </TD>

                </TR>
            </TABLE></TD>
        </TR>
        <TR> 
          <TD> <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR class="bgcolor_008">
				  <TD width="<?php echo $FG_ACTION_SIZE_COLUMN?>" align="center" class="tableBodyRight" style="padding:2px;"></TD>
				  
                  <?php 
					if (is_array($list) && count($list)>0) {
						
					foreach ($FG_TABLE_COL as $i => $val) {
					?>
	                  <TD width="<?php echo $FG_TABLE_COL[$i][2]?>" align=middle class="tableBody" style="padding:2px;" nowrap> 
	                    <center><strong> 
	                    <?php  if (mb_strtoupper($FG_TABLE_COL[$i][4])=="SORT"){?>
	                    <a href="<?php  echo $PHP_SELF."?popup_select=$popup_select&s=1&t=0&stitle=$stitle&atmenu=$atmenu&current_page=$current_page&order=".$FG_TABLE_COL[$i][1]."&choose_callowner=$choose_callowner&waitup1=$waitup1&waitup2=$waitup2&waitup1type=$waitup1type&waitup2type=$waitup2type&sens="; if ($sens=="ASC"){echo"DESC";}else{echo"ASC";} 
						echo "&id=$id&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&choose_currency=$choose_currency";?>"> 
	                    <span class="liens"><?php  } ?>
	                    <?php echo $FG_TABLE_COL[$i][0]?> 
	                    <?php if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){?>
	                    &nbsp;<img src="<?php echo Images_Path_Main ?>/icon_up_12x12.GIF" width="12" height="12" border="0"> 
	                    <?php }elseif ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC"){?>
	                    &nbsp;<img src="<?php echo Images_Path_Main ?>/icon_down_12x12.GIF" width="12" height="12" border="0"> 
	                    <?php }?>
	                    <?php  if (mb_strtoupper($FG_TABLE_COL[$i][4])=="SORT"){?>
	                    </span></a> 
	                    <?php }?>
	                    </strong></center></TD>
				   <?php } ?>
				   		
                </TR>
                <TR>
                  <TD bgColor="#e1e1e1" colSpan=<?php echo $FG_TOTAL_TABLE_COL?> height="1"></TD>
                </TR>
				<?php
				  	 $ligne_number=0;
				  	 foreach ($list as $recordset){ 
						 $ligne_number++;
				?>
				
               		 <TR bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>"  onMouseOver="bgColor='#C4FFD7'" onMouseOut="bgColor='<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>'"> 
						<TD align="right" class="tableBody"><?php  echo $ligne_number+$current_page*$FG_LIMITE_DISPLAY."."; ?></TD>
							 
				  		<?php foreach ($FG_TABLE_COL as $i => $val) {?>
						<?php
							if ($FG_TABLE_COL[$i][6]=="lie"){
									$instance_sub_table = new Table($FG_TABLE_COL[$i][7], $FG_TABLE_COL[$i][8]);
									$sub_clause = str_replace("%id", $recordset[$i], $FG_TABLE_COL[$i][9]);
									$select_list = $instance_sub_table -> Get_list ($DBHandle, $sub_clause, null, null, null, null, null, null);
									
									
									$field_list_sun = preg_split('/,/',$FG_TABLE_COL[$i][8]);
									$record_display = $FG_TABLE_COL[$i][10];
									
									for ($l=1;$l<=count($field_list_sun);$l++){
										$record_display = str_replace("%$l", $select_list[0][$l-1], $record_display);
									}
								
							}elseif ($FG_TABLE_COL[$i][6]=="list"){
									$select_list = $FG_TABLE_COL[$i][7];
									$record_display = $select_list[$recordset[$i]][0];
							
							}else{
									$record_display = $recordset[$i];
							}
							
							if ( is_numeric($FG_TABLE_COL[$i][5]) && (strlen($record_display) > $FG_TABLE_COL[$i][5]) ) {
								$record_display = substr($record_display, 0, $FG_TABLE_COL[$i][5]-3)."";
							}
							
							
				 		 ?>
                 		 <TD vAlign=top align=<?php echo $FG_TABLE_COL[$i][3]?> class=tableBody><?php
                 		 if (isset ($FG_TABLE_COL[$i][11]) && strlen($FG_TABLE_COL[$i][11])>1){
						 	call_user_func($FG_TABLE_COL[$i][11], $record_display);
						 }else{
						 	echo stripslashes($record_display);
						 }
						 ?></TD>
				 		 <?php  } ?>
                  
			</TR>
				<?php
					 }//foreach ($list as $recordset)
					 if ($ligne_number < $FG_LIMITE_DISPLAY)  $ligne_number_end=$ligne_number +2;
					 while ($ligne_number < $ligne_number_end){
					 	$ligne_number++;
				?>
					<TR bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>"> 
				  		<?php foreach ($FG_TABLE_COL as $i => $val) {
				 		 ?>
                 		 <TD vAlign=top class=tableBody>&nbsp;</TD>
				 		 <?php  } ?>
                 		 <TD align="center" vAlign=top class=tableBodyRight>&nbsp;</TD>				
					</TR>
									
				<?php					 
					 } //END_WHILE
					 
				  }else{
				  		echo gettext("No data found !!!");
				  }//end_if
				 ?>
            </TABLE></td>
        </tr>
        <TR bgcolor="#ffffff"> 
          <TD bgColor=#ADBEDE height=16 style="PADDING-LEFT: 5px; PADDING-RIGHT: 3px"> 
	    <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR> 
                  <TD align="right"><SPAN style="COLOR: #ffffff; FONT-SIZE: 11px"><B> 
                    <?php if ($current_page>0){?>
                    <img src="<?php echo Images_Path_Main ?>/fleche-g.gif" width="5" height="10"> <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page-1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&popup_select=$popup_select&id=$id&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&choose_currency=$choose_currency&choose_callowner=$choose_callowner&waitup1=$waitup1&waitup2=$waitup2&waitup1type=$waitup1type&waitup2type=$waitup2type";?>"> 
                    <?php echo gettext("PREVIOUS");?> </a> -
                    <?php }?>
                    <?php echo ($current_page+1);?> / <?php  echo $nb_record_max;?> 
                    <?php if ($current_page<$nb_record_max-1){?>
                    - <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page+1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&popup_select=$popup_select&id=$id&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&choose_currency=$choose_currency&choose_callowner=$choose_callowner&waitup1=$waitup1&waitup2=$waitup2&waitup1type=$waitup1type&waitup2type=$waitup2type";?>"> 
                    <?php echo gettext("NEXT");?> </a> <img src="<?php echo Images_Path_Main ?>/fleche-d.gif" width="5" height="10">
                    </B></SPAN> 
                    <?php }?>
                  </TD>
		</TR>
            </TABLE></TD>
        </TR>
      </table></center>

<!-- ** ** ** ** ** Part to display the GRAPHIC ** ** ** ** ** -->
<br>

<?php
if ($nb_record) {
?>

<!-- TITLE GLOBAL -->
<center>
 <table border="0" cellspacing="0" cellpadding="0" width="80%"><tr><td align="left" height="30">
		<table cellspacing="0" cellpadding="1" bgcolor="#000000" width="50%"><tr><td>
			<table cellspacing="0" cellpadding="0" width="100%">
				<tr><td class="callhistory_td1" align="left" ><?php echo gettext("SUMMARY");?></td></tr>
			</table>
		</td></tr></table>
 </td></tr></table>

<!-- FIN TITLE GLOBAL MINUTES //-->

<table border="0" cellspacing="0" cellpadding="0"  width="80%">
<tr><td bgcolor="#000000">			
	<table border="0" cellspacing="1" cellpadding="2" width="100%">
	<tr>
	<td align="center" class="callhistory_td2"></td>
	<td class="callhistory_td2" align="center" colspan="3"><?php echo gettext("SUBTOTAL");?></td>
	<td class="callhistory_td3" align="center" colspan="2"><?php echo gettext("AVERAGE");?></td>
	<td align="center" class="callhistory_td2"></td>
    </tr>
	<tr>
		<td align="center" class="callhistory_td3"><?php echo gettext("DATE");?></td>
		<td align="center" class="callhistory_td2"><?php echo gettext("DURATION");?></td>
		<td align="center" class="callhistory_td2"><?php echo gettext("GRAPHIC");?></td>
		<td align="center" class="callhistory_td2"><?php echo gettext("CALLS");?></td>
		<td align="center" class="callhistory_td3"><?php echo gettext("ALOC");?></td>
		<td align="center" class="callhistory_td3"><?php echo gettext("WAITUP");?></td>
		<td align="center" class="callhistory_td2"><?php echo gettext("TOTAL COST");?></td>

		<!-- LOOP -->
	<?php
		$i=0;
		foreach ($list_total_day as $data) {
			$i=($i+1)%2;
			$tmc = $data[1]/$data[3];
			$waitup = $data[4]/$data[3];

			if ((!isset($resulttype)) || ($resulttype=="min")){
				$tmc = sprintf("%02d",intval($tmc/60)).":".sprintf("%02d",intval($tmc%60));
			}else{
				$tmc =intval($tmc);
			}

			if ((!isset($resulttype)) || ($resulttype=="min")){
				$minutes = sprintf("%02d",intval($data[1]/60)).":".sprintf("%02d",intval($data[1]%60));
			}else{
				$minutes = $data[1];
			}

			if ((!isset($resulttype)) || ($resulttype=="min")){
				$waitup = sprintf("%02d",intval($waitup/60)).":".sprintf("%02d",intval($waitup%60));
			}else{
				$waitup = intval($waitup);
			}
			if ($mmax>0)	$widthbar = intval(($data[1]/$mmax)*200);

		?>
	</tr><tr>
		<td align="center" class="sidenav" nowrap="nowrap"><font class="callhistory_td5"><?php echo $data[0]?></font></td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap" class="fontstyle_001"><?php echo $minutes?>&nbsp;</td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="left" nowrap="nowrap" width="<?php echo $widthbar+60?>">
			<table cellspacing="0" cellpadding="0"><tr>
				<td bgcolor="#e22424"><img src="<?php echo Images_Path_Main ?>/spacer.gif" width="<?php echo $widthbar?>" height="6"></td>
			</tr></table>
		</td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap" class="fontstyle_001"><?php echo $data[3]?>&nbsp;</td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="center" nowrap="nowrap" class="fontstyle_001" ><?php echo $tmc?> </td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="center" nowrap="nowrap" class="fontstyle_001"><?php echo $waitup?> </td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap" class="fontstyle_001"><?php  display_2bill($data[2]) ?>&nbsp;</td>
	<?php	}

		if ((!isset($resulttype)) || ($resulttype=="min")){
			$total_tmc = sprintf("%02d",intval(($totalminutes/$totalcall)/60)).":".sprintf("%02d",intval(($totalminutes/$totalcall)%60));
			$total_waitup = sprintf("%02d",intval(($totalwaitup/$totalcall)/60)).":".sprintf("%02d",intval(($totalwaitup/$totalcall)%60));
			$totalminutes = sprintf("%02d",intval($totalminutes/60)).":".sprintf("%02d",intval($totalminutes%60));
		}else{
			$total_tmc = intval($totalminutes/$totalcall);
			$total_waitup = intval($totalwaitup/$totalcall);
		}

	 ?>
	</tr>

	<!-- TOTAL -->
	<tr class="callhistory_td2">
		<td align="right" nowrap="nowrap" class="callhistory_td4"><?php echo gettext("TOTAL").":";?>&nbsp;</td>
		<td align="center" nowrap="nowrap" colspan="2" class="callhistory_td4"><?php echo $totalminutes?> </td>
		<td align="right" nowrap="nowrap" class="callhistory_td4"><?php echo $totalcall?>&nbsp;</td>
		<td align="center" nowrap="nowrap" class="callhistory_td4"><?php echo $total_tmc?></td>
		<td align="center" nowrap="nowrap" class="callhistory_td4"><?php echo $total_waitup?></td>
		<td align="center" nowrap="nowrap" class="callhistory_td4"><?php  display_2bill($totalcost) ?></td>
	</tr>
	
	</table>
	  
</td></tr></table>
</center>
<br>
<!-- SECTION EXPORT //--> &nbsp; &nbsp;
<a href="export_csv?var_export=<?php echo $FG_EXPORT_SESSION_VAR?>&var_export_type=type_csv" target="_blank"><img src="<?php echo Images_Path; ?>/excel.gif" border="0" height="30" /><?php echo gettext ( "Export CSV" ); ?></a> 
&nbsp; &nbsp; &nbsp;
<a href="export_csv?var_export=<?php echo $FG_EXPORT_SESSION_VAR?>&var_export_type=type_xml" target="_blank"><img src="<?php echo Images_Path; ?>/icons_xml.gif" border="0" height="32" /><?php echo gettext ( "Export XML" ); ?></a>

<?php  } else { ?>
	<center><h3><?php echo gettext("No calls in your selection");?>.</h3></center>
<?php  } ?>

<?php

$smarty->display( 'footer.tpl');
