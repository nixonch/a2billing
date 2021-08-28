#!/usr/bin/php -q
<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,   
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 * 
 * @copyright   Copyright (C) 2004-2011 - Star2billing S.L. 
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

declare(ticks = 1);
if (function_exists('pcntl_signal')) {
	pcntl_signal(SIGHUP, SIG_IGN);
}

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));

require_once (dirname(__FILE__)."/lib/vendor/autoload.php");
include (dirname(__FILE__)."/lib/Class.Table.php");
include (dirname(__FILE__)."/lib/Class.A2Billing.php");
include (dirname(__FILE__)."/lib/Class.RateEngine.php");
include (dirname(__FILE__)."/lib/phpagi/phpagi.php");
include (dirname(__FILE__)."/lib/phpagi/phpagi-asmanager.php");
include (dirname(__FILE__)."/lib/Misc.php");
include (dirname(__FILE__)."/lib/interface/constants.php");

$charge_callback = 0;
$G_startime = time();
$agi_version = "A2Billing - Version 1.9.4 (Cuprum)";

if ($argc > 1 && ($argv[1] == '--version' || $argv[1] == '-v')) {
	echo "$agi_version\n";
	exit;
}


/********** 	 CREATE THE AGI INSTANCE + ANSWER THE CALL		**********/
$agi = new AGI();
$agi-> set_variable('_PARENTCHANNAME', $agi -> get_variable("CDR(channel)", true));

$optconfig = array();
if ($argc > 1 && strstr($argv[1], "+")) {
    /*
    This change allows some configuration overrides on the AGI command-line by allowing the user to add them after the configuration number, like so:
    exten => 0312345678,3,AGI(a2billing.php,"1+use_dnid=0&extracharge_did=12345")
    */
    //check for configuration overrides in the first argument
    $idconfig = substr($argv[1], 0, strpos($argv[1],"+"));
    $configstring = substr($argv[1], strpos($argv[1],"+")+1);
    
    foreach (explode("&",$configstring) as $conf) {
        $var = substr($conf, 0, strpos($conf,"="));
        $val = substr($conf, strpos($conf,"=")+1);
        $optconfig[$var]=$val;
    }
}elseif ($argc > 1 && is_numeric($argv[1]) && $argv[1] >= 0) {
	$idconfig = $argv[1];
} else {
	$idconfig = 1;
}

if ($dynamic_idconfig = intval($agi -> get_variable("IDCONF", true))) {
	$idconfig = $dynamic_idconfig;
}

if ($argc > 2 && strlen($argv[2]) > 0) {
	switch($argv[2])
	{
	    case 'did': 			$mode = 'did'; break;
	    case 'sms': 			$mode = 'sms'; break;
	    case 'callback':			$mode = 'callback'; break;
	    case 'cid-callback':		$mode = 'cid-callback'; break;
	    case 'cid-prompt-callback': 	$mode = 'cid-prompt-callback'; break;
	    case 'all-callback':		$mode = 'all-callback'; break;
	    case 'voucher':			$mode = 'voucher'; break;
	    case 'campaign-callback':		$mode = 'campaign-callback'; break;
	    case 'conference-moderator':	$mode = 'conference-moderator'; break;
	    case 'conference-member':		$mode = 'conference-member'; break;
	    case 'auto-did-callback-cid':	$mode = 'auto'; break;
	    case 'auto':			$mode = 'auto'; break;
	    default:				$mode = 'standard'; break;
	}
} else $mode = 'standard';

$A2B = new A2Billing();
$A2B -> load_conf($agi, NULL, 0, $idconfig, $optconfig);
$A2B -> mode = $mode;
//$A2B -> G_startime = $G_startime;


$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "IDCONFIG : $idconfig");
$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "MODE : $mode");


// get the area code for the cid-callback, all-callback and cid-prompt-callback
$caller_areacode = ($argc > 3 && strlen($argv[3]) > 0) ? $argv[3] : "";
if ($argc > 4 && strlen($argv[4]) > 0) {
	$groupid = $A2B -> group_id = $argv[4];
	$A2B -> group_mode = true;
} else $groupid = "";
$cid_1st_leg_tariff_id = ($argc > 5 && strlen($argv[5]) > 0) ? $argv[5] : "";

$A2B -> CC_TESTING = isset($A2B -> agiconfig['debugshell']) && $A2B -> agiconfig['debugshell'];
//$A2B -> CC_TESTING = true;
$A2B -> recalltime = false;

define ("DB_TYPE", isset($A2B->config["database"]['dbtype'])?$A2B->config["database"]['dbtype']:null);
define ("SMTP_SERVER", isset($A2B->config['global']['smtp_server'])?$A2B->config['global']['smtp_server']:null);
define ("SMTP_HOST", isset($A2B->config['global']['smtp_host'])?$A2B->config['global']['smtp_host']:null);
define ("SMTP_USERNAME", isset($A2B->config['global']['smtp_username'])?$A2B->config['global']['smtp_username']:null);
define ("SMTP_PASSWORD", isset($A2B->config['global']['smtp_password'])?$A2B->config['global']['smtp_password']:null);
define ("SMTP_PORT", isset($A2B->config['global']['smtp_port'])?$A2B->config['global']['smtp_port']:'25');
define ("SMTP_SECURE", isset($A2B->config['global']['smtp_secure'])?$A2B->config['global']['smtp_secure']:null);
define ("BASE_CURRENCY", isset($A2B->config['global']['base_currency'])?$A2B->config['global']['base_currency']:null);

// TEST DID
// if ($A2B -> CC_TESTING) $mode = 'did';

// Print header
$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "AGI Request:\n".print_r($agi -> request, true));

$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[INFO : $agi_version]");

/* GET THE AGI PARAMETER */
$A2B -> get_agi_request_parameter ($agi);

if (!$A2B -> DbConnect()) {
	$agi -> stream_file('prepaid-final', '#');
	exit;
}

define ("WRITELOG_QUERY", true);
$instance_table = new Table();
$A2B -> set_instance_table ($instance_table);

$startUpSystem = $agi -> get_variable('ASTUPTIME', true);
$startUpOS = $agi -> get_variable('SYSUPTIME', true);
if ($startUpSystem == '') {
	define ("MANAGER_HOST", isset($A2B->config['global']['manager_host'])?$A2B->config['global']['manager_host']:null);
	define ("MANAGER_USERNAME", isset($A2B->config['global']['manager_username'])?$A2B->config['global']['manager_username']:null);
	define ("MANAGER_SECRET", isset($A2B->config['global']['manager_secret'])?$A2B->config['global']['manager_secret']:null);
	$as = new AGI_AsteriskManager();
	$res =@ $as->connect(MANAGER_HOST,MANAGER_USERNAME,MANAGER_SECRET);
	if ($res) {
	    $res = $as->send_request('Command',array('Command'=>'core show uptime seconds'));
	    $as->disconnect();
//	    $nv = rtrim(array_pop(explode(' ',$res['data'])));
	    preg_match('/\d+$/', $res['data'], $uptime);
	    $startUpSystem = time() - $uptime[0];
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, $startUpSystem);
	} else {
	    $uptime = exec("cat /proc/uptime");
	    if ($uptime == '') {
		$uptime = explode(" ", exec("/sbin/sysctl -n kern.boottime"));
		$startUpSystem = str_replace( ",", "", $uptime[3]);
	    } else {
		$uptime = explode(" ", $uptime);
		$startUpSystem = time() - $uptime[0];
	    }
	}
	unset($as);
}
//$A2B->DBHandle->Execute("SET NAMES 'UTF8'");
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "StartUpSystem=".$startUpSystem);
if ($startUpSystem && $startUpOS == "") {
	$startUpTime = $A2B->config['global']['startup_time'];
	if ($startUpTime == 0) {
		$QUERY = "UPDATE cc_config SET config_value=$startUpSystem WHERE config_key='startup_time' AND config_group_title='global'";
		$A2B -> DBHandle -> Execute($QUERY);
		if (is_dir(MONITOR_PATH)) {
		    if ($dh = opendir(MONITOR_PATH)) {
			while (($value_de = readdir($dh)) != false) {
			    $dl_short = MONITOR_PATH . "/" . $value_de;
			    if (is_dir($dl_short)) {
				$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, $dl_short);
				continue;
			    }
			    $parts = pathinfo($value_de);
			    $value = $parts['filename'];
			    $QUERY = "SELECT cc_card.username, YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime) FROM cc_call LEFT JOIN cc_card ON cc_card.id=card_id WHERE uniqueid LIKE '$value' ORDER BY cc_call.id DESC LIMIT 1";
			    $result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
			    if (is_array($result) && count($result)>0) {
				$dl_full = MONITOR_PATH;
				for ($i = 0; $i < 4; $i++) {
				    $dl_full .= "/" . $result[0][$i];
				    if (!file_exists($dl_full)) mkdir($dl_full);
				}
				rename($dl_short, $dl_full . "/" . $value_de);
			    }
			}
			closedir($dh);
		    }
		}
	} elseif ($startUpSystem > $startUpTime+30) {
		$QUERY = "UPDATE cc_config SET config_value=$startUpSystem WHERE config_key='startup_time' AND config_group_title='global'";
		$A2B -> DBHandle -> Execute($QUERY);
		$QUERY = "UPDATE cc_card, cc_trunk, cc_did_destination SET cc_card.inuse=0, cc_trunk.inuse=0, cc_did_destination.destinuse=0";
		$A2B -> DBHandle -> Execute($QUERY);
		$QUERY = "UPDATE cc_callback_spool SET status='PENDING', last_attempt_time='1980-01-01 00:00:00', next_attempt_time=NOW() WHERE surveillance > 0 AND agi_result='AGI PROCESSING'";
		$A2B -> DBHandle -> Execute($QUERY);
	}
}

//GET CURRENCIES FROM DATABASE
$QUERY =  "SELECT id, currency, name, value FROM cc_currencies ORDER BY id";
$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY, 1, 300);

if (is_array($result)) {
	$num_cur = count($result);
	for ($i=0;$i<$num_cur;$i++) {
		$currencies_list[$result[$i][1]] = array (1 => $result[$i][2], 2 => $result[$i][3]);
	}
}

$RateEngine = new RateEngine();

if ($A2B -> CC_TESTING) {
	$RateEngine->debug_st = 1;
	$accountcode = '2222222222';
}

$callbacksound = '';

