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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function monitor_recognize(&$ipointer) {
	if (!isset($ipointer->dl_short)) $ipointer->dl_short="";
	if ($ipointer->monitor == 1 || $ipointer->agiconfig['record_call'] == 1) {
	    $dl_short = $ipointer->dl_short . $ipointer->uniqueid . ".";
	    $QUERY = "SELECT cc_card.username, YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime) FROM cc_call LEFT JOIN cc_card ON cc_card.id=card_id WHERE uniqueid LIKE '$ipointer->uniqueid' ORDER BY cc_call.id DESC LIMIT 1";
	    $result = $ipointer->instance_table -> SQLExec ($ipointer -> DBHandle, $QUERY);
	    if (is_array($result) && count($result)>0) {
		$dl_full = MONITOR_PATH . "/";
		for ($i = 0; $i < 4; $i++) {
		    if (!file_exists($dl_full)) mkdir($dl_full);
		    $dl_full .= $result[0][$i] . "/";
		}
		if ($ipointer->dl_short != $dl_full) {
		    if (file_exists($dl_short . "WAV")	|| file_exists($dl_short . "wav") || file_exists($dl_short . "gsm")  || file_exists($dl_short . "mp3")
							|| file_exists($dl_short . "sln") || file_exists($dl_short . "g723") || file_exists($dl_short . "g729")) {
			if (preg_match("/wav/i", $ipointer->agiconfig['monitor_formatfile'])) $monitor_ext = "WAV";
			    else $monitor_ext = $ipointer->agiconfig['monitor_formatfile'];
			$dl_full .= $ipointer->uniqueid . "." . $monitor_ext;
			rename($dl_short . $monitor_ext, $dl_full);
		    }
		}
	    } else return false;
	}
	return true;
}

/*
 * a2b_round: specific function to use the same precision everywhere
 */
function a2b_round($number) {
	$PRECISION = 5;
	return round($number, $PRECISION);
}

function a2b_encrypt($text, $key) {
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($iv_size);
	$temp = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $text, MCRYPT_MODE_ECB, $iv);
	return $temp;
}

function a2b_decrypt($text, $key) {
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
	$iv = mcrypt_create_iv($iv_size);
	$temp = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), $text, MCRYPT_MODE_ECB, $iv);
	return $temp;
}

/*
 * a2b_mail - function mail used in a2billing
 */
function a2b_mail($to, $subject, $mail_content, $from = 'root@localhost', $fromname = '', $contenttype = 'multipart/alternative', $attachfile = '')
{
	
	$mail = new PHPMailer(true);
	
	if (SMTP_SERVER) {
		$mail->IsSMTP();
	} else {
		$mail->IsQmail();
	}

	$mail->CharSet = 'UTF-8';
	$mail->Encoding = 'base64';
	$mail->Host = SMTP_HOST;
	$mail->Username = SMTP_USERNAME;
	$mail->Password = SMTP_PASSWORD;
	$mail->Port = SMTP_PORT;
	$mail->SMTPSecure = SMTP_SECURE;
//	$mail->Hostname = 'sipde.net';

	if (strlen(SMTP_USERNAME) > 0)
		$mail->SMTPAuth = true;

	$mail->From = $from;
	$mail->FromName = $fromname;
	$mail->Subject = $subject;
	$mail->Body = nl2br($mail_content); //$HTML;
	if ($attachfile) $mail->AddAttachment($attachfile);
	$mail->AltBody = $mail_content; // Plain text body (for mail clients that cannot read 	HTML)
	// if ContentType = multipart/alternative -> HTML will be send
	$mail->ContentType = $contenttype;
	
	if (strpos($to, ',') > 0) {
		foreach (explode(',', $to) as $toemail){
            $mail->AddAddress($toemail);
        }
	} else {
	    $mail->AddAddress($to);
	}
	
	try {
		$mail->Send();
	} catch (phpmailerException $e) {
		throw $e;
	}
}

/*
 * get_currencies
 */
function get_currencies($handle = null) {
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$QUERY = "SELECT id, currency, name, value FROM cc_currencies ORDER BY id";
	$result = $instance_table->SQLExec($handle, $QUERY, 1, 300);

	if (is_array($result)) {
		$num_cur = count($result);
		for ($i = 0; $i < $num_cur; $i++) {
			$currencies_list[$result[$i][1]] = array (
				1 => $result[$i][2],
				2 => $result[$i][3]
			);
		}
	}

	if ((isset ($currencies_list)) && (is_array($currencies_list)))
		sort_currencies_list($currencies_list);

	return $currencies_list;
}

function getCurrenciesList() {
	$currencies_list = get_currencies();
	foreach ($currencies_list as $key => $cur_value) {
		$currency_list[$key] = array (
			$cur_value[1] . ' (' . $cur_value[2] . ')',
			$key
		);
	}
	return $currency_list;
}

function getCurrenciesKeyList() {
	$currencies_list = get_currencies();
	foreach ($currencies_list as $key => $cur_value) {
		$currency_list_key[$key][0] = $key;
	}
	return $currency_list_key;
}

function getCurrenciesRateList() {
	$currencies_list = get_currencies();
	foreach ($currencies_list as $key => $cur_value) {
		$currency_list_r[$key] = array (
			$key,
			$cur_value[1]
		);
	}
	return $currency_list_r;
}

/**
* Do Currency Conversion.
* @param $currencies_list the List of currencies.
* @param $amount the amount to be converted.
* @param $from_cur Source Currency
* @param $to_cur Destination Currecny
*/
function convert_currency($currencies_list, $amount, $from_cur, $to_cur) {
	if (!is_numeric($amount) || ($amount == 0)) {
		return 0;
	}
	if ($from_cur == $to_cur) {
		return $amount;
	}
	// EUR -> 1.19175 : MAD -> 0.10897
	// FROM -> 2 - TO -> 0.5 =>>>> multiply 4
	$mycur_tobase = $currencies_list[strtoupper($from_cur)][2];
	$mycur = $currencies_list[strtoupper($to_cur)][2];
	if ($mycur == 0)
		return 0;
	$amount = $amount * ($mycur_tobase / $mycur);
	// echo "\n \n AMOUNT CONVERTED IN NEW CURRENCY $to_cur -> VALUE =".$amount;
	return $amount;
}

/*
 * sort_currencies_list
 */
function sort_currencies_list(& $currencies_list) {
	$first_array = array (
		strtoupper(BASE_CURRENCY
	), 'USD', 'EUR', 'UAH', 'RUB', 'GBP', 'AUD', 'HKD', 'JPY', 'NZD', 'SGD', 'TWD', 'PLN', 'SEK', 'DKK', 'CHF', 'COP', 'MXN', 'CLP');
	foreach ($first_array as $element_first_array) {
		if (isset ($currencies_list[$element_first_array])) {
			$currencies_list2[$element_first_array] = $currencies_list[$element_first_array];
			unset ($currencies_list[$element_first_array]);
		}
	}
	$currencies_list = array_merge((array)$currencies_list2, (array)$currencies_list);
}

/*
 * Write log into file
 */
function write_log($logfile, $output) {
	// echo "<br>$output<br>";
	if (strlen($logfile) > 1) {
		$string_log = "[" . date("d/m/Y H:i:s") . "]:[$output]\n";
		error_log($string_log . "\n", 3, $logfile);
	}
}

/*
 * function cleanInput
 */
function cleanInput($input) {
	$search = array (
		'@<script[^>]*?>.*?</script>@si', // Strip out javascript
		'@<[\/\!]*?[^<>]*?>@si', // Strip out HTML tags
		'@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
		'@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments
	);

	$output = preg_replace($search, '', $input);
	return $output;
}

/*
 * function sanitize_data
 */
function sanitize_data($input) {
	global $DBHandle;

	if (is_array($input)) {
		foreach ($input as $var => $val) {
			$output[$var] = sanitize_data($val);
		}
	} else {

		// remove whitespaces (not a must though)  
		$input = trim($input);
		$input = str_replace('--', '', $input);
		$input = str_replace('..', '', $input);
//		$input = str_replace(';', '', $input);
//		$input = str_replace('#', '', $input);
		$input = str_replace('/*', '', $input);
		
		//injection sql
		$input = str_ireplace('HAVING', '', $input);
		$input = str_ireplace('UNION', '', $input);
		$input = str_ireplace('SUBSTRING', '', $input);
		$input = str_ireplace('ASCII', '', $input);
		$input = str_ireplace('SHA1', '', $input);
//		$input = str_ireplace('MD5', '', $input);
//		$input = str_ireplace('SCRIPT', '', $input);
		$input = str_ireplace('ROW_COUNT', '', $input);
//		$input = str_ireplace('SELECT', '', $input);
//		$input = str_ireplace('INSERT', '', $input);
		$input = str_ireplace('CASE WHEN', '', $input);
		$input = str_ireplace('INFORMATION_SCHEMA', '', $input);
//		$input = str_ireplace('DROP', '', $input);
		$input = str_ireplace('RLIKE', '', $input);
//		$input = str_ireplace('DELETE', '', $input);
//		$input = str_ireplace('CONCAT', '', $input);
//		$input = str_ireplace('WHERE', '', $input);
//		$input = str_ireplace('UPDATE', '', $input);
		$input = str_ireplace(' or 1', '', $input);
		$input = str_ireplace(' or true', '', $input);
		$input = str_ireplace('=$', '+$', $input);
		$input = str_ireplace('=', '', $input);
		$input = str_ireplace('+$', '=$', $input);

//		$input  = stripslashes($input);
		$input  = cleanInput($input);
		$output = addslashes($input);
//		$output = mysqli_real_escape_string( $DBHandle->_connectionID, $input );
	}

	return $output;
}

