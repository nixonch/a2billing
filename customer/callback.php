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
include ("lib/Class.RateEngine.php");	 
include ("lib/customer.smarty.php");

//include (dirname(__FILE__)."/lib/phpagi/phpagi-asmanager.php");


getpost_ifset(array('callback', 'called', 'calling'));


if (! has_rights (ACX_CALL_BACK)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}

$FG_DEBUG = 0;
$color_msg = 'red';
$endinuse = false;

$QUERY = "SELECT username, credit, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, lastuse, activated, status FROM cc_card WHERE username = '".$_SESSION["pr_login"]."' AND uipass = '".$_SESSION["pr_password"]."'";

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

$A2B -> DBHandle = DbConnect();
$instance_table = new Table();
$A2B -> set_instance_table ($instance_table);
$A2B -> cardnumber = $_SESSION["pr_login"];
if ($A2B -> callingcard_ivr_authenticate_light ($error_msg) && $callback) {
//	$called  = $A2B -> apply_rules($called);
//	$calling = $A2B->apply_add_countryprefixto ($calling);
//	if ($A2B->removeinterprefix) $calling = $A2B -> apply_rules($calling);
	if (strlen($called)>1 && strlen($calling)>1 && is_numeric($called) && is_numeric($calling)) {
//		$virtcalled = $called;
		$virtcalling = $calling;
			$QUERY = "SELECT name, regexten FROM cc_sip_buddies
					LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
					LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->card_id} ) AS v ON v.concat_id = bb.concat_id
					WHERE (id_cc_card = {$A2B->card_id} OR v.concat_id IS NOT NULL) AND (regexten = '{$called}' OR name = '{$called}') LIMIT 1";
//			$QUERY = "SELECT name FROM cc_sip_buddies WHERE id_cc_card = $A2B->card_id AND regexten = '$called' LIMIT 1";
			$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
			if (is_array($result) && $result[0][0] != "") {
				$A2B -> dnid			= $result[0][0];
				if ($result[0][1]) $called	= $result[0][1];
			} else {
			$A2B -> dnid = $A2B->apply_add_countryprefixto ($called);
			if ($A2B->removeinterprefix) $A2B -> dnid = $A2B -> apply_rules($A2B -> dnid);
			}
			$QUERY = "SELECT name, regexten FROM cc_sip_buddies
					LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
					LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->card_id} ) AS v ON v.concat_id = bb.concat_id
					WHERE (id_cc_card = {$A2B->card_id} OR v.concat_id IS NOT NULL) AND (regexten = '{$calling}' OR name = '{$calling}') LIMIT 1";
//			$QUERY = "SELECT name FROM cc_sip_buddies WHERE id_cc_card = $A2B->card_id AND regexten = '$calling' LIMIT 1";
			$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
			if (is_array($result) && $result[0][0] != "") {
				$virtcalling			= $result[0][0];
				if ($result[0][1]) $calling	= $result[0][1];
			}
			$RateEngine = new RateEngine();
			$RateEngine -> webui = 0;
			// LOOKUP RATE : FIND A RATE FOR THIS DESTINATION

			$A2B -> agiconfig['accountcode']=$_SESSION["pr_login"];
			$A2B -> agiconfig['use_dnid']=1;
			$A2B -> agiconfig['say_timetocall']=0;						
			$A2B -> extension = $A2B -> destination = $A2B -> dnid;
			$resfindrate = $RateEngine->rate_engine_findrates($A2B, $A2B -> dnid, $_SESSION["tariff"]);
			// IF FIND RATE
			if ($resfindrate!=0) {				
				$res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($A2B, $A2B->credit);
				if ($res_all_calcultimeout) {						

				    // MAKE THE CALL
				    $channeloutcid = $RateEngine->rate_engine_performcall(false, $A2B -> dnid, $A2B);
				    if ($channeloutcid) {
					$channel = $channeloutcid[0];
					$exten = $virtcalling;
					$context = $A2B -> config["callback"]['context_callback'];
					$id_server_group = $A2B -> config["callback"]['id_server_group'];
					$priority=1;
					$timeout = $A2B -> config["callback"]['timeout']*1000;
					$timeoutbefore = $A2B -> config["callback"]['sec_wait_before_callback'];
					$application='';
					if ($channeloutcid[1]) $callerid = $channeloutcid[1];
					    else $callerid = $A2B -> config["callback"]['callerid'];
					CustomerInput::check('callerid', $callerid);
					if (!preg_match('/\A[^<>]*<[^<>]+>[ \t]*\z/', $callerid)) {
						$callerid .= "<".$callerid.">";
					}
					$account = $_SESSION["pr_login"];
					
					$uniqueid 	=  MDP_NUMERIC(5).'-'.MDP_STRING(7);
					$status = 'PENDING';
					$server_ip = 'localhost';
					$num_attempt = 0;
					
					$sep = ($A2B->config['global']['asterisk_version'] == "1_2" || $A2B->config['global']['asterisk_version'] == "1_4")?"|":",";
					$variable = "CALLED=".$A2B->dnid.$sep."CALLING=".$virtcalling.$sep."CBID=".$uniqueid.$sep."LEG=".$A2B->cardnumber.$sep."RATECARD=".
						$RateEngine->ratecard_obj[$channeloutcid[4]][6].$sep."TRUNK=".$channeloutcid[2].$sep."TD=".$channeloutcid[3];
					
					$QUERY = " INSERT INTO cc_callback_spool (uniqueid, status, server_ip, num_attempt, channel, exten, context, priority," .
							 " variable, id_server_group, callback_time, account, callerid, timeout, next_attempt_time, exten_leg_a, leg_a) " .
							 " VALUES ('$uniqueid', '$status', '$server_ip', '$num_attempt', '$channel', '$exten', '$context', '$priority'," .
							 " '$variable', '$id_server_group', ADDTIME(now(),SEC_TO_TIME($timeoutbefore)), '$account', ?," .
							 " '$timeout', ADDTIME(now(),SEC_TO_TIME($timeoutbefore)), '$A2B->dnid', '$called')";
					$res = $A2B -> DBHandle -> Execute($QUERY, array($callerid));
					
					if (!$res) {
						$error_msg= gettext("Cannot insert the callback request in the spool!");
					} else {
						$error_msg = gettext("Your callback request has been queued correctly!");
						$color_msg = 'green';
/*						if (!$A2B -> CC_TESTING) {
							$QUERY = "UPDATE cc_trunk SET inuse=inuse+1 WHERE id_trunk=".$channeloutcid[2];
							$res = $A2B -> DBHandle -> Execute($QUERY);
							if ($res) $endinuse = true;
						}
*/					}
				    } else $error_msg = gettext("Error : Sorry, not enough free trunk for make call. Try again later!");
				} else $error_msg = gettext("Error : You don t have enough credit to call you back!");
			} else $error_msg = gettext("Error : There is no route to call back your phonenumber!");
	} else $error_msg = gettext("Error : You have to specify your phonenumber and the number you wish to call!");
}

