<?php

include ("../lib/admin.defines.php");
include ("../lib/admin.module.access.php");
include ("../lib/admin.smarty.php");

if (!has_rights(ACX_MAINTENANCE)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

//check_demo_mode();
getpost_ifset(array ('method', 'file', 'to', 'ringuptag', 'trunks', 'simult', 'maxduration', 'weekdays', 'timefrom', 'timetill', 'inputa', 'inputb', 'id', 'dest', 'custid', 'callerids'));

# individual file size limit - in bytes (102400 bytes = 100KB)
$file_size_ind = (int) MY_MAX_FILE_SIZE_IMPORT;

# PHP.INI
# ; Maximum allowed size for uploaded files.
# upload_max_filesize = 8M

# the images directory
$dir_img = "templates/default/images";

// -------------------------------- //
//     SCRIPT UNDER THIS LINE!      //
// -------------------------------- //

function getlast($toget) {
	$pos = strrpos($toget, ".");
	$lastext = substr($toget, $pos +1);
	return $lastext;
}

$DBHandle = DbConnect();
$instance_table = new Table();

if ($method)
	$_SESSION['message'] = "";
$json_list = $destlist = $cidlist = $calleridsArray = $trunksArray = array();
$id_ringup = false;
$localtz = $systz = (DATE_TIMEZONE)?"'".DATE_TIMEZONE."'":"NULL";
if (!isset($trunks)) $trunks = "";
if ($method == 'ask-edit' && isset($id) && is_numeric($id)) {
	$id_ringup = $id;
	$QUERY = "SELECT tag,account_id,trunks,simult,destination,IFNULL(localtz,@@global.time_zone) timezone,callerids,maxduration FROM cc_ringup WHERE id=$id";
	$result_query = $instance_table -> SQLExec ($DBHandle, $QUERY);
	if (is_array($result_query)) {
		$json_list['ringuptag'] = $result_query[0][0];
		$json_list['custid'] = $custid = $result_query[0][1];
		if ($custid==0) $json_list['custid'] = "";
		$json_list['trunks'] = $trunks = preg_replace('/^,*|[^\d,]|(,)(?=\1)|,*$/','',$result_query[0][2]);
		$json_list['simult'] = $result_query[0][3];
		$json_list['dest'] = $dest = $result_query[0][4];
		$json_list['maxduration'] = $result_query[0][7];
		$json_list['callerids'] = $callerids = preg_replace('/^,*|[^\d,]|(,)(?=\1)|,*$/','',$result_query[0][6]);
		$localtz = "'".$result_query[0][5]."'";
		$QUERY = "SELECT `weekdays`,TIME_TO_SEC(timefrom),TIME_TO_SEC(timetill),inputa,inputb FROM `cc_sheduler_ratecard` WHERE `id_ringup`=$id ORDER BY ids";
		$result_query = $instance_table -> SQLExec ($DBHandle, $QUERY);
		if (is_array($result_query)) {
			foreach ($result_query AS $value) {
				$json_list['sheduleArray'][] = array('weekdays[]'=>$value[0],'timefrom[]'=>$value[1],'timetill[]'=>$value[2],'inputa[]'=>$value[3],'inputb[]'=>$value[4]);
			}
		}
	}
        $QUERYpop = "SELECT cid FROM cc_callerid WHERE id_cc_card=$custid AND blacklist=0 ORDER BY cid";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val) {
		$cidlist[] = $val[0];
	    }
        }
	if ($callerids) {
	    $calleridsArray = array_filter(explode(",",$callerids), 'is_numeric');
	    foreach ($calleridsArray as $val) {
		if (!in_array($val,$cidlist)) $cidlist[] = $val;
	    }
	}
	sort($cidlist,SORT_STRING);
        $QUERYpop = "SELECT regexten FROM cc_sip_buddies WHERE id_cc_card=$custid AND external=0 AND regexten>0 ORDER BY regexten";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array($val[0],$val[0]);
        }
        $QUERYpop = "SELECT `name` FROM cc_queues WHERE id_cc_card=$custid ORDER BY `name`";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array("QUEUE ".$val[0],"QUEUE ".$val[0]);
        }
	$QUERYpop = "SELECT ivrname FROM cc_ivr WHERE id_cc_card=$custid ORDER BY ivrname";
        $resmax = $DBHandle->Execute($QUERYpop);
        if ($resmax) {
	    foreach ($resmax as $val)
		$destlist[] = array($val[0],$val[0]);
        }
	if ($dest && array_search($dest,array_column($destlist,0))===false) array_unshift($destlist,array($dest,$dest));
} elseif ($method == 'upload') {
	if (isset($id) && is_numeric($id)) $id_ringup = $id;
	$callerids	= preg_replace('/^,*|[^\d,]|(,)(?=\1)|,*$/','',$callerids);
	$trunks 	= preg_replace('/^,*|[^\d,]|(,)(?=\1)|,*$/','',$trunks);
	$_SESSION['message'] = "<img src=\"$dir_img/error.gif\" width=\"15\" height=\"15\">&nbsp;<b><font size=\"3\" color=\"red\">";
	$the_file_name = $_FILES['the_file']['name'];
	$the_file_type = $_FILES['the_file']['type'];
	$the_file = $_FILES['the_file']['tmp_name'];
	$uploaded = false;
	if (count($_FILES)>0 && $the_file) {
		$errortext = validate_upload($the_file, $the_file_type);
		if ($errortext != "" || $errortext != false) {
			echo $errortext;
			exit;
		}
		if (stripos($the_file_type, 'csv') === false) {
			$_SESSION['message'] .= gettext("ERROR: your file type is not allowed") . " (" . getlast($the_file_name) . "), " . gettext("or you didn't specify a file to upload");
		} else {
			if ($_FILES['the_file']['size'] > $file_size_ind) {
				$_SESSION['message'] .= gettext("ERROR: please get the file size less than") . " " . $file_size_ind . " BYTES  (" . round(($file_size_ind / 1024), 2) . " KB)";
			} else {
				$new_filename = "/tmp/" . MDP(6) . ".csv";
				if (file_exists($new_filename)) {
					$_SESSION['message'] .=  $new_filename . " already exists. ";
				} else {
					if (!move_uploaded_file($the_file, $new_filename)) {
						$_SESSION['message'] .=  gettext("File Save Failed, FILE=" . $new_filename);
					} else {
						$_SESSION['message'] = "<font size=\"3\" color=\"green\">" . $the_file_name . " " . gettext("uploaded");
						$uploaded = true;
					}
				}
				$the_file = $new_filename;
			}
		}
	} else {
		if (!isset($id)) $_SESSION['message'] .=  gettext("File Upload Failed");
		else $_SESSION['message'] = "";
	}
	if ($FG_DEBUG == 1)
		echo "<br> THE_FILE:$the_file <br>THE_FILE_TYPE:$the_file_type";
	if (!is_numeric($custid)) $custid = 0;
	if ($custid /*&& ($localtz=="NULL" || !isset($id))*/) {
	    $QUERY = "SELECT `id_timezone` FROM `cc_card` WHERE `id`='{$custid}'";
	    $result_query = $instance_table -> SQLExec ($DBHandle, $QUERY);
	    if (is_array($result_query)) {
		$tz = explode(';',$result_query[0][0]);
		if (isset($tz[1])) $localtz = "'".$tz[1]."'";
	    }
	}
	$dest = ($dest)?"'{$dest}'":"NULL";
	if ($id_ringup!==false) {
	    $QUERY = "UPDATE cc_ringup SET tag='$ringuptag',account_id='$custid',trunks='$trunks',simult='$simult',maxduration='$maxduration',destination=$dest,localtz=$localtz,callerids='$callerids' WHERE `id`='$id_ringup'";
	    $result_query = @ $DBHandle->Execute($QUERY);
	    $QUERY = "DELETE FROM cc_sheduler_ratecard WHERE id_ringup='$id_ringup'";
	    $result_query = @ $DBHandle->Execute($QUERY);
	}
	$nb_imported = $nb_failed = 0;
	if ($uploaded) {
	  $fp = fopen($the_file, "r");
	  if (!$fp) {
	    $_SESSION['message'] .=  ". </font><font color=\"red\">".gettext('Error: Failed to open the file.');
	  } else {
	    while (!feof($fp)) {
		$ligneoriginal = fgets($fp, 4096);
		$ligneoriginal = trim($ligneoriginal);
		$ligne = str_replace(array ( '"', "'", "cc_" ), '', $ligneoriginal);
		$val = preg_split('/[;,]/', $ligne);
		$val[0] = trim($val[0]);
		if (strlen($val[0]) > 0)
		{
			if ($id_ringup===false) {
			    $QUERY = "INSERT INTO cc_ringup (tag,account_id,trunks,simult,maxduration,destination,localtz,callerids) values ('$ringuptag','$custid','$trunks','$simult','$maxduration',$dest,$localtz,'$callerids')";
			    $result_query = @ $DBHandle->Execute($QUERY);
			    if ($result_query !== false && $id_ringup===false) {
				$resmax = $instance_table -> SQLExec ($DBHandle, "SELECT LAST_INSERT_ID()");
				$id_ringup = $resmax[0][0];
			    }
			}
			if ($id_ringup===false) break;
			$adding  = (isset($val[1]) && trim($val[1]))?"'".trim($val[1])."',":"NULL,";
			$adding .= (isset($val[2]) && trim($val[2]))?"'".trim($val[2])."'" :"NULL";
			$QUERY = "INSERT INTO cc_ringup_list (id_ringup, tonum, name, info) values ($id_ringup,'{$val[0]}',$adding)";
			$result_query = $DBHandle->Execute($QUERY);
			if ($result_query) {
				$nb_imported++;
			} else {
				if ($nb_failed<5) $_SESSION['message'] .= "<br><font color=\"gray\">" . $ligneoriginal . " - </font><font color=\"red\">duplicate</font>";
				else if ($nb_failed==5) $_SESSION['message'] .= "<br><font color=\"gray\">... and more. </font>";
				$nb_failed++;
			}
		}
	    } // END WHILE EOF
	    if ($nb_failed) $_SESSION['message'] .= "<br><font color=\"red\">Total: $nb_failed duplicated.</font>";
	    if ($nb_imported) {
		$QUERY = "UPDATE cc_ringup SET lefte=lefte+$nb_imported WHERE id=$id_ringup";
		$result_query = @ $DBHandle->Execute($QUERY);
		$_SESSION['message'] .=  "<br>".$nb_imported.gettext(' phonenumbers was import successfully');
	    } else if (!isset($id)) {
		$_SESSION['message'] .= "</font><br><font color=\"red\">".gettext('But data was absent.');
		$QUERY = "DELETE FROM cc_ringup WHERE id = '" . $id_ringup . "'";
		if ($id_ringup!==false) $result_query = @ $DBHandle->Execute($QUERY);
	    }
	  }
	}
	if ($nb_imported || isset($id)) {
		for ($i=0;$i<count($weekdays);$i++) {
			if ($weekdays[$i] != '') {
			    $QUERY = "INSERT INTO `cc_sheduler_ratecard` (`id_ringup`,`weekdays`,`timefrom`,`timetill`,`inputa`,`inputb`) VALUES ('$id_ringup','{$weekdays[$i]}',SEC_TO_TIME('{$timefrom[$i]}'),SEC_TO_TIME('{$timetill[$i]}'),'{$inputa[$i]}','{$inputb[$i]}')";
			    $result_query = @ $DBHandle->Execute($QUERY);
			}
		}
	}
	if ($_SESSION['message']=="")
	    $_SESSION['message'] = "<b><font size=\"3\" color=\"green\">" . gettext("Ring-UP have been updated successfully");
	$localtz = $systz;
	$trunks = "";

	//Delete the Ring-up List
} elseif ($method == "delete" && is_numeric($id)) {
	$_SESSION['message'] = "<img src=\"$dir_img/error.gif\" width=\"15\" height=\"15\">&nbsp;<b><font size=\"3\" color=\"red\">";
	$QUERY = "DELETE FROM cc_ringup WHERE id = ".$id;
	$result_query = @ $DBHandle->Execute($QUERY);
	if ($result_query !== false) {
	    $QUERY = "DELETE FROM `cc_ringup_list` WHERE `id_ringup`='{$id}'";
	    $result_query = @ $DBHandle->Execute($QUERY);
	    $QUERY = "DELETE FROM `cc_sheduler_ratecard` WHERE `id_ringup`='{$id}'";
	    $result_query = @ $DBHandle->Execute($QUERY);
	    if ($result_query)
		$_SESSION['message'] = "<font size=\"3\" color=\"green\">" . "Ring-Up list deleted";
	    else
		$_SESSION['message'] .= "Ring-Up num list not deleted.";
	} else	$_SESSION['message'] .= "Ring-Up not deleted.";

	//StartStop ring-up
} elseif ($method == "start" && is_numeric($id)) {
	$QUERY = "SELECT `status` FROM `cc_ringup` WHERE `id`='{$id}'";
	$result_query = $instance_table -> SQLExec ($DBHandle, $QUERY);
	if (is_array($result_query) && $result_query[0][0] == 0) {
		$QUERY = "UPDATE `cc_ringup` SET `status`='1' WHERE `id`='{$id}'";
		$result_query = @ $DBHandle->Execute($QUERY);
	}
} elseif ($method == "stop" && is_numeric($id)) {
	$QUERY = "SELECT `status` FROM `cc_ringup` WHERE `id`='{$id}'";
	$result_query = $instance_table -> SQLExec ($DBHandle, $QUERY);
	if (is_array($result_query) && $result_query[0][0] == 1) {
		$QUERY = "UPDATE `cc_ringup` SET `status`='0' WHERE `id`='{$id}'";
		$result_query = @ $DBHandle->Execute($QUERY);
	}
}

