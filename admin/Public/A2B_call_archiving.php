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


include_once(dirname(__FILE__) . "/../lib/admin.defines.php");
include ("../lib/Form/Class.FormHandler.inc.php");
include_once(dirname(__FILE__) . "/../lib/admin.module.access.php");
include ("../lib/admin.smarty.php");


if (! has_rights (ACX_MAINTENANCE)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");	   
	die();
}

check_demo_mode();

$call_archiving_parameters = array('customer', 'entercustomer', 'enterprovider', 'entertariffgroup', 'entertrunk', 'enterratecard', 'posted', 'Period', 'frommonth', 'fromstatsmonth', 'tomonth', 'tostatsmonth', 'fromday', 'fromstatsday_sday', 'fromstatsmonth_sday', 'today', 'tostatsday_sday', 'tostatsmonth_sday', 'month_earlier', 'dsttype', 'srctype', 'resulttype', 'stitle', 'atmenu', 'dst', 'src', 'choose_currency', 'terminatecauseid');
getpost_ifset(array_merge($call_archiving_parameters, array('current_page', 'order', 'sens', 'mydisplaylimit', 'archive', 'archive_token')));
// CallerID/source values can contain spaces, '+' and '_' that the legacy
// request helper removes. Retain them and quote them with the database driver.
foreach (array('src', 'dst') as $parameter) {
	$request_value = isset($_POST[$parameter]) ? $_POST[$parameter] : (isset($_GET[$parameter]) ? $_GET[$parameter] : '');
	$$parameter = is_scalar($request_value) ? addslashes(trim((string)$request_value)) : $request_value;
}

$archive_requested = isset($archive) && $archive === 'true';
$archive_context = null;
$archive_message = '';
if ($archive_requested) {
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($archive_token) && is_string($archive_token)
		&& isset($_SESSION['call_archiving_searches'][$archive_token])) {
		$archive_context = $_SESSION['call_archiving_searches'][$archive_token];
		foreach ($call_archiving_parameters as $parameter) {
			$$parameter = isset($archive_context['parameters'][$parameter]) ? $archive_context['parameters'][$parameter] : '';
		}
		$order = $archive_context['order'];
		$sens = $archive_context['sens'];
	} else {
		$archive_message = gettext('The search has expired. Run the search again before archiving.');
	}
}

$invalid_filters = false;
foreach ($call_archiving_parameters as $parameter) {
	if (!isset($$parameter)) $$parameter = '';
	if (!is_scalar($$parameter)) {
		$invalid_filters = true;
		$$parameter = '';
	}
}
$current_page = isset($current_page) && is_scalar($current_page) && ctype_digit((string)$current_page) ? (int)$current_page : 0;
$HD_Form = new FormHandler("cc_call","Calls");

$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();

$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER = "starttime";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "DESC";
$HD_Form -> FG_LIMITE_DISPLAY=30;

$yesno = array();
$yesno["1"] = array( gettext("Yes"), "1");
$yesno["0"] = array( gettext("No"), "0");


$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = FALSE;
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_DO_ARCHIVE_ALL = false;
$HD_Form -> AddViewElement(gettext("ID"), "id", "3%", "center", "sort");
$HD_Form -> AddViewElement(gettext("Calldate"), "starttime", "15%", "center", "SORT", "19", "", "", "", "", "", "display_dateformat");
$HD_Form -> AddViewElement(gettext("CalledNumber"), "calledstation", "15%", "center", "SORT", "30", "", "", "", "", "", "remove_prefix");
$HD_Form -> AddViewElement(gettext("Destination"), "destination", "15%", "center", "SORT", "30", "", "", "", "", "", "remove_prefix");
$HD_Form -> AddViewElement(gettext("Duration"), "real_sessiontime", "7%", "center", "SORT", "30", "", "", "", "", "", "display_minute");
$HD_Form -> AddViewElement(gettext("CardUsed"), "card_id", "11%", "center", "SORT", "", "30", "", "", "", "", "linktocustomer_id");
$HD_Form -> AddViewElement(gettext("terminatecauseid"), "terminatecauseid", "10%", "center", "SORT", "30");
$HD_Form -> AddViewElement(gettext("IAX/SIP"), "sipiax", "6%", "center", "SORT",  "", "list", $yesno);
$HD_Form -> AddViewElement(gettext("Buy Cost"), "buycost", "10%", "center", "SORT", "30", "", "", "", "", "", "display_2bill");
$HD_Form -> AddViewElement(gettext("Cost"), "sessionbill", "10%", "center", "SORT", "30", "", "", "", "", "", "display_2bill");