if ($endinuse) {
	set_time_limit(0);
	ignore_user_abort(true);
	ob_start();
}

$customer = $_SESSION["pr_login"];


$smarty->display( 'main.tpl');

echo $CC_help_callback;

if ($calling == '' && $called == '') {
	$QUERY = "SELECT leg_a, exten FROM cc_callback_spool WHERE account = $A2B->cardnumber AND exten_leg_a != '' AND leg_a != '' AND exten NOT LIKE 'RINGUP' ORDER BY id DESC LIMIT 1";
	$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
	if (is_array($result)) {
		$called 	= $result[0][0];
		$calling	= $result[0][1];
		$QUERY = "SELECT regexten FROM cc_sip_buddies
				LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
				LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->card_id} ) AS v ON v.concat_id = bb.concat_id
				WHERE (id_cc_card = {$A2B->card_id} OR v.concat_id IS NOT NULL) AND (regexten = '{$called}' OR name = '{$called}') LIMIT 1";
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		if (is_array($result) && $result[0][0]) {
			$called	= $result[0][0];
		}
		$QUERY = "SELECT regexten FROM cc_sip_buddies
				LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
				LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->card_id} ) AS v ON v.concat_id = bb.concat_id
				WHERE (id_cc_card = {$A2B->card_id} OR v.concat_id IS NOT NULL) AND (regexten = '{$calling}' OR name = '{$calling}') LIMIT 1";
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		if (is_array($result) && $result[0][0]) {
			$calling = $result[0][0];
		}
	}
}

?>
<br>
<center>
 <font class="fontstyle_007"> 
 <font face='Arial, Helvetica, sans-serif' size='2' color='<?php echo $color_msg; ?>'><b>
 	<?php echo $error_msg ?> 
 </b></font>
 <br><br>
  <?php echo gettext("You can initiate the callback by entering your phonenumber and the number you wish to call!");?>
  </font>
  </center>
  <center>
   <table class="callback_maintable" align="center">
	<form name="theForm" action=<?php echo $PHP_SELF;?> method="POST" >
	<INPUT type="hidden" name="callback" value="1">
	<tr class="bgcolor_001">
	<td align="right" valign="bottom">
			<br/>
			<font class="fontstyle_007"><?php echo gettext("Your Phone Number");?> :</font>
			<input class="form_input_text" name="called" value="<?php echo $called; ?>" size="30" maxlength="40">&nbsp;
			<br/><br/>
			<font class="fontstyle_007"><?php echo gettext("The number you wish to call");?> :</font>
			<input class="form_input_text" name="calling" value="<?php echo $calling; ?>" size="30" maxlength="40">&nbsp;
			<br/><br/>
		</td>	
		<td align="center" valign="middle"> 
		<input class="form_input_button"  value="[ <?php echo gettext("Click here to Place Call");?> ]" type="submit"> 
		</td>
	</tr>
	</form>
   </table>
  </center>
  <br>
<br></br><br></br>
<?php

$smarty->display( 'footer.tpl');

$length = ob_get_length();
if ($endinuse) {
    header("Connection: close");
    header("Content-Length: " . $length);
    header("Content-Encoding: none");
    header("Accept-Ranges: bytes");
    ob_end_flush();
    ob_flush();
    flush();
    sleep(15);
    $QUERY = "UPDATE cc_trunk SET inuse=inuse-1 WHERE id_trunk=".$channeloutcid[2];
    $res = $A2B -> DBHandle -> Execute($QUERY);
}
