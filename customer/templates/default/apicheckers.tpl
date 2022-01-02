<main role="main">
<select name="ui_language" id="ui_language" class="lang_select">
    <option value="english" {php} if(LANGUAGE=="english") echo "selected";{/php} >English</option>
    <option value="russian" {php} if(LANGUAGE=="russian" || LANGUAGE=="ukrainian") echo "selected";{/php} >Русский</option>
</select>
{php} if (LANGUAGE=="russian" || LANGUAGE=="ukrainian") { {/php}
<h1>Настройка собственного веб-сервиса проверки баланса и стоимости звонка</h1>
<p>Если вы используете расширение <a href="{$WEBPHONE}">WebRTC SIP Phone</a> для Chrome совместно с вашим сервером, скорее всего вам захочется видеть некоторую дополнительную информацию в раскрытом окне <a href="{$WEBPHONE}">расширения</a>. На данный момент реализованы запросы на заполнение верхней информационной строки (текущий баланс) и бегущей строки над полем набора номера (стоимость звонка). Создав несложный механизм ответов на запросы, вы получите качественный скачок в предоставлении своих услуг.</p>
<p>Чтобы включить запросы, раскройте <a href="{$WEBPHONE}">расширение</a> и перейдите в <em>Control Panel &gt; Edit Account</em>, затем в строке «<em>Link to custom balance and rate checkers</em>» введите URL-адрес своей веб-службы проверок.<br>
Пример URL-адреса для запроса данных:</p>
<p1 style="color: #bf4f4c">https://my.web.com/request.php</p1>
<p1>На данный момент <a href="{$WEBPHONE}">расширение</a> передаёт запросы методом POST.</p1><br>
<p>Для получения ответа <b>о текущем балансе</b> в запрос вкладываются такие данные:</p>
<p1 style="color: #bf4f4c">u=&#123;Authorization name&#125;<br>
p=&#123;Password&#125;<br>
html=2</p1>
<p>Ваша служба должна сформировать ответ в XML-формате в соответствии с таким шаблоном:</p>
<p4>&lt;response&gt;<br>
&lt;error&gt;0&lt;/error&gt;<br>
&lt;balanceString&gt;$60.20&lt;/balanceString&gt;<br>
&lt;rereg&gt;0&lt;/rereg&gt;</p4>
<p4>&lt;/response&gt;</p4>
<p1>«$60.20» будет показано в качестве текущего баланса.<br>
Поле &lt;rereg&gt; - это рекомендация со стороны сервера, произвести(1) процедуру перерегистрации или нет(0).</p1><br>
<p>Для получения ответа <b>о стоимости звонка</b> в запрос вкладываются такие данные:</p>
<p1 style="color: #bf4f4c">u=&#123;Authorization name&#125;<br>
p=&#123;Password&#125;<br>
t=&#123;Number&#125;</p1>
<p>Ваша служба должна сформировать ответ в XML-формате в соответствии с таким шаблоном:</p>
<p4>&lt;response&gt;<br>
&lt;error&gt;0&lt;/error&gt;
<p6>&lt;callRateString&gt;United States = EUR 0.0044-0.0098 / min&lt;/callRateString&gt;</p6></p4>
<p4>&lt;/response&gt;</p4>
<p1>«United States = EUR 0.0044-0.0098 / min» будет показано в качестве информации о стоимости звонка.</p1>
{php}}else{{/php}
<h1>Setting Up Your Own Web Service to Check the Balance and Call Rates</h1>
<p>For some providers balance check service is already hardcoded in the app. For those providers that are not covered or if you want to override the hardcoded setup you need to set up your own web service.<p>
<p>Also, if you are using <a href="{$WEBPHONE}">WebRTC SIP Phone</a> extension for the Chrome browser together with your own server, most likely you will want to see some additional information in the expanded <a href="{$WEBPHONE}">Extension</a> window. Requests for filling in the top information line (current balance) and a creeping line above the dialing field (call rate) have been implemented. By creating a simple mechanism for responding to requests, you will receive a qualitative leap in the provision of your services.</p>
<p>To enable requests, please, expand the <a href="{$WEBPHONE}">Extension</a> and go to <em>Control Panel &gt; Edit Account</em>, then in the <em>«Link to custom balance and rate checkers»</em> line enter the URL of your check web service.<br>
Example URL for web callback might be:</p>
<p1 style="color: #bf4f4c">https://my.web.com/request.php</p1>
<p1>Only supported method is POST at the moment.</p1>
<p>To receive an return response <b>about the current balance</b>, the following data contains into the request:</p>
<p1 style="color: #bf4f4c">u=&#123;Authorization name&#125;<br>
p=&#123;Password&#125;<br>
html=2</p1>
<p>The return format for the balance checker is a simple XML</p>
<p4>&lt;response&gt;<br>
&lt;error&gt;0&lt;/error&gt;<br>
&lt;balanceString&gt;$60.20&lt;/balanceString&gt;<br>
&lt;rereg&gt;0&lt;/rereg&gt;</p4>
<p4>&lt;/response&gt;</p4>
<p1>«$60.20» will be displayed as the balance.<br>
Respons named &lt;rereg&gt; is recomendation to do reregistration if needed.</p1>
<p>To receive an return response <b>about the call rate</b>, the following data contains into the request:</p>
<p1 style="color: #bf4f4c">u=&#123;Authorization name&#125;<br>
p=&#123;Password&#125;<br>
t=&#123;Number&#125;</p1>
<p>The return format for the rate checker is a simple XML</p>
<p4>&lt;response&gt;<br>
&lt;error&gt;0&lt;/error&gt;
<p6>&lt;callRateString&gt;United States = EUR 0.0044-0.0098 / min&lt;/callRateString&gt;</p6></p4>
<p4>&lt;/response&gt;</p4>
<p1>«United States = EUR 0.0044-0.0098 / min» will be displayed as the call rate.</p1>
{php}}{/php}
</main>
