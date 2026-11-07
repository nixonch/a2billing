<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This file is part of A2Billing (http://www.a2billing.net/)
 *
 * A2Billing, Commercial Open Source Telecom Billing platform,
 * powered by Star2billing S.L. <http://www.star2billing.com/>
 *
 * @author      Nixon Mitin <nixon@lighttele.com>
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
include ("lib/customer.smarty.php");


if (!has_rights(ACX_SIP_IAX) && !has_rights(ACX_DID)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}


getpost_ifset(array('startnumber'));

    do {
	$QUERY = "SELECT extmin, extmax, extquantity, id FROM cc_card WHERE username = '" . $_SESSION["pr_login"] . "' AND uipass = '" . $_SESSION["pr_password"] . "'";
	$DBHandle_max = DbConnect();
	$resmax = $DBHandle_max->Execute($QUERY);
	if ($resmax) {
		$row = $resmax->fetchRow();
		$extmin = $row[0];
		$extmax = $row[1];
		$extquantity = $row[2];
		$cardid = $row[3];
		$extstart=1+$extmin;
	}
	$extlen = strlen($extmax);
	$QUERY = "SELECT regexten AS field, id_cc_card, -id FROM cc_sip_buddies
			LEFT JOIN cc_card_concat bb ON bb.concat_card_id = id_cc_card
			LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = $cardid ) AS v ON bb.concat_id = v.concat_id
			WHERE (id_cc_card = $cardid OR v.concat_id IS NOT NULL) AND external = 0
		UNION ALL
		  SELECT ext_num AS field, id_cc_card, id FROM cc_fax
			LEFT JOIN cc_card_concat bb ON bb.concat_card_id = cc_fax.id_cc_card
			LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = $cardid ) AS v ON bb.concat_id = v.concat_id
			WHERE id_cc_card = $cardid OR v.concat_id IS NOT NULL
		ORDER BY field";
	$inst_table = new Table();
	$resmax = $inst_table->SQLExec($DBHandle_max, $QUERY);
	if ($resmax) {
		for ($k=0;$k<count($resmax);$k++) {
			if ($resmax[$k][1] == $cardid && $extstart == 1+$extmin) {
				$extstart = (is_numeric($resmax[$k][0]) && $extmin <= $resmax[$k][0] && $resmax[$k][0] <= $extmax) ? floor($resmax[$k][0]/100)*100+1 : $extstart;
				break;
			}
		}
		$exten_include = array();
		for ($k=0;$k<count($resmax);$k++) {
			if (is_numeric($resmax[$k][0])) {
				if ($resmax[$k][0] == $extstart)	$extstart++;
				$exten_include[$resmax[$k][2]] = $resmax[$k][0];
				if ($startnumber == $resmax[$k][0])	unset($startnumber);
//echo $resmax[$k][2]." = ".$exten_include[$resmax[$k][2]]."<br/>";
			}
		}
	}
// ADD VIRTUAL FAX
	if (strlen($startnumber)>0  && is_numeric($startnumber)) {
		if ( $startnumber == '0' || $startnumber < $extmin || $extmax < $startnumber )	break;
		$instance_sub_table = new Table('cc_fax');
		$QUERY = "SELECT count(*) FROM cc_fax WHERE id_cc_card='".$_SESSION["card_id"]."'";
		$result = $instance_sub_table->SQLExec($DBHandle_max, $QUERY, 1);
// CHECK IF THE AMOUNT OF FAX NUMBERS IS LESS THAN THE LIMIT
		if ($result[0][0] < $A2B->config["webcustomerui"]['limit_callerid']){
			$QUERY = "INSERT INTO cc_fax (id_cc_card, ext_num, email) VALUES ('".$_SESSION["card_id"]."', '".$startnumber."', '".$_SESSION["email"]."')";
			$result = $instance_sub_table->SQLExec($DBHandle_max, $QUERY, 0);
			if ($result) {
				// Remove addition parameters before they can enter refreshProcessed.
				$list_params = array('form_action' => 'list');
				$request_params = array_merge($_GET, $_POST);
				foreach (array('current_page', 'order', 'sens', 'filterprefix', 'atmenu', 'stitle', 'section', 'popup_select', 'popup_formname', 'popup_fieldname') as $param) {
					if (isset($request_params[$param]) && is_scalar($request_params[$param])) {
						$list_params[$param] = $request_params[$param];
					}
				}
				Header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($list_params));
				die();
			}
		}
		unset($startnumber);
		continue;
	}
	break;
} while (true);

include ("./form_data/FG_var_fax.inc");

$HD_Form -> setDBHandler (DbConnect());
$HD_Form -> init();

if ($id!="" || !is_null($id)){
	$HD_Form -> FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form -> FG_EDITION_CLAUSE);
}

if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

$list = $HD_Form -> perform_action($form_action);


// #### HEADER SECTION
$smarty->display( 'main.tpl');

if ($form_action == "list") {
    // My code for Creating two functionalities in a page
    $HD_Form -> create_toppage ("ask-add");
?>
<script language="JavaScript" src="./javascript/sipiax.js"></script>
<center>
<?php
    
	if (isset($update_msg) && strlen($update_msg)>0) echo $update_msg;
	
    $count_fax = is_array($list) ? sizeof($list) : 0;
    if ($count_fax < $A2B->config["webcustomerui"]['limit_callerid']) {

?>
	   <table align="center"  border="0" width="40%" class="bgcolor_006">
		<form name="theForm">
		<tr class="bgcolor_001" >

		<td align="center" valign="top">
				<?php gettext("FAX EXTENSION :");?>
				<input class="form_input_text" id="fax1" name="startnumber" oninput="allowOnlyDigits(this);" size="15" maxlength="<?php echo $extlen;?>" value="<?php echo $extstart;?>">
			</td>
			<td align="center" valign="middle">
				<input name="quantity" type=hidden value="1">
				<input class="form_input_button" type="button" value="<?php echo gettext("ADD NEW VIRTUAL FAX");?>" onClick="openURL('<?php echo $_SERVER['PHP_SELF']?>?startnumber=',<?php echo $extquantity;?>,'Вы привысили лимит на <?php echo $extquantity;?> номеров.\nМаксимальное количество <?php echo $row[2];?>.\nУ Вас уже создано <?php echo $row[2]-$extquantity;?> номеров.\nУменьшите количество новых номеров.','<?php echo gettext("Both fields must be numeric\\nand greater than zero");?>',<?php echo $extmin;?>,<?php echo $extmax;?>)">
		</td>
        </tr>
		</form>
      </table>
	  <br>
	<?php
	} else { 
	
	?>
		<table align="center"  border="0" width="70%" class="bgcolor_006">
			<tr class="bgcolor_001" >
				<td align="center" valign="middle">
					<b><i> <?php  echo gettext("You are not allowed to add more fax numbers.");
					echo "<br/>";
					 echo gettext("Remove one if you are willing to use an other fax number.");?> </i> </b>
					<br/>
					<?php echo gettext("Max fax lines");?> &nbsp;:&nbsp; <?php echo $A2B->config["webcustomerui"]['limit_callerid'] ?>
	  			</td>
     		 </tr>
	 	</table>
	<?php 	 
	}
}
?>
</center>
<?php

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);

$HD_Form -> create_form ($form_action, $list, $id=null) ;


// #### FOOTER SECTION
$smarty->display( 'footer.tpl');


