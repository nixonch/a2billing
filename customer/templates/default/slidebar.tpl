<!DOCTYPE html>
{a2b_frame_options}
<html lang="en">
<head>
    <link rel="shortcut icon" href="{$FAVICONPATH}"/>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{$SECONDARY_TITLE} | {$CCMAINTITLE}</title>
    <link rel="stylesheet" href="templates/default/css/style-nav.css">
    <link rel="stylesheet" href="templates/default/css/left-nav-style.css">
{if (!empty($A2Bconfig.webcustomerui.google_adsense_client_id) && (!empty($A2Bconfig.webcustomerui.google_adsense_sidebar_slot) || !empty($A2Bconfig.webcustomerui.google_adsense_article_slot)))}
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$A2Bconfig.webcustomerui.google_adsense_client_id|escape:'url'}" crossorigin="anonymous"></script>
{/if}
{if (!empty($A2Bconfig.webcustomerui.google_cse_id))}
    <script async src="https://cse.google.com/cse.js?cx={$A2Bconfig.webcustomerui.google_cse_id|escape:'url'}"></script>
{/if}
</head>
<body class="bodygradient">
<input type="checkbox" id="navi-toggle" hidden>
<nav class="navi">
  <label for="navi-toggle" class="navi-toggle" onclick=""></label>
  <ul>
    <li><h4>Main</h4></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}/">Sign-in/up</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}terms">Terms & Conditions</a></li>
{if (is_file("{$smarty.current_dir}/policy.tpl"))}
    <li><a href="{$CUSTOMER_INTERFACE_URL}policy">Privacy Policy</a></li>
{/if}
{if (is_file("{$smarty.current_dir}/review_webrtc.tpl"))}
    <li><a href="{$CUSTOMER_INTERFACE_URL}review_webrtc">Write me</a></li>
{/if}
    <li><h4>Knowledge base</h4></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}AsteriskWebRTC">WebRTC setup in Asterisk</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}APICheckers">Own Checking Web Service</a></li>
    <li><a href="{$CUSTOMER_INTERFACE_URL}how-to-CRM">Integration with CRM</a></li>
  </ul>
</nav>
<div class="container" onClick="if ($('#navi-toggle').prop('checked') && event.target.tagName!='A') document.getElementById('navi-toggle').checked=false">
  <div class="footer_icons">
    <div style="text-align:center">
{a2b_payment_logos sidebar=true}

    </div>
  </div>
  <aside class="ads_right">
{if (!empty($A2Bconfig.webcustomerui.google_cse_id))}
    <div class="gcse-search"></div>
{/if}
{if (!empty($A2Bconfig.webcustomerui.google_adsense_client_id) && !empty($A2Bconfig.webcustomerui.google_adsense_sidebar_slot))}
    <ins class="adsbygoogle" style="width:100%"  data-ad-client="{$A2Bconfig.webcustomerui.google_adsense_client_id|escape:'html'}" data-ad-slot="{$A2Bconfig.webcustomerui.google_adsense_sidebar_slot|escape:'html'}" data-ad-format="auto" data-full-width-responsive="false"></ins>
    <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
{/if}
  </aside>
