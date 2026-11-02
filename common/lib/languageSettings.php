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


function SetLocalLanguage()
{
	$slectedLanguage = "";
	$languageEncoding = "";
	$charEncoding = "";
	switch (LANGUAGE)
	{
		case "arabic":
			$languageEncoding = "en_US.UTF-8";
			$slectedLanguage = "en_US";
			$charEncoding = "UTF-8";
			break;
		case "brazilian":
			$languageEncoding = "pt_BR.UTF-8";
			$slectedLanguage = "pt_BR";
			$charEncoding = "UTF-8";
			break;
		case "chinese":
			$languageEncoding = "zh_TW.UTF-8";
			$slectedLanguage = "zh_TW";
			$charEncoding = "UTF-8";
			break;
		case "english":
			$languageEncoding = "en_US.UTF-8";
			$slectedLanguage = "en_US";
			$charEncoding = "UTF-8";
			break;
		case "spanish":
			$languageEncoding = "es_ES.UTF-8";
			$slectedLanguage = "es_ES";
			$charEncoding = "UTF-8";
			break;
		case "french":
			$languageEncoding = "fr_FR.UTF-8";
			$slectedLanguage = "fr_FR";
			$charEncoding = "UTF-8";
			break;
		case "german":
			$languageEncoding = "de_DE.UTF-8";
			$slectedLanguage = "de_DE";
			$charEncoding = "UTF-8";
			break;
		case "finnish":
			$languageEncoding = "fi_FI.UTF-8";
			$slectedLanguage = "fi_FI";
			$charEncoding = "UTF-8";
			break;
		case "italian":
			$languageEncoding = "it_IT.UTF-8";
			$slectedLanguage = "it_IT";
			$charEncoding = "UTF-8";
			break;
		case "polish":
			$languageEncoding = "pt_PT.UTF-8";
			$slectedLanguage = "pl_PL";
			$charEncoding = "UTF-8";
			break;
		case "romanian":
			$languageEncoding = "ro_RO.UTF-8";
			$slectedLanguage = "ro_RO";
			$charEncoding = "UTF-8";
			break;
		case "russian":
			$languageEncoding = "ru_RU.UTF-8";
			$slectedLanguage = "ru_RU";
			$charEncoding = "UTF-8";
			break;
		case "turkish":
			// issues with Turkish 
			// http://forum.elxis.org/index.php?action=printpage%3Btopic=3090.0
			// http://bugs.php.net/bug.php?id=39993
			$languageEncoding = "en_GB.UTF-8";
			$slectedLanguage = "en_US.UTF-8";
			$charEncoding = "UTF-8";
			break;
		case "urdu":
			$languageEncoding = "ur.UTF-8";
			$slectedLanguage = "ur_PK";
			$charEncoding = "UTF-8";
			break;
		case "ukrainian": // provided by Oleh Miniv  email: oleg-min@ukr.net
			$languageEncoding = "uk_UA.UTF-8";
			$slectedLanguage = "uk_UA";
			$charEncoding = "UTF-8";
			break;
		case "farsi":
			$languageEncoding = "fa_IR.UTF-8";
			$slectedLanguage = "fa_IR";
			$charEncoding = "UTF-8";
			break;
		case "greek":
			$languageEncoding = "el_GR.UTF-8";
			$slectedLanguage = "el_GR";
			$charEncoding = "UTF-8";
			break;
		case "indonesian":
			$languageEncoding = "id_ID.UTF-8";
			$slectedLanguage = "id_ID";
			$charEncoding = "UTF-8";
			break;
		default:
			$languageEncoding = "en_US.UTF-8";
			$slectedLanguage = "en_US";
			$charEncoding = "UTF-8";
			break;
	}
	
	//echo "languageEncoding=$languageEncoding - slectedLanguage=$slectedLanguage - path=".BINDTEXTDOMAIN;
	@setlocale(LC_TIME,$languageEncoding);
	putenv("LANG=$slectedLanguage");
	putenv("LANGUAGE=$slectedLanguage");
	setlocale(LC_ALL, $slectedLanguage);
	setlocale(LC_MESSAGES,  $languageEncoding);
	setlocale(LC_NUMERIC, "C");
	$domain = 'messages';
	bindtextdomain("messages", BINDTEXTDOMAIN);
	textdomain($domain);
	bind_textdomain_codeset($domain, $charEncoding);
	define('CHARSET', $charEncoding);
	define('LANG', substr($slectedLanguage,0,2));
	mb_http_output($charEncoding);
	mb_internal_encoding($charEncoding);
}
