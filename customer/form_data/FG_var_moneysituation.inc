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
 * @author      Nick Mitin <nixon@sipde.net>
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

getpost_ifset(array('filterprefix', 'filterprefix2'));

if (isset($filterprefix2)) {
    $filterprefix2 = preg_replace("/[^\d]/", '', $filterprefix2);
} else {
    $filterprefix2 = '';
}
if (strlen($filterprefix2)>3)
    $addsearch = "%".$filterprefix2."%";
else
    $addsearch = $filterprefix2."%";

$req = "cc_card
	LEFT JOIN (
	    SELECT card_id
	    FROM cc_logrefill
	    WHERE diller_id = ".$_SESSION['card_id']."
	      AND `date` > NOW() - INTERVAL 5 MINUTE
	    ORDER BY id DESC
	    LIMIT 1
	) recent ON recent.card_id = cc_card.id";

$FG_TABLE_DEFAULT_ORDER = '';
$FG_TABLE_DEFAULT_SENS = '';

$req2 = "cc_card.id='%id'";
if (is_numeric($filterprefix2)) {
    $req .= " LEFT JOIN cc_callerid ON id_cc_card=cc_card.id";
    $req2 .= " ORDER BY fn DESC LIMIT 1";
//    $req2 .= " AND (id_cc_card IS NULL OR (id_cc_card='%id' AND cid LIKE '".$addsearch."') OR phone+0 LIKE '".$addsearch."') ORDER BY fn+0 LIKE '".$addsearch."' LIMIT 1";
} else if (!isset($filterprefix) || !$filterprefix) {
    $req .= "
	LEFT JOIN (
	    SELECT card_id, MAX(`date`) last_refill_date
	    FROM cc_logrefill
	    WHERE diller_id = ".$_SESSION['card_id']."
	    GROUP BY card_id
	) lr ON lr.card_id = cc_card.id";

    $FG_TABLE_DEFAULT_ORDER	= 'GREATEST(cc_card.creationdate, COALESCE(lr.last_refill_date, cc_card.creationdate))';
    $FG_TABLE_DEFAULT_SENS	= 'DESC';
}
$HD_Form = new FormHandler($req,"Card");

$HD_Form -> FG_TABLE_ID="cc_card.id";
$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_LIMITE_DISPLAY = 20;
$HD_Form -> FG_TABLE_CLAUSE		= 'id_diller='.$_SESSION['card_id'];
$HD_Form -> FG_TABLE_DEFAULT_ORDER	= $FG_TABLE_DEFAULT_ORDER;
$HD_Form -> FG_TABLE_DEFAULT_SENS	= $FG_TABLE_DEFAULT_SENS;

if (has_rights (ACX_DISTRIBUTION) && !($popup_select>=1)) {
	$HD_Form -> FG_ADDITION = true;
	$HD_Form -> FG_LIST_ADDING_BUTTON1 = true;
	$HD_Form -> FG_LIST_ADDING_BUTTON_LINK1 = "javascript:;\" onClick=\"alertBase('url:signup?form_action=ask-add&id_diller=".$_SESSION["pr_login"]."&margin=".$_SESSION["margin_diller"]."&popup_select=1&popup_formname=myForm&displayheader=0','".gettext("Add Customer")."',true,true)";
	$HD_Form -> FG_LIST_ADDING_BUTTON_ALT1 = $HD_Form ->FG_LIST_ADDING_BUTTON_MSG1 = gettext("Add Customer");
	$HD_Form -> FG_LIST_ADDING_BUTTON_IMG1 = Images_Path ."/user_add.png" ;
}

$req1 = "cc_card LEFT JOIN cc_callerid ON id_cc_card=cc_card.id";

$HD_Form -> AddViewElement(gettext("CUSTOMER"), "lastname", "25%", "center", "sort", "", "link2cust", $req1, "CONCAT(IF(status=0,'<del>',''),lastname,' ',firstname,IF(company_name='','',CONCAT(' (',company_name,')')),IF(status=0,'</del>','')), cc_card.id, tariff, '$filterprefix2' AS fp, IF(('$filterprefix2'='' OR cc_card.id = ".$_SESSION['card_id'].") OR (phone+0 NOT LIKE '".$addsearch."' AND cid+0 NOT LIKE '".$addsearch."'),'',CONCAT('(',IF(phone+0 LIKE '".$addsearch."',phone,cid),')')) AS fn", $req2, "%1 %5", "", "javascript:;\" onClick=\"alertBase('url:A2B_entity_moneysituation_details?form_action=ask-edit&displayheader=0&popup_select=1&idcust=%2&tariff=%3','%1',true,false,'#666666')");

