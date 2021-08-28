{include file="header.tpl"}
{if ($popupwindow == 0)}
{if ($EXPORT == 0)}

<div id="left-sidebar">
<div style="position: relative; right: 10px; text-align: center;">
<a href="userinfo"><img src="{$LOGOPATH}" width="124" height="64" alt="To Home Page"></a>
</div>
<div id="leftmenu-top">
<div id="leftmenu-down">
<div id="leftmenu-middle">
<ul id="nav">
<!--
	<div class="toggle_menu"><li><a href="userinfo"><strong>{php} echo gettext("ACCOUNT INFO");{/php}</strong></a></li></div>
-->
	{if $ACXDISTRIBUTION >0 }
	<div class="toggle_menu"><li>
	<a href="javascript:;" class="toggle_menu" target="_self"> <div> <div id="menutitlebutton"> <img id="img5"
	{if ($section == "14")}
	src="templates/{$SKIN_NAME}/images/minus.gif"
	{else}
	src="templates/{$SKIN_NAME}/images/plus.gif"
	{/if} onmouseover="this.style.cursor='hand';" ></div> <div id="menutitlesection"><strong>{php} echo gettext("MY CUSTOMERS");{/php}</strong></div></div></a></li></div>
		<div class="tohide"
	{if ($section =="14")}
		style="">
	{else}
	style="display:none;">
	{/if}
	<ul>
		<li><ul>
			<li><a href="A2B_entity_moneysituation?section=14"><strong>{php} echo gettext("LIST CUSTOMERS");{/php}</strong></a></li>
			<li><a href="A2B_entity_rewards?section=14"><strong>{php} echo gettext("COMMISSION ACCRUED");{/php}</strong></a></li>
		</ul></li>
	</ul>
	</div>
	{/if}

	{if $ACXVOICEMAIL1>0 }
	<div class="toggle_menu"><li><a href="A2B_entity_voicemail"><strong>{php} echo gettext("VOICEMAIL");{/php}</strong></a></li></div>
	{/if}

	{if $ACXCALL_HISTORY >0 }
	<div class="toggle_menu"><li><a href="call-history"><strong>{php} echo gettext("CALL HISTORY");{/php}</strong></a></li></div>

	<div class="toggle_menu"><li><a href="fax-history"><strong>{php} echo gettext("FAX HISTORY");{/php}</strong></a></li></div>
	{/if}

	{if $ACXSURVEILLANCE >0 }
	<div class="toggle_menu"><li><a href="A2B_entity_surveillance"><strong>{php} echo gettext("SURVEILLANCE");{/php}</strong></a></li></div>
	<div class="toggle_menu"><li><a href="A2B_entity_ringup"><strong>{php} echo gettext("RING-UP");{/php}</strong></a></li></div>
	{/if}

	{if (true) or $ACXAUTODIALER >0 or $ACXSURVEILLANCE >0 or $ACXDISTRIBUTION >0}
	<div class="toggle_menu"><li><a href="CC_ring_up"><strong>{php} echo gettext("CAMPAIGNs");{/php}</strong></a></li></div>
	{/if}

	{if $ACXSPEED_DIAL >0 }
	<div class="toggle_menu"><li><a href="A2B_entity_speeddial?atmenu=speeddial&stitle=Speed+Dial"><strong>{php} echo gettext("SPEED DIAL");{/php}</strong></a></li></div>
	{/if}

	{if $ACXCALL_BACK >0 }
	<div class="toggle_menu"><li><a href="callback"><strong>{php} echo gettext("CALLBACK");{/php}</strong></a></li></div>
	{/if}

	{if (false) && $ACXAUTODIALER >0}
	<div class="toggle_menu"><li>
	<a href="javascript:;" class="toggle_menu" target="_self"> <div> <div id="menutitlebutton"> <img id="img10"
	{if ($section == "10")}
	src="templates/{$SKIN_NAME}/images/minus.gif"
	{else}
	src="templates/{$SKIN_NAME}/images/plus.gif"
	{/if} onmouseover="this.style.cursor='hand';" ></div> <div id="menutitlesection"><strong>{php} echo gettext("AUTO DIALER");{/php}</strong></div></div></a></li></div>
		<div class="tohide"
	{if ($section =="10")}
		style="">
	{else}
	style="display:none;">
	{/if}
	<ul>
		<li><ul>
				<li><a href="A2B_entity_campaign?section=10">{php} echo gettext("Campaign's");{/php}</a></li>
				<li><a href="A2B_entity_phonebook?section=10">{php} echo gettext("Phone Book");{/php}</a></li>
				<li><a href="A2B_entity_phonenumber?section=10">{php} echo gettext("Phone Number");{/php}</a></li>
				<li><a href="A2B_phonelist_import?section=10">{php} echo gettext("Import Phone List");{/php}</a></li>
		</ul></li>
	</ul>
	</div>
	{/if}

	{if $ACXDID >0 or $ACXSIP_IAX >0 }
	<div class="toggle_menu"><li>
	<a href="javascript:;" class="toggle_menu" target="_self"> <div> <div id="menutitlebutton"> <img id="img5"
	{if ($section == "15")}
	src="templates/{$SKIN_NAME}/images/minus.gif"
	{else}
	src="templates/{$SKIN_NAME}/images/plus.gif"
	{/if} onmouseover="this.style.cursor='hand';" ></div> <div id="menutitlesection"><strong>{php} echo gettext("MY PBX");{/php}</strong></div></div></a></li></div>
		<div class="tohide"
	{if ($section =="15")}
		style="">
	{else}
	style="display:none;">
	{/if}
	<ul>
		<li><ul>
			{if $ACXSIP_IAX >0 }
				<li><a href="A2B_entity_sipiax_info?section=15"><strong>{php} echo gettext("EXTENSIONS ");{/php}</strong></a></li>
