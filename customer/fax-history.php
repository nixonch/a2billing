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
include ("lib/customer.smarty.php");


if (! has_rights (ACX_CALL_HISTORY)) { 
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");	   
	die();
}

getpost_ifset(array('posted', 'Period', 'frommonth', 'fromstatsmonth', 'tomonth', 'tostatsmonth', 'fromday', 'fromstatsday_sday', 'fromstatsmonth_sday', 'today', 'tostatsday_sday', 'tostatsmonth_sday', 'calleridtype', 'phonenumbertype', 'sourcetype', 'clidtype', 'channel', 'resulttype', 'stitle', 'atmenu', 'current_page', 'order', 'sens', 'callerid', 'phonenumber', 'src', 'clid', 'choose_calltype', 'download', 'file'));


if (($download == "file") && $file) {
	
	if (strpos($file, '/') !== false) exit;
	
	$value_de = base64_decode ( $file );
	if (!CustomerInput::audioName($value_de)) CustomerInput::fail('file');
	$parts = pathinfo($value_de);
	$value = $parts['filename'];
	$handle = DbConnect();
	$handle -> Execute("SET @@session.time_zone=@@global.time_zone");
	$instance_table = new Table();
	$QUERY = "SELECT YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime), cc_card.username FROM cc_call LEFT JOIN cc_card ON cc_card.id=card_id WHERE uniqueid LIKE ? ORDER BY cc_call.id DESC LIMIT 1";
	$result = $instance_table -> SQLExec ($handle, $QUERY, 1, 0, array($value));
	if (is_array($result) && count($result)>0) {
	    $dl_full = FAX_PATH . "/" . $result[0][3] . "/" . $result[0][0] . "/" . $result[0][1] . "/" . $result[0][2] . "/" . $value_de;
	    $dl_name = $value_de;
	
	    if (! file_exists ( $dl_full )) {
		echo gettext ( "ERROR: Cannot download file " . $dl_name . ", it does not exist.<br>" );
		exit ();
	    }
	
	    header ( "Content-Type: application/octet-stream" );
	    header ( "Content-Disposition: attachment; filename=$dl_name" );
	    header ( "Content-Length: " . filesize ( $dl_full ) );
	    header ( "Accept-Ranges: bytes" );
	    header ( "Pragma: no-cache" );
	    header ( "Expires: 0" );
	    header ( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	    header ( "Content-transfer-encoding: binary" );
	
	    @readfile ( $dl_full );
	}
	exit ();
}


$QUERY = "SELECT username, credit, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, lastuse, activated, status, currency FROM cc_card WHERE username = '".$_SESSION["pr_login"]."' AND uipass = '".$_SESSION["pr_password"]."'";

$DBHandle_max = DbConnect();
$numrow = 0;
$resmax = $DBHandle_max -> Execute($QUERY);
if ($resmax)
	$numrow = $resmax -> RecordCount();

if ($numrow == 0) exit();
$customer_info =$resmax -> fetchRow();

if ($customer_info[14] != "1" && $customer_info[14] != "8") {
	Header("HTTP/1.0 401 Unauthorized");
	Header("Location: PP_error.php?c=accessdenied");
	die();
}

$customer = $_SESSION["card_id"];


$dialstatus_list = Constants::getDialStatusList();

if (!isset ($current_page) || ($current_page == "")) {
	$current_page=0;
}

$FG_DEBUG = 0;
$FG_TABLE_NAME="cc_call t1";

// THIS VARIABLE DEFINE THE COLOR OF THE HEAD TABLE
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#FFFFFF";
$FG_TABLE_ALTERNATE_ROW_COLOR[] = "#F2F8FF";

$yesno = array();
$yesno["1"] = array( "Yes", "1");
$yesno["0"] = array( "No", "0");

// 0 = NORMAL CALL ; 1 = VOIP CALL (SIP/IAX) ; 2= DIDCALL + TRUNK ; 3 = VOIP CALL DID ; 4 = CALLBACK call
$list_faxstatus = array();
$list_faxstatus["-1"] = array( gettext("ALL CALLS"), "-1");
$list_faxstatus["0"]  = array( gettext("FAILED"), "0");
$list_faxstatus["1"]  = array( gettext("SUCCESS"), "1");

$DBHandle  = DbConnect();

$FG_TABLE_DEFAULT_ORDER = "t1.stoptime";
$FG_TABLE_DEFAULT_SENS = "DESC";

$FG_TABLE_COL = array();
$FG_TABLE_COL[]=array (gettext("Date"), "stoptime", "19%", "center", "SORT", "22", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("CallerID"), "src", "11%", "center", "SORT", "40");
$FG_TABLE_COL[]=array (gettext("PhoneNumber"), "calledstation", "11%", "center", "SORT", "30", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Status"), "faxstatus", "9%", "center", "SORT",  "", "list", $list_faxstatus);
$FG_TABLE_COL[]=array (gettext("Remote FaxID"), "remotefaxid", "10%", "center", "SORT", "30", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Pages"), "faxpages", "5%", "center", "SORT", "30", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Bitrate"), "faxbitrate", "8%", "center", "SORT", "30", "", "", "", "", "", "");
$FG_TABLE_COL[]=array (gettext("Resolution"), "faxresolution", "8%", "center", "SORT", "30", "", "", "", "", "", "");

$FG_COL_QUERY = "t1.stoptime, IF(t1.src_exten IS NULL, t1.src, t1.src_exten), IF(t1.card_id='$customer' AND t1.calledexten IS NOT NULL, t1.calledexten, t1.calledstation), t1.faxstatus, t1.remotefaxid, IF(t1.faxpages>0,t1.faxpages,''), IF(t1.faxbitrate>0,t1.faxbitrate,''), IF(t1.faxresolution>0,t1.faxresolution,'')";

$FG_TABLE_COL [] = array ('<span class="liens">' . gettext("PDF") . "</span>", "uniqueid", "5%", "center", "", "30", "", "", "", "", "", "linkonfaxfile_customer");
$FG_COL_QUERY .= ', t1.uniqueid';

$FG_LIMITE_DISPLAY = 25;
$FG_NB_TABLE_COL = count($FG_TABLE_COL);
$FG_EDITION = true;
$FG_TOTAL_TABLE_COL = $FG_NB_TABLE_COL;
if ($FG_DELETION || $FG_EDITION) $FG_TOTAL_TABLE_COL++;
$FG_HTML_TABLE_TITLE = " - ".gettext("FAX Logs")." - ";
$FG_HTML_TABLE_WIDTH = "98%";
$FG_ACTION_SIZE_COLUMN = "1%";


$instance_table = new Table($FG_TABLE_NAME, $FG_COL_QUERY);


if ( is_null ($order) || is_null($sens) ){
	$order = $FG_TABLE_DEFAULT_ORDER;
	$sens  = $FG_TABLE_DEFAULT_SENS;
}

if ($posted==1) {
	$SQLcmd = '';
	$SQLcmd = do_field($SQLcmd, 'src', 'source');
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src', false, 1);
	$SQLcmd = do_field($SQLcmd, 'callerid', 'src_exten', true, 2);
	$SQLcmd = do_field($SQLcmd, 'phonenumber', 'calledstation');
}

$date_clause = '';

normalize_day_of_month($fromstatsday_sday, $fromstatsmonth_sday, 1);
normalize_day_of_month($tostatsday_sday, $tostatsmonth_sday, 1);
if ($fromday && isset($fromstatsday_sday) && isset($fromstatsmonth_sday)) $date_clause.=" AND t1.stoptime >= ('$fromstatsmonth_sday-$fromstatsday_sday')";
if ($today && isset($tostatsday_sday) && isset($tostatsmonth_sday)) $date_clause.=" AND t1.stoptime <= ('$tostatsmonth_sday-".sprintf("%02d",intval($tostatsday_sday)/*+1*/)." 23:59:59')";



if (strpos($SQLcmd, 'WHERE') > 0) {
	$FG_TABLE_CLAUSE = substr($SQLcmd,6).$date_clause;
} elseif (strpos($date_clause, 'AND') > 0) {
	$FG_TABLE_CLAUSE = substr($date_clause,5);
}


if (!isset ($FG_TABLE_CLAUSE) || strlen($FG_TABLE_CLAUSE)==0) {
	$cc_yearmonth = sprintf("%04d-%02d-%02d",date("Y"),date("n"),1);
	$FG_TABLE_CLAUSE=" t1.stoptime >= ('$cc_yearmonth')";
}


if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
$FG_TABLE_CLAUSE.="(t1.card_called='$customer' AND faxstatus IS NOT NULL)";

if (!isset($choose_calltype)) $choose_calltype = 1;
if ($choose_calltype != - 1) {
	if (strlen($FG_TABLE_CLAUSE)>0) $FG_TABLE_CLAUSE.=" AND ";
	$FG_TABLE_CLAUSE .= " t1.faxstatus=$choose_calltype ";
}

if (!isset($resulttype)) $resulttype="min";

if (!$nodisplay) {
	$list = $instance_table -> Get_list ($DBHandle, $FG_TABLE_CLAUSE, $order, $sens, null, null, $FG_LIMITE_DISPLAY, $current_page*$FG_LIMITE_DISPLAY);
}
$_SESSION["pr_sql_export"] = "SELECT $FG_COL_QUERY FROM $FG_TABLE_NAME WHERE $FG_TABLE_CLAUSE";

$QUERY = "SELECT DATE(t1.stoptime) AS day, sum(t1.sessiontime) AS calltime, sum(t1.sessionbill) AS cost, count(*) as nbcall FROM $FG_TABLE_NAME WHERE ".$FG_TABLE_CLAUSE." GROUP BY day ORDER BY day"; //extract(DAY from calldate)

if (!$nodisplay) {
	$res = $DBHandle -> Execute($QUERY);
	if ($res) {
		$num = $res -> RecordCount();
		for($i=0;$i<$num;$i++) {				
			$list_total_day [] =$res -> fetchRow();
		}
	}
	
	if ($FG_DEBUG == 3) echo "<br>Clause : $FG_TABLE_CLAUSE";
	$nb_record = $instance_table -> Table_count ($DBHandle, $FG_TABLE_CLAUSE);
	if ($FG_DEBUG >= 1) var_dump ($list);
}

if ($nb_record<=$FG_LIMITE_DISPLAY) { 
	$nb_record_max=1;
} else { 
	if ($nb_record % $FG_LIMITE_DISPLAY == 0) {
		$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY));
	} else {
		$nb_record_max=(intval($nb_record/$FG_LIMITE_DISPLAY)+1);
	}	
}