$QUERY = "SELECT id_trunk, trunkcode FROM cc_trunk ORDER BY id_trunk";
$result = $instance_table -> SQLExec ($DBHandle, $QUERY);
$coltrunks = count($result);
$smarty->display('main.tpl');

// #### HELP SECTION
echo $CC_help_campaign;

$weekdays = '';
$WeekDaysList = Constants::getWeekDays();
foreach ($WeekDaysList as $key => $val) {
	$weekdays .= '"'.$val[0].'"';
	$weekdays .= ',';
}
$weekdays = substr($weekdays,0,-1);

$QUERY = "SELECT rng.id, rng.tag, CONCAT(IF(crd.status=0,'<del>',''),lastname,' ',firstname,IF(company_name='','',CONCAT(' (',company_name,')')),IF(crd.status=0,'</del>','')) customer, destination, trunks, simult, processed, lefte,
IF(`id_ringup` IS NULL OR rng.status!='1' OR (`weekdays` LIKE CONCAT('%',WEEKDAY(NOW()),'%') AND (CURTIME() BETWEEN `timefrom` AND `timetill`
OR (`timetill`<=`timefrom` AND (CURTIME()<`timetill` OR CURTIME()>=`timefrom`)))),rng.status,3) status, IFNULL(localtz,@@global.time_zone) timezone FROM cc_ringup rng
LEFT JOIN `cc_sheduler_ratecard` ON `id_ringup`=rng.id
LEFT JOIN `cc_card` crd ON crd.id=`account_id`
GROUP BY rng.id ORDER BY rng.id DESC";
$result_query = $instance_table -> SQLExec ($DBHandle, $QUERY);
?>
<script language="JavaScript" type="text/JavaScript">
	var WEEKDAYS = {"dows": [<?php echo $weekdays;?>]};
	var TIMEINTERVALLIST = {"format": "%HH%:%MM%",
                                "empt": "00:00",
                                "DaysText": "<?php echo gettext("Days")?>",
                                "HoursText": "<?php echo gettext("Hours")?>",
                                "MinText": "<?php echo gettext("Minutes")?>",
                                "daylabel": "Дн.",
                                "hourlabel": "час.",
                                "minlabel": "мин.",
                                "ResetTxt": "<?php echo gettext("Clear")?>",
                                "CancelTxt": "<?php echo gettext("Cancel")?>",
                                "OkTxt": " <?php echo gettext("Ok")?> ",
                                "hidedays": true};
