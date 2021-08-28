<!DOCTYPE html>
{php} header('X-Frame-Options: SAMEORIGIN'); {/php}
<html>
<head>
    <link rel="shortcut icon" href="{$FAVICONPATH}"/>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Asterisk WebRTC settings | {$CCMAINTITLE}</title>
    <link rel="stylesheet" href="templates/default/css/index.css" type="text/css">
</head>
<body class="bodygradient" style="max-width:900px;margin:auto">
  <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:400,300,700" type="text/css"/>
  <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Bree+Serif&subset=cyrillic,cyrillic-ext,latin,latin-ext" type="text/css"/>
{php} if (false && (LANGUAGE=="russian" || LANGUAGE=="ukrainian")) { {/php}

{php}}else{{/php}
<h1>Asterisk WebRTC</h1>
<h3>About</h3>
<p>In this guide you will find detailed instructions about WebRTC setup for Asterisk 13 or higher.</p>
<p style="color: #974806">Warning: Asterisk has only basic WebRTC support and doesn't handle corner cases such as streaming over HTTP port 80 (which is needed for most corporate networks where UDP is blocked) and also it doesn't have a built-in TURN server (a separate TURN server needs to be installed). Asterisk also has a long list of WebRTC related issues and bugs even in the latest version.</p>
<h3>WebRTC and SIP:</h3>
<p>For a server to be able to handle WebRTC, the followings needs to be implemented:</p>
<br>
<p1>1. Signaling:</p1>
<p1>This is the easiest part as it just have to implement WebSocket for SIP as described in <a href="https://tools.ietf.org/html/rfc7118">RFC 7118</a>. If two WebRTC endpoints have to call each other, then they can do it via a server supporting only websocket signaling. However if the other endpoint is a simple SIP client, then the server must also handle media conversion. In Asterisk this is handled in res_http_websocket and chan_sip or pjlib.</p1>
<br>
<p1>2. Media:</p1>
<p1>A fully webrtc compliant server should also implement media routing to enable WebRTC to SIP calls. This is a bit more complicated as the server need to understand DTLS (TLS over UDP as described in <a href="https://tools.ietf.org/html/rfc6347">RFC 6347</a>), SRTP (secure RTP for media encryption as described in <a href="https://www.ietf.org/rfc/rfc3711.txt">RFC 3711</a>) and <a href="https://tools.ietf.org/html/rfc5245">ICE</a>. In Asterisk this is handled in res_rtp_asterisk and res_srtp.</p1>
<br>
<p1>3. Extra:</p1>
<p1>A decent WebRTC implementation should also implement <a href="https://tools.ietf.org/html/rfc5389">STUN</a> and <a href="https://tools.ietf.org/html/rfc5766">TURN</a>, add secure transport (SSL certificate) and optimize WebRTC handling. This can make the difference between barely &ldquo;working&rdquo; and &ldquo;always working&rdquo; implementations.</p1>
<h3>Asterisk:</h3>
<p><a href="https://www.asterisk.org/">Asterisk</a> supports WebSocket and WebRTC since version 11. This guide is focusing mostly on WebRTC configuration for Asterisk v.13. (If you are using an older Asterisk, we strongly recommend to upgrade, because there was a lot of development in the recent months on WebRTC to make it more stable and complete implementation). This guide also applies for A2Billing, FreePBX and other Asterisk clones such as Elastix and PIAF). For old Asterisk versions you might consider <a href="https://github.com/sipml5/sipml5/tree/master/asterisk">these patches</a>.</p>
<br />
<p2>If you prefer to build from source:</p2>
<p2>We recommend Debian or CentOS</p2>
<p2>- <strong>Asterisk 11</strong> has pjproject built-in. For ICE support get the development library (uuid-dev or libuuid-devel):</p2>
<p2>&nbsp;&nbsp;&nbsp;&nbsp; sudo apt-get install build-essential libncurses5-dev libxml2-dev libsqlite3-dev libssl-dev libsrtp0-dev uuid-dev</p2>
<p2>Go to your Asterisk source /contrib/scripts directory and run the install_prereq script to get everything else that is needed:</p2>
<p2>&nbsp;&nbsp;&nbsp;&nbsp; sudo ./install_prereq install</p2>
<p2>&nbsp;&nbsp;&nbsp;&nbsp; sudo ./install_prereq install-unpackaged</p2>
<p2>&nbsp;Using menuselect make sure Asterisk will build with res_http_websocket, res_crypto and chan_sip.</p2>
<p2>In the Asterisk source dir:</p2>
<p2>&nbsp;&nbsp;&nbsp; ./configure &amp;&amp; make menuselect</p2>
<p2>Build and install Asterisk:</p2>
<p2>make &amp;&amp; make install &amp;&amp; make samples</p2>
<p2>You can find more details about Asterisk 11 setup <a href="https://sipjs.com/guides/server-configuration/asterisk/">here</a> and <a href="https://wiki.asterisk.org/wiki/display/AST/WebRTC+tutorial+using+SIPML5">here</a>.</p2>
<p2>- With <strong>Asterisk 12 </strong>you must have<a href="https://wiki.asterisk.org/wiki/display/AST/Building+and+Installing+pjproject"> pjproject installed</a>.</p2>
<p2>A good tutorial can be found <a href="http://www.nethram.com/webrtc-with-asterisk-12/">here</a>.</p2>
<p2>- <strong>Asterisk 13</strong> or higher made a lot of improvements for WebRTC handling so we recommend this latest version. WebRTC should work just fine out of the box, without the need to change/recompile any binary.</p2>
<br>
<p1>We recommend to use Asterisk version&nbsp;13.15.0 or 14.4.0 or higher for WebRTC (The last stable release is the best).</p1>
<h3>Download:</h3>
<p>As a ready to use package you can use Asterisk Now. V.13 which can be downloaded from<strong> <a href="https://www.asterisk.org/downloads/asterisknow">here</a>.</strong></p>
<p1>A public static IP address is highly recommended to avoid NAT related issues. Otherwise make sure that your Asterisk is configured properly (private/public IP, port forwarding, NAT handling).</p1>
<p1>Your Asterisk root directory will be located at /etc/asterisk.</p1>
<h3>SIP:</h3>
<p>Asterisk 11 used the old sip.conf however from Asterisk 12 upward we have the new<a href="https://wiki.asterisk.org/wiki/display/AST/Configuring+res_pjsip"> pjsip.conf</a>.</p>
<p1>Below you can find an example pjsip.conf file with 2 SIP accounts (6001 and 6002) at /etc/asterisk/pjsip.conf. Be aware of the [transport-ws] section where you must enable websocket.</p1>
<br />
<p1 style="color: #bf4f4c">
[global]<br />
type=global<br />
user_agent=FPBX-AsteriskNOW-12.0.76.2(13.2.0)<br />
realm=192.168.213.134 ;(you need to rewrite this with your IP address)<br />
bindport=5060 ;(you need to rewrite this with your servers SIP port)<br />
transport=udp,ws,wss<br />
<br />
[simpletrans]<br />
type=transport<br />
protocol=udp<br />
bind=0.0.0.0 <br />
<br />
[transport-ws]<br />
type=transport<br />
protocol=ws<br />
bind=0.0.0.0<br />
<br />
[endpoint-basic](!)<br />
type=endpoint<br />
transport=simpletrans<br />
context=internal<br />
disallow=all<br />
allow=ulaw<br />
rtcp_mux=yes<br />
<br />
[auth-userpass](!)<br />
type=auth<br />
auth_type=userpass<br />
<br />
[aor-single-reg](!)<br />
type=aor<br />
max_contacts=10<br />
<br />
[6001](endpoint-basic)<br />
auth=auth6001<br />
aors=6001<br />
<br />
[auth6001](auth-userpass)<br />
username=6001<br />
password=6666<br />
<br />
[6001](aor-single-reg)<br />
<br />
[6002](endpoint-basic)<br />
auth=auth6002<br />
aors=6002<br />
<br />
[auth6002](auth-userpass)<br />
password=6002<br />
username=6002<br />
<br />
[6002](aor-single-reg)</p1>
<h3>Dialplan:</h3>
<p>A simple plan to be able to make calls from WebRTC to SIP assigning the 6666 number to the 6001 account in /etc/asterisk/extensions.conf:</p>
<p1 style="color: #bf4f4c">[internal]<br />
exten =&gt; 6666,1,Dial(PJSIP/6001)</p1>
<h3>Logs:</h3>
<p>At /var/logs/asterisk/<br/>
Enable sip log: in sip.conf set <span style="color: #bf4f4c">sipdebug=yes</span><br />
Set high loglevel &ndash; in logger.conf set the below line</p>
<p1 style="color: #bf4f4c">
[logfiles]<br/>
Verbose =&gt; notice,warning,error,debug,verbose,dtmf</p1>
<h3>WebRTC</strong>:</h3>
<p>You need to make changes in these config files: sip.conf, http.conf, rtp.conf</p>
<h3>sip.conf configuration:</h3>
<p>In the sip.conf the [general] section should look like this:</p>
<p1 style="color: #bf4f4c">[general]<br />
udpbindaddr=YOUR IP:5060<br />
realm=YOUR IP<br />
sipdebug=yes<br />
transport=udp,tcp,tls,ws,wss</p1>
<h3>Setup a WebRTC extension:</h3>
<p>An extension to be used by the web client should have the following format:</p>
<p1 style="color: #bf4f4c">
[555888]<br />
secret=1234567890<br />
context=internal<br />
host=dynamic<br />
trustrpid=yes<br />
sendrpid=no<br />
type=peer<br />
qualify=yes<br />
qualifyfreq=600<br />
transport=ws,wss<br />
encryption=yes<br />
avpf=yes<br />
force_avp=yes<br />
icesupport=yes<br />
rtcp_mux=yes<br />
directmedia=no<br />
disallow=all<br />
allow=opus,alaw,vp8,h264<br />
dtmfmode=info<br />
nat=yes<br />
dtlsenable=yes<br />
dtlsverify=fingerprint<br />
dtlscertfile=/etc/asterisk/certificate/fullchain.pem<br />
dtlsprivatekey=/etc/asterisk/certificate/privkey.pem<br />
dtlscafile=/etc/asterisk/certificate/key/chain.pem<br />
dtlssetup=actpass
</p1>
<h3>http.conf configuration:</h3>
<p>Configure the <a href="https://wiki.asterisk.org/wiki/display/AST/Asterisk+Builtin+mini-HTTP+Server">built-in http server</a>. Bindport and bindaddr is the port and address for res_http_websocket and chan_sip used for websocket. (Websocket is implemented by the res_http_websocket module int the /ws sub-directory only)</p>
<p1>The following changes need to be made on /etc/asterisk/http.conf&nbsp; file:</p1>
<p1 style="color: #bf4f4c">[general]<br />
enabled = yes<br />
bindaddr = 0.0.0.0<br />
bindport = 8080<br />
tlsenable = yes<br />
tlsbindport = 8089<br />
tlscertfile = /etc/asterisk/certificate/fullchain.pem<br />
tlsprivatekey = /etc/asterisk/certificate/privkey.pem</p1>
<h3>rtp.conf&nbsp; configuration:</h3>
<p>Enable ICE&nbsp; and STUN (you can use any other STUN server instead of google) and set an RTP port range.</p>
<p1>The following changes need to be made on /etc/asterisk/rtp.conf&nbsp; file:</p1>
<p1 style="color: #bf4f4c">[general]<br />
rtpstart=10000<br />
rtpend=20000<br />
icesupport=yes<br />
;rtpchecksums=no<br />
;strictrtp=yes<br />
;stunaddr=stun.l.google.com:19302 &nbsp;</p1>
<h3>Firewall configuration:</h3>
<p>Make sure to enable the following ports:<br />
SIP port: UDP/TCP 5060<br />
Websocket port: TCP 8089<br />
RTP ports: UDP 10000 - 20000<br />
<br />
(Re)Start Asterisk.<br />
At this moment you should be able to make calls between a WebRTC and a SIP client.</p>
<h3>Configure WebRTC client:</h3>
<p>You can use any WebRTC SIP client with Asterisk. Just set it&rsquo;s websocket SIP address to point to your asterisk. With the <a href="{$WEBPHONE}">WebRTC SIP Phone</a>, you will need the following configuration:</p>
<p1>Authorization name:&nbsp; SIP_REGNAME<br>
Password: YOUR_SIP_PASSWORD<br>
WebRTC Server Address:&nbsp; wss://ASTERISK_IP:8089/ws</p1>
<p style="text-align: center;"><img alt="WebRTC SIP Phone" src="templates/{$SKIN_NAME}/images/phonesetup.png" style="text-align:center"/></p>
<h3>Extra configuration:</h3>
<p>To be able to make calls also from Chrome, you need:<br />
-Secure webserver to host your web client files (HTTPS)<br />
-Secure websocket (WSS)<br />
You can obtain SSL certificates for free from <a href="https://www.startssl.com/">startssl</a> or <a href="https://letsencrypt.org/">letsencrypt</a> or cheap certificate from <a href="https://www.namecheap.com/">comodo</a>.<br />
Make sure to apply the certificate for both your web server and asterisk websocket and dtls.<br />
To configure secure calling in Asterisk, check this<a href="https://wiki.asterisk.org/wiki/display/AST/Secure+Calling+Tutorial"> guide</a>.<br />
Optionally you might also setup a <a href="https://github.com/coturn/coturn">turn</a> gateway (this can be useful to bypass firewalls and to help for peer to peer calls if you have such requirements). A tutorial can be found <a href="http://danielpocock.com/using-resiprocate-to-connect-asterisk-webrtc">here</a>.</p>
<h3>Troubleshooting:</h3>
<p>To be able to see the registration and call details in the CLI: Set the VERBOSE messages to go to the console and turn verbosity to at least 3.
If you have any kind of issues during your asterisk setup, check the logs (/var/log/asterisk) by opening the verbose file with nano or other editor.
Go to your logs end and search ERRORs or WARNINGs.
For example: Can't provide secure audio requested in sdp offer =&gt; then set the dtlsenable=yes for webrtc client peer.<br />
A few common issue:<br />
- HTTP 404 Not Found response usually occures if the JavaScript library is using an incorrect URL for WebSocket access. The URL must use the /ws sub-directory.<br />
- SIP 488 Not acceptable here response when placing a call to Asterisk: set avpf=yes and make sure to enable at least G.711 (PCMU, PCMA) on the caller/server/called.<br />
- SIP 400 Bad Request response when registering using WebSocket. Update chan_sip to a newer version.</p>
<p>This <a href="http://forums.digium.com/viewtopic.php?f=1&amp;t=90167">forum post</a> offers valuable resources for troubleshooting Asterisk WebRTC related issues.</p>
<p1><a href="https://wiki.asterisk.org/wiki/display/AST/Secure+Calling+Tutorial">Asterisk Secure Calling Guide</a> can help you setup dtls certificates.</p1>
<p1><a href="https://wiki.asterisk.org/wiki/display/AST/WebRTC+tutorial+using+SIPML5">Asterisk WebRTC tutorial</a></p1>
<h3>Warning:</h3>
<p>A production capable WebRTC setup depends on the details. With the default settings you might be able to achieve 80% call success ratio, so you might not be aware how broken is your implementation in various conditions like corporate networks where UDP is usually blocked. WebRTC-SIP can be a very tricky topic and your project success will depend on the followings:<br />
- WebRTC-SIP protocol conversion in Asterisk (to handle all corner cases)<br />
- Media routing and converting from DTLS/SRTP to simple RTP <br />
- NAT handling built-in Asterisk and your configuration (proper handling of candidates, inserting own candidate)<br />
- correct STUN setup (fast / simple stun / multi-homed)<br />
- correct TURN setup (this can be very tricky as for working with asterisk you should disable UDP turn since that is better handled by Asterisk)<br />
- webrtc sip client implementation details (handling corner cases in signaling, auto reconnecting, fine-tuned media settings, correct ICE setup)<br />
- media tunneling over HTTP port 80 (as this is the only port which we can expect to be available from all networks), but still preferring UDP whenever possible<br />
- peer to peer media routing whenever possible (and failback to server or TURN assisted relay)<br />
- codec conversion: the most important <a href="http://stackoverflow.com/questions/37186859/codec-used-in-a-webrtc-stream">codec's in WebRTC</a> are OPUS and G.711 while your SIP peers might require other codec such as G.729 or GSM. Make sure that your server can handle this automatically.</p>
<p>Common WebRTC SIP clients such as SIPML5, SIP.js doesn&rsquo;t take too much care on these details leaving this configuration tasks up to the users. If you are not an expert in WebRTC-SIP client, then we recommend a solution such as the WebRTC SIP Phone extension for Web Browser which is fine-tuned for Asterisk out of the box. Otherwise make sure to test your implementation in various conditions, from behind various routers/NAT&rsquo;s and different firewall configurations.</p>
{php}}{/php}
<br><br><br><br><br>
  <div class="centerrow">
{php}
    $DBHandle = DbConnect();
    $instance_table = new Table();
    $QUERY = "SELECT configuration_key FROM cc_configuration where configuration_key in ('MODULE_PAYMENT_AUTHORIZENET_STATUS','MODULE_PAYMENT_PAYPAL_BASIC_STATUS','MODULE_PAYMENT_MONEYBOOKERS_STATUS','MODULE_PAYMENT_WORLDPAY_STATUS','MODULE_PAYMENT_PLUGNPAY_STATUS','MODULE_PAYMENT_WM_STATUS') AND configuration_value='True'";
    $payment_methods = $instance_table->SQLExec($DBHandle, $QUERY);
    $QUERY = "SELECT configuration_value FROM cc_configuration where configuration_key='MODULE_PAYMENT_WM_WMID'";
    $wmid = $instance_table->SQLExec($DBHandle, $QUERY);
    $show_logo = '';
    for ($index=0; $index<sizeof($payment_methods); $index++) {
	if ($payment_methods[$index][0] == "MODULE_PAYMENT_MONEYBOOKERS_STATUS") {
	    $show_logo .= '<a href="https://www.moneybookers.com/app/?rid=811621" target="_blank"><img src="' . KICON_PATH . '/moneybookers.gif" alt="Moneybookers"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PLUGNPAY_STATUS") {
	    $show_logo .= '<a href="http://www.plugnpay.com/" target="_blank"><img src="' . KICON_PATH . '/plugnpay.png" alt="plugnpay.com"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_WM_STATUS") {
	    $show_logo .= '<a href="http://www.web.money/" target="_blank"><img src="' . KICON_PATH . '/webmoney_virified.png" alt="WebMoney"/></a>';
	} elseif ($payment_methods[$index][0] == "MODULE_PAYMENT_PAYPAL_BASIC_STATUS") {
	    $show_logo .= '<a href="https://www.paypal.com/en/mrb/pal=PGSJEXAEXKTBU" target="_blank"><img src="' . KICON_PATH . '/payments_paypal.gif" alt="Paypal"/></a>';
	}

    }
