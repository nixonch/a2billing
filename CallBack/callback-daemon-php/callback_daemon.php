#!/usr/bin/php -q
<?php

//$id_server_group=1;

declare(ticks = 1);
if (function_exists('pcntl_signal'))
{
    pcntl_signal(SIGHUP, SIG_IGN); // Указываем игнорировать сигнал требования перезапуска
}

set_time_limit(0);
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING));


include ("lib/admin.defines.php");
include ("lib/Class.RateEngine.php");
include ("lib/ProcessHandler.php");
include ("lib/phpagi/phpagi-asmanager.php");

function reasondesc($reason) {
	switch($reason)
	{
	    case  0: return "CHANUNAVAIL";
	    case  1: return "BUSY";
	    case  3: return "NOANSWER";
	    case  4: return "ANSWER";
	    case  8: return "CHANUNAVAIL";
	    default: return "CONGESTION";
	}
}

function originateresponse($e, $parameters, $server, $port, &$ast) {

    if ($parameters['ActionID'] == $ast->actionid) {
	$ansnum   = $parameters['Reason'];
	$channel  = $parameters['Channel'];
	$ansalpha = reasondesc($ansnum);
//	write_log(LOGFILE_API_CALLBACK, "OriginateResponse{$ansalpha}: ".var_export($parameters, true));
	return array($ansnum,$ansalpha,$channel);
    }
    return false;
}

function newstateresponse($e, $parameters, $server, $port, &$ast) {

    if ($e == "varset" && $parameters['Variable'] == "ACTIONID" && strcmp($parameters['Value'], $ast->actionid) === 0) {
	if (isset($ast->event_handlers[$e])) unset($ast->event_handlers[$e]);
	$ast->channel = $parameters['Channel'];
    }
    if ($e == 'newstate' && $parameters['Channel'] == $ast->channel && !isset($parameters['Value'])) {
//	write_log(LOGFILE_API_CALLBACK, "NewState: ".var_export($parameters, true));
//	write_log(LOGFILE_API_CALLBACK, var_export($ast->actionid, true) . " GetVar: " . var_export($res, true));
	if (isset($ast->event_handlers[$e])) unset($ast->event_handlers[$e]);
	$res = $parameters['ChannelStateDesc'];
	if ($res =="Up")	$res = "ANSWER";
	return strtoupper($res);
    }
    if ($e == 'originateresponse' && $parameters['ActionID'] == $ast->actionid) {
//	write_log(LOGFILE_API_CALLBACK, "OriginateResponse: ".var_export($parameters, true));
	return reasondesc($parameters['Reason']);
    }
    return false;
}

function callback_engine($server, $username, $secret, $AmiVars, $destination, $tariff) {

global $A2B;

    $A2B -> cardnumber = $AmiVars[4];

    if ($A2B -> callingcard_ivr_authenticate_light ($error_msg))
    {
	$RateEngine = new RateEngine();
	$RateEngine -> webui = 0;

//	LOOKUP RATE : FIND A RATE FOR THIS DESTINATION
	$A2B -> agiconfig['accountcode'] = $A2B -> cardnumber;
	$A2B -> agiconfig['use_dnid'] = 1;
	$A2B -> agiconfig['say_timetocall'] = 0;
	$A2B -> extension = $A2B -> dnid = $A2B -> destination = $destination;

	$resfindrate = $RateEngine->rate_engine_findrates($A2B, $destination, $tariff);

//	IF FIND RATE
	if ($resfindrate!=0)
	{
	    $res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($A2B, $A2B->credit);
	    if ($res_all_calcultimeout)
	    {
		$ast = new AGI_AsteriskManager();
		$res = $ast -> connect($server, $username, $secret);
		if (!$res) return -4;
//		MAKE THE CALL
		$res = $RateEngine->rate_engine_performcall(false, $destination, $A2B, 8, $AmiVars, $ast);
		$ast -> disconnect();
		if ($res !== false) return $res;
		else return -2; // not enough free trunk for make call
	    }
	    else return -3; // not have enough credit to call you back
	}
	else return -1; // no route to call back your phonenumber
    }
    else return -1; // ERROR MESSAGE IS CONFIGURE BY THE callingcard_ivr_authenticate_light
}

