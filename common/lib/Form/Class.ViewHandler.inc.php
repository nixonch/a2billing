<?php

// Hack to allow PHP4 use stripos() that is only supported in PHP5
if (!function_exists("stripos")) {
	function stripos($str,$needle) {
		return strpos(strtolower($str),strtolower($needle));
	}
}


// ******************** END IF $topviewer *******************************

getpost_ifset(array('stitle', 'letter', 'current_page', 'popup_select', 'message'));

$processed = $this->getProcessed();
?>
<script type="text/JavaScript">
function openURLFilter(theLINK)
{
	selInd = document.theFormFilter.choose_list.selectedIndex;
	if(selInd==0){return false;}
	goURL = document.theFormFilter.choose_list.options[selInd].value;
	this.location.href = theLINK + goURL;
}
</script>

<div style="" align="center">
<table width="<?php echo $this->FG_VIEW_TABLE_WITDH; ?>" border="0" cellpadding="0" cellspacing="0">
<?php
if( !$processed['popup_select'] && ($this->FG_LIST_ADDING_BUTTON1 || $this->FG_LIST_ADDING_BUTTON2) && (strlen($this -> FG_LIST_ADDING_BUTTON_MSG1)>0 || !is_array($list))) {
	?>
	<tr><td align="right">
		<?php if($this->FG_LIST_ADDING_BUTTON1) {?>
			<a href="<?php echo $this -> FG_LIST_ADDING_BUTTON_LINK1	?>"><?php if ($this -> FG_LIST_ADDING_BUTTON_MSG1) echo $this -> FG_LIST_ADDING_BUTTON_MSG1."&nbsp;&nbsp;"?><img src="<?php echo $this -> FG_LIST_ADDING_BUTTON_IMG1?>" border="0" title="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT1?>" alt="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT1?>"></a>
		<?php  } //END IF ?>
		<?php if($this->FG_LIST_ADDING_BUTTON2) {?>
			<a href="<?php echo $this -> FG_LIST_ADDING_BUTTON_LINK2	?>"><?php if ($this -> FG_LIST_ADDING_BUTTON_MSG2) echo "&nbsp;".$this -> FG_LIST_ADDING_BUTTON_MSG2."&nbsp;&nbsp;"?><img src="<?php echo $this -> FG_LIST_ADDING_BUTTON_IMG2?>" border="0" title="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT2?>" alt="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT2?>"></a>
		<?php  } //END IF ?>
	</td></tr>
	<?php
} //END IF