if ($mode == 'sms') {
//	$A2B -> Reinit();
	$messfrom = addslashes($caller_areacode);
	$messto   = addslashes($groupid);
	$body	  = addslashes($cid_1st_leg_tariff_id);
//	$messbody = str_replace("'", "\'", $body);
//	$accountcode = $A2B->dnid;
	$QUERY =  "SELECT id FROM cc_card WHERE username='{$A2B->dnid}' LIMIT 1";
	$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
	if (is_array($result)) {
		$card_id = $result[0][0];
		$QUERY	 = "SELECT id, smstext FROM cc_sms_log WHERE id_card_to=$card_id AND fromnum LIKE '$messfrom' AND tonum LIKE '$messto' AND TIMESTAMPDIFF(SECOND,receivedtime,NOW())<31 ORDER BY id DESC LIMIT 1";
		$result  = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
	} else	$card_id = $result = 0;
	if (is_array($result)) {
		$QUERY = "UPDATE cc_sms_log SET receivedtime=NOW(), smstext=CONCAT(smstext,'$body') WHERE id={$result[0][0]}";
		$A2B -> DBHandle -> Execute($QUERY);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "========= !!! UPDATE !!! ============");
		$body = $result[0][1].$body;
	} else {
		$QUERY = "INSERT INTO cc_sms_log (id_card_from, id_card_to, fromnum, tonum, smstext) VALUES ($card_id, $card_id, '$messfrom', '$messto', '$body')";
		$A2B -> DBHandle -> Execute($QUERY);
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, "SELECT LAST_INSERT_ID()");
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "LAST INSERT ID: ".$result[0][0]);
	}
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "FROM: ".$messfrom);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "TO: ".$messto);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "BODY: ".$body);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "Parent: ".posix_getppid()." Current: ".getmypid());
/**	$A2B -> DbDisconnect();
//	$agi -> disconnect();
//	$agi -> hangup();
//	pcntl_signal(SIGHUP, SIG_IGN);
//	posix_setsid();
//	$parent=getmypid();
//	set_time_limit(0);
	declare(ticks = 1);
//	pcntl_signal(SIGHUP, ));
	$pid = pcntl_fork();
	if($pid == -1) {
		exit(2);
	} elseif ($pid) {
//		$A2B -> DbConnect($agi);
//		pcntl_wait($status, WNOHANG);
//		pcntl_signal_dispatch();
//		register_shutdown_function(create_function('$ppp', 'posix_kill(getmypid(), SIGKILL);', array()));
//		pcntl_signal(SIGCHLD,SIG_DFL);
//		$agi -> hangup();
//		posix_kill(getmypid(),9);
//		posix_kill(posix_getppid(),SIGCHLD);
//		posix_kill(posix_getpid(),SIGHUP);
//		wait(NULL);
		exit(0);
	} else {
//		pcntl_signal(SIGCHLD,SIG_DFL);
		posix_setsid();
		$pid = pcntl_fork();
		if($pid==-1) {
			exit(2);
		} elseif ($pid) {
//			pcntl_wait($status, WNOHANG);
			exit(0);
		}
//		posix_kill(posix_getppid(),SIGHUP);
//		ob_start();
//		umask(0);
//		posix_setsid();
//		register_shutdown_function(create_function('', 'ob_end_clean();posix_kill(getmypid(), SIGKILL);'));
		sleep(15);
		$A2B -> DbConnect($agi);
		$A2B -> set_instance_table ($instance_table);
		$QUERY = "SELECT id FROM cc_sms_log WHERE id={$result[0][0]} AND smstext LIKE '$body'";
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		if (is_array($result)) {
		} else {
			$A2B -> DBHandle -> Execute("UPDATE cc_sms_log SET sent=sent+1 WHERE id={$result[0][0]}");
		}
		$A2B -> DbDisconnect();
		exit(0);
	}
**/
} elseif ($mode == 'auto') {

	$A2B-> Reinit();

	$mydnid = rtrim($agi -> request['agi_extension'], "#");
	$didyes = $diddest = false;

	if (strlen($mydnid) > 0){
	    $QUERY = "SELECT areaprefix, citylength, countryprefix, cc_did.id, cc_did_destination.activated, callbacksound, answer, callbackprefixallow, secondtimedays, iduser FROM cc_country, cc_did
".			"LEFT JOIN cc_did_destination ON id_cc_did=cc_did.id
".			"WHERE cc_did.activated=1 AND did LIKE '$mydnid' AND startingdate<=CURRENT_TIMESTAMP AND (expirationdate>CURRENT_TIMESTAMP OR expirationdate IS NULL";
	    // if MYSQL
	    if ($A2B->config["database"]['dbtype'] != "postgres") $QUERY .= " OR expirationdate = '0000-00-00 00:00:00'";
	    $QUERY .= ") AND cc_country.id=id_cc_country ORDER BY cc_did_destination.activated DESC LIMIT 1";
	    $result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
	    if (is_array($result)) {
		$didyes = true;
		$did_id = $result[0][3];
		$diddest = $result[0][4];
		$callbacksound = ($result[0][5]=="-1")?"":$result[0][5];
		$callbackanswer = $result[0][6];
		$callbackprefixallow = explode(",",$result[0][7]);
		$secondtimedays = $result[0][8];
		$did_user_id = $result[0][9];
		$A2B -> CID_handover = $A2B->CallerID = $A2B->did_apply_add_countryprefixfrom($result[0], $A2B->CallerID);
		if ($A2B->CallerID != $agi -> request['agi_callerid'])
			$agi -> set_callerid($A2B -> CallerID);
		$QUERY = "SELECT 1 FROM cc_did, cc_callerid WHERE cc_did.id = $did_id AND cid LIKE '$A2B->CallerID' AND cc_callerid.activated = 't' AND ((id_cc_card = iduser AND allciduse <> 3) OR iduser = 0 OR allciduse = 1 OR allciduse = 4) LIMIT 1";
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		if (is_array($result) && count($result) > 0) {
			$didyes = false;
		}
	    }
	    if ($didyes) {
		if ($caller_areacode == 'recalldidless') {
			exit(0);
		}
/**		$QUERY="SELECT IF(src=src_exten,src_peername,src) src, cc_card.username, cc_card.recalltime, continuewithdid FROM cc_card
".			"INNER JOIN cc_call ON starttime > DATE_SUB(NOW(), INTERVAL recalldays DAY) AND card_id = cc_card.id
".			"INNER JOIN cc_did ON cc_did.id_trunk = cc_call.id_trunk
".			"WHERE did LIKE '$mydnid' AND calledstation LIKE '$A2B->CallerID' AND LENGTH(calledstation) > 6 ORDER BY cc_call.id DESC LIMIT 1";
**/		$QUERY="SELECT IF(od.src=od.src_exten,od.src_peername,od.src) src, cc_card.username, cc_card.recalltime, continuewithdid FROM cc_card
".			"INNER JOIN (SELECT id, card_id, src, src_exten, src_peername, id_trunk, starttime FROM cc_call WHERE calledstation LIKE '$A2B->CallerID' AND LENGTH(calledstation) > 6 AND sipiax <> 4) AS od ON od.starttime > DATE_SUB(NOW(), INTERVAL recalldays DAY) AND od.card_id = cc_card.id
".			"INNER JOIN cc_did ON cc_did.id_trunk = od.id_trunk
".			"WHERE did LIKE '$mydnid' ORDER BY od.id DESC LIMIT 1";
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		if (is_array($result) && count($result) > 0) {
			$RateEngine->Reinit();
			$A2B -> agiconfig['answer_call'] = 0;
			$A2B -> agiconfig['play_audio'] = 0;
			$A2B -> agiconfig['use_dnid'] = 1;
			$A2B -> destination = $A2B -> extension = preg_replace('/\+/','',$result[0][0]);
			$accountback = $result[0][1];
			$A2B -> recalltime = $result[0][2];
			$cia_res = $A2B -> callingcard_ivr_authenticate($agi,$accountback);
			if ($cia_res==0) {
				// RE-SET THE CALLERID
				$A2B->callingcard_auto_setcallerid($agi);

				// Feature to switch the Callplan from a customer : callplan_deck_minute_threshold
				$A2B-> deck_switch($agi);

				if (!$A2B -> enough_credit_to_call()) exit(0);

				if ($A2B->agiconfig['sip_iax_friends']==1) {
				    if ( (strlen($A2B -> destination)>0)
					&& ( strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix']) > 0)
					&& (strncmp($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix'], $A2B -> destination,strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix'])) == 0) ) {

					    $A2B -> sip_iax_buddy = $A2B->agiconfig['sip_iax_pstn_direct_call_prefix'];
					    $A2B -> dnid = substr($A2B -> dnid,strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix']));
				    }
				}
				if ( strlen($A2B-> sip_iax_buddy) > 0 || ($A2B-> sip_iax_buddy == $A2B->agiconfig['sip_iax_pstn_direct_call_prefix'])) {
					$cia_res = $A2B-> call_sip_iax_buddy($agi, $RateEngine, 0);
				} else {
					$ans = $A2B-> callingcard_ivr_authorize($agi, $RateEngine, 0, 0);

					// PERFORM THE CALL
					$result_callperf = $RateEngine->rate_engine_performcall($agi, $A2B -> destination, $A2B);

					// INSERT CDR  & UPDATE SYSTEM
					$RateEngine->rate_engine_updatesystem($A2B, $agi, $A2B -> destination);
					if ($result[0][3] && $RateEngine->dialstatus != "ANSWER") {
						$A2B -> mode = $mode = 'did';
						$A2B -> agiconfig['cid_enable']=0;
						$A2B -> agiconfig['number_try']=1;
					} elseif (!$result_callperf) {
						$prompt="prepaid-dest-unreachable";
						$agi-> stream_file($prompt, '#');
					}
				}
			}

		} elseif ($caller_areacode == 'didless') {
		    exit(0);
		} elseif ($diddest){
		    $A2B -> agiconfig['answer_call']=0;
		    $QUERY="SELECT calledstation FROM cc_call ".
				"LEFT JOIN cc_callerid ON cid LIKE src AND id_cc_card = card_id ".//AND activated = 't'
				"WHERE src LIKE '$A2B->CallerID' AND cid IS NULL ".
					"AND ((sipiax IN (0,2,3) AND starttime > DATE_SUB(NOW(), INTERVAL '$secondtimedays' DAY) AND sessiontime>20) OR ((sipiax=7 OR sipiax=4) AND starttime > DATE_SUB(NOW(), INTERVAL '2' MINUTE))) ".
				"ORDER BY starttime DESC LIMIT 1";
		    $result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		    foreach ($callbackprefixallow as $value) {
			$A2B -> prefixallow = false; // Пропускаем без защитного IVR если колбек (SOUND BEFORE CALLBACK -> NOT ACTIVE) запрещен в принципе, но защитное IVR разрешено
			if ($value == "" || strpos($A2B->CallerID,$value)===0) {
			    $A2B -> prefixallow = true; // Если префикс калерайди (Before All-Callback -> ALLOW PREFIXES) соответствует заданному, разрешаем колбек
			    break;
			}
		    }
		    if ($callbacksound && strlen($A2B->CallerID)>1 && is_numeric($A2B->CallerID) && !(is_array($result) && count($result) > 0) && $A2B -> prefixallow) {
			$accountcode = $agi -> get_variable("CHANNEL(accountcode)", true);

			$QUERY = "SELECT IF(status='SENT',0,id) FROM cc_callback_spool WHERE (status='PENDING' OR status='PROCESSING' OR status='SENT') AND account='$accountcode' AND callerid='".$A2B->config['callback']['callerid']."' AND exten_leg_a='{$A2B->CallerID}' AND timediff(now(),entry_time)<".$A2B->config['callback']['sec_avoid_repeate'];
			$result = $A2B -> instance_table -> SQLExec($A2B -> DBHandle, $QUERY);
			if (is_array($result)) {
				if ($result[0][0]) {
					$sec_wait_before_callback = $A2B -> config["callback"]['sec_wait_before_callback'];
					if (!is_numeric($sec_wait_before_callback) || $sec_wait_before_callback<1) {
						$sec_wait_before_callback = 2;
					}
					$QUERY = "UPDATE cc_callback_spool SET num_attempt='0', entry_time=now(), `next_attempt_time`=ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND ) WHERE id={$result[0][0]}";
					$A2B -> DBHandle -> Execute($QUERY);
					$agi -> exec('HangUp', 29);
					write_log(LOGFILE_API_CALLBACK, " ==========UPDATE======== Attempt of double number insert detected =====================> ".$A2B->CallerID);
					exit(0);
				} else {
					$agi -> exec('HangUp', 29);
					write_log(LOGFILE_API_CALLBACK, " ==========CANCEL======== Attempt of double number insert detected =====================> ".$A2B->CallerID);
					exit(0);
				}
			}

			if ($callbackanswer) {
			    if ($A2B -> G_startime == 0)
				$A2B -> G_startime = time();
			    $agi -> answer();
			} else {
			    $A2B -> let_stream_listening($agi, true);
			    $agi -> exec('Playtones ring');
			    sleep(2);
			}
			$res_dtmf = $agi->get_data($callbacksound, 1, 1);
			$A2B -> mode = $mode = 'all-callback';
			$A2B -> config["callback"]['extension'] = $mydnid;
		    } else {
			$A2B -> mode = $mode = 'did';
			$A2B -> agiconfig['cid_enable']=0;
			$A2B -> agiconfig['use_dnid']=1;
			$A2B -> agiconfig['number_try']=1;
		    }
		} elseif ($A2B -> agiconfig['cid_auto_create_card']==1) {
		    $A2B -> mode = $mode = 'standard';
		    $A2B -> agiconfig['cid_enable']=1;
		    $A2B -> agiconfig['answer_call']=1;
		    $A2B -> agiconfig['use_dnid']=0;
		}
	    } else {
		$QUERY = "SELECT callback, phonenumber, username, verify, allciduse FROM cc_callerid, cc_card, cc_did WHERE cc_did.id = $did_id AND cid LIKE '$A2B->CallerID' AND cc_callerid.activated='t' AND status=1 AND cc_card.id=id_cc_card LIMIT 1";
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
		if (is_array($result)) {
		    $A2B -> agiconfig['cid_enable'] = 1;
		    $A2B -> cardnumber = $result[0][2];
		    if ($result[0][0] == 1 && $result[0][4] < 3) {
			$A2B -> mode = $mode = 'cid-callback';
			$caller_areacode = "";
		    } else {
			$A2B -> mode = $mode = 'standard';
			if ($result[0][1] != "") {
			    $agi -> request['agi_extension'] = preg_replace('/\+/','',$result[0][1]);
			    $A2B -> agiconfig['use_dnid']=1;
			    $A2B -> agiconfig['number_try']=1;
			    $A2B -> cid2num = true;
			} else {
			    if ($result[0][3]==0) {
				$A2B -> cid_verify = false;
			    }
			    $A2B -> agiconfig['answer_call']=1;
			    $A2B -> agiconfig['use_dnid']=0;
			}
		    }
		} else {
		    $A2B -> debug( ERROR, $agi, __FILE__, __LINE__, '[No DID or CallerID found for this call]');
		    exit(0);
		}
	    }
	} else {
		$A2B -> debug(FATAL, $agi, __FILE__, __LINE__, "'agi_extension' can not be empty");
		exit(0);
	}
}

if ($mode == 'standard') {

	if ($A2B -> agiconfig['answer_call']==1) {
		$A2B -> G_startime = time();
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[ANSWER CALL]');
		$agi -> answer();
//		$status_channel=AST_STATE_UP;
	} else {
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[NO ANSWER CALL]');
//		$status_channel=AST_STATE_RING;
	}

	$A2B -> play_menulanguage ($agi);

	// Play intro message
	if (strlen($A2B -> agiconfig['intro_prompt'])>0) {
		$A2B -> let_stream_listening($agi);
		$agi -> stream_file($A2B -> agiconfig['intro_prompt'], '#');
	}
	
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[START TRY : callingcard_ivr_authenticate]");
	$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[END TRY   : callingcard_ivr_authenticate]");
	
//	$A2B->card_caller = $A2B->id_card;
	
	// CALL AUTHENTICATE AND WE HAVE ENOUGH CREDIT TO GO AHEAD
	if ($cia_res==0) {

		// RE-SET THE CALLERID
		$A2B->callingcard_auto_setcallerid($agi);
		
		for ($i=0;$i< $A2B -> agiconfig['number_try'] ;$i++) {
            
			$RateEngine -> Reinit();
			$A2B -> Reinit();

			// RETRIEVE THE CHANNEL STATUS AND LOG : STATUS - CREDIT - MIN_CREDIT_2CALL
			$stat_channel = $agi -> channel_status($A2B-> channel);
			$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[CHANNEL STATUS : '.$stat_channel["result"].' = '.$stat_channel["data"].']'.
						   "\n[CREDIT : ".$A2B-> credit."][CREDIT MIN_CREDIT_2CALL : ".$A2B -> agiconfig['min_credit_2call']."]");
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, '[CHANNEL STATUS : '.$stat_channel["result"].' = '.$stat_channel["data"].']'.$A2B-> channel."\n[CREDIT : ".$A2B-> credit."][CREDIT MIN_CREDIT_2CALL : ".$A2B -> agiconfig['min_credit_2call']."]");
			
			// CHECK IF THE CHANNEL IS UP
//			if (($A2B -> agiconfig['answer_call']==1) && ($stat_channel["result"]!=$status_channel) && ($A2B -> CC_TESTING!=1))
			if (array_search($stat_channel["result"], array(AST_STATE_UP, AST_STATE_RING)) === false && $A2B -> CC_TESTING!=1) {
				if ($A2B->set_inuse_username) $A2B->callingcard_acct_start_inuse($agi,0);
				$A2B -> write_log("[STOP - EXIT]", 0);
				break;
			}

            if ($A2B -> agiconfig['ivr_enable_locking_option'] == 1) {
                $QUERY = "SELECT block, lock_pin FROM cc_card WHERE username = '{$A2B->username}'";
                $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY] : " . $QUERY );
                $result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);

                // Check if the locking option is enabled for this account
                if ($result[0][0] == 1 && strlen($result[0][1]) > 0) {
                    $try = 0;
                    do {
                        $return = FALSE;
                        $res_dtmf = $agi -> get_data('prepaid-enter-pin-lock', 3000, 10, '#'); //Please enter your locking code
                        if ($res_dtmf['result'] != $result[0][1]) {
                            $agi -> say_digits($res_dtmf['result']);
                            if (strlen($res_dtmf['result']) > 0) {
				$A2B -> let_stream_listening($agi);
                                $agi -> stream_file('prepaid-no-pin-lock', '#');
                            }
                            $try++;
                            $return = TRUE;
                        }
                        if ($try > 3) {
                            if ($A2B->set_inuse_username)
                                $A2B -> callingcard_acct_start_inuse($agi,0);
                            $agi -> hangup();
                            exit();
                        }
                    } while ($return);
                }
            }
            
			// Feature to switch the Callplan from a customer : callplan_deck_minute_threshold 
			$A2B-> deck_switch($agi);

			if( !$A2B -> enough_credit_to_call() && $A2B -> agiconfig['jump_voucher_if_min_credit']==1) {

				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - Refill with vouchert]");
				$vou_res = $A2B -> refill_card_with_voucher($agi,2);
				if ($vou_res==1) {
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[ADDED CREDIT - refill_card_withvoucher Success] ");
				} else {
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - refill_card_withvoucher fail] ");
				}
			}
			
			if ($A2B -> agiconfig['ivr_enable_account_information'] == 1) {
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, " [GET ACCOUNT INFORMATION]" );
					$res_dtmf = $agi -> get_data('prepaid-press4-info', 5000, 1, '#'); //Press 4 to get information about your account
					if ($res_dtmf ['result'] == "4") {

                        $QUERY = "SELECT UNIX_TIMESTAMP(c.lastuse) as lastuse, UNIX_TIMESTAMP(c.lock_date) as lock_date, UNIX_TIMESTAMP(c.firstusedate) as firstuse
									FROM cc_card c
									WHERE username = '{$A2B->username}'
									LIMIT 1";
						$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
						$card_info = $result[0];
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY] : " . $QUERY );

                        if (is_array($card_info)) {
                            $try = 0;
                            do {
                                $try++;
                                $return = FALSE;

                                //================================================================================================================
                                //= INFORMATION MENU
                                //================================================================================================================
                                $info_menu['1'] = 'prepaid-press1-listen-lastcall'; //Press 1 to listen the time and duration of the last call
                                $info_menu['2'] = 'prepaid-press2-listen-accountlocked'; //Press 2 to time and date when the account last has been locked
                                $info_menu['3'] = 'prepaid-press3-listen-firstuse'; //Press 3 to date of when the account was first in use
                                $info_menu['9'] = 'prepaid-press9-listen-exit-infomenu'; //Press 9 to exit information menu
                                $info_menu['*'] = 'prepaid-pressdisconnect'; //Press * to disconnect
                                //================================================================================================================
                                $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[INFORMATION MENU]" );
                                $res_dtmf = $agi -> menu($info_menu, 5000);

                                switch ($res_dtmf) {
                                    case 1 :

                                        $QUERY = "SELECT starttime FROM cc_call
                                                    WHERE card_id = {$A2B->id_card} ORDER BY starttime DESC LIMIT 1";
                                        $result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
                                        $lastcall_info = $result[0];
					$A2B -> let_stream_listening($agi);
                                        if (is_array($lastcall_info)) {
                                            $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[INFORMATION MENU]:[OPTION 1]" );
                                            $agi -> stream_file('prepaid-lastcall', '#'); //Your last call was made
                                            $agi -> exec("SayUnixTime {$card_info['lastuse']}");
                                            $agi -> stream_file('prepaid-call-duration', '#'); //the duration of the call was
                                            $agi -> say_number($card_info['sessiontime']);
                                            $agi -> stream_file('seconds', '#');
                                        } else {
                                            $agi -> stream_file('prepaid-no-call', '#'); //No call has been made
                                        }
                                        $return = TRUE;
                                    break;

                                    case 2 :
					$A2B -> let_stream_listening($agi);
                                        if ($card_info['lock_date']) {
                                            $agi -> stream_file('prepaid-account-has-locked', '#'); //Your Account has been locked the
                                            $agi -> exec("SayUnixTime {$card_info['lock_date']}");
                                        } else {
                                            $agi -> stream_file('prepaid-account-nolocked', '#'); //Your account is not locked
                                        }
                                        $return = TRUE;
                                    break;

                                    case 3 :
					$A2B -> let_stream_listening($agi);
                                        $agi -> stream_file('prepaid-account-firstused', '#'); //Your Account has been used for the first time the
                                        $agi -> exec("SayUnixTime {$card_info['firstuse']}");
                                        $return = TRUE;
                                    break;

                                    case 9 :
                                        $return = FALSE;
                                    break;

                                    case '*' :
					$A2B -> let_stream_listening($agi);
                                        $agi -> stream_file('prepaid-final', '#');
                                        if ($A2B->set_inuse_username)
                                            $A2B -> callingcard_acct_start_inuse($agi,0);
                                        $agi -> hangup();
                                        exit();
                                    break;

                                }
                                $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[TRY : $try]" );
                            } while($return && $try < 0);
                        }
					}
			}

			if ($A2B -> agiconfig['ivr_enable_locking_option'] == 1) {
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[LOCKING OPTION]" );
				
				$return = FALSE;
				$res_dtmf = $agi -> get_data('prepaid-press5-lock', 5000, 1, '#'); //Press 5 to lock your account
				
				if ($res_dtmf ['result'] == 5) {
                    for ($ind_lock=0 ; $ind_lock <= 3 ; $ind_lock++) {

                        $res_dtmf = $agi -> get_data('prepaid-enter-code-lock-account', 3000, 10, '#'); //Please, Enter the code you want to use to lock your
                        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[res_dtmf = ".$res_dtmf['result']."]");

                        if (strlen($res_dtmf['result']) > 0 && is_int(intval($res_dtmf['result'])) )
                            break;
                    }

                    if (strlen($res_dtmf['result']) > 0 && is_int(intval($res_dtmf['result'])) ) {

			$A2B -> let_stream_listening($agi);
                        $agi -> stream_file('prepaid-your-locking-is', '#'); //Your locking code is
                        $agi -> say_digits($res_dtmf['result']);
                        $lock_pin = $res_dtmf['result'];

                        if (strlen($lock_pin) > 0) {
                            //================================================================================================================
                            //= MENU OF LOCK
                            //================================================================================================================
                            $lock_menu['1'] = 'prepaid-listen-press1-confirmation-lock'; //Do you want to proceed and lock your account, then press 1 ?
                            $lock_menu['9'] = 'prepaid-press9-listen-exit-lockmenu'; //Press 9 to exit lock menu
                            $lock_menu['*'] = 'prepaid-pressdisconnect'; //Press * to disconnect
                            //================================================================================================================
                            $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[MENU OF LOCK]" );
                            $res_dtmf = $agi -> menu($lock_menu, 5000);

                            switch ($res_dtmf) {
                                case 1 :
                                    $QUERY = "UPDATE cc_card SET block = 1, lock_pin = '{$lock_pin}', lock_date = NOW() WHERE username = '{$A2B->username}'";
                                    $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
                                    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY]:[$QUERY]" );
				    $A2B -> let_stream_listening($agi);
                                    $agi -> stream_file('prepaid-locking-accepted', '#'); // Your locking code has been accepted
                                    $return = TRUE;
                                break;

                                case 9 :
                                    $return = FALSE;
                                break;

                                case '*' :
				    $A2B -> let_stream_listening($agi);
                                    $agi -> stream_file('prepaid-final', '#');
                                    if ($A2B->set_inuse_username)
                                        $A2B -> callingcard_acct_start_inuse($agi,0);
                                    $agi -> hangup();
                                    exit();
                                break;
                            }
                        }
                    }
                }
			}

			$A2B -> debug( INFO, $agi, __FILE__, __LINE__,  "TARIFF ID -> ". $A2B->tariff);
			$A2B -> dnid = rtrim($agi -> request['agi_dnid'], "#");
			if (is_null($A2B -> extension)) 	$A2B -> extension = rtrim($agi -> request['agi_extension'], "#");

			if ($A2B -> agiconfig['ivr_voucher']==1 && $i == 0 && $A2B -> vouchernumber == 0) {
				if ($A2B -> first_dtmf != '') {
					$A2B -> ivr_voucher = $A2B->first_dtmf;
				} else {
					$res_dtmf = $agi -> get_data('prepaid-refill_card_with_voucher', 5000, 1);
					$A2B -> ivr_voucher = $A2B -> first_dtmf = $res_dtmf ["result"];
				}
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES REFILL CARD VOUCHER DTMF : " . $A2B -> ivr_voucher);
				if (isset($A2B -> ivr_voucher) && $A2B -> ivr_voucher == $A2B -> agiconfig['ivr_voucher_prefix']) {
					$A2B -> first_dtmf = '';
					$vou_res = $A2B->refill_card_with_voucher($agi, $i);
				}
			}
			$A2B -> vouchernumber = 0;
			if ($A2B -> agiconfig['ivr_enable_ivr_speeddial']==1) {
				$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[IVR SPEED DIAL]");
				do {
					$return_mainmenu = FALSE;
					if ($A2B -> first_dtmf != '') {
						$ivr_speeddial = $A2B -> first_dtmf;
					} else {
						$res_dtmf = $agi -> get_data("prepaid-press9-new-speeddial", 5000, 1); //Press 9 to add a new Speed Dial
						$ivr_speeddial = $A2B -> first_dtmf = $res_dtmf ["result"];
					}

					if ($ivr_speeddial == 9) {
						$A2B -> first_dtmf = '';
						$try_enter_speeddial = 0;
						do {
							$try_enter_speeddial++;
							$return_enter_speeddial = FALSE;
							$res_dtmf = $agi -> get_data("prepaid-enter-speeddial", 3000, 1); //Please enter the speeddial number
							$speeddial_number = $res_dtmf['result'];
							$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "SPEEDDIAL DTMF : ".$speeddial_number);

							if (!empty($speeddial_number) && is_numeric($speeddial_number) && $speeddial_number>=0) {
								$action = 'insert';
								$QUERY = "SELECT cc_speeddial.phone, cc_speeddial.id
											FROM cc_speeddial, cc_card WHERE cc_speeddial.id_cc_card = cc_card.id
											AND cc_card.id = ".$A2B->id_card." AND cc_speeddial.speeddial = ".$speeddial_number."";
								$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, $QUERY);
								$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
								$id_speeddial = $result[0][1];
								if (is_array($result)) {
									$agi -> say_number($speeddial_number);
									$agi -> stream_file("prepaid-is-used-for", "#");
									$agi -> say_digits($result[0][0]);
										$res_dtmf = $agi -> get_data("prepaid-press1-change-speeddial", 3000, 1); //if you want to change it press 1 or an other key to enter an other speed dial number.
									if ($res_dtmf['result'] != 1) {
										$return_mainmenu = TRUE;
										break;
									} else {
										$action = 'update';
									}
								}
                                $try_phonenumber = 0;
                                do {
                                    $try_phonenumber++;
                                    $return_phonenumber = FALSE;
                                    $res_dtmf = $agi -> get_data("prepaid-phonenumber-to-speeddial", 5000, 30, "#"); //Please enter the phone number followed by the pound key
                                    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "PHONENUMBER TO SPEEDDIAL DTMF : ".$res_dtmf['result']);

                                    if (!empty($res_dtmf["result"]) && is_numeric($res_dtmf["result"]) && $res_dtmf["result"]>0) break;

                                    if ($try_phonenumber < 3) $return_phonenumber = TRUE;
                                    else $return_mainmenu;

                                } while($return_phonenumber);

                                if (!empty($res_dtmf["result"]) && is_numeric($res_dtmf["result"]) && $res_dtmf["result"]>0) {
                                    $assigned_number = $res_dtmf["result"];
                                    $agi -> stream_file("prepaid-the-phonenumber", "#"); //The phone number
                                    $agi -> say_digits($assigned_number, "#");
                                    $agi -> stream_file("prepaid-assigned-speeddial", "#"); //will be assigned to the speed dial number
                                    $agi -> say_number($speeddial_number, "#");

                                    $res_dtmf = $agi -> get_data("prepaid-press1-add-speeddial", 3000, 1); //If you want to proceed please  press 1 or press an other key to cancel ?
                                    if ($res_dtmf['result'] == 1) {
                                        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "ACTION : ".$action);
                                        if ($action == 'insert')
                                            $QUERY = "INSERT INTO cc_speeddial (id_cc_card, phone, speeddial) VALUES (".$A2B->id_card.", ".$assigned_number.", '".$speeddial_number."')";
                                        elseif ($action == 'update')
                                            $QUERY = "UPDATE cc_speeddial SET phone='".$assigned_number."' WHERE id = ".$id_speeddial;
                                        
                                        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, $QUERY);
                                        $result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
                                        $agi -> stream_file("prepaid-speeddial-saved"); //The speed dial number has been successfully saved.
                                        $return_mainmenu = TRUE;
                                        break;
                                    }
                                }
							}

							if ($try_enter_speeddial < 3) $return_enter_speeddial = TRUE;
							else $return_mainmenu = TRUE;
						} while ($return_enter_speeddial);
					}
				} while ($return_mainmenu);
			}

			
			if ($A2B -> agiconfig['sip_iax_friends']==1) {

				if ($A2B -> agiconfig['sip_iax_pstn_direct_call']==1) {
					
					if ($A2B -> agiconfig['use_dnid']==1 && !in_array ($A2B->dnid, $A2B -> agiconfig['no_auth_dnid']) && strlen($A2B->dnid)>2 && $i==0 ) {

						$A2B -> destination = $A2B->dnid;

					} elseif ($i == 0) {
						$prompt_enter_dest = $A2B -> agiconfig['file_conf_enter_destination'];
						$res_dtmf = $agi -> get_data($prompt_enter_dest, 4000, 20);
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES sip_iax_pstndirect_call DTMF : ".$res_dtmf ["result"]);
						$A2B-> destination = $res_dtmf ["result"];
					}

					if ( (strlen($A2B-> destination)>0)
						&& (strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix'])>0) 
						&& (strncmp($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix'], $A2B-> destination,strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix']))==0) ) {
						
						$A2B -> dnid = $A2B-> destination;
						$A2B -> sip_iax_buddy = $A2B -> agiconfig['sip_iax_pstn_direct_call_prefix'];
						$A2B -> agiconfig['use_dnid'] = 1;
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "SIP 1. IAX - dnid : ".$A2B->dnid." - ".strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix']));
						$A2B -> dnid = substr($A2B->dnid,strlen($A2B -> agiconfig['sip_iax_pstn_direct_call_prefix']));
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "SIP 2. IAX - dnid : ".$A2B->dnid);
						
					} elseif (strlen($A2B->destination)>0) {
						$A2B -> dnid = $A2B->destination;
						$A2B -> agiconfig['use_dnid'] = 1;
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "TRUNK - dnid : ".$A2B->dnid." (".$A2B -> agiconfig['use_dnid'].")");
					}
				} else {
					$res_dtmf = $agi -> get_data('prepaid-sipiax-press9', 4000, 1);
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES SIP_IAX_FRIEND DTMF : ".$res_dtmf ["result"]);
					$A2B -> sip_iax_buddy = $res_dtmf ["result"];
				}
			}

			if ( strlen($A2B-> sip_iax_buddy) > 0 || ($A2B-> sip_iax_buddy == $A2B -> agiconfig['sip_iax_pstn_direct_call_prefix'])) {

				$A2B -> debug( INFO, $agi, __FILE__, __LINE__, 'CALL SIP_IAX_BUDDY');
				$cia_res = $A2B-> call_sip_iax_buddy($agi, $RateEngine, $i);

			} else {
				$isvoip = false;
				$initialdestination = $A2B->extension;
				if ($A2B -> ivr($agi,$A2B->extension,$initialdestination,$isvoip)) {
					$A2B->destination = $A2B->extension;
				}
				if (stripos($A2B->extension,'QUEUE ') === 0) {
						$ans = "QUEUE";
				} else {
					$ans = $A2B -> callingcard_ivr_authorize($agi, $RateEngine, $i, true);
				}
				if (!$A2B -> enough_credit_to_call($agi, $RateEngine)) {
					// SAY TO THE CALLER THAT IT DEOSNT HAVE ENOUGH CREDIT TO MAKE A CALL
					$A2B -> let_stream_listening($agi);
					$prompt = "prepaid-no-enough-credit-stop";
					$agi -> stream_file($prompt, '#');
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[STOP STREAM FILE $prompt]");
				
					if (($A2B -> agiconfig['notenoughcredit_cardnumber']==1) && (($i+1)< $A2B -> agiconfig['number_try'])) {

						if ($A2B->set_inuse_username)
							$A2B->callingcard_acct_start_inuse($agi,0);

						$A2B -> agiconfig['cid_enable'] = 0;
						$A2B -> agiconfig['use_dnid'] = 0;
						$A2B -> agiconfig['cid_auto_assign_card_to_cid'] = 0;
						$A2B -> accountcode = '';
						$A2B -> username = '';
						$A2B -> ask_other_cardnumber = 1;

						$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT_CARDNUMBER - TRY : callingcard_ivr_authenticate]");
						if ($cia_res!=0) break;

						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT_CARDNUMBER - callingcard_acct_start_inuse]");
						$A2B -> callingcard_acct_start_inuse($agi,1);
						continue;

					} else {
						$send_reminder = 1;
						$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SET MAIL REMINDER - NOT ENOUGH CREDIT]");
						break;
					}
				}
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, 'ANSWER fct callingcard_ivr authorize:> '.$ans);
				// CREATE A PERSONAL UNIQUEID FOR EACH TRY
				$newuniqueid = explode('.',$A2B -> uniqueid);
				if ($newuniqueid[0] == time() && $i)		sleep(1);
				$newuniqueid[0] = time();
				$A2B -> uniqueid = implode('.',$newuniqueid);
