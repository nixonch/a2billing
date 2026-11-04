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


include ("../lib/admin.defines.php");
include ("../lib/admin.module.access.php");
include ("../lib/Form/Class.FormHandler.inc.php");
include ("../lib/admin.smarty.php");

if (! has_rights (ACX_MAINTENANCE)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}

check_demo_mode();

$HD_Form = new FormHandler("cc_card","Customer");
$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_ID="id";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";
$HD_Form -> FG_FILTER_SEARCH_SESSION_NAME = 'card_archiving_selection';
$cnts = new Constants();
$language_list_r = $cnts->getLanguagesRevertList();


$simultaccess_list = array();
$simultaccess_list["0"] = array( gettext("INDIVIDUAL ACCESS"), "0");
$simultaccess_list["1"] = array( gettext("SIMULTANEOUS ACCESS"), "1");

$simultaccess_list_r = $cnts->getCardAccess_Revert_List();


$currency_list = array();
$currency_list_r = array();
$indcur=0;

$currencies_list = get_currencies();
foreach($currencies_list as $key => $cur_value) {
	$currency_list[$key]  = array( $cur_value[1].' ('.$cur_value[2].')', $key);
	$currency_list_r[$key]  = array( $key, $cur_value[1]);
	$currency_list_key[$key][0] = $key;
}


$cardstatus_list = array();
$cardstatus_list["0"]  = array( gettext("CANCELLED"), "0");
$cardstatus_list["1"]  = array( gettext("ACTIVE"), "1");
$cardstatus_list["2"]  = array( gettext("NEW"), "2");
$cardstatus_list["3"]  = array( gettext("WAITING-MAILCONFIRMATION"), "3");
$cardstatus_list["4"]  = array( gettext("RESERVED"), "4");
$cardstatus_list["5"]  = array( gettext("EXPIRED"), "5");

$cardstatus_list_r = $cnts->getCardStatus_Revert_List();


$cardstatus_list_acronym = $cnts->getCardStatus_Acronym_List();


$typepaid_list = array();
$typepaid_list["0"]  = array( gettext("PREPAID CARD"), "0");
$typepaid_list["1"]  = array( gettext("POSTPAY CARD"), "1");

$expire_list = array();
$expire_list["0"]  = array( gettext("NO EXPIRY"), "0");
$expire_list["1"]  = array( gettext("EXPIRE DATE"), "1");
$expire_list["2"]  = array( gettext("EXPIRE DAYS SINCE FIRST USE"), "2");
$expire_list["3"]  = array( gettext("EXPIRE DAYS SINCE CREATION"), "3");


$actived_list = array();
$actived_list["t"] = array( gettext("On"), "t");
$actived_list["f"] = array( gettext("Off"), "f");

$yesno = array();
$yesno["1"] = array( gettext("Yes"), "1");
$yesno["0"] = array( gettext("No"), "0");

$invoiceday_list = array();
for ($k=0;$k<=28;$k++)
	$invoiceday_list["$k"]  = array( "$k", "$k");

