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
include ("../lib/regular_express.inc");
include ("../lib/phpagi/phpagi-asmanager.php");
include ("../lib/admin.smarty.php");


if (! has_rights (ACX_MAINTENANCE)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");	   
	die();	   
}

check_demo_mode_intro();

// #### HEADER SECTION
$smarty->display('main.tpl');

?>
<br>
<center>

<?php

$astman = new AGI_AsteriskManager();
$res = $astman->connect(MANAGER_HOST,MANAGER_USERNAME,MANAGER_SECRET);

/* $Id: page.parking.php 2243 2006-08-12 17:13:17Z p_lindheimer $ */
//Copyright (C) 2006 Astrogen LLC 
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

$dispnum = 'asteriskinfo'; //used for switch on config.php

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'summary';

$modes = array(
	"summary" => "Summary",
	"dongle" => "Dongle",
	"registries" => "Registries",
	"channels" => "Channels",
	"peers" => "Peers",
	"sip" => "Sip Info",
	"iax" => "IAX Info",
	"conferences" => "Conferences",
	"subscriptions" => "Subscriptions",
//	"voicemail" => "Voicemail Users",
	"codecs" => "Codecs",
	"all" => "Full Report"
);
$arr_dongle = array(
	"Dongle" => "dongle show devices"
);
$arr_all = array(
	"Version" => "core show version",
	"Uptime" => "core show uptime",
	"Active Channel(s)" => "core show channels",
	"Sip Channel(s)" => "sip show channels",
	"IAX2 Channel(s)" => "iax2 show channels",
	"Sip Registry" => "sip show registry",
	"Sip Peers" => "sip show peers",
	"IAX2 Registry" => "iax2 show registry",
	"IAX2 Peers" => "iax2 show peers",
	"Codecs" => "core show translation",
	"Subscribe/Notify" => "core show hints",
	"Conference Info" => "ConfbridgeListRooms",
);
$arr_registries = array(
	"Sip Registry" => "sip show registry",
	"IAX2 Registry" => "iax2 show registry",
);
$arr_channels = array(
	"Active Channel(s)" => "core show channels",
	"Sip Channel(s)" => "sip show channels",
	"IAX2 Channel(s)" => "iax2 show channels",
);
$arr_codecs = array(
        "Codecs" => "core show translation",
);
$arr_peers = array(
	"Sip Peers" => "sip show peers",
	"IAX2 Peers" => "iax2 show peers",
);
$arr_sip = array(
	"Sip Registry" => "sip show registry",
	"Sip Peers" => "sip show peers",
);
$arr_iax = array(
	"IAX2 Registry" => "iax2 show registry",
	"IAX2 Peers" => "iax2 show peers",
);
$arr_conferences = array(
	"Conference Info" => "ConfbridgeListRooms",
);
$arr_subscriptions = array(
	"Subscribe/Notify" => "core show hints"
);
?>

<div class="rnav"><ul>
<?php 
$i=0;
foreach ($modes as $mode => $value) {
	$i++;
	if ($i > 1) echo " | ";
	//echo "<li><a id=\"".($extdisplay==$mode)."\" href=\"".$_SERVER['PHP_SELF']."?&section=".$section."type=".urlencode("tool")."&display=".urlencode($dispnum)."&extdisplay=".urlencode($mode)."\">"._($value)."</a></li>";
	echo "<a id=\"".($extdisplay==$mode)."\" href=\"".$_SERVER['PHP_SELF']."?section=".$section."&type=".urlencode("tool")."&display=".urlencode($dispnum)."&extdisplay=".urlencode($mode)."\">"._($value)."</a>";
}
?>
</ul></div>

<div class="content">
<h2><span class="headerHostInfo"><?php echo "Asterisk : ".$modes[$extdisplay]; ?></span></h2>

<form name="asteriskinfo" action="<?php  $_SERVER['PHP_SELF'] ?>" method="post">
<input type="hidden" name="display" value="asteriskinfo"/>
<input type="hidden" name="action" value="asteriskinfo"/>
<table>