$FG_COL_QUERY='id, starttime, calledstation, destination, real_sessiontime, card_id, terminatecauseid, sipiax, buycost, sessionbill';

$HD_Form -> FieldViewElement ($FG_COL_QUERY);

// Keep this predicate valid both for the list and for an unaliased cc_call archive query.
$clauses = array();
$quote = function ($value) use ($HD_Form) {
	return $HD_Form->DBHandle->qstr(stripslashes((string)$value));
};
if ($Period === '') {
	$Period = 'Day';
	$fromday = 'true';
	$fromstatsday_sday = date('d');
	$fromstatsmonth_sday = date('Y-m');
}
foreach (array('fromstatsmonth', 'tostatsmonth', 'fromstatsmonth_sday', 'tostatsmonth_sday') as $parameter) {
	if ($$parameter === '') $$parameter = date('Y-m');
}
foreach (array('fromstatsday_sday', 'tostatsday_sday') as $parameter) {
	if ($$parameter === '') $$parameter = '1';
}
$month_start = function ($value) use (&$invalid_filters) {
	if (!preg_match('/^([0-9]{4})-([0-9]{2})$/D', $value, $parts) || !checkdate((int)$parts[2], 1, (int)$parts[1])) {
		$invalid_filters = true;
		return null;
	}
	return new DateTime($value.'-01 00:00:00');
};
if ($Period === 'Month' || $Period === 'Day') {
	foreach (array('from', 'to') as $bound) {
		$enabled = $Period === 'Month' ? ${$bound.'month'} : ${$bound.'day'};
		if (!$enabled) continue;
		$month = $Period === 'Month' ? ${$bound.'statsmonth'} : ${$bound.'statsmonth_sday'};
		$date = $month_start($month);
		if (!$date) continue;
		if ($Period === 'Day') {
			$day_parameter = $bound.'statsday_sday';
			if (!ctype_digit((string)$$day_parameter) || $$day_parameter < 1 || $$day_parameter > 31) {
				$invalid_filters = true;
				continue;
			}
			$$day_parameter = min((int)$$day_parameter, (int)$date->format('t'));
			$date->setDate((int)$date->format('Y'), (int)$date->format('m'), $$day_parameter);
		}
		if ($bound === 'to') $date->modify($Period === 'Month' ? '+1 month' : '+1 day');
		$clauses[] = 'starttime '.($bound === 'from' ? '>=' : '<').' '.$quote($date->format('Y-m-d H:i:s'));
	}
} elseif ($Period === 'month_older_rad') {
	if ($month_earlier === '') $month_earlier = '3';
	if (!ctype_digit((string)$month_earlier) || $month_earlier < 3 || $month_earlier > 12) {
		$invalid_filters = true;
	} else {
		$clauses[] = DB_TYPE === 'postgres'
			? "starttime < CURRENT_TIMESTAMP - INTERVAL '$month_earlier months'"
			: "starttime < DATE_SUB(NOW(), INTERVAL $month_earlier MONTH)";
	}
} else {
	$invalid_filters = true;
}

foreach (array('src' => 'src', 'dst' => 'calledstation') as $parameter => $column) {
	$type_parameter = $parameter.'type';
	if ($$type_parameter === '') $$type_parameter = '1';
	if (!in_array((string)$$type_parameter, array('1', '2', '3', '4'), true)) {
		$invalid_filters = true;
		continue;
	}
	if ($$parameter === '') continue;
	$matches = array();
	foreach (explode(',', $$parameter) as $value) {
		$value = trim($value);
		if ($value === '') continue;
		if ($$type_parameter == 1) $matches[] = $column.' = '.$quote($value);
		else {
			$value = ($$type_parameter == 3 || $$type_parameter == 4 ? '%' : '').$value.($$type_parameter == 2 || $$type_parameter == 3 ? '%' : '');
			$matches[] = $column.' LIKE '.$quote($value);
		}
	}
	if (!$matches) $invalid_filters = true;
	else $clauses[] = '('.implode(' OR ', $matches).')';
}

