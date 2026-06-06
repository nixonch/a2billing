<?php

getpost_ifset(array('message', 'id', 'idcust', 'displayheader', 'type', 'popup_formname', 'popup_fieldname', 'atmenu'));

if ($form_action == "ask-edit" || $form_action == "edit" || $message == "editsuccess") {

getpost_ifset(array('lastname', 'firstname', 'company_name', 'id_group', 'email', 'company_website', 'address','city', 'state', 'areaprefix', 'citylength', 'country', 'removeaddprefix',
		    'addprefixinternational', 'zipcode', 'language', 'id_timezone', 'phone', 'fax', 'voicemail_activated', 'monitor', 'recalldays', 'tariff', 'margin', 'paypal'));

$HD_Form = new FormHandler("cc_card","Card");
$HD_Form -> FG_TABLE_CLAUSE = "id_diller = {$_SESSION['card_id']}";
//$HD_Form -> FG_TABLE_ID="id";
//$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";
if (strlen($HD_Form -> FG_EDITION_CLAUSE)>0)
	$HD_Form -> FG_EDITION_CLAUSE .= " AND ";
$HD_Form -> FG_EDITION_CLAUSE .= "id_diller = {$_SESSION['card_id']}";

$HD_Form -> FG_EDITION = true;

$HD_Form->setDBHandler(DbConnect());

$yesno_list = array(array(gettext('yes').'&nbsp;','1'),array(gettext('no').'&nbsp;','0'));

// ******* Available Tariff List Detecting
if (!empty($_SESSION['dillertariffs']))
	$callPlan_idlist = $_SESSION["dillertariffs"];
else
	$callPlan_idlist = isset ($A2B->config['signup']['callplan_id_list']) ? $A2B->config['signup']['callplan_id_list'] : NULL;

if (strlen($callPlan_idlist) == 0) {
        exit ("No Call Plan Defined.");
}
$tariff_table = new Table("cc_card", "tariff, id_group");
$tariff_list = $tariff_table->Get_list($HD_Form->DBHandle, "id='$idcust'");

//echo "1.<br>".var_export($tariff_list,true)."<br>";
if (!is_array($tariff_list) && count($tariff_list) <= 0) {
	exit (gettext("No Call Plan ID or Group ID are defined"));
}
$callPlan_idlistArr = explode(',',$callPlan_idlist);
if (!in_array($tariff_list[0][0],$callPlan_idlistArr)) {
	$callPlan_idlist .= ','.$tariff_list[0][0];
}
if (!isset($tariff))	$tariff = $tariff_list[0][0];
//echo "2.<br>".$callPlan_idlist."<br>";
//echo "3.<br>".$tariff."<br>";
$callPlan_idlistArr = explode(',',$callPlan_idlist);
if (!in_array($tariff,$callPlan_idlistArr)) {
	exit (gettext("No Call Plan id are defined"));
}
$call_table = new Table("cc_tariffgroup", "tariffgroupname,id");
$callPlan_list = $call_table->Get_list($HD_Form->DBHandle, "id IN ($callPlan_idlist)", "FIELD(id,".$callPlan_idlist.")", "ASC");

//echo "4.<br>".var_export($callPlan_list,true)."<br>";
if (!is_array($callPlan_list) && count($callPlan_list) <= 0) {
        exit (gettext("No Call Plan id are defined"));
}
$flag = true;
$callPlan_count = count($callPlan_list);
if ($tariff != "" || !is_null($tariff)) {
        for ($i = 0; $i < $callPlan_count; $i++) {
                if ($callPlan_list[$i][1] == trim($tariff, "\'")) {
                        $flag = false;
                }
        }
        if ($flag) {
                exit ("Invalid Call Plan Id");
        }
}

// ******* Available Group List Detecting
if (!empty($_SESSION['dillergroups']))
	$group_idlist = $_SESSION["dillergroups"];
else
	$group_idlist = isset($A2B->config['signup']['group_id_list']) ? $A2B->config['signup']['group_id_list'] : NULL;

if (strlen($group_idlist) == 0) {
        exit ("No Groups Defined.");
}

$group_idlistArr = explode(',',$group_idlist);
if (!in_array($tariff_list[0][1],$group_idlistArr)) {
	$group_idlist .= ','.$tariff_list[0][1];
}
if (!isset($id_group))	$id_group = $tariff_list[0][1];
$group_idlistArr = explode(',',$group_idlist);
if (!in_array($id_group,$group_idlistArr)) {
	exit (gettext("No Group id are defined"));
}
$group_table = new Table("cc_card_group", "name,id");
$group_list = $group_table->Get_list($HD_Form->DBHandle, "id IN ($group_idlist)", "FIELD(id,".$group_idlist.")", "ASC");

//echo "4.<br>".var_export($callPlan_list,true)."<br>";
if (!is_array($callPlan_list) && count($callPlan_list) <= 0) {
        exit (gettext("No Group id are defined"));
}
$flag = true;
$group_count = count($group_list);
if ($id_group != "" || !is_null($id_group)) {
        for ($i = 0; $i < $group_count; $i++) {
                if ($group_list[$i][1] == trim($id_group, "\'")) {
                        $flag = false;
                }
        }
        if ($flag) {
                exit ("Invalid Group Id");
        }
}


if ($group_count > 1) {
	$HD_Form->AddEditElement(gettext("CUSTOMER TYPE"),
			    "id_group",
			    "$id_group",
			    "SELECT",
			    "", "",
			    "", "list", "", "", "", $group_list, "%1", "", "");
	$group_editFG = "id_group, ";
} else $group_editFG = "";

if ($callPlan_count > 1) {
	$HD_Form->AddEditElement(gettext("CALL PLAN"),
				    "tariff",
				    "$tariff",
				    "SELECT",
				    "", "",
				    "", "list", "", "", "", $callPlan_list, "%1", "", "");
	$tariff_editFG = "tariff, ";
} else $tariff_editFG = "";

$HD_Form -> AddEditElement (gettext("LASTNAME"),
			   "lastname",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=50",
			   "11",
			   gettext("Insert the lastname of the customers"),
			   "" , "", "",	"", "", "" , "NO", "");

$HD_Form -> AddEditElement (gettext("FIRSTNAME"),
			   "firstname",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=50",
			   "11",
			   gettext("Insert the firstname of the customers"),
			   "" , "", "",	"", "", "" , "NO", "");

$HD_Form->AddEditElement(gettext("COMPANY NAME"),
			    "company_name",
			    '$value',
			    "INPUT",
			    "size=40 maxlength=50", "",
			    gettext("Insert Company name of this customer"), "", "", "", "", "", "", "NO", "");

$HD_Form->AddEditElement(gettext("COMPANY WEBSITE"),
			    "company_website",
			    '$value',
			    "INPUT", "size=40 maxlength=60",
			    "8", gettext("Insert the company website of this customer"), "", "", "", "", "", "", "NO", "");

$language_list = Constants::getLanguagesList();
$HD_Form->AddEditElement(gettext("LANGUAGE")." *",
	                    "language",
	                    "$language",
	                    "SELECT",
	                    "", "",
	                    "", "list", "", "", "", $language_list, "%1", "", "");

$HD_Form -> AddEditElement (gettext("TIMEZONE"),
			   "id_timezone",
			   "",
			   "SELECT",
			   "", "", "",
			   "SQL",
			   "cc_timezone",
			   "gmtzone, id",
			   "", "", "%1", "", "", "", "" );

$HD_Form -> AddEditElement (gettext("PHONE NUMBER"),
			   "phone",			
			   '$value',	
			   "INPUT",	
			   "size=30 maxlength=20",	
			   "7",
			   gettext("Insert the phone number of the customers"),
			   "", "", "", "", "", "", "", "");

$HD_Form -> AddEditElement (gettext("FAX NUMBER"),
			   "fax",			
			   '$value',	
			   "INPUT",	
			   "size=30 maxlength=20",
			   "7",
			   gettext("Insert the fax number of the customers"),
			   "" , "", "",	"", "", "" , "NO", "");

$HD_Form->AddEditElement(gettext("EMAIL")." *",
			    "email",
			    '$value',
			    "INPUT",
			    "size=30 maxlength=70", "1",
			    gettext("Insert your email"), "", "", "", "", "", "", "", "");

$HD_Form -> AddEditElement (gettext("ADDRESS"),
			   "address",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=100",
			   "0",
			   gettext("Insert the address of the customers"),
			   "" , "", "",	"", "", "" , "NO", "");


$HD_Form -> AddEditElement (gettext("CITY"),
			   "city",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=40",
			   "0",
			   gettext("Insert the city"),
			   "" , "", "",	"", "", "" , "NO", "");


$HD_Form -> AddEditElement (gettext("STATE/PROVINCE"),
			   "state",
			   '$value',	
			   "INPUT",	
			   "size=30 maxlength=40",	
			   "11",	
			   gettext("Insert the state"),
			   "" , "", "",	"", "", "" , "NO", "");


$HD_Form -> AddEditElement (gettext("ZIP/POSTAL CODE"),
			   "zipcode",			
			   '$value',
			   "INPUT",	
			   "size=30 maxlength=20",	
			   "0",
			   gettext("Insert the zipcode"),
			   "" , "", "",	"", "", "" , "NO", "");

$HD_Form -> AddEditElement (gettext("COUNTRY"),
			   "country",
			   "",
			   "SELECT",
			   "", "", "",
			   "sql",
			   "cc_country",
			   "countryname, countrycode, countryprefix",
			   "", "", "%1 +%3", "", "", "", '' );

$HD_Form -> AddEditElement(gettext("AREA CODE"),
                            "areaprefix",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=6",
                            "22",
                            "",
                            "", "", "", "", "", "", "NO-NULL", gettext("Set prefix of the city by default (i.e. 30 for Berlin)"));

$HD_Form -> AddEditElement(gettext("CITY LENGTH"),
                            "citylength",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=1",
                            "23",
                            "",
                            "", "", "", "", "", "", "NO-NULL", gettext("Set length of city numbers"));

$HD_Form -> AddEditElement(gettext("REMOVE+ADD PREFIX"),
                            "removeaddprefix",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=30",
                            "31",
                            "",
                            "", "", "", "", "", "", "NO", gettext("Set for display CallerID on internal phones which prefix you want to remove and then add with comma separated (i.e. \"38044,,38,\")"));

$HD_Form -> AddEditElement(gettext("ADD INTERNATIONAL PREFIX"),
                            "addprefixinternational",
                            '$value',
                            "INPUT",
                            "size=30 maxlength=3",
                            "",
                            "",
                            "", "", "", "", "", "", "NO", gettext("Set for display on internal phones which prefix you want see before international CallerID (i.e. \"+\")"));

$add_fields_monitor = '';
if ($ACXSEERECORDING){
$HD_Form -> AddEditElement (gettext("RECORD CALL"),
			   "monitor",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   gettext("Enable to monitor the call (to record all the conversations) value : YES - NO"),
			   "" , "", "", gettext("Yes")." :1, - ".gettext("No").":0", "", "" , "", gettext("Define if you want to record all the conversations"));
$add_fields_monitor = ', monitor';
}

$HD_Form -> AddEditElement (gettext("RECALL DAYS").", ".gettext("Days"),
			   "recalldays",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=20",
			   "4",
			   "",
			   "" , "", "", "", "", "" , "NO");
/**
$HD_Form -> AddEditElement (gettext("RECALL TIME").", ".gettext("Seconds"),
			   "recalltime",
			   '$value',
			   "INPUT",
			   "size=30 maxlength=4 readonly",
			   "4",
			   "",
			   "" , "", "", "", "", "", "NO");
**/
if (ACT_VOICEMAIL && $ACXVOICEMAIL) {
	$HD_Form -> AddEditElement (gettext("ACTIVE VOICEMAIL SERVICE"),
			   "voicemail_activated",
			   '0',
			   "RADIOBUTTON",
			   "",
			   "",
			   gettext("Customer can choose to enable the voicemail for this card"),
			   "" , "", "", gettext("Yes")." :1, - ".gettext("No").":0", "", "" , "", gettext("Customer can choose to enable the voicemail for this card"));
$add_fields_voicemail = ', voicemail_activated';
} else $add_fields_voicemail = '';

$HD_Form -> AddEditElement (gettext("MARGIN").", %%",
                                    "margin",
                                    '$value',
                                    "INPUT",
                                    "size=10 maxlength=3",
                                    "27", "(0..100)%",
                                    "", "", "", "", "", "", "NO", "");

$HD_Form -> AddEditElement (gettext("PAYPAL PAYMENT"),
                                    "paypal",
                                    $paypal,
                                    "SELECT",
                                    "",
                                    "",
                                    "",
                                    "list", "", "", "", $yesno_list, "%1", "", gettext("Apply PayPal payment to this customer"));


$HD_Form -> FieldEditElement ($group_editFG.$tariff_editFG.'lastname, firstname, company_name, company_website, language, id_timezone, phone, fax, email, address, city, state, zipcode, country, areaprefix, citylength, removeaddprefix, addprefixinternational'.$add_fields_monitor.', recalldays'.$add_fields_voicemail.', margin, paypal');

$HD_Form->FG_ADITION_HIDDEN_PARAM = $HD_Form->FG_EDITION_HIDDEN_PARAM = 'popup_select, idcust';
$HD_Form->FG_ADITION_HIDDEN_PARAM = $HD_Form->FG_EDITION_HIDDEN_PARAM_VALUE = "$popup_select, $idcust";

$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $PHP_SELF."?idcust=$idcust";

} else {

    if ($type == 'payment') {
	$HD_Form = new FormHandler("cc_logpayment as t2 LEFT JOIN cc_card ON cc_card.id=t2.card_id ", gettext("Payments"));
	$HD_Form->FG_LIMITE_DISPLAY = 24;
	$HD_Form->FG_TABLE_CLAUSE = "t2.card_id = $idcust AND cc_card.id_diller = {$_SESSION['card_id']}";
	$HD_Form->FG_TABLE_ID = 't2.id';
	$HD_Form->FG_TABLE_DEFAULT_SENS = "DESC";
	$HD_Form->AddViewElement(gettext("DATE"), "date", "30%", "center", "sort", "30", "", "", "", "", "", "display_dateformat");
	$HD_Form->AddViewElement(gettext("PAYMENT INCL FEE"), "payment", "40%", "center", "sort", "", "", "", "", "", "", "display_2bill");
	$HD_Form->AddViewElement(gettext("DESCRIPTION"), "description", "30%", "center", "sort");

	$HD_Form->FieldViewElement("t2.date, t2.payment, t2.description");
	$HD_Form->CV_NO_FIELDS = gettext("THERE IS NO PAYMENTS CREATED!");

    } elseif ($type == 'invoice') {
	$list_sql = "cc_invoice " .
	"LEFT JOIN cc_card ON cc_card.id=cc_invoice.id_card " .
	"LEFT JOIN ( SELECT st1.id_invoice, SUM(st1.price*(1+(st1.vat/100))) AS total_price FROM cc_invoice_item AS st1 GROUP BY st1.id_invoice) " .
	"AS items ON items.id_invoice=cc_invoice.id ";
	$HD_Form = new FormHandler($list_sql, gettext("INVOICES"));
	$HD_Form->FG_LIMITE_DISPLAY = 24;
	$HD_Form->FG_TABLE_ID = 'cc_invoice.id';
	$HD_Form->FG_TABLE_DEFAULT_SENS = "DESC";
	$HD_Form->FG_TABLE_CLAUSE = "cc_invoice.id_card = $idcust AND cc_card.id_diller = {$_SESSION['card_id']} ";
	$HD_Form->AddViewElement(gettext("DATE"), "date", "50%", "center", "sort", "30", "", "", "", "", "", "display_dateformat");
	$HD_Form->AddViewElement(gettext("AMOUNT INCL VAT"), "total_price", "50%", "center", "sort", "", "", "", "", "", "", "display_2bill");

	$HD_Form->FieldViewElement("cc_invoice.date, total_price");
	$HD_Form->CV_NO_FIELDS = gettext("THERE IS NO INVOICE CREATED!");

    } elseif ($type == 'sipiax') {
	$atmenu = ($atmenu=='IAX')?$atmenu:'SIP';
	$HD_Form = new FormHandler("cc_".strtolower($atmenu)."_buddies AS t2 LEFT JOIN cc_card ON cc_card.id = id_cc_card ", gettext($atmenu . " Config"));
	$HD_Form -> FG_LIMITE_DISPLAY = 24;
	$HD_Form -> FG_TABLE_ID = 't2.id';
	$HD_Form -> FG_TABLE_DEFAULT_ORDER = "regexten ";
	$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";
	$HD_Form -> FG_TABLE_CLAUSE = "id_cc_card = $idcust AND cc_card.id_diller = {$_SESSION['card_id']} AND t2.external = 0 ";
	$HD_Form -> AddViewElement(gettext("EXTENSION"), "regexten", "6%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("CALLERID NAME"), "callerid", "20%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("LANGUAGE"), "language", "5%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("HOST"), "host", "24%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("REGNAME"), "name", "9%", "center", "sort");
	$HD_Form -> AddViewElement(gettext("SECRET"), "secret", "4%", "center", "sort");

	$HD_Form -> FieldViewElement ("IF(regexten='',t2.name,regexten), callerid, t2.language, IF(port>0 AND UNIX_TIMESTAMP()<regseconds,concat('<font color=green>',ipaddr,':',port,'</font> ',IF(lastms=-1,'UNREACHABLE',IF(lastms>0,concat('(',lastms,' ms)'),'')),'<br>',useragent),host), t2.name, t2.secret");
	$HD_Form -> CV_NO_FIELDS = gettext("THERE IS NO ".$atmenu." CONFIG FOUNDED!");

    }
    $HD_Form->FG_EDITION = false;
}
$HD_Form -> FG_DEBUG = 0;

$HD_Form->CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
//$HD_Form->CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form->CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form->CV_FOLLOWPARAMETERS = "&popup_select=" . $popup_select . "&idcust=" . $idcust . "&type=" . $type;
$HD_Form->FG_VIEW_TABLE_WITDH = '95%';

$HD_Form->FG_DELETION = false;
$HD_Form->FG_ADDITION = false;
