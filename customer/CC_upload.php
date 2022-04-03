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


if (!has_rights(ACX_SIP_IAX) && !has_rights(ACX_DID)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error?c=accessdenied");
	die();
}

getpost_ifset(array ('acc', 'method', 'file', 'to'));

//Show the number of files to upload
$files_to_upload = 1;

//Directory where the uploaded files have to come
# the upload store directory (chmod 777)
$upload_dir = DIR_STORE_AUDIO."/" . $_SESSION["pr_login"];

# Handle the MusicOnHold
/*if (isset ($acc) && ($acc > 0)) {
	$file_ext_allow = $file_ext_allow_musiconhold;
	$pass_param = "acc=$acc";
	$upload_dir = DIR_STORE_MOHMP3 . "/acc_$acc";
}*/

# individual file size limit - in bytes (102400 bytes = 100KB)
$file_size_ind = MY_MAX_FILE_SIZE_AUDIO;

# PHP.INI
# ; Maximum allowed size for uploaded files.
# upload_max_filesize = 8M

# the images directory
$dir_img = "templates/default/images";

// -------------------------------- //
//     SCRIPT UNDER THIS LINE!      //
// -------------------------------- //

function getlast($toget) {
	$pos = strrpos($toget, ".");
	$lastext = substr($toget, $pos +1);
	return $lastext;
}

function arr_rid_blank($my_arr) {
	if (is_array($my_arr)) {
		for ($i = 0; $i < count($my_arr); $i++) {
			$my_arr[$i] = trim($my_arr[$i]);
		}
		return $my_arr;
	}
}
$file_ext_allow = arr_rid_blank($file_ext_allow);

//Any other action the user must be logged in!

if ($method) {
	$_SESSION['message'] = "";

	//Upload the file
	if ($method == "upload") {

		$file_array = $_FILES['file'];
		$_SESSION['message'] = "";
		$uploads = false;

		//print_r($file_ext_allow);
		//echo "<br>files_to_upload=$files_to_upload</br>";

		for ($i = 0; $i < $files_to_upload; $i++) {
			if ($_FILES['file']['name'][$i]) {
				$uploads = true;
				if ($_FILES['file']['name'][$i]) {

					$fileupload_name = $_FILES['file']['name'][0];

					for ($i = 0; $i < count($file_ext_allow); $i++) {
						//if (getlast($fileupload_name)!=$file_ext_allow[$i]){
						if (strcmp(getlast($fileupload_name), $file_ext_allow[$i]) != 0) {
							$test .= "~~";
							//echo "<br>'".getlast($fileupload_name)."' - '".$file_ext_allow[$i]."'<br>";
						}
					}
					$exp = explode("~~", $test);
					if (count($exp) == (count($file_ext_allow) + 1)) {
						$_SESSION['message'] .= "<br><img src=\"$dir_img/error.gif\" width=\"15\" height=\"15\">&nbsp;<b><font size=\"2\">" . gettext("ERROR: your file type is not allowed") . " (" . getlast($fileupload_name) . ")</font>, " . gettext("or you didn't specify a file to upload") . ".</b><br>";
					} else {
						if ($_FILES['file']['size'][0] > $file_size_ind) {
							$_SESSION['message'] .= "<br><img src=\"$dir_img/error.gif\" width=\"15\" height=\"15\">&nbsp;<b><font size=\"2\">" . gettext("ERROR: please get the file size less than") . " " . $file_size_ind . " BYTES  (" . round(($file_size_ind / 1024), 2) . " KB)</font></b><br>";
						} else {
							$file_to_upload = $upload_dir . "/" . $_FILES['file']['name'][0];
							if (!file_exists($upload_dir)) mkdir($upload_dir, 0755);
							move_uploaded_file($_FILES['file']['tmp_name'][0], $file_to_upload);
							//echo "<br>::$file_to_upload</br>";
							chmod($upload_dir, 0755);
							chmod($file_to_upload,0644);
							$_SESSION['message'] .= "<font color=\"grey\"><u>" . $_FILES['file']['name'][0] . "</u></font> uploaded.<br>";
						}
					}
				}
			}
		}
		if (!$uploads)
			$_SESSION['message'] = gettext("No files selected!");
	}

	//Logout 
	elseif ($method == "logout") {
		session_destroy();
	}

	//Delete the file
	elseif ($method == "delete" && $file) {
		if (!@ unlink($upload_dir . "/" . $file))
			$_SESSION['message'] = "File not found!";
		else
			$_SESSION['message'] = $file . " deleted";
	}

	//Download a file 
	elseif ($method == "download" && $file) {
		$file = $upload_dir . "/" . $file;
		$filename = basename($file);
		$len = filesize($file);
		header("content-type: application/stream");
		header("content-length: " . $len);
		header("content-disposition: attachment; filename=" . $filename);
		$fp = fopen($file, "r");
		fpassthru($fp);
		exit;
	}

	//Rename a file
	elseif ($method == "rename") {
		rename($upload_dir . "/" . $file, $upload_dir . "/" . $to);
		$_SESSION['message'] = "Renamed " . $file . " to " . $to;
	}
}