$HD_Form -> AddViewElement(gettext("BALANCE"), "credit", "11%", "center", "", "", "", "", "", "", "", "display_refill_money");
$HD_Form -> AddViewElement(gettext("COMMISSION ACCRUED"), "commission", "17%", "center", "", "", "", "", "", "", "", "display_refill_money");
$HD_Form -> AddViewElement(gettext("MARGIN"), "margin", "7%", "center", "");
$HD_Form -> AddViewElement(gettext("INVOICES"), "invoice", "5%", "center", "", "", "link2cust", "cc_invoice LEFT JOIN cc_invoice_item ON cc_invoice_item.id_invoice=cc_invoice.id", "SUM(ROUND(cc_invoice_item.price*(1+(cc_invoice_item.vat/100)),2)), cc_invoice.id_card", "cc_invoice.id_card='%id' GROUP BY cc_invoice.id_card", "%1", "", "javascript:;\" onClick=\"alertBase('url:A2B_entity_moneysituation_details?type=invoice&form_action=list&atmenu=card&displayheader=0&popup_select=1&idcust=%2','".gettext("INVOICES")."',true,false,'#666666')");
$HD_Form -> AddViewElement(gettext("Payments"), "payment", "5%", "center", "", "", "link2cust", "cc_logpayment as t2", "SUM(ROUND(t2.payment-t2.fee,2)),t2.card_id", "t2.card_id='%id' GROUP BY t2.card_id", "%1", "", "javascript:;\" onClick=\"alertBase('url:A2B_entity_moneysituation_details?type=payment&form_action=list&atmenu=card&displayheader=0&popup_select=1&idcust=%2','".gettext("Payments")."',true,false,'#666666')");
$HD_Form -> AddViewElement(gettext("TO PAY"), "to_pay", "10%", "center", "", "", "eval", "number_format((%4-%5+abs(%5-%4))/2, 2, '.', ' ')");

