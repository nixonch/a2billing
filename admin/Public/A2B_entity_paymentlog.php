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
include ("./form_data/FG_var_paymentlog.inc");
include ("../lib/admin.smarty.php");

if (! has_rights (ACX_BILLING)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}
$DBHandle = DbConnect();
$HD_Form -> setDBHandler ($DBHandle);
$HD_Form -> init();

if ($id!="" || !is_null($id)){
	$HD_Form -> FG_EDITION_CLAUSE = str_replace("%id", "$id", $HD_Form -> FG_EDITION_CLAUSE);
}

if (!isset($form_action))  $form_action="list"; //ask-add
if (!isset($action)) $action = $form_action;

$list = $HD_Form -> perform_action($form_action);

// #### HEADER SECTION
$smarty->display('main.tpl');

// #### HELP SECTION
if ($form_action=='list') 
	echo $CC_help_payment_log;

// #### TOP SECTION PAGE
$HD_Form -> create_toppage ($form_action);


if ($form_action=='list' && !($popup_select>=1)) {

?>
<FORM METHOD=POST name="myForm" ACTION="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $current_page?>">
	<INPUT TYPE="hidden" NAME="posted" value=1>
	<INPUT TYPE="hidden" NAME="current_page" value=0>	
		<table class="bar-status" width="85%" border="0" cellspacing="1" cellpadding="2" align="center">
			<tbody>
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
					</td></tr></table>
	  			</td>
    		</tr>
			<tr>
				<td class="bgcolor_002" align="left">			
					<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("STATUS");?></font>
				</td>				
				<td class="bgcolor_003" align="left">
				<select name="status" style="width:100px;">
				<option value="0" <?php if ($status == 0) echo "selected"?>>New</option>
				<option value="1" <?php if ($status == 1) echo "selected"?>>Proceed</option>
				<option value="2" <?php if ($status == 2) echo "selected"?>>In Progress</option>
				</select>
				</td>
			</tr>			
			<tr> 
                                <td class="bgcolor_004" align="left"> 
                                        <font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("PAYMENT METHOD");?></font> 
                                </td> 
<?php 
        $pmtable = new Table(); 
        $result = $pmtable -> SQLExec($DBHandle, "select id, payment_method from `cc_payment_methods`"); 
        //print_r($result); 
?> 
                                <td class="bgcolor_005" align="left"> 
                                <select name="pmethod" style="width:100px;"> 
                                <option value="all" <?php if ($pmethod == 'all') echo "selected";?>>All</option> 
<?php 
        foreach ($result as $row){ 
?> 
                                <option value="<?php echo $row['payment_method'];?>" <?php if ($pmethod == $row['payment_method']) echo "selected"?>><?php echo $row['payment_method'];?></option> 
<?php 
        } 

?> 
                                </select> 
                                </td> 
                        </tr> 
                        <tr> 
                        <td class="bgcolor_002" align="left" > </td> 

                                <td class="bgcolor_003" align="center" > 
                                        <input type="image"  name="image16" align="top" border="0" src="<?php echo Images_Path;?>/button-search.gif" /> 

                                </td> 
                </tr>
		</tbody></table>
</FORM>
<?php
}
$HD_Form -> create_form ($form_action, $list, $id=null) ;

$FG_TABLE_ALTERNATE_ROW_COLOR [] = "#FFFFFF";
$FG_TABLE_ALTERNATE_ROW_COLOR [] = "#F2F8FF";

$QUERY = "SELECT paymentmethod, count(*), sum(amount) FROM cc_epayment_log"
	.($HD_Form -> FG_TABLE_CLAUSE?" WHERE ".$HD_Form -> FG_TABLE_CLAUSE:"")
	." GROUP BY paymentmethod ORDER BY paymentmethod ASC";

$pmtable = new Table();
$list_total_day = $pmtable -> SQLExec($DBHandle, $QUERY);

if (is_array ( $list_total_day ) && count ( $list_total_day ) > 0) {

        $mmax = 0;
        $totalcall == 0;
        $totalminutes = 0;
        $totalsuccess = 0;
        $totalfail = 0;
        foreach ( $list_total_day as $data ) {
                if ($mmax < $data [2])
                        $mmax = $data [2];
                $totalsum += $data [2];
                $totalcount += $data [1];
        }
        $max_fail = 0;

?>
<br>
<center>
<table border="0" cellspacing="0" cellpadding="0" width="700px">
	<tbody>
		<tr>
			<td bgcolor="#000000">
			<table border="0" cellspacing="1" cellpadding="2" width="100%">
				<tbody>
					<tr>
						<td align="center" class="bgcolor_019"></td>
						<td class="bgcolor_020" align="center" colspan="3"><font
							class="fontstyle_003"><?php echo gettext ( "PAYMENT SUMMARY" ); ?></font></td>
					</tr>
					<tr class="bgcolor_019">
						<td align="center" class="bgcolor_020"><font class="fontstyle_003"><?php echo gettext ( "PAYMENT METHOD" ); ?></font></td>
						<td align="center"><font class="fontstyle_003"><?php echo gettext ( "AMOUNT" );	?></font></td>
						<td align="center"><font class="fontstyle_003"><?php echo gettext ( "GRAPHIC" ); ?></font></td>
						<td align="center"><font class="fontstyle_003"><?php echo gettext ( "COUNT" ); ?></font></td>

						<!-- LOOP -->
	<?php
	$i = 0;
	$j = 0;
	foreach ( $list_total_day as $data ) {
		$i = ($i + 1) % 2;
		
		if ($mmax > 0)
			$widthbar = intval ( ($data [2] / $mmax) * 150 );
		?>
		</tr>
					<tr>
						<td align="right" class="sidenav" nowrap="nowrap"><font
							class="fontstyle_003"><?php
		echo $data [0]?></font></td>
						<td bgcolor="<?php
		echo $FG_TABLE_ALTERNATE_ROW_COLOR [$i]?>"
							align="right" nowrap="nowrap"><font class="fontstyle_006"><?php
		echo display_2dec($data[2])?> </font></td>
						<td bgcolor="<?php
		echo $FG_TABLE_ALTERNATE_ROW_COLOR [$i]?>"
							align="left" nowrap="nowrap" width="<?php
		echo $widthbar + 40?>">
						<table cellspacing="0" cellpadding="0">
							<tbody>
								<tr>
									<td bgcolor="#e22424"><img
										src="<?php
		echo Images_Path;
		?>/spacer.gif"
										width="<?php echo $widthbar?>" height="6"></td>
								</tr>
							</tbody>
						</table>
						</td>
						<td bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR [$i]?>"
							align="right" nowrap="nowrap"><font class="fontstyle_006"><?php echo $data [1]?></font></td>
				     <?php
					$j ++;
				}
				
				?>                   	
				</tr>
					<tr bgcolor="bgcolor_019">
						<td align="right" nowrap="nowrap"><font class="fontstyle_003"><?php
						echo gettext ( "TOTAL" );
						?></font></td>
						<td align="center" nowrap="nowrap" colspan="2"><font
							class="fontstyle_003"><?php echo display_2dec($totalsum)?> </font></td>
						<td align="center" nowrap="nowrap"><font class="fontstyle_003"><?php echo $totalcount?></font></td>
					</tr>
					<!-- END TOTAL -->

				</tbody>
			</table>
			<!-- END ARRAY GLOBAL //--></td>
		</tr>
	</tbody>
</table>
</center>
<br>
<?php } else { ?>
<center>
<h3><?php echo gettext ( "No calls in your selection");?>.</h3>
<?php  } ?>
</center>

<?php


// #### FOOTER SECTION
$smarty->display('footer.tpl');


