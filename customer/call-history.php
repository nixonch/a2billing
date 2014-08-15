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
	Header ("Location: PP_error.php?c=accessdenied");	   
	die();
}

getpost_ifset(array('posted', 'Period', 'frommonth', 'fromstatsmonth', 'tomonth', 'tostatsmonth', 'fromday', 'fromstatsday_sday', 'fromstatsmonth_sday', 'today', 'tostatsday_sday', 'tostatsmonth_sday', 'fromtime', 'totime', 'fromstatsday_hour', 'tostatsday_hour', 'fromstatsday_min', 'tostatsday_min', 'calleridtype', 'phonenumbertype', 'sourcetype', 'clidtype', 'channel', 'resulttype', 'stitle', 'atmenu', 'current_page', 'order', 'sens', 'callerid', 'phonenumber', 'src', 'clid', 'choose_currency', 'terminatecauseid', 'choose_calltype', 'download', 'file'));


if (($download == "file") && $file && $ACXSEERECORDING) {
	
	if (strpos($file, '/') !== false) exit;
	
	$value_de = base64_decode ( $file );
	$parts = pathinfo($value_de);
	$value = $parts['filename'];
	$handle = DbConnect();
	$instance_table = new Table();
	$QUERY = "SELECT YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime), cc_card.username FROM cc_call LEFT JOIN cc_card ON cc_card.id=card_id WHERE uniqueid LIKE '$value' ORDER BY cc_call.id DESC LIMIT 1";
	$result = $instance_table -> SQLExec ($handle, $QUERY);
	if (is_array($result) && count($result)>0) {
	    $dl_full = MONITOR_PATH . "/" . $result[0][3] . "/" . $result[0][0] . "/" . $result[0][1] . "/" . $result[0][2] . "/" . $value_de;
	    $dl_name = $value_de;
	
	    if (! file_exists ( $dl_full )) {
		echo gettext ( "ERROR: Cannot download file " . $dl_name . ", it does not exist.<br>" );
		exit ();
	    }
	
	    header ( "Content-Type: application/octet-stream" );
	    header ( "Content-Disposition: attachment; filename=$dl_name" );
	    header ( "Content-Length: " . filesize ( $dl_full ) );
	    header ( "Accept-Ranges: bytes" );
	    header ( "Pragma: no-cache" );
	    header ( "Expires: 0" );
	    header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	    header ( "Content-transfer-encoding: binary" );
	
	    @readfile ( $dl_full );
	}
	exit ();
}


$QUERY = "SELECT username, credit, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, lastuse, activated, status, currency FROM cc_card WHERE username = '".$_SESSION["pr_login"]."' AND uipass = '".$_SESSION["pr_password"]."'";

$DBHandle_max = DbConnect();
$numrow = 0;
$resmax = $DBHandle_max -> Execute($QUERY);
if ($resmax)
	$numrow = $resmax -> RecordCount();

if ($numrow == 0) exit();
$customer_info =$resmax -> fetchRow();

