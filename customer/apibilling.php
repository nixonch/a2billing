<?php
/*
Result :
    Rate checker web service

Parameters :
    u : Customer's SIP name
    p : Customer's SIP secret
    t : Destination number, as it is dialed. This parameter will probably be always present.

Usage :
    https://localhost/customer/rate.php?u=XXXXXXXXXXX&p=XXXXXXXXXXX&t=1234567890
    https://localhost/customer/rate.php?u=XXXXXXXXXXX&t=1234567890                <<== Will have returned rate in xml with authenticate by request ip instead password
or in Acrobits apps:
    https://localhost/customer/rate.php?u=%account[username]%&p=%account[password]%&t=%targetNumber%
    https://localhost/customer/rate.php?u=%account[username]%&t=%targetNumber%    <<== Will have returned rate in xml with authenticate by request ip instead password

=========================================================================

Result :
    Get the Customer's Balance

Parameters :
    u : Customer's SIP name
    p : Customer's SIP secret
    html=1 : to display with <pre> tag
    html=xml : to return format for the balance checker is a simple XML

Usage :
    https://localhost/customer/balance.php?u=XXXXXXXXXXX&p=XXXXXXXXXXX&html=1
    https://localhost/customer/balance.php?u=XXXXXXXXXXX&p=XXXXXXXXXXX&html=xml
    https://localhost/customer/balance.php?u=XXXXXXXXXXX           <<== Will have returned balance in xml with authenticate by request ip instead password
    In Acrobits apps:
    https://localhost/customer/balance.php?u=%account[username]%&p=%account[password]%   <<== Will have returned balance in xml with authenticate by login+password
    https://localhost/customer/balance.php?u=%account[username]%                         <<== Will have returned balance in xml with authenticate by request ip instead password
*/

include ("lib/customer.defines.php");
include ("lib/Class.RateEngine.php");

getpost_ifset(array('u', 'p', 't', 'html'));

if (!isset($u)) $u='';
if (!isset($p)) $p='';
if (!isset($html)) $html='';
if (!isset($t)) $t='';

if ($t) {

$rate = Service_Get_RateInitial($u, $p, $t);

$A2B -> DbDisconnect();

header ( "Content-type: text/xml" );
echo "<response>\n";
echo "<error>0</error>\n";
echo "<callRateString>";
echo $rate[2]." ";
if (strpos($rate[2],"ERROR")===false) {
    display_money_nocur($rate[0]);
    if ($rate[1]>$rate[0]) {
	echo "-";
	display_money_nocur($rate[1]);
    }
    echo " / min";
} else {
    echo $rate[0];
}
echo "</callRateString>\n";
//echo "<messageRateString>Your Best Rate</messageRateString>\n";
echo "</response>\n";

} else {

$balance = Service_Get_Balance($u, $p, $html);

$A2B -> DbDisconnect();

header ( "Content-type: text/xml" );
if ($html=="1") {
    echo "<pre>";
    echo $balance[0];
    echo "</pre>";
} else {
    echo "<response>\n";
    echo "<error>0</error>\n";
    echo "<balanceString>";
    echo $balance[1];
    if (strpos($balance[1],"ERROR")===false && $balance[0]!='')
	display_money_nocur($balance[0]);
    else
	echo $balance[0];
    echo "</balanceString>\n";
    echo "<rereg>{$balance[2]}</rereg>\n";
    echo "</response>\n";
}
}

function Service_Get_RateInitial($accountnumber, $password, $called)
{
global $A2B;
    $A2B -> DBHandle = DbConnect();
    if (!$A2B -> DBHandle) {
        write_log(LOGFILE_API_CALLBACK, basename(__FILE__).' line:'.__LINE__." ERROR CONNECT DB");
        return array('500', '500', 'ERROR - CONNECT DB');
    }
    $instance_table = new Table();
    $A2B->set_instance_table($instance_table);

    $res = $A2B->instance_table->SQLExec($A2B->DBHandle, "SELECT cc.id, tariff, credit, currency, cc.username, status, creditlimit FROM cc_card cc LEFT JOIN cc_sip_buddies ON cc.id=id_cc_card WHERE name LIKE '$accountnumber' AND (secret LIKE '$password' OR ipaddr LIKE '".$_SERVER['REMOTE_ADDR']."') LIMIT 1");

    if (!is_array($res) || count($res) == 0) {
        return array('400', '400', 'ERROR - AUTHENTICATE CODE');
    }
    $cardid		= $res[0][0];
    $tariffplan 	= $res[0][1];
    $balance		= $res[0][2];
    if ($balance<0)
	$balance       += $res[0][6];
    $currency		= $res[0][3];
    $A2B->cardnumber	= $res[0][4];
    $A2B->credit	= $balance;
    $status		= $res[0][5];

    if (!$cardid || $cardid <= 0 || !($status == 1 || $status == 8)) {
        return array('400', '400', 'ERROR - AUTHENTICATE CODE');
    }

    $rate1 = $rate2 = $destination = '';

    if ($called && $cardid) {
	if (strlen($called) > 2 && is_numeric($called)) {
		$currencies_list = get_currencies();
		if (!isset($currencies_list[strtoupper($currency)][2]) || !is_numeric($currencies_list[strtoupper($currency)][2])) {
			$mycur = 1;
		} else {
			$mycur = $currencies_list[strtoupper($currency)][2];
		}
		if ($A2B->callingcard_ivr_authenticate_light($error_msg)) {
			$RateEngine = new RateEngine();
			$RateEngine->webui = 0;
			$A2B->agiconfig['accountcode'] = $A2B->cardnumber;
			$A2B->agiconfig['use_dnid'] = 1;
			$A2B->agiconfig['say_timetocall'] = 0;
			$A2B->agiconfig['lcr_mode'] = 1;
			$A2B->dnid = $A2B->destination = $called;
			if ($A2B->removeinterprefix)
				$A2B->destination = $A2B->apply_rules($A2B->destination);
			$A2B->destination = $A2B->apply_add_countryprefixto($A2B->destination);
			$resfindrate = $RateEngine->rate_engine_findrates($A2B, $A2B->destination, $tariffplan);
			// IF FIND RATE
			if ($resfindrate != 0) {
			    $A2B->margintotal	= $A2B->margin_calculate($cardid);
			    for($j=0;$j<count($RateEngine->ratecard_obj);$j++){
				if ($RateEngine->ratecard_obj[$j][70]=="-INFOLINE-")	break;
				$rateinitial =  round($A2B->margintotal * $RateEngine->ratecard_obj[$j][12] / $mycur, 5);
				if ($rate1=='') {
				    $rate1 = $rate2 = $rateinitial;
				} else {
				    if ($rate1>$rateinitial) $rate1 = $rateinitial;
				    if ($rate2<$rateinitial) $rate2 = $rateinitial;
				}
				if ($RateEngine->ratecard_obj[$j][7] != '') {
				    $res = $A2B->instance_table -> SQLExec ($A2B -> DBHandle, "SELECT destination FROM cc_prefix where prefix='".$RateEngine->ratecard_obj[$j][5]."'");
				    if (is_array($res))
					$destination = $res[0][0];
				}
				if (strpos($RateEngine->ratecard_obj[$j][7],"_")===0 && ($j+1==count($RateEngine->ratecard_obj) || strpos($RateEngine->ratecard_obj[$j+1][7],"_")===false))
				    break;
			    }
			}
		}
	}
    }
    if ($destination) $currency = $destination." = ".$currency;
    return array($rate1, $rate2, $currency);
}

