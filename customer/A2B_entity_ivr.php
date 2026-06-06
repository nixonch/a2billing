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
include ("./lib/Form/Class.FormHandler.inc.php");
include ("lib/customer.smarty.php");

if (!has_rights(ACX_SIP_IAX)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

getpost_ifset(array('download', 'file'));

$diraudio = DIR_STORE_AUDIO . "/" . $_SESSION["pr_login"] . "/";

$soundlist = array();
$return = scandir($diraudio);
if ($return!==false) {
        foreach ($return as $val) {
            if (is_file($diraudio.$val) && $val != 'tempplay.wav') {
                $soundlist[preg_replace('/\.[^\.\/]+$/', '', $val)] = $val;
            }
        }
}

if (($download == "file") && $file) {
	if (strpos($file, '/') !== false) {
	    if (substr_count($file,'/') > 1) exit();
	    $path_parts = pathinfo($file);
	    if (strlen($path_parts['dirname'])>3 || !ereg("[^0-9_\.]",$path_parts['dirname'])) exit();
	    $diraudio = DIR_STORE_AUDIO."/".$path_parts['dirname']."/";
	    $return = scandir($diraudio);
	    if ($return!==false) {
		foreach ($return as $val) {
		    if (is_file($diraudio.$val) && strpos($path_parts['dirname']."/".$val, $file) === 0) {
			$file = preg_replace('/\.[^\.\/]+$/', '', $val);
			$source = $diraudio . $val;
			$path_parts = pathinfo($source);
			if ($path_parts['extension']=='alaw') {
			    $val = $file.".wav";
			    $diraudio = "/tmp/";
			    $sox = "/usr/bin/sox --channels 1 --type raw --rate 8000 -e a-law ".$source." ".$diraudio.$val;
			    exec($sox." >/dev/null 2>&1");
			}
			$soundlist[$file] = $val;
			break;
		    }
		}
	    } else exit();
	}
        $dl_full = $diraudio . $soundlist[$file];
        if (!file_exists($dl_full)) {
                echo gettext ( "ERROR: Cannot download file " . $soundlist[$file] . ", it does not exist.<br>" );
                exit ();
        }
        header ( "Content-Type: application/octet-stream" );
        header ( 'Content-Disposition: attachment; filename="'.$soundlist[$file].'"');
        header ( "Content-Length: " . filesize ( $dl_full ) );
        header ( "Accept-Ranges: bytes" );
        header ( "Pragma: no-cache" );
        header ( "Expires: 0" );
        header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
        header ( "Content-transfer-encoding: binary" );

        @readfile ( $dl_full );

	if (isset($sox)) unlink($dl_full);

        exit ();
}

include ("./form_data/FG_var_ivr.inc");

$HD_Form->setDBHandler(DbConnect());
$HD_Form->init();
$instance_table = new Table();

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if (!isset ($form_action)) $form_action = "list"; //ask-add
if (!isset ($action)) $action = $form_action;

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($form_action!='ask-delete') {
    echo $CC_help_ivr;
}

if ($form_action=='list') {

$destlist = array();
$QUERYpop = "SELECT ivrname FROM cc_ivr WHERE id_cc_card=".$_SESSION["card_id"]." ORDER BY ivrname";
$resmax = $instance_table -> SQLExec ($DBHandle,$QUERYpop);
if ($resmax) {
        foreach ($resmax as $val)
            $destlist[] = array($val[0],$val[0]);
}
$QUERYpop = "SELECT `name` FROM cc_queues WHERE id_cc_card=".$_SESSION["card_id"]." ORDER BY `name`";
$resmax = $instance_table -> SQLExec ($DBHandle,$QUERYpop);
if ($resmax) {
	foreach ($resmax as $val)
	    $destlist[] = array("QUEUE ".$val[0],"QUEUE ".$val[0]);
}
if (count($destlist)==0) $nextivrname = "IVR_1";
$QUERYpop = "SELECT regexten FROM cc_sip_buddies WHERE id_cc_card=".$_SESSION["card_id"]." AND external=0 AND regexten>0 ORDER BY regexten";
$resmax = $instance_table -> SQLExec ($DBHandle,$QUERYpop);
if ($resmax) {
        foreach ($resmax as $val)
            $destlist[] = array($val[0],$val[0]);
}
/**
$QUERYpop = "SELECT cid FROM cc_callerid WHERE id_cc_card=".$_SESSION["card_id"]." AND blacklist=0 ORDER BY cid";
$resmax = $DBHandle->Execute($QUERYpop);
if ($resmax) {
        foreach ($resmax as $val)
            $destlist[] = array($val[0],$val[0]);
}
**/
?>
<!-- Init jQuery, jQuery UI -->
<script src="./javascript/jquery/jquery-ui-1.8.20.custom.min.js"></script>
<link href="./javascript/jquery/jquery-ui-1.8.20.custom.css" media="screen" type="text/css" rel="stylesheet">

<!-- Init jqDynaForm -->
<link href="./javascript/jquery/jqDynaForm.css" media="screen" type="text/css" rel="stylesheet">
<script src="./javascript/jquery/jqDynaForm.js"></script>
<script src="./javascript/jquery/fakeData.js"></script>
<script>
    PopUpDayTimeJson=<?php
	if (isset($json_data)) echo $json_data;
	else {	echo "ivrJson;";
		if (isset($nextivrname)) {?>
    PopUpDayTimeJson["ivrname"]='<?php echo $nextivrname?>';<?php
	}}?>
</script>
<script src="./javascript/sipiax.js"></script>
<?php
//echo $ivrname.'<br>';
//echo $json_data.'<br>';
//$json_str = json_decode($json_data,true,512);
//print_r($dest); echo '<br>';

if (isset($json_data)) {
    $QUERYpop = "SELECT id FROM cc_ivr WHERE id_cc_card=".$_SESSION["card_id"]." AND ivrname='$ivrname'";
    $resmax = $instance_table -> SQLExec ($DBHandle, $QUERYpop);
    if ($resmax) {
	$id = $resmax[0][0];
	$QUERYpop = "UPDATE cc_ivr SET repeats='$repeats', waitsecsfordigits='$waitsecsfordigits' WHERE id=".$id;
	$resmax = $DBHandle->Execute($QUERYpop);
	if (!$resmax) {
	    $error_msg = gettext("Cannot update the IVR")."</br>";
	}
	$resmax = $DBHandle->Execute("DELETE FROM cc_ivr_sounds WHERE id_cc_ivr_dest IN (SELECT id FROM cc_ivr_destinations WHERE id_cc_ivr='$id') OR id_cc_ivr='$id'");
	$resmax = $DBHandle->Execute("DELETE FROM cc_ivr_destinations WHERE id_cc_ivr='$id'");
    } else {
	$QUERYpop = "INSERT INTO cc_ivr (id_cc_card,ivrname,repeats,waitsecsfordigits) VALUES ('{$_SESSION["card_id"]}','$ivrname','$repeats','$waitsecsfordigits')";
	$resmax = $DBHandle->Execute($QUERYpop);
	if (!$resmax) {
	    $error_msg = gettext("Cannot insert the new IVR")."</br>";
	} else {
	    $resmax = $instance_table -> SQLExec ($DBHandle, "SELECT LAST_INSERT_ID()");
	    $id = $resmax[0][0];
	}
    }
    if (isset($id) && is_numeric($id)) {
	$QUERYpop = "INSERT INTO cc_ivr_sounds (id_cc_ivr,timeout,playsound) VALUES ";
	foreach ($soundArray as $value) {
	    if ($value['timeout']>0 || strlen($value['playsound'])>0) {
		if (strlen($QUERYpop)>65) $QUERYpop .= ",";
		$QUERYpop .= "('$id','{$value['timeout']}','{$value['playsound']}')";
	    }
	}
	if (strlen($QUERYpop)>65) {
	    $resmax = $DBHandle->Execute($QUERYpop);
	} else {
	    $resmax = true;
	}
	if (!$resmax) {
	    $error_msg = gettext("Cannot insert Sound")."</br>";
	} else {
	    foreach ($destArray as $value) {
		if (strlen($value['waitdigits'])>0) {
		    switch ($value['waitdigits']) {
			case gettext("Nothing pressed (repeat)") : $value['waitdigits'] = -1; break;
			case gettext("Input wrong (repeat)")     : $value['waitdigits'] = -2; break;
			case gettext("Nothing pressed (finish)") : $value['waitdigits'] = -3; break;
			case gettext("Input wrong (finish)")     : $value['waitdigits'] = -4; break;
			case gettext("Extension input allowed")  : $value['waitdigits'] = -5; break;
		    }
		    $destnum = (strlen($value['destinationnum'])>0)?"'".$value['destinationnum']."'":"NULL";
		    $resmax = $DBHandle->Execute("INSERT INTO cc_ivr_destinations (id_cc_ivr,waitdigits,destinationnum,playsoundcallee) VALUES ('$id','{$value['waitdigits']}',$destnum,'{$value['playsoundcallee']}')");
		    if (!$resmax) {
			$error_msg = gettext("Cannot insert Destination")."</br>";
		    } else {
			$resmax = $instance_table -> SQLExec ($DBHandle, "SELECT LAST_INSERT_ID()");
			$iddest = $resmax[0][0];
			$QUERYpop = "INSERT INTO cc_ivr_sounds (id_cc_ivr_dest,timeout,playsound) VALUES ";
			foreach ($value['soundArray'] as $valdest) {
			    if ($valdest['timeout']>0 || strlen($valdest['playsound'])>0) {
				if (strlen($QUERYpop)>70) $QUERYpop .= ",";
				$QUERYpop .= "('$iddest','{$valdest['timeout']}','{$valdest['playsound']}')";
			    }
			}
			if (strlen($QUERYpop)>70) {
			    $resmax = $DBHandle->Execute($QUERYpop);
			    if (!$resmax) {
				$error_msg = gettext("Cannot insert Sound")."</br>";
				break;
			    }
			}
		    }
		}
	    }
	}
    }
}
}
$list = $HD_Form->perform_action($form_action);

if ($error_msg) {
    $error_msg = '<center><font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.$error_msg.'</b></font></center><br>';
    echo $error_msg;
}

$json_list = array();
if (isset($id) && is_numeric($id) && $form_action=='list' && !isset($play)) {
	$QUERY = "SELECT ivrname, repeats, waitsecsfordigits FROM cc_ivr WHERE id='$id' AND id_cc_card='" . $_SESSION["card_id"] . "' LIMIT 1";
	$resmax = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY);
	if ($resmax) {
	    $json_list['ivrname']		= $resmax[0][0];
	    $json_list['repeats']		= $resmax[0][1];
	    $json_list['waitsecsfordigits']	= $resmax[0][2];

	    $QUERY = "SELECT timeout, playsound FROM cc_ivr_sounds WHERE id_cc_ivr='$id' ORDER BY id";
	    $resmax = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY);
	    if ($resmax) {
//		$resmax = array_reverse($resmax);
		foreach ($resmax as $value) {
		    $json_list['soundArray'][] = array('timeout'=>$value[0],'playsound'=>$value[1]);
		}
	    } else  $json_list['soundArray'][] = array('timeout'=>'','playsound'=>'');
	    $QUERY = "SELECT waitdigits, destinationnum, playsoundcallee, id FROM cc_ivr_destinations WHERE id_cc_ivr='$id' ORDER BY id";
	    $resmax = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY);
	    if ($resmax) {
		foreach ($resmax as $value) {
		    switch ($value[0]) {
			case -1 : $value[0] = gettext("Nothing pressed (repeat)"); break;
			case -2 : $value[0] = gettext("Input wrong (repeat)"); break;
			case -3 : $value[0] = gettext("Nothing pressed (finish)"); break;
			case -4 : $value[0] = gettext("Input wrong (finish)"); break;
			case -5 : $value[0] = gettext("Extension input allowed"); break;
		    }
		    $QUERY = "SELECT timeout, playsound FROM cc_ivr_sounds WHERE id_cc_ivr_dest='{$value[3]}' ORDER BY id";
		    $result = $instance_table -> SQLExec ($HD_Form -> DBHandle, $QUERY);
		    $soundArray = array();
		    if ($result) {
			foreach ($result as $val) {
			    $soundArray[] = array('timeout'=>$val[0],'playsound'=>$val[1]);
			}
		    } else {
			$soundArray[] = array('timeout'=>'0','playsound'=>'');
		    }
		    $json_list['destArray'][] = array('waitdigits'=>$value[0],'destinationnum'=>$value[1],'soundArray'=>$soundArray,'playsoundcallee'=>$value[2]);
		}
	    } else {
		$json_list['destArray'][] = array('waitdigits'=>'','destinationnum'=>'','soundArray'=>array(array('timeout'=>'0','playsound'=>'')),'playsoundcallee'=>'');
	    }
	    $json_str = json_encode($json_list);
	    ?><script language="JavaScript"> PopUpDayTimeJson=<?php echo $json_str;?> </script><?php
	}
}