<!--				<li><a href="A2B_entity_sipiax_info?section=15"><strong>{php} echo gettext("Outbound call plan");{/php}</strong></a></li>
-->			{/if}
			{if $ACXCALLER_ID >0 }
			<div class="toggle_menu"><li><a href="A2B_entity_callerid?section=15&atmenu=callerid&stitle=CallerID"><strong>{php} echo gettext("MY CALLER IDs");{/php}</strong></a></li></div>
			{/if}
			{if $ACXDID >0 }
<!--				<li><a href="A2B_entity_did_info?section=15"><strong>{php} echo gettext("EXTERNAL LINES");{/php}</strong></a></li>
-->				<li><a href="A2B_entity_did?section=15&form_action=list"><strong>{php} echo gettext("INCOMING CALLS");{/php}</strong></a></li>
			{/if}
			<li><a href="A2B_entity_ivr?section=15"><strong>{php} echo gettext("IVR");{/php}</strong></a></li>
<!--			<li><a href="A2B_entity_queues?section=15"><strong>{php} echo gettext("QUEUES");{/php}</strong></a></li>
-->			<li><a href="A2B_entity_fax?section=15&form_action=list"><strong>{php} echo gettext("VIRTUAL FAX");{/php}</strong></a></li>
			<li><a href="A2B_entity_greeting?section=15&form_action=list"><strong>{php} echo gettext("VOICE GREETINGS");{/php}</strong></a></li>
		</ul></li>
	</ul>
	</div>
	{/if}

	{if $ACXINVOICES >0 or $ACXPAYMENT_HISTORY >0 or $ACXNOTIFICATION >0 or $ACXVOUCHER >0 or $ACXRATECARD >0 or $ACXSIMULATOR >0}
	<div class="toggle_menu"><li>
	<a href="javascript:;" class="toggle_menu" target="_self"> <div> <div id="menutitlebutton"> <img id="img5"
	{if ($section == "5")}
	src="templates/{$SKIN_NAME}/images/minus.gif"
	{else}
	src="templates/{$SKIN_NAME}/images/plus.gif"
	{/if} onmouseover="this.style.cursor='hand';" ></div> <div id="menutitlesection"><strong>{php} echo gettext("MONEY MATTERS");{/php}</strong></div></div></a></li></div>
		<div class="tohide"
	{if ($section =="5")}
		style="">
	{else}
	style="display:none;">
	{/if}
	<ul>
		<li><ul>
			{if $ACXNOTIFICATION >0}
				<li><a href="A2B_notification?section=5&form_action=ask-edit"><strong>{php} echo gettext("NOTIFICATION");{/php}</strong></a></li>
			{/if}
			{if $ACXPAYMENT_HISTORY >0}
				<li><a href="A2B_entity_logrefill?section=5"><strong>{php} echo gettext("ACCOUNT ACTIVITY");{/php}</strong></a></li>
				<li><a href="payment-history?section=5"><strong>{php} echo gettext("PAYMENT HISTORY");{/php}</strong></a></li>
			{/if}
			{if $ACXINVOICES1 >0}
				<li><a href="A2B_entity_receipt?section=5"><strong>{php} echo gettext("View Receipts");{/php}</strong></a></li>
				<li><a href="A2B_entity_invoice?section=5"><strong>{php} echo gettext("View Invoices");{/php}</strong></a></li>
				<li><a href="A2B_billing_preview?section=5"><strong>{php} echo gettext("Preview Next Billing");{/php}</strong></a></li>
			{/if}
	{if $ACXVOUCHER >0}
				<li><a href="A2B_entity_voucher?section=5&form_action=list"><strong>{php} echo gettext("VOUCHERS");{/php}</strong></a></li>
	{/if}
	{if $ACXRATECARD >0}
				<li><a href="A2B_entity_ratecard?section=5&form_action=list"><strong>{php} echo gettext("RATECARD");{/php}</strong></a></li>
	{/if}
	{if $ACXSIMULATOR >0}
				<li><a href="simulator?section=5"><strong>{php} echo gettext("SIMULATOR");{/php}</strong></a></li>
	{/if}
		</ul></li>
	</ul>
	</div>
	{/if}

	<div class="toggle_menu"><li><a href="A2B_entity_log_viewer"><strong>{php} echo gettext("ACTIVITY");{/php}</strong></a></li></div>

	{if $ACXPASSWORD>0 }
	<div class="toggle_menu"><li><a href="A2B_entity_password?atmenu=password&form_action=ask-edit&stitle=Password"><strong>{php} echo gettext("PASSWORD");{/php}</strong></a></li></div>
	{/if}

	{if $ACXSUPPORT >0 }
	<div class="toggle_menu"><li><a href="support"><strong>{php} echo gettext("SUPPORT");{/php}</strong></a></li></div>
	{/if}