//$A2B -> debug( FATAL, $agi, __FILE__, __LINE__, $A2B -> uniqueid);

				if ($ans=="QUEUE") {
					$dialstr = $A2B->destination;
					$que = explode(",",$dialstr);
					if (stripos($que[1],"r") === false) {
						$A2B -> let_stream_listening($agi);
					} else {
						$A2B -> let_stream_listening($agi,false,true);
					}
					$myres = $A2B -> run_dial($agi, $dialstr);
					$A2B -> DbReConnect($agi);
					$answeredtime = $agi->get_variable("ANSWEREDTIME", true);
					if ($answeredtime == "")
						$answeredtime	= $agi->get_variable("CDR(billsec)",true);
					if ($answeredtime>1356000000) {
						$answeredtime	= time() - $answeredtime;
						$dialstatus	= 'ANSWER';
					} else {
						$answeredtime	= 0;
						$dialstatus	= $A2B -> get_dialstatus_from_queuestatus($agi);
					}
					$bridgepeer = $agi->get_variable('QUEUEDNID', true);
					if (strlen($A2B -> dialstatus_rev_list[$dialstatus])>0) {
						$terminatecauseid = $A2B -> dialstatus_rev_list[$dialstatus];
					} else {
						$terminatecauseid = 0;
					}
					if ($answeredtime == 0) $inst_listdestination4 = $A2B -> realdestination;
					else {
						$inst_listdestination4 = $A2B -> destination = $bridgepeer;
					}
					$QUERY = "SELECT regexten, id_cc_card FROM cc_sip_buddies WHERE name LIKE '{$inst_listdestination4}' LIMIT 1";
					$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
					if (is_array($result)) {
						$A2B->calledexten = ($result[0][0] != "") ? "'".$result[0][0]."'" : 'NULL';
						$card_called = "'".$result[0][1]."'";
					} else {
						$A2B->calledexten = 'NULL';
						$card_called = "'0'";
					}
					$QUERY = "INSERT INTO cc_call (uniqueid, sessionid, card_id, nasipaddress, starttime, sessiontime, calledstation, ".
						" terminatecauseid, stoptime, sessionbill, id_tariffgroup, id_tariffplan, id_ratecard, id_trunk, src, sipiax, id_did, calledexten, card_called, dnid, callbackid) VALUES ".
						"('".$A2B->uniqueid."', '".$A2B->channel."',  '".$A2B->id_card."', '".$A2B->hostname."',";
					$QUERY .= " CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND ";
					$QUERY .= ", '$answeredtime', '".$inst_listdestination4."', '$terminatecauseid', now(), '0', '0', '0', '0', '0', '$A2B->CallerID', '3', '$A2B->id_did', $A2B->calledexten, $card_called, '$A2B->dnid', '$A2B->callback_id')";
					$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY, 0);
					if ($A2B->set_inuse_username)	$A2B -> callingcard_acct_start_inuse($agi,0);
				} else if ($ans=="2FAX") {
//					$transferername = $agi->get_variable("TRANSFERERNAME", true);
//					if ($transferername == "") $transferername = $agi->get_variable("BLINDTRANSFER", true);

//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "BLINDTRANSFER: ".$blindtransfer);

//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "TRANSFERERNAME: ".$transferername);

//					preg_match("/([^\/]+)(?=-[^-]*$)/",$transferername,$transferername);

//if (isset($transferername[0])) $A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "TRANSFERERNAME[0]: ".$transferername[0]);

					$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[ CALL OF THE SYSTEM - [FAX=".$A2B-> destination."]");
					$A2B -> call_fax($agi);
					if (isset($A2B->transferername[0])) {
//						$A2B-> uniqueid = $A2B-> uniqueid + 1000000000;
						$A2B-> extension = $A2B-> dnid = $A2B-> destination = $A2B-> backafter;
//						$A2B-> backafterfax = true;
						unset($A2B->transferername);
						$A2B-> CallerIDext = $A2B->cidextafter . "<b>&#9100;</b>";
						$agi-> set_callerid('<'. $A2B-> cidextafter .'>');
						$A2B-> agiconfig['number_try'] = 1;
						$A2B-> CallerID = $A2B-> cidafter;
//						$agi-> set_variable('__TEMPONFORWARDCID1', $A2B-> cidafter);
//						$agi-> set_variable('__ONFORWARDCID2', $A2B-> backafter);
//						sleep(1);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "UNIQUEID + 1000000000 = ".$A2B-> uniqueid);
						$agi-> evaluate("STREAM FILE one-moment-please \"#\" 0");
						$ans = $A2B-> callingcard_ivr_authorize($agi, $RateEngine, $i, true);
					} elseif ($A2B->set_inuse_username) {
						$A2B -> callingcard_acct_start_inuse($agi,0);
					}
				}
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, $A2B-> destination);
				if ($ans==1) {
				    do {
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, $A2B-> destination);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "extension=".$A2B->extension." ====== dnid=".$A2B->dnid." ====== destination=".$A2B-> destination);
					$RateEngine -> Reinit();
					// PERFORM THE CALL
					$result_callperf = $RateEngine->rate_engine_performcall ($agi, $A2B-> destination, $A2B);
					if (!$result_callperf && !(!$A2B->extext && $A2B->voicemail && !is_null($A2B->voicebox) && !isset($A2B->transferername[0]))) {
						$A2B -> let_stream_listening($agi);
						if ($A2B -> outoflength == true) {
							$prompt = "pbx-invalid";
							$A2B -> outoflength = false;
						} else {
							$prompt = "prepaid-dest-unreachable";
						}
						$agi -> stream_file($prompt, '#');
					}
					// INSERT CDR  & UPDATE SYSTEM
					$RateEngine -> rate_engine_updatesystem($A2B, $agi, $A2B-> destination);
					if ($A2B->backaftertransfer && isset($A2B->transferername[0]) && $RateEngine->dialstatus != "ANSWER") {
						$A2B-> extension = $A2B-> dnid = $A2B-> destination = $A2B-> backafter;
						unset($A2B->transferername);
						$A2B-> backaftertransfer = false;
						$A2B-> CallerIDext = $A2B->cidextafter . "<b>&#9100;</b>";
						$agi-> set_callerid('<'. $A2B-> cidextafter .'>');
						$A2B-> agiconfig['number_try'] = 1;
						$A2B-> CallerID = $A2B-> cidafter;
						$agi-> evaluate("STREAM FILE one-moment-please \"#\" 0");
						$ans = $A2B-> callingcard_ivr_authorize($agi, $RateEngine, $i, true);
					} else	$ans = 0;
				    } while ($ans==1);


					if (!$A2B->extext && $A2B->voicemail && !is_null($A2B->voicebox) && !isset($A2B->transferername[0])) {
						if ($RateEngine->dialstatus =="CHANUNAVAIL" || $RateEngine->dialstatus == "NOANSWER" || $RateEngine->dialstatus == "CONGESTION") {
							$A2B->voicebox .= "|su";
						} elseif ($RateEngine->dialstatus =="BUSY") {
							$A2B->voicebox .= "|sb";
						} else	$A2B->voicebox	= false;
						if ($A2B->voicebox) {
							// The following section will send the caller to VoiceMail with the unavailable priority.\
							$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[STATUS] CHANNEL ($RateEngine->dialstatus) - GOTO VOICEMAIL ($A2B->voicebox)");
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[STATUS] CHANNEL ($RateEngine->dialstatus) - GOTO VOICEMAIL ($A2B->voicebox)");
							$agi-> exec('VoiceMail', $A2B -> format_parameters ($A2B->voicebox));
						}
					}
					if ($A2B -> agiconfig['say_balance_after_call']==1) {
						$A2B-> fct_say_balance ($agi, $A2B-> credit);
					}
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[a2billing account stop]');
					if (!$A2B->dtmf_destination) {
//$A2B -> debug( FATAL, $agi, __FILE__, __LINE__, "[NOT RESTART DIAL]");
						$A2B -> agiconfig['use_dnid']=0;
						break;
					}
