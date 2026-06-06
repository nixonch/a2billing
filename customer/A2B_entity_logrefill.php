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
include ("lib/Form/Class.FormHandler.inc.php");
include ("form_data/FG_var_logrefill.inc");
include ("lib/customer.smarty.php");

if (!has_rights(ACX_PAYMENT_HISTORY)) {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error?c=accessdenied");
	die();
}


$HD_Form->setDBHandler(DbConnect());

$HD_Form->init();

if ($id != "" || !is_null($id)) {
	$HD_Form->FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form->FG_EDITION_CLAUSE);
}

if (!isset ($form_action))
	$form_action = "list"; //ask-add
if (!isset ($action))
	$action = $form_action;

if ($message != "addsuccess")
	$list = $HD_Form->perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($form_action=='list' && !($popup_select>=1))
	echo $CC_help_view_refill;

if ($form_action == "list") {
	$HD_Form->create_search_form();
}

// #### TOP SECTION PAGE
$HD_Form->create_toppage($form_action);

if ($message != "addsuccess") {
	$HD_Form->create_form($form_action, $list, $id = null);
?>	<script type="text/javascript">
	document.getElementById('<?php if ($form_action=='ask-add') echo 'credit'; else echo 'filterprefix';?>')?.focus();
	</script>
<?php
}

if ($form_action == "list" && $message != "addsuccess" && has_rights (ACX_DISTRIBUTION)) {

	$table = new Table();

	$temp = date("Y-m-01");
	$now_month = date("m");
	$nb_month = 11;
	$datetime = new DateTime($temp);
	$datetime->modify("-$nb_month month");
	$checkdate = $datetime->format("Y-m-d");

	$undoQuery = "";
	for ($i = 0; $i < count($list_refill_type); $i++) {
		$undoQuery .= ", SUM(IF(refill_type='$i',IF(card_id=".$_SESSION['card_id']." OR card_id<0,credit,-credit),0)) A$i";
	}
	$QUERY = "SELECT DATE_FORMAT(`date`,'%c') A".$undoQuery." FROM ".$QUERY." WHERE ";
	if (strlen($HD_Form -> FG_TABLE_CLAUSE)>0) $QUERY .= $HD_Form -> FG_TABLE_CLAUSE." AND";
	$QUERY .= " `date` >= TIMESTAMP('$checkdate') AND `date` <= CURRENT_TIMESTAMP";
	$QUERY .= " GROUP BY MONTH(`date`) ORDER BY `date` DESC";
	$result_refills_unmonth = $table->SQLExec($HD_Form->DBHandle, $QUERY);
	$result_column = $result_refills = array ();
	$j = 0;
	for ($i = 0; $i <= $nb_month; $i++) {
		if (sizeof($result_refills_unmonth) > $j) {
			$val = array_intersect_key($result_refills_unmonth[$j],array('0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8));
			if ($now_month > $i)
				$month_test = intval($now_month - $i);
			else
				$month_test = $now_month + (12 - $i);
			if ($val[0] == $month_test) {
				$result_refills[] = $val;
				$j++;
			} else
				$result_refills[] = 0;
		} else {
			$result_refills[] = 0;
		}
	}
	for ($i = 1; $i <= count($list_refill_type); $i++) {
		$result_column[$i] = 0;
		foreach ($result_refills as $row) {
			$result_column[$i] += $row[$i];
		}
	}
	if (array_sum($result_column) != 0) {
		$list_month = Constants :: getMonth();
?>
	<br/><center>
	<table border="1" cellpadding="4" cellspacing="2" class="bgcolor_017" >		
		<tr>
			<td>		
				<table border="2" cellpadding="3" cellspacing="5" class="bgcolor_018">		
					<tr class="form_head">
						<td>&nbsp;</td><?php
						for ($i = 1; $i <= count($list_refill_type); $i++) {
							if ($result_column[$i]!=0) {
						?><td width="20%" align="center" nowrap="nowrap" class="tableBodyRight" style="padding: 2px;"><strong><?php echo $list_refill_type[$i-1][0];?></strong></td><?php
							}
						} ?>
					</tr>
					<?php for($i=0;$i<=$nb_month;$i++){
						if($now_month>$i) $month_display=intval($now_month-$i);
						else $month_display = $now_month + (12-$i)
						?>
					<tr>
						<td valign="top" align="center" class="tableBody" bgcolor="#c3c0ec"><b><?php echo $list_month[$month_display][0]; ?></b></td><?php
						for ($k = 1; $k <= count($list_refill_type); $k++) {
							if ($result_column[$k]!=0) {
						?><td valign="top" align="center" class="tableBody" nowrap="nowrap" bgcolor="#ecd9c0"><b>&nbsp;<?php echo display_money_nocur($result_refills[$i][$k]); ?>&nbsp;</b></td><?php
							}
						} ?>
					</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
	</table></center>
		<br></br>
<?php
	}
}

if ($message == "addsuccess") {
?>
<TABLE cellSpacing=2 class="toppage_actionfinish">
    <TR>
	<TD class="form_head"> 
	  <?php echo gettext("INSERT NEW Refill")." ".$HD_Form -> FG_INTRO_TEXT_ADITION;?>
	</TD>
    </TR><TR>
	<TD width="516" valign="top" class="tdstyle_001"> <br>
	    <div align="center"><strong>
		<?php echo gettext("Transaction was completed succesfully")?><br>
	    </strong></div>
	    <br>
	</TD>
    </TR>
</TABLE>
<br>
<center>
<form runat="server">
    <div>
        <input id="button1" onclick="window.parent.postMessage({action:'closeAlert'},'*')" class="form_input_button" type="button" value="" />
    </div>
</form>
<script type="text/javascript">
    window.parent.postMessage({action:'refreshInside'},'*');
    objbutton=document.getElementById('button1');
    objbutton.focus();
    timeleft=10;
    function buttontimer(){
	timeleft--;
	if(timeleft==0) {
	    objbutton.click();
	}
        objbutton.value = '<?php echo gettext("Close Window")?> ('+timeleft+')';
    }
    buttontimer();
    setInterval(function() {buttontimer()}, 1000);
</script>

<?php
}

// #### FOOTER SECTION
$smarty->display('footer.tpl');