/*
 * function getpost_ifset
 */
function getpost_ifset($test_vars) {
	if (!is_array($test_vars)) {
		$test_vars = array (
			$test_vars
		);
	}
	foreach ($test_vars as $test_var) {
		if (isset ($_POST[$test_var])) {
			global $$test_var;
			$$test_var = $_POST[$test_var];
			$$test_var = sanitize_data($$test_var);
			if ($test_var == 'username' || $test_var == 'filterprefix' || $test_var == 'callerid' || $test_var == 'phonenumber' || $test_var == 'src') {
				//rebuild the search parameter to filter character to format card number
				$filtered_char = array (
					" ",
					"-",
					"+",
					"_",
					"(",
					")",
					"&"
				);
				$$test_var = str_replace($filtered_char, "", $$test_var);

			}
		} elseif (isset ($_GET[$test_var])) {
			global $$test_var;
			$$test_var = $_GET[$test_var];
			$$test_var = sanitize_data($$test_var);
			//rebuild the search parameter to filter character to format card number
			if ($test_var == 'username' || $test_var == 'filterprefix' || $test_var == 'callerid' || $test_var == 'phonenumber' || $test_var == 'src') {
				//rebuild the search parameter to filter character to format card number
				$filtered_char = array (
					" ",
					"-",
					"+",
					"&",
					"_",
					"(",
					")",
					"/",
					"\\"
				);
				$$test_var = str_replace($filtered_char, "", $$test_var);
			}
		}
	}
}

/*
 * function display_money
 */
function display_money($value, $currency = BASE_CURRENCY) {
	$body = explode(" ", $value);
	if (isset($body[1]))
		$currency = $body[1];
	echo number_format($body[0], 2, '.', ' ') . ' ' . mb_strtoupper($currency);
}

function display_refill_money($value, $currency = BASE_CURRENCY, $echoreturn = true) {
	$body = explode(" ", $value);
	if (isset($body[1]) && $body[1]!="1")
		$currency = $body[1];
	$tempval = 100*abs($value-floor($value));
	$value = number_format($value, 5, '.', '');
	$value = (round($tempval-floor($tempval),5) == 0) ? number_format($value, 2, '.', ' ') : rtrim(number_format($value, 5, '.', ' '), '0');
	if ($echoreturn)
	    if (isset($body[1]) && $body[1]=="1") {
		echo "<font class=\"blinkmoney\" title=\"" . gettext('Just refilled') . "\">" . $value . " " . mb_strtoupper($currency) . "</font>";
	    }
	    else {
		echo $value . ' ' . mb_strtoupper($currency);
	    }
	else
	    return $value . ' ' . mb_strtoupper($currency);
}

function display_money_nocur($var, $currency = BASE_CURRENCY) {
	global $currencies_list, $choose_currency;

	if (isset($choose_currency) && strlen($choose_currency) == 3)
		$currency = $choose_currency;
	if ((!isset ($currencies_list)) || (!is_array($currencies_list)))
		$currencies_list = get_currencies();
	$var = round($var / $currencies_list[mb_strtoupper($currency)][2], 5);
	$tempval = 100*abs($var);
	$var = number_format($var, 5, '.', '');
	$var = (round($tempval-floor($tempval),3) == 0) ? number_format($var, 2, '.', ' ') : rtrim(number_format($var, 5, '.', ' '), '0');
	echo $var;
}

function display_2bill($var, $currency = BASE_CURRENCY) {
	global $currencies_list, $choose_currency;

	if (isset($choose_currency) && strlen($choose_currency) == 3)
		$currency = $choose_currency;
	if ((!isset ($currencies_list)) || (!is_array($currencies_list)))
		$currencies_list = get_currencies();
	$var = round($var / $currencies_list[mb_strtoupper($currency)][2], 5);
	$tempval = 100*abs($var-floor($var));
	$var = number_format($var, 5, '.', '');
	$var = (round($tempval-floor($tempval),5) == 0) ? number_format($var, 2, '.', ' ') : rtrim($var, '0');
	echo $var . ' ' . mb_strtoupper($currency);
//	echo number_format($var, 5) . ' ' . strtoupper($currency);
}

/*
 * function display_dateformat
 */
function display_dateformat($mydate) {
	if (DB_TYPE == "mysql") {
		if (strlen($mydate) == 14) {
			// YYYY-MM-DD HH:MM:SS 20300331225242
			echo substr($mydate, 0, 4) . '-' . substr($mydate, 4, 2) . '-' . substr($mydate, 6, 2);
			echo ' ' . substr($mydate, 8, 2) . ':' . substr($mydate, 10, 2) . ':' . substr($mydate, 12, 2);
			return;
		}
	}
//	echo "<font color=red>".$mydate."</font>";
	echo $mydate;
}

/*
 * function display_vm_callerid
 */
function display_date_timestamp($timestamp){
	echo date("m/d/Y H:i:s", $timestamp);
}	

/*
 * function display_vm_callerid
 */
function display_vm_callerid($callerid_string){
	$arr_spli = preg_split("/ /", $callerid_string);
	echo str_replace('"',"", $arr_spli[0]);
}	



/*
 * function display_dateonly
 */
function display_dateonly($mydate) {
	if (strlen($mydate) > 0 && $mydate != '0000-00-00') {
		echo date("m/d/Y", strtotime($mydate));
	}
}

/*
 * function res_display_dateformat
 */
function res_display_dateformat($mydate) {
	if (DB_TYPE == "mysql") {
		if (strlen($mydate) == 14) {
			// YYYY-MM-DD HH:MM:SS 20300331225242
			$res = substr($mydate, 0, 4) . '-' . substr($mydate, 4, 2) . '-' . substr($mydate, 6, 2);
			$res .= ' ' . substr($mydate, 8, 2) . ':' . substr($mydate, 10, 2) . ':' . substr($mydate, 12, 2);
			return $res;
		}
	}
	return $mydate;
}

function res_display_timeformat($mydate) {
	if (DB_TYPE == "mysql") {
		if (strlen($mydate) == 6) {
			// YYYY-MM-DD HH:MM:SS 20300331225242
			$res = substr($mydate, 0, 4) . ':' . substr($mydate, 4, 2) . ':' . substr($mydate, 6, 2);
			return $res;
		}
	}
	return $mydate;
}

/*
 * function display_minute
 */
function display_minute($sessiontime) {
	global $resulttype;
	if ($sessiontime < 0)
		$sessiontime = 0;
	if ((!isset ($resulttype)) || ($resulttype == "min")) {
		$minutes = sprintf("%02d", intval($sessiontime / 60)) . ":" . sprintf("%02d", intval($sessiontime % 60));
	} else {
		$minutes = $sessiontime;
	}
	echo $minutes;
}

function display_2dec($var) {
	echo number_format($var, 2);
}

function display_2dec_percentage($var) {
	if (isset ($var)) {
		echo number_format($var, 2) . "%";
	} else {
		echo "n/a";
	}
}

function display_percentage($var) {
	if (isset ($var)) {
		printf("%d%%", $var);
	} else {
		echo "n/a";
	}
}

function remove_prefix($phonenumber) {
	if (substr($phonenumber, 0, 3) == "011") {
		echo substr($phonenumber, 3);
		return 1;
	}
	if (substr($phonenumber, 0, 2) == "00") {
		echo substr($phonenumber, 2);
		return 1;
	}
	echo $phonenumber;
}

/*
 * function linkonmonitorfile
 */
