<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 *
 * @author      Nixon Mitin <nixon@lighttele.com>
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
include ("lib/Form/Class.FormHandler.inc.php");
include ("lib/customer.smarty.php");


if (!has_rights(ACX_SIP_IAX) && !has_rights(ACX_DID)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}


getpost_ifset(array('download', 'file', 'langlocale', 'voicename', 'gender', 'greettext', 'greetname', 'speakingRate', 'play', 'id'));

$customer = $_SESSION["card_id"];
if (!isset($speakingRate)) $speakingRate="1.0";
$diraudio = DIR_STORE_AUDIO."/" . $_SESSION["pr_login"];

if (($download == "file") && $file) {
	if (strpos($file, '/') !== false) exit;
	$dl_full = $diraudio . "/" . $file;
	if (!file_exists($dl_full)) {
		echo gettext ( "ERROR: Cannot download file " . $file . ", it does not exist.<br>" );
		exit ();
	}
	header ( "Content-Type: application/octet-stream" );
	header ( "Content-Disposition: attachment; filename=$file" );
	header ( "Content-Length: " . filesize ( $dl_full ) );
	header ( "Accept-Ranges: bytes" );
	header ( "Pragma: no-cache" );
	header ( "Expires: 0" );
	header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header ( "Content-transfer-encoding: binary" );

	@readfile ( $dl_full );

	exit ();
}

$client = new GuzzleHttp\Client();

if ($play==1 || $play==3) {
    if ($langlocale && $voicename && $greettext) {
	$greettext = urldecode($greettext);
	if ($greetname) {
		$greetname = urldecode($greetname);
		if (strpos($greetname, '/') !== false || strpos($greetname, '.') !== false) exit;
	} else {
		$greetname = "tempplay";
	}
	$dl_full = $diraudio . "/" . $greetname . ".wav";
	$requestData = [
	    'input' =>[
		'text' => $greettext
	    ],
	    'voice' => [
		'languageCode' => $langlocale,
		'name' => $voicename
	    ],
	    'audioConfig' => [
		'audioEncoding' => 'LINEAR16',
		'sampleRateHertz' => 8000,
		'pitch' => 0.00,
		'speakingRate' => $speakingRate
	    ]
	];
	try {
	    $response = $client->post('https://texttospeech.googleapis.com/v1/text:synthesize?key=' . GOOGLE_TTS_KEY, ['json' => $requestData]);
	} catch (Exception $e) {
	    die('Something went wrong');//: ' . $e->getMessage());
	}
	if (!file_exists($diraudio)) mkdir($diraudio, 0755);
	$fileData = json_decode($response->getBody()->getContents(), true);
	file_put_contents($dl_full, base64_decode($fileData['audioContent']));
	chmod($diraudio, 0755);
	chmod($dl_full, 0644);

	if ($greetname == "tempplay") {
		header ( "Content-Type: application/octet-stream" );
		header ( "Content-Disposition: attachment; filename=".$greetname.".wav" );
		header ( "Content-Length: " . filesize ( $dl_full ) );
		header ( "Accept-Ranges: bytes" );
		header ( "Pragma: no-cache" );
		header ( "Expires: 0" );
		header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header ( "Content-transfer-encoding: binary" );

		@readfile ( $dl_full );
		exit ();
	}
    } else exit ();
} else if ($play==2) {
	$soundlist = array();
	$return = scandir($diraudio);
	if ($return!==false) {
	    foreach ($return as $val) {
	        if ($val != '.' && $val != '..' && is_file($diraudio.'/'.$val) && $val != 'tempplay.wav') {
	            $soundlist[] = $val;
	        }
	    }
	arsort($soundlist);
	}
}

include ("./form_data/FG_var_greeting.inc");

$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();

$instance_table = new Table('cc_greeting_records');