$smarty->display('main.tpl');

?>
<center>
<table cellspacing="0" cellpadding="0" border="0" align="center">
  <tr>
    <td><font size="3"><b><i><?php echo gettext("File Upload");?></i></b></font>&nbsp;
    <br><br>
    <font style="text-decoration: bold; font-size: 9px;">  <b><?php echo gettext("Note that if you're using .wav, (eg, recorded with Microsoft Recorder)<br>the file must be PCM Encoded, 16 Bits, at 8000Hz.");?> </b>
    </font>&nbsp;
	<br>
    </td>
   </tr>
</table>


<table cellspacing="5" cellpadding="2" border="0" style="padding-top:5px;padding-left=5px;padding-bottom:5px;padding-right:5px">
  <form method='post' enctype='multipart/form-data' action='<?php echo $_SERVER['PHP_SELF'];?>?popup_select=1&method=upload'>
  <input type="hidden" value="<?php echo $acc?>" name="acc"/>
 <?php for( $i = 0; $i < $files_to_upload; $i++ ) { ?>
         <tr>
           <td>file:</td><td><input type='file' name='file[]' class="upload_textfield" size="30"></td>
         </tr>
    <?php } ?>
	<tr>
    <td nowrap><?php echo gettext("file types allowed");?>:</td><td>

	<?php
	for($i=0;$i<count($file_ext_allow);$i++) {
		if (($i<>count($file_ext_allow)-1))$commas=", ";else $commas="";
		list($key,$value)=each($file_ext_allow);
		echo $value.$commas;
	}
	?>   </td>
  </tr>

  <tr>
    <td nowrap><?php echo gettext("file size limit");?>:</td>
	<td>
		<b><?php 
			if ($file_size_ind >= 1048576) {
				$file_size_ind_rnd = round(($file_size_ind/1024000),3) . " MB";
			} elseif ($file_size_ind >= 1024) {	
				$file_size_ind_rnd = round(($file_size_ind/1024),2) . " KB";
			} elseif ($file_size_ind >= 0) {
				$file_size_ind_rnd = $file_size_ind . " bytes";
			} else {
				$file_size_ind_rnd = "0 bytes";
			}
			
			echo "$file_size_ind_rnd";
		?></b>
	</td>

  </tr>
  <tr>
    <td colspan="2"><input type="submit" value="<?php echo gettext("Upload");?>" class="upload_button">&nbsp;<input type="reset" value="<?php echo gettext("Clear");?>" class="upload_button"></td>
  </tr>
  </form>
</table>
<?php
        //When there is a message, after an action, show it
        if(isset($_SESSION['message']))
        {
          echo "<br><font color='red'>" . $_SESSION['message'] . "</font>";
          unset($_SESSION['message']);
?>
<br>
<center>
<form runat="server">
    <div>
        <input id="button1" onclick="self.close()" class="form_input_button" type="button" value="" />
    </div>
</form>
<script type="text/javascript">
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
    window.opener.location.href = window.opener.location.protocol + '//' + window.opener.location.host + window.opener.location.pathname + '?play=2';
    buttontimer();
    window.resizeBy(0, 90);
    setInterval(function() {buttontimer()}, 1000);
</script>

<?php   } ?>
</center>
<?php