$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = FALSE;
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_DO_ARCHIVE_ALL = false;
$HD_Form -> AddViewElement(gettext("ID"), "id", "3%", "center", "sort");
$HD_Form -> AddViewElement(gettext("ACCOUNT NUMBER"), "username", "20%", "center", "sort", "", "30", "", "", "", "", "linktocustomer");
$HD_Form -> AddViewElement("<acronym title=\"".gettext("BALANCE")."\">".gettext("BA")."</acronym>", "credit", "5%", "center", "sort", "", "", "", "", "", "", "display_2dec");
$HD_Form -> AddViewElement(gettext("LASTNAME"), "lastname", "10%", "center", "sort", "15");
$HD_Form -> AddViewElement(gettext("STATUS"), "status", "8%", "center", "sort", "", "list", $cardstatus_list_acronym);
$HD_Form -> AddViewElement(gettext("LG"), "language", "10%", "center", "sort");
$HD_Form -> AddViewElement(gettext("USE"), "inuse", "8%", "center", "sort");
$HD_Form -> AddViewElement("<acronym title=\"".gettext("CURRENCY")."\">".gettext("CUR")."</acronym>", "currency", "8%", "center", "sort", "", "list", $currency_list_key);
$HD_Form -> AddViewElement(gettext("SIP"), "sip_buddy", "10%", "center", "sort", "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("IAX"), "iax_buddy", "10%", "center", "sort", "", "list", $yesno);
$HD_Form -> AddViewElement("<acronym title=\"AMOUNT OF CALL DONE\">".gettext("ACD")."</acronym>", "nbused", "10%", "center", "sort");
$FG_COL_QUERY='id, username, credit, lastname, status, language, inuse, currency, sip_buddy, iax_buddy, nbused';

$HD_Form -> FieldViewElement ($FG_COL_QUERY);


$HD_Form -> CV_NO_FIELDS  = gettext("NO CUSTOMER SEARCHED!");
$HD_Form -> FG_LIMITE_DISPLAY = 30;

$HD_Form -> FG_FILTER_SEARCH_FORM = true;
$HD_Form -> FG_FILTER_SEARCH_DELETE_ALL = false;
$HD_Form -> FG_FILTER_SEARCH_TOP_TEXT = gettext('Define specific criteria to search for cards created.');
$HD_Form -> FG_FILTER_SEARCH_1_TIME = true;
$HD_Form -> FG_FILTER_SEARCH_1_TIME_TEXT = gettext('Creation date');


$HD_Form -> FG_FILTER_SEARCH_1_TIME_BIS = true;
$HD_Form -> FG_FILTER_SEARCH_1_TIME_TEXT_BIS = gettext('FIRST USE DATE');
$HD_Form -> FG_FILTER_SEARCH_1_TIME_FIELD_BIS = 'firstusedate';

$HD_Form -> FG_FILTER_SEARCH_3_TIME = true;
$HD_Form -> FG_FILTER_SEARCH_3_TIME_TEXT = gettext('Select customer created more than');
$HD_Form -> FG_FILTER_SEARCH_3_TIME_FIELD = 'creationdate';

//Select card older than : 3 Months, 4 Months, 5.... 12 Months
$HD_Form -> AddSearchElement_C1(gettext("ACCOUNT NUMBER"), 'username','usernametype');
$HD_Form -> AddSearchElement_C1(gettext("LASTNAME"),'lastname','lastnametype');
$HD_Form -> AddSearchElement_C1(gettext("LOGIN"),'useralias','useraliastype');
$HD_Form -> AddSearchElement_C1(gettext("MACADDRESS"),'mac_addr','macaddresstype');
$HD_Form -> AddSearchElement_C1(gettext("EMAIL"),'email','emailtype');
$HD_Form -> AddSearchElement_C2(gettext("CUSTOMER ID"),'id1','id1type','id2','id2type','id');
$HD_Form -> AddSearchElement_C2(gettext("CREDIT"),'credit1','credit1type','credit2','credit2type','credit');
$HD_Form -> AddSearchElement_C2(gettext("INUSE"),'inuse1','inuse1type','inuse2','inuse2type','inuse');

$HD_Form -> FG_FILTER_SEARCH_FORM_SELECT_TEXT = '';
$HD_Form -> AddSearchElement_Select(gettext("SELECT LANGUAGE"), null, null, null, null, null, "language", 0, $language_list_r);
$HD_Form -> AddSearchElement_Select(gettext("SELECT TARIFF"), "cc_tariffgroup", "id, tariffgroupname, id", "", "tariffgroupname", "ASC", "tariff");
$HD_Form -> AddSearchElement_Select(gettext("SELECT STATUS"), null, null, null, null,null , "status", 0, $cardstatus_list_r);
$HD_Form -> AddSearchElement_Select(gettext("SELECT ACCESS"), null, null, null, null, null, "simultaccess", 0, $simultaccess_list_r);
$HD_Form -> AddSearchElement_Select(gettext("SELECT GROUP"), "cc_card_group", "id, name", "", "name", "ASC", "id_group");
$HD_Form -> AddSearchElement_Select(gettext("SELECT CURRENCY"), null, null, null, null, null, "currency", 0, $currency_list_r);
// Page-local search state: do not use the legacy pipe-delimited search session
// or let perform_action() rebuild a different predicate for this archive page.
$customer_search_defaults = array('use_age' => '', 'month_earlier' => '3', 'Period' => '');
foreach (array('', '_bis') as $suffix) {
	foreach (array('from', 'to') as $bound) {
		$customer_search_defaults[$bound.'day'.$suffix] = '';
		$customer_search_defaults[$bound.'statsday_sday'.$suffix] = '1';
		$customer_search_defaults[$bound.'statsmonth_sday'.$suffix] = date('Y-m');
	}
}
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_1C as $field) {
	$customer_search_defaults[$field[1]] = '';
	$customer_search_defaults[$field[2]] = '1';
}
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_2C as $field) {
	$customer_search_defaults[$field[1]] = $customer_search_defaults[$field[3]] = '';
	$customer_search_defaults[$field[2]] = '1';
	$customer_search_defaults[$field[4]] = '2';
}
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_SELECT as $field) $customer_search_defaults[$field[2]] = '';
$request = array_merge($_GET, $_POST);
$archive_requested = isset($request['archive']) && $request['archive'] === 'true';
$archive_context = null;
$archive_message = '';
$search_active = false;
$customer_search = $customer_search_defaults;
$invalid_filters = false;
if ($archive_requested) {
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($request['archive_token']) && is_string($request['archive_token'])
		&& isset($_SESSION['card_archiving_searches'][$request['archive_token']])) {
		$archive_context = $_SESSION['card_archiving_searches'][$request['archive_token']];
		$customer_search = array_replace($customer_search, $archive_context['parameters']);
		$search_active = true;
	} else $archive_message = gettext('The search has expired. Run the search again before archiving.');
} elseif (isset($request['cancelsearch']) && in_array($request['cancelsearch'], array('true', '1'), true)) {
	unset($_SESSION['card_archiving_selection']);
} elseif (isset($request['posted_search']) && $request['posted_search'] === '1') {
	foreach ($customer_search_defaults as $parameter => $default) {
		if (array_key_exists($parameter, $request)) {
			if (!is_scalar($request[$parameter])) $invalid_filters = true;
			else $customer_search[$parameter] = trim((string)$request[$parameter]);
		}
	}
	$search_active = true;
} elseif (isset($_SESSION['card_archiving_selection']) && is_array($_SESSION['card_archiving_selection'])) {
	$customer_search = array_replace($customer_search, $_SESSION['card_archiving_selection']);
	$search_active = true;
}
$clauses = array();
$quote = function ($value) use ($HD_Form) { return $HD_Form->DBHandle->qstr($value); };
foreach (array('use_age', 'fromday', 'today', 'fromday_bis', 'today_bis') as $parameter) {
	if (!in_array($customer_search[$parameter], array('', '0', '1', 'true', 'on'), true)) $invalid_filters = true;
}
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_1C as $field) {
	$value = $customer_search[$field[1]];
	$type = $customer_search[$field[2]];
	if (!in_array($type, array('1', '2', '3', '4'), true)) { $invalid_filters = true; continue; }
	if ($value === '') continue;
	if ($type === '1') $clauses[] = $field[1].' = '.$quote($value);
	else {
		$pattern = ($type === '3' || $type === '4' ? '%' : '').$value.($type === '2' || $type === '3' ? '%' : '');
		$clauses[] = 'LOWER('.$field[1].') LIKE LOWER('.$quote($pattern).')';
	}
}
$comparisons = array('1' => '=', '2' => '<=', '3' => '<', '4' => '>', '5' => '>=');
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_2C as $field) {
	foreach (array(array($field[1], $field[2]), array($field[3], $field[4])) as $bound) {
		$value = $customer_search[$bound[0]];
		$type = $customer_search[$bound[1]];
		if (!isset($comparisons[$type])) { $invalid_filters = true; continue; }
		if ($value === '') continue;
		$valid = $field[5] === 'credit' ? preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', $value) : ctype_digit($value);
		if (!$valid) { $invalid_filters = true; continue; }
		$clauses[] = $field[5].' '.$comparisons[$type].' '.$quote($value);
	}
}
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_SELECT as $field) {
	$value = $customer_search[$field[2]];
	if ($value === '') continue;
	if ($field[1]) $valid = ctype_digit($value);
	else {
		$allowed = array();
		foreach ($field[3] as $option) $allowed[] = (string)$option[0];
		$valid = in_array($value, $allowed, true);
	}
	if (!$valid) { $invalid_filters = true; continue; }
	$clauses[] = $field[2].' = '.$quote($value);
}
foreach (array('' => 'creationdate', '_bis' => 'firstusedate') as $suffix => $column) {
	foreach (array('from', 'to') as $bound) {
		if (!$customer_search[$bound.'day'.$suffix]) continue;
		$month = $customer_search[$bound.'statsmonth_sday'.$suffix];
		$day_name = $bound.'statsday_sday'.$suffix;
		$day = $customer_search[$day_name];
		if (!preg_match('/^([0-9]{4})-([0-9]{2})$/D', $month, $parts) || !checkdate((int)$parts[2], 1, (int)$parts[1])
			|| !ctype_digit($day) || $day < 1 || $day > 31) { $invalid_filters = true; continue; }
		$date = new DateTime($month.'-01 00:00:00');
		$customer_search[$day_name] = sprintf('%02d', min((int)$day, (int)$date->format('t')));
		$date->setDate((int)$parts[1], (int)$parts[2], (int)$customer_search[$day_name]);
		if ($bound === 'to') $date->modify('+1 day');
		$clauses[] = $column.($bound === 'from' ? ' >= ' : ' < ').$quote($date->format('Y-m-d H:i:s'));
	}
}
if ($customer_search['use_age'] || $customer_search['Period'] === 'month_older_rad') {
	$months = $customer_search['month_earlier'];
	if (!ctype_digit($months) || $months < 3 || $months > 12) $invalid_filters = true;
	else $clauses[] = DB_TYPE === 'postgres' ? "creationdate < CURRENT_TIMESTAMP - INTERVAL '$months months'" : "creationdate < DATE_SUB(NOW(), INTERVAL $months MONTH)";
	$customer_search['use_age'] = 'true';
}
$HD_Form->FG_TABLE_CLAUSE = $invalid_filters ? '1=0' : ($clauses ? implode(' AND ', $clauses) : '1=1');
if ($invalid_filters) $archive_message = gettext('Invalid search criteria. Correct the filters and run the search again.');
$sort_columns = array('id', 'username', 'credit', 'lastname', 'status', 'language', 'inuse', 'currency', 'sip_buddy', 'iax_buddy', 'nbused');
$order = $archive_context ? $archive_context['order'] : (isset($request['order']) ? $request['order'] : 'id');
$sens = $archive_context ? $archive_context['sens'] : (isset($request['sens']) ? $request['sens'] : 'ASC');
if (!in_array($order, $sort_columns, true)) $order = 'id';
if (!in_array($sens, array('ASC', 'DESC'), true)) $sens = 'ASC';
$current_page = isset($request['current_page']) && is_scalar($request['current_page']) && ctype_digit((string)$request['current_page']) ? (int)$request['current_page'] : 0;
$navigation = array('posted_search' => '1') + $customer_search;
foreach (array('atmenu', 'stitle', 'section') as $parameter) {
	if ($archive_context && isset($archive_context['navigation'][$parameter])) $navigation[$parameter] = $archive_context['navigation'][$parameter];
	elseif (isset($request[$parameter]) && is_scalar($request[$parameter])) $navigation[$parameter] = (string)$request[$parameter];
}
$HD_Form->CV_FOLLOWPARAMETERS = '&'.http_build_query($navigation);
$HD_Form->_vars = array_merge(array_map('rawurlencode', $navigation), array('order' => $order, 'sens' => $sens, 'current_page' => $current_page));
if (isset($request['mydisplaylimit']) && is_scalar($request['mydisplaylimit']) && ($request['mydisplaylimit'] === 'ALL'
	|| (ctype_digit((string)$request['mydisplaylimit']) && $request['mydisplaylimit'] > 0 && $request['mydisplaylimit'] <= 5000))) {
	$HD_Form->_vars['mydisplaylimit'] = $request['mydisplaylimit'];
}
$HD_Form->_processed = array();
$HD_Form->FG_FILTER_SEARCH_FORM = false;
$form_action = $action = 'list';
if ($archive_context && !$invalid_filters) {
	$rec = archive_data('WHERE '.$archive_context['clause'], 'card');
	if ($rec !== false) {
		unset($_SESSION['card_archiving_searches'][$request['archive_token']]);
		$_SESSION['card_archiving_message'] = $rec > 0 ? gettext('The data has been successfully archived') : gettext('No customers matched the archive criteria.');
		header('Location: '.$PHP_SELF.'?'.http_build_query($navigation + array('order' => $order, 'sens' => $sens)));
		exit;
	}
	$archive_message = gettext('Archiving failed. Check that the customer and archive table structures match.');
}
if (!$archive_requested && isset($_SESSION['card_archiving_message'])) {
	$archive_message = $_SESSION['card_archiving_message'];
	unset($_SESSION['card_archiving_message']);
}