//$A2B -> debug( FATAL, $agi, __FILE__, __LINE__, "[RESTART DIAL]");
				} elseif ($ans=="2DID") {
//$A2B -> debug( FATAL, $agi, __FILE__, __LINE__, "[2DID]");
					$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[ CALL OF THE SYSTEM - [DID=".$A2B-> destination."]");
					$QUERY = "SELECT cc_did.id, cc_did_destination.id, billingtype, tariff, destination, voip_call, username, useralias, connection_charge, selling_rate, did, ".
						" aleg_carrier_connect_charge, aleg_carrier_cost_min, aleg_retail_connect_charge, aleg_retail_cost_min, ".
						" aleg_carrier_initblock, aleg_carrier_increment, aleg_retail_initblock, aleg_retail_increment, ".
						" aleg_timeinterval, ".
						" aleg_carrier_connect_charge_offp, aleg_carrier_cost_min_offp, aleg_retail_connect_charge_offp, aleg_retail_cost_min_offp,".
						" aleg_carrier_initblock_offp, aleg_carrier_increment_offp, aleg_retail_initblock_offp, aleg_retail_increment_offp,".
						" cc_card.id, playsound, timeout, margin, id_diller, voicebox, removeaddprefix, addprefixinternational, answer, chanlang,".
						" aftercallbacksound, digitaftercallbacksound, spamfilter, secondtimedays, calleridname, speech2mail, send_text, send_sound, calleesound".
						" FROM cc_did, cc_card, cc_country, cc_did_destination".
						" LEFT JOIN cc_sheduler_ratecard ON id_did_destination=cc_did_destination.id".
						" WHERE id_cc_did=cc_did.id AND cc_card.status=1 AND cc_card.id=id_cc_card AND cc_did_destination.activated=1 AND cc_did.activated=1 AND did LIKE '$A2B->destination'".
						" AND cc_country.id=id_cc_country AND cc_did.startingdate <= CURRENT_TIMESTAMP".
						" AND (cc_did.expirationdate > CURRENT_TIMESTAMP OR cc_did.expirationdate IS NULL OR cc_did.expirationdate = '0000-00-00 00:00:00')".
						" AND cc_did_destination.validated = 1".
						" AND (`cc_sheduler_ratecard`.`id_did_destination` IS NULL OR".
						    " (`weekdays` LIKE CONCAT('%',WEEKDAY(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1)) )),'%')".
							" AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1)))) BETWEEN `timefrom` AND `timetill`".
							    " OR (`timetill`<=`timefrom` AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1))))<`timetill`".
								" OR TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1))))>=`timefrom`)))))".
						" ORDER BY priority ASC";

					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, $QUERY);
					$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);

					if (is_array($result)) {
						//On Net
/**						if ($A2B->cardnumber != $result[0][6]) {
							if ($A2B->set_inuse_username)
								$A2B -> callingcard_acct_start_inuse($agi,0);
							$A2B -> callingcard_ivr_authenticate($agi,$result[0][6]);
						}
**/
						$A2B -> call_2did($agi, $RateEngine, $result);
						if ($A2B->set_inuse_username)
							$A2B -> callingcard_acct_start_inuse($agi,0);
					}
				}
			}
			$A2B -> agiconfig['use_dnid']=0;
		}//END FOR

	}
	/****************  SAY GOODBYE   ***************/
	if ($A2B -> agiconfig['say_goodbye']==1) $agi -> stream_file('prepaid-final', '#');