<table class="box">
<?php
if (!$astman) {
?>
	<tr class="boxheader">
		<td colspan="2" align="center"><h5><?php echo _("ASTERISK MANAGER ERROR")?><hr></h5></td>
	</tr>
		<tr class="boxbody">
			<td>
			<table border="0" >
				<tr>
					<td align="left">
							<?php 
							echo "<br>The module was unable to connect to the asterisk manager.<br>Make sure Asterisk is running and your manager.conf settings are proper.<br><br>";
							?>
					</td>
				</tr>
			</table>
			</td>
		</tr>
<?php
} else {
	if ($extdisplay != "summary") {
		$arr="arr_".$extdisplay;
		foreach ($$arr as $key => $value) {
?>
			<tr class="boxheader">
				<td colspan="2" align="center"><h5><?php echo _("$key")?><hr></h5></td>
			</tr>
			<tr class="boxbody">
				<td>
				<table border="0" >
					<tr>
						<td>
							<pre>
<?php
								if ($key=='Conference Info')
								    $response = $astman->send_request($value);
								else
								    $response = $astman->send_request('Command',array('Command'=>$value));
								$new_value = (!isset($response['data']))?$response['Output']:$response['data'];
								if ($new_value=='')
								    $new_value = $response['Message'];
								echo htmlspecialchars($new_value, ENT_QUOTES);
								?>
							</pre>
						</td>
					</tr>
				</table>
				</td>
			</tr>
		<?php
		}
	} else {
	?>
			<tr class="boxheader">
				<td colspan="2" align="center"><h5><?php echo _("Summary")?><hr></h5></td>
			</tr>
			<tr class="boxbody">
				<td>
				<table border="0">
					<tr>
						<td>
							<?php echo buildAsteriskInfo(); ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
<?php
	}
}
?>
	</table>
<tr>
	<td colspan="2"><h6><input name="Submit" class="form_input_button" type="submit" value="<?php echo _("Refresh")?>"></h6></td>
</tr>
</table>

<script language="javascript">
<!--
var theForm = document.asteriskinfo;
//-->
</script>
</form>

<?php

function convertActiveChannel($sipChannel, $channel = NULL){
	if($channel == NULL){
		print_r($sipChannel);
		exit();
		$sipChannel_arr = explode(' ', $sipChannel[1]);
		if($sipChannel_arr[0] == 0){
			return 0;
		}else{
			return count($sipChannel_arr[0]);
		}
	}elseif($channel == 'IAX2'){
		$iaxChannel = $sipChannel;
	}
}

function getActiveChannel($channel_arr, $channelType = NULL){
	if(count($channel_arr) > 1){
		if($channelType == NULL || $channelType == 'SIP'){
			$sipChannel_arr = $channel_arr;
			$sipChannel_arrCount = count($sipChannel_arr);
			$sipChannel_string = $sipChannel_arr[$sipChannel_arrCount - 1];
			$sipChannel = explode(' ', $sipChannel_string);
			return $sipChannel[0];
		}elseif($channelType == 'IAX2'){
			$iax2Channel_arr = $channel_arr;
			$iax2Channel_arrCount = count($iax2Channel_arr);
			$iax2Channel_string = $iax2Channel_arr[$iax2Channel_arrCount - 1];
			$iax2Channel = explode(' ', $iax2Channel_string);
			return $iax2Channel[0];
		}
	}
}

function getRegistration($registration, $channelType = 'SIP'){
	if($channelType == NULL || $channelType == 'SIP'){
		$sipRegistration_arr = $registration;
		$sipRegistration_arrCount = count($sipRegistration_arr);
		$sipRegistration_string = $sipRegistration_arr[$sipRegistration_arrCount - 1];
		$sipRegistration = explode(' ', $sipRegistration_string);
		return $sipRegistration[0];
		
	}elseif($channelType == 'IAX2'){
		$iax2Registration_arr = $registration;
		$iaxRegistration_arrCount = count($iaxRegistration_arr);
		$iaxRegistration_string = $iaxRegistration_arr[$iaxRegistration_arrCount - 1];
		$iaxRegistration = explode(' ', $iaxRegistration_string);
		return $iaxRegistration[0];
	}
}

function getPeer($iax2Peer){
	if(count($iax2Peer) > 1){
		$iax2Peer_count = count($iax2Peer);
		$iax2PeerInfo_string = $iax2Peer[$iax2Peer_count -1];
		$iax2PeerInfo_arr2 = explode('[',$iax2PeerInfo_string);
		$iax2PeerInfo_arr3 = explode(' ',$iax2PeerInfo_arr2[1]);
		$iax2PeerInfo_arr['online'] = $iax2PeerInfo_arr3[0];
		$iax2PeerInfo_arr['offline'] = $iax2PeerInfo_arr3[2];
		$iax2PeerInfo_arr['unmonitored'] = $iax2PeerInfo_arr3[4];
		return $iax2PeerInfo_arr;
	}
	return array('online'=>0,'offline'=>0,'unmonitored'=>0);
}

