<main role="main">
<select name="ui_language" id="ui_language" class="lang_select">
    <option value="english" {php} if(LANGUAGE=="english") echo "selected";{/php} >English</option>
    <option value="russian" {php} if(LANGUAGE=="russian" || LANGUAGE=="ukrainian") echo "selected";{/php} >Русский</option>
</select>
{php} if (LANGUAGE=="russian" || LANGUAGE=="ukrainian") { {/php}
<h1>Интеграция WebRTC SIP Phone с вашей CRM</h1>
<p>Для интеграции с CRM вы можете использовать способность <a href="{$WEBPHONE}">расширения</a> посылать предопределенные заголовки в HTTP запросе методом HEAD в момент ответа на входящий звонок. Эти заголовки задаются двумя способами...</p>
<p><b>1-й способ</b> — с помощью 2-х настроек прямо в <a href="{$WEBPHONE}">расширении</a>.</p>
<p1><ul style="margin:auto">
<li><em>Message to send on answer</em> — здесь впишите требуемые заголовки, например:<br>
<text style="color:#9f2f2c">Message: Call\r\nCallerID: $&#123;CALLERNUM&#125;</text><br>
где $&#123;CALLERNUM&#125; - ключевое слово, вместо которого будет подставлен номер позвонившего абонента. Таким образом к стандартным заголовкам HTTP запроса будут добавлены ещё два, предопределённых вами.</li>
<li><em>IP:Port to send message on answer</em> — тут задайте IP-адрес и порт получателя, например: <text style="color:#9f2f2c">127.0.0.1:6001</text>.</li>
</ul></p1>
<p><b>2-й способ</b> — с помощью SIP заголовков, посылаемых от АТС вместе с входящим звонком, в первом же INVITE сообщении. Просто добавьте два заголовка к инициирующему звонок INVITE сообщению, как в этом примере из диалплана Asterisk:</p>
<p3 style="padding-left:5.5em">same => n, SIPAddHeader(X-mess-text: Message: Call\\r\\nCallerID: $&#123;CALLERID(num)&#125;)</p3>
<p3 style="padding-left:5.5em">same => n, SIPAddHeader(X-mess-port: 6001)</p3>
<p3 style="padding-left:5.5em">same => n, DIAL(1001)</p3>
<p1>В итоге, заданные в <em>X-mess-text</em> заголовки, будут посланы в 127.0.0.1:6001 в момент, когда абонент 1001 ответит на звонок.</p1><br>
<p1><b><u>Замечания.</u></b></p1>
<p1><ul style="margin:auto">
<li>При использовании 2-го способа, настройки 1-го способа не применятся и не сработают.</li>
<li>При получении и обработке HTTP запроса на стороне CRM следует учитывать, что заданные заголовки могут располагаться в случайном порядке и в случайном месте. Вот пример HTTP запроса, который будет получать ваша CRM:<br>
<b>HEAD / HTTP/1.1<br>
Host: 127.0.0.1:6001<br>
Connection: keep-alive<br>
<text style="color:blue">CallerID: +1234567890</text>
<p3 style="color:#0f2b4e;text-indent:-3rem">User-Agent: Mozilla/5.0 (X11; Linux x86_64)</p3>
<text style="color:blue">Message: Call</text><br>
Accept: */*<br>
Accept-Encoding: gzip, deflate, br
<p3 style="color:#0f2b4e;text-indent:-3rem;word-break:break-all">Accept-Language: uk,ru;q=0.9,en;q=0.8,ru-RU;q=0.7,en-US;q=0.6,he-IL;q=0.5,he;q=0.4</p3></b></li></ul></p1>
<br><p1>Ваши вопросы и пожелания, пожалуйста, направляйте по адресу <a href="mailto:{$MAILTO}">{$MAILTO}</a></p1>
{php}}else{{/php}
<h1>Integration of WebRTC SIP Phone with your CRM</h1>
<p>To integrate with CRM, you can use the <a href="{$WEBPHONE}">Extension's</a> built-in function to send predefined headers in an HTTP request using the HEAD method when answering an incoming call. These headers are set in two ways ...</p>
<p><b>1st method</b> — using two settings directly in the <a href="{$WEBPHONE}">Extension</a>.</p>
<p1><ul style="margin:auto">
<li><em>Message to send on answer</em> — here you need to specify the required headers, for example:<br>
<text style="color:#9f2f2c">Message: Call\r\nCallerID: $&#123;CALLERNUM&#125;</text><br>
where $&#123;CALLERNUM&#125; will be replaced to natural CallerID. Thus, two more predefined by you will be added to the standard HTTP request headers.</li>
<li><em>IP:Port to send message on answer</em> — here put IP-address and port of receiver side, for example: <text style="color:#9f2f2c">127.0.0.1:6001</text>.</li>
</ul></p1>
<p><b>2nd method</b> — using SIP headers along with first INVITE message. To do this, add two headers to the initiating SIP INVITE message. For a better understanding, use this example running in the Asterisk dialplan:</p>
<p3 style="padding-left:5.5em">same => n, SIPAddHeader(X-mess-text: Message: Call\\r\\nCallerID: $&#123;CALLERID(num)&#125;)</p3>
<p3 style="padding-left:5.5em">same => n, SIPAddHeader(X-mess-port: 6001)</p3>
<p3 style="padding-left:5.5em">same => n, DIAL(1001)</p3>
<p1>As a result, the headers set in <em>X-mess-text</em> will be sent to 127.0.0.1:6001 at the moment when callee 1001 answers the call.</p1><br>
<p1><b><u>Notes.</u></b></p1>
<p1><ul style="margin:auto">
<li>When you are using the 2nd method, the settings of the 1st method will not have any affect to sending headers.</li>
<li>When receiving and processing an HTTP request on the CRM side, you need to keep in mind that the specified headers can be located in a random order and in a random place. Here is an example of an HTTP request your CRM will receive:<br>
<b>HEAD / HTTP/1.1<br>
Host: 127.0.0.1:6001<br>
Connection: keep-alive<br>
<text style="color:blue">CallerID: +1234567890</text>
<p3 style="color:#0f2b4e;text-indent:-3rem">User-Agent: Mozilla/5.0 (X11; Linux x86_64)</p3>
<text style="color:blue">Message: Call</text><br>
Accept: */*<br>
Accept-Encoding: gzip, deflate, br
<p3 style="color:#0f2b4e;text-indent:-3rem">Accept-Language: uk,ru;q=0.9,en;q=0.8,ru-RU;q=0.7,en-US;q=0.6,he-IL;q=0.5,he;q=0.4</p3></b></li></ul></p1>
<br><p1>Please, send your questions and wishes to <a href="mailto:{$MAILTO}">{$MAILTO}</a></p1>
{php}}{/php}
</main>
