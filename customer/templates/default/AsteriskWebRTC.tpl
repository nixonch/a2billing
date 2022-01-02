<main role="main">
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
<p3>[global]</p3>
<p3>type=global</p3>
<p3>user_agent=FPBX-AsteriskNOW-12.0.76.2(13.2.0)</p3>
<p3>realm=192.168.213.134 ;(you need to rewrite this with your IP address)</p3>
<p3>bindport=5060 ;(you need to rewrite this with your servers SIP port)</p3>
<p3>transport=udp,ws,wss</p3>
<p3 style="-webkit-margin-before:1em">[simpletrans]</p3>
<p3>type=transport</p3>
<p3>protocol=udp</p3>
<p3>bind=0.0.0.0 </p3>
<p3 style="-webkit-margin-before:1em">[transport-ws]</p3>
<p3>type=transport</p3>
<p3>protocol=ws</p3>
<p3>bind=0.0.0.0</p3>
<p3 style="-webkit-margin-before:1em">[endpoint-basic](!)</p3>
<p3>type=endpoint</p3>
<p3>transport=simpletrans</p3>
<p3>context=internal</p3>
<p3>disallow=all</p3>
<p3>allow=ulaw</p3>
<p3>rtcp_mux=yes</p3>
<p3 style="-webkit-margin-before:1em">[auth-userpass](!)</p3>
<p3>type=auth</p3>
<p3>auth_type=userpass</p3>
<p3 style="-webkit-margin-before:1em">[aor-single-reg](!)</p3>
<p3>type=aor</p3>
<p3>max_contacts=10</p3>
<p3 style="-webkit-margin-before:1em">[6001](endpoint-basic)</p3>
<p3>auth=auth6001</p3>
<p3>aors=6001</p3>
<p3 style="-webkit-margin-before:1em">[auth6001](auth-userpass)</p3>
<p3>username=6001</p3>
<p3>password=6666</p3>
<p3 style="-webkit-margin-before:1em">[6001](aor-single-reg)</p3>
<p3 style="-webkit-margin-before:1em">[6002](endpoint-basic)</p3>
<p3>auth=auth6002</p3>
<p3>aors=6002</p3>
<p3 style="-webkit-margin-before:1em">[auth6002](auth-userpass)</p3>
<p3>password=6002</p3>
<p3>username=6002</p3>
<p3 style="-webkit-margin-before:1em">[6002](aor-single-reg)</p3>
<h3>Dialplan:</h3>
<p>A simple plan to be able to make calls from WebRTC to SIP assigning the 6666 number to the 6001 account in /etc/asterisk/extensions.conf:</p>
<p3>[internal]</p3>
<p3>exten =&gt; 6666,1,Dial(PJSIP/6001)</p3>
<h3>Logs:</h3>
<p>At /var/logs/asterisk/<br/>
Enable sip log: in sip.conf set <span style="color: #9f2f2c">sipdebug=yes</span><br />
Set high loglevel &ndash; in logger.conf set the below line</p>
<p3>[logfiles]</p3>
<p3>Verbose =&gt; notice,warning,error,debug,verbose<wbr>,dtmf</p3>
<h3>WebRTC</strong>:</h3>
<p>You need to make changes in these config files: sip.conf, http.conf, rtp.conf</p>
<h3>sip.conf configuration:</h3>
<p>In the sip.conf the [general] section should look like this:</p>
<p3>[general]</p3>
<p3>udpbindaddr=YOUR IP:5060</p3>
<p3>realm=YOUR IP</p3>
<p3>sipdebug=yes</p3>
<p3>transport=udp,tcp,tls,ws,wss</p3>
<h3>Setup a WebRTC extension:</h3>
<p>An extension to be used by the web client should have the following format:</p>
<p1 style="color:#9f2f2c">
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
<p1 style="color: #9f2f2c">[general]<br />
enabled=yes<br />
bindaddr=0.0.0.0<br />
bindport=8080<br />
tlsenable=yes<br />
tlsbindport=8089<br />
tlscertfile=/etc/asterisk/certificate/fullchain.pem<br />
tlsprivatekey=/etc/asterisk/certificate/privkey.pem</p1>
<h3>rtp.conf&nbsp; configuration:</h3>
<p>Enable ICE&nbsp; and STUN (you can use any other STUN server instead of google) and set an RTP port range.</p>
<p1>The following changes need to be made on /etc/asterisk/rtp.conf&nbsp; file:</p1>
<p1 style="color: #9f2f2c">[general]<br />
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
<p>You can use any WebRTC SIP client with Asterisk. Just set it&rsquo;s websocket SIP address to point to your asterisk. With <a href="{$WEBPHONE}">WebRTC SIP Phone</a>, you will need the following configuration:</p>
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
<p>Common WebRTC SIP clients such as SIPML5, SIP.js doesn&rsquo;t take too much care on these details leaving this configuration tasks up to the users. If you are not an expert in WebRTC-SIP client, then we recommend a solution such as the <a href="{$WEBPHONE}">WebRTC SIP Phone extension</a> for Web Browser which is fine-tuned for Asterisk out of the box. Otherwise make sure to test your implementation in various conditions, from behind various routers/NAT&rsquo;s and different firewall configurations.</p>
{php}}{/php}
</main>