//$FieldViewElement = "cc_card.id, CONCAT(cc_card.credit,IFNULL((SELECT IF(card_id=cc_card.id,' 1','') FROM cc_logrefill WHERE diller_id=".$_SESSION["card_id"]." AND TIMEDIFF(NOW(),cc_logrefill.date)<'00:05:00' ORDER BY id DESC LIMIT 1),'')) credit, commission, CONCAT_WS(' ',margin,'%') margin, cc_card.id, cc_card.id, cc_card.id";
$HD_Form -> FieldViewElement("
    cc_card.id,
    CONCAT(cc_card.credit, IF(recent.card_id IS NOT NULL, ' 1', '')) credit,
    cc_card.commission,
    CONCAT_WS(' ', cc_card.margin, '%') margin,
    cc_card.id,
    cc_card.id,
    cc_card.id
");

$HD_Form -> FG_ACTION_SIZE_COLUMN = '10%';
$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO CARD FOUNDED!"); 
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;
$HD_Form -> FG_VIEW_TABLE_WITDH = '80%';


$HD_Form -> FG_EDITION = false;
$HD_Form -> FG_DELETION = false;
$HD_Form -> FG_ADDITION = false;


if (has_rights (ACX_DISTRIBUTION) && !($popup_select>=1)) {
/*	$FG_COL_QUERY_FIELD = '';
	if (strlen($FG_COL_QUERY_FIELD)>2) {
		$FG_COL_QUERY_FIELD = substr( $FG_COL_QUERY_FIELD,2);
	}
	$FG_COL_QUERY_COUNT = count(preg_split('/,/',$FG_COL_QUERY_FIELD));
*/
//	$HD_Form -> FG_INFO = true;

//	$HD_Form -> FG_OTHER_BUTTON1 = true;
	$HD_Form -> FG_OTHER_BUTTON2 = true;
//	$HD_Form -> FG_OTHER_BUTTON3 = true;
	$HD_Form -> FG_OTHER_BUTTON4 = true;
	$HD_Form -> FG_OTHER_BUTTON5 = true;
	$HD_Form -> FG_OTHER_BUTTON1_LINK= "javascript:;\" onClick=\"alertBase('url:A2B_entity_moneysituation_details?type=sipiax&form_action=list&atmenu=SIP&displayheader=0&popup_select=4&idcust=|param|','|col0|',true,false,'#445760',event.pageX-20,event.pageY-15,'UR')";
	$HD_Form -> FG_OTHER_BUTTON2_LINK= "javascript:;\" onClick=\"alertBase('url:A2B_entity_callerid?form_action=list&displayheader=0&popup_select=1&idcust=|param|','|col0|',true,false,'#445760',event.pageX-55,event.pageY-15,'UR')";
	$HD_Form -> FG_OTHER_BUTTON3_LINK= "javascript:;\" onClick=\"alertBase('url:didivr?popup_select=1&choose_callowner=4&id=|param|','|col0|',true,false,'#445760',event.pageX-78,event.pageY-15,'UR')";
	$HD_Form -> FG_OTHER_BUTTON4_LINK= "javascript:;\" onClick=\"alertBase('url:call-history?popup_select=1&choose_callowner=4&id=|param|','|col0|',true,false,'#445760',event.pageX-78,event.pageY-15,'UR')";
	$HD_Form -> FG_OTHER_BUTTON5_LINK= "javascript:;\" onClick=\"alertBase('url:A2B_entity_logrefill?form_action=ask-add&popup_select=1&popup_formname=myForm&card_id=|param|&displayheader=0','|col0|',true,true,'info',event.pageX-100,event.pageY+15,'UR'"./*,'main-content'*/")\" style=\"height:12.8px";
	$HD_Form -> FG_OTHER_BUTTON1_ALT = '<font color="red">'.gettext('EXT').'</font>';
	$HD_Form -> FG_OTHER_BUTTON2_ALT = '<font color="red">'.gettext('CID').'</font>';
	$HD_Form -> FG_OTHER_BUTTON3_ALT = '<font color="red">'.gettext('DID').'</font>';
	$HD_Form -> FG_OTHER_BUTTON4_ALT = '<font color="red">'.gettext('LOG').'</font>';
	$HD_Form -> FG_OTHER_BUTTON5_ALT = gettext('Add Refill to this Card');
	$HD_Form -> FG_OTHER_BUTTON5_CONDITION = 'strpos("|col0|", "<del>") === false';

	$HD_Form -> FG_OTHER_BUTTON1_IMG = '';
	$HD_Form -> FG_OTHER_BUTTON2_IMG = '';
	$HD_Form -> FG_OTHER_BUTTON3_IMG = '';
	$HD_Form -> FG_OTHER_BUTTON4_IMG = '';
	$HD_Form -> FG_OTHER_BUTTON5_IMG = Images_Path . "/money.png";

}

$HD_Form -> FG_INTRO_TEXT_EDITION= gettext("You can modify, through the following form, the different properties of your")." ".$HD_Form->FG_INSTANCE_NAME;
$HD_Form -> FG_INTRO_TEXT_ASK_DELETION = gettext("If you really want remove this")." ".$HD_Form->FG_INSTANCE_NAME.", ".gettext("click on the delete button.");
$HD_Form -> FG_INTRO_TEXT_ADD = gettext("you can add easily a new")." ".$HD_Form->FG_INSTANCE_NAME.".<br>".gettext("Fill the following fields and confirm by clicking on the button add.");


$HD_Form -> FG_FILTER_FORM_ACTION = 'list';

$HD_Form -> FG_FILTER_APPLY = true;
$HD_Form -> FG_FILTERFIELDNAME = gettext("CUSTOMER");
$HD_Form -> FG_FILTER_APPLY2 = true;
//$HD_Form -> FG_FILTERFIELD2 = 'cc_ratecard.id';
$HD_Form -> FG_FILTERFIELDNAME2 = 'CallerID';

if (isset($filterprefix)  &&  (strlen($filterprefix)>0)) {
	$tempCLAUSE = " (upper(lastname) like upper('$filterprefix%') OR upper(company_name) like upper('$filterprefix%'))";
} else
	$tempCLAUSE = "";

if (is_numeric($filterprefix2)) {
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
		$HD_Form -> FG_TABLE_CLAUSE.=" AND";
	$HD_Form -> FG_TABLE_CLAUSE .= " ((cid LIKE '".$addsearch."' OR phone+0 LIKE '".$addsearch."')";
	if ($tempCLAUSE) $HD_Form -> FG_TABLE_CLAUSE .= " OR".$tempCLAUSE;
	$HD_Form -> FG_TABLE_CLAUSE .= ")";
} else if ($tempCLAUSE) {
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0)
		$HD_Form -> FG_TABLE_CLAUSE.=" AND";
	$HD_Form -> FG_TABLE_CLAUSE .= $tempCLAUSE;
}

$HD_Form -> FG_INTRO_TEXT_ADITION = gettext("Add a ".$HD_Form->FG_INSTANCE_NAME." now.");
$HD_Form -> FG_TEXT_ADITION_CONFIRMATION = gettext("Your new ".$HD_Form->FG_INSTANCE_NAME." has been inserted. <br>");

$HD_Form -> FG_BUTTON_EDITION_SRC = $HD_Form -> FG_BUTTON_ADITION_SRC  = Images_Path . "/cormfirmboton.gif";
$HD_Form -> FG_BUTTON_EDITION_BOTTOM_TEXT = $HD_Form -> FG_BUTTON_ADITION_BOTTOM_TEXT = gettext("Once you have completed the form above, click on the CONTINUE button.");

$HD_Form -> FG_GO_LINK_AFTER_ACTION_ADD = $PHP_SELF."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_EDIT = $PHP_SELF."?atmenu=document&stitle=Document&wh=AC&id=";
$HD_Form -> FG_GO_LINK_AFTER_ACTION_DELETE = $PHP_SELF."?atmenu=document&stitle=Document&wh=AC&id=";