function buildAsteriskInfo(){
	global $astman;

	$arr = array(
		"Uptime" => "core show uptime",
		"Active SIP Channel(s)" => "sip show channels",
		"Active IAX2 Channel(s)" => "iax2 show channels",
		"Sip Registry" => "sip show registry",
		"IAX2 Registry" => "iax2 show registry",
		"Sip Peers" => "sip show peers",
		"IAX2 Peers" => "iax2 show peers",
	);

	$htmlOutput = '<div style="color:#000000;font-size:12px;margin:10px;">';
	$htmlOutput .= '<table border="1" cellpadding="10">';

	$DBHandle_max = DbConnect();
	$instance_table = new Table();

	foreach ($arr as $key => $value) {
		if ($key!='Sip Peers') {
		    $response = $astman->send_request('Command',array('Command'=>$value));
		    $astout = explode("\n",$response['data']);
		}
		switch ($key) {
			case 'Uptime':
				$uptime = $astout;
				$htmlOutput .= '<tr><td colspan="2">'.$uptime[0]."<br />".$uptime[1]."<br /></td>";
				$htmlOutput .= '</tr>';
			break;
			case 'Active SIP Channel(s)':
				$activeSipChannel = $astout;
				$activeSipChannel_count = getActiveChannel($activeSipChannel, 'SIP');
				$htmlOutput .= '<tr>';
				$htmlOutput .= "<td>Active Sip Channels: ".$activeSipChannel_count."</td>";
			break;
			case 'Active IAX2 Channel(s)':
				$activeIAX2Channel = $astout;
				$activeIAX2Channel_count = getActiveChannel($activeIAX2Channel, 'IAX2');
				$htmlOutput .= "<td>Active IAX2 Channels: ".$activeIAX2Channel_count."</td>";
				$htmlOutput .= '</tr>';
			break;
			case 'Sip Registry':
				$sipRegistration = $astout;
				$sipRegistration_count = getRegistration($sipRegistration, 'SIP');
				$htmlOutput .= '<tr>';
				$htmlOutput .= "<td>SIP Registrations: ".$sipRegistration_count."</td>";
			break;
			case 'IAX2 Registry':
				$iax2Registration = $astout;
				$iax2Registration_count = getRegistration($iax2Registration, 'IAX2');
				$htmlOutput .= "<td>IAX2 Registrations: ".$iax2Registration_count."</td>";
				$htmlOutput .= '</tr>';
			break;
			case 'Sip Peers':
				$Peer_arr = array('mononline'=>0,'monoffline'=>0,'unmononline'=>0,'unmonoffline'=>0);
				$QUERY = "SELECT qualify, COUNT(*) FROM cc_sip_buddies WHERE port>0 AND UNIX_TIMESTAMP()<regseconds GROUP BY qualify";
				$result = $instance_table -> SQLExec ($DBHandle_max, $QUERY);
				if (is_array($result) && count($result)>0) {
				    foreach ($result as $val) {
					if ($val[0]=='no' || $val[0]==NULL) $Peer_arr['unmononline']+=$val[1];
					else $Peer_arr['mononline']+=$val[1];
				    }
				}
				$QUERY = "SELECT qualify, COUNT(*) FROM cc_sip_buddies WHERE port=0 OR port IS NULL OR UNIX_TIMESTAMP()>=regseconds GROUP BY qualify";
				$result = $instance_table -> SQLExec ($DBHandle_max, $QUERY);
				if (is_array($result) && count($result)>0) {
				    foreach ($result as $val) {
					if ($val[0]=='no' || $val[0]==NULL) $Peer_arr['unmonoffline']+=$val[1];
					else $Peer_arr['monoffline']+=$val[1];
				    }
				}
				$htmlOutput .= '<tr>';
				$htmlOutput .= "<td>SIP Peers<br />&nbsp;&nbsp;&nbsp;&nbsp;Monitored Online: ".$Peer_arr['mononline']."<br />&nbsp;&nbsp;&nbsp;&nbsp;Monitored Offline: ".$Peer_arr['monoffline']."<br />&nbsp;&nbsp;&nbsp;&nbsp;Unmonitored Online: ".$Peer_arr['unmononline']."<br />&nbsp;&nbsp;&nbsp;&nbsp;Unmonitored Offline: ".$Peer_arr['unmonoffline']."</td>";
			break;
			case 'IAX2 Peers':
				$iax2Peer = $astout;
				$iax2Peer_arr = getPeer($iax2Peer, 'IAX2');
				if($iax2Peer_arr['offline'] != 0){
					$iax2PeerColor = 'red';
				}else{
					$iax2PeerColor = '#000000';
				}
				$htmlOutput .= "<td>IAX2 Peers<br />&nbsp;&nbsp;&nbsp;&nbsp;Online: ".$iax2Peer_arr['online']."<br />&nbsp;&nbsp;&nbsp;&nbsp;Offline: ".$iax2Peer_arr['offline']."<br />&nbsp;&nbsp;&nbsp;&nbsp;Unmonitored: ".$iax2Peer_arr['unmonitored']."<br />&nbsp;</td>";
				$htmlOutput .= '</tr>';
			break;
			default:
			}
		}
	$htmlOutput .= '</table>';
	return $htmlOutput."</div>";
}
?>
</center>
<?php

// #### FOOTER SECTION
$smarty->display('footer.tpl');