function linkonmonitorfile($value) {
	$handle = DbConnect();
	$handle -> Execute("SET @@session.time_zone=@@global.time_zone");
	$instance_table = new Table();
	$QUERY = "SELECT YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime), cc_card.username FROM cc_call LEFT JOIN cc_card ON cc_card.id=card_id WHERE uniqueid LIKE '$value' ORDER BY cc_call.id DESC LIMIT 1";
	$result = $instance_table -> SQLExec ($handle, $QUERY);
	if (is_array($result) && count($result)>0) {
	    $dl_short = MONITOR_PATH . "/" . $result[0][3] . "/" . $result[0][0] . "/" . $result[0][1] . "/" . $result[0][2];
	    $format_list = array ('WAV','wav','gsm','GSM','mp3','MP3','sln','g723','g729');
	    $find_record = false;
	    foreach ($format_list as $c_format){
		$myfile = $value . "." . $c_format;
		$dl_full = $dl_short . "/" . $myfile;
		if (file_exists($dl_full)) {
			$find_record = true;
			break;
		}
	    }
	    if (!$find_record) return false;
	    $myfile = base64_encode($myfile);
	    echo '<a href="javascript:;"><img src="' . Images_Path . "/flv.gif\" height=\"16\" onClick=\"GreetPlay(this,'".$_SERVER['PHP_SELF']."?download=file&file=" . $myfile . "')\" border=\"0\"></a>";
	    echo '<a target=_blank href="'.$_SERVER['PHP_SELF'].'?download=file&file=' . $myfile . '&sens=1"><img src="' . Images_Path . '/icoDisk.gif" height="16"/></a>';
	} else return false;
}

/*
 * function linkonfaxfile_customer
 */
function linkonfaxfile_customer($value) {
	$handle = DbConnect();
	$handle -> Execute("SET @@session.time_zone=@@global.time_zone");
	$instance_table = new Table();
	$QUERY = "SELECT YEAR(starttime), MONTH(starttime), DAYOFMONTH(starttime), cc_card.username, faxstatus FROM cc_call LEFT JOIN cc_card ON cc_card.id=card_id WHERE uniqueid LIKE '$value' ORDER BY cc_call.id DESC LIMIT 1";
	$result = $instance_table -> SQLExec ($handle, $QUERY);
	if (is_array($result) && count($result)>0) {
		$dl_short = FAX_PATH . "/" . $result[0][3] . "/" . $result[0][0] . "/" . $result[0][1] . "/" . $result[0][2];
		$myfile = $value . ".pdf";
		if (!file_exists($dl_short . "/" . $myfile)) return false;
		$myfile = base64_encode($myfile);
		echo '<a href="fax-history.php?download=file&file=' . $myfile . '"></a>';
		echo '<a target=_blank href="fax-history.php?download=file&file=' . $myfile . '"><img src="' . Images_Path . '/pdf.gif" height="16"/></a>';
	} else return false;
}

/*
 * function linkdelete_cdr
 */
function linkdelete_cdr($value) {
	echo "<a href=\"javascript:;\" onClick=\"alertBase('url:A2B_entity_call?form_action=ask-delete&popup_select=4&id=" . $value . "',null,true,false,'#445760',event.pageX-20,event.pageY-100,'UR')\">";
	echo '<img src="' . Images_Path . '/delete.png"/></a>';
}

function linktocustomer($value) {
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "id");
	$FG_TABLE_CLAUSE = "username = '$value'";
	$list_customer = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	$id = $list_customer[0][0];
	if ($id > 0) {
		echo "<a href=\"A2B_entity_card.php?form_action=ask-edit&id=$id\">$value</a>";
	} else {
		echo $value;
	}
}

function linktocustomer_id($id) {
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "username, CONCAT_WS(' ',lastname,firstname,IF(company_name='','',CONCAT('(',company_name,')'))) customer");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_customer = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	$value = $list_customer[0][0];
	$customer = $list_customer[0][1];
	if (!is_numeric($id)) {
		echo $id;
	} else if ($id > 0) {
		echo "<a href=\"A2B_entity_card.php?form_action=ask-edit&id=$id\" title=\"$customer\">$value</a>";
	} else {
		echo $value;
	}
}

function linkto_TC($id) {
	$call_status = Constants :: getDialStatusList();
	if (!empty ($call_status[$id][0]))
		echo $call_status[$id][0];
	else
		echo gettext("UNKNOWN");
}

function infocustomer_id($id) {
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "username,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_customer = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if (is_array($list_customer))
		$value = $list_customer[0][1] . " " . $list_customer[0][2] . " (" . $list_customer[0][0] . ")";
	else
		$value = "";
	if ($id > 0) {
		echo "<a href=\"A2B_card_info.php?id=$id\">$value</a>";
	} else {
		echo $value;
	}
}

function nameofadmin($id) {
	echo getnameofadmin($id);
}

function getnameofadmin($id) {
	$handle = DbConnect();
	$inst_table = new Table("cc_ui_authen", "login,name");
	$FG_TABLE_CLAUSE = "userid = '$id'";
	$list_admin = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if (is_array($list_admin))
		$value = $list_admin[0][1] . " (" . $list_admin[0][0] . ")";
	else
		$value = "";
	return $value;
}

function nameofcustomer_id($id) {
	echo getnameofcustomer_id($id);
}