</script>
<!-- Init jQuery, jQuery UI and jQuery PI -->
<link href="./javascript/jquery/jquery.pi_ctl.min.css" type="text/css" rel="stylesheet">
<script language="JavaScript" src="./javascript/jquery/jquery.pi_timeInterval.min.js"></script>
<script language="JavaScript" src="./javascript/jquery/jquery.pi_dowselect.min.js"></script>
<script language="JavaScript" src="./javascript/jquery/jquery-ui-1.8.20.custom.min.js"></script>
<link href="./javascript/jquery/jquery-ui-1.8.20.custom.css" media="screen" type="text/css" rel="stylesheet">

<!-- Init jqDynaForm -->
<link href="./javascript/jquery/jqDynaForm.css" media="screen" type="text/css" rel="stylesheet">
<script language="JavaScript" src="./javascript/jquery/jqDynaForm.js"></script>
<script language="JavaScript" src="./javascript/jquery/fakeData.js"></script>
<script> PopUpDayTimeJson=<?php if(count($json_list)){echo json_encode($json_list);}else{?>ringupJson;<?php if($result_query){?>PopUpDayTimeJson['ringuptag']='';<?php }}?> </script>

<script language="JavaScript" type="text/javascript">

function deselectHeaders(search_sources)
{
    sss = document.myForm["selected_" + search_sources];
    uss = document.myForm["unselected_" + search_sources];
    uss[0].selected = false;
    sss[0].selected = false;
}

