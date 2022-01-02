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


$FG_DEBUG = 0;
error_reporting(E_ALL & ~E_NOTICE);

define ("MODULE_ACCESS_DOMAIN",		"A2Billing - VoIP Billing Software");
define ("MODULE_ACCESS_DENIED",		"./Access_denied.htm");

define ("ACX_ACCESS",						      1);
define ("ACX_PASSWORD", 					      2);
define ("ACX_SIP_IAX",						      4);	// 1 << 1
define ("ACX_CALL_HISTORY",					      8);	// 1 << 2
define ("ACX_PAYMENT_HISTORY",					     16);	// 1 << 3
define ("ACX_VOUCHER",						     32);	// 1 << 4
define ("ACX_INVOICES", 					     64);	// 1 << 5
define ("ACX_DID",						    128);	// 1 << 6
define ("ACX_SPEED_DIAL",					    256);	// 1 << 7
define ("ACX_RATECARD", 					    512);	// 1 << 8
define ("ACX_SIMULATOR",					   1024);	// 1 << 9
define ("ACX_CALL_BACK",					   2048);	// 1 << 10
define ("ACX_WEB_PHONE",					   4096);	// 1 << 11
define ("ACX_CALLER_ID",					   8192);	// 1 << 12
define ("ACX_SUPPORT",						  16384);	// 1 << 14
define ("ACX_NOTIFICATION",					  32768);	// 1 << 15
define ("ACX_AUTODIALER",					  65536);	// 1 << 16
define ("ACX_PERSONALINFO",					 131072);
define ("ACX_SEERECORDING",					 262144);
define ("ACX_DISTRIBUTION",					 524288);
define ("ACX_SURVEILLANCE",					1048576);

//header("Expires: Sat, Jan 01 2000 01:01:01 GMT");

if(strlen(RETURN_URL_DISTANT_LOGIN)>1) {
    $C_RETURN_URL_DISTANT_LOGIN = RETURN_URL_DISTANT_LOGIN;
} else {
    $C_RETURN_URL_DISTANT_LOGIN = 'index';
}

if (isset($_GET["logout"]) && $_GET["logout"]=="true") {
//	if(stripos($URI, "logout.php")===false && isset($_SESSION["card_id"])) {
	    $log = new Logger();
	    $log -> insertLog($_SESSION["card_id"], 1, "LOGGED OUT", "User Logged out from website", '', $_SERVER['REMOTE_ADDR'], '', '', 2);
	    $log = null;
//	}
	session_destroy();
	$cus_rights=0;
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: .");
	die();
}

getpost_ifset (array('pr_login', 'pr_password', 'done'));

function sendSignin($error,$signinString) {
    header("Content-type: text/xml");
    echo "<response><error>$error</error><signinString><![CDATA[$signinString]]></signinString></response>";
    die();
}

if (!isset($_SESSION['pr_login']) || !isset($_SESSION['pr_password']) || !isset($_SESSION['cus_rights']) || (isset($_POST["done"]) && ($done=="submit_log" || $done=="submit_sig"))){

	if ($FG_DEBUG == 1) echo "<br>0. HERE WE ARE";

	if ($done=="submit_log" || $done=="submit_sig") {
		if (!isset ($_SESSION["date_forgot"]) || (time() - $_SESSION["date_forgot"]) > 5) {
                        $_SESSION["date_forgot"] = time();
                } else {
                        sendSignin(9,gettext("Too frequent requests"));
                }

		$DBHandle  = DbConnect();
		if ($FG_DEBUG == 1) echo "<br>1. ".$pr_login." - ".$pr_password;
		
		$return = login ($pr_login, $pr_password);
		if ($FG_DEBUG == 1) print_r($return);
		if ($FG_DEBUG == 1) echo "==>".$return[1];
		
		if (!is_array($return)) {
        		if (is_int($return)) {
            		    if ($return == -1) {
			        sendSignin(3,gettext("BLOCKED ACCOUNT :<br>Please contact the administrator!"));
            		    } elseif ($return == -2) {
			        sendSignin(4,gettext("NEW ACCOUNT :<br>Your account has not been validate yet!"));
            		    } else {
			        sendSignin(2,gettext("INACTIVE ACCOUNT :<br>Your account need to be activated!"));
            		    }
        		} else {
			        sendSignin(1,gettext("AUTHENTICATION REFUSED :<br>please check your login/password!"));
        		}
			header ("HTTP/1.0 401 Unauthorized");
			die();
		}
		
		$cust_default_right=1;
		if ($pr_login) {
			
			$pr_login = $return[0];
			
			if ($FG_DEBUG == 1)
				echo "<br>3. $pr_login-$pr_password-$cus_rights";
			
			$_SESSION["pr_login"]=$pr_login;
			$_SESSION["pr_password"]=$pr_password;
			
			if(empty($return[10])) {
				$_SESSION["cus_rights"]=$cust_default_right;
			} else {
				$_SESSION["cus_rights"]=$return[10]+$cust_default_right;
			}
						
			$_SESSION["user_type"]		= "CUST";
			$_SESSION["card_id"]		= $return[3];
			$_SESSION["id_didgroup"]	= $return[4];
			$_SESSION["tariff"]		= $return[5];
			$_SESSION["vat"]		= $return[6];
			$_SESSION["gmtoffset"]		= $return[7];
			$_SESSION["voicemail"]		= $return[8];
			$_SESSION["currency"]		= $return[11];
			$_SESSION["email"]		= $return[12];
			$_SESSION["margin_diller"]	= $return[13];
			$_SESSION["timezone"]		= $return[14];
			$_SESSION["dillertariffs"]	= $return[15];
			$_SESSION["dillergroups"]	= $return[16];
			$_SESSION["paypal"]		= $return[17];
			$_SESSION["simultaccess"]	= $return[18];
			
			$QUERY = "SELECT ipaddress from cc_system_log WHERE agent=0 ORDER BY creationdate DESC LIMIT 1";
			$res = $DBHandle -> Execute($QUERY);
			if ($res!==false) {
				$row [] = $res -> fetchRow();
				$_SESSION["admin_ip"] = $row [0][0];
			}
			
			$log = new Logger();
			$log -> insertLog($return[3], 1, "<b>LOGGED IN</b>", "User Logged in to website", '', $_SERVER['REMOTE_ADDR'], '', '', 2);
			$log = null;

			if ($_POST["done"]=="submit_sig") {
			    sendSignin(0,gettext("SUCCESS"));
			}
		}
	} else {
		$_SESSION["cus_rights"]=0;
	}
}


