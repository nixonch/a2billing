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


$disable_load_conf = true;

include ("lib/customer.defines.php");

getpost_ifset(array ('error', 'password', 'username', 'pr_email', 'action'));

function sendForgot($error,$forgotString) {
	header("Content-type: text/xml");
	echo "<response><error>$error</error><forgotString><![CDATA[$forgotString]]></forgotString></response>";
	die();
}

if (isset ($pr_email) && isset ($action)) {
	if ($action == "email") {
		if (!isset ($_SESSION["date_forgot"]) || (time() - $_SESSION["date_forgot"]) > 10) {
			$_SESSION["date_forgot"] = time();
		} else {
			sendForgot(9,gettext("Please wait 1 minute before making any other request!"));
		}
		$phone = $pr_email;
		$pr_email = filter_var(trim($pr_email), FILTER_VALIDATE_EMAIL);
		$DBHandle = DbConnect();
		if ($pr_email===false) {
		    if (is_numeric($phone)) {
			$num = 0;
			if (strlen($phone)>=10) {
			    $phone = (int)$phone;
			    $QUERY = "SELECT id, username, lastname, firstname, email, uipass, useralias, phone, UNIX_TIMESTAMP(NOW())-UNIX_TIMESTAMP(IFNULL(last_sms,0)) FROM cc_card WHERE phone LIKE '%" . $phone . "' ";
			    $res = $DBHandle->Execute($QUERY);
			    if ($res)
				$num = $res->RecordCount();
			    if (!$num) {
				$QUERY = "SELECT 1 FROM cc_callerid WHERE cid LIKE '%".$phone."' AND blacklist = 0";
				$res = $DBHandle->Execute($QUERY);
				if ($res) $num = $res->RecordCount();
				if ($num) {
				    sendForgot(6,gettext("To receiving SMS enter main phonenumber, please!"));
				}
			    }
			}
			if (!$num) {
			    sendForgot(6,gettext("No such phonenumber exists"));
			}
			for ($i = 0; $i < $num; $i++) {
			    $list[] = $res->fetchRow();
			}
			foreach ($list as $recordset) {
			    list ($id_card, $username, $lastname, $firstname, $email, $uipass, $cardalias, $phone, $secsleft) = $recordset;
			}
			$notmobile = ((strpos($phone,'49')===0 && strpos($phone,'491')!==0) || strpos($phone,'38044')===0) ? true : false;
			if (!D7_API_TOKEN || $secsleft<3600 || $notmobile) {
			    foreach ($list as $recordset) {
				list ($id_card, $username, $lastname, $firstname, $email, $uipass, $cardalias, $phone, $secsleft) = $recordset;
				if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) try {
				    $mail = new Mail(Mail::$TYPE_FORGETPASSWORD, $id_card);
				    $mail -> replaceInEmail(Mail::$CUSTOMER_INTERFACE, CUSTOMER_INTERFACE_URL);
				    $mail -> send();
				    $mailto .= "<br>".preg_replace("/(?!^).(?=[^@]+@)/", "*", $email);
				} catch (A2bMailException $e) {
				    $error_msg = $e->getMessage();
				}
			    }
			    if ($notmobile) {
				sendForgot(5,gettext("Your home phone can't receive SMS.<br>Login information has been sent to your e-mailbox.").$mailto);
			    } else {
				sendForgot(5,gettext("Your login information email<br>has been sent to you.").$mailto);
			    }
			}
			switch(LANGUAGE) {
			    case 'german'	: $message = "Login: $cardalias\nPasswort: $uipass"; break;
			    case 'russian'	: $message = "Логин: $cardalias\nПароль: $uipass"; break;
			    case 'ukrainian'	: $message = "Логін: $cardalias\nПароль: $uipass"; break;
			    default		: $message = "Login: $cardalias\nPassword: $uipass";
			}
			$client = new GuzzleHttp\Client(['headers' => ['Authorization' => 'Basic '.D7_API_TOKEN]]);
			$requestData = [
			    'coding' => 8,
			    'to' => $phone,
			    'hex_content' => bin2hex(mb_convert_encoding($message,'UTF-16BE')),
			    'from' => 'REMINDER'
			];
			try {
			    $response = $client->post('https://rest-api.d7networks.com/secure/send', ['json' => $requestData]);
			} catch (Exception $e) {
			    $response = $client->get('https://rest-api.d7networks.com/secure/balance');
			    if ($response->getStatusCode() == 200) {
				$body = json_decode($response->getBody(),true);
				$balance = '$'.$body['data']['balance'];
				$sms_count = $body['data']['sms_count'];
			    } else {
				$balance = $sms_count = 'N/A';
			    }
			    try {
				$mail = new Mail(Mail::$TYPE_SMS_ERROR);
				$mail->replaceInEmail(Mail::$PHONE_NUMBER, $phone);
				$mail->replaceInEmail(Mail::$ERR_MESS, $e->getMessage().'<br>Balance: '.$balance.'<br>SMS count: '.$sms_count);
				$mail->send(ADMIN_EMAIL);
			    } catch (A2bMailException $e) {
				$error_msg = $e->getMessage();
			    }
			    foreach ($list as $recordset) {
				list ($id_card, $username, $lastname, $firstname, $email, $uipass, $cardalias, $phone, $secsleft) = $recordset;
				if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) try {
				    $mail = new Mail(Mail::$TYPE_FORGETPASSWORD, $id_card);
				    $mail -> replaceInEmail(Mail::$CUSTOMER_INTERFACE, CUSTOMER_INTERFACE_URL);
				    $mail -> send();
				} catch (A2bMailException $e) {
				    $error_msg = $e->getMessage();
				}
			    }
			    sendForgot(7,gettext("SMS sender error.<br>Try again please."));
			}
			$DBHandle->Execute("UPDATE cc_card SET last_sms=NOW() WHERE id='$id_card'");
			sendForgot(5,gettext("Login information SMS<br>has been sent to your phonenumber"));
		    }
		    sendForgot(7,gettext("Please provide your valid email address<br>to get your login information"));
		}
		$QUERY = "SELECT id, username, lastname, firstname, email, uipass, useralias FROM cc_card WHERE email='" . $pr_email . "' ";
		$res = $DBHandle->Execute($QUERY);
		$num = 0;
		if ($res)
			$num = $res->RecordCount();
		if (!$num) {
			sendForgot(6,gettext("No such login exists"));
		}
		for ($i = 0; $i < $num; $i++) {
			$list[] = $res->fetchRow();
		}
		foreach ($list as $recordset) {
			list ($id_card, $username, $lastname, $firstname, $email, $uipass, $cardalias) = $recordset;
			try {
				$mail = new Mail(Mail :: $TYPE_FORGETPASSWORD, $id_card);
				$mail -> replaceInEmail(Mail::$CUSTOMER_INTERFACE, CUSTOMER_INTERFACE_URL);
				$mail -> send();
			} catch (A2bMailException $e) {
				sendForgot(7,gettext("Mail sender error.<br>Try again please."));
			}
		}
		sendForgot(5,gettext("Your login information email<br>has been sent to you."));
	} else {
		sendForgot(7,gettext("Invalid Action"));
	}
}