// MODE DID

} elseif ($mode == 'did') {
	
	if ($A2B -> agiconfig['answer_call']==1) {
		$A2B -> G_startime = time();
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[ANSWER CALL]');
		$agi -> answer();
	} else {
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[NO ANSWER CALL]');
	}
	// TODO
	// CRONT TO CHARGE MONTLY

	$RateEngine -> Reinit();
	$A2B -> Reinit();

	$mydnid = $agi -> request['agi_extension'];
	if ($A2B -> CC_TESTING) $mydnid = '11111111';

	if (strlen($mydnid) > 0){
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - [CallerID=".$A2B->CallerID."]:[DID=".$mydnid."]");

		$QUERY =  "SELECT cc_did.id, cc_did_destination.id, billingtype, tariff, destination, voip_call, username, useralias, connection_charge, selling_rate, did, ".
                    " aleg_carrier_connect_charge, aleg_carrier_cost_min, aleg_retail_connect_charge, aleg_retail_cost_min, ".
                    " aleg_carrier_initblock, aleg_carrier_increment, aleg_retail_initblock, aleg_retail_increment, ".
                    " aleg_timeinterval, ".
                    " aleg_carrier_connect_charge_offp, aleg_carrier_cost_min_offp, aleg_retail_connect_charge_offp, aleg_retail_cost_min_offp, ".
                    " aleg_carrier_initblock_offp, aleg_carrier_increment_offp, aleg_retail_initblock_offp, aleg_retail_increment_offp,".
                    " answer, playsound, timeout, margin, id_diller, voicebox, removeaddprefix, addprefixinternational, chanlang, buyrate, billblock, spamfilter, secondtimedays, calleridname, speech2mail, send_text, send_sound, calleesound".
			        " FROM cc_did, cc_card, cc_country, cc_did_destination".
			        " LEFT JOIN cc_sheduler_ratecard ON id_did_destination=cc_did_destination.id".
			        " WHERE id_cc_did=cc_did.id AND cc_card.status=1 AND cc_card.id=id_cc_card AND cc_did_destination.activated=1 AND cc_did.activated=1 AND did LIKE '$mydnid'".
			        " AND cc_country.id=id_cc_country AND cc_did.startingdate<= CURRENT_TIMESTAMP".
				" AND (cc_did.expirationdate > CURRENT_TIMESTAMP OR cc_did.expirationdate IS NULL OR cc_did.expirationdate = '0000-00-00 00:00:00')".
			        " AND cc_did_destination.validated=1 ".
				" AND (`cc_sheduler_ratecard`.`id_did_destination` IS NULL OR".
				    " (`weekdays` LIKE CONCAT('%',WEEKDAY(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1)) )),'%')".
					" AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1)))) BETWEEN `timefrom` AND `timetill`".
					    " OR (`timetill`<=`timefrom` AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1))))<`timetill`".
						" OR TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1))))>=`timefrom`)))))".
				" ORDER BY priority ASC";

//		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, $QUERY);
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, $QUERY);
		$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
//		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, var_export($result,true));
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, var_export($result,true));
        
		if (is_array($result) && !is_null($result[0][1])) {
		    //Off Net
			$chanlang = $result[0][36];
			if ($chanlang != 'not_set') {
				if ($A2B->agiconfig['asterisk_version'] == "1_2") {
					$lg_var_set = 'LANGUAGE()';
				} else {
					$lg_var_set = 'CHANNEL(language)';
				}
				$agi -> set_variable($lg_var_set, $chanlang);
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SET $lg_var_set $chanlang]");
			}
			$A2B -> current_language = $agi -> get_variable('CHANNEL(language)', true);
			$A2B -> call_did($agi, $RateEngine, $result);
			if ($A2B->set_inuse_username) $A2B -> callingcard_acct_start_inuse($agi,0);
		}
	}

// MOVE VOUCHER TO LET CUSTOMER ONLY REFILL
} elseif ($mode == 'voucher'){

	if ($A2B -> agiconfig['answer_call']==1){
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[ANSWER CALL]');
		$agi -> answer();
		$status_channel=6;
	}else{
		$A2B -> debug( INFO, $agi, __FILE__, __LINE__, '[NO ANSWER CALL]');
		$status_channel=4;
	}

	$A2B -> play_menulanguage ($agi);
	/*************************   PLAY INTRO MESSAGE   ************************/
	if (strlen($A2B -> agiconfig['intro_prompt'])>0) 		$agi -> stream_file($A2B -> agiconfig['intro_prompt'], '#');

	if (strlen($A2B->CallerID)>1 && is_numeric($A2B->CallerID)) {
		$A2B->CallerID = $caller_areacode.$A2B->CallerID;
	}
	$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[TRY : callingcard_ivr_authenticate]");

    // CALL AUTHENTICATE AND WE HAVE ENOUGH CREDIT TO GO AHEAD
	if ($A2B->id_card > 0) {
	    for ($k=0;$k<3;$k++) {
		    $vou_res = $A2B -> refill_card_with_voucher($agi, null);
		    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "VOUCHER RESULT = $vou_res");
		    if ($vou_res==1){
			    break;
		    } else {
			    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - refill_card_withvoucher fail] ");
		    }
	    }
    }
    
	// SAY GOODBYE
	if ($A2B -> agiconfig['say_goodbye']==1) $agi -> stream_file('prepaid-final', '#');

	$agi -> hangup();
	if ($A2B->set_inuse_username) $A2B->callingcard_acct_start_inuse($agi,0);
	$A2B -> write_log("[STOP - EXIT]", 0);
	exit();

// MODE CAMPAIGN-CALLBACK
} elseif ($mode == 'campaign-callback'){
	$A2B -> update_callback_campaign ($agi);

// MODE cid-callback & cid-prompt-callback
} elseif ($mode == 'cid-callback' || $mode == 'cid-prompt-callback') {

	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[MODE : '.strtoupper($mode).' - '.$A2B->CallerID.']');
	
	if ($mode == 'cid-callback') {
		$agi -> exec('Busy');
		$agi -> hangup();
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[HANGUP CLI CALLBACK TRIGGER]');
	} elseif ($mode == 'cid-prompt-callback') {
		$agi -> answer();
	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CLI CALLBACK TRIGGER RINGING]');
	}

	// MAKE THE AUTHENTICATION ACCORDING TO THE CALLERID
	$A2B -> agiconfig['cid_enable']=1;
	$A2B -> agiconfig['cid_askpincode_ifnot_callerid']=0;
	$A2B -> agiconfig['say_balance_after_auth']=0;

	if (strlen($A2B->CallerID)>1 && is_numeric($A2B->CallerID)) {

		/* WE START ;) */
		$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[TRY : callingcard_ivr_authenticate]");
		if ($cia_res==0) {

			$RateEngine = new RateEngine();
			
			// Apply 1st leg tariff override if param was passed in
			if (strlen($cid_1st_leg_tariff_id) > 0) {
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, 'Callback Tariff override for 1st Leg only. New tariff is '.$cid_1st_leg_tariff_id);
				$A2B ->tariff = $cid_1st_leg_tariff_id;
			}
            
			$A2B -> agiconfig['use_dnid']=1;
			$A2B -> agiconfig['say_timetocall']=0;

			// We arent removing leading zero in front of the callerID if needed this might be done over the dialplan
			$A2B -> extension = $A2B -> dnid = $A2B -> destination = $caller_areacode.$A2B -> apply_rules($A2B -> CallerID);
			
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[destination: - '.$A2B->destination.']');
			
			// LOOKUP RATE : FIND A RATE FOR THIS DESTINATION
			$resfindrate = $RateEngine->rate_engine_findrates($A2B, $A2B ->destination, $A2B ->tariff);
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[resfindrate: - '.$resfindrate.']');

			$instance_table = new Table("cc_callback_spool");
			$FG_TABLE_CLAUSE = "(status='PENDING' OR status='PROCESSING') AND account='{$A2B->accountcode} AND 'callerid='{$A2B->config['callback']['callerid']}' AND exten_leg_a='{$A2B->destination}' AND timediff(now(),entry_time)<{$A2B->config['callback']['sec_avoid_repeate']}";
			$FG_NB_RECORD = $instance_table -> Table_count ($A2B -> DBHandle, $FG_TABLE_CLAUSE);

			$sec_wait_before_callback = $A2B -> config["callback"]['sec_wait_before_callback'];
			if (!is_numeric($sec_wait_before_callback) || $sec_wait_before_callback<1) {
				$sec_wait_before_callback = 1;
			}
			if ($FG_NB_RECORD > 0) {
				$QUERY = "num_attempt='0', entry_time=now(), `next_attempt_time`=ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND )";
				$result = $instance_table -> Update_table ($A2B -> DBHandle, $QUERY, $FG_TABLE_CLAUSE);
			// IF FIND RATE
			} elseif ($resfindrate!=0 && $FG_NB_RECORD == 0) {
				//$RateEngine -> debug_st	=1;
				$res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($A2B, $A2B->credit);
				//echo ("RES_ALL_CALCULTIMEOUT ::> $res_all_calcultimeout");

				if ($res_all_calcultimeout) {

                    $CALLING_VAR = '';
                    $MODE_VAR = "MODE=CID";
                    if ($mode == 'cid-prompt-callback') {

                        $MODE_VAR = "MODE=CID-PROMPT";

                        $try = 0;
                        do {
                            $try++;
                            $return = TRUE;
                            
                            // GET THE DESTINATION NUMBER
                            $prompt_enter_dest = $A2B -> agiconfig['file_conf_enter_destination'];
                            $res_dtmf = $agi -> get_data($prompt_enter_dest, 6000, 20);
                            $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES DTMF : ".$res_dtmf ["result"]);
                            $outbound_destination = $res_dtmf ["result"];
                            
                            if ($A2B -> agiconfig['cid_prompt_callback_confirm_phonenumber'] == 1) {
                                $agi -> stream_file('prepaid-the-number-u-dialed-is', '#'); //Your locking code is
                                $agi -> say_digits($outbound_destination);

                                $subtry=0;
                                do {
                                    $subtry++;
                                    //= CONFIRM THE DESTINATION NUMBER
                                    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[MENU OF CONFIRM (".$res_dtmf ["result"].")]" );
                                    $res_dtmf = $agi -> get_data('prepaid-re-enter-press1-confirm	', 4000, 1);
                                    if ($subtry >= 3) {
                                        if ($A2B->set_inuse_username)
                                            $A2B -> callingcard_acct_start_inuse($agi,0);
                                        $agi -> hangup();
                                        exit();
                                    }
                                } while ($res_dtmf ["result"]!='1' && $res_dtmf ["result"]!='2');
                            
                                // Check the result
                                if ($res_dtmf ["result"]=='1') {
                                    $return = TRUE;
                                } elseif ($res_dtmf ["result"]=='2') {
                                    $return = FALSE;
                                }
                            
                                $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[TRY : $try]" );
                            } else {
                                $return = FALSE;
                            }
                        } while($return && $try < 3);
                        
                        if (strlen($outbound_destination)<=0) {
                            if ($A2B->set_inuse_username)
                                $A2B -> callingcard_acct_start_inuse($agi,0);
                            $agi -> hangup();
                            exit();
                        }

                        $CALLING_VAR = "CALLING=".$outbound_destination;
                    } // if ($mode == 'cid-prompt-callback')
                    
				    // MAKE THE CALL
				    $channeloutcid = $RateEngine->rate_engine_performcall($agi, $A2B->destination, $A2B, 9);
				    if ($channeloutcid) {
					$channel = $channeloutcid[0];
					$sep = ($A2B->config['global']['asterisk_version'] == "1_2" || $A2B->config['global']['asterisk_version'] == "1_4")?'|':',';
					if (isset($A2B -> cidphonenumber) && $A2B -> cidphonenumber) {
					    $exten = $A2B -> cidphonenumber;
					    $variable = '';
					} else {
					    $exten = $A2B -> config["callback"]['extension'];
					    $variable = $MODE_VAR.$sep."IDCONF=$idconfig".$sep;
					}
					if ($argc > 4 && strlen($argv[4]) > 0) $exten = $argv[4];
					if (!$CALLING_VAR && $exten) $CALLING_VAR = "CALLING=".$exten;
					$context = $A2B -> config["callback"]['context_callback'];
					$id_server_group = $A2B -> config["callback"]['id_server_group'];
					$priority = 1;
					$timeout = $A2B -> config["callback"]['timeout']*1000;
					if ($channeloutcid[1]) $callerid = $channeloutcid[1];
					else $callerid = $A2B -> config["callback"]['callerid'];
					$application = '';
					$account = $A2B -> accountcode;
					$uniqueid = MDP_NUMERIC(5).'-'.MDP_STRING(7);
					
					$variable .= "CALLED=".$A2B ->destination.$sep.$CALLING_VAR.$sep."CBID=$uniqueid".$sep."LEG=".$A2B -> username;
					
					$callbackrate = $RateEngine -> ratecard_obj[$channeloutcid[4]]['callbackrate'];
					foreach($callbackrate as $key => $value){
						$variable .= $sep.strtoupper($key).'='.$value;
					}
					//pass the tariff if it was passed in
					if (strlen($cid_1st_leg_tariff_id) > 0)
					{ 
						$variable .= $sep.'TARIFF='.$cid_1st_leg_tariff_id;
					}
					$variable .= $sep."RATECARD=".$RateEngine->ratecard_obj[$channeloutcid[4]][6].$sep."TRUNK=".$channeloutcid[2].$sep."TD=".$channeloutcid[3];
					$status = 'PENDING';
					$server_ip = 'localhost';
					$num_attempt = 0;

					$QUERY = "INSERT INTO cc_callback_spool (uniqueid, status, server_ip, num_attempt, channel, exten, context, priority, variable, id_server_group, callback_time, account, callerid, timeout, next_attempt_time, exten_leg_a)" .
						" VALUES ('$uniqueid', '$status', '$server_ip', '$num_attempt', '$channel', '$exten', '$context', '$priority', '$variable', '$id_server_group', ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND ), '$account', '$callerid', '$timeout', ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND ), '$A2B->destination')";
					$res = $A2B -> DBHandle -> Execute($QUERY);
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-ALL : INSERT CALLBACK REQUEST IN SPOOL : QUERY=$QUERY]");
/*
					if ($res && !$A2B->CC_TESTING) {
					    $QUERY = "UPDATE cc_trunk SET inuse=inuse+1 WHERE id_trunk=".$channeloutcid[2];
					    $res = $A2B -> DBHandle -> Execute($QUERY);
					    sleep(10);
					    $QUERY = "UPDATE cc_trunk SET inuse=inuse-1 WHERE id_trunk=".$channeloutcid[2];
					    $res = $A2B -> DBHandle -> Execute($QUERY);
					}
*/
				    } else $error_msg = gettext("Error : Sorry, not enough free trunk for make call. Try again later!");

				}else{
					$error_msg = 'Error : You don t have enough credit to call you back !!!';
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | $error_msg]");
				}

			}else{
				$error_msg = 'Error : There is no route to call back your phonenumber !!!';
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | $error_msg]");
			}

		}else{
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | Authentication failed]");
		}

	}else{
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | error callerid]");
	}

} elseif ($mode == 'all-callback') {

	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[MODE : ALL-CALLBACK - '.$A2B->CallerID.']');

	// END
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[HANGUP ALL CALLBACK TRIGGER]');
//	$agi -> exec('Congestion');
	$agi -> exec('Busy');
	$agi -> hangup();

	$A2B ->credit = 1000;
	$A2B ->tariff = $A2B -> config["callback"]['all_callback_tariff'];

	// MAKE THE AUTHENTICATION ACCORDING TO THE CALLERID
	$A2B -> agiconfig['cid_enable']=0;
	$A2B -> agiconfig['cid_askpincode_ifnot_callerid']=0;
	$A2B -> agiconfig['say_balance_after_auth']=0;

	if (strlen($A2B->CallerID)>1 && is_numeric($A2B->CallerID)) {

		/* WE START ;) */
		$cia_res = $A2B -> callingcard_ivr_authenticate($agi,$cid_1st_leg_tariff_id); // variable $cid_1st_leg_tariff_id is using as accountcode
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[TRY : callingcard_ivr_authenticate]");
		if ($cia_res==0) {

			$RateEngine = new RateEngine();
			// $RateEngine -> webui = 0;
			// LOOKUP RATE : FIND A RATE FOR THIS DESTINATION

			$A2B -> agiconfig['use_dnid']=1;
			$A2B -> agiconfig['say_timetocall']=0;
			$A2B -> agiconfig['say_balance_after_auth']=0;
			$A2B -> extension = $A2B -> dnid = $A2B -> destination = $caller_areacode.$A2B -> apply_rules($A2B -> CallerID);
// !!!!!!!!!!!!!!!!!!!!!!!!!!!
// Accountcode of incoming trunk & cid_card_id are not compare now !!!!!!!
			$QUERY = "SELECT callback FROM cc_callerid WHERE cid LIKE '$A2B->destination' AND (activated='f' OR (activated='t' AND (callback=0 OR blacklist=1)))";
			$result = ($callbacksound) ? false : $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);

			$instance_table = new Table("cc_callback_spool");
			$FG_TABLE_CLAUSE = "(status LIKE 'PENDING' OR status LIKE 'PROCESSING' OR (timediff(now(),entry_time)<".$A2B->config['callback']['sec_avoid_repeate']." AND status='SENT')) AND account='{$A2B->accountcode}' AND callerid='".$A2B->config['callback']['callerid']."' AND exten_leg_a='{$A2B->destination}'";
			$FG_NB_RECORD = $instance_table -> Table_count ($A2B -> DBHandle, $FG_TABLE_CLAUSE);

			$sec_wait_before_callback = $A2B -> config["callback"]['sec_wait_before_callback'];
			if (!is_numeric($sec_wait_before_callback) || $sec_wait_before_callback<1) {
				$sec_wait_before_callback = 1;
			}
			if ($FG_NB_RECORD > 0) {
				$QUERY = "num_attempt='0', entry_time=now(), `next_attempt_time`=ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND )";
				$result = $instance_table -> Update_table ($A2B -> DBHandle, $QUERY, $FG_TABLE_CLAUSE);
$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "\033[1;31m============UPDATE============   Attempt of double number insert detected > ".$A2B->destination."\33[0m");
				write_log(LOGFILE_API_CALLBACK, " =========UPDATE========= Attempt of double number insert detected =====================> ".$A2B->destination);
			} elseif (!is_array($FG_NB_RECORD) && !is_array($result) && $FG_NB_RECORD == 0) {
			    $resfindrate = $RateEngine->rate_engine_findrates($A2B, $A2B -> destination, $A2B -> tariff);
$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "\033[1;32m============INSERT============  Try to inserting CallBack to > ".$A2B->destination."\33[0m");
			    // IF FIND RATE
			    if ($resfindrate!=0) {
				//$RateEngine -> debug_st = 1;
				$res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($A2B, $A2B->credit);
				if ($res_all_calcultimeout){
				    // MAKE THE CALL
				    $channeloutcid = $RateEngine->rate_engine_performcall($agi, $A2B->destination, $A2B, 9);
				    if ($channeloutcid) {
					$channel = $channeloutcid[0];
					$sep = ($A2B->config['global']['asterisk_version'] == "1_2" || $A2B->config['global']['asterisk_version'] == "1_4")?'|':',';
					if (isset($A2B -> cidphonenumber) && $A2B -> cidphonenumber) {
					    $exten = $A2B -> cidphonenumber;
					    $variable = '';
					} else {
					    $exten = $A2B -> config["callback"]['extension'];
					    $variable = "IDCONF=$idconfig".$sep;
					}
					if ($argc > 4 && strlen($argv[4]) > 0) $exten = $argv[4];
					$context = $A2B -> config["callback"]['context_callback'];
					$id_server_group = $A2B -> config["callback"]['id_server_group'];
					$priority = 1;
					$timeout = $A2B -> config["callback"]['timeout']*1000;
					if ($channeloutcid[1] && $channeloutcid[1] != $A2B->destination) $callerid = $channeloutcid[1];
					else $callerid = $A2B -> config["callback"]['callerid'];
//$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$callerid." / ".$A2B -> config["callback"]['callerid']);
					$application = '';
					$account = $A2B -> accountcode;

					$uniqueid = MDP_NUMERIC(5).'-'.MDP_STRING(7);
					
					$variable .= "CALLED=".$A2B ->destination.$sep."CALLING=".$exten.$sep."MODE=ALL".$sep."CBID=$uniqueid".$sep."TARIFF=".$A2B ->tariff.$sep."LEG=".$A2B -> username.$sep."RATECARD=".$RateEngine->ratecard_obj[$channeloutcid[4]][6].$sep."TRUNK=".$channeloutcid[2].$sep."TD=".$channeloutcid[3];
					
					$status = 'PENDING';
					$server_ip = 'localhost';
					$num_attempt = 0;

					$QUERY = "INSERT INTO cc_callback_spool (uniqueid, status, server_ip, num_attempt, channel, exten, context, priority, variable, id_server_group, callback_time, account, callerid, timeout, next_attempt_time, exten_leg_a)" .
						" VALUES ('$uniqueid', '$status', '$server_ip', '$num_attempt', '$channel', '$exten', '$context', '$priority', '$variable', '$id_server_group', ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND ), '$account', '$callerid', '$timeout', ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND ), '$A2B->destination')";
					$res = $A2B -> DBHandle -> Execute($QUERY);
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-ALL : INSERT CALLBACK REQUEST IN SPOOL : QUERY=$QUERY]");

/*					if ($res && !$A2B->CC_TESTING) {
					    $QUERY = "UPDATE cc_trunk SET inuse=inuse+1 WHERE id_trunk=".$channeloutcid[2];
					    $res = $A2B -> DBHandle -> Execute($QUERY);
					    sleep(10);
					    $QUERY = "UPDATE cc_trunk SET inuse=inuse-1 WHERE id_trunk=".$channeloutcid[2];
					    $res = $A2B -> DBHandle -> Execute($QUERY);
					}
*/
				    } else $error_msg = gettext("Error : Sorry, not enough free trunk for make call. Try again later!");

				} else {
					$error_msg = 'Error : You don t have enough credit to call you back !!!';
					$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | $error_msg]");
				}
			    } else {
				$error_msg = 'Error : There is no route to call back your phonenumber !!!';
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | $error_msg]");
			    }
			} else $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | Extension for callback in the list of CallerID is inactive or CallBack not resolved]");
		} else {
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | Authentication failed]");
		}

	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | error callerid]");
	}


