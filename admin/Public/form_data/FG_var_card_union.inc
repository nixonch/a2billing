<?php

getpost_ifset(array('id', 'concat_id', 'concat_card_id', 'root_manager', 'foreignvoipconf', 'foreignlogs', 'filterprefix'));

$HD_Form = new FormHandler("cc_card_concat", gettext("Unions"));

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_ID="concat_card_id";
//$HD_Form -> FG_TABLE_DEFAULT_ORDER = "concat_id DESC, rmanager";
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "concat_id";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "DESC";
$HD_Form -> FG_ACTION_SIZE_COLUMN = '';
$HD_Form -> FG_VIEW_TABLE_WITDH = '80%';

$addpost = '';
if (isset($filterprefix)  &&  (strlen($filterprefix)>0)){
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	$HD_Form -> FG_TABLE_CLAUSE.="concat_id='$filterprefix'";
	$addpost .= "filterprefix=".$filterprefix."&";
}
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT	= $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&".$addpost."id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE	= $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&".$addpost."id=";
if (is_numeric($current_page)) {
	$addpost .= "current_page=".$current_page."&";
}
$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD 	= $_SERVER['PHP_SELF']."?atmenu=document&stitle=Document&wh=AC&".$addpost."id=";

$HD_Form -> FG_LIST_ADDING_BUTTON1	= true;
$HD_Form -> FG_LIST_ADDING_BUTTON_LINK1 = "A2B_entity_card_union.php?form_action=ask-add&".$addpost."section=".$_SESSION["menu_section"];
$HD_Form -> FG_LIST_ADDING_BUTTON_ALT1	= $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add Member");
$HD_Form -> FG_LIST_ADDING_BUTTON_IMG1	= Images_Path ."/page_white_add.png" ;

$yesno = Constants::getYesNoList();

$HD_Form -> AddViewElement(gettext("UNION"), "concat_id", "", "center", "sort");
$HD_Form -> AddViewElement(gettext("MEMBER ACCOUNT"), "concat_card_id", "", "center", "sort", "", "lie_link", "cc_card", "username, id", "id='%id'", "%1", "", "A2B_entity_card.php");
$HD_Form -> AddViewElement(gettext("MEMBER NAME"), "concat_card_id", "", "center\" nowrap \"", "sort", "", "lie_link", "cc_card", "lastname, id, firstname, IF(company_name='','',CONCAT('<br/>(',company_name,')'))", "id='%id'", "%1 %3 %4", "", "A2B_card_info.php");
//$HD_Form -> AddViewElement(gettext("ROOT CONTROL"), "rmanager", "", "center", "SORT");
//$HD_Form -> AddViewElement(gettext("ADMINISTER VoIP & DID INSIDE THE UNION"), "foreignvoipconf", "", "center", "SORT", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("ACCESS TO UNION'S LOGS"), "foreignlogs", "", "center", "SORT", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("OPEN LOGS"), "mylogs", "", "center", "SORT", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("ACCESS TO UNION'S RECORDS"), "foreignrecords", "", "center", "SORT", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("OPEN RECORDS"), "myrecords", "", "center", "SORT", "", "list", $yesno);

//$HD_Form -> FieldViewElement ("concat_id, concat_card_id, concat_card_id, IF(root_manager=0,'".gettext("No")."','<font color=green>".gettext("Yes")."</font>') rmanager, foreignvoipconf, foreignlogs, mylogs, foreignrecords, myrecords");
$HD_Form -> FieldViewElement ("concat_id, concat_card_id, concat_card_id, foreignlogs, mylogs, foreignrecords, myrecords");