function resetHidden(search_sources,sss)
{
    var tmp = '';
    for (i = 1; i < sss.length; i++) {
        tmp += sss[i].value;
        if (i < sss.length - 1)
            tmp += ",";
    }
    document.myForm[search_sources].value = tmp;
}

function addSource(search_sources)
{
    sss = document.myForm["selected_" + search_sources];
    uss = document.myForm["unselected_" + search_sources];
    for (i = 1; i < uss.length; i++) {
        if ((uss[i].selected) && (uss[i].style.display != 'none')) {
            ll = sss.length;
            sss[ll] = new Option(uss[i].text, uss[i].value);
            sss[ll].idx = i;
            uss[i].style.display = 'none';
        }
    }
    resetHidden(search_sources,sss);
}

function removeSource(search_sources)
{
    sss = document.myForm["selected_" + search_sources];
    uss = document.myForm["unselected_" + search_sources];
    for (i = 1; i < sss.length; i++) {
        if (sss[i].selected) {
            uss[sss[i].idx] = new Option(sss[i].text, sss[i].value);
            sss[i] = null;
            i--;
        }
    }
    resetHidden(search_sources,sss);
}

function moveSourceUp(search_sources)
{
    sss = document.myForm["selected_" + search_sources];
    var sel = sss.selectedIndex;
    if (sel == -1 || sss.length <= 2) return;
    // deselect everything but the first selected item
    sss.selectedIndex = sel;
    if (sel == 1) {
        tmp = sss[sel];
        sss[sel] = null;
        sss[sss.length] = tmp;
        sss.selectedIndex = sss.length - 1;
    } else {
        tmp = new Array();
        for (i = 1; i < sss.length; i++) {
            tmp[i - 1] = new Option(sss[i].text, sss[i].value);
            tmp[i - 1].idx = sss[i].idx;
        }
        for (i = 0; i < tmp.length; i++) {
            if (i + 1 == sel - 1) {
                sss[i + 1] = tmp[i + 1];
            } else if (i + 1 == sel) {
                sss[i + 1] = tmp[i - 1];
            } else {
                sss[i + 1] = tmp[i];
            }
        }
        sss.selectedIndex = sel - 1;
    }
    resetHidden(search_sources,sss);
}

function moveSourceDown(search_sources)
{
    sss = document.myForm["selected_" + search_sources];
    var sel = sss.selectedIndex;
    if (sel == -1 || sss.length <= 2) return;
    // deselect everything but the first selected item
    sss.selectedIndex = sel;
    if (sel == sss.length - 1) {
        tmp = new Array();
        for (i = 1; i < sss.length; i++) {
            tmp[i - 1] = new Option(sss[i].text, sss[i].value);
            tmp[i - 1].idx = sss[i].idx;
        }
        sss[1] = tmp[tmp.length - 1];
        for (i = 0; i < tmp.length - 1; i++) {
            sss[i + 2] = tmp[i];
        }
        sss.selectedIndex = 1;
    } else {
        tmp = new Array();
        for (i = 1; i < sss.length; i++) {
            tmp[i - 1] = new Option(sss[i].text, sss[i].value)
            tmp[i - 1].idx = sss[i].idx;
        }
        for (i = 0; i < tmp.length; i++) {
            if (i + 1 == sel) {
                sss[i + 1] = tmp[i + 1];
            } else if (i + 1 == sel + 1) {
                sss[i + 1] = tmp[i - 1];
            } else {
                sss[i + 1] = tmp[i];
            }
        }
        sss.selectedIndex = sel + 1;
    }
    resetHidden(search_sources,sss);
}