if ($play==3) {
	$filename = $greetname.'.wav';
	$QUERY = "INSERT INTO cc_greeting_records (id_cc_card,technology,lang_locale,voice_name,gender,speed,greet_text,greet_filename) VALUES ({$_SESSION["card_id"]},'Google','$langlocale','$voicename','$gender','$speakingRate','$greettext','$filename') ON DUPLICATE KEY UPDATE no_browther_cache=no_browther_cache+1, technology='Google', lang_locale='$langlocale', voice_name='$voicename', gender='$gender', speed='$speakingRate', greet_text='$greettext', updatetime=CURRENT_TIMESTAMP";
	$instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY, 0);
}

if (isset($soundlist) && count($soundlist)>0) {
	$QUERY = "SELECT greet_filename FROM cc_greeting_records WHERE id_cc_card = " . $_SESSION["card_id"];
	$resmax = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY, 1);
	$return = array();
	if ($resmax) foreach ($resmax as $val) {
		$return[] = $val[0];
	}
	foreach ($soundlist as $val) {
		if (in_array($val,$return)===false) {
			$QUERY = "INSERT INTO cc_greeting_records (id_cc_card,greet_filename) VALUES (".$_SESSION["card_id"].",'$val')";
			$instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY, 0);
		}
	}
}

$istts = true;
try {
    $response = $client->get('https://texttospeech.googleapis.com/v1/voices?key=' . GOOGLE_TTS_KEY);
} catch (Exception $e) {
    $istts = false;
//    die('Something went wrong, e.g. make sure that GOOGLE_TTS_KEY is setted: ' . $e->getMessage());
}
if ($istts) {
    $fileData = json_decode($response->getBody()->getContents(), true);

    foreach ($fileData['voices'] as $eachvoice) {
	$langlist[]=$eachvoice['languageCodes'][0];
    }
    $langlist = array_unique($langlist);
    sort($langlist);

    foreach ($langlist as $eachvoice) {
	foreach ($fileData['voices'] as $eachname) {
		if ($eachname['languageCodes'][0]==$eachvoice) {
			$voicenamelist[$eachvoice][] = array(0 => $eachname['name'], 1 => $eachname['ssmlGender'], 2 => $eachname['naturalSampleRateHertz']);
			$wavesort[] = (strpos($eachname['name'],'Wavenet'))?0:1;
		}
	}
	array_multisort($wavesort,$voicenamelist[$eachvoice]);
	unset($wavesort);
    }
}
if ($id!="" || !is_null($id)) {
	$HD_Form -> FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form -> FG_EDITION_CLAUSE);
}

if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

if (isset($id) && is_numeric($id) && $form_action=='list' && !isset($play)) {
	$QUERY = "SELECT technology, lang_locale, voice_name, gender, speed, greet_text, greet_filename FROM cc_greeting_records WHERE id='$id' AND id_cc_card = " . $_SESSION["card_id"] . " LIMIT 1";
	$resmax = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY, 1);
	if ($resmax) {
	    $langlocale   = $resmax[0][1];
	    $voicename	  = $resmax[0][2];
	    $gender	  = $resmax[0][3];
	    $speakingRate = $resmax[0][4];
	    $greettext	  = $resmax[0][5];
	    $greetname	  = preg_replace('/\.[^\.\/]+$/', '', $resmax[0][6]);
	}
}
$greettext = stripslashes($greettext);
$greettext = addslashes($greettext);

if (!isset($langlocale)) {
	$QUERY = "SELECT technology, lang_locale, voice_name, gender, speed FROM cc_greeting_records WHERE id_cc_card = " . $_SESSION["card_id"] . " AND speed ORDER BY updatetime DESC LIMIT 1";
	$resmax = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY, 1);
	if ($resmax) {
	    $langlocale   = $resmax[0][1];
	    $voicename	  = $resmax[0][2];
	    $gender	  = $resmax[0][3];
	    $speakingRate = $resmax[0][4];
	}
}

$list = $HD_Form -> perform_action($form_action);