$smarty->display( 'main.tpl');

// #### HELP SECTION
echo $CC_help_fax_customer;

?>

<!-- ** ** ** ** ** Part for the research ** ** ** ** ** -->
	<center>
	<FORM METHOD=POST ACTION="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php echo $current_page?>">
		<INPUT TYPE="hidden" NAME="posted" value=1>
		<INPUT TYPE="hidden" NAME="current_page" value=0>
		<table class="callhistory_maintable" align="center">
			
			<tr>
        		<td align="left" class="bgcolor_002"> &nbsp;
					<font class="fontstyle_003"><?php echo gettext("DATE");?></b></font>
				</td>
      			<td align="left" class="bgcolor_003">
					<table width="100%" border="0" cellspacing="0" cellpadding="0" >
					<tr><td class="fontstyle_searchoptions">&nbsp;
	  				<input type="checkbox" name="fromday" value="true" <?php  if ($fromday){ ?>checked<?php }?>> <?php echo gettext("FROM");?> :
					<select name="fromstatsday_sday" class="form_input_select">
						<?php  
							for ($i=1;$i<=31;$i++){
								if ($fromstatsday_sday==sprintf("%02d",$i)){$selected="selected";}else{$selected="";}
								echo '<option value="'.sprintf("%02d",$i)."\"$selected>".sprintf("%02d",$i).'</option>';
							}
						?>	
					</select>
				 	<select name="fromstatsmonth_sday" class="form_input_select">
					<?php 	
						$year_actual = date("Y");
						for ($i=$year_actual;$i >= $year_actual-1;$i--) {		   
							$monthname = array( gettext("JANUARY"), gettext("FEBRUARY"), gettext("MARCH"), gettext("APRIL"), gettext("MAY"), gettext("JUNE"), gettext("JULY"), gettext("AUGUST"), gettext("SEPTEMBER"), gettext("OCTOBER"), gettext("NOVEMBER"), gettext("DECEMBER"));
							if ($year_actual==$i){
								$monthnumber = date("n")-1; // Month number without lead 0.
							}else{
								$monthnumber=11;
							}		   
							for ($j=$monthnumber;$j>=0;$j--){	
								$month_formated = sprintf("%02d",$j+1);
							   	if ($fromstatsmonth_sday=="$i-$month_formated"){$selected="selected";}else{$selected="";}
								echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
							}
						}
					?>
					</select>
					</td><td class="fontstyle_searchoptions">&nbsp;&nbsp;
					<input type="checkbox" name="today" value="true" <?php  if ($today){ ?>checked<?php }?>> <?php echo gettext("TO");?> :
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
							   $monthname = array( gettext("JANUARY"), gettext("FEBRUARY"), gettext("MARCH"), gettext("APRIL"), gettext("MAY"), gettext("JUNE"), gettext("JULY"), gettext("AUGUST"), gettext("SEPTEMBER"), gettext("OCTOBER"), gettext("NOVEMBER"), gettext("DECEMBER"));
							   if ($year_actual==$i){
									$monthnumber = date("n")-1; // Month number without lead 0.
							   }else{
									$monthnumber=11;
							   }		   
							   for ($j=$monthnumber;$j>=0;$j--){	
										$month_formated = sprintf("%02d",$j+1);
							   			if ($tostatsmonth_sday=="$i-$month_formated"){$selected="selected";}else{$selected="";}
										echo "<OPTION value=\"$i-$month_formated\" $selected> $monthname[$j]-$i </option>";				
							   }
						}
					?>
					</select>
					</td></tr></table>
	  			</td>
    		</tr>
			<tr>
				<td  align="left" class="bgcolor_004" nowrap>
					<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("CALLERID");?>&nbsp;&nbsp;</font>
				</td>
				<td  align="left" class="bgcolor_005">
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td class="fontstyle_searchoptions" nowrap>&nbsp;&nbsp;<INPUT TYPE="text" NAME="callerid" value="<?php echo $callerid?>" class="form_input_text">&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="1" <?php if((!isset($calleridtype))||($calleridtype==1)){?>checked<?php }?>><?php echo gettext("Exact");?>&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="2" <?php if($calleridtype==2){?>checked<?php }?>><?php echo gettext("Begins with")?>&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="3" <?php if($calleridtype==3){?>checked<?php }?>><?php echo gettext("Contains");?>&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="calleridtype" value="4" <?php if($calleridtype==4){?>checked<?php }?>><?php echo gettext("Ends with");?>&nbsp;</td>
				</tr></table></td>
			</tr>		
			<tr>
				<td  align="left" class="bgcolor_002" nowrap>
					<font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("PHONENUMBER");?>&nbsp;&nbsp;</font>
				</td>
				<td  align="left" class="bgcolor_003" nowrap>
				<table width="100%" border="0" cellspacing="0" cellpadding="0">
				<tr><td class="fontstyle_searchoptions" nowrap>&nbsp;&nbsp;<INPUT TYPE="text" NAME="phonenumber" value="<?php echo $phonenumber?>" class="form_input_text">&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="1" <?php if((!isset($phonenumbertype))||($phonenumbertype==1)){?>checked<?php }?>><?php echo gettext("Exact");?>&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="2" <?php if($phonenumbertype==2){?>checked<?php }?>><?php echo gettext("Begins with")?>&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="3" <?php if($phonenumbertype==3){?>checked<?php }?>><?php echo gettext("Contains");?>&nbsp;</td>
				<td  align="center" class="fontstyle_searchoptions" nowrap><input type="radio" NAME="phonenumbertype" value="4" <?php if($phonenumbertype==4){?>checked<?php }?>><?php echo gettext("Ends with");?>&nbsp;</td>
				</tr></table></td>
			</tr>		
			<!-- Select Calltype: -->
			<tr>
			  <td class="bgcolor_004" align="left" nowrap><font class="fontstyle_003">&nbsp;&nbsp;<?php echo gettext("FAX STATUS"); ?>&nbsp;&nbsp;</font></td>
			  <td class="bgcolor_005" align="center">
			  <table width="100%" border="0" cellspacing="0" cellpadding="0">
								<tr>
					<td  class="fontstyle_searchoptions" nowrap>&nbsp;
					<select NAME="choose_calltype" size="1" class="form_input_select" >
							<?php
								foreach($list_faxstatus as $key => $cur_value) {
							?>
								<option value='<?php echo $cur_value[1] ?>' <?php if ($choose_calltype==$cur_value[1]){?>selected<?php } ?>><?php echo gettext($cur_value[0]) ?>
								</option>
							<?php 	} ?>
						</select>
					</td>
				</tr>				
				</table>
			  </td>
			  </tr>
			<tr>
        		<td class="bgcolor_002" align="left" > </td>
				<td class="bgcolor_003" align="center" >
					<input class="form_input_button" value=" <?php echo gettext("Search");?> " type="submit">
	  			</td>
    		</tr>
	</table>
	</FORM>