function ringup_engine($server, $username, $secret, $AmiVars, $destination, $tariff, $trunk) {

global $A2B;

	$A2B -> cardnumber = $AmiVars[4];

	$removeprefix	= explode(",",$trunk[4]);
	$prefix 	= $trunk[1];
	$ipaddress	= $trunk[3];
	$tech		= $trunk[2];
	if (is_array($removeprefix) && count($removeprefix)>0) {
	    foreach ($removeprefix as $testprefix) {
		if (substr($destination,0,strlen($testprefix))==$testprefix) {
		    $destination = substr($destination,strlen($testprefix));
		    break;
		}
	    }
	}
	$pos_dialingnumber = max(strpos($ipaddress, '%dialingnumber%'),strpos($ipaddress, '%none%'));
	$ipaddress = str_replace("%none%", '', $ipaddress);
	if (strncmp($destination, $prefix, strlen($prefix)) == 0 && strlen($prefix) > 1)	$prefix="";
	$ipaddress = str_replace("%dialingnumber%", $prefix.$destination, $ipaddress);
	if ($pos_dialingnumber !== false) {
	    $channel = "$tech/$ipaddress";
	} elseif ($A2B->agiconfig['switchdialcommand'] == 1) {
	    $channel = "$tech/$prefix$destination@$ipaddress";
	} else {
	    $channel = "$tech/$ipaddress/$prefix$destination";
	}
	$ast = new AGI_AsteriskManager();
	$res = $ast -> connect($server, $username, $secret);
	if (!$res) return -4;
	$ast -> actionid = $AmiVars[5];
	$ast -> add_event_handler('VarSet', 'newstateresponse');
	$ast -> add_event_handler('Newstate', 'newstateresponse');
	$ast -> add_event_handler('OriginateResponse', 'newstateresponse');
//	MAKE THE CALL
	write_log(LOGFILE_API_CALLBACK, " ActionID = {$ast->actionid} [#### Starting AMI ORIGINATE     ####] $channel ");
	$res = $ast -> Originate($channel,$AmiVars[0],$AmiVars[6],NULL,NULL,NULL,$A2B->config['callback']['timeout']*1000,$AmiVars[2],$AmiVars[3],$AmiVars[4],true,$ast->actionid);
	write_log(LOGFILE_API_CALLBACK, " ActionID = {$ast->actionid} [#### RESULT   AMI ORIGINATE     ####]\r\n".var_export($res, true));
	write_log(LOGFILE_API_CALLBACK, " ActionID = {$ast->actionid} [#### Starting AMI WAIT_RESPONSE ####] $channel ");
	$response = $ast -> wait_response(true);
	if ($AmiVars[0] && $response=='ANSWER') {
	    if ($AmiVars[7])	$res = $ast -> AbsoluteTimeout($ast->channel, $AmiVars[7]-0.2);
	} elseif ($ast->channel) {
	    $ast -> Hangup ($ast->channel);
	}
	$ast -> disconnect();
	if (is_array($response)) {
		write_log(LOGFILE_API_CALLBACK, " ActionID = {$ast->actionid} [#### ERROR    AMI WAIT_RESPONSE ####]: ".var_export($response, true));
		$response = 'ERROR';
	}
	write_log(LOGFILE_API_CALLBACK, " ActionID = {$ast->actionid} [#### RESULT   AMI WAIT_RESPONSE ####] $channel = ".$response." ");
	return $response;
//	if ($res !== false) return $res;
//	else return -2; // not enough free trunk for make call
}

function statussave($status,$manager_id,$cc_id)
{
    global $A2B;
    $query = "UPDATE `cc_callback_spool` SET `status`='$status',`id_server`='$manager_id' WHERE `id`=$cc_id";
    if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
}

$FG_DEBUG = 0;
$verbose_level = 1;

$A2B = new A2Billing();
$A2B->load_conf($agi);

if (!defined('PID'))
    define("PID", $A2B->config["daemon-info"]['pidfile']);

// CHECK IF THE DAEMON IS ALREADY RUNNING
$pH = new ProcessHandler();
if ($pH->isActive()) {
    die("Already running!");
} else {
    $pH->activate();
}

write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . "[#### CALLBACK ENGINE START ####]");

if (!$A2B->DbConnect()) {
    echo "[Cannot connect to the database]\n";
    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . "[Cannot connect to the database]");
    exit;
}