if ($processed['popup_select'] == 4 && $processed['message'] == 'delsuccess') {
?>
<script type="text/javascript">
    window.parent.postMessage({action:'refreshInside'},'*');
    window.parent.postMessage({action:'closeAlert'},'*')
</script>
<?php
} else if ((count($list)>0) && is_array($list)){
	$ligne_number=0;
?>

	<tr><td align="center">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <?php  IF ($this -> CV_DISPLAY_LINE_TITLE_ABOVE_TABLE){ ?>
		<TR>
		<TD class="tdstyle_002"><span>
			<b><?php echo $this -> CV_TEXT_TITLE_ABOVE_TABLE?></b></span>
		</TD>
		</TR>
	   <?php  } //END IF ?>
	  <?php  IF ($this -> CV_DO_ARCHIVE_ALL){ ?>
		<TR>
		<td class="viewhandler_filter_td1">
			<FORM NAME="theFormFilter" action="<?php echo $PHP_SELF?>">
			<input type="hidden" name="atmenu" value="<?php echo $processed['atmenu']?>">
			<input type="hidden" name="popup_select" value="<?php echo $processed['popup_select']?>">
			<input type="hidden" name="popup_formname" value="<?php echo $processed['popup_formname']?>">
			<input type="hidden" name="popup_fieldname" value="<?php echo $processed['popup_fieldname']?>">
			<input type="hidden" name="archive" value="true">
			<input type="SUBMIT" value="<?php echo gettext("Archiving All ");?>" class="form_input_button" onclick="return confirm('This action will archive the data, Are you sure?');"/>
			</FORM>
		</td>
		</TR>
	   <?php  } //END IF ?>
	   <?php  IF ($this -> CV_DISPLAY_FILTER_ABOVE_TABLE){ ?>
		<TR><FORM NAME="theFormFilter">
			<input type="hidden" name="popup_select" value="<?php echo $processed['popup_select']?>">
			<input type="hidden" name="popup_formname" value="<?php echo $processed['popup_formname']?>">
			<input type="hidden" name="popup_fieldname" value="<?php echo $processed['popup_fieldname']?>">
		<TD class="tdstyle_002"><SPAN>
				<SELECT name="choose_list" size="1" class="form_input_select" style="width: 185px;" onchange="openURLFilter('<?php echo $PHP_SELF.$this->CV_FILTER_ABOVE_TABLE_PARAM?>')">

					<OPTION><?php echo gettext("Sort");?></OPTION>

					<?php
						// TODO not sure for what should be used that, because exist already a filter.
						if (!isset($list_site)) $list_site = $list;
						foreach ($list_site as $recordset){
					?>
					<OPTION class=input value='<?php echo $recordset[0]?>'  <?php if ($recordset[0]==$site_id) echo "selected";?>><?php echo $recordset[1]?></OPTION>
					<?php 	 }
					?>
				</SELECT>
		</SPAN></TD>
		</FORM>
		</TR>
		<?php  } //END IF ?>

		<tr>
		<td class="viewhandler_table2_td3">
		    <table border="0" cellpadding="0" cellspacing="0" width="100%">
		    <tr>
		    <td nowrap><span class="viewhandler_span2"> - <?php echo $this->CV_TITLE_TEXT ?>  - </span>
			<span class="viewhandler_span1"> <?php echo $this -> FG_NB_RECORD.' '.gettext("Records"); ?></span>
		<?php if($this->FG_LIST_ADDING_BUTTON1 &&  strlen($this -> FG_LIST_ADDING_BUTTON_MSG1)==0) {?>
			<a href="<?php echo $this -> FG_LIST_ADDING_BUTTON_LINK1;?>"><img src="<?php echo $this -> FG_LIST_ADDING_BUTTON_IMG1?>" valign="bottom" border="0" title="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT1?>" alt="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT1?>"></a>
		<?php if($this->FG_LIST_ADDING_BUTTON2) {?>
			<a href="<?php echo $this -> FG_LIST_ADDING_BUTTON_LINK2;?>"><img src="<?php echo $this -> FG_LIST_ADDING_BUTTON_IMG2?>" valign="bottom" border="0" title="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT2?>" alt="<?php echo $this->FG_LIST_ADDING_BUTTON_ALT2?>"></a>
		<?php }} ?>
		    </td>
		    <td align="right" nowrap>
		    <span class="viewhandler_span2">
			<?php
			$c_url = $PHP_SELF.'?current_page=%s';
			foreach ($processed as $key => $val) {
				if (!in_array($key,array('current_page','id')) && $val!='') {
					$c_url .= '&'.$key.'='.$val;
				}
			}
			$c_url .= $this-> CV_FOLLOWPARAMETERS;
			$this -> printPages($this -> CV_CURRENT_PAGE+1, $this -> FG_NB_RECORD_MAX, $c_url) ;
			?>
		    </span>
		    </td>
		    </tr>
		    </table>
		</td>
		</tr>

		<?php
		// Add filter  FG_FILTER_APPLY , FG_FILTERFIELD and FG_FILTER_FORM_ACTION
		if ($this -> FG_FILTER_APPLY || $this -> FG_FILTER_APPLY2){
		?>
		<tr><FORM NAME="theFormFilter" action="<?php echo $PHP_SELF?>">
            <?php
            	foreach ($processed as $key => $val) {
            		if (!in_array($key,array('current_page','id','form_action','filterfield','filterfield2','filterprefix','filterprefix2')) && $val!='') {
            	?>
                   <input type="hidden" name="<?php echo $key?>" value="<?php echo $val?>">
             	<?php  
             		}
            	}
             ?>
			<INPUT type="hidden" name="form_action"	value="<?php echo $this->FG_FILTER_FORM_ACTION ?>">
            <td class="viewhandler_filter_td1">
			<?php if ($this -> FG_FILTER_APPLY){ ?>

				<font class="viewhandler_filter_on"><?php echo gettext("FILTER ON ");?> <?php mb_internal_encoding('UTF-8');?><?php echo mb_strtoupper($this->FG_FILTERFIELDNAME)?> :</font>
				<INPUT type="text" id="filterprefix" name="filterprefix" value="<?php if(!empty($processed['filterprefix'])) echo $processed['filterprefix']; ?>" class="form_input_text">
				<?php if ($this->FG_FILTERFIELD!='') {?>
				<INPUT type="hidden" name="filterfield"	value="<?php echo $this->FG_FILTERFIELD?>">
				<?php
				}
				if ($this -> FG_FILTERTYPE == 'INPUT'){
					// IT S OK
				}elseif ($this -> FG_FILTERTYPE == 'POPUPVALUE'){
				?>
					<a href="#" onclick="window.open('<?php echo $this->FG_FILTERPOPUP[0]?>popup_formname=theFormFilter&popup_fieldname=filterprefix' <?php echo $this->FG_FILTERPOPUP[1]?>);"><img src="<?php echo Images_Path_Main;?>/icon_arrow_orange.gif" valign="bottom"/></a>
				<?php
				}

			}

			if ($this -> FG_FILTER_APPLY2){ ?>
				&nbsp;&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;&nbsp;
				<font class="viewhandler_filter_on"><?php echo gettext("FILTER ON ");?> <?php echo $this->FG_FILTERFIELDNAME2?> :</font>
				<INPUT type="text" name="filterprefix2" value="<?php if(!empty($processed['filterprefix2'])) echo $processed['filterprefix2']; ?>" class="form_input_text">&nbsp;
				<?php if ($this->FG_FILTERFIELD2!='') {?>
				<INPUT type="hidden" name="filterfield2"	value="<?php echo $this->FG_FILTERFIELD2?>">
				<?php
				}
				if ($this -> FG_FILTERTYPE2 == 'INPUT') {
					// IT S OK
				} elseif ($this -> FG_FILTERTYPE2 == 'POPUPVALUE') {
				?>
					<a href="#" onclick="window.open('<?php echo $this->FG_FILTERPOPUP2[0]?>popup_formname=theFormFilter&popup_fieldname=filterprefix2' <?php echo $this->FG_FILTERPOPUP2[1]?>);"><img src="<?php echo Images_Path_Main;?>/icon_arrow_orange.gif" valign="bottom"/></a>
				<?php
				}
			}
			?>
				<input type="SUBMIT" value="<?php echo gettext("APPLY FILTER ");?>" class="form_input_button"/>
			</td></FORM>
        </tr>
		<?php } ?>

        <TR>
          <TD id="main-list">
			<TABLE border="0" cellPadding="2" cellSpacing="2" width="100%">
				<TR class="form_head">
				<?php
					  for($i=0;$i<$this->FG_NB_TABLE_COL;$i++){
				?>
				 <td class="tableBody" style="padding: 2px;" align="center" width="<?php echo $this->FG_TABLE_COL[$i][2]?>" >
						<?php  if (strtoupper($this->FG_TABLE_COL[$i][4])=="SORT"){?>
						<a href="<?php echo $PHP_SELF."?stitle=$stitle&atmenu=$atmenu&current_page=$current_page&letter=".$processed["letter"]."&popup_select=".$processed["popup_select"]."&order=".$this->FG_TABLE_COL[$i][1]."&sens="; if ($this->FG_SENS=="ASC"){echo"DESC";}else{echo"ASC";} echo $this-> CV_FOLLOWPARAMETERS;?>">
						<font color="#FFF999"><?php  } ?>
						<?php echo $this->FG_TABLE_COL[$i][0]?>
						<?php if ($this->FG_ORDER==$this->FG_TABLE_COL[$i][1] && $this->FG_SENS=="ASC"){?>
						&nbsp;<img src="<?php echo Images_Path_Main;?>/icon_up_12x12.GIF" border="0">
						<?php }elseif ($this->FG_ORDER==$this->FG_TABLE_COL[$i][1] && $this->FG_SENS=="DESC"){?>
						&nbsp;<img src="<?php echo Images_Path_Main;?>/icon_down_12x12.GIF" border="0">
						<?php }?>
						<?php  if (strtoupper($this->FG_TABLE_COL[$i][4])=="SORT"){?>
						</font></a>
						<?php }?>
						</TD>
			   <?php }
				 if ($this->FG_DELETION || $this->FG_INFO || $this->FG_EDITION || $this -> FG_OTHER_BUTTON1 || $this -> FG_OTHER_BUTTON2 || $this -> FG_OTHER_BUTTON3 || $this -> FG_OTHER_BUTTON4 ){ ?>
					 <td width="<?php echo $this->FG_ACTION_SIZE_COLUMN?>" align="center" class="tableBody" ><strong> <?php echo gettext("ACTION");?></strong> </td>
			   <?php } ?>
                </TR>
		<?php
			/**********************   START BUILDING THE TABLE WITH BROWSING VALUES ************************/
			for ($ligne_number=0;$ligne_number<count($list);$ligne_number++){
		?>

				<TR bgcolor="<?php echo $this->FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>"  onmouseover="bgColor='#FFDEA6'" onMouseOut="bgColor='<?php echo $this->FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>'">
		  		<?php
				$k=0;
				for($i=0;$i<$this->FG_NB_TABLE_COL;$i++) {
					/**********************   select the mode to browse define the column value : lie, list, value, eval.... ************************/
					$record_clear = null;
					if ($this->FG_TABLE_COL[$i][6]=="lie") {

						$instance_sub_table = new Table($this->FG_TABLE_COL[$i][7], $this->FG_TABLE_COL[$i][8]);
						$sub_clause = str_replace("%id", $list[$ligne_number][$i-$k], $this->FG_TABLE_COL[$i][9]);

						$select_list = $instance_sub_table -> Get_list ($this->DBHandle, $sub_clause, null, null, null, null, null, null, null, 10);
						$record_display = $this->FG_TABLE_COL[$i][10];

						for ($l=count($select_list[0])+1;$l>=1;$l--) {
							$record_display = str_replace("%$l", $select_list[0][$l-1], $record_display);
						}

					} elseif($this->FG_TABLE_COL[$i][6]=="lie_link") {

						$instance_sub_table = new Table($this->FG_TABLE_COL[$i][7], $this->FG_TABLE_COL[$i][8]);
						$sub_clause = str_replace("%id", $list[$ligne_number][$i-$k], $this->FG_TABLE_COL[$i][9]);
						$select_list = $instance_sub_table -> Get_list ($this->DBHandle, $sub_clause, null, null, null, null, null, null, null, 10);
						if (is_array($select_list)) {
							$record_display = $this->FG_TABLE_COL[$i][10];
							$link = $this->FG_TABLE_COL[$i][12] . "?";
							if (stripos($this->FG_TABLE_COL[$i][12],'form_action')===false)
								$link .= "form_action=ask-edit&";
							$link.= "id=".$select_list[0][1];
							for ($l=count($select_list[0])+1;$l>=1;$l--){
								$record_display = str_replace("%$l", $select_list[0][$l-1], $record_display);
							}
							$record_display = "<a href=\"$link\">$record_display</a>";
						} elseif ($list[$ligne_number][$i-$k] != 0) {
							$record_display = "<a href='{$this->FG_TABLE_COL[$i][12]}'>{$list[$ligne_number][$i-$k]}</a>";
							$string_to_eval = $this->FG_TABLE_COL[$i][7];
							for ($l=count($list[$ligne_number])-1;$l>=0;$l--){
								if ($list[$ligne_number][$l]=='')
									$list[$ligne_number][$l]=0;
								$record_display = str_replace("%$l", $list[$ligne_number][$l], $record_display);
							}
						} else {
							$record_display = "";
						}

					} elseif($this->FG_TABLE_COL[$i][6]=="link2cust") {

						$sub_clause = str_replace("%id", $list[$ligne_number][$i-$k], $this->FG_TABLE_COL[$i][8]);
						$instance_sub_table = new Table($this->FG_TABLE_COL[$i][7], $sub_clause);
						$sub_clause = str_replace("%id", $list[$ligne_number][$i-$k], $this->FG_TABLE_COL[$i][9]);
						$select_list = $instance_sub_table -> Get_list ($this->DBHandle, $sub_clause, null, null, null, null, null, null, null, 10);
						if (is_array($select_list)) {
							$record_clear = $this->FG_TABLE_COL[$i][10];
							$link_display = $this->FG_TABLE_COL[$i][12];
							if (count($select_list[0])>=5 && strlen($select_list[0][3])>0) {
							    $select_list[0][4] = str_replace($select_list[0][3], "<b>".$select_list[0][3]."</b>", $select_list[0][4]);
							}
							for ($l=count($select_list[0]);$l>=1;$l--){
								if ($l==1 && $list[$ligne_number][$i-$k] >= 0)
								    $record_clear = str_replace("%1", "%1</a>", $record_clear);
								$record_clear = str_replace("%$l", $select_list[0][$l-1], $record_clear);
								$link_display = str_replace("%$l", $select_list[0][$l-1], $link_display);
							}
							if ($list[$ligne_number][$i-$k] >= 0)
								$record_display = "<a href=\"".$link_display."\">".$record_clear;
							else	$record_display = str_replace($select_list[0][0], "<b>".$select_list[0][0]."</b>", $record_clear);
						} elseif ($list[$ligne_number][$i-$k] != 0) {
							$record_display = $this->FG_TABLE_COL[$i][13];
						} else {
							$record_display = "";
						}

					} elseif ($this->FG_TABLE_COL[$i][6]=="eval") {

						$string_to_eval = $this->FG_TABLE_COL[$i][7]; // %4-%3
						for ($l=count($list[$ligne_number])-1;$l>=0;$l--){
							if ($list[$ligne_number][$l]=='') $list[$ligne_number][$l]=0;
							$string_to_eval = str_replace("%$l", $list[$ligne_number][$l], $string_to_eval);
						}
						@eval("\$eval_res = $string_to_eval;");
						$record_display = $eval_res;

					} elseif ($this->FG_TABLE_COL[$i][6]=="list") {

						$select_list = $this->FG_TABLE_COL[$i][7];
						$record_display = $select_list[$list[$ligne_number][$i-$k]][0];

					} elseif ($this->FG_TABLE_COL[$i][6]=="list-conf") {

						$select_list = $this->FG_TABLE_COL[$i][7];
						$key_config =  $list[$ligne_number][$i-$k + 3];
						$record_display = $select_list[$key_config][0];

					} elseif ($this->FG_TABLE_COL[$i][6]=="value") {

						$record_display = $this->FG_TABLE_COL[$i][7];
						$k++;

					} elseif ($this->FG_TABLE_COL[$i][6]=="timeleft") {

						$record_display = $list[$ligne_number][$i-$k];
						if ($record_display < 0) {
							$record_display = "<span style='color: red'>00:00</span>";
						} else {
							$string_to_eval = $this->FG_TABLE_COL[$i][7]; // %4-%3
							for ($l=count($list[$ligne_number])-1;$l>=0;$l--) {
								if ($list[$ligne_number][$l]=='') $list[$ligne_number][$l]=0;
								$string_to_eval = str_replace("%$l", $list[$ligne_number][$l], $string_to_eval);
							}
							eval("\$eval_res = $string_to_eval;");

							$record_display ="
								<span style='color: green' id='timer{$ligne_number}_out'></span>
								<div style='display: none' id='timer{$ligne_number}_inp'>{$record_display}</div>
								<script type='text/javascript'>
								    function timer{$ligne_number}(){
									var objout=document.getElementById('timer{$ligne_number}_out');
									var objinp=document.getElementById('timer{$ligne_number}_inp');
									objinp.innerHTML--;

									var m = Math.floor(objinp.innerHTML/60);
									var _m = (m < 10 ? '0' : '') + m;
									var s = objinp.innerHTML%60;
									var _s = (s < 10 ? '0' : '') + s;
									objout.innerHTML = _m+':'+_s;

									if(objinp.innerHTML<=0) {
										objinp.innerHTML = {$eval_res}+1;
									}
								    }
								    timer{$ligne_number}();
								    setInterval(timer{$ligne_number}, 998);
								</script>
							";
						}

					} else {

						$record_display = $list[$ligne_number][$i-$k];

					}

					/**********************   IF LENGHT OF THE VALUE IS TOO LONG IT MIGHT BE CUT ************************/
					if ( is_numeric($this->FG_TABLE_COL[$i][5]) && (strlen($record_display) > $this->FG_TABLE_COL[$i][5])  ){
						$record_display = substr($record_display, 0, $this->FG_TABLE_COL[$i][5])."";
					} ?>
					<TD valign="middle" align="<?php echo $this->FG_TABLE_COL[$i][3]?>" class="tableBody"><?php
						$origlist[$ligne_number][$i-$k] = $list[$ligne_number][$i-$k];
						$list[$ligne_number][$i-$k] = is_null($record_clear) ? $record_display : $record_clear;

						if (isset ($this->FG_TABLE_COL[$i][11]) && strlen($this->FG_TABLE_COL[$i][11])>1) {
							print call_user_func($this->FG_TABLE_COL[$i][11], $record_display);
						} else {
							echo stripslashes($record_display);
						}
						?>
					</TD>

				 <?php  }

					if($this->FG_EDITION  || $this->FG_INFO || $this->FG_DELETION || $this -> FG_OTHER_BUTTON1 || $this -> FG_OTHER_BUTTON2 || $this -> FG_OTHER_BUTTON3 || $this -> FG_OTHER_BUTTON4 || $this -> FG_OTHER_BUTTON5){
						if (($capcha = $this->FG_CAPCHA_POPUP)) {
							foreach ($list[$ligne_number] as $h => $val) {
								$findme = "|col$h|";
								$pos = stripos($capcha, $findme);
								if ($pos !== false) {
									$capcha = str_replace($findme,trim(strip_tags($val)),$capcha);
								}
							}
						}
						?><TD align="center" valign="middle" class="tableBodyRight" style="display:flex;justify-content:center;align-items:center;" nowrap><?php
						if($this->FG_DELETION && !in_array($list[$ligne_number][$this->FG_NB_TABLE_COL],$this->FG_DELETION_FORBIDDEN_ID) ){
							$check = true;
							$condition_eval=$this->FG_DELETION_CONDITION;
							$check_eval=false;
							if (!empty($this->FG_DELETION_CONDITION) && (preg_match ('/col[0-9]/', $this->FG_DELETION_CONDITION))) {
								$check =false;
								for ($h=count($list[$ligne_number]);$h>=0;$h--){
									$findme = "|col$h|";
									$pos = stripos($condition_eval, $findme);
									if ($pos !== false) {
										$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
									}
								}
								eval('$check_eval = '.$condition_eval.';');
							}
							if ($check || $check_eval) {
							    if (strpos($this->FG_DELETION_LINK,'url:')===0) {
							?><a href="javascript:;" onClick="alertBase('<?= $this->FG_DELETION_LINK . $list[$ligne_number][$this->FG_NB_TABLE_COL] ?>','<?= $capcha ?>',true,false,'#c33d3c',event.pageX-55,event.pageY-100,'UR')"><img src="<?php echo Images_Path_Main;?>/<?php echo $this->FG_DELETION_IMG?>" border="0" title="<?php echo $this->FG_DELETE_ALT?>" alt="<?php echo $this->FG_DELETE_ALT?>"></a><?php
							    } else {
							?><a href="<?php echo $this->FG_DELETION_LINK?><?php echo $list[$ligne_number][$this->FG_NB_TABLE_COL]?>"><img src="<?php echo Images_Path_Main;?>/<?php echo $this->FG_DELETION_IMG?>" border="0" title="<?php echo $this->FG_DELETE_ALT?>" alt="<?php echo $this->FG_DELETE_ALT?>"></a><?php
							    }
							?><div style="width:20px"></div><?php
							}
						}
						if($this->FG_INFO){
							?><a href="<?php echo $this->FG_INFO_LINK?><?php echo $list[$ligne_number][$this->FG_NB_TABLE_COL]?>"><img src="<?php echo Images_Path_Main;?>/<?php echo $this->FG_INFO_IMG?>" border="0" title="<?php echo $this->FG_INFO_ALT?>" alt="<?php echo $this->FG_INFO_ALT?>"></a><?php
						}
						if($this->FG_EDITION){
							$check = true;
							$condition_eval=$this->FG_EDITION_CONDITION;
							$check_eval=false;
							if (!empty($this->FG_EDITION_CONDITION) && (preg_match ('/col[0-9]/i', $this->FG_EDITION_CONDITION))) {
								$check =false;
								for ($h=count($list[$ligne_number]);$h>=0;$h--){
									$findme = "|col$h|";
									$pos = stripos($condition_eval, $findme);
									if ($pos !== false) {
										$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
									}
								}
								eval('$check_eval = '.$condition_eval.';');
							}
							if($check || $check_eval){
							    if (strpos($this->FG_EDITION_LINK,'url:')===0) {
							?><a href="javascript:;" onClick="alertBase('<?= $this->FG_EDITION_LINK . $list[$ligne_number][$this->FG_NB_TABLE_COL] ?>','<?= $list[$ligne_number][0] ?>',true,false,'#445760',event.pageX-55,null,'UR')"><img src="<?= Images_Path_Main ?>/<?= $this->FG_EDITION_IMG ?>" border="0" title="<?= $this->FG_EDIT_ALT ?>" alt="<?= $this->FG_EDIT_ALT ?>"></a><?php
							    } else {
							?><a href="<?php echo $this->FG_EDITION_LINK?><?php echo $list[$ligne_number][$this->FG_NB_TABLE_COL]?>"><img src="<?php echo Images_Path_Main;?>/<?php echo $this->FG_EDITION_IMG?>" border="0" title="<?php echo $this->FG_EDIT_ALT?>" alt="<?php echo $this->FG_EDIT_ALT?>"></a><?php
							    }
							}
						}
						if($this->FG_OTHER_BUTTON1){ 

							$check = true;
							$condition_eval=$this->FG_OTHER_BUTTON1_CONDITION;
							$check_eval=false;
							if (!empty($this->FG_OTHER_BUTTON1_CONDITION) && (preg_match ('/col[0-9]/i', $this->FG_OTHER_BUTTON1_CONDITION))) {
								$check =false;
								for ($h=count($list[$ligne_number]);$h>=0;$h--){
									$findme = "|col$h|";
									$pos = stripos($condition_eval, $findme);
									if ($pos !== false) {
										$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
									}
								}
								eval('$check_eval = '.$condition_eval.';');
							}
							if ($check || $check_eval) {
							?>
							<a href="<?php
								$new_FG_OTHER_BUTTON1_LINK = $this -> FG_OTHER_BUTTON1_LINK;
								$new_FG_OTHER_BUTTON1_IMG = $this -> FG_OTHER_BUTTON1_IMG;
								// we should depreciate |param| and only use |col|
								if (strpos($this -> FG_OTHER_BUTTON1_LINK,"|param|")){
									$new_FG_OTHER_BUTTON1_LINK = str_replace("|param|",$list[$ligne_number][$this->FG_NB_TABLE_COL],$this -> FG_OTHER_BUTTON1_LINK);
									// SHOULD DO SMTH BETTER WITH paramx and get the x number to find the value to use
								}
								if (strpos($this -> FG_OTHER_BUTTON1_LINK,"|param1|")){
									$new_FG_OTHER_BUTTON1_LINK = str_replace("|param1|",$list[$ligne_number][$this->FG_NB_TABLE_COL-1],$this -> FG_OTHER_BUTTON1_LINK);
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if ((preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON1_LINK))) {
									for ($h=count($list[$ligne_number]);$h>=0;$h--) {
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON1_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON1_LINK = str_replace($findme,trim(strip_tags($list[$ligne_number][$h])),$new_FG_OTHER_BUTTON1_LINK);
										}
									}
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if ((preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON1_IMG))) {
									for ($h=count($list[$ligne_number]);$h>=0;$h--) {
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON1_IMG, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON1_IMG = str_replace($findme,$list[$ligne_number][$h],$new_FG_OTHER_BUTTON1_IMG);
										}
									}
								}

								// REPLACE |col_origX|  where is a numero of the column by the column value
								if (preg_match ('/col_orig[0-9]/i', $new_FG_OTHER_BUTTON1_LINK)) {
									for ($h=count($list[$ligne_number]);$h>=0;$h--) {
										$findme = "|col_orig$h|";
										$pos = stripos($new_FG_OTHER_BUTTON1_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON1_LINK = str_replace($findme,$origlist[$ligne_number][$h],$new_FG_OTHER_BUTTON1_LINK);
										}
									}
								}

								echo $new_FG_OTHER_BUTTON1_LINK;
								
								$extra_html = "";
								
								if (!empty($this->FG_OTHER_BUTTON1_HTML_ID) && (preg_match ('/col[0-9]/i',$this->FG_OTHER_BUTTON1_HTML_ID))) {
									$temp_id =$this->FG_OTHER_BUTTON1_HTML_ID;
									for ($h=count($list[$ligne_number]);$h>=0;$h--) {
										$findme = "|col$h|";
										$pos = stripos($temp_id, $findme);
										if ($pos !== false) {
											$temp_id = str_replace($findme,$origlist[$ligne_number][$h],$temp_id);
										}
									}
									$extra_html.=' id="'.$temp_id.'"';
								}
								
					  			if (!empty($this->FG_OTHER_BUTTON1_HTML_CLASS)) {
									$extra_html.= ' class="'.$this->FG_OTHER_BUTTON1_HTML_CLASS.'" ';
								}
								
								if (substr($new_FG_OTHER_BUTTON1_LINK,-1)=='=') echo $list[$ligne_number][$this->FG_NB_TABLE_COL];
								if (strlen($this -> FG_OTHER_BUTTON1_IMG)==0) {
									echo '"'.$extra_html.'> '.'<span class="cssbutton">'.$this->FG_OTHER_BUTTON1_ALT.'</span>';
								} else {
									?>"<?php echo $extra_html ?>><img src="<?php echo $new_FG_OTHER_BUTTON1_IMG?>" border="0" title="<?php echo $this->FG_OTHER_BUTTON1_ALT?>" alt="<?php echo $this->FG_OTHER_BUTTON1_ALT?>"><?php
								}
								?></a>
						<?php } 
			  				}
						?>
						<?php if($this->FG_OTHER_BUTTON2){ 
						
							$check = true;
					  		$condition_eval=$this->FG_OTHER_BUTTON2_CONDITION;
							$check_eval=false;	
					  		if (!empty($this->FG_OTHER_BUTTON2_CONDITION) && (preg_match ('/col[0-9]/i', $this->FG_OTHER_BUTTON2_CONDITION))) {
					  			$check =false;
								for ($h=count($list[$ligne_number]);$h>=0;$h--){
									$findme = "|col$h|";
									$pos = stripos($condition_eval, $findme);
									if ($pos !== false) {
										$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
									}
								}
								eval('$check_eval = '.$condition_eval.';');
								}
					  		if($check || $check_eval){
							?>
							<a href="<?php
								$new_FG_OTHER_BUTTON2_LINK = $this -> FG_OTHER_BUTTON2_LINK;
								$new_FG_OTHER_BUTTON2_IMG = $this -> FG_OTHER_BUTTON2_IMG;
								if (strpos($this -> FG_OTHER_BUTTON2_LINK,"|param|")){
									$new_FG_OTHER_BUTTON2_LINK = str_replace("|param|",$list[$ligne_number][$this->FG_NB_TABLE_COL],$this -> FG_OTHER_BUTTON2_LINK);
									// SHOULD DO SMTH BETTER WITH paramx and get the x number to find the value to use
								}
								if (strpos($this -> FG_OTHER_BUTTON2_LINK,"|param1|")){
									$new_FG_OTHER_BUTTON2_LINK = str_replace("|param1|",$list[$ligne_number][$this->FG_NB_TABLE_COL-1],$this -> FG_OTHER_BUTTON2_LINK);
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if (preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON2_LINK)) {
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON2_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON2_LINK = str_replace($findme,trim(strip_tags($list[$ligne_number][$h])),$new_FG_OTHER_BUTTON2_LINK);
										}
									}
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if ((preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON2_IMG))) {
									for ($h=count($list[$ligne_number]);$h>=0;$h--) {
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON2_IMG, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON2_IMG = str_replace($findme,$list[$ligne_number][$h],$new_FG_OTHER_BUTTON2_IMG);
										}
									}
								}

								// REPLACE |col_origX|  where is a numero of the column by the column value
								if (preg_match ('/col_orig[0-9]/i', $new_FG_OTHER_BUTTON2_LINK)) {
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col_orig$h|";
										$pos = stripos($new_FG_OTHER_BUTTON2_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON2_LINK = str_replace($findme,$origlist[$ligne_number][$h],$new_FG_OTHER_BUTTON2_LINK);
										}
									}
								}
								echo $new_FG_OTHER_BUTTON2_LINK;
								
								$extra_html="";
								
								if(!empty($this->FG_OTHER_BUTTON2_HTML_ID) && (preg_match ('/col[0-9]/i',$this->FG_OTHER_BUTTON2_HTML_ID))){
									$temp_id =$this->FG_OTHER_BUTTON2_HTML_ID;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($temp_id, $findme);
										if ($pos !== false) {
											$temp_id = str_replace($findme,$origlist[$ligne_number][$h],$temp_id);
										}
									}
									$extra_html.=' id="'.$temp_id.'"';
									
								}
								
					  			if(!empty($this->FG_OTHER_BUTTON2_HTML_CLASS) ){
									$extra_html.= ' class="'.$this->FG_OTHER_BUTTON2_HTML_CLASS.'" ';
								}
								
								
								
								if (substr($new_FG_OTHER_BUTTON2_LINK,-1)=='=') echo $list[$ligne_number][$this->FG_NB_TABLE_COL];
								if (strlen($this -> FG_OTHER_BUTTON2_IMG)==0){
									echo '"'.$extra_html.'> '.'<span class="cssbutton">'.$this->FG_OTHER_BUTTON2_ALT.'</span>';
								}else{
									?>"<?php echo $extra_html ?>><img src="<?php echo $new_FG_OTHER_BUTTON2_IMG?>" border="0" title="<?php echo $this->FG_OTHER_BUTTON2_ALT?>" alt="<?php echo $this->FG_OTHER_BUTTON2_ALT?>"><?php
								}
								?></a>
						<?php }
							} ?>
						<?php if($this->FG_OTHER_BUTTON3){
							$check = true;
					  		$condition_eval=$this->FG_OTHER_BUTTON3_CONDITION;
							$check_eval=false;	
					  		if (!empty($this->FG_OTHER_BUTTON3_CONDITION) && (preg_match ('/col[0-9]/i', $this->FG_OTHER_BUTTON3_CONDITION))) {
					  			$check =false;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($condition_eval, $findme);
										if ($pos !== false) {
											$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
										}
									}
							eval('$check_eval = '.$condition_eval.';');	
								}
					  		if($check || $check_eval){
							?>
							<a href="<?php
								$new_FG_OTHER_BUTTON3_LINK = $this -> FG_OTHER_BUTTON3_LINK;
								if (strpos($this -> FG_OTHER_BUTTON3_LINK,"|param|")){
									$new_FG_OTHER_BUTTON3_LINK = str_replace("|param|",$list[$ligne_number][$this->FG_NB_TABLE_COL],$this -> FG_OTHER_BUTTON3_LINK);
									// SHOULD DO SMTH BETTER WITH paramx and get the x number to find the value to use
								}
								if (strpos($this -> FG_OTHER_BUTTON3_LINK,"|param1|")){
									$new_FG_OTHER_BUTTON3_LINK = str_replace("|param1|",$list[$ligne_number][$this->FG_NB_TABLE_COL-1],$this -> FG_OTHER_BUTTON3_LINK);
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if (preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON3_LINK)) {
									for ($h=0;$h<=$this->FG_NB_TABLE_COL;$h++){
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON3_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON3_LINK = str_replace($findme,trim(strip_tags($list[$ligne_number][$h])),$new_FG_OTHER_BUTTON3_LINK);
										}
									}
								}

								// REPLACE |col_origX|  where is a numero of the column by the column value
								if (preg_match ('/col_orig[0-9]/i', $new_FG_OTHER_BUTTON3_LINK)) {
										for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col_orig$h|";
										$pos = stripos($new_FG_OTHER_BUTTON3_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON3_LINK = str_replace($findme,$origlist[$ligne_number][$h],$new_FG_OTHER_BUTTON3_LINK);
										}
									}
								}
								echo $new_FG_OTHER_BUTTON3_LINK;
								
								$extra_html="";
								
								if(!empty($this->FG_OTHER_BUTTON3_HTML_ID) && (preg_match ('/col[0-9]/i',$this->FG_OTHER_BUTTON3_HTML_ID))){
									$temp_id =$this->FG_OTHER_BUTTON3_HTML_ID;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($temp_id, $findme);
										if ($pos !== false) {
											$temp_id = str_replace($findme,$origlist[$ligne_number][$h],$temp_id);
										}
									}
									$extra_html.=' id="'.$temp_id.'"';
									
								}
								
					  			if(!empty($this->FG_OTHER_BUTTON3_HTML_CLASS) ){
									$extra_html.= ' class="'.$this->FG_OTHER_BUTTON3_HTML_CLASS.'" ';
								}
								
								
								
								if (substr($new_FG_OTHER_BUTTON3_LINK,-1)=='=') echo $list[$ligne_number][$this->FG_NB_TABLE_COL];
								if (strlen($this -> FG_OTHER_BUTTON3_IMG)==0){
									echo '"'.$extra_html.'> '.'<span class="cssbutton">'.$this->FG_OTHER_BUTTON3_ALT.'</span>';
								}else{
									?>" <?php echo $extra_html ?> ><img src="<?php echo $this -> FG_OTHER_BUTTON3_IMG?>" border="0" title="<?php echo $this->FG_OTHER_BUTTON3_ALT?>" alt="<?php echo $this->FG_OTHER_BUTTON3_ALT?>"><?php

								}
								?></a>
						<?php }
							} ?>
						<?php if($this->FG_OTHER_BUTTON4){
							$check = true;
					  		$condition_eval=$this->FG_OTHER_BUTTON4_CONDITION;
							$check_eval=false;	
					  		if (!empty($this->FG_OTHER_BUTTON4_CONDITION) && (preg_match ('/col[0-9]/i', $this->FG_OTHER_BUTTON4_CONDITION))) {
					  			$check =false;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($condition_eval, $findme);
										if ($pos !== false) {
											$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
										}
									}
							eval('$check_eval = '.$condition_eval.';');	
								}
					  		if($check || $check_eval){
							?>
							<a href="<?php
								$new_FG_OTHER_BUTTON4_LINK = $this -> FG_OTHER_BUTTON4_LINK;
								if (strpos($this -> FG_OTHER_BUTTON4_LINK,"|param|")){
									$new_FG_OTHER_BUTTON4_LINK = str_replace("|param|",$list[$ligne_number][$this->FG_NB_TABLE_COL],$this -> FG_OTHER_BUTTON4_LINK);
									// SHOULD DO SMTH BETTER WITH paramx and get the x number to find the value to use
								}
								if (strpos($this -> FG_OTHER_BUTTON4_LINK,"|param1|")){
									$new_FG_OTHER_BUTTON4_LINK = str_replace("|param1|",$list[$ligne_number][$this->FG_NB_TABLE_COL-1],$this -> FG_OTHER_BUTTON4_LINK);
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if (preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON4_LINK)) {
									for ($h=0;$h<=$this->FG_NB_TABLE_COL;$h++){
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON4_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON4_LINK = str_replace($findme,$list[$ligne_number][$h],$new_FG_OTHER_BUTTON4_LINK);
										}
									}
								}

								// REPLACE |col_origX|  where is a numero of the column by the column value
								if (preg_match ('/col_orig[0-9]/i', $new_FG_OTHER_BUTTON4_LINK)) {
										for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col_orig$h|";
										$pos = stripos($new_FG_OTHER_BUTTON4_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON4_LINK = str_replace($findme,trim(strip_tags($origlist[$ligne_number][$h])),$new_FG_OTHER_BUTTON4_LINK);
										}
									}
								}
								echo $new_FG_OTHER_BUTTON4_LINK;
								
								$extra_html="";
								
								if(!empty($this->FG_OTHER_BUTTON4_HTML_ID) && (preg_match ('/col[0-9]/i',$this->FG_OTHER_BUTTON4_HTML_ID))){
									$temp_id =$this->FG_OTHER_BUTTON4_HTML_ID;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($temp_id, $findme);
										if ($pos !== false) {
											$temp_id = str_replace($findme,$origlist[$ligne_number][$h],$temp_id);
										}
									}
									$extra_html.=' id="'.$temp_id.'"';
									
								}
								
					  			if(!empty($this->FG_OTHER_BUTTON4_HTML_CLASS) ){
									$extra_html.= ' class="'.$this->FG_OTHER_BUTTON4_HTML_CLASS.'" ';
								}
								
								
								
								if (substr($new_FG_OTHER_BUTTON4_LINK,-1)=='=') echo $list[$ligne_number][$this->FG_NB_TABLE_COL];
								if (strlen($this -> FG_OTHER_BUTTON4_IMG)==0){
									echo '"'.$extra_html.'> '.'<span class="cssbutton">'.$this->FG_OTHER_BUTTON4_ALT.'</span>';
								}else{
									?>" <?php echo $extra_html ?> ><img src="<?php echo $this -> FG_OTHER_BUTTON4_IMG?>" border="0" title="<?php echo $this->FG_OTHER_BUTTON4_ALT?>" alt="<?php echo $this->FG_OTHER_BUTTON4_ALT?>"><?php

								}
								?></a>
						<?php }
						} 
 
						if($this->FG_OTHER_BUTTON5){
							$check = true;
					  		$condition_eval=$this->FG_OTHER_BUTTON5_CONDITION;
							$check_eval=false;	
					  		if (!empty($this->FG_OTHER_BUTTON5_CONDITION) && (preg_match ('/col[0-9]/i', $this->FG_OTHER_BUTTON5_CONDITION))) {
					  			$check =false;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($condition_eval, $findme);
										if ($pos !== false) {
											$condition_eval = str_replace($findme,$list[$ligne_number][$h],$condition_eval);
										}
									}
							eval('$check_eval = '.$condition_eval.';');	
								}
					  		if($check || $check_eval){
							?>
							<a href="<?php
								$new_FG_OTHER_BUTTON5_LINK = $this -> FG_OTHER_BUTTON5_LINK;
								if (strpos($this -> FG_OTHER_BUTTON5_LINK,"|param|")){
									$new_FG_OTHER_BUTTON5_LINK = str_replace("|param|",$list[$ligne_number][$this->FG_NB_TABLE_COL],$this -> FG_OTHER_BUTTON5_LINK);
									// SHOULD DO SMTH BETTER WITH paramx and get the x number to find the value to use
								}
								if (strpos($this -> FG_OTHER_BUTTON5_LINK,"|param1|")){
									$new_FG_OTHER_BUTTON5_LINK = str_replace("|param1|",$list[$ligne_number][$this->FG_NB_TABLE_COL-1],$this -> FG_OTHER_BUTTON5_LINK);
								}

								// REPLACE |colX|  where is a numero of the column by the column value
								if (preg_match ('/col[0-9]/i', $new_FG_OTHER_BUTTON5_LINK)) {
									for ($h=0;$h<=$this->FG_NB_TABLE_COL;$h++){
										$findme = "|col$h|";
										$pos = stripos($new_FG_OTHER_BUTTON5_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON5_LINK = str_replace($findme,trim(strip_tags($list[$ligne_number][$h])),$new_FG_OTHER_BUTTON5_LINK);
										}
									}
								}

								// REPLACE |col_origX|  where is a numero of the column by the column value
								if (preg_match ('/col_orig[0-9]/i', $new_FG_OTHER_BUTTON5_LINK)) {
										for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col_orig$h|";
										$pos = stripos($new_FG_OTHER_BUTTON5_LINK, $findme);
										if ($pos !== false) {
											$new_FG_OTHER_BUTTON5_LINK = str_replace($findme,$origlist[$ligne_number][$h],$new_FG_OTHER_BUTTON5_LINK);
										}
									}
								}
								echo $new_FG_OTHER_BUTTON5_LINK;
								
								$extra_html="";
								
								if(!empty($this->FG_OTHER_BUTTON5_HTML_ID) && (preg_match ('/col[0-9]/i',$this->FG_OTHER_BUTTON5_HTML_ID))){
									$temp_id =$this->FG_OTHER_BUTTON5_HTML_ID;
									for ($h=count($list[$ligne_number]);$h>=0;$h--){
										$findme = "|col$h|";
										$pos = stripos($temp_id, $findme);
										if ($pos !== false) {
											$temp_id = str_replace($findme,$origlist[$ligne_number][$h],$temp_id);
										}
									}
									$extra_html.=' id="'.$temp_id.'"';
				
								}
								
					  			if(!empty($this->FG_OTHER_BUTTON5_HTML_CLASS) ){
									$extra_html.= ' class="'.$this->FG_OTHER_BUTTON5_HTML_CLASS.'" ';
								}
								
								
								
								if (substr($new_FG_OTHER_BUTTON5_LINK,-1)=='=') echo $list[$ligne_number][$this->FG_NB_TABLE_COL];
								if (strlen($this -> FG_OTHER_BUTTON5_IMG)==0){
									echo '"'.$extra_html.'> '.'<span class="cssbutton">'.$this->FG_OTHER_BUTTON5_ALT.'</span>';
								}else{
									?>" <?php echo $extra_html ?> ><img src="<?php echo $this -> FG_OTHER_BUTTON5_IMG?>" border="0" title="<?php echo $this->FG_OTHER_BUTTON5_ALT?>" alt="<?php echo $this->FG_OTHER_BUTTON5_ALT?>"><?php

								}
								?></a>
						<?php
							 }
						}
						?>
					  </TD>
					<?php  } ?>

					</TR>
				<?php
					} //  for (ligne_number=0;ligne_number<count($list);$ligne_number++)
					while ($ligne_number < 7){
				?>
					<TR bgcolor="<?php echo $this->FG_TABLE_ALTERNATE_ROW_COLOR[$ligne_number%2]?>">
				  		<?php
							$REMOVE_COL = ($this->FG_OTHER_BUTTON1 || $this->FG_OTHER_BUTTON2 || $this->FG_OTHER_BUTTON3 || $this->FG_OTHER_BUTTON4 || $this->FG_EDITION || $this->FG_INFO || $this->FG_DELETION )? 0 : 1;
							for($i=0;$i<$this->FG_NB_TABLE_COL-$REMOVE_COL;$i++){
				 		 ?>
                 		 <TD valign=top class="tableBody">&nbsp;</TD>
				 		 <?php  } ?>
                 		 <TD align="center" valign=top class="tableBodyRight">&nbsp;</TD>
					</TR>

				<?php
						$ligne_number++;
					} //END_WHILE
				 ?>
                <TR>
                  <TD class="tableDivider" colSpan=<?php echo $this->FG_TOTAL_TABLE_COL?>><IMG height=1 src="<?php echo Images_Path_Main;?>/clear.gif" width=1></TD>
                </TR>
            </TABLE>

		  </TD>
        </TR>
		<?php if ($this->CV_DISPLAY_BROWSE_PAGE){ ?>
        <TR >
          <TD height=16 style="PADDING-LEFT: 5px; PADDING-RIGHT: 3px">
			<TABLE border=0 cellPadding=0 cellSpacing=0 width="100%">
                <TR>
                  <TD align="right" valign="bottom"><span class="viewhandler_span2">
					<?php
					$this -> printPages($this -> CV_CURRENT_PAGE+1, $this -> FG_NB_RECORD_MAX, $c_url) ;
					?>
					</span>
                  </TD>
            </TABLE></TD>
        </TR>
		<?php  	} 	?>

        <FORM name="otherForm2" action="<?php echo $PHP_SELF; ?>">
		<tr><td>
		<?php if ($this->CV_DISPLAY_RECORD_LIMIT){ ?>
			<?php echo gettext("DISPLAY");?>
            <?php 
            	foreach ($processed as $key => $val) {
            		if (!in_array($key,array('current_page','mydisplaylimit')) && $val!='') {
            	?>
                   <input type="hidden" name="<?php echo $key?>" value="<?php echo $val?>">
             	<?php
             		}
            	}
             ?>
			<select name="mydisplaylimit" size="1" class="form_input_select">
			<?php $session_limit = $this->FG_TABLE_NAME."-displaylimit"; ?>
			
				<option value="10" <?php if($_SESSION[$session_limit]==10 || empty($_SESSION[$session_limit])) echo "selected" ?>>10</option>
				<option value="50" <?php if(is_numeric($_SESSION[$session_limit]) && $_SESSION[$session_limit]==50 ) echo "selected" ?>>50</option>
				<option value="100" <?php if( is_numeric($_SESSION[$session_limit]) && $_SESSION[$session_limit]==100 ) echo "selected" ?>>100</option>
				<option value="ALL" <?php if( is_numeric($_SESSION[$session_limit]) && $_SESSION[$session_limit]>100 ) echo "selected" ?>>All</option>
			</select>
			<input class="form_input_button"  value=" <?php echo gettext("GO");?> " type="SUBMIT">
			&nbsp; &nbsp; &nbsp;
		<?php  	} 	?>
		<?php if ($this->FG_EXPORT_CSV){ ?>
		 - &nbsp; &nbsp; <a href="export_csv.php?var_export=<?php echo $this->FG_EXPORT_SESSION_VAR ?>&var_export_type=type_csv" target="_blank" ><img src="<?php echo Images_Path;?>/excel.gif" border="0" height="30"/><?php echo gettext("Export CSV");?></a>

		<?php  	} 	?>
        <?php if ($this->FG_EXPORT_XML){ ?>
		 - &nbsp; &nbsp; <a href="export_csv.php?var_export=<?php echo $this->FG_EXPORT_SESSION_VAR ?>&var_export_type=type_xml" target="_blank" ><img src="<?php echo Images_Path;?>/icons_xml.gif" border="0" height="32"/><?php echo gettext("Export XML");?></a>

		<?php  	}?>

		</td></tr>
		</FORM>
      </table>
	</td></tr>
      </table>
   </div>