// MODE CALLBACK
} elseif ($mode == 'callback') {

	$callbackuniqueid = $A2B->uniqueid;
	$newuniqueid = explode('.',$A2B->uniqueid);
	$newuniqueid[0] = time();
	$A2B->uniqueid = implode('.',$newuniqueid);
	
	$callback_been_connected = 0;
	
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[MODE : CALLBACK]');
	
	if ($A2B -> config["callback"]['answer_call']==1) {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[ANSWER CALL]');
		$agi -> answer();
		$status_channel = 6;
		$A2B -> play_menulanguage ($agi);

		// PLAY INTRO FOR CALLBACK
		if (strlen($A2B -> config["callback"]['callback_audio_intro']) > 0) {
			$agi -> stream_file($A2B -> config["callback"]['callback_audio_intro'], '#');
		}
	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[NO ANSWER CALL]');
		$status_channel = 4;
		$A2B -> play_menulanguage ($agi);
	}

	$callback_td			= $agi -> get_variable("TD", true);
	$callback_usedtrunk		= $agi -> get_variable("TRUNK", true);
	$called_party = $calling_num	= $agi -> get_variable("CALLED", true);
	$calling_party			= $agi -> get_variable("CALLING", true);
	$cid				= $agi -> get_variable("CID", true);
	$callback_mode			= $agi -> get_variable("MODE", true);
	$callback_tariff		= $agi -> get_variable("TARIFF", true);
	$callback_uniqueid		= $agi -> get_variable("CBID", true);
	$A2B->callback_id		= $agi -> get_variable("CALLBACKID", true);
	$ringup_list_id 		= $agi -> get_variable("RINGUPLISTID", true);
	$ringup_id			= $agi -> get_variable("RINGUPID", true);
	$callback_leg			= $agi -> get_variable("LEG", true);
	$usedratecard			= $agi -> get_variable("RATECARD", true);

//	if (is_numeric($called_party) && strlen($called_party > 6))
	$A2B -> CID_handover		= $called_party;

	$QUERY = "UPDATE cc_trunk SET inuse=inuse+1 WHERE id_trunk=".$callback_usedtrunk;
	$res = $A2B -> DBHandle -> Execute($QUERY);

	// |MODEFROM=ALL-CALLBACK|TARIFF=".$A2B ->tariff;
	$A2B -> extension = $A2B -> dnid = $A2B -> destination = $calling_party;
	$A2B -> CallerID =  $called_party;

	if ($callback_mode=='CID') {
		$charge_callback = 1;
		$A2B -> agiconfig['use_dnid'] = 0;

	} elseif ($callback_mode=='CID-PROMPT') {
		$charge_callback = 1;
		$A2B -> agiconfig['use_dnid'] = 1;

	} elseif ($callback_mode=='ALL') {
		$A2B -> agiconfig['use_dnid'] = ($calling_party)?1:0;
		$A2B -> agiconfig['cid_enable'] = 0;

	} else {
		if (is_numeric($callback_mode)) {
			$A2B->recalltime = $callback_mode * 60;
			$A2B -> agiconfig['number_try']=1;
			$A2B -> agiconfig['play_audio'] = 0;
		}
		$charge_callback = 1;
		// FOR THE WEB-CALLBACK
		$A2B -> agiconfig['use_dnid'] = 1;
		$A2B -> agiconfig['say_balance_after_auth'] = 0;
		$A2B -> agiconfig['cid_enable'] = 0;
		$A2B -> agiconfig['say_timetocall'] = 0;
	}

	if ($A2B -> agiconfig['callback_beep_to_enter_destination'] == 1) {
	    $A2B -> callback_beep_to_enter_destination = True;
	}

	$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[CALLBACK]:[GET VARIABLE : CALLED=$called_party | CALLING=$calling_party | MODE=$callback_mode | TARIFF=$callback_tariff | CALLBACKID={$A2B->callback_id} |OR| RINGUPLISTID=$ringup_list_id | LEG=$callback_leg]");
	
	if ($ringup_list_id) {
	    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[RING-UP : AGI PROCESSING ]");
	} else {
	    $QUERY = "UPDATE cc_callback_spool SET agi_result='AGI PROCESSING' WHERE uniqueid LIKE '$callback_uniqueid'";
	    $res = $A2B -> DBHandle -> Execute($QUERY);
	    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK : UPDATE CALLBACK AGI_RESULT : QUERY=$QUERY]");
	}

	/* WE START ;) */
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[TRY : callingcard_ivr_authenticate]");
	$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
	if ($cia_res==0) {

		$charge_callback = 1; // EVEN FOR  ALL CALLBACK
		$callback_leg = $A2B -> username;

		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[Start]");
		$A2B -> callingcard_auto_setcallerid($agi);

		for ($i=0;$i< $A2B -> agiconfig['number_try'] ;$i++) {

			$RateEngine->Reinit();
			$A2B-> Reinit();
			
			// DIVIDE THE AMOUNT OF CREDIT BY 2 IN ORDER TO AVOID NEGATIVE BALANCE IF THE USER USE ALL HIS CREDIT
			$orig_credit = $A2B -> credit;

			if ($A2B -> agiconfig['callback_reduce_balance'] > 0 && $A2B->credit > $A2B -> agiconfig['callback_reduce_balance']) {
				$A2B->credit = $A2B->credit - $A2B -> agiconfig['callback_reduce_balance'];
			} else {
				$A2B->credit = $A2B->credit / 2;
			}
			
			$stat_channel = $agi -> channel_status($A2B-> channel);
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[CHANNEL STATUS : '.$stat_channel["result"].' = '.$stat_channel["data"].']'.
							"[status_channel=$status_channel]:[ORIG_CREDIT : ".$orig_credit." - CUR_CREDIT - : ".$A2B -> credit.
							" - CREDIT MIN_CREDIT_2CALL : ".$A2B -> agiconfig['min_credit_2call']."]");
			
			if( !$A2B->enough_credit_to_call()) {
				// SAY TO THE CALLER THAT IT DEOSNT HAVE ENOUGH CREDIT TO MAKE A CALL
				$prompt = "prepaid-no-enough-credit-stop";
				$agi -> stream_file($prompt, '#');
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[STOP STREAM FILE $prompt]");
			}

			$isvoip = false;
			$initialdestination = $A2B->extension;
			if ($A2B -> ivr($agi,$A2B->extension,$initialdestination,$isvoip)) {
			    $A2B->destination = $A2B->extension;
			}
			if (stripos($A2B->extension,'QUEUE ') === 0) {
			    $ans = "QUEUE";
			} else {
			    $ans = $A2B -> callingcard_ivr_authorize($agi, $RateEngine, $i, true);
			}

			$QUERY = "SELECT regexten FROM cc_sip_buddies LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->id_card} ) AS v ON bb.concat_id = v.concat_id
								WHERE (id_cc_card = {$A2B->id_card} OR v.concat_id IS NOT NULL) AND name = '{$A2B->src_peername}' AND name = '$called_party' LIMIT 1";
			$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
			if (is_array($result) && $result[0][0] != "") {
				$A2B -> src = $result[0][0];
				$QUERY = "SELECT regexten FROM cc_sip_buddies LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->id_card} ) AS v ON bb.concat_id = v.concat_id
								WHERE (id_cc_card = {$A2B->id_card} OR v.concat_id IS NOT NULL) AND name = '{$A2B->destination}' LIMIT 1";
				$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
				if (is_array($result))	$calling_num = $A2B -> src;
			} else $A2B -> src = 'NULL';

			$calling_party = $agi -> get_variable('CALLERID(name)', true);
//			$agi -> set_variable('__TEMPONFORWARDCIDEXT1', $calling_num);
			$agi -> set_variable('CALLERID(num)', $calling_num);
			if ($calling_party == '') {
				$agi -> set_variable('CALLERID(name)', ($A2B -> config['callback']['callerid'] != '') ? $A2B -> config['callback']['callerid'] : $calling_num);
			}
			// CREATE A PERSONAL UNIQUEID FOR EACH TRY
			if ($i>0) {
				$newuniqueid = explode('.',$A2B -> uniqueid);
				if ($newuniqueid[0] == time())		sleep(1);
				$newuniqueid[0] = time();
				$A2B -> uniqueid = implode('.',$newuniqueid);
			}
			if ($ans=="QUEUE") {
					$dialstr = $A2B->destination;
					$que = explode(",",$dialstr);
					if (stripos($que[1],"r") === false) {
						$A2B -> let_stream_listening($agi);
					} else {
						$A2B -> let_stream_listening($agi,false,true);
					}
					$myres = $A2B -> run_dial($agi, $dialstr);
					$A2B -> DbReConnect($agi);
					$answeredtime = $agi->get_variable("ANSWEREDTIME", true);
					if ($answeredtime == "")
						$answeredtime	= $agi->get_variable("CDR(billsec)",true);
					if ($answeredtime>1356000000) {
						$answeredtime	= time() - $answeredtime;
						$dialstatus	= 'ANSWER';
					} else {
						$answeredtime	= 0;
						$dialstatus	= $A2B -> get_dialstatus_from_queuestatus($agi);
					}
					$bridgepeer = $agi->get_variable('QUEUEDNID', true);
					if (strlen($A2B -> dialstatus_rev_list[$dialstatus])>0) {
						$terminatecauseid = $A2B -> dialstatus_rev_list[$dialstatus];
					} else {
						$terminatecauseid = 0;
					}
					if ($answeredtime == 0) $inst_listdestination4 = $A2B -> realdestination;
					else {
						$inst_listdestination4 = $A2B -> destination = $bridgepeer;
					}
					$QUERY = "SELECT regexten, id_cc_card FROM cc_sip_buddies WHERE name LIKE '{$inst_listdestination4}' LIMIT 1";
					$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
					if (is_array($result)) {
						$A2B->calledexten = ($result[0][0] != "") ? "'".$result[0][0]."'" : 'NULL';
						$card_called = "'".$result[0][1]."'";
					} else {
						$A2B->calledexten = 'NULL';
						$card_called = "'0'";
					}
					$QUERY = "INSERT INTO cc_call (uniqueid, sessionid, card_id, nasipaddress, starttime, sessiontime, calledstation, ".
						" terminatecauseid, stoptime, sessionbill, id_tariffgroup, id_tariffplan, id_ratecard, id_trunk, src, sipiax, id_did, calledexten, card_called, dnid, callbackid) VALUES ".
						"('".$A2B->uniqueid."', '".$A2B->channel."',  '".$A2B->id_card."', '".$A2B->hostname."',";
					$QUERY .= " CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND ";
					$QUERY .= ", '$answeredtime', '".$inst_listdestination4."', '$terminatecauseid', now(), '0', '0', '0', '0', '0', '$A2B->CallerID', '3', '$A2B->id_did', $A2B->calledexten, $card_called, '$A2B->dnid', '$A2B->callback_id')";
					$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY, 0);
					if ($A2B->set_inuse_username)	$A2B -> callingcard_acct_start_inuse($agi,0);

			} else if ($ans=="2FAX") {
				$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[ CALL OF THE SYSTEM - [FAX=".$A2B-> destination."]");
				$A2B -> call_fax($agi);
				if ($A2B->set_inuse_username) {
					$A2B -> callingcard_acct_start_inuse($agi,0);
				}
			} else if ($ans==1) {
				// PERFORM THE CALL
				$QUERY = "SELECT flagringup, timeout1, sound1 FROM cc_callback_spool WHERE uniqueid LIKE '$callback_uniqueid' AND flagringup=1 LIMIT 1";
				$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
				if (is_array($result) && $result[0][0]) {
					sleep($result[0][1]);
					if ($result[0][2])	//$agi -> evaluate("STREAM FILE {$result[0][2]} 0123456789*#");
								$agi-> stream_file($result[0][2], "0123456789*#");
					$RateEngine -> answeredtime = $agi->get_variable("CDR(billsec)",true);
					$RateEngine -> dialstatus = "ANSWER";
					$result_callperf = true;
					
				} else if ($A2B-> destination != 'RECORDER') {
					$result_callperf = $RateEngine->rate_engine_performcall ($agi, $A2B-> destination, $A2B);
				} else {
                                        $A2B->dl_short = MONITOR_PATH . "/" . $A2B->username . "/" . date('Y') . "/" . date('n') . "/" . date('j') . "/";
                                        mkdir($A2B->dl_short);
                                        $dl_short = $A2B->dl_short . $A2B->uniqueid;
                                        while (file_exists($dl_short . ".WAV") || file_exists($dl_short . ".wav") || file_exists($dl_short . ".gsm") || file_exists($dl_short . ".mp3")
                                        || file_exists($dl_short . ".sln") || file_exists($dl_short . ".g723") || file_exists($dl_short . ".g729")) {
                                                $A2B->uniqueid++;
                                                $dl_short = $A2B->dl_short . $A2B->uniqueid;
                                        }
					$agi -> record_file($dl_short, $A2B->agiconfig['monitor_formatfile'], '', $A2B->recalltime * 1000);
					$RateEngine -> answeredtime = $agi->get_variable("CDR(billsec)",true);
					$RateEngine -> dialstatus = "ANSWER";
					$result_callperf = true;
				}
				if (is_numeric($callback_mode)) {
					$QUERY = "UPDATE cc_callback_spool SET status='PENDING', next_attempt_time=NOW(), agi_result='AGI ENDED' WHERE uniqueid LIKE '$callback_uniqueid' AND surveillance > 0";
					$A2B -> DBHandle -> Execute($QUERY);
					$A2B -> recalltime = false;
				}
				if (!$result_callperf) {
					$prompt="prepaid-dest-unreachable";
					$agi -> stream_file($prompt, '#');
				}

				// INSERT CDR  & UPDATE SYSTEM
				if ($A2B->destination!='RINGUP') {
					$RateEngine->rate_engine_updatesystem($A2B, $agi, $A2B->destination);
				}

				if ($A2B->agiconfig['say_balance_after_call']==1) {
					$A2B->fct_say_balance ($agi, $A2B->credit);
				}

				$charge_callback = 1;
				if ($RateEngine->dialstatus == "ANSWER") {
					$callback_been_connected = 1;
				}
				if (!$A2B->dtmf_destination) {
					break;
				}
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[a2billing end loop num_try] RateEngine->usedratecard=".$RateEngine->usedratecard);
			} elseif ($ans=="2DID") {

				$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[ CALL OF THE SYSTEM - [DID=".$A2B-> destination."]");
				
				$QUERY = "SELECT cc_did.id, cc_did_destination.id, billingtype, tariff, destination, voip_call, username, useralias, connection_charge, selling_rate, did, ".
					" aleg_carrier_connect_charge, aleg_carrier_cost_min, aleg_retail_connect_charge, aleg_retail_cost_min, ".
					" aleg_carrier_initblock, aleg_carrier_increment, aleg_retail_initblock, aleg_retail_increment, ".
					" aleg_timeinterval, ".
					" aleg_carrier_connect_charge_offp, aleg_carrier_cost_min_offp, aleg_retail_connect_charge_offp, aleg_retail_cost_min_offp, ".
					" aleg_carrier_initblock_offp, aleg_carrier_increment_offp, aleg_retail_initblock_offp, aleg_retail_increment_offp, ".
					" cc_card.id, playsound, timeout, margin, id_diller, voicebox, removeaddprefix, addprefixinternational, answer, chanlang, ".
					" aftercallbacksound, digitaftercallbacksound, spamfilter, secondtimedays, calleridname, speech2mail, send_text, send_sound, calleesound".
					" FROM cc_did, cc_card, cc_country, cc_did_destination".
					" LEFT JOIN cc_sheduler_ratecard ON id_did_destination=cc_did_destination.id".
					" WHERE id_cc_did=cc_did.id AND cc_card.status=1 AND cc_card.id=id_cc_card and cc_did_destination.activated=1 AND cc_did.activated=1 AND did LIKE '$A2B->destination'".
					" AND cc_country.id=id_cc_country AND cc_did.startingdate <= CURRENT_TIMESTAMP".
					" AND (cc_did.expirationdate > CURRENT_TIMESTAMP OR cc_did.expirationdate IS NULL OR cc_did.expirationdate = '0000-00-00 00:00:00')".
					" AND cc_did_destination.validated = 1 ".
					" AND (`cc_sheduler_ratecard`.`id_did_destination` IS NULL OR".
					    " (`weekdays` LIKE CONCAT('%',WEEKDAY(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1)) )),'%')".
						" AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1)))) BETWEEN `timefrom` AND `timetill`".
						    " OR (`timetill`<=`timefrom` AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1))))<`timetill`".
							" OR TIME(CONVERT_TZ(NOW(),@@global.time_zone,IF(CONCAT(id_timezone+0) = id_timezone, @@global.time_zone, SUBSTRING_INDEX(id_timezone, ';', -1))))>=`timefrom`)))))".
					" ORDER BY priority ASC";

				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, $QUERY);
				$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
				if (is_array($result)) {
					$chanlang = $result[0][37];
					if ($chanlang != 'not_set') {
						$lg_var_set = 'CHANNEL(language)';
						$agi -> set_variable($lg_var_set, $chanlang);
					}
					$A2B -> current_language = $agi -> get_variable('CHANNEL(language)', true);
					if ($result[0][38] && $result[0][38]!="-1") {
						sleep(1); 
						$res_dtmf = $agi->get_data($result[0][38], 10000, 1);
						$dtmf = (string)$res_dtmf["result"];
						if ($result[0][39]!==NULL && strpos((string)$result[0][39], $dtmf)===false) {
							$agi -> hangup();
							break;
						}
					}
					$A2B -> call_2did($agi, $RateEngine, $result, true);
					if ($A2B->set_inuse_username) $A2B -> callingcard_acct_start_inuse($agi,0);
				}
			}
		}//END FOR

		if ($A2B->set_inuse_username) {
			$A2B->callingcard_acct_start_inuse($agi,0);
		}

	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[AUTHENTICATION FAILED (cia_res:".$cia_res.")]");
	}
	$QUERY = "UPDATE cc_trunk SET inuse=inuse-1 WHERE id_trunk=".$callback_usedtrunk;
	$res = $A2B -> DBHandle -> Execute($QUERY);