function clearMyForm()
{
    sss = document.myForm["selected_callerids"];
    uss = document.myForm["unselected_callerids"];
    for (i = 1; i < sss.length; i++) {
        sss[i] = null;
	i--;
    }
    for (i = 1; i < uss.length; i++) {
        uss[i] = null;
	i--;
    }
    sss = document.myForm["selected_trunks"];
    uss = document.myForm["unselected_trunks"];
    for (i = 1; i < sss.length; i++) {
        uss[sss[i].idx] = new Option(sss[i].text, sss[i].value);
        sss[i] = null;
        i--;
    }
    $('#myForm').jqDynaForm('set', ringupJson);
    $('#destlist').empty();
//    $('#dest').prop('disabled', true);
    $('#rhead').html('<?php echo gettext("Ring-Up").". ".gettext("New upload");?>.');
    $('#id').remove();
}
</script>

<div style="display:none">
    <div data-name="sheduleArray" data-label="Shedule">
	<input name="weekdays[]" />
	<style type="text/css">.span12{padding:3px 10px;}</style>
	&nbsp;&nbsp;&nbsp;
	<?php echo gettext("From")?>: <b><input name="timefrom[]" /></b>&nbsp;
	<?php echo gettext("To")?>: <b><input name="timetill[]" /></b>&nbsp;&nbsp;
	<?php echo gettext("Retries")?>: <b><input class="form_input_text" name="inputb[]" oninput="allowOnlyDigits(this);" size="4" maxlength="2" /></b>&nbsp;&nbsp;
	<?php echo gettext("Repeat not before")?>: <b><input class="form_input_text" name="inputa[]" oninput="allowOnlyDigits(this);" size="4" maxlength="4" /></b>&nbsp;<font style="color:#BC2222"><?php echo gettext("min")?></font>&nbsp;
    </div>
</div>
<div id="popup"></div>

<script language="JavaScript" type="text/javascript">

function allowOnlyDigits(id_el) {
  var tval = id_el.value.replace(/[^\d]/g,'');
  if (id_el.value==tval) id_el.style.color = "blue";
  if (document.querySelector("#"+id_el.getAttribute("list")+" option[value='"+id_el.value+"']")===null) id_el.value = tval;
}

function allowOnlyDigitsComa(id_el) {
  var tval = id_el.value.replace(/[^\d,]/g,'');
  if (id_el.value==tval) id_el.style.color = "blue";
  if (document.querySelector("#"+id_el.getAttribute("list")+" option[value='"+id_el.value+"']")===null) id_el.value = tval;
}

function isValidChars(sText,ValidChars)
{
	var IsNumber=true;
	var Char;
	var len=sText.length;

	if (len == 0) IsNumber=false;
	else
	for (i = 0; i < len && IsNumber == true; i++)
	{
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1)
		{
			IsNumber = false;
		}
	}
	return IsNumber;
}

function checkDest(){
	if (document.myForm.custid.value.length < 1){
		$('#dest').prop('disabled', true);
		alert ('<?php echo addslashes(gettext("Please, you have first select a Customer !")); ?>');
		return (false);
	}
}

function sendtoupload(form){
	if (form.ringuptag.value.length < 1){
		alert ('<?php echo addslashes(gettext("Please, you have first enter TAG !")); ?>');
		form.ringuptag.focus();
		return (false);
	}
	if (form.the_file.value.length < 2 && !document.getElementById("id")){
		alert ('<?php echo addslashes(gettext("Please, you have first select a file !")); ?>');
		form.the_file.focus();
		return (false);
	}
	if (form.trunks.value.length < 1 && form.custid.value.length < 1){
		alert ('<?php echo addslashes(gettext("Please, you have first select Customer or atleast one Trunk !")); ?>');
		return (false);
	}
	if (form.simult.value.length<1 || !isValidChars(form.simult.value,'0123456789')){
		alert ('<?php echo addslashes(gettext("Please, you have first insert quantity of simultaneous channels !")); ?>');
		form.simult.focus();
		return (false);
	}
	if (form.maxduration.value.length<1 || !isValidChars(form.maxduration.value,'0123456789')){
		alert ('<?php echo addslashes(gettext("Please, you have first to set maximum duration of call !")); ?>');
		form.maxduration.focus();
		return (false);
	}
	if (form.callerids.value.length>0 && (!isValidChars(form.callerids.value,'0123456789,') /*|| form.callerids.value.slice(0,1)==',' || form.callerids.value.slice(-1)==','*/)){
		alert ('<?php echo addslashes(gettext("Please, type only permitted characters \",0123456789\" into CallerIDs field !")); ?>');
		form.callerids.focus();
		return (false);
	}
	document.myForm.submit();
}
</script>

<datalist id="destlist">
    <?php foreach ($destlist as $value) {?>
	<option value="<?php echo $value[0];?>">
    <?php }?>
</datalist>
<center>
<div class="toggle_<?php if($result_query && $method!='ask-edit'){?>hide2show<?php }else{?>show2hide<?php }?>">
<a href="#" target="_self" class="toggle_menu">
<table width="900" cellspacing="0" cellpadding="0" border="0" align="center">
  <tr>
    <td><img src="<?php echo KICON_PATH; ?>/toggle_hide2show<?php if(!($result_query && $method!='ask-edit')){?>_on<?php }?>.png" onmouseover="this.style.cursor='hand';" HEIGHT="16"><font size="3"><b><i id="rhead"><?php echo gettext("Ring-Up").". ";echo ($id_ringup!==false && $method=='ask-edit')?gettext("Edit").".":gettext("New upload").".";?></i></b></font>&nbsp;</td>
  </tr>