$account = $customer !== '' ? $customer : $entercustomer;
if ($account !== '') {
	if (!ctype_digit((string)$account)) $invalid_filters = true;
	else $clauses[] = 'card_id IN (SELECT id FROM cc_card WHERE username = '.$quote($account).')';
	$entercustomer = $account;
	$customer = '';
}
if ($_SESSION['is_admin'] == 1) {
	foreach (array('enterprovider' => 'id_provider', 'entertrunk' => 'id_trunk', 'entertariffgroup' => 'id_tariffgroup', 'enterratecard' => 'id_ratecard') as $parameter => $column) {
		if ($$parameter === '') continue;
		if (!ctype_digit((string)$$parameter)) {
			$invalid_filters = true;
			continue;
		}
		$clauses[] = $parameter === 'enterprovider'
			? 'id_trunk IN (SELECT id_trunk FROM cc_trunk WHERE id_provider = '.$quote($$parameter).')'
			: $column.' = '.$quote($$parameter);
	}
}
if ($terminatecauseid === '') $terminatecauseid = 'ANSWER';
if ($terminatecauseid === 'ANSWER') $clauses[] = 'terminatecauseid = 1';
elseif ($terminatecauseid !== 'ALL') $invalid_filters = true;
$HD_Form->FG_TABLE_CLAUSE = $invalid_filters ? '1=0' : ($clauses ? implode(' AND ', $clauses) : '1=1');
if ($invalid_filters) $archive_message = gettext('Invalid search criteria. Correct the filters and run the search again.');

$currencies_list = get_currencies();
if (!isset($currencies_list[$choose_currency])) $choose_currency = strtoupper(BASE_CURRENCY);
if (!in_array($resulttype, array('min', 'sec'), true)) $resulttype = 'min';
$sort_columns = array('id', 'starttime', 'calledstation', 'destination', 'real_sessiontime', 'card_id', 'terminatecauseid', 'sipiax', 'buycost', 'sessionbill');
if (!isset($order) || !in_array($order, $sort_columns, true)) $order = 'starttime';
if (!isset($sens) || !in_array($sens, array('ASC', 'DESC'), true)) $sens = 'DESC';
$search_parameters = array();
foreach ($call_archiving_parameters as $parameter) $search_parameters[$parameter] = $$parameter;
$follow_parameters = array_map('stripslashes', array_map('strval', $search_parameters));
// The legacy list renderer inserts these values directly into navigation URLs.
$HD_Form->_vars = array_merge(array_map('rawurlencode', $follow_parameters), array('order' => $order, 'sens' => $sens, 'current_page' => $current_page));
if (isset($mydisplaylimit) && is_scalar($mydisplaylimit)
	&& ($mydisplaylimit === 'ALL' || (ctype_digit((string)$mydisplaylimit) && $mydisplaylimit > 0 && $mydisplaylimit <= 5000))) {
	$HD_Form->_vars['mydisplaylimit'] = $mydisplaylimit;
}
$HD_Form->_processed = array();
$HD_Form->CV_FOLLOWPARAMETERS = '&'.http_build_query($follow_parameters);

if ($archive_context && !$invalid_filters) {
	$rec = archive_data('WHERE '.$archive_context['clause'], 'call');
	if ($rec !== false) {
		unset($_SESSION['call_archiving_searches'][$archive_token]);
		$_SESSION['call_archiving_message'] = $rec > 0 ? gettext('The data has been successfully archived') : gettext('No calls matched the archive criteria.');
		header('Location: '.$PHP_SELF.'?'.http_build_query(array_merge($follow_parameters, array('order' => $order, 'sens' => $sens))));
		exit;
	}
	$archive_message = gettext('Archiving failed.');
}
if (!$archive_requested && isset($_SESSION['call_archiving_message'])) {
	$archive_message = $_SESSION['call_archiving_message'];
	unset($_SESSION['call_archiving_message']);
}

if (isset($form_action) && $form_action === 'ask-add' && !$HD_Form->FG_ADDITION) {
    header('Location: ' . $PHP_SELF);
    exit;
}
$smarty->display('main.tpl');

