<?php

include ("./lib/customer.defines.php");
include ("./lib/customer.module.access.php");
include ("./lib/Form/Class.FormHandler.inc.php");
include ("./form_data/FG_var_ringup.inc");
include ("./lib/Class.RateEngine.php");
include ("./lib/customer.smarty.php");

if (!has_rights(ACX_SURVEILLANCE)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

$FG_DEBUG = 0;
$color_msg = 'red';

getpost_ifset(array ('callback', 'data', 'called', 'calling', 'duration'));

$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();
/**
if (strlen($called) > 0 && is_numeric($called) && is_numeric($duration)) {

	$FG_SPEEDDIAL_TABLE = "cc_callback_spool_temp";
	$FG_SPEEDDIAL_FIELDS = "surveillance";
	$instance_sub_table = new Table($FG_SPEEDDIAL_TABLE, $FG_SPEEDDIAL_FIELDS);
	
	$QUERY = "INSERT INTO cc_callback_spool_temp (exten_leg_a ,surveillance, account) VALUES ('" . $called . "', '" . $duration . "', '" . $_SESSION["pr_login"] . "')";
	
	$result = $instance_sub_table->SQLExec($HD_Form->DBHandle, $QUERY, 0);
}
**/
//=====================================================================

$A2B -> DBHandle = DbConnect();
$instance_table = new Table();
$A2B -> set_instance_table ($instance_table);
$A2B -> cardnumber = $_SESSION["pr_login"];
if ($A2B -> callingcard_ivr_authenticate_light ($error_msg) && $callback) {
	$called  = $A2B -> apply_rules($called);
	$calling = $A2B -> apply_rules($calling);
	if (strlen($called)>1 && is_numeric($called) && ($calling == "" || (strlen($calling)>1 && is_numeric($calling)))) {
		if ($calling=='')
			$calling = 'RINGUP';
		$virtcalled = $called;
		$virtcalling = $calling;
			$QUERY = "SELECT name, regexten FROM cc_sip_buddies
					LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
					LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->card_id} ) AS v ON v.concat_id = bb.concat_id
					WHERE (id_cc_card = {$A2B->card_id} OR v.concat_id IS NOT NULL) AND (regexten = '{$called}' OR name = '{$called}') LIMIT 1";
			$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
			if (is_array($result) && $result[0][0] != "") {
				$virtcalled			= $result[0][0];
				if ($result[0][1]) $called	= $result[0][1];
			}
			$QUERY = "SELECT name, regexten FROM cc_sip_buddies
					LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
					LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$A2B->card_id} ) AS v ON v.concat_id = bb.concat_id
					WHERE (id_cc_card = {$A2B->card_id} OR v.concat_id IS NOT NULL) AND (regexten = '{$calling}' OR name = '{$calling}') LIMIT 1";
			$result = $A2B -> instance_table -> SQLExec ($A2B->DBHandle, $QUERY);
			if (is_array($result) && $result[0][0] != "") {
				$virtcalling			= $result[0][0];
				if ($result[0][1]) $calling	= $result[0][1];
			}