$HD_Form -> CV_NO_FIELDS = gettext("THERE ARE NO UNIONS CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_LIMITE_DISPLAY = 10;

$HD_Form -> FG_CAPCHA_POPUP = '<u>'.gettext('UNION').': |col0|</u> | |col2|';
$HD_Form -> FG_ADDITION = true;
$HD_Form -> FG_EDITION = true;
$HD_Form -> FG_DELETION = true;

if (isset ($form_action) && $form_action == "ask-add") {
	$value = 0;
}
$instance_config_table = new Table("cc_card_concat", "concat_id");
$return = $instance_config_table -> Get_list($DBHandle,NULL,"concat_id","ASC",NULL,NULL,NULL,NULL,"GROUP BY concat_id");
$unions = array();
foreach ($return as $n => $val) {
	$unions [$n] = array($val[0],$val[0]);
}

$HD_Form -> AddEditElement (gettext("UNION"),
			"concat_id",
			"$concat_id",
			"SELECT",
			"",
			"",
			"",
			"list", "", "", "", $unions, "%1", "", gettext("Choose Union for this Member"), "", '<OPTION  value="0" selected>'.gettext("NEW").'&nbsp;</OPTION>');

$HD_Form -> AddEditElement (gettext("ID CARD"),
			"concat_card_id",
			"",
			"POPUPVALUE",
			"readonly size=25 maxlength=25",
			"4",
			gettext("Insert the Id CardNumber"),
			"", "", "", "", "", "A2B_entity_card.php?popup_select=1&",
			", 'CardNumberSelection','width=550,height=580,top=20,left=100,scrollbars=1'",
			gettext("Define the card number ID of this Member"));
/**
$HD_Form -> AddEditElement (gettext("ROOT CONTROL"),
			"root_manager",
			"$value",
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0");

$HD_Form -> AddEditElement (gettext("ADMINISTER VoIP & DID INSIDE THE UNION"),
			"foreignvoipconf",
			"$value",
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0");
**/
$HD_Form -> AddEditElement (gettext("ACCESS TO UNION'S LOGS"),
			"foreignlogs",
			"$value",
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "",
			gettext("Define is Member have access to logs of his Union"));

$HD_Form -> AddEditElement (gettext("OPEN LOGS"),
			"mylogs",
			"$value",
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "",
			gettext("Define is other members have access to logs of the Member"));

$HD_Form -> AddEditElement (gettext("ACCESS TO UNION'S RECORDS"),
			"foreignrecords",
			"$value",
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "",
			gettext("Define is Member have access to recors of his Union"));

$HD_Form -> AddEditElement (gettext("OPEN RECORDS"),
			"myrecords",
			"$value",
			"RADIOBUTTON",
			"",
			"",
			"",
			"", "", "", gettext("Yes").":1, - ".gettext("No").":0", "", "", "",
			gettext("Define is other members have access to records of the Memeber"));

//$HD_Form -> FieldEditElement ('concat_id, concat_card_id, root_manager, foreignvoipconf, foreignlogs, mylogs, foreignrecords, myrecords');
$HD_Form -> FieldEditElement ('concat_id, concat_card_id, foreignlogs, mylogs, foreignrecords, myrecords');
$HD_Form -> FG_EDITION_CLAUSE = " concat_card_id='%id'";

$HD_Form->FG_ADITION_HIDDEN_PARAM		= $HD_Form->FG_EDITION_HIDDEN_PARAM		= 'filterprefix,current_page';
$HD_Form->FG_ADITION_HIDDEN_PARAM_VALUE 	= $HD_Form->FG_EDITION_HIDDEN_PARAM_VALUE	= $filterprefix.",".$current_page;

// Set the filter variables
$HD_Form -> FG_FILTER_APPLY = true;
$HD_Form -> FG_FILTERFIELD = 'concat_id';
$HD_Form -> FG_FILTERFIELDNAME = gettext('UNION');

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("Modify via the form below, the properties of the Member");
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext(" If you really want remove this").gettext(" Member").gettext(" from Unions list, ").gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".gettext("Member")."<br>".gettext("Fill the following fields and confirm by clicking on the button add.");

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Click Confirm Data to apply your changes");

$HD_Form -> FG_ADDITIONAL_FUNCTION_BEFORE_EDITION = $HD_Form -> FG_ADDITIONAL_FUNCTION_BEFORE_ADD = 'check_union_member';

$HD_Form -> FG_INTRO_TEXT_ADITION = gettext("Add a ".gettext("Member")." now.");
//$HD_Form -> FG_TEXT_ADITION_ERROR = '<font color="Red"> '.gettext("Your can't add existing Member").'</font><br>';
$HD_Form -> FG_TEXT_ADITION_ERROR = '';
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new")." ".gettext("Member")." ".gettext("has been inserted.")."<br>";