?>



<!-- ** ** ** ** ** Part for the research ** ** ** ** ** -->
<center>
<FORM METHOD=POST name="myForm" ACTION="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $current_page?>">
<INPUT TYPE="hidden" NAME="posted" value=1>
<INPUT TYPE="hidden" NAME="current_page" value=0>	
	<TABLE class="bar-status" width="85%" border="0" cellspacing="1" cellpadding="2" align="center">
		<?php  if ($_SESSION["pr_groupID"]==2 && is_numeric($_SESSION["pr_IDCust"])){ ?>
		<?php  }else{ ?>
		<tr>
			<td align="left" valign="top" class="bgcolor_004">					
				<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("CUSTOMERS");?></font>
			</td>				
			<td class="bgcolor_005" align="left">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr>
				<td class="fontstyle_searchoptions" width="50%" valign="top">
					<?php echo gettext("Enter the cardnumber");?>: <INPUT TYPE="text" NAME="entercustomer" value="<?php echo htmlspecialchars(stripslashes($entercustomer), ENT_QUOTES, 'UTF-8'); ?>" class="form_input_text">
					<a href="#" onclick="alertBase('url:A2B_entity_card?popup_select=2&popup_formname=myForm&popup_fieldname=entercustomer','Card Number Selection',true,false,'#445760',null,event.pageY+20,'UR',null,null,null,null,null,sendValueCallback); return false;"><img src="<?php echo Images_Path;?>/icon_arrow_orange.gif"></a>
				</td>
				<td width="50%">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td align="left" class="fontstyle_searchoptions"><?php echo gettext("CallPlan");?> :</td>
							<td align="left" class="fontstyle_searchoptions"><INPUT TYPE="text" NAME="entertariffgroup" value="<?php echo $entertariffgroup?>" size="4" class="form_input_text">&nbsp;<a href="#" onclick="alertBase('url:A2B_entity_tariffgroup?popup_select=2&popup_formname=myForm&popup_fieldname=entertariffgroup','Call Plan Selection',true,false,'#445760',event.pageX+200,event.pageY+20,'UR',null,null,null,null,null,sendValueCallback); return false;"><img src="<?php echo Images_Path;?>/icon_arrow_orange.gif"></a></td>
							<td align="left" class="fontstyle_searchoptions"><?php echo gettext("Provider");?> :
							<td align="left" class="fontstyle_searchoptions"><INPUT TYPE="text" NAME="enterprovider" value="<?php echo $enterprovider?>" size="4" class="form_input_text">&nbsp;<a href="#" onclick="alertBase('url:A2B_entity_provider?popup_select=2&popup_formname=myForm&popup_fieldname=enterprovider','Provider Selection',true,false,'#445760',event.pageX+200,event.pageY+20,'UR',null,null,null,null,null,sendValueCallback); return false;"><img src="<?php echo Images_Path;?>/icon_arrow_orange.gif"></a></td>
						</tr>
						<tr>
							<td align="left" class="fontstyle_searchoptions"><?php echo gettext("Trunk");?> :</td>
							<td align="left" class="fontstyle_searchoptions"><INPUT TYPE="text" NAME="entertrunk" value="<?php echo $entertrunk?>" size="4" class="form_input_text">&nbsp;<a href="#" onclick="alertBase('url:A2B_entity_trunk?popup_select=2&popup_formname=myForm&popup_fieldname=entertrunk','Trunk Selection',true,false,'#445760',event.pageX+200,event.pageY+20,'UR',null,null,null,null,null,sendValueCallback); return false;"><img src="<?php echo Images_Path;?>/icon_arrow_orange.gif"></a></td>
							<td align="left" class="fontstyle_searchoptions"><?php echo gettext("Ratecard ID");?> :</td>
							<td align="left" class="fontstyle_searchoptions"><INPUT TYPE="text" NAME="enterratecard" value="<?php echo $enterratecard?>" size="4" class="form_input_text">&nbsp;<a href="#" onclick="alertBase('url:A2B_entity_def_ratecard?popup_select=2&popup_formname=myForm&popup_fieldname=enterratecard','Ratecard Selection',true,false,'#445760',event.pageX+200,event.pageY+20,'UR',null,null,null,null,null,sendValueCallback); return false;"><img src="<?php echo Images_Path;?>/icon_arrow_orange.gif"></a></td>
						</tr>
					</table>
				</tr>
			</table>
			</td>
		</tr>			
		<?php  }?>
		<tr>
			<td class="bgcolor_002" align="left">

				<input type="radio" name="Period" value="Month" <?php  if (($Period=="Month") || !isset($Period)){ ?>checked="checked" <?php  } ?>> 
				<font class="fontstyle_003"><?php echo gettext("SELECT MONTH");?></font>
			</td>
			<td class="bgcolor_003" align="left">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td class="fontstyle_searchoptions">
				<input type="checkbox" name="frommonth" value="true" <?php  if ($frommonth){ ?>checked<?php }?>>
				<?php echo gettext("From");?> : <select name="fromstatsmonth" class="form_input_select">
				<?php
					$monthname = array( gettext("January"), gettext("February"),gettext("March"), gettext("April"), gettext("May"), gettext("June"), gettext("July"), gettext("August"), gettext("September"), gettext("October"), gettext("November"), gettext("December"));
					$year_actual = date("Y");  	
					for ($i=$year_actual;$i >= $year_actual-1;$i--)
					{		   
					   if ($year_actual==$i){
						$monthnumber = date("n")-1; // Month number without lead 0.
					   }else{
						$monthnumber=11;
					   }		   
					   for ($j=$monthnumber;$j>=0;$j--){	
						$month_formated = sprintf("%02d",$j+1);
						if ($fromstatsmonth=="$i-$month_formated")	$selected="selected";
						else $selected="";
						echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
					   }
					}
				?>		
				</select>
				</td><td  class="fontstyle_searchoptions">&nbsp;&nbsp;
				<input type="checkbox" name="tomonth" value="true" <?php  if ($tomonth){ ?>checked<?php }?>> 
				<?php echo gettext("To");?> : <select name="tostatsmonth" class="form_input_select">
				<?php 	$year_actual = date("Y");  	
					for ($i=$year_actual;$i >= $year_actual-1;$i--)
					{		   
					   if ($year_actual==$i){
						$monthnumber = date("n")-1; // Month number without lead 0.
					   }else{
						$monthnumber=11;
					   }		   
					   for ($j=$monthnumber;$j>=0;$j--){	
						$month_formated = sprintf("%02d",$j+1);
						if ($tostatsmonth=="$i-$month_formated") $selected="selected";
						else $selected="";
						echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
					   }
					}
				?>
				</select>
				</td></tr></table>
			</td>
		</tr>
		
		<tr>
			<td align="left" class="bgcolor_004">
				<input type="radio" name="Period" value="Day" <?php  if ($Period=="Day"){ ?>checked="checked" <?php  } ?>> 
				<font class="fontstyle_003"><?php echo gettext("SELECT DAY");?></font>
			</td>
			<td align="left" class="bgcolor_005">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td class="fontstyle_searchoptions">
				<input type="checkbox" name="fromday" value="true" <?php  if ($fromday){ ?>checked<?php }?>> <?php echo gettext("From");?> :
				<select name="fromstatsday_sday" class="form_input_select">
					<?php  
					for ($i=1;$i<=31;$i++){
						if ($fromstatsday_sday==sprintf("%02d",$i)) $selected="selected";
						else	$selected="";
						echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
					}
					?>	
				</select>
				<select name="fromstatsmonth_sday" class="form_input_select">
				<?php 	$year_actual = date("Y");  	
					for ($i=$year_actual;$i >= $year_actual-1;$i--)
					{		   
						if ($year_actual==$i){
							$monthnumber = date("n")-1; // Month number without lead 0.
						}else{
							$monthnumber=11;
						}		   
						for ($j=$monthnumber;$j>=0;$j--){	
							$month_formated = sprintf("%02d",$j+1);
							if ($fromstatsmonth_sday=="$i-$month_formated") $selected="selected";
							else $selected="";
							echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
						}
					}
				?>
				</select>
				</td><td class="fontstyle_searchoptions">&nbsp;&nbsp;
				<input type="checkbox" name="today" value="true" <?php  if ($today){ ?>checked<?php }?>> 
				<?php echo gettext("To");?>  :
				<select name="tostatsday_sday" class="form_input_select">
				<?php  
					for ($i=1;$i<=31;$i++){
						if ($tostatsday_sday==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}
						echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
					}
				?>						
				</select>
				<select name="tostatsmonth_sday" class="form_input_select">
				<?php 	$year_actual = date("Y");  	
					for ($i=$year_actual;$i >= $year_actual-1;$i--)
					{		   
						if ($year_actual==$i){
							$monthnumber = date("n")-1; // Month number without lead 0.
						}else{
							$monthnumber=11;
						}		   
						for ($j=$monthnumber;$j>=0;$j--){	
							$month_formated = sprintf("%02d",$j+1);
							if ($tostatsmonth_sday=="$i-$month_formated") $selected="selected";
							else	$selected="";
							echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
						}
					}
				?>
				</select>
				</td></tr>
				</table>
			</td>
		</tr>
		<tr>
    		<td align="left" class="bgcolor_002">
				<input type="radio" name="Period" value="month_older_rad" <?php  if ($Period =="month_older_rad"){ ?>checked="checked" <?php  } ?>>
				<font class="fontstyle_003"><?php echo gettext("Select calls older than");?></font>
			</td>
  			<td align="left" class="bgcolor_003">
				<table  border="0" cellspacing="0" cellpadding="0" width="100%">
				<tr><td class="fontstyle_searchoptions">&nbsp;&nbsp;
				<select name="month_earlier" class="form_input_select">
					<?php
						for ($i=3;$i<=12;$i++){
							if ($month_earlier == $i){$selected="selected";}else{$selected="";}
							echo '<option value="'.$i."\"$selected>".$i.' Months</option>';
						}
					?>
				</select>
				</td></tr></table>
  			</td>
		</tr>
		<tr>
			<td class="bgcolor_002" align="left">			
				<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("DESTINATION");?></font>
			</td>				
			<td class="bgcolor_003" align="left">
			<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="dst" value="<?php echo htmlspecialchars(stripslashes($dst), ENT_QUOTES, 'UTF-8'); ?>" class="form_input_text"></td>
				<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="dsttype" value="1" <?php if((!isset($dsttype))||($dsttype==1)){?>checked<?php }?>><?php echo gettext("Exact");?></td>
				<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="dsttype" value="2" <?php if($dsttype==2){?>checked<?php }?>><?php echo gettext("Begins with");?></td>
				<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="dsttype" value="3" <?php if($dsttype==3){?>checked<?php }?>><?php echo gettext("Contains");?></td>
				<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="dsttype" value="4" <?php if($dsttype==4){?>checked<?php }?>><?php echo gettext("Ends with");?></td>
				</tr>
			</table></td>
		</tr>			
		<tr>
			<td align="left" class="bgcolor_004">					
				<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("SOURCE");?></font>
			</td>				
			<td class="bgcolor_005" align="left">
			<table width="100%" border="0" cellspacing="0" cellpadding="0" >
			<tr><td>&nbsp;&nbsp;<INPUT TYPE="text" NAME="src" value="<?php echo htmlspecialchars(stripslashes($src), ENT_QUOTES, 'UTF-8'); ?>" class="form_input_text"></td>
			<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="srctype" value="1" <?php if((!isset($srctype))||($srctype==1)){?>checked<?php }?>><?php echo gettext("Exact");?></td>
			<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="srctype" value="2" <?php if($srctype==2){?>checked<?php }?>><?php echo gettext("Begins with");?></td>
			<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="srctype" value="3" <?php if($srctype==3){?>checked<?php }?>><?php echo gettext("Contains");?></td>
			<td class="fontstyle_searchoptions" align="center" ><input type="radio" NAME="srctype" value="4" <?php if($srctype==4){?>checked<?php }?>><?php echo gettext("Ends with");?></td>
			</tr></table></td>
		</tr>
		
		
		<!-- Select Option : to show just the Answered Calls or all calls, Result type, currencies... -->
		<tr>
		  <td class="bgcolor_002" align="left" ><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("OPTIONS");?></font></td>
		  <td class="bgcolor_003" align="center"><div align="left">
		  
		  <table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td width="20%"  class="fontstyle_searchoptions">
					<?php echo gettext("SHOW");?> :  						
			   </td>
			   <td width="80%"  class="fontstyle_searchoptions">
					<?php echo gettext("Answered Calls");?>  
					<input name="terminatecauseid" type="radio" value="ANSWER" <?php if((!isset($terminatecauseid))||($terminatecauseid=="ANSWER")){?>checked<?php }?> /> 
					<?php echo gettext("All Calls");?>  
					<input name="terminatecauseid" type="radio" value="ALL" <?php if($terminatecauseid=="ALL"){?>checked<?php }?>/>
				</td>
			</tr>
			<tr class="bgcolor_005">
				<td  class="fontstyle_searchoptions">
					<?php echo gettext("RESULT");?> : 
			   </td>
			   <td  class="fontstyle_searchoptions">
					<?php echo gettext("mins");?><input type="radio" NAME="resulttype" value="min" <?php if((!isset($resulttype))||($resulttype=="min")){?>checked<?php }?>> - <?php echo gettext("secs")?> <input type="radio" NAME="resulttype" value="sec" <?php if($resulttype=="sec"){?>checked<?php }?>>
				</td>
			</tr>
			<tr>
				<td  class="fontstyle_searchoptions">
					<?php echo gettext("CURRENCY");?> :
				</td>
				<td  class="fontstyle_searchoptions">
					<select NAME="choose_currency" size="1" class="form_input_select">
						<?php
							foreach($currencies_list as $key => $cur_value) {
						?>
							<option value='<?php echo $key ?>' <?php if (($choose_currency==$key) || (!isset($choose_currency) && $key==strtoupper(BASE_CURRENCY))){?>selected<?php } ?>><?php echo $cur_value[1].' ('.$cur_value[2].')' ?>
							</option>
						<?php 	} ?>
					</select>
				</td>
			</tr>
			</table>
		</td>
		</tr>
		<!-- Select Option : to show just the Answered Calls or all calls, Result type, currencies... -->
		
		<tr>
			<td class="bgcolor_004" align="left" > </td>
			<td class="bgcolor_005" align="center" >
				<input type="image"  name="image16" align="top" border="0" src="<?php echo Images_Path;?>/button-search.gif"/>
			</td>
		</tr>
	</table>