</table>
</a>
<div class="tohide" <?php if($result_query && $method!='ask-edit'){?>style="display:none;"<?php }?>>
<form name="myForm" method="POST" id="myForm" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF'];?>">
<input type="hidden" value="upload" name="method"/>
<?php if($id_ringup!==false && $method=='ask-edit'){?>
<input type="hidden" value="<?php echo $id_ringup;?>" name="id" id="id"/>
<?php }?>
<table width="900" cellspacing="0" cellpadding="5" border="0">
         <tr bgcolor="#D2D2D2">
           <td style="padding:5px"><?php echo gettext("TAG");?>:</td><td><input class="form_input_text" id="ringuptag" name="ringuptag" size="30" maxlength="30"></td>
         </tr>
         <tr bgcolor="#F2F8FF">
	   <td style="padding:5px"><?php echo gettext("Customer");?>:</td>
	   <td><INPUT class="form_enter" id="custid" name="custid" size="30" maxlength="30" value="" readonly /><a href="#" onclick="window.open('A2B_entity_card?popup_select=3&popup_formname=myForm&popup_fieldname=custid', 'CardNumberSelection','width=590,height=550,top=100,left=100,scrollbars=1');"><img src="<?php echo Images_Path_Main;?>/icon_arrow_orange.gif" valign="bottom"/></a></td>
         </tr>
         <tr bgcolor="#E2E2E2">
	    <td></td>
	    <td>
		<?php echo gettext("Use the example below to format the CSV file.</br>Fields are separated by [,] or [;]");?>
		<div style="display:flex;width:290px;justify-content:space-around;">
		<a href="importsamples.php?sample=Phonebook_Complex" target="superframe"><?php echo gettext("Complex Sample");?></a><a href="importsamples.php?sample=Phonebook_Simple" style="flex-grow:0" target="superframe"><?php echo gettext("Simple Sample");?></a>
		</div>
		<iframe name="superframe" src="importsamples?sample=Phonebook_Simple" width="290" height="70" marginWidth="10" marginHeight="10" frameBorder="1" scrolling="no"></iframe>
	    </td>
	 </tr>
         <tr bgcolor="#E2E2E2">
           <td style="padding-left:5px"><?php echo gettext("Phonebook");?>:</td><td>
           <input name="the_file" id="the_file" type="file" size="50" class="saisie1">
           </td>
         </tr>
         <tr bgcolor="#E2E2E2">
    <td nowrap style="padding-left:5px;padding-bottom:5px"><?php echo gettext("File size limit");?>:</td>
	    <td>
		<b><?php 
			if ($file_size_ind >= 1048576) {
				$file_size_ind_rnd = round(($file_size_ind/1024000),3) . " MB";
			} elseif ($file_size_ind >= 1024) {	
				$file_size_ind_rnd = round(($file_size_ind/1024),2) . " KB";
			} elseif ($file_size_ind >= 0) {
				$file_size_ind_rnd = $file_size_ind . " bytes";
			} else {
				$file_size_ind_rnd = "0 bytes";
			}
			
			echo "$file_size_ind_rnd";
		?></b>
	    </td>
	 </tr>
         <tr bgcolor="#F2F8FF">
           <td style="padding-left:5px"><?php echo gettext("Trunks");?>:</td>
           <td style="padding:5px">
		<input name="trunks" id="trunks" type="hidden"/>
		<table cellspacing="0" cellpadding="0" border="0">
		    <tr><td>
		    <SELECT name="unselected_trunks" multiple="multiple" size="9" width="50" onchange="deselectHeaders('trunks')" class="form_input_select">
			<OPTION value=""><?php echo gettext("Unselected Fields...");?></OPTION>
			<script language="JavaScript" type="text/JavaScript">
				document.myForm.unselected_trunks[0].style.color="#505050";
			</script>
<?php foreach($result AS $val){?>
			<OPTION value="<?php echo $val[0]?>"<?php if (array_search($val[0], explode(',',$trunks)) !== false) echo ' style="display:none"';?>><?php echo $val[0].'-'.$val[1]?></OPTION>
<?php }?>
		    </SELECT>
		    </td><td>
		    <a href="" onclick="addSource('trunks'); return false;"><img src="<?php echo $dir_img?>/forward.png" alt="add source" title="add source" border="0"></a>
		    <br>
		    <a href="" onclick="removeSource('trunks'); return false;"><img src="<?php echo $dir_img?>/back.png" alt="remove source" title="remove source" border="0"></a>
		    </td><td>
		    <SELECT name="selected_trunks" multiple="multiple" size="9" width="50" onchange="deselectHeaders('trunks');" class="form_input_select">
			<OPTION value=""><?php echo gettext("Selected Fields...");?></OPTION>
			<script language="JavaScript" type="text/JavaScript">
				document.myForm.selected_trunks[0].style.color="#505050";
			</script>
