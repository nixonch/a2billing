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


include ("./lib/customer.defines.php");
include ("./lib/customer.module.access.php");
include ("./lib/Form/Class.FormHandler.inc.php");
include ("./form_data/FG_var_ratecard.inc");
include ("./lib/customer.smarty.php");


if (! has_rights (ACX_RATECARD)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}

getpost_ifset(array('letter', 'posted_search', 'filterprefix'));

$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();

if (isset($letter) && strlen($letter)==1) $HD_Form -> FG_TABLE_CLAUSE .= " AND (SUBSTRING(destination,1,1)='".strtolower($letter)."' OR SUBSTRING(destination,1,1)='".$letter."')"; // sort by first letter

$FG_LIMITE_DISPLAY=10;
if (isset($mydisplaylimit) && (is_numeric($mydisplaylimit) || ($mydisplaylimit=='ALL'))) {
	if ($mydisplaylimit=='ALL') {
		$FG_LIMITE_DISPLAY=5000;
	} else {
		$FG_LIMITE_DISPLAY=$mydisplaylimit;
	}
}

if ($id!="" || !is_null($id)) {
	$HD_Form -> FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form -> FG_EDITION_CLAUSE);
}

if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

if ( ($form_action == "list") &&  ($HD_Form->FG_FILTER_SEARCH_FORM) && ($posted_search == 1 ) && isset($mytariff_id) ) {
	$HD_Form->FG_TABLE_CLAUSE = "idtariffplan='$mytariff_id'";
}

$list = $HD_Form -> perform_action($form_action);


// #### HEADER SECTION
$smarty->display('main.tpl');


// #### HELP SECTION
if ($form_action == 'list') {
    echo $CC_help_ratecard.'';
}

 // #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

?>
<br/>
<center>
    <table width="80%" border=0 cellspacing=1 cellpadding=3 bgcolor="#000033" align="center">
        <tr>
       <td bgcolor="#EEEEEE" width="100%" valign="top" align="center" class="bb2">
	  <a href="A2B_entity_ratecard.php?form_action=list&letter="><?php echo gettext("NONE")?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=A"><?php if ($letter=="A") echo "<font class=\"blink\">A</font>"; else echo "A";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=B"><?php if ($letter=="B") echo "<font class=\"blink\">B</font>"; else echo "B";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=C"><?php if ($letter=="C") echo "<font class=\"blink\">C</font>"; else echo "C";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=D"><?php if ($letter=="D") echo "<font class=\"blink\">D</font>"; else echo "D";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=E"><?php if ($letter=="E") echo "<font class=\"blink\">E</font>"; else echo "E";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=F"><?php if ($letter=="F") echo "<font class=\"blink\">F</font>"; else echo "F";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=G"><?php if ($letter=="G") echo "<font class=\"blink\">G</font>"; else echo "G";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=H"><?php if ($letter=="H") echo "<font class=\"blink\">H</font>"; else echo "H";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=I"><?php if ($letter=="I") echo "<font class=\"blink\">I</font>"; else echo "I";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=J"><?php if ($letter=="J") echo "<font class=\"blink\">J</font>"; else echo "J";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=K"><?php if ($letter=="K") echo "<font class=\"blink\">K</font>"; else echo "K";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=L"><?php if ($letter=="L") echo "<font class=\"blink\">L</font>"; else echo "L";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=M"><?php if ($letter=="M") echo "<font class=\"blink\">M</font>"; else echo "M";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=N"><?php if ($letter=="N") echo "<font class=\"blink\">N</font>"; else echo "N";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=O"><?php if ($letter=="O") echo "<font class=\"blink\">O</font>"; else echo "O";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=P"><?php if ($letter=="P") echo "<font class=\"blink\">P</font>"; else echo "P";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=Q"><?php if ($letter=="Q") echo "<font class=\"blink\">Q</font>"; else echo "Q";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=R"><?php if ($letter=="R") echo "<font class=\"blink\">R</font>"; else echo "R";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=S"><?php if ($letter=="S") echo "<font class=\"blink\">S</font>"; else echo "S";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=T"><?php if ($letter=="T") echo "<font class=\"blink\">T</font>"; else echo "T";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=U"><?php if ($letter=="U") echo "<font class=\"blink\">U</font>"; else echo "U";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=V"><?php if ($letter=="V") echo "<font class=\"blink\">V</font>"; else echo "V";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=W"><?php if ($letter=="W") echo "<font class=\"blink\">W</font>"; else echo "W";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=X"><?php if ($letter=="X") echo "<font class=\"blink\">X</font>"; else echo "X";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=Y"><?php if ($letter=="Y") echo "<font class=\"blink\">Y</font>"; else echo "Y";?></a> - 
              <a href="A2B_entity_ratecard.php?form_action=list&letter=Z"><?php if ($letter=="Z") echo "<font class=\"blink\">Z</font>"; else echo "Z";?></a> 
       </td>
        </tr>
    </table>
</center>
<?php

$HD_Form -> create_form ($form_action, $list, $id=null) ;

// #### CREATE SEARCH FORM
if ($form_action == "list") {
	$HD_Form -> create_search_form();
}


// #### FOOTER SECTION
$smarty->display('footer.tpl');