function getnameofcustomer_id($id) {
	$handle = DbConnect();
	$inst_table = new Table("cc_card", "username,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_customer = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if (is_array($list_customer))
		$value = $list_customer[0][1] . " " . $list_customer[0][2] . " (" . $list_customer[0][0] . ")";
	else
		$value = "";
	return $value;
}

function linktoagent($id) {
	$handle = DbConnect();
	$inst_table = new Table("cc_agent", "login,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_agent = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if (is_array($list_agent))
		$value = $list_agent[0][1] . " " . $list_agent[0][2] . " (" . $list_agent[0][0] . ")";
	else
		$value = "";
	if ($id > 0) {
		return "<a href=\"A2B_entity_agent.php?form_action=ask-edit&id=$id\">$value</a>";
	} else {
		return $value;
	}
}

function nameofagent($id) {
	echo getnameofagent($id);
}
function getnameofagent($id) {
	$handle = DbConnect();
	$inst_table = new Table("cc_agent", "login,firstname,lastname");
	$FG_TABLE_CLAUSE = "id = '$id'";
	$list_agent = $inst_table->Get_list($handle, $FG_TABLE_CLAUSE, "", "", "", "", "", "", "", 10);
	if (is_array($list_agent))
		$value = $list_agent[0][1] . " " . $list_agent[0][2] . " ( login: " . $list_agent[0][0] . ")";
	else
		$value = "";
	return $value;
}

/*
 * function MDP_STRING
 */
function MDP_STRING($chrs = LEN_CARDNUMBER)
{
	do {
	    $pwd = "";
	    mt_srand((double) microtime() * 1000000);
	    while (strlen($pwd) < $chrs) {
		$chr = chr(mt_rand(0, 255));
		if (preg_match("/^[0-9a-z]$/i", $chr))
			$pwd = $pwd . $chr;
	    }
	} while (!preg_match('/(([a-zA-Z]\d)|(\d[a-zA-Z]))+/',$pwd));
	return $pwd;
}

/*
 * function MDP_NUMERIC
 */
function MDP_NUMERIC($chrs = LEN_CARDNUMBER)
{
    $myrand = "";
    for($i = 0; $i < $chrs; $i++){
        if ($i == 0)
    	    $myrand .= mt_rand(1,9);
	else
    	    $myrand .= mt_rand(0,9);
    }
    
    return $myrand;
}

/*
 * function MDP
 */
function MDP($chrs = LEN_CARDNUMBER)
{
	return MDP_NUMERIC ($chrs);
}

/*
 * function gen_card
 */
function gen_card($table = "cc_card, cc_voucher", $len = LEN_CARDNUMBER, $field1 = "username", $field2 = "voucher")
{
	return generate_unique_value ($table, $len, $field1, $field2);
}

/*
 * function generate_unique_value
 */
function generate_unique_value($table, $len, $field1, $field2 = false)
{
	$DBHandle_max = DbConnect();
	for ($k = 0; $k <= 100000; $k++) {
		do {
			$card_gen = MDP($len);
		} while ($card_gen < pow(10,$len-1));
		if ($k == 100000) {
			echo "ERROR : Impossible to generate a $field1 not yet used!<br>Perhaps check the LEN_CARDNUMBER (value:" . LEN_CARDNUMBER . ")";
			exit ();
		}
		$addwhere = $field1;
		if ($field2 !== false)	{
			$addwhere = "='$card_gen' OR $field2";
			$field2   = ", " . $field2;
		} else	$addwhere = "";
		$query = "SELECT " . $field1 . $field2 . " FROM " . $table . " WHERE " . $addwhere . "='$card_gen'";
		$resmax = $DBHandle_max->Execute($query);
		$numrow = 0;
		if ($resmax)
			$numrow = $resmax->RecordCount();

		if ($numrow != 0)
			continue;
		return $card_gen;
	}
}

/*
 * function gen_card_with_alias
 */
function gen_card_with_alias($table = "cc_card, cc_voucher", $api = 0, $length_cardnumber = LEN_CARDNUMBER, $DBHandle = null, $alias_static = 0, $card_id = 0)
{
	if (!isset($DBHandle)) {
		$DBHandle = DbConnect();
	}

	for ($k = 0; $k <= 10000; $k++) {
/**		do {
			$card_gen = MDP($length_cardnumber);
		} while ($card_gen < pow(10,$length_cardnumber-1));
**/		$card_gen = gen_card($table, $length_cardnumber);
		do {
			$alias_gen = ($k==0 && LEN_ALIASNUMBER==strlen($alias_static))?$alias_static:MDP(LEN_ALIASNUMBER);
		} while ($alias_gen != $alias_static && $alias_gen < pow(10,LEN_ALIASNUMBER-1));
		if ($k == 10000) {
			if ($api) {
				global $mail_content, $email_alarm, $logfile;
				mail($email_alarm, "ALARM : API (gen_card_with_alias - CODE_ERROR 8)", $mail_content);
				error_log("[" . date("Y/m/d G:i:s", mktime()) . "] " . "[gen_card_with_alias] - CODE_ERROR 8" . "\n", 3, $logfile);
				echo ("500 Internal server error");
				exit ();
			} else {
				echo "ERROR : Impossible to generate a Cardnumber & Aliasnumber not yet used!<br>Perhaps check the LEN_CARDNUMBER  (value:" . LEN_CARDNUMBER . ") & LEN_ALIASNUMBER (value:" . LEN_ALIASNUMBER . ")";
				exit ();
			}
		}

		if ($card_id) {
			$inst_table = new Table($table, "useralias");
			$resmax = $inst_table->Get_list($DBHandle, "id = '$card_id' AND useralias NOT IN (SELECT name FROM cc_sip_buddies WHERE id_cc_card = '$card_id' AND external=0) AND LENGTH(useralias)=" . LEN_ALIASNUMBER, "", "", "", "", "", "", "", 10);
			if (is_array($resmax) && count($resmax)) {
				$alias_gen = $resmax[0][0];
			}
		}
		$query = "SELECT cc_card.username, cc_sip_buddies.name  FROM " . $table . " where cc_card.username='$card_gen' OR cc_card.useralias='$alias_gen' OR cc_card.useralias='$card_gen' FULL OUTER JOIN cc_sip_buddies ON cc_sip_buddies.name='$card_gen' OR cc_sip_buddies.name='$alias_gen'";
		$numrow = 0;
		$resmax = $DBHandle->Execute($query);

		if ($resmax)
			$numrow = $resmax->RecordCount();

		if ($numrow != 0)
			continue;
		$arr_val[0] = $card_gen;
		$arr_val[1] = $alias_gen;
		return $arr_val;
	}
}

/*
 * function 
 gen_friends
 */
function gen_friends($card_id, $start, $quantity, $min, $max, $DBHandle = null, &$A2B, $language = false, $api = 0)
{
	if (!isset($DBHandle)) {
		$DBHandle = DbConnect();
	}
	$inst_table = new Table('cc_sip_buddies');

	$amaflags	= $A2B->config["peer_friend"]['amaflag'];
	$type		= $A2B->config["peer_friend"]['type'];
	$nat		= $A2B->config["peer_friend"]['nat'];
	$dtmfmode	= $A2B->config["peer_friend"]['dtmfmode'];
	$allow		= $A2B->config["peer_friend"]['allow'];
	$host		= $A2B->config["peer_friend"]['host'];
	$context	= $A2B->config["peer_friend"]['context'];
	$qualify	= $A2B->config["peer_friend"]['qualify'];
	if ($language===false)	$language = $A2B->config["global"]['base_language'];
	$regseconds	= "'0'";
	$rtptimeout	= "'60'";
	$rtpholdtimeout	= "'300'";
	$rtpkeepalive	= "'30'";
	$allowtransfer	= "'yes'";
	$nums		= array();

		for ($i = 1; $i <= $quantity; $i++) {
		    for ($k = 0; $k <= 1000; $k++) {
			if ($k == 1000) {
				if ($api) {
					global $mail_content, $email_alarm, $logfile;
					mail($email_alarm, "ALARM : API (gen_friends - CODE_ERROR 8)", $mail_content);
					error_log("[" . date("Y/m/d G:i:s", mktime()) . "] " . "[gen_friends] - CODE_ERROR 8" . "\n", 3, $logfile);
					echo "500 Internal server error";
					exit ();
				} else {
					echo "ERROR : Impossible to generate a Friends not yet used!<br>Perhaps check the StartNumber (value:" . $start . ") & Quantity (value:" . $quantity . ")";
					exit ();
				}
			}
			$QUERY = "SELECT regexten AS field FROM cc_sip_buddies
					LEFT JOIN cc_card_concat bb ON id_cc_card = bb.concat_card_id
					LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = $card_id ) AS v ON bb.concat_id = v.concat_id
					WHERE (id_cc_card = $card_id OR v.concat_id IS NOT NULL) AND regexten = '$start' AND external = 0
				UNION ALL
				  SELECT ext_num AS field FROM cc_fax
					LEFT JOIN cc_card_concat bb ON bb.concat_card_id = id_cc_card
					LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = $card_id ) AS v ON bb.concat_id = v.concat_id
					WHERE (id_cc_card = $card_id OR v.concat_id IS NOT NULL) AND ext_num = '$start'
				ORDER BY field";
			$numrow = 0;
			$resmax = $DBHandle->Execute($QUERY);
			if ($resmax)
				$numrow = $resmax->RecordCount();
			if ($numrow != 0) {
				if ($start >= $max) return $i-1;
				$start++;
				continue;
			}
			break;
		    }
//echo $start."-";
		$pass = MDP_STRING(10);
		$c_id = $i==1 ? $card_id : 0 ;
		$name = gen_card_with_alias("cc_card", 0, LEN_CARDNUMBER, $DBHandle, $start, $c_id);

		$QUERY = "INSERT INTO cc_sip_buddies (id_cc_card, name, defaultuser, accountcode, secret, regexten, callerid, amaflags, type, nat, dtmfmode, allow, host, context, regseconds, language, mailbox, rtptimeout, rtpholdtimeout, rtpkeepalive, qualify, allowtransfer)
			VALUES ('$card_id', '{$name[1]}', '{$name[1]}', '" . $_SESSION["pr_login"] . "', '$pass', '$start', 'Internal-$start', '$amaflags', '$type', '$nat', '$dtmfmode', '$allow', '$host', '$context', $regseconds, '$language', '{$start}@{$_SESSION["pr_login"]}', $rtptimeout, $rtpholdtimeout, $rtpkeepalive, '$qualify', $allowtransfer)";
		$result = $inst_table->SQLExec($DBHandle, $QUERY, 0);
		$nums[] = $start;
//echo $QUERY."<br/>";
		$QUERY = "INSERT INTO cc_voicemail_users (customer_id, sip_buddy_id, context, mailbox, password, fullname, email, language)
					SELECT cc_card.id, cc_sip_buddies.id, cc_card.username, '$start', '0000', concat(lastname,' ',firstname) fullname, email, cc_card.language FROM cc_card, cc_sip_buddies WHERE cc_card.id='$card_id' AND cc_sip_buddies.name='{$name[1]}'";
		$result = $inst_table->SQLExec($DBHandle, $QUERY, 0);
		$start++;
		}
		return $nums;
}