<?php if ($trunks) foreach(explode(',',$trunks) AS $select_number=>$value){foreach($result AS $key=>$val){if($val[0]==$value){?>
			<option value="<?php echo $val[0]?>" idx="<?php echo $key+1?>"><?php echo $val[0].'-'.$val[1]?></option>
			<script language="JavaScript" type="text/JavaScript">
			    document.myForm.selected_trunks[<?php echo $select_number+1;?>].idx=<?php echo $key+1;?>;
			</script>
<?php break;}}}?>
		    </SELECT>
		    </td><td>
		    <a href="" onclick="moveSourceUp('trunks'); return false;"><img src="<?php echo $dir_img?>/up_black.png" alt="move up" title="move up" border="0"></a>
		    <br>
		    <a href="" onclick="moveSourceDown('trunks'); return false;"><img src="<?php echo $dir_img?>/down_black.png" alt="move down" title="move down" border="0"></a>
		    </td></tr>
		</table>
           </td>
         </tr>
         <tr bgcolor="#E2E2E2">
           <td nowrap style="padding:5px"><?php echo gettext("Simultaneous channels");?>:</td><td style="padding-left:5px"><input class="form_input_text" id="simult" name="simult" oninput="allowOnlyDigits(this);" size="10" maxlength="2"></td>
         </tr>
         <tr bgcolor="#F2F8FF">
           <td nowrap style="padding-left:5px" valign="top"><?php echo gettext("Max duration");?>:</td><td style="padding-left:5px;padding-bottom:2px"><input class="form_input_text" id="maxduration" name="maxduration" oninput="allowOnlyDigits(this);" size="10" maxlength="4">&nbsp;<font style="color:#BC2222;font-size:10px;"><?php echo gettext("sec")?></font></br><?php echo gettext("After the specified number of seconds have elapsed, the connection will be terminated.")?></td>
         </tr>
         <tr bgcolor="#E2E2E2">
           <td style="padding-left:5px"><?php echo gettext("Time Zone");?>:</td>
           <td style="padding-left:5px">
             <b><div id="tzc"><?php echo trim($localtz,"'");?></div></b>
           </td>
         </tr>
         <tr bgcolor="#E2E2E2">
           <td style="padding-left:5px" valign="top"><?php echo gettext("Sheduler");?>:</td>
           <td style="padding-left:3px;padding-bottom:5px">
		<div class="blockDyna">
		    <div data-holder-for="sheduleArray"></div>
		</div>
		<div class="clear"></div>
           </td>
         </tr>
         <tr bgcolor="#F2F8FF">
           <td valign="top" style="padding:5px"><?php echo gettext("CallerIDs");?>:</td>
           <td style="padding:5px">
		<input name="callerids" id="callerids" oninput="allowOnlyDigitsComa(this);" class="form_input_text" maxlength="250" style="width:100%"/>
		<table cellspacing="0" cellpadding="0" border="0" style="padding-top:5px">
		    <tr><td>
		    <SELECT name="unselected_callerids" multiple="multiple" size="9" width="50" onchange="deselectHeaders('callerids')" class="form_input_select">
			<OPTION value=""><?php echo gettext("Unselected Fields...");?></OPTION>
<?php foreach($cidlist AS $val){?>
			<OPTION value="<?php echo $val?>"<?php if(in_array($val,$calleridsArray)) echo ' style="display:none"';?>><?php echo $val?></OPTION>
<?php }?>
		    </SELECT>
		    </td><td>
		    <a href="" onclick="addSource('callerids'); return false;"><img src="<?php echo $dir_img?>/forward.png" alt="add source" title="add source" border="0"></a>
		    <br>
		    <a href="" onclick="removeSource('callerids'); return false;"><img src="<?php echo $dir_img?>/back.png" alt="remove source" title="remove source" border="0"></a>
		    </td><td>
		    <SELECT name="selected_callerids" multiple="multiple" size="9" width="50" onchange="deselectHeaders('callerids');" class="form_input_select">
			<OPTION value=""><?php echo gettext("Selected Fields...");?></OPTION>
<?php if ($callerids) foreach($calleridsArray AS $select_number=>$value){foreach($cidlist AS $key=>$val){if($val==$value){?>
			<OPTION value="<?php echo $val?>" idx="<?php echo $key+1;?>"><?php echo $val?></OPTION>
			<script language="JavaScript" type="text/JavaScript">
			    document.myForm.selected_callerids[<?php echo $select_number+1;?>].idx=<?php echo $key+1;?>;
			</script>
<?php break;}}}?>
		    </SELECT>
			<script language="JavaScript" type="text/JavaScript">
				document.myForm.unselected_callerids[0].style.color="#505050";
				document.myForm.selected_callerids[0].style.color="#505050";
			</script>
		    </td><td>
		    <a href="" onclick="moveSourceUp('callerids'); return false;"><img src="<?php echo $dir_img?>/up_black.png" alt="move up" title="move up" border="0"></a>
		    <br>
		    <a href="" onclick="moveSourceDown('callerids'); return false;"><img src="<?php echo $dir_img?>/down_black.png" alt="move down" title="move down" border="0"></a>
		    </td></tr>
		</table>
           </td>
         </tr>
         <tr bgcolor="#E2E2E2">
           <td valign="top" style="padding:5px">
	    <?php echo gettext("Destination");?>:</td><td style="padding-left:5px;padding-top:5px"><INPUT type="text" class="form_input_text" id="dest" name="dest" list="destlist" autocomplete="off" maxlength="100" style="width:50%" onClick="checkDest();"><br>
	    <?php echo gettext("Enter here the phonenumber or IVR name you wish to send call, or select destination from the drop-down list");?>
           </td>
         </tr>
  <tr>
    <td colspan="2" style="padding:5px"><p align="center">
    <input type="button" value="<?php echo gettext("Upload");?>" class="form_input_button" name="submit1" onClick="sendtoupload(this.form);">&nbsp;
    <input type="button" value="<?php echo gettext("Clear");?>" class="form_input_button" onClick="clearMyForm();">
    </p></td>
  </tr>