<?php
	}else{
?>
<tr><td>
<br>
</td></tr>
<?php
		// Add filter  FG_FILTER_APPLY , FG_FILTERFIELD and FG_FILTER_FORM_ACTION
		if ($this -> FG_FILTER_APPLY || $this -> FG_FILTER_APPLY2){
		?>
		<tr><FORM NAME="theFormFilter" action="<?php echo $PHP_SELF?>">
            <?php
            	foreach ($processed as $key => $val) {
            		if (!in_array($key,array('current_page','id','form_action','filterfield','filterfield2','filterprefix','filterprefix2')) && $val!='') {
            	?>
                   <input type="hidden" name="<?php echo $key?>" value="<?php echo $val?>">
             	<?php
             		}
            	}
             ?>
			<INPUT type="hidden" name="form_action"	value="<?php echo $this->FG_FILTER_FORM_ACTION ?>">
			<td class="viewhandler_filter_td1">
			<span >
			<?php if ($this -> FG_FILTER_APPLY){ ?>

				<font class="viewhandler_filter_on"><?php echo gettext("FILTER ON ");?> <?php mb_internal_encoding('UTF-8');?><?php echo mb_strtoupper($this->FG_FILTERFIELDNAME)?> :</font>
				<INPUT type="text" name="filterprefix" value="<?php if(!empty($processed['filterprefix'])) echo $processed['filterprefix']; ?>" class="form_input_text">
				<?php if ($this->FG_FILTERFIELD!='') {?>
				<INPUT type="hidden" name="filterfield"	value="<?php echo $this->FG_FILTERFIELD?>">
				<?php
				}
				if ($this -> FG_FILTERTYPE == 'INPUT'){
					// IT S OK
				}elseif ($this -> FG_FILTERTYPE == 'POPUPVALUE'){
				?>
					<a href="#" onclick="window.open('<?php echo $this->FG_FILTERPOPUP[0]?>popup_formname=theFormFilter&popup_fieldname=filterprefix' <?php echo $this->FG_FILTERPOPUP[1]?>);"><img src="<?php echo Images_Path_Main;?>/icon_arrow_orange.gif" valign="bottom"/></a>
				<?php
				}

			}

			if ($this -> FG_FILTER_APPLY2){ ?>
				&nbsp;&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;&nbsp;
				<font class="viewhandler_filter_on"><?php echo gettext("FILTER ON ");?> <?php echo strtoupper($this->FG_FILTERFIELDNAME2)?> :</font>
				<INPUT type="text" name="filterprefix2" value="<?php if(!empty($processed['filterprefix2'])) echo $processed['filterprefix2']; ?>" class="form_input_text">
				<?php if ($this->FG_FILTERFIELD2!='') {?>
				<INPUT type="hidden" name="filterfield2"	value="<?php echo $this->FG_FILTERFIELD2?>">
				<?php
				}
				if ($this -> FG_FILTERTYPE2 == 'INPUT') {
					// IT S OK
				} elseif ($this -> FG_FILTERTYPE2 == 'POPUPVALUE') {
				?>
					<a href="#" onclick="window.open('<?php echo $this->FG_FILTERPOPUP2[0]?>popup_formname=theFormFilter&popup_fieldname=filterprefix2' <?php echo $this->FG_FILTERPOPUP2[1]?>);"><img src="<?php echo Images_Path_Main;?>/icon_arrow_orange.gif" valign="bottom"/></a>
				<?php
				}
			}
			?>
				<input type="SUBMIT" value="<?php echo gettext("APPLY FILTER ");?>" class="form_input_button"/>
			</span>
			</td></FORM>
		<?php } ?>
		</tr>
      </table>
   </div>
	<br>
	<div align="center">
	<table width="80%" border="0" align="center">
		
		<tr>
			<td align="center">
				<?php echo $this -> CV_NO_FIELDS;?><br>
			</td>
		</tr>
	</table>
	</div>
	<br><br>
<?php 
	}//end_if
?>