// MODE CONFERENCE MODERATOR
} elseif ($mode == 'conference-moderator') {

	$callback_been_connected = 0;
//	$endinuse = false;
	
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[MODE : CONFERENCE MODERATOR]');
	
	if ($A2B -> config["callback"]['answer_call']==1) {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[ANSWER CALL]');
		$agi -> answer();
		$status_channel = 6;
	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[NO ANSWER CALL]');
		$status_channel = 4;
	}

	$A2B -> play_menulanguage ($agi);

	$callback_td = $agi -> get_variable("TD", true);
	$callback_usedtrunk = $agi -> get_variable("TRUNK", true);
	$called_party = $agi -> get_variable("CALLED", true);
	$calling_party = $agi -> get_variable("CALLING", true);
	$callback_mode = $agi -> get_variable("MODE", true);
	$callback_tariff = $agi -> get_variable("TARIFF", true);
	$callback_uniqueid = $agi -> get_variable("CBID", true);
	$A2B->callback_id = $agi -> get_variable("CALLBACKID", true);
	$callback_leg = $agi -> get_variable("LEG", true);
	$usedratecard = $agi -> get_variable("RATECARD", true);
    $accountcode = $agi -> get_variable("ACCOUNTCODE", true);
    $phonenumber_member = $agi -> get_variable("PN_MEMBER", true);
    $room_number = $agi -> get_variable("ROOMNUMBER", true);

    $A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[CALLBACK]:[GET VARIABLE : CALLED=$called_party | CALLING=$calling_party | MODE=$callback_mode | TARIFF=$callback_tariff | CBID=$callback_uniqueid | LEG=$callback_leg | ACCOUNTCODE=$accountcode | PN_MEMBER=$phonenumber_member | ROOMNUMBER=$room_number]");


    $error_settings = False;
    $room_number = intval($room_number);
    if ($room_number <= 0){
        $error_settings = True;
    }

    if (strlen($accountcode)==0 || strlen($phonenumber_member)==0) {
        $error_settings = True;
    } else {
        $list_pn_member = preg_split("/[\s;]+/", $phonenumber_member);

        if (count($list_pn_member)==0){
            $error_settings = True;
        }
    }
    
    if ($error_settings) {
        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK : Error settings accountcode and phonenumber_member]");
        $agi -> hangup();
        $A2B -> write_log("[STOP - EXIT]", 0);
        exit();
    }

    $A2B -> username = $A2B -> accountcode = $accountcode;
    $A2B -> callingcard_acct_start_inuse($agi,1);

	if ($callback_mode=='CONF-MODERATOR') {
		$charge_callback = 1;		
		$A2B->CallerID = $called_party;
		$A2B -> agiconfig['number_try'] =1;
		$A2B -> agiconfig['use_dnid'] =1;
		$A2B -> agiconfig['say_balance_after_auth']=0;
		$A2B -> agiconfig['cid_enable'] =0;
		$A2B -> agiconfig['say_timetocall']=0;
	}
    
	$QUERY = "UPDATE cc_callback_spool SET agi_result='AGI PROCESSING' WHERE uniqueid LIKE '$callback_uniqueid'";
	$res = $A2B -> DBHandle -> Execute($QUERY);
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK : UPDATE CALLBACK AGI_RESULT : QUERY=$QUERY]");
    
    
	/* WE START ;) */
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[TRY : callingcard_ivr_authenticate]");
	$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
	if ($cia_res==0) {

		$charge_callback = 1; // EVEN FOR  ALL CALLBACK
		$callback_leg = $A2B -> username;

		
		for ($i=0;$i< $A2B -> agiconfig['number_try'] ;$i++) {
		
			$RateEngine->Reinit();
			//$A2B-> Reinit();
			
			$stat_channel = $agi -> channel_status($A2B-> channel);
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[CHANNEL STATUS : '.$stat_channel["result"].' = '.$stat_channel["data"].']'.
							"[status_channel=$status_channel]:[CREDIT - : ".$A2B -> credit." - CREDIT MIN_CREDIT_2CALL : ".$A2B -> agiconfig['min_credit_2call']."]");
			
			if( !$A2B->enough_credit_to_call()) {
				// SAY TO THE CALLER THAT IT DEOSNT HAVE ENOUGH CREDIT TO MAKE A CALL
				$prompt = "prepaid-no-enough-credit-stop";
				$agi -> stream_file($prompt, '#');
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[STOP STREAM FILE $prompt]");
			}

            // find the route and Initiate new callback for all the members
            foreach ($list_pn_member as $inst_pn_member){
                $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[Spool Callback for the PhoneNumber '.$inst_pn_member.']');
                $A2B -> extension = $A2B -> dnid = $A2B -> destination = $inst_pn_member;

                $resfindrate = $RateEngine->rate_engine_findrates($A2B, $A2B -> destination, $A2B -> tariff);

                // IF FIND RATE
                if ($resfindrate!=0) {
                    //$RateEngine -> debug_st = 1;
                    $res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($A2B, $A2B->credit);

                    if ($res_all_calcultimeout){
                        // MAKE THE CALL
                        $RateEngine -> usedtrunk= $RateEngine -> ratecard_obj[0][29];
                        $prefix 		= $RateEngine -> ratecard_obj[0][30];
                        $tech			= $RateEngine -> ratecard_obj[0][31];
                        $ipaddress		= $RateEngine -> ratecard_obj[0][32];
                        $removeprefix		= $RateEngine -> ratecard_obj[0][33];
                        $timeout		= $RateEngine -> ratecard_obj[0]['timeout'];
                        $failover_trunk 	= $RateEngine -> ratecard_obj[0][35];
                        $addparameter		= $RateEngine -> ratecard_obj[0][36];

                        $destination = $A2B ->destination;
                        if (strncmp($destination, $removeprefix, strlen($removeprefix)) == 0) $destination= substr($destination, strlen($removeprefix));

                        $pos_dialingnumber = strpos($ipaddress, '%dialingnumber%' );

                        $ipaddress = str_replace("%cardnumber%", $A2B->cardnumber, $ipaddress);
                        $ipaddress = str_replace("%dialingnumber%", $prefix.$destination, $ipaddress);

                        if ($pos_dialingnumber !== false) {
                               $dialstr = "$tech/$ipaddress";
                        } else {
                            if ($A2B -> agiconfig['switchdialcommand'] == 1) {
                                $dialstr = "$tech/$prefix$destination@$ipaddress";
                            } else {
                                $dialstr = "$tech/$ipaddress/$prefix$destination";
                            }
                        }

                        //ADDITIONAL PARAMETER 			%dialingnumber%,	%cardnumber%
                        if (strlen($addparameter)>0){
                            $addparameter = str_replace("%cardnumber%", $A2B->cardnumber, $addparameter);
                            $addparameter = str_replace("%dialingnumber%", $prefix.$destination, $addparameter);
                            $dialstr .= $addparameter;
                        }

                        $channel= $dialstr;
                        $exten = $inst_pn_member;
                        $context = 'a2billing-conference-member';;
                        $id_server_group = $A2B -> config["callback"]['id_server_group'];
                        $callerid = $called_party;
                        $priority = 1;
                        $timeout = $A2B -> config["callback"]['timeout']*1000;
                        $application = '';
                        $account = $A2B -> accountcode;
                        $uniqueid = $callback_uniqueid.'-'.MDP_NUMERIC(5);
                        
                        $sep = ($A2B->config['global']['asterisk_version'] == "1_6" || $A2B->config['global']['asterisk_version'] == "1_8")?',':'|';

                        $variable = "CALLED=$inst_pn_member".$sep."CALLING=$inst_pn_member".$sep."CBID=$callback_uniqueid".$sep."TARIFF=$callback_tariff".$sep.
                                    "LEG=".$A2B -> accountcode.$sep."ACCOUNTCODE=".$A2B -> accountcode.$sep."ROOMNUMBER=".$room_number.$sep."RATECARD=".$RateEngine -> ratecard_obj[0][6];
                        
                        $status = 'PENDING';
                        $server_ip = 'localhost';
                        $num_attempt = 0;

                        if (is_numeric($A2B -> config["callback"]['sec_wait_before_callback']) && $A2B -> config["callback"]['sec_wait_before_callback']>=1) {
                            $sec_wait_before_callback = $A2B -> config["callback"]['sec_wait_before_callback'];
                        } else {
                            $sec_wait_before_callback = 1;
                        }

                        $QUERY = " INSERT INTO cc_callback_spool (uniqueid, status, server_ip, num_attempt, channel, exten, context, priority, variable, id_server_group, callback_time, account, callerid, timeout ) VALUES ('$uniqueid', '$status', '$server_ip', '$num_attempt', '$channel', '$exten', '$context', '$priority', '$variable', '$id_server_group', ADDDATE( CURRENT_TIMESTAMP, INTERVAL $sec_wait_before_callback SECOND ), '$account', '$callerid', '$timeout')";
                        $res = $A2B -> DBHandle -> Execute($QUERY);
                        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-ALL : INSERT CALLBACK REQUEST IN SPOOL : QUERY=$QUERY]");
/**
			if ($res && !$A2B->CC_TESTING) {
			    $endinuse = true;
			    $QUERY = "UPDATE cc_trunk SET inuse=inuse+1 WHERE id_trunk='".$channeloutcid[2]."'";
			    $res = $A2B -> DBHandle -> Execute($QUERY);
			}
**/
                    } else {
                        $error_msg = 'Error : You don t have enough credit to call you back !!!';
                        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | $error_msg]");
                    }
                } else {
                    $error_msg = 'Error : There is no route to call back your phonenumber !!!';
                    $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK-CALLERID : CALLED=".$A2B ->destination." | $error_msg]");
                }
            }

			// DIAL INTO THE CONFERENCE AS ADMINISTRATOR
            $dialstr = "local/$room_number@a2billing-conference-room";
            
            $A2B -> debug( INFO, $agi, __FILE__, __LINE__, "DIAL $dialstr");
            $myres = $A2B -> run_dial($agi, $dialstr);
            
            $charge_callback = 1;