<br/>
	<li>
	{if $ACX_PERSONALINFO >0 }
	<a href="A2B_entity_card?atmenu=password&form_action=ask-edit&stitle=Personal+Information" style="height:16px;padding: 0px 0px 2px 18px"><img style="vertical-align:middle;padding-bottom:2px" src="templates/{$SKIN_NAME}/images/cog.png"> <font color="Teal">{php}echo gettext("MY PROFILE"){/php}</font></a>
	</li><li>
	{/if}
	<a href="logout?logout=true" target="_top" style="height:16px;padding: 2px 0px 0px 18px"><img style="vertical-align:middle;padding-bottom:2px" src="templates/{$SKIN_NAME}/images/logout.png"> <strong><font color="#DD0000">{php}echo gettext("LOGOUT"){/php}</font></strong></a>
	</li>
</ul>

</div>
</div>
</div>

<table width="90%" cellspacing="15">
<tr>
   <td>
		<a href="{$PAGE_SELF}?ui_language=english"><img src="templates/{$SKIN_NAME}/images/flags/gb.gif" border="0" title="English" alt="English"></a>
<!--
		<a href="{$PAGE_SELF}?ui_language=ukrainian"><img src="templates/{$SKIN_NAME}/images/flags/ua.gif" border="0" title="Ukrainian" alt="Ukrainian"></a>
-->
		<a href="{$PAGE_SELF}?ui_language=russian"><img src="templates/{$SKIN_NAME}/images/flags/ru.gif" border="0" title="russian" alt="russian"></a>
		<a href="{$PAGE_SELF}?ui_language=spanish"><img src="templates/{$SKIN_NAME}/images/flags/es.gif" border="0" title="Spanish" alt="Spanish"></a>
		<a href="{$PAGE_SELF}?ui_language=french"><img src="templates/{$SKIN_NAME}/images/flags/fr.gif" border="0" title="French" alt="French"></a>
		<a href="{$PAGE_SELF}?ui_language=german"><img src="templates/{$SKIN_NAME}/images/flags/de.gif" border="0" title="German" alt="German"></a>
		<a href="{$PAGE_SELF}?ui_language=finnish"><img src="templates/{$SKIN_NAME}/images/flags/fi.gif" border="0" title="Finnish" alt="Finnish"></a>
		<a href="{$PAGE_SELF}?ui_language=portuguese"><img src="templates/{$SKIN_NAME}/images/flags/pt.gif" border="0" title="Portuguese" alt="Portuguese"></a>
		<a href="{$PAGE_SELF}?ui_language=brazilian"><img src="templates/{$SKIN_NAME}/images/flags/br.gif" border="0" title="Brazilian" alt="Brazilian"></a>
		<a href="{$PAGE_SELF}?ui_language=italian"><img src="templates/{$SKIN_NAME}/images/flags/it.gif" border="0" title="Italian" alt="Italian"></a>
		<a href="{$PAGE_SELF}?ui_language=romanian"><img src="templates/{$SKIN_NAME}/images/flags/ro.gif" border="0" title="Romanian" alt="Romanian"></a>
		<a href="{$PAGE_SELF}?ui_language=chinese"><img src="templates/{$SKIN_NAME}/images/flags/cn.gif" border="0" title="Chinese" alt="Chinese"></a>
		<a href="{$PAGE_SELF}?ui_language=polish"><img src="templates/{$SKIN_NAME}/images/flags/pl.gif" border="0" title="Polish" alt="Polish"></a>
		<a href="{$PAGE_SELF}?ui_language=turkish"><img src="templates/{$SKIN_NAME}/images/flags/tr.gif" border="0" title="Turkish" alt="Turkish"></a>
		<a href="{$PAGE_SELF}?ui_language=urdu"><img src="templates/{$SKIN_NAME}/images/flags/pk.gif" border="0" title="Urdu" alt="Urdu"></a>
		<a href="{$PAGE_SELF}?ui_language=farsi"><img src="templates/{$SKIN_NAME}/images/flags/ir.gif" border="0" title="Farsi" alt="Farsi"></a>
		<a href="{$PAGE_SELF}?ui_language=greek"><img src="templates/{$SKIN_NAME}/images/flags/gr.gif" border="0" title="Greek" alt="Greek"></a>
		<a href="{$PAGE_SELF}?ui_language=indonesian"><img src="templates/{$SKIN_NAME}/images/flags/id.gif" border="0" title="Indonesian" alt="Indonesian"></a>
   </td>
</tr>
</table>

</div>

<div id="main-content">
	<div id="inside">

{else}
<div>
{/if}
{else}
<div>
{/if}


{$MAIN_MSG}
