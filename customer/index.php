<?php

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
			$client = new GuzzleHttp\Client(['base_uri' => "https://api.d7networks.com",
							 'timeout' => 30,
							 'headers' => [	'User-Agent' => 'sipde.net/Guzzle php/'.phpversion(),
									'Content-Type' => 'application/json',
									'Accept' => 'application/json',
									'Authorization' => 'Bearer '.D7_API_TOKEN
								      ]
							]);
			$requestData =
			[ 'messages' =>
			  [
			    [
			    'originator' => "REMINDER",
			    'channel' => "sms",
			    'recipients' => [$phone],
			    'content' => $message,
			    'msg_type' => "text",
			    'data_coding' => "auto"
			    ]
			  ]
			];
			try {
			    $response = $client->post('https://api.d7networks.com/messages/v1/send', ['json' => $requestData]);
			} catch (Exception $e) {
			    $response = $client->get('https://api.d7networks.com/messages/v1/balance');
			    if ($response->getStatusCode() == 200) {
				$body = json_decode($response->getBody(),true);
				$balance = '$'.$body['balance'];
				$sms_count = (isset($body['sms_count'])) ? $body['sms_count'] : 'N/A';
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
