<?php

$json_data = $_POST["json_data"];
if (isset($json_data)) {
	$json_data = stripslashes($json_data);
	extract(json_decode($json_data,true,512));
} else {
	getpost_ifset(array('id', 'exten_leg_a', 'exten', 'max_attempt'));
}

if ( (empty($form_action) || $form_action=="list") && $deleteselected != 'true') {
	$HD_Form = new FormHandler("cc_callback_spool LEFT JOIN cc_sheduler_ratecard ON id_callback=id", "ring-up");
	$HD_Form -> SQL_GROUP = "GROUP by id";
} else {
	$HD_Form = new FormHandler("cc_callback_spool","ring-up");
}

$HD_Form -> FG_DEBUG = 0;

$HD_Form -> FG_TABLE_DEFAULT_ORDER = "entry_time";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "DESC";

$HD_Form -> FG_CAPCHA_POPUP = '|col0| | |col1|';
$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;
$HD_Form -> FG_ACTION_SIZE_COLUMN = '5%';

// Dependent Tables
$HD_Form -> FG_FK_DELETE_ALLOWED = true;
$HD_Form -> FG_FK_DELETE_CONFIRM = false;
$HD_Form -> FG_FK_WARNONLY = false;
$HD_Form -> FG_FK_TABLENAMES = array("cc_sheduler_ratecard");
$HD_Form -> FG_FK_EDITION_CLAUSE = array("id_callback");

$soundlist = array();
$soundlist[] = array(gettext("NOT ACTIVE")."&nbsp", "");
$username = $_SESSION["pr_login"];
$return = @scandir(DIR_STORE_AUDIO."/$username/");
if ($return!==false) {
    foreach ($return as $val) {
        if (is_file(DIR_STORE_AUDIO."/$username/".$val) && $val != 'tempplay.wav') {
            $soundlist[] = array(preg_replace('/\.[^\.\/]+$/', '', $val),$username."/".preg_replace('/\.[^\.\/]+$/', '', $val));
        }
    }
}

$HD_Form -> AddViewElement(gettext("NAME"), "data", "15%", "center\" nowrap \"", "sort");
$HD_Form -> AddViewElement(gettext(" PhoneNumber"), "leg_a", "10%", "center", "sort", "", "lie_link", "", "", "", "", "", "call-history.php?s=1&t=0&order=t1.starttime&sens=DESC&current_page=0&posted=1&Period=&frommonth=&fromstatsmonth=&tomonth=&tostatsmonth=&fromday=true&fromstatsday_sday=1&fromstatsmonth_sday=".date('Y')."-".date('m')."&today=&tostatsday_sday=".date('d')."&tostatsmonth_sday=".date('Y')."-".date('m')."&calleridtype=1&phonenumbertype=1&sourcetype=&clidtype=&channel=&resulttype=min&callerid=&phonenumber=%1&src=&clid=&terminatecauseid=ANSWER&choose_calltype=-1&choose_currency=");
$HD_Form -> AddViewElement(gettext("LAST ACTIVITY"), "lastactivity", "15%", "center\" nowrap \"", "sort", "50");
$HD_Form -> AddViewElement(gettext("CALLS MADE"), "num_attempt" , "2%", "center", "sort");
$HD_Form -> AddViewElement(gettext("MAX CALLS"), "max_attempt" , "3%", "center", "sort");
$HD_Form -> AddViewElement(gettext("NOANSWER"), "num_attempts_noanswer" , "2%", "center", "sort");
$HD_Form -> AddViewElement(gettext("BUSY"), "num_attempts_busy" , "3%", "center", "sort");
$HD_Form -> AddViewElement(gettext("UNAVAILABLE"), "num_attempts_unavailable" , "2%", "center", "sort");
$HD_Form -> AddViewElement(gettext("ANSWERED"), "num_attempts_answered" , "3%", "center", "sort");

$HD_Form -> FieldViewElement (	"data, IFNULL((SELECT regexten FROM cc_sip_buddies
                                        LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
                                        LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = {$_SESSION['card_id']} ) AS v ON v.concat_id = bb.concat_id
                                        WHERE (id_cc_card = {$_SESSION['card_id']} OR v.concat_id IS NOT NULL) AND (regexten = cc_callback_spool.exten_leg_a OR name = cc_callback_spool.exten_leg_a) LIMIT 1),exten_leg_a) AS leg_a, ".
				"IF(status='PENDING' AND num_attempt<max_attempt AND (weekdays LIKE CONCAT('%',WEEKDAY(NOW()),'%') AND (TIME(NOW()) BETWEEN timefrom AND timetill OR (timetill<=timefrom AND (TIME(NOW())<timetill OR TIME(NOW())>=`timefrom`)))),".
					"IF(last_status LIKE 'ERROR_NO%',".
						"CONCAT('<font color=green>',last_status,'</font>'),".
						"CONCAT('<font color=green>',DATE_FORMAT(last_attempt_time,'%Y-%m-%d %T'),'</font>')),".
					"IF(ISNULL(ids) OR num_attempt>=max_attempt,".
						"IF(num_attempt>=max_attempt AND DATEDIFF(NOW(),last_attempt_time)<=2,".
							"CONCAT('<font color=red>',DATE_FORMAT(last_attempt_time,'%Y-%m-%d %T'),'</font>'),".
							"DATE_FORMAT(last_attempt_time,'%Y-%m-%d %T')),".
						"CONCAT('<font color=blue>',DATE_FORMAT(last_attempt_time,'%Y-%m-%d %T'),'</font>'))".
				") AS lastactivity, ".
				"IF(num_attempt>=max_attempt AND num_attempt>0,CONCAT('<font color=red>',num_attempt,'</font>'),num_attempt) num_attempt, max_attempt, num_attempts_noanswer, num_attempts_busy, num_attempts_unavailable, num_attempt-num_attempts_noanswer-num_attempts_busy-num_attempts_unavailable AS num_attempts_answered");