if ($A2B->config["database"]['dbtype'] == "postgres")
    $UNIX_TIMESTAMP = "date_part('epoch',";
else
    $UNIX_TIMESTAMP = "UNIX_TIMESTAMP(";

$instance_table = new Table();
$A2B -> set_instance_table ($instance_table);
$intid = 0;

while(true)
{
    pcntl_wait($status, WNOHANG);
    $query="SELECT `id`,`status`,`exten_leg_a`,`account`,`callerid`,`exten`,`context`,`priority`,`variable`,`timeout`,`reason`,`num_attempts_unavailable`,`num_attempts_busy`,`num_attempts_noanswer`,".
			"TIMEDIFF(now(),`callback_time`),`id_server_group`, `surveillance`, `inputa`, LEAST(IF(`max_attempt`<0,1,`max_attempt`-`num_attempt`),IFNULL(`inputb`,1)), IFNULL(`inputc`,-1), `calleridprefix`, `calleridlength`, `flagringup`".
		" FROM `cc_callback_spool` LEFT JOIN `cc_sheduler_ratecard` ON `id_callback`=`id`".
		" WHERE `status`='PENDING' AND (`next_attempt_time`<=now() OR ISNULL(`next_attempt_time`)) AND (max_attempt=-1 OR num_attempt<max_attempt)".
		" AND (flagringup=0 OR (`weekdays` LIKE CONCAT('%',WEEKDAY(CONVERT_TZ(NOW(),@@global.time_zone,localtz)),'%') AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,localtz)) BETWEEN `timefrom` AND `timetill`".
		"	OR (`timetill`<=`timefrom` AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,localtz))<`timetill` OR TIME(CONVERT_TZ(NOW(),@@global.time_zone,localtz))>=`timefrom`)))))".
		" GROUP BY id";
		
    $result=$instance_table->SQLExec($A2B->DBHandle, $query);
    foreach ($result as $value) {
	list($cc_id,$cc_status,$cc_exten_leg_a,$cc_account,$cc_callerid,$cc_exten,$cc_context,$cc_priority,$cc_variable,$cc_timeout,$cc_reason,$cc_num_attempts_unavailable,$cc_num_attempts_busy,$cc_num_attempts_noanswer,$cc_timediff,$id_server_group,$duration,$secperaction,$callsperaction,$maxduration,$calleridprefix,$calleridlength,$flagringup)=$value;
	if ($duration > 0) {
		$cc_timediff = 0;
	}
	$query="SELECT `id`,`manager_host`,`manager_username`,`manager_secret` FROM `cc_server_manager` WHERE `id_group`=$id_server_group LIMIT 1";
	$result1=$instance_table->SQLExec($A2B->DBHandle, $query);
	if (!(is_array($result1) && count($result1)>0)) {
	    print("id_server_group $id_server_group does not exist\n");
	    exit(1);
	}
	list($manager_id,$manager_host,$manager_username,$manager_secret)=$result1[0];
	$query="UPDATE `cc_server_manager` SET `lasttime_used`=now()";
	if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");

	$query="SELECT `tariff`,`cbtimeoutunavailable`,`cbattemptunavailable`,`cbtimeoutbusy`,`cbattemptbusy`,`cbtimeoutnoanswer`,`cbattemptnoanswer`,`cbtimeoutmax`,TIME_TO_SEC(TIMEDIFF(SEC_TO_TIME(`cbtimeoutmax`),'$cc_timediff')) FROM `cc_card` WHERE `username`='$cc_account' LIMIT 1";
	$result1=$instance_table->SQLExec($A2B->DBHandle, $query);
	if (!(is_array($result1) && count($result1)>0)) die("Can't execute query '$query'\n");
	list($acc_tariff,$acc_to_unav,$acc_max_unav,$acc_to_busy,$acc_max_busy,$acc_to_noansw,$acc_max_noansw,$acc_max_timeout,$acc_timeout_res)=$result1[0];
	if ($duration > 0) {
		$acc_to_unav=$acc_to_busy=$acc_to_noansw=$cc_num_attempts_unavailable=$cc_num_attempts_busy=$cc_num_attempts_noanswer=0;
	}
	if    ($flagringup == 0 && $acc_timeout_res < 0)			statussave('ERROR_TIMEOUT',$manager_id,$cc_id);
	elseif($flagringup == 0 && $acc_max_unav<=$cc_num_attempts_unavailable) statussave('ERROR_UNAVAILABLE',$manager_id,$cc_id);
	elseif($flagringup == 0 && $acc_max_busy<=$cc_num_attempts_busy)	statussave('ERROR_BUSY',$manager_id,$cc_id);
	elseif($flagringup == 0 && $acc_max_noansw<=$cc_num_attempts_noanswer)	statussave('ERROR_NO-ANSWER',$manager_id,$cc_id);
	else for (;$callsperaction>0;$callsperaction--) {
	    $A2B->DbDisconnect();
	    $intid++;
	    $pid=pcntl_fork();
	    if($pid==-1) {
		print("Can't fork!\n");
		exit(2);
	    }
	    elseif($pid) {
		pcntl_wait($status, WNOHANG);
		$A2B -> DbConnect($agi);
		$A2B -> set_instance_table ($instance_table);
		continue;
	    }
	    else {
		ob_start();
		register_shutdown_function(function(){ob_end_clean();posix_kill(getmypid(), SIGKILL);}, array());

		$A2B -> DbConnect($agi);
		$A2B -> set_instance_table ($instance_table);
		/*if ($flagringup==2) {
		} else*/ if ($flagringup==1) {
			$query="UPDATE `cc_callback_spool` SET `status`='PENDING',`num_attempt`=`num_attempt`+1,`last_attempt_time`=now(),`next_attempt_time`=ADDTIME(now(),SEC_TO_TIME($secperaction)),`id_server`='$manager_id' WHERE `id`=$cc_id";
		} else {
			$query="UPDATE `cc_callback_spool` SET `status`='PROCESSING',`num_attempt`=`num_attempt`+1,`last_attempt_time`=now(),`id_server`='$manager_id' WHERE `id`=$cc_id";
		}
		if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
		if ($calleridprefix) {
			$prefixes = explode(",", $calleridprefix);
			$countprefixes = count($prefixes) - 1;
			$numcid = mt_rand(0,$countprefixes);
			$cc_callerid = $prefixes[$numcid];
			$chrs = $calleridlength-strlen($cc_callerid);
			for($i = 0; $i < $chrs; $i++){
			        $cc_callerid .= mt_rand(0,9);
			}
		}
		$cc_variable = "CALLBACKID=".$cc_id.",".$cc_variable;
		$return=callback_engine($manager_host.":5038", $manager_username, $manager_secret, array($cc_exten,$cc_priority,$cc_callerid,$cc_variable,$cc_account,$cc_id."-".$intid,$cc_context,$maxduration), $cc_exten_leg_a, $acc_tariff);
		$timeout=-1;
		$fatal=0;
		switch($return)
		{
		    case -4: $last_status="ERROR_AMI";$fatal=1;break; // AMI not have connecting
		    case -3: $last_status="ERROR_NO-MONEY";$fatal=1;break; // not have enough credit to call you back
		    case -2: $last_status="ERROR_CHANNEL-UNAVAILABLE";$timeout=$acc_to_unav;break; // not enough free trunk for make call
		    case -1: $last_status="ERROR_NO-RATE-AVAILABLE";$fatal=1;break; // no route to call back your phonenumber or other fatal errors
		    case  0: $last_status="ERROR_CHANNEL-UNAVAILABLE";$timeout=$acc_to_unav;break;
		    case  1: $last_status="BUSY";$timeout=$acc_to_busy;break;
		    case  3: $last_status="NO-ANSWER";$timeout=$acc_to_noansw;break;
		    case  4: $last_status="SENT";$fatal=1;break;
		    case  5: $last_status="ERROR_CONGESTION";$fatal=1;break;
		    case  8: $last_status="ERROR_CONGESTION_OR_CHANNEL-UNAVAILABLE";$fatal=0;$timeout=$acc_to_unav;break;
		    default: $last_status="ERROR_UNKNOWN (#$return)";$fatal=1;break;
		}
		if($fatal && ($duration == 0 || $return == 4) && $flagringup==0) {
			$status=$last_status;
		} else	$status='PENDING';
		$query="UPDATE `cc_callback_spool` SET `status`='$status',`last_status`='$last_status',`manager_result`='$last_status'";
		if($return==-2 || $return==0 || $return==8) $query.=",`num_attempts_unavailable`=`num_attempts_unavailable`+1";
		if($return==1) $query.=",`num_attempts_busy`=`num_attempts_busy`+1";
		if($return==3) $query.=",`num_attempts_noanswer`=`num_attempts_noanswer`+1";
		if($return<=-3 || $return==-1) $query.=",`num_attempt`=`num_attempt`-1";
		if($timeout>=0 && $maxduration==0) $query.=",`next_attempt_time`=ADDTIME(now(),SEC_TO_TIME($timeout))";
		$query.=" WHERE `id`=$cc_id";
		if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
		$A2B->DbDisconnect();
		exit(0);
	    }
	}
    }
// while (false) {
    $query = "SELECT `cc_ringup`.`id`,`simult`-`cc_ringup`.`inuse`,`trunks`,`id_server_group`,`username`,`tariff`,`destination`,`callerids`,IFNULL(`inputa`,0),IFNULL(`inputb`,0),maxduration FROM `cc_ringup`
    LEFT JOIN `cc_card` ON `cc_card`.`id`=`account_id`
    LEFT JOIN `cc_sheduler_ratecard` ON `id_ringup`=`cc_ringup`.`id`
    WHERE `cc_ringup`.`status`='1' AND `cc_ringup`.`inuse`<`simult`
	AND (`cc_sheduler_ratecard`.`id_ringup` IS NULL OR (`weekdays` LIKE CONCAT('%',WEEKDAY(CONVERT_TZ(NOW(),@@global.time_zone,localtz)),'%') AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,localtz)) BETWEEN `timefrom` AND `timetill`
	OR (`timetill`<=`timefrom` AND (TIME(CONVERT_TZ(NOW(),@@global.time_zone,localtz))<`timetill` OR TIME(CONVERT_TZ(NOW(),@@global.time_zone,localtz))>=`timefrom`)))))";
    $result1 = $instance_table->SQLExec($A2B->DBHandle, $query);
    foreach ($result1 as $valringup) {
	list($ringup_id,$simult_left,$trunks,$id_server_group,$cc_account,$acc_tariff,$cc_exten,$callerids,$timeout,$adding_attempts,$maxduration)=$valringup;
	$query = "SELECT `id_trunk`,`trunkprefix`,`providertech`,`providerip`,`removeprefix`,`outbound_cidgroup_id` FROM `cc_trunk` WHERE `id_trunk` IN ($trunks) AND `status`='1' AND (`maxuse`='-1' OR `inuse`<`maxuse`) ORDER BY RAND() LIMIT 1";
	$trunk = $instance_table->SQLExec($A2B->DBHandle, $query);
	$query = "SELECT `id`,`manager_host`,`manager_username`,`manager_secret` FROM `cc_server_manager` WHERE `id_group`='$id_server_group' LIMIT 1";
	$result= $instance_table->SQLExec($A2B->DBHandle, $query);
	if (!(is_array($result) && count($result)>0)) {
		print("id_server_group $id_server_group does not exist\n");
		exit(1);
	}
	$query="UPDATE `cc_server_manager` SET `lasttime_used`=now()";
	if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
	list($manager_id,$manager_host,$manager_username,$manager_secret)=$result[0];
	$query = "SELECT `id`,`try`,`tonum` FROM `cc_ringup_list` WHERE `id_ringup`='$ringup_id' AND `inuse`='0' AND `passed`='0' AND try<=$adding_attempts AND lastattempt<DATE_SUB(NOW(), INTERVAL $timeout MINUTE) LIMIT $simult_left";
	$result = $instance_table->SQLExec($A2B->DBHandle, $query);
	if (is_array($result) && count($result)>0) {
//	    $ringupcount = count($result);
	    foreach ($result as $valnum) {
		list($cc_id,$try,$cc_exten_leg_a)=$valnum;
		$cc_context = $A2B->config['callback']['context_callback'];
//		$maxduration = -1;
//		$trunk = $trunk[0][0];
		$A2B->DbDisconnect();
		$intid++;
		$pid=pcntl_fork();
		if($pid==-1) {
		    print("Can't fork!\n");
		    exit(2);
		} elseif($pid) {
		    pcntl_wait($status, WNOHANG);
		    $A2B -> DbConnect($agi);
		    $A2B -> set_instance_table ($instance_table);
		} else {
		    ob_start();
		    register_shutdown_function(function(){ob_end_clean();posix_kill(getmypid(), SIGKILL);}, array());
		    $A2B -> DbConnect($agi);
		    $A2B -> set_instance_table ($instance_table);
		    if ($callerids) {
			$callerids = explode(',',$callerids);
			foreach($callerids as $key=>$value){
			    if ($value==$cc_exten_leg_a) unset($callerids[$key]);
			}
		    }
		    if (count($callerids)>0) {
			$outcid = $callerids[mt_rand(0, count($callerids)-1)];
//			$outcid .= "<$outcid>";
		    } else {
			$query = "SELECT cid FROM cc_outbound_cid_list WHERE activated = 1 AND outbound_cid_group = {$trunk[0][5]} AND cid NOT LIKE '$cc_exten_leg_a' ORDER BY RAND() LIMIT 1";
			$cidresult = $instance_table->SQLExec($A2B->DBHandle, $query);
			$outcid = (is_array($cidresult) && count($cidresult) > 0) ? $cidresult[0][0] : NULL;
		    }
		    if (is_array($trunk) && count($trunk)>0) {
			$query = "UPDATE `cc_ringup`,`cc_ringup_list`,`cc_trunk` SET `try`=`try`+1,`cc_ringup`.`inuse`=`cc_ringup`.`inuse`+1,`lastattempt`=NOW(),`cc_ringup_list`.`inuse`='1',`cc_trunk`.`inuse`=`cc_trunk`.`inuse`+1 WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$cc_id' AND `id_trunk`='{$trunk[0][0]}'";
			if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
			
			$cc_variable = "ACTIONID=RingUp-$cc_id,RINGUPID=$ringup_id,RINGUPLISTID=$cc_id,CALLED=$cc_exten_leg_a,CALLING=$cc_exten,LEG=$cc_account,TRUNK={$trunk[0][0]}";
			$return = ringup_engine($manager_host.":5038", $manager_username, $manager_secret, array($cc_exten,1,$outcid,$cc_variable,$cc_account,'RingUp-'.$cc_id,$cc_context,$maxduration), $cc_exten_leg_a, $acc_tariff, $trunk[0]);
			if ($cc_exten) {
			    if ($return=='ANSWER') {
				$query = "UPDATE `cc_ringup_list`,`cc_trunk` SET `channelstatedesc`='$return',`cc_trunk`.`inuse`=`cc_trunk`.`inuse`-1 WHERE `id`='$cc_id' AND `id_trunk`='{$trunk[0][0]}'";
				if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
			    } else {
				$query = "UPDATE `cc_ringup`,`cc_ringup_list`,`cc_trunk` SET `cc_ringup`.`status`=IF(`lefte`='1' AND `try`>=$adding_attempts,2,`cc_ringup`.`status`),`cc_ringup`.`inuse`=`cc_ringup`.`inuse`-1,`processed`=IF(`try`>=$adding_attempts,`processed`+1,`processed`),`lefte`=IF(`try`>=$adding_attempts,`lefte`-1,`lefte`),`lastattempt`=NOW(),`cc_ringup_list`.`inuse`='0',`channelstatedesc`='$return',`passed`=0,`cc_trunk`.`inuse`=`cc_trunk`.`inuse`-1 WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$cc_id' AND `id_trunk`='{$trunk[0][0]}'";
				if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
			    }
			} else {
			    $query = "UPDATE `cc_ringup`,`cc_ringup_list`,`cc_trunk` SET `cc_ringup`.`status`=IF(`lefte`='1',2,`cc_ringup`.`status`),`cc_ringup`.`inuse`=`cc_ringup`.`inuse`-1,`processed`=`processed`+1,`lefte`=`lefte`-1,`lastattempt`=NOW(),`cc_ringup_list`.`inuse`='0',`channelstatedesc`='$return',`passed`=1,`cc_trunk`.`inuse`=`cc_trunk`.`inuse`-1 WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$cc_id' AND `id_trunk`='{$trunk[0][0]}'";
			    if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
			}
		    } else {
			/////////////////////////////////////////////////////////////
			$query = "UPDATE `cc_ringup`,`cc_ringup_list` SET `try`=`try`+1,`lastattempt`=NOW(),`cc_ringup`.`inuse`=`cc_ringup`.`inuse`+1,`cc_ringup_list`.`inuse`=`cc_ringup_list`.`inuse`+1 WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$cc_id'";
			if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
			$cc_variable = "RINGUPID=$ringup_id,RINGUPLISTID=$cc_id,CALLED=$cc_exten_leg_a,CALLING=$cc_exten,LEG=$cc_account,RATECARD";
			$return=callback_engine($manager_host.":5038", $manager_username, $manager_secret, array($cc_exten,1,$outcid,$cc_variable,$cc_account,$cc_id."-".$intid,$cc_context,$maxduration), $cc_exten_leg_a, $acc_tariff);
//			$passed = ($return==4)?1:0;
//			$inuse  = ($return==4)?0:1;
			if ($return==4) {
				$query = ",`cc_ringup`.`status`=IF(`lefte`='1',2,`cc_ringup`.`status`)";
			} else	$query = ",`cc_ringup`.`inuse`=`cc_ringup`.`inuse`-1,`cc_ringup_list`.`inuse`='0'";
			if (is_array($return)) $return = 9; //ERROR_UNKNOWN
//			$query = "UPDATE `cc_ringup`,`cc_ringup_list` SET `cc_ringup`.`status`=IF(`lefte`='1',2,`cc_ringup`.`status`),`cc_ringup`.`inuse`=`cc_ringup`.`inuse`-1,`processed`=`processed`+$passed,`lefte`=`lefte`-$passed,`cc_ringup_list`.`inuse`='0',`channelstatedesc`='$return',`lastattempt`=NOW(),`passed`='$passed' WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$cc_id'";
			$query = "UPDATE `cc_ringup`,`cc_ringup_list` SET `channelstatedesc`='$return'".$query." WHERE `cc_ringup`.`id`='$ringup_id' AND `cc_ringup_list`.`id`='$cc_id'";
			if (!$A2B->DBHandle->Execute($query)) die("Can't execute query '$query'\n");
			$timeout=-1;
			$fatal=0;
		    }
		    $A2B->DbDisconnect();
		    exit(0);
		    }
		}
	}
    }
    $query="SELECT `id`,`id_cc_card`,`mailaddr`,`audiofile`,`answeredtime`,`languagecode`,`send_sound`,`send_text`,`save_sound`,`src`,`destination` FROM `cc_recognize_spool` WHERE `status`=0";
    $result=$instance_table->SQLExec($A2B->DBHandle, $query);
    foreach ($result as $value) {
	list($cc_id,$cc_cardid,$cc_mailaddr,$cc_audiofile,$cc_answeredtime,$cc_languagecode,$cc_sendsound,$cc_sendtext,$cc_savesound,$cc_src,$cc_dest)=$value;
	$query="UPDATE `cc_recognize_spool` SET `status`=1 WHERE `id`=$cc_id";
	if (!$A2B->DBHandle->Execute($query)) {
	    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " Can't execute query: " . $query);
	    die("Can't execute query '$query'\n");
	}
	$A2B->DbDisconnect();
	$pid=pcntl_fork();
	if($pid==-1) {
	    print("Can't fork!\n");
	    exit(2);
	} elseif($pid) {
	    pcntl_wait($status, WNOHANG);
	    $A2B -> DbConnect($agi);
	    $A2B -> set_instance_table ($instance_table);
	} else {
	    ob_start();
	    register_shutdown_function(function(){ob_end_clean();posix_kill(getmypid(), SIGKILL);}, array());
	    $A2B -> DbConnect($agi);
	    $A2B -> set_instance_table ($instance_table);
	    $status = $A2B->google_recognize($cc_cardid,$cc_sendsound,$cc_sendtext,$cc_savesound,$cc_src,$cc_dest,$cc_mailaddr,$cc_audiofile,$cc_answeredtime,$cc_languagecode);
	    $query="UPDATE `cc_recognize_spool` SET `status`='$status' WHERE `id`=$cc_id";
	    if (!$A2B->DBHandle->Execute($query)) {
		write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " Can't execute query: " . $query);
		die("Can't execute query '$query'\n");
	    }
	    $A2B->DbDisconnect();
	    exit(0);
	}
    }
// break;
// }
    sleep(1);
}
?>