</table>
</form>
</div>
</div>
      <?php
        //When there is a message, after an action, show it
        if($_SESSION['message'])
        {
          echo $_SESSION['message'] . "</font></b>";	$_SESSION['message'] = '';
        }
      ?>
<br><table cellspacing="1" cellpadding="1" border="0">
  <tr class="form_head">
    <td class="tableBody" style="padding:2px 5px 2px 5px;" align="center"><?php echo gettext("TAG");?></td>
    <td class="tableBody" style="padding:2px 10px 2px 10px;" align="center"><?php echo gettext("CUSTOMER");?></td>
    <td class="tableBody" style="padding:2px 5px 2px 5px;" align="center"><?php echo gettext("DESTINATION");?></td>
    <td class="tableBody" style="padding:2px 10px 2px 10px;" align="center"><?php echo gettext("TRUNK");?></td>
    <td class="tableBody" style="padding:2px 5px 2px 5px;" align="center"><?php echo gettext("CHANNELS");?></td>
    <td class="tableBody" style="padding:2px 5px 2px 5px;" align="center"><?php echo gettext("PASSED");?></td>
    <td class="tableBody" style="padding:2px 10px 2px 10px;" align="center"><?php echo gettext("LEFT");?></td>
    <td class="tableBody" style="padding:2px 10px 2px 10px;" align="center"><?php echo gettext("TIMEZONE");?></td>
    <td class="tableBody" style="padding:2px 5px 2px 5px;" align="center"><?php echo gettext("STATUS");?></td>
    <td class="tableBody" style="padding:2px 10px 2px 10px;" align="center" colspan="2"><?php echo gettext("ACTION");?></td>
  </tr>
  <?php
	if ($result_query) {
	    // EXPORT
	    $FG_EXPORT_SESSION_VAR = "pr_export_entity_ringup";
	    // Query Preparation for the Export Functionality
	    $_SESSION [$FG_EXPORT_SESSION_VAR] = "SELECT tonum Number, channelstatedesc Result, lastattempt Date, try Tryes, keyspressed `Keys pressed` FROM cc_ringup_list WHERE id_ringup=";

	    for ($i = 0; $i < count($result_query); $i++) {
  ?><tr>
	<td style="padding-left:5px;padding-right:5px">&nbsp;<?php echo $result_query[$i][1];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center" nowrap><?php echo $result_query[$i][2];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php echo $result_query[$i][3];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php echo $result_query[$i][4];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php echo $result_query[$i][5];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php echo $result_query[$i][6];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php echo $result_query[$i][7];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php echo $result_query[$i][9];?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php if($result_query[$i][8]==0) echo gettext('STOPPED'); elseif($result_query[$i][8]==1) echo gettext('IN PROGRESS'); elseif($result_query[$i][8]==2) echo gettext('FINISHED'); elseif($result_query[$i][8]==3) echo gettext('TIME-OUT');?></td>
	<td style="padding-left:5px;padding-right:5px" align="center"><?php if($result_query[$i][8]!=2) { ?><A href="<?php echo $_SERVER['PHP_SELF'];?>?method=<?php if($result_query[$i][8]==0) echo "start"; else echo "stop";?>&amp;id=<?php echo $result_query[$i][0];?>"><div class="upload_button">&nbsp;<?php if($result_query[$i][8]==1 || $result_query[$i][8]==3) echo gettext('Stop'); elseif($result_query[$i][8]==0) echo gettext('Start');?>&nbsp;</div></a><?php }?></td>
	<td align="left" style="padding-top:4px" nowrap>
		<A href="javascript:if(confirm('<?php echo gettext("Are you sure to delete ");?> <?php echo $result_query[$i][1];?>?')) location.href='<?php echo $_SERVER['PHP_SELF'];?>?method=delete&amp;id=<?php echo $result_query[$i][0];?>';"><img src="<?php echo $dir_img?>/cross.gif" title="Delete" alt="Delete" border="0"></a>
		<A href="export_csv.php?var_export=<?php echo $FG_EXPORT_SESSION_VAR?>&var_export_type=type_csv&filename=<?php echo str_replace(" ","_",$result_query[$i][1]);?>&id=<?php echo $result_query[$i][0];?>" target="_blank"><img src="<?php echo $dir_img?>/dl.gif" title="<?php echo gettext("Export CSV");?>" alt="<?php echo gettext("Export CSV");?>" border="0"></a>
		<A href="<?php echo $_SERVER['PHP_SELF'];?>?method=ask-edit&amp;id=<?php echo $result_query[$i][0];?>"><img src="<?php echo $dir_img?>/edit.gif" title="Edit" alt="Edit" border="0"></a>
<!--		<A href="<?php echo $_SERVER['PHP_SELF'];?>?method=ask-edit&amp;id=<?php echo $result_query[$i][0];?>"><img src="<?php echo $dir_img?>/info.png" title="Phones list" alt="Phones list" border="0" width="12" height="12"></a>
		<A href="javascript: var inserttext = ''; if(inserttext = prompt('Rename <?php echo $entry;?>. Fill in the new name for the file.','<?php echo $entry;?>')) location.href='<?php echo $_SERVER['PHP_SELF'];?>?method=rename&amp;file=<?php echo $entry;?>&amp;to='+inserttext; "><img src='<?php echo $dir_img?>/edit.gif' alt='Rename <?php echo $entry;?>' border="0" valign="bottom"></a>
-->
	</td>
  </tr>
    <?php
	    }
	}
    ?>

</table></center>
<br><br>
<?php

$smarty->display('footer.tpl');