if ($form_action=='list') {
?>

<div class="toggle_show2hide">
<div class="blocka">
<div class="itema lefta">
<a href="#" target="_self" class="toggle_menu"><font class="fontstyle_002"><?php echo gettext("HIDE");?></font> <img src="<?php echo Images_Path; ?>/toggle_hide2show_on.png" onmouseover="this.style.cursor='hand';" HEIGHT="16"></a>
</div>
<div class="itema centera tohide">
<form name="myForm" method="POST" id="myForm" enctype="multipart/form-data" action="<?php echo $PHP_SELF;?>">
    <input type="hidden" value="list" name="form_action"/>
    <div class="bgivr">
	    <div class="bgivrth">
		<?php echo gettext("IVR Name")?>: <INPUT class="form_input_text" name="ivrname" size="11" maxlength="13"/>
	    </div>
	    <div class="bgivrth">
		<?php echo gettext("Repeats")?>: <SELECT name="repeats" class="form_input_select">
				<option>0</option>
				<option>1</option>
				<option>2</option>
				<option>3</option>
				<option>4</option>
				<option>5</option>
				<option>6</option>
				<option>7</option>
				<option>8</option>
				<option>9</option>
			 </SELECT>
	    </div>
	    <div class="bgivrth">
		<?php echo gettext("Wait for input, sec")?>: <SELECT name="waitsecsfordigits" class="form_input_select">
				     </SELECT>
	    </div>
	    <div data-holder-for="soundArray" class="bgivrth"></div>
    </div>
    <div class="ivrbee" data-holder-for="destArray"></div>
    <div class="bgivrtr">
	<button type="submit" class="form_input_button"><?php echo gettext("SAVE/UPDATE");?></button>
    </div>
</form>
</div>
<div class="itema"></div>
</div>
</div>
<datalist id="soundlist">
    <?php foreach ($soundlist as $ke => $value) {?>
	<option value="<?php echo $ke?>">
    <?php }?>
</datalist>
<datalist id="destlist">
    <?php foreach ($destlist as $value) {?>
	<option value="<?php echo $value[0]?>">
    <?php }?>
</datalist>
<datalist id="eventlist">
	<option value="<?php echo gettext("Extension input allowed")?>">
	<option value="<?php echo gettext("Nothing pressed (repeat)")?>">
	<option value="<?php echo gettext("Nothing pressed (finish)")?>">
	<option value="<?php echo gettext("Input wrong (repeat)")?>">
	<option value="<?php echo gettext("Input wrong (finish)")?>">
</datalist>

<!-- Subforms library -->
<div style="display:none;">
<div class="ivrbeetr" data-name="destArray" data-label="Dest">
	<div class="ivrbeetd">
	    <?php echo gettext("Input Action")?>:<br><INPUT name="waitdigits" type="text" list="eventlist" autocomplete="off" class="form_input_text" oninput="allowOnlyNumberPad(this);" style="width:100%"/>
	</div>
	<div class="ivrbeetd" data-holder-for="soundArray"></div>
	<div class="ivrbeetd">
	    <?php echo gettext("Exit Action")?>:<br><INPUT type="text" name="destinationnum" list="destlist" class="form_input_text" maxlength="100" style="width:100%"/>
	</div>
	<div class="ivrbeetd" style="position:relative">
	    <?php echo gettext("Callee sound")?>:
	    <div style="position:relative">
		<INPUT type="text" name="playsoundcallee" list="soundlist" class="form_input_text" maxlength="100" style="width:100%;padding-left:13px;box-sizing:border-box" onchange="Visi(this)"/>
		<a href="javascript:;" name="ton" style="position:absolute;left:2px;top:.3em;visibility:hidden">
		    <img src="<?php echo Images_Path; ?>/flv.gif" height="10" onClick="GreetPlay(this)">
		</a>
	    </div>
	</div>
	<div class="ivrbeetd">
	</div>
</div>
<div class="soundFlex" data-name="soundArray" data-label="Sound">
	<div style="white-space:nowrap">
	<?php echo gettext("Pause,sec")?>: <SELECT name="timeout" class="form_input_select"></SELECT>&nbsp;&nbsp;<?php echo gettext("Sound")?>:&nbsp;
	</div>
	<div style="position:relative">
	    <INPUT type="text" name="playsound" list="soundlist" class="form_input_text" maxlength="100" style="width:100%;padding-left:13px;box-sizing:border-box" onchange="Visi(this)"/>
	    <a href="javascript:;" name="ton" style="position:absolute;left:2px;top:.3em;visibility:hidden">
		<img src="<?php echo Images_Path; ?>/flv.gif" height="10" onClick="GreetPlay(this)">
	    </a>
	</div>
</div>
</div>
<div id="popup"></div>
<script>
    $('select').html($('select[name="repeats"]').html());
</script>
<audio id="sound1" preload="auto" controlsList="nodownload" onended="playnext(sound2)"></audio>
<audio id="sound2" preload="auto" controlsList="nodownload" onended="playnext(sound1)"></audio>
<script src="./javascript/ivrplayer.js"></script>
<?php
}

// #### TOP SECTION PAGE
$HD_Form->create_toppage($form_action);

$HD_Form->create_form($form_action, $list, $id = null);

// #### FOOTER SECTION
$smarty->display('footer.tpl');
