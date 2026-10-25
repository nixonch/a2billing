<main role="main">
<select name="ui_language" id="ui_language" class="lang_select">
    <option value="english" {php} if(LANGUAGE=="english") echo "selected";{/php} >English</option>
    <option value="russian" {php} if(LANGUAGE=="russian" || LANGUAGE=="ukrainian") echo "selected";{/php} >Русский</option>
</select>
{php} if (LANGUAGE=="russian" || LANGUAGE=="ukrainian") { {/php}
<h1>Политика конфиденциальности {$CCMAINTITLE} и WebRTC SIP Phone</h1>
<p>Настоящая политика описывает обработку данных на сайте {$CCMAINTITLE}, при предоставлении услуг связи и в расширении для Chrome WebRTC SIP Phone with Click2Dial (далее — «WebRTC SIP Phone»). Оператором сайта и разработчиком расширения является {$CCMAINTITLE}. По вопросам конфиденциальности можно обратиться по адресу <a href="mailto:{$MAILTO}">{$MAILTO}</a> или через <a href="{$CUSTOMER_INTERFACE_URL}support">службу поддержки</a>.</p>

<h2>Какие данные обрабатывает сайт и сервис {$CCMAINTITLE}</h2>
<p>В зависимости от используемых функций и предоставленных вами сведений мы можем обрабатывать:</p>
<ul>
    <li>регистрационные и контактные данные, например имя, адрес, адрес электронной почты и номер телефона;</li>
    <li>данные учётной записи и авторизации, настройки услуг и сведения, необходимые для обслуживания аккаунта;</li>
    <li>платёжные и расчётные сведения, баланс, историю операций и данные, необходимые для выполнения требований бухгалтерского и налогового законодательства;</li>
    <li>телефонные номера, сведения о времени, направлении, продолжительности и стоимости звонков, а также другие данные, необходимые для оказания услуг связи;</li>
    <li>обращения в поддержку, отзывы, указанный для ответа адрес электронной почты и выбранные вами настройки получения ответов или новостей;</li>
    <li>технические сведения: IP-адрес, дата и время запроса, User-Agent, запрошенная страница, результат запроса и приблизительное местоположение, определённое по IP-адресу. Эти сведения используются для работы сервиса, диагностики, защиты от злоупотреблений и ограничения частоты запросов.</li>
</ul>
<p>Если платёж выполняется через внешнего платёжного оператора, он обрабатывает платёжные данные по собственной политике конфиденциальности. Мы не получаем и не храним полный номер банковской карты, если он вводится непосредственно на стороне такого оператора.</p>

<h2>Какие данные обрабатывает расширение WebRTC SIP Phone</h2>
<p>Расширение обрабатывает только данные, необходимые для настройки SIP-аккаунта и работы заявленных функций:</p>
<ul>
    <li>адрес, протокол и порт SIP/WebSocket-сервера;</li>
    <li>имя пользователя или внутренний номер, пароль, отображаемое имя и идентификатор голосовой почты;</li>
    <li>настройки перевода вызовов, проверки баланса и тарифа, а также необязательной CRM-интеграции;</li>
    <li>состояние функции Click2Call и номер из ссылки <code>tel:</code>, по которой непосредственно нажал пользователь;</li>
    <li>набранные и полученные телефонные номера, последний набранный номер, журнал вызовов, сведения о состоянии регистрации и звонка;</li>
    <li>доступ к микрофону и аудиопоток во время звонка.</li>
</ul>
<p>Настройки SIP-аккаунта, включая данные авторизации, последний набранный номер и журнал вызовов сохраняются локально в хранилище расширения в профиле Chrome. Временные сведения о состоянии интерфейса и пропущенных вызовах могут храниться в хранилище сеанса Chrome. Текущая версия не синхронизирует настройки между устройствами; при обновлении она может перенести прежний журнал вызовов из Chrome Sync в локальное хранилище и удалить прежнюю синхронизированную копию.</p>

<h2>Click2Call и доступ к веб-страницам</h2>
<p>Click2Call по умолчанию отключён. Функция активируется только после того, как пользователь установит флажок Enable Click2Call в настройках и подтвердит необязательное разрешение Chrome на доступ к HTTP- и HTTPS-страницам. Если доступ не предоставлен или впоследствии отозван, Click2Call отключается. Пользователь может в любое время отключить функцию в настройках и отозвать разрешение средствами Chrome.</p>
<p>Когда Click2Call включён, расширение устанавливает content script только в верхний фрейм разрешённых страниц. Скрипт не сканирует содержимое страницы, формы, пароли, поля ввода или cookies и не собирает историю посещений. При непосредственном нажатии пользователя по ссылке он проверяет только её адрес. Значения обычных ссылок не сохраняются и не передаются, а браузер открывает их обычным способом. Если адрес начинается с <code>tel:</code>, скрипт читает его значение, удаляет разделители и параметры и передаёт нормализованный телефонный номер во внутренний service worker расширения.</p>
<p>При включении функции Chrome предоставляет расширению сведения об открытых HTTP- и HTTPS-вкладках, чтобы content script начал работать и на уже открытых страницах; код использует идентификатор вкладки для внедрения скрипта. При нажатии ссылки Chrome также предоставляет service worker адрес страницы-источника для проверки того, что сообщение пришло из верхнего фрейма HTTP- или HTTPS-страницы. Адрес и заголовок страницы не сохраняются, не добавляются в журнал и не передаются {$CCMAINTITLE}, SIP-оператору, API проверки тарифа, CRM или рекламным сервисам.</p>
<p>Полученный из ссылки номер открывает боковую панель телефона и подставляется в поле номера, но звонок автоматически не начинается. Для запуска закрытой панели номер может временно храниться в <code>chrome.storage.session</code> не более 30 секунд, а после отображения может быть сохранён локально как последний набранный номер. Если настроена проверка тарифа, номер может быть передан выбранному пользователем сервису проверки тарифа, как описано ниже. Разрешение <code>scripting</code> используется только для регистрации и внедрения этого Click2Call-скрипта, <code>tabs</code> — для поиска уже открытых разрешённых вкладок и работы собственных страниц расширения, а <code>sidePanel</code> — для открытия панели телефона.</p>

<h2>Куда расширение передаёт данные</h2>
<ul>
    <li><strong>SIP-оператору или АТС, выбранным пользователем:</strong> данные авторизации, отображаемое имя, телефонные номера и SIP-сигнализация передаются для регистрации, установления и управления звонками. Доступность и обработка этих данных зависят от выбранного оператора или администратора АТС.</li>
    <li><strong>Узлам WebRTC:</strong> во время звонка аудио и технические сетевые сведения передаются медиасерверу или другому медиаконечному узлу, определённому SIP-оператором или АТС. Расширение не записывает и не сохраняет содержимое разговора.</li>
    <li><strong>Публичному STUN-серверу Google:</strong> для установления WebRTC-соединения передаются сетевые метаданные, необходимые для определения доступного IP-адреса и порта. SIP-пароль и содержимое разговора этому серверу не передаются.</li>
    <li><strong>Сервису проверки баланса или тарифа, указанному пользователем:</strong> при использовании этой функции передаются имя SIP-пользователя и пароль, а при проверке тарифа — также набранный номер. Адрес сервиса задаётся пользователем.</li>
    <li><strong>CRM или локальному приложению, указанным пользователем:</strong> после ответа на входящий звонок расширение может отправить настроенный HTTP-запрос, пользовательские заголовки и номер вызывающего абонента. Передача выполняется только после включения и настройки этой функции пользователем либо в соответствии с параметрами, полученными от его SIP-сервера.</li>
    <li><strong>{$CCMAINTITLE}:</strong> после удаления расширения Chrome открывает страницу обратной связи {$CCMAINTITLE}. Обычный запрос к веб-серверу может содержать IP-адрес, время и User-Agent, а браузер может приложить ранее созданные сайтом cookies. Расширение не добавляет к этому запросу SIP-реквизиты, телефонные номера, журнал вызовов или настройки. Отзыв, адрес электронной почты и согласие на ответ или новости передаются только если пользователь самостоятельно заполняет и отправляет форму.</li>
</ul>
<p>Расширение не продаёт пользовательские данные, не использует их для персонализированной рекламы и не передаёт рекламным сетям. Данные передаются только для выполнения выбранных пользователем функций, обеспечения безопасности, исполнения требований закона или с отдельного согласия пользователя.</p>

<h2>Cookies и локальное хранение</h2>
<p><strong>Расширение WebRTC SIP Phone не создаёт cookies, не читает cookies браузера, не использует Chrome Cookies API и не запрашивает разрешение <code>cookies</code>.</strong> Данные в <code>chrome.storage.local</code>, <code>chrome.storage.session</code> и локальном хранилище страниц расширения не являются cookies.</p>
<p>Сайт {$CCMAINTITLE} может использовать строго необходимые cookies для сеанса, авторизации, выбора языка, безопасности, ограничения злоупотреблений и сохранения состояния форм. Если на сайте показывается реклама Google AdSense или используются другие внешние компоненты, соответствующий поставщик может применять cookies или аналогичные идентификаторы в соответствии со своей политикой и настройками согласия пользователя. Данные расширения не передаются рекламным сервисам.</p>

<h2>Цели и правовые основания обработки</h2>
<p>Мы обрабатываем данные для предоставления запрошенных услуг связи и функций расширения, управления аккаунтом и расчётами, ответа на обращения, предотвращения мошенничества и злоупотреблений, обеспечения безопасности, улучшения надёжности сервиса и выполнения юридических обязанностей. В зависимости от ситуации правовым основанием является исполнение договора или запроса пользователя, согласие, законный интерес в обеспечении безопасности и работоспособности сервиса либо обязанность, установленная законом.</p>

<h2>Получатели и сторонние сервисы</h2>
<p>Данные могут получать поставщики хостинга и технической инфраструктуры, платёжные операторы, операторы связи и маршрутизации звонков, выбранные пользователем SIP-операторы и АТС, выбранные пользователем API- и CRM-сервисы, Google как оператор используемого STUN-сервера и, только для сайта, поставщики отображаемой на сайте рекламы. Каждый независимый поставщик обрабатывает полученные им данные по собственной политике. Информация также может быть раскрыта, если это требуется законом, судебным решением или уполномоченным государственным органом.</p>

<h2>Срок хранения и удаление</h2>
<p>Локальные данные расширения хранятся до их удаления пользователем, очистки данных расширения или удаления расширения. Настройки SIP-аккаунта можно удалить на странице настроек, а журнал вызовов — в панели расширения. Срок хранения данных сайта и услуг зависит от срока действия аккаунта и договора, необходимости оказания услуг, разрешения споров, предотвращения злоупотреблений и обязательных требований бухгалтерского, налогового и телекоммуникационного законодательства. Отзывы и контактные данные хранятся до достижения цели обращения или отзыва согласия, если более длительное хранение не требуется законом.</p>

<h2>Права пользователя</h2>
<p>В пределах, предусмотренных применимым законодательством, пользователь может запросить доступ к своим данным, их исправление, удаление, ограничение обработки или перенос, возразить против обработки, а также отозвать ранее данное согласие. Отзыв согласия не влияет на законность обработки, выполненной до его отзыва. Запрос можно направить по адресу <a href="mailto:{$MAILTO}">{$MAILTO}</a> или через <a href="{$CUSTOMER_INTERFACE_URL}support">службу поддержки</a>. Пользователь также вправе обратиться в компетентный орган по защите данных.</p>
<p>Данные, находящиеся только у выбранного пользователем SIP-оператора, АТС, платёжного оператора, API- или CRM-сервиса, должны запрашиваться или удаляться непосредственно у соответствующего поставщика.</p>

<h2>Безопасность и международная передача</h2>
<p>Мы применяем разумные технические и организационные меры для защиты данных. Для удалённых SIP-, API- и CRM-сервисов рекомендуется использовать только защищённые адреса WSS и HTTPS. Незащищённые WS или HTTP могут использоваться для совместимости с выбранными пользователем локальными или устаревшими системами; передаваемые таким способом данные не защищены шифрованием транспортного уровня. Пользователь отвечает за выбор и безопасность самостоятельно настроенных серверов.</p>
<p>Интернет-сервисы, операторы связи и инфраструктура WebRTC могут находиться в других странах. В таких случаях данные передаются в объёме, необходимом для оказания выбранной услуги, с применением предусмотренных законом гарантий, когда они требуются.</p>

<h2>Дети</h2>
<p>Сайт, услуги связи и расширение не предназначены для самостоятельного использования детьми. Мы сознательно не собираем персональные данные детей без согласия родителя или законного представителя. Если вы считаете, что ребёнок предоставил нам такие данные, свяжитесь с нами для их проверки и удаления.</p>

<h2>Chrome Web Store Limited Use</h2>
<p>Использование информации, полученной через API Google Chrome, соответствует Политике пользовательских данных Chrome Web Store, включая требования Limited Use. Доступ к веб-страницам, адрес страницы-источника и номер из выбранной пользователем ссылки <code>tel:</code> используются только для работы заявленной функции Click2Call и связанного показа тарифа. Остальная информация используется только для предоставления и улучшения заявленных пользовательских функций WebRTC SIP Phone. Мы не используем и не передаём её для персонализированной рекламы, оценки кредитоспособности, перепродажи данных или иных целей, не связанных с функциями расширения. Доступ человека к пользовательским данным допускается только с явного согласия пользователя для поддержки, для обеспечения безопасности, для исполнения требований закона либо к агрегированным и обезличенным данным для внутренних операций.</p>

<h2>Изменения политики</h2>
<p>Мы можем обновлять эту политику при изменении функций, способов обработки данных или требований законодательства. Актуальная версия и дата обновления публикуются на этой странице. Если практики обработки данных расширением существенно изменятся, соответствующая информация будет раскрыта пользователю до начала новой обработки в порядке, требуемом Chrome Web Store и применимым законодательством.</p>

<h2>Контакты</h2>
<p>По вопросам конфиденциальности, доступа к данным или их удаления обращайтесь по адресу <a href="mailto:{$MAILTO}">{$MAILTO}</a> или через <a href="{$CUSTOMER_INTERFACE_URL}support">{$CUSTOMER_INTERFACE_URL}support</a>.</p>
<br><p>Настоящая политика конфиденциальности последний раз обновлялась: <strong>4 сентября 2026 года.</strong></p>
{php}}else{{/php}
<h1>Privacy Policy for {$CCMAINTITLE} and WebRTC SIP Phone</h1>
<p>This policy describes data processing on the {$CCMAINTITLE} website, in connection with its communication services, and in the WebRTC SIP Phone with Click2Dial Chrome extension ("WebRTC SIP Phone"). {$CCMAINTITLE} operates the website and develops the extension. For privacy questions, contact <a href="mailto:{$MAILTO}">{$MAILTO}</a> or use the <a href="{$CUSTOMER_INTERFACE_URL}support">support service</a>.</p>

<h2>Data processed by the {$CCMAINTITLE} website and service</h2>
<p>Depending on the features you use and the information you provide, we may process:</p>
<ul>
    <li>registration and contact information, such as your name, address, email address, and telephone number;</li>
    <li>account and authentication information, service settings, and information required to maintain your account;</li>
    <li>payment and billing information, account balance, transaction records, and information required by accounting and tax laws;</li>
    <li>telephone numbers, call time, direction, duration and cost, and other information required to provide communication services;</li>
    <li>support requests, feedback, an email address supplied for a response, and your choices about receiving responses or product news;</li>
    <li>technical information, including IP address, request date and time, User-Agent, requested page, request result, and approximate location derived from the IP address. We use this information to operate and diagnose the service, protect it from abuse, and enforce request limits.</li>
</ul>
<p>If a payment is made through an external payment processor, that processor handles payment data under its own privacy policy. We do not receive or store the full payment card number when it is entered directly on the processor's service.</p>

<h2>Data processed by the WebRTC SIP Phone extension</h2>
<p>The extension processes only the data needed to configure a SIP account and provide its disclosed features:</p>
<ul>
    <li>the SIP/WebSocket server address, protocol, and port;</li>
    <li>the user name or extension number, password, display name, and voicemail identifier;</li>
    <li>call transfer, balance and rate checker, and optional CRM integration settings;</li>
    <li>the Click2Call setting and the number in a <code>tel:</code> link directly selected by the user;</li>
    <li>dialled and received telephone numbers, the last dialled number, call history, registration state, and call state;</li>
    <li>microphone access and call audio while a call is active.</li>
</ul>
<p>SIP account settings, including authentication information, the last dialled number, and call history are stored locally in extension storage in the Chrome profile. Temporary interface state and missed-call state may be stored in Chrome session storage. The current version does not synchronize settings between devices. During an update, it may migrate legacy call history from Chrome Sync to local storage and remove the old synchronized copy.</p>

<h2>Click2Call and access to websites</h2>
<p>Click2Call is disabled by default. It is activated only when the user selects Enable Click2Call in the settings and confirms Chrome's optional permission to access HTTP and HTTPS pages. If access is denied or later revoked, Click2Call is disabled. The user can disable the feature in the settings and revoke the permission through Chrome at any time.</p>
<p>When Click2Call is enabled, the extension installs a content script only in the top frame of permitted pages. The script does not scan page contents, forms, passwords, input fields, or cookies and does not collect browsing history. When the user directly selects a link, the script checks only its address. Ordinary link values are not stored or transmitted, and the browser follows them normally. If the address starts with <code>tel:</code>, the script reads its value, removes separators and parameters, and passes the normalized telephone number to the extension's internal service worker.</p>
<p>When the feature is enabled, Chrome provides information about open HTTP and HTTPS tabs so that the content script can also begin working on pages that are already open; the code uses the tab identifier to inject the script. When a link is selected, Chrome also provides the source page address to the service worker to verify that the message came from the top frame of an HTTP or HTTPS page. The page address and title are not stored, added to call history, or transmitted to {$CCMAINTITLE}, the SIP provider, the rate checker API, a CRM service, or advertising services.</p>
<p>The number obtained from the link opens the phone Side Panel and populates the number field, but does not start a call automatically. To support a closed panel, the number may be stored temporarily in <code>chrome.storage.session</code> for no more than 30 seconds and, after it is displayed, may be stored locally as the last dialled number. If rate checking is configured, the number may be transmitted to the rate checker selected by the user as described below. The <code>scripting</code> permission is used only to register and inject this Click2Call script, <code>tabs</code> is used to locate already-open permitted tabs and operate the extension's own pages, and <code>sidePanel</code> is used to open the phone panel.</p>

<h2>Where the extension sends data</h2>
<ul>
    <li><strong>The SIP provider or PBX selected by the user:</strong> authentication information, display name, telephone numbers, and SIP signalling are transmitted to register, establish, and manage calls. Availability and processing depend on the selected provider or PBX administrator.</li>
    <li><strong>WebRTC endpoints:</strong> during a call, audio and technical network information are transmitted to the media server or other media endpoint selected by the SIP provider or PBX. The extension does not record or store call audio.</li>
    <li><strong>Google's public STUN server:</strong> network metadata required to determine an available IP address and port is transmitted to establish WebRTC connectivity. The extension does not send the SIP password or call content to this server.</li>
    <li><strong>The balance or rate checker selected by the user:</strong> when this feature is used, the SIP user name and password are transmitted, and a rate request also includes the dialled number. The user selects the service address.</li>
    <li><strong>The CRM or local application selected by the user:</strong> after an incoming call is answered, the extension may send a configured HTTP request, custom headers, and the caller's telephone number. This occurs only after the user enables and configures the feature or according to parameters supplied by the user's SIP server.</li>
    <li><strong>{$CCMAINTITLE}:</strong> after the extension is uninstalled, Chrome opens the {$CCMAINTITLE} feedback page. An ordinary web request may include the IP address, time, and User-Agent, and the browser may attach cookies previously created by the website. The extension does not add SIP credentials, telephone numbers, call history, or settings to this request. Feedback, an email address, and choices about responses or news are transmitted only when the user completes and submits the form.</li>
</ul>
<p>The extension does not sell user data, use it for personalized advertising, or transfer it to advertising networks. Data is transferred only to provide features selected by the user, maintain security, comply with law, or with the user's separate consent.</p>

<h2>Cookies and local storage</h2>
<p><strong>The WebRTC SIP Phone extension does not create cookies, read browser cookies, use the Chrome Cookies API, or request the <code>cookies</code> permission.</strong> Data in <code>chrome.storage.local</code>, <code>chrome.storage.session</code>, and extension-page local storage is not a cookie.</p>
<p>The {$CCMAINTITLE} website may use strictly necessary cookies for sessions, authentication, language selection, security, abuse prevention, and form state. If the website displays Google AdSense advertising or uses other external components, the relevant provider may use cookies or similar identifiers under its own policy and the user's consent settings. Extension data is not provided to advertising services.</p>

<h2>Purposes and legal bases</h2>
<p>We process data to provide requested communication services and extension features, administer accounts and billing, respond to requests, prevent fraud and abuse, maintain security, improve service reliability, and comply with legal obligations. Depending on the circumstances, the legal basis is performance of a contract or user request, consent, a legitimate interest in service security and operation, or a legal obligation.</p>

<h2>Recipients and third-party services</h2>
<p>Recipients may include hosting and infrastructure providers, payment processors, telecommunications and call-routing providers, SIP providers and PBXs selected by the user, API and CRM services selected by the user, Google as the operator of the STUN server used by the extension, and, for the website only, providers of advertising displayed on the website. Each independent provider processes the data it receives under its own policy. We may also disclose information when required by law, court order, or an authorized public body.</p>

<h2>Retention and deletion</h2>
<p>Local extension data is retained until the user deletes it, clears extension data, or uninstalls the extension. SIP account settings can be deleted from the settings page, and call history can be cleared from the extension panel. Website and service data is retained according to the account and contract term and as required to provide services, resolve disputes, prevent abuse, and meet accounting, tax, and telecommunications obligations. Feedback and contact details are retained until the request has been resolved or consent is withdrawn, unless longer retention is required by law.</p>

<h2>User rights</h2>
<p>Subject to applicable law, a user may request access to, correction or deletion of personal data, restriction of processing, data portability, or object to processing, and may withdraw consent. Withdrawal does not affect processing that was lawful before withdrawal. Send a request to <a href="mailto:{$MAILTO}">{$MAILTO}</a> or through the <a href="{$CUSTOMER_INTERFACE_URL}support">support service</a>. A user may also lodge a complaint with the competent data protection authority.</p>
<p>Data held only by a SIP provider, PBX administrator, payment processor, API service, or CRM service selected by the user must be requested or deleted directly through that provider.</p>

<h2>Security and international transfers</h2>
<p>We apply reasonable technical and organizational measures to protect data. Remote SIP, API, and CRM services should use secure WSS and HTTPS addresses. Unencrypted WS or HTTP may be used for compatibility with local or legacy systems selected by the user; data transmitted in this way is not protected by transport-layer encryption. The user is responsible for the selection and security of independently configured servers.</p>
<p>Internet services, telecommunications providers, and WebRTC infrastructure may be located in other countries. In such cases, data is transferred only as needed to provide the selected service and with safeguards required by applicable law where those safeguards apply.</p>

<h2>Children</h2>
<p>The website, communication services, and extension are not intended for independent use by children. We do not knowingly collect children's personal data without the consent of a parent or legal guardian. If you believe that a child has provided such data, contact us so that we can review and delete it.</p>

<h2>Chrome Web Store Limited Use</h2>
<p>The use of information received from Google Chrome APIs adheres to the Chrome Web Store User Data Policy, including the Limited Use requirements. Website access, the source page address, and the number in the <code>tel:</code> link selected by the user are used only to provide the disclosed Click2Call feature and related rate display. Other information is used only to provide and improve the disclosed user-facing features of WebRTC SIP Phone. We do not use or transfer it for personalized advertising, credit-worthiness, data resale, or purposes unrelated to the extension's features. Human access to user data is permitted only with the user's explicit consent for support, when necessary for security or compliance with law, or for aggregated and anonymized data used for internal operations.</p>

<h2>Changes to this policy</h2>
<p>We may update this policy when features, data practices, or legal requirements change. The current version and its update date are published on this page. If the extension's data practices change materially, the change will be disclosed to users before the new processing begins as required by Chrome Web Store policies and applicable law.</p>

<h2>Contact</h2>
<p>For privacy, data access, or deletion requests, contact <a href="mailto:{$MAILTO}">{$MAILTO}</a> or use <a href="{$CUSTOMER_INTERFACE_URL}support">{$CUSTOMER_INTERFACE_URL}support</a>.</p>
<br><p>This privacy policy was last updated: <strong>September 4, 2026.</strong></p>
{php}}{/php}
<br><br>
</main>