// #### HEADER SECTION
$smarty->display( 'main.tpl');

if ($form_action == "list") {
    $HD_Form -> create_toppage ("ask-add");
    if ($istts) {
?>
<center>
<?php
	if (isset($update_msg) && strlen($update_msg)>0) echo $update_msg;
?>
	<table align="center"  border="0" width="80%" class="bgcolor_006">
	<form name="theForm">
		<tr>
		<td align="center" valign="top">
			<select class="form_input_select" id="langlocale" name="langlocale" size="1">
<?php foreach ($langlist as $value) { ?>
			<option value="<?php echo $value?>" <?php if ($value==$langlocale) {?> selected<?php } ?>><?php echo $value?></option>
<?php } ?>
			</select>
		</td>
		<td align="center" valign="top">
			<select class="form_input_select" id="voicename" name="voicename" size="1" onchange="setsrcaudio();">
<?php foreach ($langlist as $eachvoice) foreach ($voicenamelist[$eachvoice] as $value) { ?>
			<option main-value="<?php echo $eachvoice;?>" second-value="<?php echo $value[1];?>" value="<?php echo $value[0]?>"<?php if ($value[0]==$voicename) {?> selected<?php } ?>><?php echo $value[0]."/".$value[1];?></option>
<?php } ?>
			</select>
		</td>
		<td align="right" valign="top" colspan="2">
			<input class="form_input_text" style="width: 100%;" id="greettext" name="greettext" placeholder=" <?php echo gettext("Let type text of greeting here");?>" onkeypress="return keytoDownAny(event,id);" onchange="setsrcaudio();" size="100%" maxlength="200" required>
		</td>
		</tr>
		<tr>
		<td align="left" colspan="2" nowrap><!--	     max="4.0" min="0.25" -->
			<?php echo gettext("Speed");?>: <input type="range" id="speakingRate" max="1.5" min="0.5" style="width: 61%;" name="speakingRate" step="0.05" value="<?php echo $speakingRate?>" oninput="range_weight_disp.value = speakingRate.value;" onchange="setsrcaudio();"> <output id="range_weight_disp"></output></input>
		</td>
		<td align="left" width="95%" valign="bottom">
			<audio id="sound2" preload="none" style="width: 100%;" controls controlsList="nodownload"></audio>
		</td>
		<td align="right" width="5%">
			<input class="form_input_text" style="width: 100%;" id="greetname" name="greetname" value="<?php echo $greetname?>" placeholder=" <?php echo gettext("Filename");?>" onkeypress="return keytoDownNumber(event,id,'<?php echo gettext("Enter the filename without extension");?>');" size="16" maxlength="96" required></br>
			<input class="form_input_button" style="width: 100%;" type="button" value="&nbsp;<?php echo gettext("SAVE/UPDATE");?>&nbsp;" onClick="openURL('<?php echo $_SERVER['PHP_SELF']?>?langlocale=','<?php echo gettext("Enter greeting text");?>','<?php echo gettext("Enter the filename without extension");?>',3);">
		</td>
		<tr>
	</form>
	</table>
</center>
<?php
    } else {?>
	<audio id="sound2" preload="none" controlsList="nodownload"></audio>
<?php
    } ?>
	<audio id="sound1" preload="none" controlsList="nodownload"></audio>
	<script language="JavaScript"> var langfirst = '<?php echo $langlocale?>', voicefirst = '<?php echo $voicename?>', soundpath2 = '<?php echo $_SERVER['PHP_SELF']; ?>?langlocale=';document.theForm.range_weight_disp.value=<?php echo $speakingRate?>;document.theForm.greettext.value="<?php echo $greettext?>";</script>
	<script language="JavaScript" src="./javascript/player.js"></script>
<?php
}

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

$HD_Form -> create_form ($form_action, $list, $id=null) ;


// #### FOOTER SECTION
$smarty->display( 'footer.tpl');