//			if ($virtcalling==0) $virtcalling ="";
			$RateEngine = new RateEngine();
			$RateEngine -> webui = 0;
			// LOOKUP RATE : FIND A RATE FOR THIS DESTINATION

			$A2B -> agiconfig['accountcode']=$_SESSION["pr_login"];
			$A2B -> agiconfig['use_dnid']=1;
			$A2B -> agiconfig['say_timetocall']=0;						
			$A2B -> extension = $A2B -> dnid = $A2B -> destination = $virtcalled;
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
					if ($channeloutcid[1]) $callerid = $channeloutcid[1];
					    else $callerid = $A2B -> config["callback"]['callerid'];
					$callerid .= "<".$callerid.">";
					$account = $_SESSION["pr_login"];
					
					$uniqueid 	=  MDP_NUMERIC(5).'-'.MDP_STRING(7);
					$status = 'PENDING';
					$server_ip = 'localhost';
					$num_attempt = 0;
					
					$sep = ($A2B->config['global']['asterisk_version'] == "1_2" || $A2B->config['global']['asterisk_version'] == "1_4")?"|":",";
					$variable = "CALLED=".$A2B->dnid.$sep."CALLING=".$virtcalling.$sep."CBID=".$uniqueid.$sep."LEG=".$A2B->cardnumber.$sep."MODE=".$duration.$sep.
						"RATECARD=".$RateEngine->ratecard_obj[$channeloutcid[4]][6].$sep."TRUNK=".$channeloutcid[2].$sep."TD=".$channeloutcid[3];
					
					$QUERY = " INSERT INTO cc_callback_spool (uniqueid, status, server_ip, num_attempt, max_attempt, last_attempt_time, channel, exten, context, priority," .
							 " variable, id_server_group, callback_time, account, callerid, timeout, next_attempt_time, exten_leg_a, flagringup, localtz, data) " .
							 " VALUES ('$uniqueid', '$status', '$server_ip', '$num_attempt', '0', now(), '$channel', '$exten', '$context'," .
							 " '$priority', '$variable', '$id_server_group', now(), '$account', '$callerid'," .
							 " '$timeout', now(), '$A2B->dnid', '1', '".$_SESSION["timezone"]."', '$data')";
					$res = $A2B -> DBHandle -> Execute($QUERY);
					if (!$res) {
						$error_msg = gettext("Cannot insert the ringup request in the spool!")."</br>";
					} else {
						$error_msg = gettext("Your ringup request has been queued correctly")."!</br>";
						$color_msg = 'green';
					}
					sleep(1);
				    } else $error_msg = gettext("Error : Sorry, not enough free trunk for make call. Try again later!")."</br>";
				} else $error_msg = gettext("Error : You don t have enough credit to set ringup!")."</br>";
			} else $error_msg = gettext("Error : There is no route to call for ringup your phonenumber!")."</br>";
	} else $error_msg = gettext("Error : You have to specify at least phonenumber1!")."</br>";
}

$customer = $_SESSION["pr_login"];

//=====================================================================

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if (!isset ($form_action))
	$form_action = "list";

if (!isset ($action))
	$action = $form_action;

$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($form_action == 'list') {
	echo $CC_help_ringuplist;
} elseif ($form_action == 'ask-edit') {
	echo $CC_help_ringupedit;
}

if ($form_action == "list") {
	$HD_Form->create_toppage("ask-add");

	if (isset($update_msg) && strlen($update_msg)>0)
		echo $update_msg;
?>
	  <center>
	  <font class="error_message"><?php echo gettext("Введите номер, чтобы дозваниваться по этому номеру. Нажмите кнопку ДОБАВИТЬ, затем отредактируйте остальные параметры дозвона."); ?></font>
	   <table align="center" class="speeddial_table1">
		<form name="theForm" action="<?php  $_SERVER["PHP_SELF"]?>">
		<INPUT type="hidden" name="callback" value="1">
		<tr class="bgcolor_001">
		<td align="center" valign="top">
				<font class="fontstyle_002"><?php echo gettext("Name");?> :</font>
				<input class="form_input_text" name="data" size="15" maxlength="60" >
				&nbsp;<font class="fontstyle_002"><?php echo gettext(" PhoneNumber");?> :</font>
				<input class="form_input_text" name="called" size="15" maxlength="40" >
				&nbsp;&nbsp;&nbsp;&nbsp;
						<input class="form_input_button"  value="<?php echo "&nbsp;".gettext("ADD")."&nbsp;";?>"  type="submit">
		</td>
        </tr>
	</form>
      </table>
	  <font class="fontstyle_007">
	  <font face='Arial, Helvetica, sans-serif' size='2' color='<?php echo $color_msg; ?>'><b>
 	  <?php echo $error_msg ?>
	  </b></font>
	  </center>
	<?php
}

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

$HD_Form -> create_form ($form_action, $list, $id=null) ;

// #### FOOTER SECTION
$smarty->display('footer.tpl');