</center>


<!-- ** ** ** ** ** Part to display the CDR ** ** ** ** ** -->
<center><?php echo gettext("Number of Faxes");?> : <?php if (is_array($list) && count($list)>0){ echo $nb_record . "<h3></h3>";}else{echo "0";}?></center>
     <table width="<?php echo $FG_HTML_TABLE_WIDTH?>" border="0" align="center" cellpadding="0" cellspacing="0">
		<TR bgcolor="#ffffff"> 
          <TD class="callhistory_td11"> 
            <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR> 
                  <TD><SPAN style="COLOR: #ffffff; FONT-SIZE: 11px"><B><?php echo $FG_HTML_TABLE_TITLE?></B></SPAN></TD>
                </TR>
            </TABLE></TD>
        </TR>
        <TR> 
          <TD> <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR class="bgcolor_008">
				  <TD width="<?php echo $FG_ACTION_SIZE_COLUMN?>" align="center" class="tableBodyRight" style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px"></TD>					
				  
                  <?php 
				  	if (is_array($list) && count($list)>0) {
					
				  	for($i=0;$i<$FG_NB_TABLE_COL;$i++) {
					?>
	                  <TD width="<?php echo $FG_TABLE_COL[$i][2]?>" align=middle class="tableBody" style="PADDING-BOTTOM: 2px; PADDING-LEFT: 2px; PADDING-RIGHT: 2px; PADDING-TOP: 2px" nowrap>
	                    <center><strong> 
	                    <?php  if (mb_strtoupper($FG_TABLE_COL[$i][4])=="SORT"){?>
	                    <a href="<?php  echo $PHP_SELF."?s=1&t=0&stitle=$stitle&atmenu=$atmenu&current_page=$current_page&order=".$FG_TABLE_COL[$i][1]."&sens="; if ($sens=="ASC"){echo"DESC";}else{echo"ASC";} 
						echo "&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&src=$src&clid=$clid&choose_calltype=$choose_calltype";?>"> 
	                    <span class="liens"><?php  } ?>
	                    <?php echo $FG_TABLE_COL[$i][0]?> 
	                    <?php if ($order==$FG_TABLE_COL[$i][1] && $sens=="ASC"){?>
	                    &nbsp;<img src="<?php echo Images_Path_Main ?>/icon_up_12x12.GIF" width="12" height="12" border="0"> 
	                    <?php }elseif ($order==$FG_TABLE_COL[$i][1] && $sens=="DESC"){?>
	                    &nbsp;<img src="<?php echo Images_Path_Main ?>/icon_down_12x12.GIF" width="12" height="12" border="0"> 
	                    <?php }?>
	                    <?php  if (mb_strtoupper($FG_TABLE_COL[$i][4])=="SORT") {?>
	                    </span></a> 
	                    <?php }?>
	                    </strong></center></TD>
				   <?php } ?>
				   		
                </TR>
                <TR> 
                  <TD bgColor="#e1e1e1" colSpan=<?php echo $FG_TOTAL_TABLE_COL?> height="1">
                </TR>
				<?php
				  	 $ligne_number=0;					 
				  	 foreach ($list as $recordset){
						 $ligne_number++;
				?>
				
               		 <TR bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>"  onMouseOver="bgColor='#C4FFD7'" onMouseOut="bgColor='<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>'"> 
						<TD align="<?php echo $FG_TABLE_COL[$i][3]?>" class="tableBody"><?php  echo $ligne_number+$current_page*$FG_LIMITE_DISPLAY.".&nbsp;"; ?></TD>
							 
				  		<?php for($i=0;$i<$FG_NB_TABLE_COL;$i++){ ?>
						<?php 				
							if ($FG_TABLE_COL[$i][6]=="lie"){
									$instance_sub_table = new Table($FG_TABLE_COL[$i][7], $FG_TABLE_COL[$i][8]);
									$sub_clause = str_replace("%id", $recordset[$i], $FG_TABLE_COL[$i][9]);																																	
									$select_list = $instance_sub_table -> Get_list ($DBHandle, $sub_clause, null, null, null, null, null, null);
									
									
									$field_list_sun = preg_split('/,/',$FG_TABLE_COL[$i][8]);
									$record_display = $FG_TABLE_COL[$i][10];
									
									for ($l=1;$l<=count($field_list_sun);$l++){										
										$record_display = str_replace("%$l", $select_list[0][$l-1], $record_display);	
									}
								
							}elseif ($FG_TABLE_COL[$i][6]=="list"){
									$select_list = $FG_TABLE_COL[$i][7];
									$record_display = $select_list[$recordset[$i]][0];
							
							}else{
									$record_display = $recordset[$i];
							}
							
							if ( is_numeric($FG_TABLE_COL[$i][5]) && (strlen($record_display) > $FG_TABLE_COL[$i][5]) ) {
								$record_display = substr($record_display, 0, $FG_TABLE_COL[$i][5]-3)."";  
							}
							
							
				 		 ?>
                 		 <TD vAlign=top align="<?php echo $FG_TABLE_COL[$i][3]?>" class=tableBody><?php 
                 		 if (isset ($FG_TABLE_COL[$i][11]) && strlen($FG_TABLE_COL[$i][11])>1){
						 	call_user_func($FG_TABLE_COL[$i][11], $record_display);
						 }else{
						 	echo stripslashes($record_display);
						 }						 
						 ?></TD>
				 		 <?php  } ?>
                  
			</TR>
				<?php
					 }//foreach ($list as $recordset)
					 if ($ligne_number < $FG_LIMITE_DISPLAY)  $ligne_number_end=$ligne_number +2;
					 while ($ligne_number < $ligne_number_end){
					 	$ligne_number++;
				?>
					<TR bgcolor="<?php echo $FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>"> 
				  		<?php for($i=0;$i<$FG_NB_TABLE_COL;$i++){ 
				 		 ?>
                 		 <TD vAlign=top class=tableBody>&nbsp;</TD>
				 		 <?php  } ?>
                 		 <TD align="center" vAlign=top class=tableBodyRight>&nbsp;</TD>				
					</TR>
									
				<?php					 
					 } //END_WHILE
					 
				  }else{
				  		echo gettext("No data found !!!");
				  }//end_if
				 ?>
            </TABLE></td>
        </tr>
        <TR bgcolor="#ffffff"> 
          <TD bgColor=#ADBEDE height=16 style="PADDING-LEFT: 5px; PADDING-RIGHT: 3px"> 
	    <TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR> 
                  <TD align="right"><SPAN style="COLOR: #ffffff; FONT-SIZE: 11px"><B> 
                    <?php if ($current_page>0){?>
                    <img src="<?php echo Images_Path_Main ?>/fleche-g.gif" width="5" height="10"> <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page-1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&src=$src&clid=$clid&choose_calltype=$choose_calltype";?>"> 
                    <?php echo gettext("PREVIOUS");?> </a> -
                    <?php }?>
                    <?php echo ($current_page+1);?> / <?php  echo $nb_record_max;?> 
                    <?php if ($current_page<$nb_record_max-1){?>
                    - <a href="<?php echo $PHP_SELF?>?s=1&t=0&order=<?php echo $order?>&sens=<?php echo $sens?>&current_page=<?php  echo ($current_page+1)?><?php  if (!is_null($letter) && ($letter!="")){ echo "&letter=$letter";} 
					echo "&posted=$posted&Period=$Period&frommonth=$frommonth&fromstatsmonth=$fromstatsmonth&tomonth=$tomonth&tostatsmonth=$tostatsmonth&fromday=$fromday&fromstatsday_sday=$fromstatsday_sday&fromstatsmonth_sday=$fromstatsmonth_sday&today=$today&tostatsday_sday=$tostatsday_sday&tostatsmonth_sday=$tostatsmonth_sday&calleridtype=$calleridtype&phonenumbertype=$phonenumbertype&sourcetype=$sourcetype&clidtype=$clidtype&channel=$channel&resulttype=$resulttype&callerid=$callerid&phonenumber=$phonenumber&src=$src&clid=$clid&choose_calltype=$choose_calltype";?>"> 
                    <?php echo gettext("NEXT");?> </a> <img src="<?php echo Images_Path_Main ?>/fleche-d.gif" width="5" height="10">
                    </B></SPAN> 
                    <?php }?>
                  </TD>
		</TR>
            </TABLE></TD>
        </TR>
      </table>

<!-- ** ** ** ** ** Part to display the GRAPHIC ** ** ** ** ** -->
<br>

<?php

$smarty->display( 'footer.tpl');