/**
* Do multi-page navigation.  Displays the prev, next and page options.
* @param $page the page currently viewed
* @param $pages the maximum number of pages
* @param $url the url to refer to with the page number inserted
* @param $max_width the number of pages to make available at any one time (default = 20)
*/
function printPages($page, $pages, $url, $max_width = 20)
{
	$lang['strfirst'] = '&lt;&lt; ' . gettext('First');
	$lang['strprev'] = '&lt; ' . gettext('Prev');
	$lang['strnext'] = gettext('Next') . ' &gt;';
	$lang['strlast'] = gettext('Last') . ' &gt;&gt;';

	$window = 8;

	if ($page < 0 || $page > $pages)
		return;
	if ($pages < 0)
		return;
	if ($max_width <= 0)
		return;

	if ($pages > 1) {
		//echo "<center><p>\n";
		if ($page != 1) {
			$temp = str_replace('%s', 1 - 1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strfirst']}</a>\n";
			$temp = str_replace('%s', $page -1 - 1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strprev']}</a>\n";
		}

		if ($page <= $window) {
			$min_page = 1;
			$max_page = min(2 * $window, $pages);
		} elseif ($page > $window && $pages >= $page + $window) {
			$min_page = ($page - $window) +1;
			$max_page = $page + $window;
		} else {
			$min_page = ($page - (2 * $window - ($pages - $page))) +1;
			$max_page = $pages;
		}

		// Make sure min_page is always at least 1
		// and max_page is never greater than $pages
		$min_page = max($min_page, 1);
		$max_page = min($max_page, $pages);

		for ($i = $min_page; $i <= $max_page; $i++) {
			$temp = str_replace('%s', $i -1, $url);
			if ($i != $page)
				echo "<a class=\"pagenav\" href=\"{$temp}\">$i</a>\n";
			else
				echo "$i\n";
		}
		if ($page != $pages) {
			$temp = str_replace('%s', $page +1 - 1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strnext']}</a>\n";
			$temp = str_replace('%s', $pages -1, $url);
			echo "<a class=\"pagenav\" href=\"{$temp}\">{$lang['strlast']}</a>\n";
		}
	}
}

/**
* Validate the Uploaded Files.  Return the error string if any.
* @param $the_file the file to validate
* @param $the_file_type the file type
*/
function validate_upload($the_file, $the_file_type) {
	$registered_types = array (
		"application/x-gzip-compressed" => ".tar.gz, .tgz",
		"application/x-zip-compressed" => ".zip",
		"application/x-tar" => ".tar",
		"text/plain" => ".html, .php, .txt, .inc (etc)",
		"image/bmp" => ".bmp, .ico",
		"image/gif" => ".gif",
		"image/pjpeg" => ".jpg, .jpeg",
		"image/jpeg" => ".jpg, .jpeg",
		"image/png" => ".png",
		"application/x-shockwave-flash" => ".swf",
		"application/msword" => ".doc",
		"application/vnd.ms-excel" => ".xls",
		"application/octet-stream" => ".exe, .fla (etc)",
		"text/x-comma-separated-values" => ".csv",
		"text/comma-separated-values" => ".csv",
		"text/csv" => ".csv",
		"text/x-csv" => ".csv"
	); # these are only a few examples, you can find many more!

	$allowed_types = array (
		"text/plain",
		"text/x-comma-separated-values",
		"text/comma-separated-values",
		"text/csv",
		"text/x-csv",
		"application/vnd.ms-excel"
	);

	$start_error = "\n<b>ERROR:</b>\n<ul>";
	$error = "";
	if ($the_file == "") {
		$error .= "\n<li>" . gettext("File size is greater than allowed limit.") . "\n<ul>";
	} else {
		if ($the_file == "none") {
			$error .= "\n<li>" . gettext("You did not upload anything!") . "</li>";
		}
		elseif ($_FILES['the_file']['size'] == 0) {
			$error .= "\n<li>" . gettext("Failed to upload the file, The file you uploaded may not exist on disk.") . "!</li>";
		} else {
			if (!in_array($the_file_type, $allowed_types)) {
				$error .= "\n<li>" . gettext("file type is not allowed") . ': ' . $the_file_type . "\n<ul>";
				while ($type = current($allowed_types)) {
					$error .= "\n<li>" . $registered_types[$type] . " (" . $type . ")</li>";
					next($allowed_types);
				}
				$error .= "\n</ul>";
			}
		}
	}
	if ($error) {
		$error = $start_error . $error . "\n</ul>";
		return $error;
	} else {
		return false;
	}

} # END validate_upload

/*
	Function securitykey
*/
function securitykey($key, $data) {
	// RFC 2104 HMAC implementation for php.
	// Creates an md5 HMAC.
	// Eliminates the need to install mhash to compute a HMAC
	// Hacked by Lance Rushing

	$b = 64; // byte length for md5
	if (strlen($key) > $b) {
		$key = pack("H*", md5($key));
	}
	$key = str_pad($key, $b, chr(0x00));
	$ipad = str_pad('', $b, chr(0x36));
	$opad = str_pad('', $b, chr(0x5c));
	$k_ipad = $key ^ $ipad;
	$k_opad = $key ^ $opad;

	return md5($k_opad . pack("H*", md5($k_ipad . $data)));
}

function getAcceptLanguage($languages)
{
    if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ||
        !$_SERVER['HTTP_ACCEPT_LANGUAGE']) {
        return $languages[0];
    }

    /*
     * Разбираем заголовок Accept-Language
     *
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
     * 14.4 Accept-Language
     * Accept-Language = "Accept-Language" ":"
     *                   1#( language-range [ ";" "q" "=" qvalue ] )
     * language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
     *
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html#sec3.9
     * 3.9 Quality Values
     * qvalue         = ( "0" [ "." 0*3DIGIT ] )
     *                | ( "1" [ "." 0*3("0") ] )
     */
    preg_match_all("/([a-z]{1,8})(?:-([a-z]{1,8}))?(?:\s*;\s*q\s*=\s*(1|1\.0{0,3}|0|0\.[0-9]{0,3}))?\s*(?:,|$)/i",
                   $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);

    // Результат по умолчанию - первый из доступных языков
    $result = $languages[0];
    $max_q = 0;

    for ($i = 0; $i < count($matches[1]); $i++) {
        // Выделяем очередной язык
        $lang = $matches[1][$i];
        if (!empty($matches[2][$i])) {
            // Переводим ru-RU в ru_RU (т.к. локаль ru_RU)
            $lang .= '_'.$matches[2][$i];
        }

        // Определяем приоритет
        if (!empty($matches[3][$i])) {
            $q = (float)$matches[3][$i];
        } else {
            $q = 1.0;
        }
        // Проверяем есть ли проверяемый язык в массиве доступных
        if (in_array($lang, $languages) && ($q > $max_q)) {
            $result = $lang;
            $max_q = $q;
        }
        // Если язык только из первой части (например, просто ru, а не ru-RU) и более приоритетный язык еще не найден,
        // то пробуем найти в массиве доступых языков тот, который начинается так же
        elseif (empty($matches[2][$i]) && ($q * 0.8 > $max_q)) {
            $n = strlen($lang);
            foreach ($languages as $l) {
                if (!strncmp($l, $lang, $n)) {
                    $result = $l;
                    // Поскольку не точное совпадение, то уменьшаем q на 20%
                    $max_q = $q * 0.8;
                    break;
                }
            }
        }
    }

    switch (substr($result,0,2))
    {
	case 'ru':
		$result = 'russian';
		break;
	case 'uk':
		$result = 'ukrainian';
		break;
	case 'de':
		$result = 'german';
		break;
	case 'en':
		$result = 'english';
		break;
	case 'it':
		$result = 'italian';
		break;
	case 'fr':
		$result = 'french';
		break;
	default:
		$result = 'english';
		break;
    }
//echo $result;
    return $result;
}

/*
	Function to show GMT DateTime.
*/
function get_timezones($handle = null, $clause = null) {
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();
	if (!is_null($clause)) 
		$clause = 'WHERE '.$clause; 
	$QUERY = "SELECT id, gmttime, gmtzone, gmtoffset FROM cc_timezone $clause ORDER by id";
	$result = $instance_table->SQLExec($handle, $QUERY, 1, 300);
	
	if (is_array($result)) {
		$num_cur = count($result);
		for ($i = 0; $i < $num_cur; $i++) {
			$timezone_list[$result[$i][0]] = array (
				1 => $result[$i][1],
				2 => $result[$i][2],
				3 => $result[$i][3]
			);
		}
	}

	return $timezone_list;
}

function display_GMT($currDate, $number, $fulldate = 1)
{	
	$timezone_list = get_timezones(null, "gmttime = '".SERVER_GMT."'");
	foreach ($timezone_list as $key => $timezone_list_val) {
		$server_offset = $timezone_list_val[3];
		break;
	}
	
	$date_time_array = getdate(strtotime($currDate));
	$hours = $date_time_array['hours'];
	$minutes = $date_time_array['minutes'];
	$seconds = $date_time_array['seconds'];
	$month = $date_time_array['mon'];
	$day = $date_time_array['mday'];
	$year = $date_time_array['year'];
	$timestamp = mktime($hours, $minutes, $seconds, $month, $day, $year);
	
	$timestamp = $timestamp - ($server_offset - $number);
	/*
	if ($fulldate == 1) {
		$gmdate = gmdate("Y-m-d H:i:s", $timestamp);
	} else {
		$gmdate = gmdate("Y-m-d", $timestamp);
	}
	return $gmdate;
	*/
	
	if ($fulldate == 1) {
		$date = date("Y-m-d H:i:s", $timestamp);
	} else {
		$date = date("Y-m-d", $timestamp);
	}
	return $date;
}


function check_translated($id, $languages, $mailtype)
{
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();

	$QUERY = "SELECT id FROM cc_templatemail WHERE mailtype = '$mailtype' AND id_language = '$languages'";
	$result = $instance_table->SQLExec($handle, $QUERY);
	if (is_array($result)) {
		if (count($result) > 0)
			return true;
		else
			return false;
	} else {
		return false;
	}

}

function update_translation($id, $languages, $subject, $mailtext, $mailtype) {
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$param_update = "subject = '$subject', messagetext = '$mailtext'";
	$clause = "mailtype = '$mailtype' AND id_language = '$languages'";
	$func_table = 'cc_templatemail';
	$update = $instance_table->Update_table($handle, $param_update, $clause, $func_table);
	return $update;
}

function insert_translation($id, $languages, $subject, $mailtext, $mailtype) {
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$fromemail = '';
	$fromname = '';
	$QUERY = "SELECT fromemail, fromname, mailtype FROM cc_templatemail WHERE mailtype = '$mailtype' AND id_language = 'en'";
	$result = $instance_table->SQLExec($handle, $QUERY);
	if (is_array($result)) {
		if (count($result) > 0) {
			$fromemail = $result[0][0];
			$fromname = $result[0][1];
			$mailtype = $result[0][2];
		}
	}

	$value = "'$languages', '$subject', '$mailtext', '$mailtype','$fromemail','$fromname'";
	$func_fields = "id_language, subject, messagetext, mailtype, fromemail, fromname";
	$func_table = 'cc_templatemail';
	$id_name = "id";
	$inserted = $instance_table->Add_table($handle, $value, $func_fields, $func_table, $id_name);
	return $inserted;
}

function mailtemplate_latest_id() {
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();

	$QUERY = "SELECT max(id) as latest_id from cc_templatemail where id_language = 'en'";
	$result = $instance_table->SQLExec($handle, $QUERY);
	$result[0][0] = $result[0][0] + 1;
	return $result[0][0];

}

function get_db_languages($handle = null) {
	if (empty ($handle)) {
		$handle = DbConnect();
	}
	$instance_table = new Table();
	$QUERY = "SELECT code, name from cc_iso639 order by code";
	$result = $instance_table->SQLExec($handle, $QUERY);

	return $result;
}

/*
 * Function use to archive data and call records
 * Insert in cc_call_archive and cc_card_archive on seletion criteria
 * Delete from cc_call and cc_card
 * Used in
 * 1. A2Billing_UI/Public/A2B_data_archving.php
 * 2. A2Billing_UI/Public/A2B_call_archiving.php
 */

function archive_data($condition, $entity = "")
{	
	$handle = DbConnect();
	$instance_table = new Table();
	if (!empty ($entity)) {
		if ($entity == "card") {
			$func_fields = "id, creationdate, firstusedate, expirationdate, enableexpire, expiredays, username, useralias, uipass, credit, tariff, id_didgroup, activated, status, lastname, firstname, address, city, state, country, zipcode, phone, email, fax, inuse, simultaccess, currency, lastuse, nbused, typepaid, creditlimit, voipcall, sip_buddy, iax_buddy, language, redial, runservice, nbservice, id_campaign, num_trials_done, vat, servicelastrun, initialbalance, invoiceday, autorefill, loginkey, mac_addr, id_timezone, tag, voicemail_permitted, voicemail_activated, last_notification, email_notification, notify_email, credit_notification, id_group, company_name, company_website, VAT_RN, traffic, traffic_target, discount, restriction";
			$value = "SELECT $func_fields FROM cc_card $condition";
			$func_table = 'cc_card_archive';
			$id_name = "";
			$subquery = true;
			$result = $instance_table->Add_table($handle, $value, $func_fields, $func_table, $id_name, $subquery);
			$fun_table = "cc_card";
			if (strpos($condition, 'WHERE') > 0) {
				$condition = str_replace("WHERE", "", $condition);
			}

			$result = $instance_table->Delete_table($handle, $condition, $fun_table);
		} else
			if ($entity == "call") {
				$value = "SELECT id, sessionid,uniqueid,card_id,nasipaddress,starttime,stoptime,sessiontime,calledstation,sessionbill,id_tariffgroup,id_tariffplan,id_ratecard,id_trunk,sipiax,src,id_did,buyrate,id_card_package_offer,real_sessiontime FROM cc_call $condition";
				$func_fields = "id, sessionid,uniqueid,card_id,nasipaddress,starttime,stoptime,sessiontime,calledstation,sessionbill,id_tariffgroup,id_tariffplan,id_ratecard,id_trunk,sipiax,src,id_did,buyrate,id_card_package_offer,real_sessiontime";
				$func_table = 'cc_call_archive';
				$id_name = "";
				$subquery = true;
				$result = $instance_table->Add_table($handle, $value, $func_fields, $func_table, $id_name, $subquery);
				if (strpos($condition, 'WHERE') > 0) {
					$condition = str_replace("WHERE", "", $condition);
				}
				$fun_table = "cc_call";
				$result = $instance_table->Delete_table($handle, $condition, $fun_table);
			}
	}
	return 1;
}

/*
 * Function use to define exact sql statement for
 * different criteria selection
 */
function do_field($sql, $fld, $dbfld, $oro=false, $sko=0) {
	$fldtype = $fld . 'type';
	global $$fld;
	global $$fldtype;
	
	if ($$fld) {
		if (strpos($sql, 'WHERE') > 0) {
			if ($oro) $sql .= " OR ";
			else $sql .= " AND ";
			if ($sko==1) $sql .= "(";
		} else {
			$sql .= " WHERE ";
			if ($sko==1) $sql .= "(";
		}
		$sql .= "($dbfld";
		$args = explode(",", $$fld);
		foreach ($args as $value) {
//		    if (is_numeric($value)) {
			if (isset ($$fldtype)) {
			    switch ($$fldtype) {
				case 1 :
					$sql .= "='" . $value . "'";
					break;
				case 2 :
					$sql .= " LIKE '" . $value . "%'";
					break;
				case 3 :
					$sql .= " LIKE '%" . $value . "%'";
					break;
				case 4 :
					$sql .= " LIKE '%" . $value . "'";
			    }
			} else {
			    $sql .= " LIKE '%" . $value . "%'";
			}
			if ($value !== end($args))	$sql .= " OR $dbfld";
//		    }
		}
		$sql .= ")";
		if ($sko==2) $sql .= ")";
	}
	return $sql;
}

// Update currency exchange rate list from finance.yahoo.com.
// To work around yahoo truncating to 4 decimal places before
// doing a division, leading to >10% errors with weak base_currency,
// we always request in a strong currency and convert ourselves.
// We use ounces of silver,  as if silver ever devalues significantly
// we'll all be pretty much boned anyway,  wouldn't you say?
function currencies_update_yahoo ($DBHandle, $instance_table)
{
	$FG_DEBUG = 0;
	$strong_currency = 'EUR';
	$url = "http://download.finance.yahoo.com/d/quotes.csv?s=";
	$return = "";
	
	$QUERY = "SELECT id, currency, basecurrency, value FROM cc_currencies ORDER BY id";
	$old_currencies = $instance_table->SQLExec($DBHandle, $QUERY);
	// we will retrieve a .CSV file e.g. USD to EUR and USD to CAD with a URL like:
	// http://download.finance.yahoo.com/d/quotes.csv?s=USDEUR=X+USDCAD=X&f=l1
	if (is_array($old_currencies)) {
		$num_cur = count($old_currencies);
		if ($FG_DEBUG >= 1)
			$return .= basename(__FILE__) . ' line:' . __LINE__ . "[CURRENCIES TO UPDATE = $num_cur]\n";
		for ($i = 0; $i < $num_cur; $i++) {
			if ($FG_DEBUG >= 1)
				$return .= $old_currencies[$i][0] . ' - ' . $old_currencies[$i][1] . ' - ' . $old_currencies[$i][2] . "\n";
			// Finish and add termination ?
			if ($i +1 == $num_cur) {
				$url .= $strong_currency . $old_currencies[$i][1] . "=X&f=l1";
			} else {
				$url .= $strong_currency . $old_currencies[$i][1] . "=X+";
			}

			// Save the index of base_currency when we find it
			if (strcasecmp(BASE_CURRENCY, $old_currencies[$i][1]) == 0) {
				$index_base_currency = $i;
			}
		}

		// Check we found the index of base_currency
		if (!isset ($index_base_currency)) {
			return gettext("Can't find our base_currency in cc_currencies.") . ' ' . gettext('Currency update ABORTED.');
		}

		// Call wget to download the URL to the .CVS file
		$command = "/usr/bin/wget '" . $url . "' -O /tmp/currencies.cvs 2>&1";
		exec($command, $output);
		if ($FG_DEBUG >= 1)
			$return .= "/usr/bin/wget '" . $url . "' -O /tmp/currencies.cvs\n" . $output;

		// get the file with the currencies to update the database
		$currencies = file("/tmp/currencies.cvs");

		// trim off any leading/trailing comments/headers that may have been added
		$i = 0;
		while (isset($currencies[$i]) && !is_numeric(trim($currencies[$i])) && trim($currencies[$i]) != "N/A") {
			$i++;
		}
/**		$num_res = count($currencies);
		for ($i = 0; $i < $num_res; $i++) {
			if (is_numeric(trim($currencies[$i])) || trim($currencies[$i]) == "N/A") {
				
				break;
			}
		}
**/		$currencies = array_slice($currencies, $i, $num_cur);

		// do some simple checks to try to verify we've received exactly one
		// valid response for each currency we requested
		$num_res = count($currencies);
		if ($num_res < $num_cur) {
			return gettext("The CSV file doesn't contain all the currencies we requested.") . ' ' . gettext('Currency update ABORTED.');
		}
		for ($i = 0; $i < $num_cur; $i++) {
			if (!is_numeric(trim($currencies[$i]))) {
				if (trim($currencies[$i]) == "N/A")		$currencies[$i] = $old_currencies[$i][3];
				else return gettext("At least one of the entries in the CSV file isn't a number.").' '.$old_currencies[$i][1].' = '.$currencies[$i].'. '.gettext('Currency update ABORTED.');
			}
		}
		
		// Find base_currency's value in $strong_currency to help avoid Yahoo's
		// early truncation,  and therefore win back a lot of accuracy
		$base_value = $currencies[$index_base_currency];

		// Check our base_currency will still fund our addiction to tea and biscuits
		if (round($base_value, 5) < 0.00001) {
			return gettext('Our base_currency seems to be worthless.') . ' ' . gettext('Currency update ABORTED.');
		}

		// update each row we originally retrieved from cc_currencies
		$i = 0;
		foreach ($currencies as $currency) {

			$currency = trim($currency);

			if ($currency != 0) {
				$currency = $base_value / $currency;
			}
			if ($old_currencies[$i][1] == 'UAH') {
				if ($currency > $old_currencies[$i][3])
					$currency = $old_currencies[$i][3];
				$url = "http://kurs.com.ua/ajax/valyuta_table/all/".date("d.m.Y")."/".strtolower(BASE_CURRENCY);
				$command = "/usr/bin/wget '" . $url . "' -O /tmp/currency-uah.json 2>&1";
				exec($command, $output);
				$currency_uah = json_decode(file_get_contents("/tmp/currency-uah.json"),true,512);
				$currency_uah = strstr($currency_uah['table'],BASE_CURRENCY);
				$dlm = "<span>";
				if ($currency_uah === false) {
					$url = "http://m.kurs.com.ua/ajax/main_table/all/".strtolower(BASE_CURRENCY)."/".date("Y-m-d")."/bank";
					$command = "/usr/bin/wget '" . $url . "' -O /tmp/currency-uah.cvs 2>&1";
					exec($command, $output);
					$currency_uah = strstr(file_get_contents("/tmp/currency-uah.cvs"),BASE_CURRENCY);
					$dlm = "t";
				}
				if ($currency_uah !== false) {
					preg_match_all("/(?<=".$dlm.")\d*\.\d*/", $currency_uah, $uah);
					if (isset($uah[0][2]) && $uah[0][2] > 1/$currencies[$i])	$currency = 1/$uah[0][2];
				}
			}
//if ($old_currencies[$i][1] == 'RUB')		$currency = $currency*0.83;
//if ($old_currencies[$i][1] == 'USD')		$currency = $currency*0.96;
			//  extremely weak currencies are assigned the smallest value the schema permits
			if (round($currency, 5) < 0.00001) {
				$currency = '0.00001';
			}

			// if the currency is base_currency then set to exactly 1.00000
			if ($i == $index_base_currency)
				$currency = 1;

			$QUERY = "UPDATE cc_currencies SET value='$currency'";

			// if we've changed base_currency,  update each SQL row to reflect this
			if (BASE_CURRENCY != $old_currencies[$i][2]) {
				$QUERY .= ", basecurrency='" . strtoupper(BASE_CURRENCY) . "'";
			}

			$QUERY .= " , lastupdate = CURRENT_TIMESTAMP WHERE id ='" . $old_currencies[$i][0] . "'";
			$result = $instance_table->SQLExec($DBHandle, $QUERY, 0);
			if ($FG_DEBUG >= 1)
				$return .= "$QUERY -> [$result]\n";

			$i++;
			if ($i > 2000)
				return $return;
		}
		$return .= gettext('Success! All currencies are now updated.');
	}
	return $return;
}

/*
 * arguments - function to handle arguments in CLI script
 */
function arguments($argv) {
	$_ARG = array ();
	array_shift($argv); //skip argv[0] !
	foreach ($argv as $arg) {
		if (preg_match('/--([^=]+)=(.*)/', $arg, $reg)) {
			$_ARG[$reg[1]] = $reg[2];
		}
		elseif (preg_match('/--([^=]+)/', $arg, $reg)) {
			$_ARG[$reg[1]] = true;
		}
		elseif (preg_match('/^-([a-zA-Z0-9])/', $arg, $reg)) {
			$_ARG[$reg[1]] = true;
		} else {
			$_ARG['input'][] = $arg;
		}
	}
	return $_ARG;
}

function generate_invoice_reference() {
	$handle = DbConnect();
	$year = date("Y");
	$invoice_conf_table = new Table('cc_invoice_conf', 'value');
	$conf_clause = "key_val = 'count_$year'";
	$result = $invoice_conf_table->Get_list($handle, $conf_clause, 0);
	
	if (is_array($result) && !empty ($result[0][0])) {
		$count = $result[0][0];
		if (!is_numeric($count)) {
			$count = 0;
		}
		$count++;
		$param_update_conf = "value ='" . $count . "'";
		$clause_update_conf = "key_val = 'count_$year'";
		$invoice_conf_table->Update_table($handle, $param_update_conf, $clause_update_conf, $func_table = null);
	} else {
		//insert newcount
		$count = 1;
		$QUERY = "INSERT INTO cc_invoice_conf (key_val ,value) VALUES ( 'count_$year', '1');";
		$invoice_conf_table->SQLExec($handle, $QUERY);
	}
	$reference = $year . sprintf("%08d", $count);
	return $reference;
}

function check_demo_mode()
{
	if (DEMO_MODE) {
		if (strpos($_SERVER['HTTP_REFERER'], '?') === false)
			Header("Location: " . $_SERVER['HTTP_REFERER'] . "?msg=nodemo");
		else
			Header("Location: " . $_SERVER['HTTP_REFERER'] . "&msg=nodemo");

		die();
	}
}

function check_demo_mode_intro()
{
	if (DEMO_MODE) {
		Header("Location: PP_intro.php?msg=nodemo");
		die();
	}
}

function isLuhnNum($num)
{
	$length = strlen($num);
	$tot = 0;
	for($i=$length-1;$i>=0;$i--)
	{
		$digit = substr($num, $i, 1);
		if ((($length - $i) % 2) == 0)
		{
			$digit = $digit*2;
			if ($digit>9)
			{
				$digit = $digit-9;
			}
		}
		$tot += $digit;
	}
	return (($tot % 10) == 0);
}

/*
 * Checks the day of month for date related forms and reduces the day to the last valid day of the month if too large.
 * Inputs: &$day: 'xx' from '01' to '31'
 *         $year_month: 'xxxx-mm'
 *         $inplace : 1 == edit day in place.
 * Return  normalized day (integer)
 */
function normalize_day_of_month(&$day, $year_month, $inplace=0)
{
	if( isset($day) && isset($year_month)) {
		$year_month_ary = preg_split('/-/', $year_month);
		$year = (int) $year_month_ary[0];
		$month = (int) $year_month_ary[1];
		$normalized_day = min( (int) $day, cal_days_in_month(CAL_GREGORIAN, $month, $year) );
		if($inplace == 1) $day = $normalized_day;
		return $normalized_day;
	}
}




// mt_get: returns the current microtime
function mt_get()
{
    global $mt_time;
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
 
// mt_start: starts the microtime counter
function mt_start()
{
    global $mt_time; $mt_time = mt_get();
}
 
// mt_end: calculates the elapsed time
function mt_end($len=4)
{
    global $mt_time;
    $time_end = mt_get();
    return round($time_end - $mt_time, $len);
}



/*
   * @return string
   * @param string $url
   * @desc Return string content from a remote file
*/

function open_url($url)
{
    $ch = curl_init();

    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HEADER, 0);

    ob_start();

    curl_exec ($ch);
    curl_close ($ch);
    $string = ob_get_contents();

    ob_end_clean();
   
    return $string;    
}


/*
 * function retrieve_rates_callplan
 */
function retrieve_rates_callplan($callplan_id, $DBHandle)
{
    $instance_table = new Table();
    
    $QUERY = "SELECT DISTINCT cc_ratecard.destination, cc_ratecard.dialprefix, cc_ratecard.buyrate, cc_ratecard.rateinitial, cc_ratecard.startdate, cc_ratecard.stopdate, cc_ratecard.initblock, " .
    		"cc_ratecard.connectcharge, cc_ratecard.id_trunk , cc_ratecard.idtariffplan , cc_ratecard.id FROM cc_tariffgroup RIGHT JOIN cc_tariffgroup_plan ON cc_tariffgroup_plan.idtariffgroup=cc_tariffgroup.id " .
    		"INNER JOIN cc_tariffplan ON (cc_tariffplan.id=cc_tariffgroup_plan.idtariffplan ) LEFT JOIN cc_ratecard ON cc_ratecard.idtariffplan=cc_tariffplan.id WHERE cc_tariffgroup.id= '$callplan_id' " .
    		"AND cc_ratecard.rateinitial = (SELECT min(c1.rateinitial) FROM cc_tariffgroup RIGHT JOIN cc_tariffgroup_plan ON cc_tariffgroup_plan.idtariffgroup=cc_tariffgroup.id " .
    		"INNER JOIN cc_tariffplan ON (cc_tariffplan.id=cc_tariffgroup_plan.idtariffplan ) LEFT JOIN cc_ratecard AS c1 ON c1.idtariffplan=cc_tariffplan.id WHERE cc_tariffgroup.id= '$callplan_id' " .
    		"AND cc_ratecard.dialprefix=c1.dialprefix) ORDER BY destination ASC";
	
	$result = $instance_table -> SQLExec ($DBHandle, $QUERY, 1, 3600); // cached for 1hour
	
	if (!is_array($result)) {
	    return false;
	}
	
	return $result;
	
}

/*
 * function
 */
function check_cp()
{
	$randn = rand(1, 10);
	$ret_val = ($randn == 5)? 1 : 0;
	
	$pos_star = strpos(COPYRIGHT, 'star2billing');
	if ($pos_star === false) {
		return $ret_val;
	}
	$pageURL = get_curPageURL();
    $pos = strpos($pageURL, 'phpsysinfo');
    
    if ($pos === false) {
        
        $footer_content = file_get_contents("templates/default/footer.tpl");

        $pos_copyright = strpos($footer_content, '$COPYRIGHT');
        if ($pos_copyright === false) {
            return $ret_val;
        }
    }
	return 0;
}

function get_curPageURL($show_get = 0) {
	$pageURL = 'http';
	if (array_key_exists('HTTPS', $_SERVER) && ($_SERVER["HTTPS"] == "on")) {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}

	$pos = strpos($pageURL, '?');

	if ($show_get || ($pos === false))
		return $pageURL;


	$pageURL = substr($pageURL, 0, strpos($pageURL, '?'));

	return $pageURL;
}

//////////////////////////////////////////////////////
// Get the last day of the month
function lastDayOfMonth($month = '', $year = '' , $format = 'd-m-Y')
{
   if (empty($month)) {
      $month = date('m');
   }
   if (empty($year)) {
      $year = date('Y');
   }
   $result = strtotime("{$year}-{$month}-01");
   $result = strtotime('-1 second', strtotime('+1 month', $result));
   return date($format, $result);
}

function addRealMonth($timeStamp)
{
    // Check if it's the end of the year and the month and year need to be changed
    $tempMonth = date('m', $timeStamp);
    $tempYear  = date('Y', $timeStamp);
    if($tempMonth == "12")
    {
        $tempMonth = 1;
        $tempYear++;
    }
    else
        $tempMonth++;

    $newDate = lastDayOfMonth($tempMonth, $tempYear);
    return strtotime($newDate);
}


function Display_Login_Button ($DBHandle, $id) {
	
	$inst_table = new Table("cc_card", "useralias, uipass");
	$FG_TABLE_CLAUSE = "id = $id";
	$list_card_info = $inst_table -> Get_list ($DBHandle, $FG_TABLE_CLAUSE);
	$username = $list_card_info[0][0];
	$password = $list_card_info[0][1];
	$link = CUSTOMER_UI_URL;

	if (strpos($link, 'index') !== false) {
		$link = substr($link, 0, strlen($link)-9) . 'userinfo';
	} else {
		$link = $link . '/userinfo';
	}
	$content = '<div align="right" style="padding-right:20px;">
		<form action="'.$link.'" method="POST" target="_blank" name="formcust">
			<input type="hidden" name="done" value="submit_log"/>
			<input type="hidden" name="pr_login" value="'.$username.'"/>
			<input type="hidden" name="pr_password" value="'.$password.'"/>
			<a href="javascript:;" onclick="javascript:document.formcust.submit();" > '.gettext("GO TO CUSTOMER ACCOUNT").'</a>
		</form>
	</div>';
	return $content;
}


function regex_range($from, $to, $exclude = NULL) {

	if($from < 0 || $to < 0) {
	    throw new Exception("Negative values not supported");
	}
	if($from > $to) {
		throw new Exception("Invalid range $from..$to, from > to");
	}

	$ranges = array($from);
	$increment = 1;
	$next = $from;
	$higher = true;

	while(true) {

		$next += $increment;

		if($next + $increment > $to) {
			if($next <= $to) {
				$ranges[] = $next;
			}
			$increment /= 10;
			$higher = false;
		}
		else if($next % ($increment*10) === 0) {
			$ranges[] = $next;
			$increment = $higher ? $increment*10 : $increment/10;
		}

		if(!$higher && $increment < 10) {
			break;
		}
	}

	$ranges[] = $to + 1;
	$excludeseparated = implode("|",$exclude);
	$regex = "^(?:";
	$regex .= ($excludeseparated != "") ? "(?!".$excludeseparated.")" : $excludeseparated;
	$regex .= "(";
	for($i = 0; $i < sizeof($ranges) - 1; $i++) {
		$str_from = (string)($ranges[$i]);
		$str_to = (string)($ranges[$i + 1] - 1);
		for($j = 0; $j < strlen($str_from); $j++) {
			if($str_from[$j] == $str_to[$j]) {
				$regex .= $str_from[$j];
			}
			else {
				$regex .= "[" . $str_from[$j] . "-" . $str_to[$j] . "]";
			}
		}
		$regex .= "|";
	}

	return substr($regex, 0, strlen($regex)-1) . '))$';
}

function test($from, $to) {
	try {
		printf("%-10s %s\n", $from . '-' . $to, regex_range($from, $to));
	} catch (Exception $e) {
		echo $e->getMessage() . "\n";
	}
}

function translit($string) {
	$arStrES = array("ае","уе","ое","ые","ие","эе","яе","юе","ёе","ее","ье","ъе","ый","ий");
	$arStrOS = array("аё","уё","оё","ыё","иё","эё","яё","юё","ёё","её","ьё","ъё","ый","ий");
	$arStrRS = array("а$","у$","о$","ы$","и$","э$","я$","ю$","ё$","е$","ь$","ъ$","@","@");
	$replace = array("А"=>"A","а"=>"a","Б"=>"B","б"=>"b","В"=>"V","в"=>"v","Г"=>"G","г"=>"g","Д"=>"D","д"=>"d",
			"Е"=>"Ye","е"=>"e","Ё"=>"Ye","ё"=>"e","Ж"=>"Zh","ж"=>"zh","З"=>"Z","з"=>"z","И"=>"I","и"=>"i",
			"Й"=>"Y","й"=>"y","К"=>"K","к"=>"k","Л"=>"L","л"=>"l","М"=>"M","м"=>"m","Н"=>"N","н"=>"n",
			"О"=>"O","о"=>"o","П"=>"P","п"=>"p","Р"=>"R","р"=>"r","С"=>"S","с"=>"s","Т"=>"T","т"=>"t",
			"У"=>"U","у"=>"u","Ф"=>"F","ф"=>"f","Х"=>"Kh","х"=>"kh","Ц"=>"Ts","ц"=>"ts","Ч"=>"Ch","ч"=>"ch",
			"Ш"=>"Sh","ш"=>"sh","Щ"=>"Shch","щ"=>"shch","Ъ"=>"","ъ"=>"","Ы"=>"Y","ы"=>"y","Ь"=>"","ь"=>"",
			"Э"=>"E","э"=>"e","Ю"=>"Yu","ю"=>"yu","Я"=>"Ya","я"=>"ya","@"=>"y","$"=>"ye");
	$string = str_replace($arStrES, $arStrRS, $string);
	$string = str_replace($arStrOS, $arStrRS, $string);
	return iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
}