if ($customer_info[14] != "1" && $customer_info[14] != "8") {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

$customer = $_SESSION["card_id"];


$dialstatus_list = Constants::getDialStatusList();

if (!isset ($current_page) || ($current_page == "")) {
	$current_page=0;
}

$FG_DEBUG = 0;
$FG_TABLE_NAME="cc_call t1";

// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#FFFFFF";
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#F2F8FF";

$yesno = array();
$yesno["1"] = array( "Yes", "1");
$yesno["0"] = array( "No", "0");

// 0 = NORMAL CALL ; 1 = VOIP CALL (SIP/IAX) ; 2= DIDCALL + TRUNK ; 3 = VOIP CALL DID ; 4 = CALLBACK call
$list_calltype = array(); 
$list_calltype["0"]  = array( gettext("STANDARD"), "0");
$list_calltype["1"]  = array( gettext("SIP/IAX"), "1");
$list_calltype["2"]  = array( gettext("DIDCALL"), "2");
$list_calltype["3"]  = array( gettext("DID_VOIP"), "3");
$list_calltype["4"]  = array( gettext("CALLBACK"), "4");
$list_calltype["5"]  = array( gettext("PREDICT"), "5");
$list_calltype ["6"] = array (gettext("AUTO DIALER"), "6" );
$list_calltype ["7"] = array (gettext("DID-ALEG"), "7" );

if ($ACXSEERECORDING) $p = .85;
else $p = 1;

$DBHandle  = DbConnect();

$FG_TABLE_DEFAULT_ORDER = "t1.starttime";
$FG_TABLE_DEFAULT_SENS = "DESC";

$FG_TABLE_COL = array();
$FG_TABLE_COL[]=array (gettext("Date"), "starttime", 19*$p ."%", "center", "SORT", "22", "", "", "", "", "", "");
//$FG_TABLE_COL[]=array (gettext("RegName"), "src_peername", 4*$p ."%", "center", "SORT", "30");
$FG_TABLE_COL[]=array (gettext("CallerID"), "src", 11*$p ."%", "center", "SORT", "40");
$FG_TABLE_COL[]=array (gettext("DID"), "did", 8*$p ."%", "center", "SORT", "30");
$FG_TABLE_COL[]=array (gettext("PhoneNumber"), "calledstation", 11*$p ."%", "center", "SORT", "30", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Destination"), "destination", 21*$p ."%", "center", "SORT", "30", "lie", "cc_prefix", "destination", "prefix='%id'", "%1" );
$FG_TABLE_COL[]=array (gettext("Route"), "route", $p ."%", "center", "SORT", "10");
$FG_TABLE_COL[]=array (gettext ("WaitUp"), "waitup", 2*$p, "center", "SORT", "30", "", "", "", "", "", "display_minute" );
$FG_TABLE_COL[]=array (gettext("Duration"), "sessiontime", 8*$p ."%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
$FG_TABLE_COL[]=array ('<acronym title="'.gettext("Terminate Cause").'">'.gettext("TC").'</acronym>', "terminatecauseid", 7*$p ."%", "center", "SORT", "", "list", $dialstatus_list);
//$FG_TABLE_COL[]=array (gettext("CallType"), "sipiax", 8*$p ."%", "center", "SORT",  "", "list", $list_calltype);
$FG_TABLE_COL[]=array (gettext("Cost"), "sessionbill", 12*$p ."%", "center", "SORT", "30", "", "", "", "", "", "display_2bill");

//$FG_COL_QUERY = "t1.starttime, IF(t1.src_exten IS NULL, t1.src, IF(card_caller=$customer,t1.src_exten,t1.src)) src, IF(t1.card_id='$customer', IF(t1.calledexten IS NOT NULL, t1.calledexten, t1.calledstation), t1.dnid) calledstation, t1.destination, id_ratecard as route, ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1)) AS waitup, t1.sessiontime, t1.terminatecauseid, t1.sipiax, IF(t1.card_id='$customer', t1.sessionbill+margindillers, 0) sessionbill";
$FG_COL_QUERY = "t1.starttime, IF(t1.src_exten IS NULL, t1.src, IF(card_caller=$customer,t1.src_exten,t1.src)) src, IF(t1.card_id=$customer AND (sipiax=2 OR sipiax=3 OR sipiax=5),t1.dnid,'') did, IF(t1.card_id='$customer', IF(t1.calledexten IS NOT NULL, t1.calledexten, t1.calledstation), t1.dnid) calledstation, IF(t1.card_id=$customer,t1.destination,-1), id_ratecard as route, ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1)) AS waitup, t1.sessiontime, t1.terminatecauseid, IF(t1.card_id='$customer', t1.sessionbill+margindillers, 0) sessionbill";
$et = $resulttype=="sec" ? "t1.sessiontime" : "sec_to_time(t1.sessiontime)";
$FG_EXPORT_QUERY = "t1.starttime Date, IF(t1.src_exten IS NULL, t1.src, IF(card_caller=$customer,t1.src_exten,t1.src)) CallerID, IF(t1.card_id=$customer AND (sipiax=2 OR sipiax=3 OR sipiax=5),t1.dnid,'') DID, IF(t1.card_id='$customer', IF(t1.calledexten IS NOT NULL, t1.calledexten, t1.calledstation), t1.dnid) PhoneNumber, IF(t1.card_id=$customer,t2.destination,'') Destination, $et Duration, ROUND(IF(t1.card_id='$customer', t1.sessionbill+margindillers, 0),5) Cost";

if ($ACXSEERECORDING) {
	$FG_TABLE_COL [] = array ('<span class="liens">' . gettext("Audio") . "</span>", "uniqueid", 100*(1-$p) ."%", "center", "", "30", "", "", "", "", "", "linkonmonitorfile_customer");
	$FG_COL_QUERY .= ', t1.uniqueid';
}

$FG_LIMITE_DISPLAY = 25;
$FG_NB_TABLE_COL = count($FG_TABLE_COL);
$FG_EDITION = true;
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;
if ($FG_DELETION || $FG_EDITION) $FG_TOTAL_TABLE_COL++;
$FG_HTML_TABLE_TITLE = " - ".gettext("Call Logs")." - ";
$FG_HTML_TABLE_WIDTH = "98%";
$FG_ACTION_SIZE_COLUMN = "1%";

$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);


if ( is_null ($order) || is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}

if ($posted==1) {
	$SQLcmd = '';
	$SQLcmd = do_field($SQLcmd, 'src', 'source');
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src', false, 1);
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src_exten', true, 2);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'calledstation', false, 1);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'calledexten', true);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'dnid', true, 2);
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
		$date_clause .= " AND t1.starttime <= '$tostatsmonth_sday-" . sprintf ( "%02d", intval ( $tostatsday_sday )/*+1*/) . " $tostatsday_hour:$tostatsday_min:59'";
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

if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
$FG_TABLE_CLAUSE.="(t1.card_id='$customer' OR t1.card_caller='$customer')";

if (!isset($choose_calltype)) $choose_calltype = -1;
elseif ($choose_calltype != - 1) {
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
	$FG_TABLE_CLAUSE .= " t1.sipiax='$choose_calltype' ";
}

if (!isset($terminatecauseid)) {
	$terminatecauseid="ANSWER";
}
if ($terminatecauseid=="ANSWER") {
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE .= " AND ";
	$FG_TABLE_CLAUSE .= " (t1.terminatecauseid=1) ";
}

if (!isset($resulttype)) $resulttype="min";

if (!$nodisplay) {
	$list = $instance_table -> Get_list ($DBHandle, $FG_TABLE_CLAUSE, $order, $sens, null, null, $FG_LIMITE_DISPLAY, $current_page*$FG_LIMITE_DISPLAY);
}

// EXPORT
$FG_EXPORT_SESSION_VAR = "pr_export_entity_call";

// Query Preparation for the Export Functionality
$_SESSION [$FG_EXPORT_SESSION_VAR] = "SELECT $FG_EXPORT_QUERY FROM $FG_TABLE_NAME LEFT JOIN cc_prefix t2 ON prefix = t1.destination WHERE $FG_TABLE_CLAUSE";

if (! is_null ( $order ) && ($order != '') && ! is_null ( $sens ) && ($sens != '')) {
	$_SESSION [$FG_EXPORT_SESSION_VAR] .= " ORDER BY $order $sense";
}

//$_SESSION["pr_sql_export"] = "SELECT $FG_COL_QUERY FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE";

$QUERY = "SELECT DATE(t1.starttime) AS day, sum(t1.sessiontime) AS calltime, sum(IF(t1.card_id='$customer',t1.sessionbill+margindillers,0)) AS cost, count(*) as nbcall, SUM(IF(ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1))>0,ROUND(UNIX_TIMESTAMP(t1.starttime)-INSERT(t1.uniqueid,1,1,1)),0)) AS waitup FROM $FG_TABLE_NAME WHERE ".$FG_TABLE_CLAUSE." GROUP BY day ORDER BY day"; //extract(DAY from calldate)

if (!$nodisplay) {
	$res = $DBHandle -> Execute($QUERY);
	if ($res) {
		$num = $res -> RecordCount();
		for($i=0;$i<$num;$i++) {				
			$list_total_day [] =$res -> fetchRow();
		}
	}
	
	if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
	$nb_record = $instance_table -> Table_count ($DBHandle, $FG_TABLE_CLAUSE);
	if ($FG_DEBUG >= 1) var_dump ($list);
}

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

// #### HELP SECTION
echo $CC_help_balance_customer;

if ($ACXSEERECORDING && $nb_record>0){ echo '
<script src="./javascript/WavPlayer/domready.js"></script>
<script src="./javascript/WavPlayer/swfobject.js"></script>
<script src="./javascript/WavPlayer/wavplayer.js"></script>
';}?>

<!-- ** ** ** ** ** Part for the research ** ** ** ** ** -->
	<center>
	<FORM METHOD=POST ACTION="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $current_page?>&terminatecauseid=<?php echo $terminatecauseid?>">
		<INPUT TYPE="hidden" NAME="posted" value=1>
		<INPUT TYPE="hidden" NAME="current_page" value=0>
		<table class="callhistory_maintable" align="center">
	<tr>
		<td align="left" class="bgcolor_004"><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext ( "DATE" ); ?></font>
		</td>
		<td align="left" class="bgcolor_005">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td class="fontstyle_searchoptions"><input type="checkbox"
					name="fromday" value="true" <?php
					if ($fromday) {
						?> checked
					<?php
					}
					?>> <?php
					echo gettext ( "From" );
					?> :
				<select name="fromstatsday_sday" class="form_input_select">
					<?php
					for($i = 1; $i <= 31; $i ++) {
						if ($fromstatsday_sday == sprintf ( "%02d", $i ))
							$selected = "selected";
						else
							$selected = "";
						echo "<option value=\"" . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . "</option>";
					}
					?>	
				</select>
				<select name="fromstatsmonth_sday"
					class="form_input_select">
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
							$selected = "selected";
						else
							$selected = "";
						echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";
					}
				}
				?>
				</select> <br />
				<input type="checkbox" name="fromtime" value="true"
					<?php
					if ($fromtime) {
						?> checked <?php
					}
					?>> 
				<?php
				echo gettext ( "Time :" )?>
				<select name="fromstatsday_hour" class="form_input_select">
				<?php
				for($i = 0; $i <= 23; $i ++) {
					if ($fromstatsday_hour == sprintf ( "%02d", $i )) {
						$selected = "selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?>						
				</select> : <select name="fromstatsday_min"
					class="form_input_select">
				<?php
				for($i = 0; $i < 60; $i = $i + 5) {
					if ($fromstatsday_min == sprintf ( "%02d", $i )) {
						$selected = "selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?>						
				</select></td>
				<td class="fontstyle_searchoptions"><input type="checkbox"
					name="today" value="true" <?php
					if ($today) {
						?> checked <?php
					}
					?>> 
				<?php
				echo gettext ( "To" );
				?>  :
				<select name="tostatsday_sday" class="form_input_select">
				<?php
				for($i = 1; $i <= 31; $i ++) {
					if ($tostatsday_sday == sprintf ( "%02d", $i )) {
						$selected = "selected";
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
							$selected = "selected";
						else
							$selected = "";
						echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";
					}
				}
				?>
				</select> <br />
				<input type="checkbox" name="totime" value="true"
					<?php
					if ($totime) {
						?> checked <?php
					}
					?>> 
				<?php
				echo gettext ( "Time :" )?>
				<select name="tostatsday_hour" class="form_input_select">
				<?php
				for($i = 0; $i <= 23; $i ++) {
					if ($tostatsday_hour == sprintf ( "%02d", $i )) {
						$selected = "selected";
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
						$selected = "selected";
					} else {
						$selected = "";
					}
					echo '<option value="' . sprintf ( "%02d", $i ) . "\"$selected>" . sprintf ( "%02d", $i ) . '</option>';
				}
				?>						
				</select></td>
			</tr>
		</table>
		</td>
	</tr>
			<tr>
				<td  align="left" class="bgcolor_004">
					<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("CALLERID");?></font>
				</td>
				<td  align="left" class="bgcolor_005">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td class="fontstyle_searchoptions">&nbsp;&nbsp;<INPUT TYPE="text" NAME="callerid" value="<?php echo $callerid?>" class="form_input_text"></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="calleridtype" value="1" <?php if((!isset($calleridtype))||($calleridtype==1)){?>checked<?php }?>><?php echo gettext("Exact");?></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="calleridtype" value="2" <?php if($calleridtype==2){?>checked<?php }?>><?php echo gettext("Begins with")?></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="calleridtype" value="3" <?php if($calleridtype==3){?>checked<?php }?>><?php echo gettext("Contains");?></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="calleridtype" value="4" <?php if($calleridtype==4){?>checked<?php }?>><?php echo gettext("Ends with");?></td>
				</tr></table></td>
			</tr>		
			<tr>
				<td  align="left" class="bgcolor_002">
					<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("PHONENUMBER");?></font>
				</td>
				<td  align="left" class="bgcolor_003">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td class="fontstyle_searchoptions">&nbsp;&nbsp;<INPUT TYPE="text" NAME="phonenumber" value="<?php echo $phonenumber?>" class="form_input_text"></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="phonenumbertype" value="1" <?php if((!isset($phonenumbertype))||($phonenumbertype==1)){?>checked<?php }?>><?php echo gettext("Exact");?></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="phonenumbertype" value="2" <?php if($phonenumbertype==2){?>checked<?php }?>><?php echo gettext("Begins with")?></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="phonenumbertype" value="3" <?php if($phonenumbertype==3){?>checked<?php }?>><?php echo gettext("Contains");?></td>
				<td  align="center" class="fontstyle_searchoptions"><input type="radio" NAME="phonenumbertype" value="4" <?php if($phonenumbertype==4){?>checked<?php }?>><?php echo gettext("Ends with");?></td>
				</tr></table></td>
			</tr>		
			<!-- Select Calltype: -->
			<tr>
			  <td class="bgcolor_004" align="left" ><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("CALL TYPE"); ?></font></td>
			  <td class="bgcolor_005" align="center">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
					<td  class="fontstyle_searchoptions">&nbsp;
					<select NAME="choose_calltype" size="1" class="form_input_select" >
						<option value='-1' <?php if (($choose_calltype==-1) || (!isset($choose_calltype))){?>selected<?php } ?>><?php echo gettext('ALL CALLS') ?>
								</option>
							<?php
								foreach($list_calltype as $key => $cur_value) {
							?>
								<option value='<?php echo $cur_value[1] ?>' <?php if ($choose_calltype==$cur_value[1]){?>selected<?php } ?>><?php echo gettext($cur_value[0]) ?>
								</option>
							<?php 	} ?>
						</select>
					</td>
				</tr>				
				</table>
			  </td>
			  </tr>
	
			<!-- Select Option : to show just the Answered Calls or all calls, Result type, currencies... -->
			<tr>
			  <td class="bgcolor_002" align="left" ><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("OPTIONS"); ?></font></td>
			  <td class="bgcolor_003" align="center">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td width="20%"  class="fontstyle_searchoptions">
						<?php echo gettext("SHOW");?> :  						
				   </td>
				   <td width="80%"  class="fontstyle_searchoptions">				   		
				 <?php echo gettext("Answered Calls"); ?>
				  <input name="terminatecauseid" type="radio" value="ANSWER" <?php if((!isset($terminatecauseid))||($terminatecauseid=="ANSWER")){?>checked<?php }?> />
				  <?php echo gettext("All Calls"); ?>

				   <input name="terminatecauseid" type="radio" value="ALL" <?php if($terminatecauseid=="ALL"){?>checked<?php }?>/>
					</td>
				</tr>
				<tr class="bgcolor_005">
					<td  class="fontstyle_searchoptions">
						<?php echo gettext("RESULT");?> : 
				   </td>
				   <td  class="fontstyle_searchoptions">
					<?php echo gettext("Minutes");?> <input type="radio" NAME="resulttype" value="min" <?php if(!isset($resulttype)||$resulttype=="min"){?>checked<?php }?> />
					<?php echo "- ".gettext("Seconds");?> <input type="radio" NAME="resulttype" value="sec" <?php if($resulttype=="sec"){?>checked<?php }?>/>
					</td>
				</tr>
				<tr>
					<td  class="fontstyle_searchoptions">
						<?php echo gettext("CURRENCY");?> :
					</td>
					<td  class="fontstyle_searchoptions">
					<select NAME="choose_currency" size="1" class="form_input_select" >
							<?php
								$currencies_list = get_currencies();
								if ($choose_currency == "")	$choose_currency = strtoupper($customer_info[15]);
								foreach($currencies_list as $key => $cur_value) {
							?>
								<option value='<?php echo $key ?>' <?php if (($choose_currency==$key) || (!isset($choose_currency) && $key==strtoupper(BASE_CURRENCY))){?>selected<?php } ?>><?php echo $cur_value[1].' ('.$cur_value[2].')' ?>
								</option>
							<?php 	} ?>
						</select>
					</td>
				</tr>				
				</table>
			  </td>
			  </tr>
			<!-- Select Option : to show just the Answered Calls or all calls, Result type, currencies... -->
			
			<tr>
        		<td class="bgcolor_004" align="left" > </td>
				<td class="bgcolor_005" align="center" >
					<input class="form_input_button" value=" <?php echo gettext("Search");?> " type="submit">
	  			</td>
    		</tr>
	</table>
	</FORM>
</center>


<!-- ** ** ** ** ** Part to display the CDR ** ** ** ** ** -->
<center><?php echo gettext("Number of Calls");?> : <?php if (is_array($list) && count($list)>0){ echo $nb_record . "<h3></h3>";}else{echo "0";}?></center>
     <table width="<?php echo $FG_HTML_TABLE_WIDTH?>" border="0" align="center" cellpadding="0" cellspacing="0">
		<TR bgcolor="#ffffff"> 
          <TD class="callhistory_td11"> 
            <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR> 
                  <TD><SPAN style="COLOR: #ffffff; FONT-SIZE: 11px"><B><?php echo $FG_HTML_TABLE_TITLE?></B></SPAN></TD>
                </TR>
            </TABLE></TD>
        </TR>
        <TR> 
          <TD> <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR class="bgcolor_008">
				  <TD width="<?php echo $FG_ACTION_SIZE_COLUMN?>" align="center" class="tableBodyRight" style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px"></TD>					
				  
                  <?php 
				  	if (is_array($list) && count($list)>0) {
					
				  	for($i=0;$i<$FG_NB_TABLE_COL;$i++) {
					?>
	                  <TD width="<?php echo $FG_TABLE_COL[$i][2]?>" align=middle class="tableBody" style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px"> 
	                    <center><strong> 
	                    <?php  if (strtoupper($FG_TABLE_COL[$i][4])=="SORT"){?>
	                    <a href="<?php  echo $PHP_SELF."?s=1&t=0&stitle=$stitle&atmenu=$atmenu&current_page=$current_page&order=".$FG_TABLE_COL[$i][1]."&sens="; if ($sens=="ASC"){echo"DESC";}else{echo"ASC";} 
						echo "&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&src=$src&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&choose_currency=$choose_currency";?>"> 
	                    <span class="liens"><?php  } ?>
	                    <?php echo $FG_TABLE_COL[$i][0]?> 
	                    <?php if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){?>
	                    &nbsp;<img src="<?php echo Images_Path_Main ?>/icon_up_12x12.GIF" width="12" height="12" border="0"> 
	                    <?php }elseif ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC"){?>
	                    &nbsp;<img src="<?php echo Images_Path_Main ?>/icon_down_12x12.GIF" width="12" height="12" border="0"> 
	                    <?php }?>
	                    <?php  if (strtoupper($FG_TABLE_COL[$i][4])=="SORT"){?>
	                    </span></a> 
	                    <?php }?>
	                    </strong></center></TD>
				   <?php } ?>
				   		
                </TR>
                <TR> 
                  <TD bgColor="#e1e1e1" colSpan=<?php echo $FG_TOTAL_TABLE_COL?> height="1">
                </TR>
				<?php
				  	 $ligne_number=0;					 
				  	 foreach ($list as $recordset){ 
						 $ligne_number++;
				?>
				
               		 <TR bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>"  onMouseOver="bgColor='#C4FFD7'" onMouseOut="bgColor='<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>'"> 
						<TD align="<?php echo $FG_TABLE_COL[$i][3]?>" class="tableBody"><?php  echo $ligne_number+$current_page*$FG_LIMITE_DISPLAY.".&nbsp;"; ?></TD>
							 
				  		<?php for($i=0;$i<$FG_NB_TABLE_COL;$i++){ ?>
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
                 		 <TD vAlign=top align="<?php echo $FG_TABLE_COL[$i][3]?>" class=tableBody><?php 
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
				  		<?php for($i=0;$i<$FG_NB_TABLE_COL;$i++){ 
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
					echo "&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&src=$src&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&choose_currency=$choose_currency";?>"> 
                    <?php echo gettext("PREVIOUS");?> </a> -
                    <?php }?>
                    <?php echo ($current_page+1);?> / <?php  echo $nb_record_max;?> 
                    <?php if ($current_page<$nb_record_max-1){?>
                    - <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page+1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&fromtime=$fromtime&totime=$totime&fromstatsday_hour=$fromstatsday_hour&fromstatsday_min=$fromstatsday_min&tostatsday_hour=$tostatsday_hour&tostatsday_min=$tostatsday_min&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&src=$src&clid=$clid&terminatecauseid=$terminatecauseid&choose_calltype=$choose_calltype&choose_currency=$choose_currency";?>"> 
                    <?php echo gettext("NEXT");?> </a> <img src="<?php echo Images_Path_Main ?>/fleche-d.gif" width="5" height="10">
                    </B></SPAN> 
                    <?php }?>
                  </TD>
		</TR>
            </TABLE></TD>
        </TR>
      </table>

<!-- ** ** ** ** ** Part to display the GRAPHIC ** ** ** ** ** -->
<br>

<?php 

if (is_array($list_total_day) && count($list_total_day)>0){

$mmax = $totalwaitup = $totalcall = $totalminutes = 0;
foreach ($list_total_day as $data){
	if ($mmax < $data[1])	$mmax = $data[1];
	$totalcall+=$data[3];
	$totalminutes+=$data[1];
	$totalcost+=$data[2];
	$totalwaitup+=$data[4];
}

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
		<td align="center" class="callhistory_td3"><acronym title="<?php echo gettext("AVERAGE LENGTH OF CALL");?>"><?php echo gettext("ALOC");?></acronym></font></td>
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
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap" class="fontstyle_001"><?php echo $minutes?> </td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="left" nowrap="nowrap" width="<?php echo $widthbar+60?>">
			<table cellspacing="0" cellpadding="0"><tr>
				<td bgcolor="#e22424"><img src="<?php echo Images_Path_Main ?>/spacer.gif" width="<?php echo $widthbar?>" height="6"></td>
			</tr></table>
		</td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap" class="fontstyle_001"><?php echo $data[3]?></td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="center" nowrap="nowrap" class="fontstyle_001" ><?php echo $tmc?> </td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="center" nowrap="nowrap" class="fontstyle_001"><?php echo $waitup?> </td>
		<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$i]?>" align="right" nowrap="nowrap" class="fontstyle_001"><?php  display_2bill($data[2]) ?></td>
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
		<td align="right" nowrap="nowrap" class="callhistory_td4"><?php echo gettext("TOTAL").":";?></td>
		<td align="center" nowrap="nowrap" colspan="2" class="callhistory_td4"><?php echo $totalminutes?> </td>
		<td align="right" nowrap="nowrap" class="callhistory_td4"><?php echo $totalcall?></td>
		<td align="center" nowrap="nowrap" class="callhistory_td4"><?php echo $total_tmc?></td>
		<td align="center" nowrap="nowrap" class="callhistory_td4"><?php echo $total_waitup?></td>
		<td align="center" nowrap="nowrap" class="callhistory_td4"><?php  display_2bill($totalcost) ?></td>
	</tr>
	
	</table>
	  
</td></tr></table>
</center>
<br>
<!-- SECTION EXPORT //--> &nbsp; &nbsp;
<a href="export_csv.php?var_export=<?php echo $FG_EXPORT_SESSION_VAR?>&var_export_type=type_csv" target="_blank"><img src="<?php echo Images_Path; ?>/excel.gif" border="0" height="30" /><?php echo gettext ( "Export CSV" ); ?></a> 
&nbsp; &nbsp; &nbsp;
<a href="export_csv.php?var_export=<?php echo $FG_EXPORT_SESSION_VAR?>&var_export_type=type_xml" target="_blank"><img src="<?php echo Images_Path; ?>/icons_xml.gif" border="0" height="32" /><?php echo gettext ( "Export XML" ); ?></a>

<?php  } else { ?>
	<center><h3><?php echo gettext("No calls in your selection");?>.</h3></center>
<?php  } ?>

<?php

$smarty->display( 'footer.tpl');