$HD_Form -> CV_NO_FIELDS  = gettext("There are no ring-up created.");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_VIEW_TABLE_WITDH = '70%';
$HD_Form -> FG_LIMITE_DISPLAY = 25;

if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
    $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="account = ".$_SESSION["pr_login"]." AND flagringup = 1";
	
//if (strlen($HD_Form -> FG_EDITION_CLAUSE)>0)
//    $HD_Form -> FG_EDITION_CLAUSE.=" AND ";
//	$HD_Form -> FG_EDITION_CLAUSE.="account = ".$_SESSION["pr_login"];

$HD_Form -> AddEditElement (gettext("NAME"),
			   "data",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=60",
			   "",
			   "",
			   "", "", "", "", "", "", "", "");

$HD_Form -> AddEditElement (gettext(" PhoneNumber"),
			   "exten_leg_a",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=40 readonly",
			   "4",
			   "",
			   "", "", "", "", "", "", "", "");

$HD_Form -> AddEditElement (gettext("MAX ATTEMPTS"),
			   "max_attempt",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=10",
			   "4",
			   "",
			   "", "", "", "", "", "", "", "Предельное количество звонков на заданный номер");

if ($form_action<>"ask-delete") {
$HD_Form -> AddEditElement(gettext("SCHEDULE"),
        "shedule",
        '$value',
        "POPUPDAYTIME3",
        "", "", "", "",
        "cc_sheduler_ratecard",
        "weekdays, TIME_TO_SEC(timefrom) timefrom, TIME_TO_SEC(timetill) timetill, inputa, inputb, inputc",
        "id_callback='%id'", "'%id','%1',SEC_TO_TIME('%2'),SEC_TO_TIME('%3'),'%4','%5','%6'", "", "", "Расписание, пауза между звонками, количество звонков каждый раз и ограничение максимальной продолжительности звонка", ":", "`id_callback`,`weekdays`,`timefrom`,`timetill`,`inputa`,`inputb`,`inputc`");
$shedule = ", priority";
} else $shedule = "";

$HD_Form -> AddEditElement (gettext("CallerID PREFIXes"),
        "calleridprefix",
        "$value",
        "INPUT",
        "size=70 maxlength=256 ",
        "28",
        "",
        "", "", "", "", "", "", "NO", gettext("Spam CallerID prefixes. Insert the prefixes separated by comma, e.g. for UK,IT,RU,UA are '44,39,7,380'."), null, null, gettext("SPAM CallerID"));

$HD_Form -> AddEditElement(gettext("CallerID LENGTH"),
        "calleridlength",
        '$value',
        "INPUT",
        "size=30 maxlength=2",
        "30",
        "",
        "", "", "", "", "", "", "NO-NULL", gettext("Set CallerID length"));

$HD_Form -> AddEditElement (gettext("TIMEOUT1"),
        "timeout1",
        '$value',
        "INPUT",
        "size=50 maxlength=3",
        "22",
        gettext("Insert max 3 digits"),
        "", "", "", "", "", "", "", gettext("Pause, in seconds"));

$HD_Form -> AddEditElement (gettext("SOUND1"),
        "sound1",
        '$callbacksound',
        "SELECT",
        "",
        "",
        gettext("Insert the path and name of sound file"),
        "list", "", "", "", $soundlist, "%1");

if ($form_action == 'edit') {
$HD_Form -> AddEditElement("ENTRY TIME",
	"entry_time",
	'$value',
	"INPUT",
	"size=40 maxlength=40",
	"",
	gettext("Insert the starting date"),
	"", "", "", "", "", "", "" , gettext("Format YYYY-MM-DD HH:MM:SS. For instance, '2004-12-31 00:00:00'"),
	null, "res_display_dateformat", gettext("Define the period when this rate table is active."));
}

$HD_Form -> FieldEditElement ("data, exten_leg_a, max_attempt".$shedule.", calleridprefix, calleridlength, timeout1, sound1, entry_time");

$HD_Form->FG_EDITION_HIDDEN_PARAM	= 'entry_time';
$HD_Form->FG_EDITION_HIDDEN_PARAM_VALUE = date("Y-m-d H:i:s");

/**
if (isset($filterprefix)  &&  (strlen($filterprefix)>0)) {
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="id_cc_card like '$filterprefix'";
}
**/
$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("You can modify, through the following form, the different properties of your")." ".$HD_Form->FG_INSTANCE_NAME;
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".strtoupper($HD_Form->FG_INSTANCE_NAME).", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

$HD_Form -> FG_INTRO_TEXT_ADITION = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".$HD_Form->FG_INSTANCE_NAME." ".gettext("has been inserted.")."<br>";


$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path."/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Once you have completed the form above, click on the CONTINUE button.");


$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $PHP_SELF."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $PHP_SELF."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $PHP_SELF."?atmenu=document&stitle=Document&wh=AC&id=";