include ("lib/customer.module.access.php");
include ("lib/customer.smarty.php");

if (has_rights(ACX_ACCESS)) {
    Header("Location: userinfo");
    exit();
}

$zippostcode = '';
//      -= Need to install GeoIP http://ua2.php.net/manual/en/geoip.setup.php =-
if (function_exists('geoip_db_avail') && (geoip_db_avail(GEOIP_REGION_EDITION_REV0) || geoip_db_avail(GEOIP_REGION_EDITION_REV1))) {
        $countryregion = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
        $zippostcode = "value='".$countryregion['postal_code']."'"; // ZIP/POSTAL CODE
} else {
        $countrycode = $region = "";
	$countryregion = array();
}

$country_city_list = array (array('Jeru'  ,'Israel' ),
                            array('Berlin','Germany')
                            );
$town = "";
foreach ($country_city_list as $cur_value) {
        if ($cur_value[1]==$countryregion['country_name'])
            $town = $cur_value[0];
}

$countrycode = $countryregion['country_code3'];
if ($countrycode=="") {
    $countrycode = 'USA';
}

$curzonename = "";
$timezone_list = get_timezones();
                $one_select = false;
                if (function_exists('geoip_time_zone_by_country_and_region')) {
                        if ($countryregion===false) {
                                $country = $region = "";
                        } else {
                                $country = $countryregion['country_code'];
                                $region = $countryregion['region'];
                        }
                        if ($region == "") {
                                if ($country == "") $country = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
                                $region = '01';
                        }
                        if ($country == "") {
                                $country = 'US';
                                $region = 'CA';
                        }
                        try {
                                $UserDateTimeZone       = new \DateTimeZone(geoip_time_zone_by_country_and_region($country,$region));
                        } catch (\Exception $e) {
                                $UserDateTimeZone       = new \DateTimeZone('UTC');
                        }
                        $zonename               = $UserDateTimeZone->getName();
//                      $UserDateTime           = new DateTime(null, $UserDateTimeZone);
//                      $servergmt              = $UserDateTimeZone->getOffset($UserDateTime);
                        $UserDateTime           = new DateTime('2017-12-14', $UserDateTimeZone);
                        $servergmt              = $UserDateTime->getOffset();
                } else $servergmt = SERVER_GMT;

                foreach ($timezone_list as $key => $cur_value) {
                        $timezone_list[$key] = array (
                                $cur_value[2],
                                $key
                        );
                                if (in_array($servergmt, $cur_value) && !$one_select) {
                                        $cur_id_timezone = $key.";".$zonename;
                                        if ($town=="" || strpos($cur_value[2], $town) !== false) {
                                                $timezone_list[$key][1] = $cur_id_timezone;
                                                if ($zonename != "") $curzonename = $timezone_list[$key][0] = substr_replace($cur_value[2],") ".$zonename,strpos($cur_value[2],')'));
                                                if (!isset($id_timezone) || $key == $id_timezone)
                                                        $id_timezone = $cur_id_timezone;
                                                $one_select = true;
                                        }
                                }
                }

$smarty -> assign("curzonename", $curzonename);
$smarty -> assign("curzonecode", $cur_id_timezone);
$smarty -> assign("error", $error);

$smarty -> assign("username", $username);
$smarty -> assign("password", $password);

$smarty -> assign("SHOW_SLIDEBAR", SHOW_SLIDEBAR);
$smarty -> assign("SECONDARY_TITLE", "Sign in or Register");

$smarty -> display('index.tpl');