/**
	    if ($endinuse) {
		$QUERY = "UPDATE cc_trunk SET inuse=inuse-1 WHERE id_trunk='".$channeloutcid[2]."'";
		$res = $A2B -> DBHandle -> Execute($QUERY);
	    }
**/
		}//END FOR

		if ($A2B->set_inuse_username) {
			$A2B->callingcard_acct_start_inuse($agi,0);
		}

	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[AUTHENTICATION FAILED (cia_res:".$cia_res.")]");
	}

// MODE CONFERENCE MEMBER
} elseif ($mode == 'conference-member') {

	$callback_been_connected = 0;
	
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[MODE : CONFERENCE MEMBER]');
	
	if ($A2B -> config["callback"]['answer_call']==1) {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[ANSWER CALL]');
		$agi -> answer();
		$status_channel = 6;
	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[NO ANSWER CALL]');
		$status_channel = 4;
	}

    $A2B -> play_menulanguage ($agi);
    
	$callback_td = $agi -> get_variable("TD", true);
	$callback_usedtrunk = $agi -> get_variable("TRUNK", true);
	$called_party = $agi -> get_variable("CALLED", true);
	$calling_party = $agi -> get_variable("CALLING", true);
	$callback_mode = $agi -> get_variable("MODE", true);
	$callback_tariff = $agi -> get_variable("TARIFF", true);
	$callback_uniqueid = $agi -> get_variable("CBID", true);
	$A2B->callback_id = $agi -> get_variable("CALLBACKID", true);
	$callback_leg = $agi -> get_variable("LEG", true);
	$usedratecard = $agi -> get_variable("RATECARD", true);
    $accountcode = $agi -> get_variable("ACCOUNTCODE", true);
    $room_number = $agi -> get_variable("ROOMNUMBER", true);

    $A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[CALLBACK]:[GET VARIABLE : CALLED=$called_party | CALLING=$calling_party | MODE=$callback_mode | TARIFF=$callback_tariff | CBID=$callback_uniqueid | LEG=$callback_leg | ACCOUNTCODE=$accountcode | ROOMNUMBER=$room_number]");


    $error_settings = False;
    $room_number = intval($room_number);
    if ($room_number <= 0){
        $error_settings = True;
    }

    if (strlen($accountcode)==0) {
        $error_settings = True;
    }
    
    if ($error_settings) {
        $A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK : Error settings accountcode]");
        $agi -> hangup();
        $A2B -> write_log("[STOP - EXIT]", 0);
        exit();
    }

    $A2B -> username = $A2B -> accountcode = $accountcode;
    $A2B -> callingcard_acct_start_inuse($agi,1);

	if ($callback_mode=='CONF-MODERATOR') {
		$charge_callback = 1;		
		$A2B->CallerID = $called_party;
		$A2B -> agiconfig['number_try'] =1;
		$A2B -> agiconfig['use_dnid'] =1;
		$A2B -> agiconfig['say_balance_after_auth']=0;
		$A2B -> agiconfig['cid_enable'] =0;
		$A2B -> agiconfig['say_timetocall']=0;
	}
    
	$QUERY = "UPDATE cc_callback_spool SET agi_result='AGI PROCESSING' WHERE uniqueid LIKE '$callback_uniqueid'";
	$res = $A2B -> DBHandle -> Execute($QUERY);
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK : UPDATE CALLBACK AGI_RESULT : QUERY=$QUERY]");
    
    
	/* WE START ;) */
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[TRY : callingcard_ivr_authenticate]");
	$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
	if ($cia_res==0) {

		$charge_callback = 1; // EVEN FOR  ALL CALLBACK
		$callback_leg = $A2B -> username;
		
		for ($i=0;$i< $A2B -> agiconfig['number_try'] ;$i++) {

			$RateEngine->Reinit();
			//$A2B-> Reinit();
			
			$stat_channel = $agi -> channel_status($A2B-> channel);
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, '[CALLBACK]:[CHANNEL STATUS : '.$stat_channel["result"].' = '.$stat_channel["data"].']'.
							"[status_channel=$status_channel]:[CREDIT - : ".$A2B -> credit." - CREDIT MIN_CREDIT_2CALL : ".$A2B -> agiconfig['min_credit_2call']."]");
			
			if( !$A2B->enough_credit_to_call()) {
				// SAY TO THE CALLER THAT IT DEOSNT HAVE ENOUGH CREDIT TO MAKE A CALL
				$prompt = "prepaid-no-enough-credit-stop";
				$agi -> stream_file($prompt, '#');
				$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[STOP STREAM FILE $prompt]");
			}

            // DIAL INTO THE CONFERENCE AS ADMINISTRATOR
            $dialstr = "local/$room_number@a2billing-conference-room";
            
            $A2B -> debug( INFO, $agi, __FILE__, __LINE__, "DIAL $dialstr");
            $myres = $A2B -> run_dial($agi, $dialstr);


            $charge_callback = 1;
			
		}//END FOR

		if ($A2B->set_inuse_username) {
			$A2B->callingcard_acct_start_inuse($agi,0);
		}

	} else {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK]:[AUTHENTICATION FAILED (cia_res:".$cia_res.")]");
	}

}




// CHECK IF WE HAVE TO CHARGE CALLBACK
if ($charge_callback) {

	$A2B->uniqueid = $callbackuniqueid;
	$callback_username = $callback_leg;
	$A2B -> accountcode = $callback_username;
	$A2B -> agiconfig['say_balance_after_auth'] = 0;
	$A2B -> agiconfig['cid_enable'] = 0;
	$A2B -> agiconfig['say_timetocall'] = 0;

	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[INFO FOR THE 1ST LEG - callback_username=$callback_username");
	$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[TRY : callingcard_ivr_authenticate]");
	$cia_res = $A2B -> callingcard_ivr_authenticate($agi);
	
	//overrides the tariff for the user with the one passed in.
	if (strlen($callback_tariff) > 0)
	{ 
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "*** Tariff override **** Changing from ".$A2B -> tariff." to ".$callback_tariff." cia_res=$cia_res");
		$A2B -> tariff = $callback_tariff;
	}
	
	$QUERY = "";
	if ($cia_res==0) {
		$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[MAKE BILLING FOR THE 1ST LEG - TARIFF:".$A2B -> tariff.";CALLED=$called_party]");
		$A2B -> agiconfig['use_dnid'] = 1;
		$A2B -> dnid = $A2B -> destination = $called_party;
		
		$resfindrate = $RateEngine -> rate_engine_findrates($A2B, $called_party, $A2B -> tariff);
		// IF FIND RATE
		if ($resfindrate != 0) {
			if (isset($usedratecard)) {
				for ($k=0;$k<count($RateEngine -> ratecard_obj);$k++) {
					if ($RateEngine -> ratecard_obj[$k][6] == $usedratecard) {
						$RateEngine -> usedratecard = $k;
						break;
					}
				}
			} else $RateEngine -> usedratecard = 0;
			$res_all_calcultimeout = $RateEngine -> rate_engine_all_calcultimeout($A2B, $A2B->credit);

			if ($res_all_calcultimeout) {
				// SET CORRECTLY THE CALLTIME FOR THE 1st LEG
				$RateEngine -> answeredtime  = time() - $G_startime;
				$RateEngine -> dialstatus = 'ANSWERED';
				$A2B -> debug( INFO, $agi, __FILE__, __LINE__, "[CALLBACK]:[RateEngine -> answeredtime=".$RateEngine -> answeredtime."]");
				
				if (isset($cid)) {
					$A2B -> CallerID	= $cid;
					$A2B -> src_peername	= 'NULL';
				} else
					$A2B -> CallerID =  $A2B -> config["callback"]['callerid'];
				unset($A2B->src);
				//(ST) replace above code with the code below to store CDR for all callbacks and to only charge for the callback if requested
				if ($callback_been_connected==1 || ($A2B -> agiconfig['callback_bill_1stleg_ifcall_notconnected']==1) )  {
					//(ST) this is called if we need to bill the user
					$RateEngine -> rate_engine_updatesystem($A2B, $agi, $A2B-> destination, 1, 0, 1, $callback_usedtrunk, $callback_td, $callback_mode);
				} else {
					//(ST) this is called if we don't bill ther user but to keep track of call costs
					$RateEngine -> rate_engine_updatesystem($A2B, $agi, $A2B-> destination, 0, 0, 1, $callback_usedtrunk, $callback_td, $callback_mode);
				}
				if ($RateEngine->answeredtime > 0 && isset($ringup_list_id) && $ringup_list_id && $ringup_id) {
					$QUERY = ",`cc_ringup`.`status`=IF(`lefte`='1',2,`cc_ringup`.`status`),`processed`=`processed`+1,`lefte`=`lefte`-1,`passed`=1,`keyspressed`='$A2B->dtmfs'";
				}
			} else {
				$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[ERROR - BILLING FOR THE 1ST LEG - rate_engine_all_calcultimeout: CALLED=$called_party]");
			}
		} else {
			$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[ERROR - BILLING FOR THE 1ST LEG - rate_engine_findrates: CALLED=$called_party - RateEngine->usedratecard=".$RateEngine->usedratecard."]");
		}
	} else {
		$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[ERROR - AUTHENTICATION USERNAME]");
	}
	$QUERY = "UPDATE `cc_ringup`,`cc_ringup_list` SET `cc_ringup`.`inuse`=`cc_ringup`.`inuse`-1,`cc_ringup_list`.`inuse`='0'".$QUERY." WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$ringup_list_id'";
	$A2B -> DBHandle -> Execute($QUERY);
$A2B -> debug( ERROR, $agi, __FILE__, __LINE__, "[CALLBACK 1ST LEG]:[FINISH : $QUERY]");
}// END if ($charge_callback)

// END
if (($mode != 'cid-callback' && $mode != 'all-callback' && $mode != 'did' && $mode != 'standard' && $mode != 'sms') || $A2B -> agiconfig['answer_call'] == 1) {
	$agi -> hangup();
}

// SEND MAIL REMINDER WHEN CREDIT IS TOO LOW
if (isset($send_reminder) && $send_reminder == 1 && $A2B -> agiconfig['send_reminder'] == 1) {
	if (strlen($A2B -> cardholder_email) > 5) {
		include_once (dirname(__FILE__)."/lib/Class.Mail.php");
		try {
			$mail = new Mail(Mail::$TYPE_REMINDERCALL,$A2B->id_card,null,null,null,$A2B->DBHandle);
			$mail -> send();
			$A2B -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SEND-MAIL REMINDER]:[TO:".$A2B -> cardholder_email." - FROM:$from - SUBJECT:$subject]");
		} catch (A2bMailException $e) {
		}
	}
}

if ($A2B->set_inuse_username) {
	$A2B->callingcard_acct_start_inuse($agi,0);
}

/************** END OF THE APPLICATION ****************/
$A2B -> write_log("[exit]", 0);