// Execute the list using the already validated predicate.
$list = $HD_Form -> perform_action($form_action);
if ($list !== false && !$invalid_filters && $search_active) $_SESSION['card_archiving_selection'] = $customer_search;

// Prepare presentation data; the Smarty template performs no database reads.
$search_view = array('action' => $PHP_SELF, 'state' => $customer_search, 'active' => $search_active,
	'width' => $HD_Form->FG_FILTER_SEARCH_TABLE_WITDH, 'caption' => $HD_Form->FG_FILTER_SEARCH_TOP_TEXT,
	'hidden' => array('posted_search' => '1', 'current_page' => '0', 'order' => $order, 'sens' => $sens),
	'dates' => array(), 'text_fields' => array(), 'range_fields' => array(), 'select_fields' => array(),
	'text_types' => array('1' => gettext('Exact'), '2' => gettext('Begins with'), '3' => gettext('Contains'), '4' => gettext('Ends with')),
	'numeric_types' => array('4' => '>', '5' => '>=', '1' => '=', '2' => '<=', '3' => '<'),
	'age_months' => range(3, 12), 'search_image' => Images_Path_Main.'/button-search.gif');
foreach (array('atmenu', 'stitle', 'section') as $parameter) if (isset($navigation[$parameter])) $search_view['hidden'][$parameter] = $navigation[$parameter];
$month_names = array(gettext('January'), gettext('February'), gettext('March'), gettext('April'), gettext('May'), gettext('June'), gettext('July'), gettext('August'), gettext('September'), gettext('October'), gettext('November'), gettext('December'));
$month_options = array();
for ($year = (int)date('Y'); $year >= (int)date('Y') - 10; $year--) {
	for ($month = $year === (int)date('Y') ? (int)date('n') : 12; $month >= 1; $month--) $month_options[sprintf('%04d-%02d', $year, $month)] = $month_names[$month-1].'-'.$year;
}
foreach (array('' => gettext('Creation date'), '_bis' => gettext('FIRST USE DATE')) as $suffix => $label) {
	$row = array('label' => $label, 'bounds' => array());
	foreach (array('from', 'to') as $bound) {
		$month_name = $bound.'statsmonth_sday'.$suffix;
		$options = $month_options;
		if (!isset($options[$customer_search[$month_name]])) $options[$customer_search[$month_name]] = $customer_search[$month_name];
		$row['bounds'][] = array('checkbox' => $bound.'day'.$suffix, 'label' => $bound === 'from' ? gettext('From :') : gettext('To :'),
			'day_name' => $bound.'statsday_sday'.$suffix, 'month_name' => $month_name, 'months' => $options, 'days' => range(1, 31));
	}
	$search_view['dates'][] = $row;
}
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_1C as $field) $search_view['text_fields'][] = array('label' => $field[0], 'name' => $field[1], 'type_name' => $field[2]);
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_2C as $field) $search_view['range_fields'][] = array('label' => $field[0], 'bounds' => array(array('name' => $field[1], 'type_name' => $field[2]), array('name' => $field[3], 'type_name' => $field[4])));
foreach ($HD_Form->FG_FILTER_SEARCH_FORM_SELECT as $field) {
	$options = $field[3];
	if ($field[1]) {
		$table = new Table($field[1][0], $field[1][1]);
		$options = $table->Get_list($HD_Form->DBHandle, $field[1][2], $field[1][3], $field[1][4]);
	}
	$search_view['select_fields'][] = array('label' => $field[0], 'name' => $field[2], 'options' => is_array($options) ? $options : array());
}
$smarty->assign('customer_search_view', $search_view);