//    $show_logo .= '<a href="http://www.gnu.org/licenses/agpl.html" target="_blank"><img src="' . KICON_PATH . '/agplv3-155x51.png" alt="AGPLv3"/></a>';
    echo $show_logo;
{/php}
{if (false)}
<!--LiveInternet counter-->
<script>
  document.write("<a href='http://www.liveinternet.ru/click' "+"target=_blank><img src='//counter.yadro.ru/hit?t39.2;r"+escape(document.referrer)+((typeof(screen)=="undefined")?"":";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+";"+Math.random()+"' alt='' title='LiveInternet' "+"border='0' width='31' height='31'><\/a>")
</script>
<!--/LiveInternet end-->
{/if}
  </div>
  <div>
  <a id="refresh" value="Refresh" onClick="opback()"> <!-- "history.go()"> -->
    <svg class="refreshicon"   version="1.1" id="Capa_1"  xmlns="https://www.w3.org/2000/svg" xmlns:xlink="https://www.w3.org/1999/xlink" x="0px" y="0px"
         width="25px" height="25px" viewBox="0 0 322.447 322.447" style="enable-background:new 0 0 322.447 322.447;"
         xml:space="preserve">
         <path  d="M321.832,230.327c-2.133-6.565-9.184-10.154-15.75-8.025l-16.254,5.281C299.785,206.991,305,184.347,305,161.224
                c0-84.089-68.41-152.5-152.5-152.5C68.411,8.724,0,77.135,0,161.224s68.411,152.5,152.5,152.5c6.903,0,12.5-5.597,12.5-12.5
                c0-6.902-5.597-12.5-12.5-12.5c-70.304,0-127.5-57.195-127.5-127.5c0-70.304,57.196-127.5,127.5-127.5
                c70.305,0,127.5,57.196,127.5,127.5c0,19.372-4.371,38.337-12.723,55.568l-5.553-17.096c-2.133-6.564-9.186-10.156-15.75-8.025
                c-6.566,2.134-10.16,9.186-8.027,15.751l14.74,45.368c1.715,5.283,6.615,8.642,11.885,8.642c1.279,0,2.582-0.198,3.865-0.614
                l45.369-14.738C320.371,243.946,323.965,236.895,321.832,230.327z"/>
    </svg>
  </a>
  </div>
{php}if(!isset($_COOKIE["cookiescript"])) {{/php}
<div id="cookiescript_container">
    <div id="cookiescript_wrapper">
	<span id="cookiescript_header">{php} echo gettext("This website uses cookies"){/php}</span>
{php}if(LANGUAGE=="russian"){{/php}
        &nbsp;&nbsp;&nbsp;Информируем, что на этом сайте используются cookie-файлы. Cookie-файлы используются для выполнения идентификации пользователя и накапливания данных о посещении сайта. Продолжая пользоваться этим веб-сайтом, Вы соглашаетесь на сбор и использование данных cookie-файлов на Вашем устройстве. Свое согласие Вы в любой момент можете отозвать, удалив сохраненные cookie-файлы.
{php}}elseif(LANGUAGE=="ukrainian"){{/php}
        &nbsp;&nbsp;&nbsp;Інформуємо, що на цьому сайті використовуються cookie-файли. Cookie-файли використовуються для виконання ідентифікації користувача і накопичення даних про відвідування сайту. Продовжуючи користуватися цим веб-сайтом, Ви погоджуєтесь на збір і використання даних cookie-файлів на Вашому пристрої. Свою згоду Ви в будь-який момент можете відкликати, видаливши збережені cookie-файли.
{php}}elseif(LANGUAGE=="german"){{/php}
        &nbsp;&nbsp;&nbsp;Auf dieser Webseite werden Cookies und ähnliche Technologien verwendet. Mit dem Klick auf "Zustimmen" akzeptierst Du die Verarbeitung und Weitergabe Deiner Daten an Drittanbieter. Diese werden auf unserer Seite sowie auf Seiten von Drittanbietern zur Analyse, Retargeting und zur Ausspielung von personalisierten Inhalten und Werbung genutzt. In unseren <a href="policy">Datenschutzhinweisen</a> findest Du weitere Informationen zur Datenverarbeitung durch Drittanbieter. Die Verwendung von Cookies kannst Du jederzeit über die Einstellungen anpassen.
{php}}else{{/php}
        &nbsp;&nbsp;&nbsp;We use cookies to ensure you have the best browsing experience on our website. By using our site, you acknowledge that you have read and understood our <a href="policy">Privacy Policy</a>.
{php}}{/php}<br>
        <div id="cookiescript_buttons">
            <div id="cookiescript_accept" onClick="closeCookieScript();">{php} echo gettext("Got it!"){/php}</div>
        </div>
    </div>
</div>
<script>
function closeCookieScript() {
    $("#cookiescript_container").remove();
    var expiryDate = new Date();
    expiryDate.setMonth(expiryDate.getMonth() + 3);
    document.cookie = "cookiescript=set;expires="+expiryDate.toGMTString()+";path=/;";
}
</script>
{php}}{/php}
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XG19QQ243S"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-XG19QQ243S');
</script>
<!-- /Google Analytics -->
<script src="./javascript/jquery/jquery-1.7.2.min.js"></script>
<script src="./javascript/index.js"></script>
</body>
</html>