function Service_Get_Balance($accountnumber, $password, $html=null)
{
global $A2B;
    $A2B -> DBHandle = DbConnect();
    if (!$A2B -> DBHandle) {
        write_log(LOGFILE_API_CALLBACK, basename(__FILE__).' line:'.__LINE__." ERROR CONNECT DB");
        return array('500', 'ERROR - CONNECT DB ', '0');
    }
    $card_id = 0;
    $instance_table = new Table();
    $A2B->set_instance_table($instance_table);

    if ($password=='')
	$QUERY = "SELECT cc.username, cc.credit, cc.status, cc.id, cc.currency, sb.id, avpf, sb.allow FROM cc_card cc LEFT JOIN cc_sip_buddies sb ON cc.id=id_cc_card WHERE name LIKE '$accountnumber' AND ipaddr LIKE '".$_SERVER['REMOTE_ADDR']."' LIMIT 1";
    else
	$QUERY = "SELECT cc.username, cc.credit, cc.status, cc.id, cc.currency, sb.id, avpf, sb.allow FROM cc_card cc LEFT JOIN cc_sip_buddies sb ON cc.id=id_cc_card WHERE name LIKE '$accountnumber' AND (secret LIKE '$password' OR ipaddr LIKE '".$_SERVER['REMOTE_ADDR']."') LIMIT 1";
    for ($i=10;$i>=0;$i--) {
	$res = $instance_table->ExecuteQuery($A2B->DBHandle, $QUERY);
	if ($res===false) {
	    return array('400', 'ERROR - AUTHENTICATE CODE ', '0');
	} else {
	    $num = $res -> RecordCount();
	    if ($num>0) {
		$row [] = $res -> fetchRow();
		$card_id = $row[0][3];
		break;
	    }
	    if ($i>0) sleep(2);
	}
    }

    if (!$card_id || $card_id <= 0) {
	if ($password=='')
	    return array('', 'Your ip: '.$_SERVER['REMOTE_ADDR'], '0');
	else
	    return array('400', 'ERROR - TIMEOUT ', '0');
    }

    $balance  = $row[0][1];
    $currency = $row[0][4]." ";
    $rereg    = 0;

    if ($row[0][6]!='yes' && $html==2 && USE_REALTIME) {
	$allow = array_filter(explode(',',str_replace(' ','',$row[0][7])),function($v){ return strcasecmp($v,'opus')!=0;});
	array_unshift($allow,'opus');
	$allow = rtrim(implode(',',$allow),', ');
	$QUERY = "UPDATE cc_sip_buddies SET allow='$allow',avpf='yes',dtmfmode='info',dtlscertfile='".$A2B->config['global']['certfile']."',dtlsprivatekey='".$A2B->config['global']['privatekey']."',dtlscafile='".$A2B->config['global']['cafile']."',force_avp='yes',icesupport='yes',dtlsenable='yes',dtlsverify='fingerprint',dtlssetup='actpass',rtcp_mux='yes' WHERE id='{$row[0][5]}'";
	$instance_table->SQLExec($A2B->DBHandle, $QUERY, 0);
	require_once (dirname(__FILE__)."/lib/phpagi/phpagi-asmanager.php");
	$as = new AGI_AsteriskManager();
	$res =@  $as->connect(MANAGER_HOST,MANAGER_USERNAME,MANAGER_SECRET);
	if ($res) {
		$res = $as->Command('sip unregister '.$accountnumber);
		$res = $as->Command('sip prune realtime peer '.$accountnumber);
		$as->disconnect();
		$rereg = 1;
	}
    }

    return array($balance, $currency, $rereg);
}