// #### HEADER SECTION
$smarty->display('main.tpl');
echo $CC_help_data_archive;

?>
<script language="JavaScript" src="javascript/card.js"></script>
<div class="toggle_hide2show">
<center><a href="#" target="_self" class="toggle_menu"><img class="toggle_hide2show" src="<?php echo KICON_PATH; ?>/toggle_hide2show.png" onmouseover="this.style.cursor='hand';" HEIGHT="16"> <font class="fontstyle_002"><?php echo gettext("SEARCH CUSTOMERS");?> </font></a><?php if($search_active){ ?>&nbsp;(<font style="color:#EE6564;" > <?php echo gettext("search activated"); ?> </font> ) <?php } ?></center>
	<div class="tohide" style="<?php echo $invalid_filters ? '' : 'display:none;'; ?>">

<?php
$smarty->display('data_archiving_search.tpl');
?>

	</div>
</div>

<center>
<FORM name="frm_archive" id="frm_archive">
<table class="bar-status" width="50%" border="0" cellspacing="1" cellpadding="2" align="center">
			<tbody>			
			<tr>
				<td width="30%" align="left" valign="top" class="bgcolor_004">					
					<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("ARCHIVING OPTIONS");?></font>
				</td>				
				<td width="70%" align="CENTER" class="bgcolor_005">
				<table width="100%" border="0" cellspacing="0" cellpadding="0" align="center"><tr>
				  <td class="fontstyle_searchoptions">
				<select name="archiveselect" class="form_input_select" onchange="window.location.href=this.value;">
				<option value="A2B_data_archiving.php" selected><?php echo gettext("Customer Archiving");?></option>
				<option value="A2B_call_archiving.php"><?php echo gettext("Calls Archiving");?></option>
				</select>
					</td>					
				</tr></table></td>
			</tr>			
		</tbody></table>
