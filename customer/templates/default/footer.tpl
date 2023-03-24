
			</div>
			</div>
		</div>
		
		<div style="clear: both;"></div>
	
	</div>
	
	<br><br>
	{include file="profiler.tpl"}
{php}if(!isset($_COOKIE["cookiescript"]) && !isset($_COOKIE["UICSESSION"])) {{/php}
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
  function getDeviceWidth() {
    var deviceWidth = window.orientation == 0 ? window.screen.width : window.screen.height;
    return deviceWidth;
  }
  var deviceWidth = getDeviceWidth()-10;
  if (deviceWidth < 600) {
    $("#cookiescript_wrapper").css('max-width',deviceWidth);
    $("#cookiescript_wrapper").css('margin-left','0px');
  }
  function closeCookieScript() {
    $("#cookiescript_container").remove();
    document.cookie = "PHPSESSID=;max-age=-1;path=/";
    document.cookie = "UICSESSION="+self.crypto.randomUUID()+";path=/";
    var expiryDate = new Date();
    expiryDate.setMonth(expiryDate.getMonth() + 12);
    document.cookie = "cookiescript=set;expires="+expiryDate.toGMTString()+";path=/";
  }
</script>
{php}}{/php}
</body>
</html>