</FORM>
</center>


<!-- ** ** ** ** ** Displaying the Archiving options ** ** ** ** ** -->
<center>
<form name="frm_archive" id="frm_archive">
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
				<option value="A2B_call_archiving.php" selected><?php echo gettext("Calls Archiving");?></option>
				<option value="A2B_data_archiving.php"><?php echo gettext("Customer Archiving");?></option>
				</select>
					</td>					
				</tr></table></td>
			</tr>			
		</tbody></table>
</form>
</center>


<!-- ** ** ** ** ** Displaying the Archiving message, calls list and Archive action button and link ** ** ** ** ** -->
<?php

if ($archive_message !== '') {
	print '<div align="center">'.htmlspecialchars($archive_message, ENT_QUOTES, 'UTF-8').'</div>';
}

if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

$list = $HD_Form -> perform_action($form_action);

if (!$invalid_filters && is_array($list) && count($list) > 0) {
	// Bind this button to this search, including when another browser tab searches later.
	$archive_token = bin2hex(random_bytes(16));
	if (!isset($_SESSION['call_archiving_searches'])) $_SESSION['call_archiving_searches'] = array();
	$_SESSION['call_archiving_searches'][$archive_token] = array('clause' => $HD_Form->FG_TABLE_CLAUSE, 'parameters' => $search_parameters, 'order' => $order, 'sens' => $sens);
	while (count($_SESSION['call_archiving_searches']) > 10) array_shift($_SESSION['call_archiving_searches']);
?>
<form method="post" action="<?php echo htmlspecialchars($PHP_SELF, ENT_QUOTES, 'UTF-8'); ?>" style="text-align:center">
	<input type="hidden" name="archive" value="true">
	<input type="hidden" name="archive_token" value="<?php echo $archive_token; ?>">
	<input type="submit" value="<?php echo gettext('Archiving All '); ?>" class="form_input_button" onclick="return confirm('This action will archive the data, Are you sure?');">
</form>
<?php
}

$HD_Form -> create_form ($form_action, $list, $id=null) ;

$smarty->display('footer.tpl');