</FORM>
</center>

<?php 	

if ($archive_message !== '') print '<div align="center">'.htmlspecialchars($archive_message, ENT_QUOTES, 'UTF-8').'</div>';
if (!$invalid_filters && is_array($list) && count($list) > 0) {
	$archive_token = bin2hex(random_bytes(16));
	if (!isset($_SESSION['card_archiving_searches'])) $_SESSION['card_archiving_searches'] = array();
	$_SESSION['card_archiving_searches'][$archive_token] = array('clause' => $HD_Form->FG_TABLE_CLAUSE, 'parameters' => $customer_search, 'navigation' => $navigation, 'order' => $order, 'sens' => $sens);
	while (count($_SESSION['card_archiving_searches']) > 10) array_shift($_SESSION['card_archiving_searches']);
?>
<form method="post" action="<?php echo htmlspecialchars($PHP_SELF, ENT_QUOTES, 'UTF-8'); ?>" style="text-align:center">
	<input type="hidden" name="archive" value="true">
	<input type="hidden" name="archive_token" value="<?php echo $archive_token; ?>">
	<input type="submit" value="<?php echo gettext('Archiving All '); ?>" class="form_input_button" onclick="return confirm('This action will archive the data, Are you sure?');">
</form>
<?php
}
$HD_Form -> create_form ($form_action, $list, $id=null);


$smarty->display('footer.tpl');

