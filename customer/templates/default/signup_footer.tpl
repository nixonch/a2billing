
               <br></br>               
               <br>
              
       </div>
{if !isset($smarty.cookies.cookiescript) && !isset($smarty.cookies.UICSESSION)}
<div id="cookiescript_container">
    <span id="cookiescript_header">{gettext("This website uses cookies")}</span>
    <div id="cookiescript_wrapper">
{if $smarty.const.LANGUAGE=="russian"}
        &nbsp;&nbsp;&nbsp;Информируем, что на этом сайте используются cookie-файлы. Cookie-файлы используются для выполнения идентификации пользователя и накапливания данных о посещении сайта. Продолжая пользоваться этим веб-сайтом, Вы соглашаетесь на сбор и использование данных cookie-файлов на Вашем устройстве. Свое согласие Вы в любой момент можете отозвать, удалив сохраненные cookie-файлы.
{elseif $smarty.const.LANGUAGE=="german"}
        &nbsp;&nbsp;&nbsp;Auf dieser Webseite werden Cookies und ähnliche Technologien verwendet. Mit dem Klick auf "Zustimmen" akzeptierst Du die Verarbeitung und Weitergabe Deiner Daten an Drittanbieter. Diese werden auf unserer Seite sowie auf Seiten von Drittanbietern zur Analyse, Retargeting und zur Ausspielung von personalisierten Inhalten und Werbung genutzt. In unseren <a href="policy">Datenschutzhinweisen</a> findest Du weitere Informationen zur Datenverarbeitung durch Drittanbieter. Die Verwendung von Cookies kannst Du jederzeit über die Einstellungen anpassen.
{elseif $smarty.const.LANGUAGE=="ukrainian"}
        &nbsp;&nbsp;&nbsp;Інформуємо, що на цьому сайті використовуються cookie-файли. Cookie-файли використовуються для виконання ідентифікації користувача і накопичення даних про відвідування сайту. Продовжуючи користуватися цим веб-сайтом, Ви погоджуєтесь на збір і використання даних cookie-файлів на Вашому пристрої. Свою згоду Ви в будь-який момент можете відкликати, видаливши збережені cookie-файли.
{else}
        &nbsp;&nbsp;&nbsp;We use cookies to ensure you have the best browsing experience on our website. By using our site, you acknowledge that you have read and understood our <a href="policy">Privacy Policy</a>.
{/if}<br>
        <div id="cookiescript_buttons">
            <div id="cookiescript_accept" onClick="closeCookieScript();">{gettext("Got it!")}</div>
        </div>
    </div>
</div>
<script>
function closeCookieScript() {
    $("#cookiescript_container").remove();
    document.cookie = "PHPSESSID=;max-age=-1;path=/";
    document.cookie = "UICSESSION="+self.crypto.randomUUID()+";path=/";
    var expiryDate = new Date();
    expiryDate.setMonth(expiryDate.getMonth() + 12);
    document.cookie = "cookiescript=set;expires="+expiryDate.toGMTString()+";path=/";
}
</script>
{/if}
</body>
{if (!empty($A2Bconfig.webcustomerui.google_ga4_id))}
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$A2Bconfig.webcustomerui.google_ga4_id|escape:'url'}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$A2Bconfig.webcustomerui.google_ga4_id|escape:'javascript'}');
</script>
<!-- /Google Analytics -->
{/if}
</html>
