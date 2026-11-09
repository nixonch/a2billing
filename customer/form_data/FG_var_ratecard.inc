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



getpost_ifset(array('destination', 'dialprefix', 'rateinitial', 'posted', 'tariffplan'));

$cardid = $_SESSION['card_id'];
$margin = 1;
$DBHandle_max = DbConnect();
while ($cardid > 0) {
	$QUERY  = "SELECT id_diller, margin FROM cc_card WHERE id=$cardid LIMIT 1";
	$resmax = $DBHandle_max->Execute($QUERY);
	if ($resmax) {
		$row = $resmax->fetchRow();
		$cardid = $row[0];
		$margin = $margin * ($row[1] / 100 + 1);
	}
}

$HD_Form = new FormHandler("cc_callplan_lcr", "Rates");
$HD_Form -> FG_TABLE_ID = 'ratecard_id';


$HD_Form -> FG_DEBUG = 0;
$HD_Form -> FG_TABLE_DEFAULT_ORDER =  " SUBSTRING(destination,1,4),
					IF(CONCAT(dialprefix+0) = dialprefix, dialprefix, ''),
					IF(CONCAT(dialprefix+0) = dialprefix, cast(dialprefix as signed), -LENGTH(dialprefix))";
$HD_Form -> FG_TABLE_DEFAULT_SENS = "ASC";

$HD_Form -> FG_VIEW_TABLE_WITDH = "65%";
$HD_Form -> FG_FILTER_SEARCH_SESSION_NAME = 'entity_ratecard_selection';

//$HD_Form -> FG_TABLE_CLAUSE = "(tariffgroup_id = '".$_SESSION["tariff"]."' OR tariffname = '".$_SESSION["pr_login"]."') AND SUBSTRING(dialprefix,1,1) != '_' ";
$HD_Form -> FG_TABLE_CLAUSE = "(tariffgroup_id = '".$_SESSION["tariff"]."' OR tariffname = '".$_SESSION["pr_login"]."') AND CONCAT('',dialprefix * 1) = dialprefix AND startdate<= CURRENT_TIMESTAMP AND (stopdate > CURRENT_TIMESTAMP OR stopdate = 0) ";
$HD_Form -> SQL_GROUP = "GROUP BY dialprefix";

$HD_Form -> AddViewElement("<acronym title=\"DIALING PREFIX\">".gettext("PREFIX")."</acronym>", "dialprefix", "20%", "center", "sort", "");
$HD_Form -> AddViewElement(gettext("DESTINATION"), "destination", "60%", "center", "sort", "40");
$HD_Form -> AddViewElement("<acronym title=\"".gettext("SELLING RATE")."\">".gettext("SR")."</acronym>", "ratein", "20%", "center", "sort");

//$margin = strtr($margin,",",".");

//$HD_Form -> FieldViewElement ('dialprefix, destination, FORMAT(cc_card.margintotal*MIN(rateinitial),5) as rateinitial');
//$HD_Form -> FieldViewElement ("dialprefix, destination, IF((MIN(rateinitial)=MAX(rateinitial) AND MAX(rateinitial)=100),'not in service',CONCAT(FORMAT(cc_card.margintotal*MIN(rateinitial),3),' - ',FORMAT(cc_card.margintotal*MAX(rateinitial),3))) as rateinitial");
//$HD_Form -> FieldViewElement ("dialprefix, destination, IF(MIN(rateinitial)=MAX(rateinitial),IF(MAX(rateinitial)=100,'not in service',FORMAT(cc_card.margintotal*rateinitial,3)),CONCAT(FORMAT(cc_card.margintotal*MIN(rateinitial),3),' - ',FORMAT(cc_card.margintotal*MAX(rateinitial),3))) as rateinitial");
$HD_Form -> FieldViewElement ("dialprefix, destination, IF(MIN(rateinitial)=MAX(rateinitial),IF(MAX(rateinitial)=100,'not in service',FORMAT($margin*rateinitial,3)),CONCAT(FORMAT($margin*MIN(rateinitial),3),' - ',FORMAT($margin*MAX(rateinitial),3))) as ratein");


$HD_Form -> CV_NO_FIELDS  = gettext("THERE IS NO RATECARD CREATED!");
$HD_Form -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE = false;
$HD_Form -> CV_TEXT_TITLE_ABOVE_TABLE = '';
$HD_Form -> CV_DISPLAY_FILTER_ABOVE_TABLE = false;

$HD_Form -> FG_FILTER_APPLY = true;
$HD_Form -> FG_FILTERFIELDNAME = gettext('PREFIX');
$HD_Form -> FG_FILTER_FORM_ACTION = 'list';

if (isset($filterprefix)  &&  (strlen($filterprefix)>0)) {
	if (strlen($HD_Form -> FG_TABLE_CLAUSE) > 0) {
		$HD_Form -> FG_TABLE_CLAUSE.=" AND ";
	}
	$HD_Form -> FG_TABLE_CLAUSE.="dialprefix like '$filterprefix%'";
}