// Functions

function login ($user, $pass)
{
	global $DBHandle;
	$user = trim($user);
	$pass = trim($pass);
	if (strlen($user)==0 || strlen($user)>=50 || strlen($pass)==0 || strlen($pass)>=50) return false;

    $user = filter_var($user, FILTER_SANITIZE_STRING);
    $pass = filter_var($pass, FILTER_SANITIZE_STRING);

//	$QUERY = "SELECT cc.username, cc.credit, cc.status, cc.id, cc.id_didgroup, cc.tariff, cc.vat, IF(CONCAT(cc.id_timezone+0) = cc.id_timezone, ct.gmtoffset, (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(CONVERT_TZ(NOW(), SUBSTRING_INDEX(cc.id_timezone, ';', -1), 'UTC')))), cc.voicemail_permitted, " .
	$QUERY = "SELECT cc.username, cc.credit, cc.status, cc.id, cc.id_didgroup, cc.tariff, cc.vat, IF(CONCAT(cc.id_timezone+0) = cc.id_timezone, ct.gmtoffset, 0), cc.voicemail_permitted, cc.voicemail_activated, cc_card_group.users_perms, " .
			 "cc.currency, cc.email, cc.margin_diller, IF(CONCAT(cc.id_timezone+0) = cc.id_timezone, IF(ct.gmttime='GMT', ct.gmttime, SUBSTRING(ct.gmttime,4,6)), SUBSTRING_INDEX(cc.id_timezone, ';', -1)), cc.dillertariffs, cc.dillergroups, cc.paypal, simultaccess " .
			 "FROM cc_card cc LEFT JOIN cc_timezone AS ct ON ct.id = cc.id_timezone LEFT JOIN cc_card_group ON cc_card_group.id=cc.id_group " .
			 "WHERE (cc.email = '".$user."' OR cc.useralias = '".$user."') AND cc.uipass = '".$pass."'";
			 
	$res = $DBHandle -> Execute($QUERY);
	
	if (!$res) {
		$errstr = $DBHandle->ErrorMsg();
		return (false);
	}
	
	$row [] =$res -> fetchRow();

	if(!isset($row [0][2])) return (false);

	if($row [0][2] != "t" && $row [0][2] != "1"  && $row [0][2] != "8") {
		if ($row [0][2] == "2")
			return -2;
		elseif ($row [0][2] == "3")
			return -3;
		else
			return -1;
	}
	$_SESSION["user_alias"] = $user;
	return ($row[0]);
}



function has_rights ($condition)
{
	return ($_SESSION['cus_rights'] & $condition);
}


$ACXPASSWORD				= has_rights (ACX_PASSWORD);
$ACXSIP_IAX				= has_rights (ACX_SIP_IAX);
$ACXCALL_HISTORY			= has_rights (ACX_CALL_HISTORY);
$ACXPAYMENT_HISTORY			= has_rights (ACX_PAYMENT_HISTORY);
$ACXVOUCHER				= has_rights (ACX_VOUCHER);
$ACXINVOICES				= has_rights (ACX_INVOICES);
$ACXDID					= has_rights (ACX_DID);
$ACXSPEED_DIAL				= has_rights (ACX_SPEED_DIAL);
$ACXRATECARD				= has_rights (ACX_RATECARD);
$ACXSIMULATOR				= has_rights (ACX_SIMULATOR);
$ACXWEB_PHONE				= has_rights (ACX_WEB_PHONE);
$ACXCALL_BACK				= has_rights (ACX_CALL_BACK);
$ACXCALLER_ID				= has_rights (ACX_CALLER_ID);
$ACXSUPPORT				= has_rights (ACX_SUPPORT);
$ACXNOTIFICATION			= has_rights (ACX_NOTIFICATION);
$ACXAUTODIALER				= has_rights (ACX_AUTODIALER);
$ACXSEERECORDING			= has_rights (ACX_SEERECORDING);
$ACX_PERSONALINFO			= has_rights (ACX_PERSONALINFO);
$ACXDISTRIBUTION			= has_rights (ACX_DISTRIBUTION);
$ACXSURVEILLANCE			= has_rights (ACX_SURVEILLANCE);

if (ACT_VOICEMAIL) {
    $ACXVOICEMAIL 				= $_SESSION["voicemail"];
}
