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
 * @contributor Steve Dommett <steve@st4vs.net>
 *              Belaid Rachid <rachid.belaid@gmail.com>
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

define('A2B_CONFIG_DIR', '/etc/');
define('AST_CONFIG_DIR', '/etc/asterisk/');
define('DEFAULT_A2BILLING_CONFIG', A2B_CONFIG_DIR . 'a2billing.conf');

define('SCRIPT_CONFIG_DIR', '/var/lib/a2billing/script/');

// DEFINE VERBOSITY & LOGGING LEVEL : 0 = FATAL; 1 = ERROR; WARN = 2 ; INFO = 3 ; DEBUG = 4
define ('FATAL',			0);
define ('ERROR',			1);
define ('WARN',				2);
define ('INFO',				3);
define ('DEBUG',			4);

include_once (dirname(__FILE__)."/vendor/autoload.php");

// Imports the Google Cloud client library
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Speech\V1p1beta1\SpeechClient;
use Google\Cloud\Speech\V1p1beta1\RecognitionAudio;
use Google\Cloud\Speech\V1p1beta1\RecognitionConfig;
use Google\Cloud\Speech\V1p1beta1\RecognitionConfig\AudioEncoding;

class A2Billing {

	/**
    * Config variables
    *
    * @var array
    * @access public
    */
	public $config;

	/**
    * Config AGI variables
	* Create for coding readability facilities
    *
    * @public array
    * @access public
    */
	public $agiconfig;

	/**
    * IDConfig variables
    *
    * @var interger
    * @access public
    */
	public $idconfig=1;

	/**
    * hangupdetected variables
    *
    * @var interger
    * @access public
    */
	public $hangupdetected = false;


	/**
    * cardnumber & CallerID variables
    *
    * @var string
    * @access public
    */
	public $cardnumber;
	public $CallerID;
	public $CallerIDName = '';
	public $CallerIDext = '';
	public $CID_handover = '';
	public $cid_verify = true;
	public $id_did = NULL;
	public $speech2mail = '';
	public $send_text = '';
	public $send_sound = '';
	public $calleesound = NULL;
	public $callback_id = 0;


	/**
    * Buffer variables
    *
    * @var string
    * @access public
    */
	public $BUFFER;


	/**
    * DBHandle variables
    *
    * @var object
    * @access public
    */
	public $DBHandle;


	/**
    * instance_table variables
    *
    * @var object
    * @access public
    */
	public $instance_table;

	/**
    * store the file name to store the logs
    *
    * @var string
    * @access public
    */
	public $log_file = '';


	/**
    * request AGI variables
    *
    * @var string
    * @access public
    */

	public $channel;
	public $uniqueid;
	public $accountcode;
	public $dnid;
	public $cid2num = false;
	public $extension;
	public $outoflength = false;

	// from apply_rules, if a prefix is removed we keep it to track exactly what the user introduce
	
	public $myprefix;
	public $ipaddress;
	public $rate;
	public $destination;
	public $dtmf_destination = false;
	public $sip_iax_buddy;
	public $credit;
	public $tariff;
	public $active;
	public $status;
	public $hostname='';
	public $currency='usd';
	public $margintotal=1;
	public $margin=0;
	public $groupe_mode = false;
	public $groupe_id = '';
	public $mode = '';
	public $timeout;
	public $newdestination;
	public $tech;
	public $prefix;
	public $username;

	public $typepaid = 0;
	public $removeinterprefix = 1;
	public $restriction = 1;
	public $redial;
	public $nbused = 0;
	
	public $enableexpire;
	public $expirationdate;
	public $expiredays;
	public $firstusedate;
	public $creationdate;
	public $voicebox = NULL;
	
	public $creditlimit = 0;

	public $backaftertransfer = false;
	public $languageselected;
	public $current_language;
	public $streamfirst = true;
	public $first_dtmf = '';
	public $dtmfs = '';

	public $cardholder_lastname;
	public $cardholder_firstname;
	public $cardholder_email;
	public $cardholder_uipass;
	public $id_campaign;
	public $id_card;
	public $caller_concat_id = 0;
//	public $card_caller = 0;
//	public $card_called = 0;
	public $useralias;
	public $countryprefix;
	public $areaprefix;
	public $citylength;

	public $extext = true;
	public $auth_through_accountcode = false;
	
	// Start time of the Script
	public $G_startime = 0;
	
	// Enable voicemail for this card. For DID and SIP/IAX call
	public $voicemail = 0;

	// Flag to know that we ask for an othercardnumber when for instance we doesnt have enough credit to make a call
	public $ask_other_cardnumber=0;
	public $update_callerid=0;

	public $ivr_voucher;
	public $vouchernumber = 0;
	public $add_credit;
	public $didsellrate = 0;
	public $didbuyrate = 0;
	public $billblock = 1;
	public $prefixallow = true;

	public $cardnumber_range;

	// Define if we have changed the status of the card
	public $set_inuse_username = 0;

	public $callback_beep_to_enter_destination = False;

	/**
	* CC_TESTING variables
	* for developer purpose, will replace some get_data inputs in order to test the application from shell
	*
	* @var interger
	* @access public
	*/
	public $CC_TESTING;
	
	// List of dialstatus
	public $dialstatus_rev_list;
	

	/* CONSTRUCTOR */
	public function __construct()
	{
		// $this -> agiconfig['debug'] = true;
		// $this -> DBHandle = $DBHandle;
		
		$this -> dialstatus_rev_list = Constants::getDialStatus_Revert_List();

		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGHUP, array(&$this,"Hangupsignal"));
		}
	}


	/* Init */
	public function Reinit()
	{
		$this -> myprefix='';
		$this -> ipaddress='';
		$this -> rate='';
		$this -> destination='';
	}


	/* Hangupsignal */
	public function Hangupsignal()
	{
		$this -> hangupdetected = true;
		$this -> debug( INFO, null, __FILE__, __LINE__, "HANGUP DETECTED!\n");
	}


	/*
	 * Debug
	 *
	 * usage : $A2B -> debug( INFO, $agi, __FILE__, __LINE__, $buffer_debug);
	 */
	public function debug( $level, $agi, $file, $line, $buffer_debug)
	{
		$file = basename($file);
		
		// VERBOSE
		if ($this->agiconfig['verbosity_level'] >= $level && $agi) {
			if ($line == '') $agi -> verbose($buffer_debug);
			else $agi -> verbose('file:'.$file.' - line:'.$line.' - uniqueid:'.$this->uniqueid.' - '.$buffer_debug);
		}
		
		// LOG INTO FILE
		if ($this->agiconfig['logging_level'] >= $level) {
			$this -> write_log ($buffer_debug, 1, "[file:$file - line:$line - uniqueid:".$this->uniqueid."]:");
		}
	}
	
	/*
	 * Write log into file
	 */
	public function write_log($output, $tobuffer = 1, $line_file_info = '')
	{
		//$tobuffer = 0;

		if (strlen($this->log_file) > 1) {
			if (is_array($output))
				$output = 'Array';
			$string_log = "[".date("d/m/Y H:i:s")."]:".$line_file_info."[CallerID:".$this->CallerID."]:[CN:".$this->cardnumber."]:[".$output."]\n";
			if ($this->CC_TESTING) echo $string_log;

			$this -> BUFFER .= $string_log;
			if (!$tobuffer || $this->CC_TESTING) {
				error_log ($this -> BUFFER, 3, $this->log_file);
				$this-> BUFFER = '';
			}
		}
	}

	/*
	 * set the DB handler
	 */
	public function set_dbhandler ($DBHandle)
	{
		$this->DBHandle	= $DBHandle;
	}

	/*
	 * set_instance_table
	 */
	public function set_instance_table ($instance_table)
	{
		$this->instance_table = $instance_table;
	}

	/*
	 * load_conf
	 */
	public function load_conf( &$agi, $config=NULL, $webui=0, $idconfig=1, $optconfig=array())
    {
		$this -> idconfig = $idconfig;
		// load config
		if (!is_null($config) && file_exists($config)) {
			$this->config = parse_ini_file($config, true);
		} elseif (file_exists(DEFAULT_A2BILLING_CONFIG)) {
			$this->config = parse_ini_file(DEFAULT_A2BILLING_CONFIG, true);
		} else {
			echo "Error : A2Billing configuration file is missing!";
			exit;
		}

	  	/*  We don't need to do this twice.  load_conf_db() will do it
		// If optconfig is specified, stuff vals and vars into 'a2billing' config array.
		foreach($optconfig as $var=>$val) {
			$this->config["agi-conf$idconfig"][$var] = $val;
		}*/

		// conf for the database connection
		if (!isset($this->config['database']['hostname']))	$this->config['database']['hostname'] = 'localhost';
		if (!isset($this->config['database']['port']))		$this->config['database']['port'] = '5432';
		if (!isset($this->config['database']['user']))		$this->config['database']['user'] = 'postgres';
		if (!isset($this->config['database']['password']))	$this->config['database']['password'] = '';
		if (!isset($this->config['database']['dbname']))		$this->config['database']['dbname'] = 'a2billing';
		if (!isset($this->config['database']['dbtype']))		$this->config['database']['dbtype'] = 'postgres';

		return $this->load_conf_db($agi, NULL, 0, $idconfig, $optconfig);
    }

	/*
	 * Load config from Database
	 */
	public function load_conf_db( &$agi, $config=NULL, $webui=0, $idconfig=1, $optconfig=array())
    {
		$this -> idconfig = $idconfig;
		// load config
		$config_table = new Table("cc_config", "config_key as cfgkey, config_value as cfgvalue, config_group_title as cfggname, config_valuetype as cfgtype");
		$this->DbConnect();

		$config_res = $config_table -> Get_list($this->DBHandle, "");
		if (!$config_res) {
			echo 'Error : cannot load conf : load_conf_db';
			return false;
		}

		foreach ($config_res as $conf)
		{
			// FOR DEBUG
			/*if ($conf['cfgkey'] == 'sip_iax_pstn_direct_call_prefix') {
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "\n\n conf :".$conf['cfgkey']);
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "\n\n conf :".$conf['cfgvalue']);
			}*/
			if ($conf['cfgtype'] == 0) // if its type is text
			{
				$this->config[$conf['cfggname']][$conf['cfgkey']] = $conf['cfgvalue'];
			}
			elseif ($conf['cfgtype'] == 1) // if its type is boolean
			{
				if (strtoupper($conf['cfgvalue']) == "YES" || $conf['cfgvalue'] == 1 || strtoupper($conf['cfgvalue']) == "TRUE") // if equal to 'yes'
				{
					$this->config[$conf['cfggname']][$conf['cfgkey']] = 1;
				}
				else // if equal to 'no'
				{
					$this->config[$conf['cfggname']][$conf['cfgkey']] = 0;
				}
			}
		}
		$this->DbDisconnect($this->DBHandle);

		// If optconfig is specified, stuff vals and vars into 'a2billing' config array.
		foreach($optconfig as $var=>$val)
		{
			$this->config["agi-conf$idconfig"][$var] = $val;
		}

		// add default values to config for uninitialized values
		//Card Number Length Code
		$card_length_range = isset($this->config['global']['interval_len_cardnumber'])?$this->config['global']['interval_len_cardnumber']:NULL;
		$this -> cardnumber_range = $this -> splitable_data ($card_length_range);

		if (is_array($this -> cardnumber_range) && ($this -> cardnumber_range[0] >= 4))
		{
			define ("CARDNUMBER_LENGTH_MIN", $this -> cardnumber_range[0]);
			define ("CARDNUMBER_LENGTH_MAX", $this -> cardnumber_range[count($this -> cardnumber_range)-1]);
			define ("LEN_CARDNUMBER", CARDNUMBER_LENGTH_MIN);
		}
		else
		{
			echo gettext("Invalid card number lenght defined in configuration.");
			exit;
		}
		if (!isset($this->config['global']['len_aliasnumber']))		$this->config['global']['len_aliasnumber'] = 15;
		if (!isset($this->config['global']['len_voucher']))			$this->config['global']['len_voucher'] = 15;
		if (!isset($this->config['global']['base_currency'])) 		$this->config['global']['base_currency'] = 'usd';
		if (!isset($this->config['global']['didbilling_daytopay'])) 	$this->config['global']['didbilling_daytopay'] = 5;
		if (!isset($this->config['global']['admin_email'])) 			$this->config['global']['admin_email'] = 'root@localhost';
		define ("DATE_TIMEZONE", isset($this->config['global']['date_timezone'])?$this->config['global']['date_timezone']:0);

				// Conf for the Callback
		if (!isset($this->config['callback']['context_callback']))	$this->config['callback']['context_callback'] = 'a2billing-callback';
		if (!isset($this->config['callback']['context_surveillance']))	$this->config['callback']['context_surveillance'] = 'surveillance';
		if (!isset($this->config['callback']['ani_callback_delay']))	$this->config['callback']['ani_callback_delay'] = '10';
		if (!isset($this->config['callback']['extension']))		$this->config['callback']['extension'] = '1000';
		if (!isset($this->config['callback']['sec_avoid_repeate']))	$this->config['callback']['sec_avoid_repeate'] = '30';
		if (!isset($this->config['callback']['timeout']))		$this->config['callback']['timeout'] = '50';
		if (!isset($this->config['callback']['answer_call']))		$this->config['callback']['answer_call'] = '1';
		if (!isset($this->config['callback']['nb_predictive_call']))	$this->config['callback']['nb_predictive_call'] = '10';
		if (!isset($this->config['callback']['nb_day_wait_before_retry']))	$this->config['callback']['nb_day_wait_before_retry'] = '1';
		if (!isset($this->config['callback']['context_preditctivedialer']))	$this->config['callback']['context_preditctivedialer'] = 'a2billing-predictivedialer';
		if (!isset($this->config['callback']['predictivedialer_maxtime_tocall']))	$this->config['callback']['predictivedialer_maxtime_tocall'] = '5400';
		if (!isset($this->config['callback']['sec_wait_before_callback']))	$this->config['callback']['sec_wait_before_callback'] = '10';

		// Conf for the signup
		if (!isset($this->config['signup']['enable_signup']))$this->config['signup']['enable_signup'] = '1';
		if (!isset($this->config['signup']['credit']))		$this->config['signup']['credit'] = '0';
		if (!isset($this->config['signup']['tariff']))		$this->config['signup']['tariff'] = '8';
		if (!isset($this->config['signup']['activated']))	$this->config['signup']['activated'] = 't';
		if (!isset($this->config['signup']['simultaccess']))	$this->config['signup']['simultaccess'] = '0';
		if (!isset($this->config['signup']['typepaid']))		$this->config['signup']['typepaid'] = '0';
		if (!isset($this->config['signup']['creditlimit']))	$this->config['signup']['creditlimit'] = '0';
		if (!isset($this->config['signup']['runservice']))	$this->config['signup']['runservice'] = '0';
		if (!isset($this->config['signup']['enableexpire']))	$this->config['signup']['enableexpire'] = '0';
		if (!isset($this->config['signup']['expiredays']))	$this->config['signup']['expiredays'] = '0';

		// Conf for Paypal
		if (!isset($this->config['paypal']['item_name']))	$this->config['paypal']['item_name'] = 'Credit Purchase';
		if (!isset($this->config['paypal']['currency_code']))	$this->config['paypal']['currency_code'] = 'USD';
		if (!isset($this->config['paypal']['purchase_amount']))	$this->config['paypal']['purchase_amount'] = '5;10;15';
		if (!isset($this->config['paypal']['paypal_fees']))   $this->config['paypal']['paypal_fees'] = '1';

		// Conf for Backup
		if (!isset($this->config['backup']['backup_path']))	$this->config['backup']['backup_path'] ='/tmp';
		if (!isset($this->config['backup']['gzip_exe']))		$this->config['backup']['gzip_exe'] ='/bin/gzip';
		if (!isset($this->config['backup']['gunzip_exe']))	$this->config['backup']['gunzip_exe'] ='/bin/gunzip';
		if (!isset($this->config['backup']['mysqldump']))	$this->config['backup']['mysqldump'] ='/usr/bin/mysqldump';
		if (!isset($this->config['backup']['pg_dump']))		$this->config['backup']['pg_dump'] ='/usr/bin/pg_dump';
		if (!isset($this->config['backup']['mysql']))		$this->config['backup']['mysql'] ='/usr/bin/mysql';
		if (!isset($this->config['backup']['psql']))		$this->config['backup']['psql'] ='/usr/bin/psql';
		if (!isset($this->config['backup']['archive_data_x_month']))		$this->config['backup']['archive_data_x_month'] ='3';

		// Conf for Customer Web UI
		if (!isset($this->config['webcustomerui']['customerinfo']))	$this->config['webcustomerui']['customerinfo'] = '1';
		if (!isset($this->config['webcustomerui']['personalinfo']))	$this->config['webcustomerui']['personalinfo'] = '1';
		if (!isset($this->config['webcustomerui']['limit_callerid']))	$this->config['webcustomerui']['limit_callerid'] = '5';
		if (!isset($this->config['webcustomerui']['error_email']))	$this->config['webcustomerui']['error_email'] = 'root@localhost';
		// conf for the web ui
		if (!isset($this->config['webui']['buddy_sip_file']))		$this->config['webui']['buddy_sip_file'] = '/etc/asterisk/additional_a2billing_sip.conf';
		if (!isset($this->config['webui']['buddy_iax_file']))		$this->config['webui']['buddy_iax_file'] = '/etc/asterisk/additional_a2billing_iax.conf';
		if (!isset($this->config['webui']['api_logfile']))		$this->config['webui']['api_logfile'] = '/tmp/api_ecommerce_request.log';
		if (isset($this->config['webui']['api_ip_auth']))		$this->config['webui']['api_ip_auth'] = explode(";", $this->config['webui']['api_ip_auth']);

		if (!isset($this->config['webui']['dir_store_mohmp3']))		$this->config['webui']['dir_store_mohmp3'] = '/var/lib/asterisk/mohmp3';
		if (!isset($this->config['webui']['num_musiconhold_class']))	$this->config['webui']['num_musiconhold_class'] = 10;
		if (!isset($this->config['webui']['show_help']))			$this->config['webui']['show_help'] = 1;
		if (!isset($this->config['webui']['my_max_file_size_import']))	$this->config['webui']['my_max_file_size_import'] = 1024000;
		if (!isset($this->config['webui']['dir_store_audio']))		$this->config['webui']['dir_store_audio'] = '/var/lib/asterisk/sounds/a2billing';
		if (!isset($this->config['webui']['my_max_file_size_audio']))	$this->config['webui']['my_max_file_size_audio'] = 3072000;

		if (isset($this->config['webui']['file_ext_allow']))		$this->config['webui']['file_ext_allow'] = explode(",", $this->config['webui']['file_ext_allow']);
		else $this->config['webui']['file_ext_allow'] = explode(",", "gsm, mp3, wav");

		if (isset($this->config['webui']['file_ext_allow_musiconhold']))	$this->config['webui']['file_ext_allow_musiconhold'] = explode(",", $this->config['webui']['file_ext_allow_musiconhold']);
		else $this->config['webui']['file_ext_allow_musiconhold'] = explode(",", "mp3");

		if (!isset($this->config['webui']['show_top_frame'])) 		$this->config['webui']['show_top_frame'] = 1;
		if (!isset($this->config['webui']['currency_choose'])) 		$this->config['webui']['currency_choose'] = 'all';
		if (!isset($this->config['webui']['card_export_field_list']))	$this->config['webui']['card_export_field_list'] = 'creationdate, username, credit, lastname, firstname';
		if (!isset($this->config['webui']['rate_export_field_list']))    $this->config['webui']['rate_export_field_list'] = 'dest_name, dialprefix, rateinitial';
		if (!isset($this->config['webui']['voucher_export_field_list']))	$this->config['webui']['voucher_export_field_list'] = 'id, voucher, credit, tag, activated, usedcardnumber, usedate, currency';
		if (!isset($this->config['webui']['advanced_mode']))				$this->config['webui']['advanced_mode'] = 0;
		if (!isset($this->config['webui']['delete_fk_card']))			$this->config['webui']['delete_fk_card'] = 1;
		if (!defined('MONITOR_PATH')) define ("MONITOR_PATH",	isset($this->config['webui']['monitor_path'])			?$this->config['webui']['monitor_path']:"/tmp");
		if (!defined('FAX_PATH')) define ("FAX_PATH",	isset($this->config['webui']['fax_path'])			?$this->config['webui']['fax_path']:"/tmp");

		// conf for the recurring process
		if (!isset($this->config["recprocess"]['batch_log_file'])) 	$this->config["recprocess"]['batch_log_file'] = '/tmp/batch-a2billing.log';

		// conf for the peer_friend
		if (!isset($this->config['peer_friend']['type'])) 		$this->config['peer_friend']['type'] = 'friend';
		if (!isset($this->config['peer_friend']['allow'])) 		$this->config['peer_friend']['allow'] = 'ulaw,alaw,gsm,g729';
		if (!isset($this->config['peer_friend']['context'])) 	$this->config['peer_friend']['context'] = 'a2billing';
		if (!isset($this->config['peer_friend']['nat'])) 		$this->config['peer_friend']['nat'] = 'yes';
		if (!isset($this->config['peer_friend']['amaflags'])) 	$this->config['peer_friend']['amaflags'] = 'billing';
		if (!isset($this->config['peer_friend']['qualify'])) 	$this->config['peer_friend']['qualify'] = 'yes';
		if (!isset($this->config['peer_friend']['host'])) 		$this->config['peer_friend']['host'] = 'dynamic';
		if (!isset($this->config['peer_friend']['dtmfmode'])) 	$this->config['peer_friend']['dtmfmode'] = 'RFC2833';
		if (!isset($this->config['peer_friend']['use_realtime'])) 	$this->config['peer_friend']['use_realtime'] = '0';


		//conf for the notifications
		if (!isset($this->config['notifications']['values_notifications'])) $this->config['notifications']['values_notifications'] = '0';
		if (!isset($this->config['notifications']['cron_notifications'])) $this->config['notifications']['cron_notifications'] = '1';
		if (!isset($this->config['notifications']['delay_notifications'])) $this->config['notifications']['delay_notifications'] = '1';

		// conf for the log-files
		if (isset($this->config['log-files']['agi']) && strlen ($this->config['log-files']['agi']) > 1)
		{
			$this -> log_file = $this -> config['log-files']['agi'];
		}
		define ("LOGFILE_CRONT_ALARM", 			isset($this->config['log-files']['cront_alarm'])			?$this->config['log-files']['cront_alarm']:null);
		define ("LOGFILE_CRONT_AUTOREFILL", 	isset($this->config['log-files']['cront_autorefill'])		?$this->config['log-files']['cront_autorefill']:null);
		define ("LOGFILE_CRONT_BATCH_PROCESS", 	isset($this->config['log-files']['cront_batch_process'])	?$this->config['log-files']['cront_batch_process']:null);
		define ("LOGFILE_CRONT_ARCHIVE_DATA", 	isset($this->config['log-files']['cront_archive_data'])	?$this->config['log-files']['cront_archive_data']:null);
		define ("LOGFILE_CRONT_BILL_DIDUSE", 	isset($this->config['log-files']['cront_bill_diduse'])		?$this->config['log-files']['cront_bill_diduse']:null);
		define ("LOGFILE_CRONT_SUBSCRIPTIONFEE",isset($this->config['log-files']['cront_subscriptionfee'])	?$this->config['log-files']['cront_subscriptionfee']:null);
		define ("LOGFILE_CRONT_CURRENCY_UPDATE",isset($this->config['log-files']['cront_currency_update'])	?$this->config['log-files']['cront_currency_update']:null);
		define ("LOGFILE_CRONT_INVOICE",		isset($this->config['log-files']['cront_invoice'])			?$this->config['log-files']['cront_invoice']:null);
		define ("LOGFILE_CRONT_CHECKACCOUNT",	isset($this->config['log-files']['cront_check_account'])	?$this->config['log-files']['cront_check_account']:null);
		define ("LOGFILE_API_ECOMMERCE", 		isset($this->config['log-files']['api_ecommerce'])			?$this->config['log-files']['api_ecommerce']:null);
		define ("LOGFILE_API_CALLBACK", 		isset($this->config['log-files']['api_callback'])			?$this->config['log-files']['api_callback']:null);
		define ("LOGFILE_PAYPAL", 				isset($this->config['log-files']['paypal'])					?$this->config['log-files']['paypal']:null);
		define ("LOGFILE_EPAYMENT", 			isset($this->config['log-files']['epayment'])				?$this->config['log-files']['epayment']:null);

		// conf for the AGI
		if (!isset($this->config["agi-conf$idconfig"]['play_audio'])) 	$this->config["agi-conf$idconfig"]['play_audio'] = 1;
		define ("PLAY_AUDIO", 											$this->config["agi-conf$idconfig"]['play_audio']);

		if (!isset($this->config["agi-conf$idconfig"]['verbosity_level'])) 	$this->config["agi-conf$idconfig"]['verbosity_level'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['logging_level'])) 	$this->config["agi-conf$idconfig"]['logging_level'] = 3;
		
		if (!isset($this->config["agi-conf$idconfig"]['logger_enable'])) $this->config["agi-conf$idconfig"]['logger_enable'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['log_file'])) $this->config["agi-conf$idconfig"]['log_file'] = '/var/log/a2billing/a2billing.log';

		if (!isset($this->config["agi-conf$idconfig"]['answer_call'])) $this->config["agi-conf$idconfig"]['answer_call'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['auto_setcallerid'])) $this->config["agi-conf$idconfig"]['auto_setcallerid'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['say_goodbye'])) $this->config["agi-conf$idconfig"]['say_goodbye'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['play_menulanguage'])) $this->config["agi-conf$idconfig"]['play_menulanguage'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['force_language'])) $this->config["agi-conf$idconfig"]['force_language'] = 'EN';
		if (!isset($this->config["agi-conf$idconfig"]['min_credit_2call'])) $this->config["agi-conf$idconfig"]['min_credit_2call'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['min_duration_2bill'])) $this->config["agi-conf$idconfig"]['min_duration_2bill'] = 0;

		if (!isset($this->config["agi-conf$idconfig"]['use_dnid'])) $this->config["agi-conf$idconfig"]['use_dnid'] = 0;
		// Explode the no_auth_dnid string
		if (isset($this->config["agi-conf$idconfig"]['no_auth_dnid'])) $this->config["agi-conf$idconfig"]['no_auth_dnid'] = explode(",",$this->config["agi-conf$idconfig"]['no_auth_dnid']);

		// Explode the international_prefixes, extracharge_did and extracharge_fee strings
		if (isset($this->config["agi-conf$idconfig"]['extracharge_did'])) $this->config["agi-conf$idconfig"]['extracharge_did'] = explode(",",$this->config["agi-conf$idconfig"]['extracharge_did']);
		if (isset($this->config["agi-conf$idconfig"]['extracharge_fee'])) $this->config["agi-conf$idconfig"]['extracharge_fee'] = explode(",",$this->config["agi-conf$idconfig"]['extracharge_fee']);
		if (isset($this->config["agi-conf$idconfig"]['extracharge_buyfee'])) $this->config["agi-conf$idconfig"]['extracharge_buyfee'] = explode(",",$this->config["agi-conf$idconfig"]['extracharge_buyfee']);
		
		if (isset($this->config["agi-conf$idconfig"]['international_prefixes'])) {
			$this->config["agi-conf$idconfig"]['international_prefixes'] = explode(",",$this->config["agi-conf$idconfig"]['international_prefixes']);
		} else {
			// to retain config file compatibility assume a default unless config option is set
			$this->config["agi-conf$idconfig"]['international_prefixes'] = explode(",","+,011,09,00,1");
		}
		
		if (!isset($this->config["agi-conf$idconfig"]['number_try'])) $this->config["agi-conf$idconfig"]['number_try'] = 3;
		if (!isset($this->config["agi-conf$idconfig"]['say_balance_after_auth'])) $this->config["agi-conf$idconfig"]['say_balance_after_auth'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['say_balance_after_call'])) $this->config["agi-conf$idconfig"]['say_balance_after_call'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['say_rateinitial'])) $this->config["agi-conf$idconfig"]['say_rateinitial'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['say_timetocall'])) $this->config["agi-conf$idconfig"]['say_timetocall'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['cid_enable'])) $this->config["agi-conf$idconfig"]['cid_enable'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['cid_sanitize'])) $this->config["agi-conf$idconfig"]['cid_sanitize'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['cid_askpincode_ifnot_callerid'])) $this->config["agi-conf$idconfig"]['cid_askpincode_ifnot_callerid'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['cid_auto_assign_card_to_cid'])) $this->config["agi-conf$idconfig"]['cid_auto_assign_card_to_cid'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['notenoughcredit_cardnumber'])) $this->config["agi-conf$idconfig"]['notenoughcredit_cardnumber'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['notenoughcredit_assign_newcardnumber_cid'])) $this->config["agi-conf$idconfig"]['notenoughcredit_assign_newcardnumber_cid'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['maxtime_tocall_negatif_free_route'])) $this->config["agi-conf$idconfig"]['maxtime_tocall_negatif_free_route'] = 1800;
		if (!isset($this->config["agi-conf$idconfig"]['callerid_authentication_over_cardnumber'])) $this->config["agi-conf$idconfig"]['callerid_authentication_over_cardnumber'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['cid_auto_create_card_len'])) $this->config["agi-conf$idconfig"]['cid_auto_create_card_len'] = 10;
		if (!isset($this->config["agi-conf$idconfig"]['cid_auto_create_card'])) $this->config["agi-conf$idconfig"]['cid_auto_create_card'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['sip_iax_friends'])) $this->config["agi-conf$idconfig"]['sip_iax_friends'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['sip_iax_pstn_direct_call'])) $this->config["agi-conf$idconfig"]['sip_iax_pstn_direct_call'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['dialcommand_param'])) $this->config["agi-conf$idconfig"]['dialcommand_param'] = '|30|HL(%timeout%:61000:30000)';
		if (!isset($this->config["agi-conf$idconfig"]['dialcommand_param_sipiax_friend'])) $this->config["agi-conf$idconfig"]['dialcommand_param_sipiax_friend'] = '|30|HL(3600000:61000:30000)';
		if (!isset($this->config["agi-conf$idconfig"]['dialcommand_param_call_2did '])) $this->config["agi-conf$idconfig"]['dialcommand_param_call_2did '] = '|30|HL(3600000:61000:30000)';
		if (!isset($this->config["agi-conf$idconfig"]['switchdialcommand'])) $this->config["agi-conf$idconfig"]['switchdialcommand'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['failover_recursive_limit'])) $this->config["agi-conf$idconfig"]['failover_recursive_limit'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['record_call'])) $this->config["agi-conf$idconfig"]['record_call'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['monitor_formatfile'])) $this->config["agi-conf$idconfig"]['monitor_formatfile'] = 'gsm';
		
		if (!isset($this->config["agi-conf$idconfig"]['currency_association']))	$this->config["agi-conf$idconfig"]['currency_association'] = 'all:credit';
		$this->config["agi-conf$idconfig"]['currency_association'] = explode(",",$this->config["agi-conf$idconfig"]['currency_association']);
		foreach($this->config["agi-conf$idconfig"]['currency_association'] as $cur_val) {
			$cur_val = explode(":",$cur_val);
			$this->config["agi-conf$idconfig"]['currency_association_internal'][$cur_val[0]]=$cur_val[1];
		}
		
		if (isset($this->config["agi-conf$idconfig"]['currency_cents_association']) && strlen($this->config["agi-conf$idconfig"]['currency_cents_association']) > 0) {
			$this->config["agi-conf$idconfig"]['currency_cents_association'] = explode(",",$this->config["agi-conf$idconfig"]['currency_cents_association']);
			foreach($this->config["agi-conf$idconfig"]['currency_cents_association'] as $cur_val) {
				$cur_val = explode(":",$cur_val);
				$this->config["agi-conf$idconfig"]['currency_cents_association_internal'][$cur_val[0]]=$cur_val[1];
			}
		}
		if (!isset($this->config["agi-conf$idconfig"]['file_conf_enter_destination']))	$this->config["agi-conf$idconfig"]['file_conf_enter_destination'] = 'prepaid-enter-number-u-calling-1-or-011';
		if (!isset($this->config["agi-conf$idconfig"]['file_conf_enter_menulang']))	$this->config["agi-conf$idconfig"]['file_conf_enter_menulang'] = 'prepaid-menulang';
		if (!isset($this->config["agi-conf$idconfig"]['send_reminder'])) $this->config["agi-conf$idconfig"]['send_reminder'] = 0;
		if (isset($this->config["agi-conf$idconfig"]['debugshell']) && $this->config["agi-conf$idconfig"]['debugshell'] == 1 && isset($agi)) $agi->nlinetoread = 0;

		if (!isset($this->config["agi-conf$idconfig"]['ivr_voucher'])) $this->config["agi-conf$idconfig"]['ivr_voucher'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['ivr_voucher_prefix'])) $this->config["agi-conf$idconfig"]['ivr_voucher_prefix'] = 8;
		if (!isset($this->config["agi-conf$idconfig"]['jump_voucher_if_min_credit'])) $this->config["agi-conf$idconfig"]['jump_voucher_if_min_credit'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['failover_lc_prefix'])) $this->config["agi-conf$idconfig"]['failover_lc_prefix'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['cheat_on_announcement_time'])) $this->config["agi-conf$idconfig"]['cheat_on_announcement_time'] = 0;
		if (!isset($this->config["agi-conf$idconfig"]['busy_timeout'])) $this->config["agi-conf$idconfig"]['busy_timeout'] = 1;
		if (!isset($this->config["agi-conf$idconfig"]['lcr_mode'])) $this->config["agi-conf$idconfig"]['lcr_mode'] = 0;
		
		// Define the agiconfig property
		$this->agiconfig = $this->config["agi-conf$idconfig"];

		// Print out on CLI for debug purpose
		if (!$webui) $this -> debug( DEBUG, $agi, __FILE__, __LINE__, 'A2Billing AGI internal configuration:');
		if (!$webui) $this -> debug( DEBUG, $agi, __FILE__, __LINE__, print_r($this->agiconfig, true));
		
		if ( DATE_TIMEZONE ) date_default_timezone_set( DATE_TIMEZONE );
//		$this -> streamfirst = true;
		return true;
    }


	/**
    * Log to console if debug mode.
    *
    * @example examples/ping.php Ping an IP address
    *
    * @param string $str
    * @param integer $vbl verbose level
    */
    public function conlog($str, $vbl=1)
    {
		global $agi;
		static $busy = false;

		if ($this->agiconfig['debug'] != false)
		{
			if (!$busy) // no conlogs inside conlog!!!
			{
			  $busy = true;
			  if (isset($agi)) $agi->verbose($str, $vbl);
			  $busy = false;
			}
		}
    }

	/*
	 * Function to create a menu to select the language
	 */
	public function play_menulanguage ($agi)
	{
		// MENU LANGUAGE
		if ($this->agiconfig['play_menulanguage']==1) {
			
			$list_prompt_menulang = explode(':',$this->agiconfig['conf_order_menulang']);
			$i=1;
			foreach ($list_prompt_menulang as $lg_value ) {
				$res_dtmf = $agi->get_data("menu_".$lg_value, 500, 1);
				if (!empty($res_dtmf["result"]) && is_numeric($res_dtmf["result"])&& $res_dtmf["result"]>0)break;
				
				if ($i==sizeof($list_prompt_menulang)) {$res_dtmf = $agi->get_data("num_".$lg_value."_".$i,3000, 1);}
				else {$res_dtmf = $agi->get_data("num_".$lg_value."_".$i,1000, 1);}
				
				if (!empty($res_dtmf["result"]) && is_numeric($res_dtmf["result"]) && $res_dtmf["result"]>0 )break;
				$i++;
			}
			
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES Menu Language DTMF : ".$res_dtmf ["result"]);

			$this -> languageselected = $res_dtmf ["result"];
			
			if ($this -> languageselected>0 && $this -> languageselected<=sizeof($list_prompt_menulang) ) {
				$language = $list_prompt_menulang[$this -> languageselected-1];
			} else {
				if (strlen($this->agiconfig['force_language'])==2) {
					$language = strtolower($this->agiconfig['force_language']);
				} else {
					$language = 'en';
				}
				
			}

            $this ->current_language = $language;
            
            $this -> debug( DEBUG, $agi, __FILE__, __LINE__, " CURRENT LANGUAGE : ".$language);
            
            
			if ($this->agiconfig['asterisk_version'] == "1_2") {
				$lg_var_set = 'LANGUAGE()';
			} else {
				$lg_var_set = 'CHANNEL(language)';
			}
			$agi -> set_variable($lg_var_set, $language);
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[SET $lg_var_set $language]");
			$this->languageselected = 1;

		} elseif (strlen($this->agiconfig['force_language'])==2) {

			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "FORCE LANGUAGE : ".$this->agiconfig['force_language']);
			$this->languageselected = 1;
			$language = strtolower($this->agiconfig['force_language']);
			$this ->current_language = $language;
			if ($this->agiconfig['asterisk_version'] == "1_2") {
				$lg_var_set = 'LANGUAGE()';
			} else {
				$lg_var_set = 'CHANNEL(language)';
			}
			$agi -> set_variable($lg_var_set, $language);
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[SET $lg_var_set $language]");
		}
	}



	/*
	 * function to get and conversion queuestatus to dialstatus
	 */
	public function get_dialstatus_from_queuestatus($agi)
	{
		if ($agi->get_variable('ANSWEREDTIME',true)) return "ANSWER";
		switch($agi->get_variable('QUEUESTATUS',true))
		{
		    case '':			$ret = "CANCEL"; break;
		    case 'TIMEOUT':		$ret = "NOANSWER"; break;
		    case 'FULL':		$ret = "BUSY"; break;
		    case 'JOINEMPTY':		$ret = "CHANUNAVAIL"; break;
		    case 'LEAVEEMPTY':		$ret = "CONGESTION"; break;
		    case 'JOINUNAVAIL':		$ret = "CHANUNAVAIL"; break;
		    case 'LEAVEUNAVAIL':	$ret = "CONGESTION"; break;
		    default:			$ret = "UNKNOWN"; break;
		}
		return $ret;
	}



	/*
	 * function to let audable not answer status
	 */
	public function let_stream_listening($agi, $play_anyway=false, $ringing=false)
	{
		if (array_search($agi -> channel_status('',true), array(AST_STATE_UP, AST_STATE_DOWN)) === false
		&& $this -> streamfirst && !$this -> agiconfig['answer_call'] && ($this -> agiconfig['play_audio'] || $play_anyway)) {
			if ($ringing) {
				$agi -> exec('Ringing');
			} else {
				$agi -> exec('Progress');
			}
			$this -> streamfirst = false;
			usleep(200000);
		}
	}


	/*
	 * intialize evironement variables from the agi values
	 */
	public function get_agi_request_parameter($agi)
	{
		$this -> CallerID = $this -> src	= $agi -> request['agi_callerid'];
//		if (!is_numeric($this -> src) || strlen($this -> src) > 4)
//			$this -> src			= 'NULL';
		$this -> src_peername 			= array_search($agi -> request['agi_type'], array('Dongle','Dahdi','Local','Message')) === false ? $agi -> get_variable("CHANNEL(peername)",true) : '';
		if (!is_numeric($this -> src_peername))
			$this -> src_peername		= 'NULL';
		$this -> channel			= $agi -> request['agi_channel'];
		$this -> uniqueid			= $agi -> request['agi_uniqueid'];
		$this -> accountcode			= $agi -> request['agi_accountcode'];
//		$this -> dnid				= rtrim($agi -> request['agi_dnid'], "#");
		$this -> dnid				= rtrim($agi -> request['agi_extension'], "#");
		$this -> dnid				= str_replace('%23','',$this -> dnid);

//		Call function to find the cid number
		$this -> isolate_cid();
		$this -> realdestination		= $this -> dnid;
		$this -> debug( INFO, $agi, __FILE__, __LINE__, ' get_agi_request_parameter = '.$this->CallerID.' ; '.$this->channel.' ; '.$this->uniqueid.' ; '.$this->accountcode.' ; '.$this->dnid);
	}



	/*
	 *	function to find the cid number
	 */
	public function isolate_cid()
	{
		$pos_lt = strpos($this->CallerID, '<');
		$pos_gt = strpos($this->CallerID, '>');

		if (($pos_lt !== false) && ($pos_gt !== false)) {
			$len_gt = $pos_gt - $pos_lt - 1;
			$this->CallerID = substr($this->CallerID,$pos_lt+1,$len_gt);
		}
/**		$this->CallerID = str_replace("+", '', $this->CallerID);
		if (substr($this->CallerID,0,2) == '00') {
			$this->CallerID = substr($this->CallerID,2);
		}
**/
		if (strpos($this->CallerID,'+')===0) {
			$this->CallerID = substr($this->CallerID,1);
		} elseif (substr($this->CallerID,0,2) === '00') {
			$this->CallerID = substr($this->CallerID,2);
		} elseif (substr($this->CallerID,0,3) === '011') {
			$this->CallerID = substr($this->CallerID,3);
		}
	}


	/*
	 *	function would set when the card is used or when it release
	 */
	public function callingcard_acct_start_inuse($agi, $inuse)
	{
		$upd_balance = 0;
		if (is_numeric($this->agiconfig['dial_balance_reservation'])) {
			$upd_balance = $this->agiconfig['dial_balance_reservation'];	
		}
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CARD STATUS UPDATE]");
		if ($inuse) {
			$QUERY = "UPDATE cc_card SET inuse=inuse+1, credit=credit-$upd_balance WHERE username='".$this->username."'";
			$this -> set_inuse_username = $this->username;
		} else {
			$username = $this->set_inuse_username ? $this->set_inuse_username : $this->username;
			$QUERY = "UPDATE cc_card SET inuse=inuse-1, credit=credit+$upd_balance WHERE username='".$username."'";
			$this -> set_inuse_username = 0;
		}
		if (!$this -> CC_TESTING) $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
		   $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY USING CARD UPDATE::> ".$QUERY."]");
		return 0;
	}

	/*
	 *	function enough_credit_to_call
	 */
	public function enough_credit_to_call(&$agi = NULL, &$RateEngine = NULL)
	{	
		if ($this->typepaid == 0) {
//if (!isset($RateEngine -> ratecard_obj[0][72]))		$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj is not set]");
			if (($this->credit < $this->agiconfig['min_credit_2call'] || $this->credit < 0) && !(isset($RateEngine -> ratecard_obj[0][72]) && $RateEngine -> ratecard_obj[0][72] == 'EMERGENCY')) {
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj[72]=".$RateEngine -> ratecard_obj[0][72]);
				return false;
			} else {
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj[72]=".$RateEngine -> ratecard_obj[0][72]);
				return true;
			}
		} else {
			if ($this->credit <= -$this->creditlimit && !(isset($RateEngine -> ratecard_obj[0][72]) && $RateEngine -> ratecard_obj[0][72] == 'EMERGENCY')) {
				$QUERY = "SELECT id_cc_package_offer FROM cc_tariffgroup WHERE id= ".$this->tariff ;
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
				if (!empty($result[0][0])) {
					$id_package_groupe = $result[0][0];
					if ($id_package_groupe > 0) {
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj[72]=".$RateEngine -> ratecard_obj[0][72]);
						return true;
					} else {
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj[72]=".$RateEngine -> ratecard_obj[0][72]);
						return false;
					}
				} else {
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj[72]=".$RateEngine -> ratecard_obj[0][72]);
					return false;
				}
			} else {
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[Ratecard_Obj[72]=".$RateEngine -> ratecard_obj[0][72]);
				return true;
			}
		}
	}

	public function margin_calculate($cardid = NULL, $margin = 1)
	{
		if (is_null($cardid)) $cardid = $this->id_card;
		while ($cardid > 0) {
			$QUERY	= "SELECT id_diller, margin FROM cc_card WHERE id=$cardid LIMIT 1";
			$result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY);
			$cardid = $result[0][0];
			$margin = $margin * ($result[0][1] / 100 + 1);
		}
		return $margin;
	}

	/**
	 *	Function callingcard_ivr_authorize : check the dialed/dialing number and play the time to call
	 *
	 *  @param object $agi
     *  @param float $credit
     *  @return 1 if Ok ; -1 if error
	**/
	public function callingcard_ivr_authorize($agi, &$RateEngine, $try_num = 0, $call2did = false, $callertodidcredit = NULL)
	{
		$res=0;
		$this -> caller_concat_id = 0;
		/************** 	ASK DESTINATION 	******************/

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__,  "use_dnid:".$this->agiconfig['use_dnid']." && (!in_array:".in_array ($this->dnid, $this->agiconfig['no_auth_dnid']).") && len_dnid:(".strlen($this->dnid)." || len_exten:".strlen($this->extension). " ) && (try_num:$try_num)");

		// CHECK IF USE_DNID IF NOT GET THE DESTINATION NUMBER
		if (($this->agiconfig['use_dnid'] == 1) && (!in_array ($this->dnid, $this->agiconfig['no_auth_dnid'])) && ( strlen($this->dnid)>=1 || strlen($this->extension)>=1 ) && $try_num==0) {
			if ($this->extension == 's') {
				$this->destination = $this->dnid;
			} else {
				$this->destination = $this->extension;
			}
			$this->oldphonenumber = $this->destination;
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[USE_DNID DESTINATION ::> ".$this->destination."]");
		} else {
			if ($this->first_dtmf != '') {
				$prompt_enter_dest = '""';
				if ($this->first_dtmf == '0') {
					$max_digits = 1;
				} else $max_digits = 0;
			} else {
				$prompt_enter_dest = $this->callback_beep_to_enter_destination ? 'beep' : $this->agiconfig['file_conf_enter_destination'];
				$this->first_dtmf = '';
				$max_digits = 2;
			}
			if ($max_digits) {
				$res_dtmf = $agi->get_data($prompt_enter_dest, 6000, $max_digits);
				$dtmf = $res_dtmf["result"];
				if ($dtmf == '-1')
					return -1;
				$prompt_enter_dest = '""';
				$this->first_dtmf .= $dtmf;
			} else	$dtmf = '';
			if ($this->first_dtmf != '0*' && $this->first_dtmf != '*0' && strlen($dtmf) == $max_digits) {
				$res_dtmf = $agi->get_data($prompt_enter_dest, 6000, 18);
				$dtmf = $res_dtmf["result"];
				if ($dtmf == '-1')
					return -1;
				$this->first_dtmf .= $dtmf;
			}
			$this->destination = $this->oldphonenumber = $this->first_dtmf;
//			$this->dtmf_destination = true;
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES DTMF : ".$this->destination);
		}
		$this->first_dtmf = '';

        //REDIAL FIND THE LAST DIALED NUMBER (STORED IN THE DATABASE)
        if ($this->destination == '0*' || $this->destination == '*0') {
            $this->destination = $this->oldphonenumber = $this->redial;
            $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[REDIAL : DTMF DESTINATION ::> ".$this->destination."]");
        } else {
		//REDIAL FIND THE LAST DIALED NUMBER (STORED IN THE DATABASE)
		if ( strlen($this->destination) <= 2 && is_numeric($this->destination) && $this->destination >= 0) {
			$QUERY = "SELECT phone FROM cc_speeddial WHERE id_cc_card='".$this->id_card."' AND speeddial='".$this->destination."'";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			if (is_array($result))
				$this->destination = $result[0][0];
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "SPEEDIAL REPLACE DESTINATION ::> ".$this->destination);
		}
	}
	// FOR TESTING : ENABLE THE DESTINATION NUMBER
	if ($this->CC_TESTING) $this->destination="1800300200";

	if ($try_num==0 && $call2did !== -2) {
	    if (!$this->CallerIDext) {
		$this->transferername = $this->transfererchannel = $agi->get_variable("TRANSFERERNAME", true);
		if ($this->transferername == "") {
			$this->transferername = $this->transfererchannel = $agi->get_variable("BLINDTRANSFER", true);
			$this->backaftertransfer = ($this->transferername)?true:false;
		}
		preg_match("/([^\/]+)(?=-[^-]*$)/",$this->transferername,$this->transferername);
		if (isset($this->transferername[0])) {
			$this->src_peername = $this->transferername[0];
		}
	    }
	}
	if ($try_num==0 /*&& $call2did !== -1*/) {
		$QUERY = "SELECT regexten, concat_id, id_cc_card FROM cc_sip_buddies
			LEFT JOIN cc_card_concat ON id_cc_card = concat_card_id
			WHERE name = '{$this->src_peername}' AND regexten IS NOT NULL LIMIT 1";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		if (is_array($result)) {
		    if (!is_null($result[0][1]))
			$this -> caller_concat_id = $result[0][1];
		    $this -> src = $this -> src_exten = $result[0][0];
		    $this -> card_caller = $result[0][2];
		    $this -> CID_handover	= '';
		} else {
		    $QUERY = "SELECT concat_id FROM cc_card_concat WHERE concat_card_id = {$this->id_card}";
		    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		    if (is_array($result)) {
			$this -> caller_concat_id = $result[0][0];
		    }
		}
	}
	$this->extext = true;
	if ($this->destination) {
		$QUERY = "SELECT name, regexten, mailbox, concat_id, id_cc_card, translit, voicemail_activated, removeaddprefix, addprefixinternational FROM cc_sip_buddies
			LEFT JOIN cc_card_concat ON concat_card_id = id_cc_card
			LEFT JOIN cc_card ON cc_card.id = id_cc_card
			WHERE (name = '{$this->destination}' OR (regexten = '{$this->destination}' AND (id_cc_card = {$this->id_card} OR concat_id = '$this->caller_concat_id'))) AND regexten IS NOT NULL GROUP BY regexten LIMIT 1";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		if (is_array($result)) {
			$this -> destination	= $result[0][0];
			$this -> called_exten	= $result[0][1];
			$this -> voicebox	= $result[0][2];
			$this -> voicemail	= $result[0][6];
			$this -> called_concat_id = $result[0][3];
			$this -> card_called	= $result[0][4];
			if (isset($this->src_exten)) {
			    if ($this -> card_caller == $this -> card_called || ($this -> caller_concat_id == $this -> called_concat_id && !is_null($this -> caller_concat_id))) {
				if (!isset($this->transferername[0]) && !isset($this->cidextafter)) {
					$cname = '"' . trim(preg_replace('/(?<!\d)(' . $this->src_exten . ')(?!\d)/', '', $agi->get_variable('CALLERID(name)', true)), "\x00..\x2F") . '"<' . $this->src_exten . '>';
					if ($result[0][5])	$cname = translit($cname);
					$agi -> set_callerid($cname);
					unset($this->src_exten);
				}
			    } else {
				$cname = '"' . trim(preg_replace('/(?<!\d)(' . $this->src_peername . ')(?!\d)/', '', ($this->CallerIDext ? $this->CallerIDext : $this->CallerID) . ' ' . $agi->get_variable('CALLERID(name)', true)), "\x00..\x2F") . '"<' . $this->src_peername . '>';
				if ($result[0][5])	$cname = translit($cname);
				$agi -> set_callerid($cname);
				unset($this->src_exten);
			    }
			    $this -> CID_handover = '';
			} else {
			    $this -> CID_handover = $this -> apply_cid_rules ($this -> CallerID, $result[0][7], $result[0][8]);
			}
			$this->extext = false;
		}
	}
	if ($this->extext) {
		if ($this->removeinterprefix && strlen($this->destination) > 6)
			$this->destination = $dest_wo_int_prefix = $this -> apply_rules ($this->destination);
		else	$dest_wo_int_prefix = $this->destination;
		if (strcmp($this->destination, $this->oldphonenumber) == 0) {
			$this->destination = $this->apply_add_countryprefixto ($this->destination);
			if (($this->removeinterprefix || $this->agiconfig['prefix_required']) && $call2did===false && (strcmp($this->oldphonenumber, $this->destination) == 0 && !preg_match("/^[a-zA-Z]/", $this->oldphonenumber)) && !(preg_match("/^1[2-9]/", $this->oldphonenumber) && preg_match("/^[1a-zA-Z]/", $this->CallerID)) && strlen($this->oldphonenumber) > 5) {
//				$agi -> answer();
				$this -> let_stream_listening($agi);
				if (!preg_match("/[a-zA-Z]/", $this->oldphonenumber)) {
					$agi-> stream_file('the-number-u-dialed', '#');
					$agi-> say_digits($this->oldphonenumber, '#');
					$agi-> stream_file('pbx-invalid', '#');
				}
				return -1;
			}
		}
	}
	if ($this->destination) {
	    if (isset($this->transferername[0])) {
		$this -> agiconfig['number_try']=1;
		$onforwardcidtemp = $agi->get_fullvariable('${ONFORWARDCID1}', $this->transfererchannel, true);
		if (!$onforwardcidtemp) {
			$onforwardcidtemp = $agi->get_variable('ONFORWARDCID1', true);
		}
		$QUERY = "SELECT regexten FROM cc_sip_buddies WHERE id_cc_card = {$this->id_card} AND name = '{$onforwardcidtemp}' LIMIT 1";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		if (is_array($result) && $result[0][0] != "")
			$onforwardcid1sql = $onforwardcid1asterisk = $result[0][0];
		else $onforwardcid1sql = $onforwardcid1asterisk = $onforwardcidtemp;
		if ($onforwardcidtemp == $this->transferername[0] || $onforwardcidtemp == $this->CallerID) {
			$this->backafter = $onforwardcidtemp;
			$this->backextafter = $onforwardcid1sql;
//			$onforwardcid1asterisk = "↗" . $onforwardcid1asterisk;
			$onforwardcid1sql = "&#11017;" . $onforwardcid1sql;
		} else {
			$agi -> set_variable('MASTER_CHANNEL(__TEMPONFORWARDCID1)', $onforwardcidtemp);
			$this->cidafter = $onforwardcidtemp;
			$this->cidextafter = $onforwardcid1sql;
		}
		$onforwardcidtemp = $agi->get_fullvariable('${ONFORWARDCID2}', $this->transfererchannel, true);
		if (!$onforwardcidtemp) {
			$onforwardcidtemp = $agi->get_variable('ONFORWARDCID2', true);
		}
		$QUERY = "SELECT regexten FROM cc_sip_buddies WHERE id_cc_card = {$this->id_card} AND name = '{$onforwardcidtemp}' LIMIT 1";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		if (is_array($result) && $result[0][0] != "")
			$onforwardcid2sql = $onforwardcid2asterisk = $result[0][0];
		else $onforwardcid2sql = $onforwardcid2asterisk = $onforwardcidtemp;
		if ($onforwardcidtemp == $this->transferername[0] || $onforwardcidtemp == $this->CallerID) {
			$this->backafter = $onforwardcidtemp;
			$this->backextafter = $onforwardcid1sql;
//			$onforwardcid2asterisk .= "↗";
			$onforwardcid2asterisk = $onforwardcid1asterisk;
			$onforwardcid1asterisk = $onforwardcid2sql;
			$onforwardcid2sql .= "&#11016;";
		} else {
			$agi -> set_variable('MASTER_CHANNEL(__TEMPONFORWARDCID1)', $onforwardcidtemp);
			$this->cidafter = $onforwardcidtemp;
			$this->cidextafter = $onforwardcid2sql;
		}
		$this->CallerIDext = $onforwardcid1sql . "&#8660;" . $onforwardcid2sql;
		$agi -> set_callerid('"' . $onforwardcid1asterisk . '*"<' . $onforwardcid2asterisk . '>');
	    } else {
		$tempcid1 = ($this->CallerID == $this->src_peername && $this->src != 'NULL') ? $this->src : $this->CallerID;
		$agi -> set_variable('MASTER_CHANNEL(__TEMPONFORWARDCID1)', $tempcid1);
		$agi -> set_variable('MASTER_CHANNEL(__TEMPONFORWARDPEER1)', $this->src_peername);
		if ($this->extext && $this->destination>0) {
			$QUERY = "SELECT cid FROM cc_callerid, cc_country aa
				WHERE '{$this->destination}' LIKE concat(aa.countryprefix,'%') AND id_cc_card={$this->id_card} AND activated='t' AND cid LIKE '$this->CID_handover' AND (cli_replace=1
				OR (cli_replace=2 AND (SELECT cid FROM cc_callerid, cc_country bb WHERE cid LIKE concat(bb.countryprefix,'%') AND id_cc_card = {$this->id_card} AND verify = 1 AND cid != '{$this->destination}'
					AND ((cli_localreplace = 1 AND bb.countryprefix LIKE aa.countryprefix) OR cli_prefixreplace LIKE concat(aa.countryprefix,'%')) ORDER BY bb.countryprefix DESC LIMIT 1) IS NOT NULL)
				) ORDER BY aa.countryprefix DESC LIMIT 1";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			if (is_array($result)) {
				$this -> CID_handover = '';
			}
		}
	    }
	}

	//Check if Account have restriction
	if ($this->restriction == 1 || $this->restriction == 2 ) {

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[ACCOUNT WITH RESTRICTION]");

		$QUERY = "SELECT * FROM cc_restricted_phonenumber WHERE id_card='".$this->id_card."' AND '".$this->destination."' LIKE number";
		$QUERY .= " LIMIT 1";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);

		if ($this->restriction == 1) {
			// NOT ALLOW TO CALL RESTRICTED NUMBERS
			if (is_array($result)) {
				// NUMBER NOT AUHTORIZED
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[NUMBER NOT AUHTORIZED - NOT ALLOW TO CALL RESTRICTED NUMBERS]");
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-not-authorized-phonenumber', '#');
				return -1;
			}
		} else {
			// ALLOW TO CALL ONLY RESTRICTED NUMBERS
			if (!is_array($result)) {
				//NUMBER NOT AUHTORIZED
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[NUMBER NOT AUHTORIZED - ALLOW TO CALL ONLY RESTRICTED NUMBERS]");
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-not-authorized-phonenumber', '#');
				return -1;
			}
		}
	}

        //Test if the destination is a did or fax
        //if call to did is authorized chez if the destination is a did of system
	$iscall2did = $iscall2fax = false;

	$QUERY =  "SELECT id_cc_card, localstationid, store, email, notify_email FROM cc_fax
		LEFT JOIN cc_card_concat bb ON bb.concat_card_id = cc_fax.id_cc_card
		LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = $this->id_card ) AS v ON bb.concat_id = v.concat_id
		WHERE (id_cc_card = $this->id_card OR v.concat_id IS NOT NULL) AND ext_num = '$this->destination' LIMIT 1";
	$result_did = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
	if (is_array($result_did)) {
		$this->faxidcard	= $result_did[0][0];
		$this->localstationid	= $result_did[0][1];
		$this->faxstore 	= $result_did[0][2];
		$this->faxemail 	= $result_did[0][3];
		$this->fax2mail 	= $result_did[0][4];
		$iscall2fax = true;
	} elseif ($call2did===true) {
		$this -> debug( INFO, $agi, __FILE__, __LINE__, "[CALL 2 DID]");
		$QUERY = "SELECT username FROM cc_did, cc_card
".			"WHERE cc_card.status=1 AND cc_card.id=iduser AND cc_did.activated=1 AND did LIKE '$this->destination'
".			"AND cc_did.startingdate <= CURRENT_TIMESTAMP AND cc_did.expirationdate > CURRENT_TIMESTAMP LIMIT 1";
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, $QUERY);
		$result_did = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		if (is_array($result_did) && !empty($result_did[0][0])) {
			$iscall2did =true;
//			$this->auth_through_accountcode=false;
			if ($this -> cardnumber != $result_did[0][0]) {
				if ($this -> set_inuse_username) {
					$this -> callingcard_acct_start_inuse($agi,0);
				}
				$this -> callingcard_ivr_authenticate($agi,$result_did[0][0]);
				$this -> agiconfig['number_try'] = 1;
			}
		}
	}
	$this -> debug( INFO, $agi, __FILE__, __LINE__, "DESTINATION ::> ".$this->destination);

	if (!$iscall2did && !$iscall2fax) {
		if ($this->destination) $agi -> set_variable('MASTER_CHANNEL(__TEMPONFORWARDCID2)', $this->destination);

//		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RULES APPLY ON DESTINATION ::> ".$this->destination);

		// TRIM THE "#"s IN THE END, IF ANY
		// usefull for SIP or IAX friends with "use_dnid" when their device sends also the "#"
		// it should be safe for normal use
		$this->destination = rtrim($this->destination, "#");

		// SAY BALANCE AND FT2C PACKAGE IF APPLICABLE
		// this is hardcoded for now but we might have a setting in a2billing.conf for the combination
		if ($this->destination=='*0') {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SAY BALANCE ::> ".$this->credit."]");
				$this -> fct_say_balance ($agi, $this->credit);
				
				// Retrieve this customer's FT2C package details
				$QUERY = "SELECT freetimetocall, label, packagetype, billingtype, startday, id_cc_package_offer ".
						 "FROM cc_card RIGHT JOIN cc_tariffgroup ON cc_tariffgroup.id=cc_card.tariff ".
						 "RIGHT JOIN cc_package_offer ON cc_package_offer.id=cc_tariffgroup.id_cc_package_offer ".
						 "WHERE cc_card.id='".$this->id_card."'";
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
				if (is_array($result) && ($result[0][0] > 0) ) {
				        $freetime = $result[0][0];
				        $label = $result[0][1];
				        $packagetype = $result[0][2];
				        $billingtype = $result[0][3];
				        $startday = $result[0][4];
				        $id_cc_package_offer = $result[0][5];
				        $freetimetocall_used = $this->FT2C_used_seconds($this->DBHandle, $this->id_card, $id_cc_package_offer, $billingtype, $startday);
				
				        //TO MANAGE BY PACKAGE TYPE IT -> only for freetime
				        if (($packagetype == 0) || ($packagetype == 1)) {
			                $minutes = intval(($freetime-$freetimetocall_used)/60);
			                $seconds = ($freetime-$freetimetocall_used) % 60;
				        } else {
			                $minutes = intval($freetimetocall_used/60);
			                $seconds = $freetimetocall_used % 60;
				        }
				        // Now say either "You have X minutes and Y seconds of free package calls remaining this week/month"
				        // or "You have dialed X minutes and Y seconds of free package calls this week/month"
				        if (($packagetype == 0) || ($packagetype == 1)) {
			                $agi-> stream_file('prepaid-you-have', '#');
				        } else {
			                $agi-> stream_file('prepaid-you-have-dialed', '#');
				        }
				        if (($minutes > 0) || ($seconds == 0)) {
							if ($minutes==1) {
								if ((strtolower($this ->current_language)=='ru')) {
									$agi-> stream_file('digits/1f', '#');
								} else {
									$agi->say_number($minutes);
								}
								$agi-> stream_file('prepaid-minute', '#');
							} else {
								$agi->say_number($minutes);
								if ((strtolower($this ->current_language)=='ru')&& ( ( $minutes%10==2) || ($minutes%10==3 )|| ($minutes%10==4)) ) {
									// test for the specific grammatical rules in RUssian
									$agi-> stream_file('prepaid-minute2', '#');
								} else {
									$agi-> stream_file('prepaid-minutes', '#');
								}
							}
				        }
				        if ($seconds > 0) {
				                if ($minutes > 0) $agi-> stream_file('vm-and', '#');
				                if ($seconds == 1) {
				                        if ((strtolower($this ->current_language)=='ru')) {
				                                $agi-> stream_file('digits/1f', '#');
				                        } else {
				                                $agi->say_number($seconds);
				                        }
				                        $agi-> stream_file('prepaid-second', '#');
				                } else {
				                        $agi->say_number($seconds);
				                        if ((strtolower($this ->current_language)=='ru')&& ( ( $seconds%10==2) || ($seconds%10==3 )|| ($seconds%10==4)) ) {
				                                // test for the specific grammatical rules in RUssian
				                                $agi-> stream_file('prepaid-second2', '#');
				                        } else {
				                                $agi-> stream_file('prepaid-seconds', '#');
				                        }
				                }
				        }
				        $agi-> stream_file('prepaid-of-free-package-calls', '#');
				        if (($packagetype == 0) || ($packagetype == 1)) {
				                $agi-> stream_file('prepaid-remaining', '#');
				                $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SAY FT2C REMAINING ::> ".$minutes.":".$seconds."]");
				        } else {
				                $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SAY FT2C USED ::> ".$minutes.":".$seconds."]");
				        }
				        $agi-> stream_file('this', '#');
				        if ($billingtype == 0) {
				                $agi-> stream_file('month', '#');
				        } else {
				                $agi-> stream_file('weeks', '#');
				        }
				}
				return -1;
		}
		if (!preg_match("/^[a-zA-Z]/", $this->destination) && $this->destination<=0) {
			$prompt = "prepaid-invalid-digits";
                // do not play the error message if the destination number is not numeric
                // because most probably it wasn't entered by user (he has a phone keypad remember?)
                // it helps with using "use_dnid" and extensions.conf routing
			if (is_numeric($this->destination)) $agi-> stream_file($prompt, '#');
			return -1;
		}
		// STRIP * FROM DESTINATION NUMBER
		if (!preg_match("/^[a-z]/", $this->destination))
			$this->destination = str_replace('*', '', $this->destination);

		$this->save_redial_number($agi, $this->destination);

		// LOOKUP RATE : FIND A RATE FOR THIS DESTINATION
		$resfindrate = $RateEngine->rate_engine_findrates($this, $this->destination,$this->tariff);
		if ($resfindrate==0) {
				$this -> debug( ERROR, $agi, __FILE__, __LINE__, $this->destination." ::> RateEngine didnt succeed to match the dialed number over the ratecard (Please check : id the ratecard is well create ; if the removeInter_Prefix is set according to your prefix in the ratecard ; if you hooked the ratecard to the Call Plan)");
				$this -> let_stream_listening($agi);
				sleep(1);
				$agi-> stream_file('the-number-u-dialed', '#');
				$agi-> stream_file('pbx-invalid-number', '#');
			} else {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "OK - RESFINDRATE::> ".$resfindrate);
			}

		// IF DONT FIND RATE
		if ($resfindrate==0) {
			$this -> let_stream_listening($agi);
			$prompt="prepaid-dest-unreachable";
			$agi-> stream_file($prompt, '#');
			return -1;
		}
		// CHECKING THE TIMEOUT
		$credit = is_null($callertodidcredit) ? $this->credit : $callertodidcredit;
		$res_all_calcultimeout = $RateEngine->rate_engine_all_calcultimeout($this, $credit);

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES_ALL_CALCULTIMEOUT ::> $res_all_calcultimeout");
		if (!$res_all_calcultimeout) {
			$this -> let_stream_listening($agi);
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "CREDIT :: $this->credit");
			$prompt="prepaid-no-enough-credit";
			$agi-> stream_file($prompt, '#');
			return -1;
	        }

	        // calculate timeout
	        //$this->timeout = intval(($this->credit * 60*100) / $rate);  // -- RATE is millime cents && credit is 1cents

/**
	        $this->timeout = $RateEngine-> ratecard_obj[0]['timeout'];
	        $timeout = $this->timeout;
	        if ($this->agiconfig['cheat_on_announcement_time']==1) {
	        	$res_all_calcultimeout = 0;
	        	$timeout = $RateEngine-> ratecard_obj[0]['timeout_without_rules'];
	        }

	        $announce_time_correction = $RateEngine->ratecard_obj[0][50];
	        $timeout = $timeout * $announce_time_correction;
	        $this -> fct_say_time_2_call($agi,$timeout,$RateEngine->ratecard_obj[0][12],$res_all_calcultimeout);
**/
		return 1;
	} else {  //END if is not a call to DID or FAX
	        //it s call to did or fax
	        $this->save_redial_number($agi, $this->destination);
	        if ($iscall2fax) {
			return "2FAX";
		} else return "2DID";
	    }
	}


	/**
	 *	Function call_sip_iax_buddy : make the Sip/IAX free calls
	 *
	 *  @param object $agi
	 *  @param object $RateEngine
     *  @param integer $try_num
     *  @return 1 if Ok ; -1 if error
	**/
	public function call_sip_iax_buddy($agi, &$RateEngine, $try_num)
	{
		$res = 0;

		if ( ($this->agiconfig['use_dnid']==1) && (!in_array ($this->dnid, $this->agiconfig['no_auth_dnid'])) && (strlen($this->dnid)>2 )) {
			$this->destination = $this->dnid;
		} else {
			$res_dtmf = $agi->get_data('prepaid-sipiax-enternumber', 6000, $this->config['global']['len_aliasnumber'], '#');
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES DTMF : ".$res_dtmf ["result"]);
			$this -> destination = $res_dtmf ["result"];

			if ($this->destination<=0) {
				return -1;
			}
		}
		
		$this->save_redial_number($agi, $this->destination);
		
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "SIP o IAX DESTINATION : ".$this->destination);
		$sip_buddies = 0;
		$iax_buddies = 0;

		$QUERY = "SELECT name, cc_card.username FROM cc_iax_buddies, cc_card WHERE cc_iax_buddies.id_cc_card=cc_card.id AND useralias='".$this->destination."'";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, $result);

		if ( is_array($result) && count($result) > 0) {
			$iax_buddies = 1;
			$destiax = $result[0][0];
			$dest_username = $result[0][1];
		}

		$card_alias = $this->destination;
		$QUERY = "SELECT name, cc_card.username FROM cc_sip_buddies, cc_card WHERE cc_sip_buddies.id_cc_card=cc_card.id AND useralias='".$this->destination."'";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RESULT : ".print_r($result,true));
		
		if ( is_array($result) && count($result) > 0) {
			$sip_buddies = 1;
			$destsip=$result[0][0];
			$dest_username = $result[0][1];
		}

		if (!$sip_buddies && !$iax_buddies) {
			$agi-> stream_file('prepaid-sipiax-num-nomatch', '#');
			return -1;
		}
		
		for ($k=0;$k< $sip_buddies+$iax_buddies;$k++)
		{
			if ($k==0 && $sip_buddies) {
				$this->tech = 'SIP';
				$this->destination= $destsip;
			} else {
				$this->tech = 'IAX2';
				$this->destination = $destiax;
			}
			if ($this -> CC_TESTING) $this->destination = "kphone";

			if ((($this->send_sound || $this->send_text) && $this->speech2mail) || $this->monitor == 1 || $this->agiconfig['record_call'] == 1) {
			    $this->dl_short = MONITOR_PATH . "/" . $this->username . "/" . date('Y') . "/" . date('n') . "/" . date('j') . "/";
				$command_mixmonitor = "MixMonitor ". $this->dl_short ."{$this->uniqueid}.{$this->agiconfig['monitor_formatfile']}|b";
				$command_mixmonitor = $this -> format_parameters ($command_mixmonitor);
				$myres = $agi->exec($command_mixmonitor);
				$this -> debug( INFO, $agi, __FILE__, __LINE__, $command_mixmonitor);
			}
            
			$agi->set_callerid($this->useralias);
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[EXEC SetCallerID : ".$this->useralias."]");

			$dialparams = $this->agiconfig['dialcommand_param_sipiax_friend'];
			$dialstr = $this->tech."/".$this->destination.$dialparams;

			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "app_callingcard sip/iax friend: Dialing '$dialstr' ".$this->tech." Friend.\n");

			//# Channel: technology/number@ip_of_gw_to PSTN
			// Dial(IAX2/guest@misery.digium.com/s@default)
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "DIAL $dialstr");
			$myres = $this -> run_dial($agi, $dialstr);

			$this -> DbReConnect($agi);
			$answeredtime = $agi->get_variable("ANSWEREDTIME",true);
			$dialstatus = $agi->get_variable("DIALSTATUS",true);

			if ((($this->send_sound || $this->send_text) && $this->speech2mail) || $this->monitor == 1 || $this->agiconfig['record_call'] == 1) {
				// Monitor(wav,kiki,m)
				$myres = $agi->exec($this -> format_parameters ("StopMixMonitor"));
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "EXEC StopMixMonitor (".$this->uniqueid.")");
				$monfile = $this->dl_short ."{$this->uniqueid}.";
				$monfile.= ($this->agiconfig['monitor_formatfile'] == 'wav49') ? 'WAV' : $this->agiconfig['monitor_formatfile'];
				if (file_exists($monfile) && filesize($monfile) < 100) {
					unlink($monfile);
				}
			}

			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[".$this->tech." Friend][K=$k]:[ANSWEREDTIME=".$answeredtime."-DIALSTATUS=".$dialstatus."]");

			//# Ooh, something actually happend!
			if ($dialstatus  == "BUSY") {
				$answeredtime = 0;
				if ($this->agiconfig['busy_timeout'] > 0)
					$res_busy = $agi->exec("Busy ".$this->agiconfig['busy_timeout']);
				$agi-> stream_file('prepaid-isbusy', '#');
			} elseif ($dialstatus == "NOANSWER") {
				$answeredtime = 0;
				$agi-> stream_file('prepaid-noanswer', '#');
			} elseif ($dialstatus == "CANCEL") {
				$answeredtime = 0;
			} elseif ($dialstatus == "ANSWER") {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "-> dialstatus : $dialstatus, answered time is ".$answeredtime." \n");
			} elseif ($k+1 == $sip_buddies+$iax_buddies) {
				$prompt="prepaid-dest-unreachable";
				$agi-> stream_file($prompt, '#');
			}

			if (($dialstatus  == "CHANUNAVAIL") || ($dialstatus  == "CONGESTION"))
				continue;
			
			if (strlen($this -> dialstatus_rev_list[$dialstatus])>0) {
				$terminatecauseid = $this -> dialstatus_rev_list[$dialstatus];
			} else {
				$terminatecauseid = 0;
			}
			
			if ($answeredtime > 0) {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CC_RATE_ENGINE_UPDATESYSTEM: usedratecard K=$K - (answeredtime=$answeredtime :: dialstatus=$dialstatus :: cost=$cost)]");
				$QUERY = "INSERT INTO cc_call (uniqueid, sessionid, card_id, nasipaddress, starttime, sessiontime, calledstation, ".
					" terminatecauseid, stoptime, sessionbill, id_tariffplan, id_ratecard, id_trunk, src, sipiax, callbackid) VALUES ".
					"('".$this->uniqueid."', '".$this->channel."',  '".$this->id_card."', '".$this->hostname."',";
				$QUERY .= " CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND ";
				$QUERY .= ", '$answeredtime', '".$card_alias."', '$terminatecauseid', now(), '0', '0', '0', '0', '$this->CallerID', '1', '$this->callback_id')";
				
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
				monitor_recognize($this);
				return 1;
			}
		}
		
		if ($this->voicemail) {
			
			if (($dialstatus =="CHANUNAVAIL") || 
				($dialstatus == "CONGESTION") ||
				($dialstatus == "NOANSWER")) {
				// The following section will send the caller to VoiceMail 
				// with the unavailable priority.
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[STATUS] CHANNEL UNAVAILABLE - GOTO VOICEMAIL ($dest_username)");
				
				$vm_parameters = $this -> format_parameters ($dest_username.'|u');
				$agi-> exec('VoiceMail', $vm_parameters);
			}

			if (($dialstatus =="BUSY")) {
				// The following section will send the caller to VoiceMail with the busy priority.
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[STATUS] CHANNEL BUSY - GOTO VOICEMAIL ($dest_username)");
				
				$vm_parameters = $this -> format_parameters ($dest_username.'|b');
				$agi-> exec('VoiceMail', $vm_parameters);
			}
		}

		return -1;
	}

	public function ivr_did ($agi)
	{
		$this -> let_stream_listening($agi, true);
		$agi -> exec('Playtones ring');
		sleep(2);
		$num = mt_rand(0,9);
		if ($num>=2 && $num<=6) {
			$agi-> stream_file("{$this->username}/monteure".$num, $num);
		}
		usleep(mt_rand(0,1000000));
		$num = (string)mt_rand(0,9);
		$res_dtmf = $agi->get_data("{$this->username}/rand".$num, 1, 1);
		$dtmf = (string)$res_dtmf["result"];
		if ($dtmf == $num) {
			sleep(1);
			$agi-> stream_file("queue-periodic-announce", "#");
			return 0;
		}
		if ((empty($dtmf) || $dtmf < 0) && $agi -> channel_status('',true) != AST_STATE_DOWN) {
		    if ($this -> G_startime == 0) {
			$agi -> answer();
			$this -> G_startime = time();
		    }
		    $res_dtmf = $agi->get_data('silence/1', 9000, 1);
		    $dtmf = (string)$res_dtmf["result"];
		    if ($dtmf == $num) {
			if (time()-$this->G_startime > 3)
				return 1;
			return 0;
		    }
		}
		return 0;
	}

        /*
         *      function would set when the did_destination is used or when it release
         */
        public function destination_start_inuse($agi, $id, $inuse) {

                if ($inuse) {
			$QUERY = "SELECT id FROM cc_did_destination WHERE id=$id AND (destmaxuse-destinuse>0 OR destmaxuse<0)";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			if ( is_array($result) && count($result) > 0) {
				$QUERY = "UPDATE cc_did_destination SET destinuse=destinuse+1 WHERE id=$id";
			} else {
				return true; // Destination is busy
			}
                } else {
                        $QUERY = "UPDATE cc_did_destination SET destinuse=destinuse-1 WHERE id=$id";
                }

                if (!$this -> CC_TESTING) $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);

                return false; // Destination is available
        }

	public function is_voip ($setur)
	{
	    if (stripos($setur,"QUEUE ") === 0 || strpos($setur,"/") > 2)
		return true;
	    return false;
	}

// waitdigits =
// 'цифра или номер' - выполнить пункт меню по нажатию одной или нескольких цифр
// '-1' - ничего не нажато, идем на повтор
// '-2' - нажато не верно,  идем на повтор
// '-3' - ничего не нажато, выход на destinationnum
// '-4' - нажато не верно,  выход на destinationnum
// '-5' - выбран extension
	public function ivr (&$agi,&$instdest,&$initdest,&$isvoip)
	{
		$instdest = $initdest;
		$QUERY = "SELECT DISTINCT cc_ivr_destinations.id, repeats, waitsecsfordigits, waitdigits, destinationnum, playsoundcallee, cc_ivr.id
			    FROM cc_ivr, cc_ivr_destinations WHERE id_cc_card = '{$this->id_card}' AND ivrname LIKE '{$initdest}' AND id_cc_ivr=cc_ivr.id";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);

		while (is_array($result)) {
			if ($agi -> channel_status('',true) == AST_STATE_RING) {
				$this -> G_startime = time();
				$this -> debug( INFO, $agi, __FILE__, __LINE__, '[ANSWER CALL]');
				$agi -> answer();
			}
			$initdest = $instdest;
			$this -> calleesound = '';
			$maxlen = 0;
			$waitdigits = array();
			foreach ($result as $value) {
			    if ($value[3]>=0 && strlen($value[3])>$maxlen)
				$maxlen = strlen($value[3]);
			    if ($value[3]=='-5') { // Dial Extensions
				$QUERY = "SELECT regexten AS field FROM cc_sip_buddies
						LEFT JOIN cc_card_concat bb ON bb.concat_card_id = id_cc_card
						LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = '{$this->id_card}' ) AS v ON bb.concat_id = v.concat_id
						WHERE (id_cc_card = '{$this->id_card}' OR v.concat_id IS NOT NULL) AND external = 0
					UNION ALL
					    SELECT ext_num AS field FROM cc_fax
						LEFT JOIN cc_card_concat bb ON bb.concat_card_id = cc_fax.id_cc_card
						LEFT JOIN ( SELECT aa.concat_id FROM cc_card_concat aa WHERE aa.concat_card_id = '{$this->id_card}' ) AS v ON bb.concat_id = v.concat_id
						WHERE id_cc_card = '{$this->id_card}' OR v.concat_id IS NOT NULL
					ORDER BY field";
				$resmax = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
				if (is_array($resmax)) {
					foreach ($resmax as $val) {
					    if (strlen($val[0])>$maxlen)
						$maxlen = strlen($val[0]);
					    $value[3] = $value[4] = $val[0];
//					    $dests[] = $val[0];
					    $result[] = $value;
					}
				}
			    }
			}
			foreach ($result as $value) {
			    $waitdigits[] = $value[3];
			}
			$QUERY = "SELECT id_cc_ivr,id_cc_ivr_dest,timeout,playsound FROM cc_ivr_sounds ".
				    "WHERE {$result[0][6]}=id_cc_ivr OR id_cc_ivr_dest IN (SELECT id FROM cc_ivr_destinations WHERE id_cc_ivr={$result[0][6]}) ORDER BY cc_ivr_sounds.id";
			$resultsound = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			$ed = '0123456789';
			for ($r=$result[0][1];$r>=0;$r--) {
			    $this -> calleesound = '';
			    $res = array();
			    $res['result'] = 0;
			    foreach ($resultsound as $val) {
				if ($val[0]>0) {
					$wait = $val[2];
					if (isset($first_dtmf)) $wait++;
					if ($wait>0) {
					    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						return false;
					    }
					    $res = $agi -> wait_for_digit($wait*1000);
					    if ($res['result'] > 0) break;
					}
					$file = $val[3];
					if ($file) {
					    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						return false;
					    }
					    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
					    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
					    $res = $agi -> stream_file($file, $ed);
					    if ($res['result'] > 0) break;
					}
				}
			    }
			    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
				return false;
			    }
			    if ($res['result'] > 0) {
				$first_dtmf = chr($res['result']);
				if ($result[0][2]<6) $result[0][2] = 6;
			    } else $first_dtmf = '';
			    while (array_search($first_dtmf, $waitdigits) === false && strlen($first_dtmf)<$maxlen) {
				$res = $agi -> wait_for_digit($result[0][2]*1000);
				if ($res['result'] > 0) $first_dtmf .= chr($res['result']);
					else break;
				if ($result[0][2]<6) $result[0][2] = 6;
			    }
			    $this -> dtmfs .= (($this->dtmfs)?"_":"").$first_dtmf;
			    $key = array_search($first_dtmf, $waitdigits);
			    if ($first_dtmf=='' || $key===false) { // ничего не нажали или нажали неверно
				if ($r>0) { // если повтор...
				    if ($first_dtmf=='') { // и ничего не нажали
					foreach ($result as $value) {
					    if ($value[3]=='-1') {
						foreach ($resultsound as $val) { // перебираем что проиграть перед повтором
						    if ($val[1]==$value[0]) {
							$wait = $val[2];
							$file = $val[3];
							if ($wait>0) {
							    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
								return false;
							    }
							    sleep((int)$wait);
							}
							if ($file) { // проиграем перед повтором
							    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
								return false;
							    }
							    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
							    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
							    $agi -> evaluate("STREAM FILE $file \"#\" 0");
							}
						    }
						}
						break;
					    }
					}
				    } else { // и нажали неверно
					foreach ($result as $value) {
					    if ($value[3]=='-2') {
						foreach ($resultsound as $val) { // перебираем что проиграть перед повтором
						    if ($val[1]==$value[0]) {
							$wait = $val[2];
							$file = $val[3];
							if ($wait>0) {
							    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
								return false;
							    }
							    sleep((int)$wait);
							}
							if ($file) { // проиграем перед повтором
							    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
								return false;
							    }
							    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
							    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
							    $agi -> evaluate("STREAM FILE $file \"#\" 0");
							}
						    }
						}
						break;
					    }
					}
				    }
				    continue; // повтор IVR
				}
				
//				continue 4; // уходим на следующий PRIOR
			    } else {
				$instdest = $result[$key][4];
				$isvoip = $this->is_voip($instdest);
				foreach ($resultsound as $val) { // перебираем что проиграть перед вызовом
				    if ($val[1]==$result[$key][0]) {
					$wait = $val[2];
					$file = $val[3];
					if ($wait>0) {
					    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						return false;
					    }
					    sleep((int)$wait);
					}
					if ($file) { // проиграем перед повтором
					    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						return false;
					    }
					    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
					    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
					    $agi -> evaluate("STREAM FILE $file \"#\" 0");
					}
				    }
				}
				$file = $result[$key][5];
				if ($file) {
				    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
					return false;
				    }
				    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
				    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
				    $this -> calleesound = $file;
				}
				$QUERY = "SELECT DISTINCT cc_ivr_destinations.id, repeats, waitsecsfordigits, waitdigits, destinationnum, playsoundcallee, cc_ivr.id
					FROM cc_ivr, cc_ivr_destinations WHERE id_cc_card = '{$this->id_card}' AND ivrname LIKE '{$instdest}' AND id_cc_ivr=cc_ivr.id";
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
				continue 2;
			    }
			}

			if ($first_dtmf=='') { // и ничего не нажали
				foreach ($result as $value) {
				    if ($value[3]=='-3') {
					$instdest = $value[4];
					$isvoip = $this->is_voip($instdest);
					foreach ($resultsound as $val) { // перебираем что проиграть перед повтором
					    if ($val[1]==$value[0]) {
						$wait = $val[2];
						$file = $val[3];
						if ($wait>0) {
						    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
							return false;
						    }
						    sleep((int)$wait);
						}
						if ($file) { // проиграем перед повтором
						    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
							return false;
						    }
						    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
						    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
						    $agi -> evaluate("STREAM FILE $file \"#\" 0");
						}
					    }
					}
					$file = $value[5];
					if ($file) {
					    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						return false;
					    }
					    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
					    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
					    $this -> calleesound = $file;
					}
					break;
				    }
				}
			} else { // и нажали неверно
				foreach ($result as $value) {
				    if ($value[3]=='-4') {
					$instdest = $value[4];
					$isvoip = $this->is_voip($instdest);
					foreach ($resultsound as $val) { // перебираем что проиграть перед повтором
					    if ($val[1]==$value[0]) {
						$wait = $val[2];
						$file = $val[3];
						if ($wait>0) {
						    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
							return false;
						    }
						    sleep((int)$wait);
						}
						if ($file) { // проиграем перед повтором
						    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
							return false;
						    }
						    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
						    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
						    $agi -> evaluate("STREAM FILE $file \"#\" 0");
						}
					    }
					}
					$file = $value[5];
					if ($file) {
					    if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						return false;
					    }
					    $diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
					    if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
					    $this -> calleesound = $file;
					}
					break;
				    }
				}
			}
			$QUERY = "SELECT DISTINCT cc_ivr_destinations.id, repeats, waitsecsfordigits, waitdigits, destinationnum, playsoundcallee, cc_ivr.id
				FROM cc_ivr, cc_ivr_destinations WHERE id_cc_card = '{$this->id_card}' AND ivrname LIKE '{$instdest}' AND id_cc_ivr=cc_ivr.id";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		}
		return true;
	}

	/**
	 *	Function call_did
	 *
	 *  @param object $agi
	 *  @param object $RateEngine
	 *  @param object $listdestination
	 	cc_did.id, cc_did_destination.id, billingtype, cc_did.id_trunk,	destination, cc_did.id_trunk, voip_call

     *  @return 1 if Ok ; -1 if error
	**/
	public function call_did ($agi, &$RateEngine, $listdestination)
	{
		$res=0;
		$this->agiconfig['say_balance_after_auth'] = 0;
		$this->agiconfig['say_timetocall'] = 0;
		$didvoicebox = NULL;
		$connection_charge = $listdestination[0][8];
		$selling_rate = $this->didsellrate = $listdestination[0][9];
		$this->didbuyrate	= $listdestination[0][37];
		$this->billblock	= $listdestination[0][38];
		if ($this->billblock < 1)
		    $this->billblock	= 1;

		$onetry = true;
		$callcount=0;
		$keytotal = count($listdestination);
		foreach ($listdestination as $inst_listdestination) {
			$key = false;
			$callcount++;
			
			if (($listdestination[0][2]==0) || ($listdestination[0][2]==2)) {
			    $doibill = 1;
			} else {
			    $doibill = 0;
			}

			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[A2Billing] DID call friend: FOLLOWME=$callcount (cardnumber:".$inst_listdestination[6]."|destination:".$inst_listdestination[4]."|tariff:".$inst_listdestination[3].")\n");

			$this->agiconfig['cid_enable']	= 0;
			$this->tariff 					= $inst_listdestination[3];
			$this->destination = $initialdestination	= $inst_listdestination[4];
			$isvoip 			 = $this->is_voip($inst_listdestination[4]);
			$this->accountcode = $this->username		= $inst_listdestination[6];
			$this->useralias 				= $inst_listdestination[7];
			$this->time_out 				= $inst_listdestination[30];
			$this->id_did					= $inst_listdestination[0];
			$this->dnid					= $inst_listdestination[10];
			$this->margin					= $inst_listdestination[31];
			$this->id_diller				= $inst_listdestination[32];
			$calleridname					= $inst_listdestination[41];
			$this->speech2mail				= $inst_listdestination[42];
			$this->send_text				= $inst_listdestination[43];
			$this->send_sound				= $inst_listdestination[44];
			$this->calleesound				= ($inst_listdestination[45]) ? $this->accountcode."/".$inst_listdestination[45] : NULL;
			$didvoicebox				= is_null($inst_listdestination[33]) ? NULL : $inst_listdestination[33]."@".$this->username;
			$file  = preg_replace('/\.[^\.\/]+$/','',basename($inst_listdestination[29]));
			
			if ($this -> set_inuse_username) $this -> callingcard_acct_start_inuse($agi,0);
			
			// MAKE THE AUTHENTICATION TO GET ALL VALUE : CREDIT - EXPIRATION - ...
			if ($this -> callingcard_ivr_authenticate($agi)!=0) {
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[A2Billing] DID call friend: AUTHENTICATION FAILS !!!\n");
			} else {
				// CHECK IF DESTINATION IS SET AND CREDIT IS ENOUGH
				if (strlen($inst_listdestination[4])==0 || $inst_listdestination[8]+$inst_listdestination[9]>$this->credit)
					continue;

				if ($inst_listdestination[28]) {
					if ($this -> G_startime == 0)
					    $this -> G_startime = time();
					$agi -> answer();
				} elseif ($inst_listdestination[29]) {
					$this -> let_stream_listening($agi, true);
				}
				if ($onetry && $inst_listdestination[39]) {
					$onetry = false;
					$QUERY="SELECT calledstation FROM cc_call ".
						"LEFT JOIN cc_callerid ON cid LIKE src AND id_cc_card = card_id ".
						"WHERE src LIKE '$this->CallerID' AND ((sipiax IN (0,2,3) AND starttime > DATE_SUB(NOW(), INTERVAL '{$inst_listdestination[40]}' DAY) AND sessiontime>20) ".
							"OR ((sipiax=7 OR sipiax=4) AND starttime > DATE_SUB(NOW(), INTERVAL '2' MINUTE))) AND cid IS NULL ORDER BY starttime DESC LIMIT 1";
					$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
					if (!(is_array($result) && count($result) > 0) && $this -> prefixallow && $this->ivr_did($agi) == 0) {
					    $agi -> hangup();
					    break;
					}
				}
				if ($this -> destination_start_inuse($agi, $inst_listdestination[1], 1))
					continue;
				if ($file) {
					$diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
					if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
					sleep(2);
					$agi -> evaluate("STREAM FILE $file \"#\" 0");
				}
	    while (true) {
		if (!$this -> ivr($agi,$inst_listdestination[4],$initialdestination,$isvoip)) {
			$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
			$RateEngine->dialstatus = $dialstatus = "CANCEL";
			$RateEngine->rate_engine_updatesystem($this, $agi, $this->destination, $doibill, 2);
			break 2;
		}
// IF VOIP CALL
				if ($isvoip) {
					$monfile = false;
					$localcount = substr_count(strtoupper($inst_listdestination[4]),"LOCAL/");
					// RUN MIXMONITOR TO RECORD CALL
					if (((($this->send_sound || $this->send_text) && $this->speech2mail) || $this->monitor == 1 || $this->agiconfig['record_call'] == 1) && $localcount < 2) {
						$this->dl_short = MONITOR_PATH . "/" . $this->username . "/" . date('Y') . "/" . date('n') . "/" . date('j') . "/";
						$monfile = $dl_short = $this->dl_short . $this->uniqueid . ".";
						if ($this -> send_sound || $this -> send_text) {
						    $format_file = 'wav';
						    $monfile .= $format_file;
						} else {
						    $format_file = $this->agiconfig['monitor_formatfile'];
						    $monfile .= ($format_file == 'wav49') ? 'WAV' : $format_file;
						}
						if ($format_file == 'wav' && $this -> send_text) {
						    $command_mixmonitor = "Monitor wav,{$dl_short}wav,b";
						    $stopmon = "StopMonitor";
						} else {
						    $command_mixmonitor = $this -> format_parameters ("MixMonitor {$dl_short}{$format_file}|b");
						    $stopmon = "StopMixMonitor";
						}
						$myres = $agi->exec($command_mixmonitor);
						$this -> debug( INFO, $agi, __FILE__, __LINE__, "Exec ". $command_mixmonitor);
					}
					
					$dialstr = $inst_listdestination[4];
					if (stripos($dialstr,"QUEUE ") !== 0) {
						$max_long = 36000000; //Maximum 10 hours
						$time2call = $this->agiconfig['max_call_call_2_did'];
						$dialparams = str_replace("%timeout%", min($time2call * 1000, $max_long), $this->agiconfig['dialcommand_param_call_2did']);
						if ($localcount > 1) {
							$dialparams.= "m";
						}
						$dialstr .= str_replace("%timeoutsec%", min($time2call, $max_long), $dialparams);
					} else {
						$que = explode(",",$dialstr);
						if ((!isset($que[1]) || stripos($que[1],"r") === false) && strpos($inst_listdestination[10],"38080021") !== 0) {
							$this -> let_stream_listening($agi);
						} else {
							$this -> let_stream_listening($agi,false,true);
						}
					}
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[A2Billing] DID call friend: Dialing '$dialstr' Friend.\n");

					if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
						$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
						break 2;
					}
					$CID_handover = $this -> apply_cid_rules ($this->CallerID, $inst_listdestination[34], $inst_listdestination[35]);
					if ($CID_handover != $this->CallerID)
						$agi -> set_variable('CALLERID(num)', $CID_handover);
					$agi -> set_variable('CHANNEL(accountcode)', $this->accountcode);
					if (!is_null($calleridname))
						$agi -> set_variable('CALLERID(name)', $calleridname);
					//# Channel: technology/number@ip_of_gw_to PSTN
					// Dial(IAX2/guest@misery.digium.com/s@default)
					$this -> debug( INFO, $agi, __FILE__, __LINE__, "DIAL $dialstr");
					$myres = $this -> run_dial($agi, $dialstr);

					$this -> DbReConnect($agi);

					$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
					$answeredtime			= $agi->get_variable("ANSWEREDTIME", true);
					if ($answeredtime == "")
							  $answeredtime = $agi->get_variable("CDR(billsec)",true);
					if (stripos($dialstr,'QUEUE ') === 0) {
						if ($answeredtime>1356000000) {
							$answeredtime	= time() - $answeredtime;
							$dialstatus	= 'ANSWER';
						} else {
							$answeredtime	= 0;
							$dialstatus	= $this -> get_dialstatus_from_queuestatus($agi);
						}
$tempdebug="ANSWEREDTIME: $answeredtime sec";
					} else	{
							$dialstatus	= $agi->get_variable("DIALSTATUS", true);
$tempdebug="DIALSTATUS: $dialstatus";
					}
					$bridgepeer = $agi->get_variable('QUEUEDNID', true);
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[ \033[1;34m".$bridgepeer." ".$dialstatus." > $tempdebug\33[0m ]");
					if (isset($stopmon)) {
						$myres = $agi->exec($stopmon);
						$this -> debug( INFO, $agi, __FILE__, __LINE__, "EXEC $stopmon (".$this->uniqueid.")");
						if (file_exists($monfile) && (filesize($monfile) < 100 || $answeredtime<2)) {
							unlink($monfile);
							$monfile = false;
						}
						if (file_exists($monfile."-in.wav") && (filesize($monfile."-in.wav") < 100 || $answeredtime<2)) {
							unlink($monfile."-in.wav");
							$monfile = false;
						}
						if (file_exists($monfile."-out.wav") && (filesize($monfile."-out.wav") < 100 || $answeredtime<2)) {
							unlink($monfile."-out.wav");
							$monfile = false;
						}
					} else $monfile = false;

					$this -> debug( INFO, $agi, __FILE__, __LINE__, "[".$inst_listdestination[4]." Friend][followme=$callcount]:[ANSWEREDTIME=".$answeredtime."-DIALSTATUS=".$dialstatus."]");
					
					//# Ooh, something actually happend!
					if ($dialstatus == "BUSY") {
						$answeredtime = 0;
						if ($keytotal > $callcount)
							continue 2;
						$this -> let_stream_listening($agi);
						if ($this->agiconfig['busy_timeout'] > 0) {
							$agi->exec("Playtones busy");
							sleep($this->agiconfig['busy_timeout']);
						} else	$agi-> stream_file('prepaid-isbusy', '#');
					} elseif ($dialstatus == "NOANSWER") {
						$answeredtime = 0;
						if ($keytotal > $callcount) {
							continue 2;
						} else $agi-> stream_file('prepaid-noanswer', '#');
					} elseif ($dialstatus == "CANCEL") {
						// Call cancelled, no need to follow-me
						$answeredtime = 0;
					} elseif ($dialstatus == "ANSWER") {
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__,"[A2Billing] DID call friend: dialstatus : $dialstatus, answered time is ".$answeredtime." \n");
					} elseif (($dialstatus  == "CHANUNAVAIL") || ($dialstatus  == "CONGESTION")) {
						$answeredtime = 0;
						if ($keytotal > $callcount) {
							continue 2;
						}
					} else {
						if ($keytotal > $callcount) {
							continue 2;
						} else $agi-> stream_file('prepaid-callfollowme', '#');
					}

					$this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - LOG CC_CALL: FOLLOWME=$callcount - (answeredtime=$answeredtime :: dialstatus=$dialstatus)]");

					if ($localcount < 2) { //(stripos($inst_listdestination[4],"@a3billing") === false) 
						if (strlen($this -> dialstatus_rev_list[$dialstatus])>0) {
							$terminatecauseid = $this -> dialstatus_rev_list[$dialstatus];
						} else {
							$terminatecauseid = 0;
						}
						if ($answeredtime == 0) $inst_listdestination[4] = $this -> realdestination;
						else {
							$inst_listdestination[4] = $this -> destination = $bridgepeer;
							$this -> debug( INFO, $agi, __FILE__, __LINE__, "Destination: " . $inst_listdestination[4]);
						}
						$QUERY = "SELECT regexten, id_cc_card FROM cc_sip_buddies WHERE name LIKE '{$inst_listdestination[4]}' LIMIT 1";
						$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
						if (is_array($result)) {
							$this->calledexten = ($result[0][0] != "") ? "'".$result[0][0]."'" : 'NULL';
							$card_called = "'".$result[0][1]."'";
						} else {
							$this->calledexten = 'NULL';
							$card_called = "'0'";
						}
						$QUERY = "INSERT INTO cc_call (uniqueid, sessionid, card_id, nasipaddress, starttime, sessiontime, calledstation, ".
							" terminatecauseid, stoptime, sessionbill, id_tariffgroup, id_tariffplan, id_ratecard, id_trunk, src, sipiax, id_did, calledexten, card_called, dnid, callbackid) VALUES ".
							"('".$this->uniqueid."', '".$this->channel."',  '".$this->id_card."', '".$this->hostname."',";
						$QUERY .= " CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND ";
						$QUERY .= ", '$answeredtime', '".$inst_listdestination[4]."', '$terminatecauseid', now(), '0', '0', '0', '0', '0', '$this->CallerID', '3', '$this->id_did', $this->calledexten, $card_called, '$this->dnid', '$this->callback_id')";
						$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
						$this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - LOG CC_CALL: SQL: $QUERY]:[result:$result]");
						
						if (stripos($inst_listdestination[4],"@a3billing") === false) {
							$this -> send_talk($agi, $this -> speech2mail, $monfile, $answeredtime, $this -> current_language);
						}
						$monfile = false;
					}
					monitor_recognize($this);
					break 2;

// ELSEIF NOT VOIP CALL
				} else {
					$this->agiconfig['use_dnid']=1;
					$this->agiconfig['say_timetocall']=0;
					$this->CID_handover = $this->CallerID;
					$this->extension = $this->destination = $inst_listdestination[4];
					if ($this->CC_TESTING) $this->extension = $this->dnid = $this->destination="011324885";
					
					$ast = $this -> callingcard_ivr_authorize($agi, $RateEngine, 0, -1);
					if ($ast==1 || $ast=='2FAX') {
					    if ($ast==1) {
						// PERFORM THE CALL
						if ($agi -> channel_status('',true) != AST_STATE_DOWN) {
						    $this->agiconfig['dialcommand_param'] = $this->agiconfig['dialcommand_param_call_2did'];
						    $result_callperf = $RateEngine->rate_engine_performcall ($agi, $this -> destination, $this, 44+$keytotal-$callcount); // 44 = For not to play announce seconds and call cost
						    if (!$result_callperf) {
							if ($keytotal == $callcount) {
							    if (is_null($didvoicebox) && is_null($this->voicebox)) {
								$prompt="prepaid-callfollowme";
								$agi-> stream_file($prompt, '#');
							    }
							} else {
							    $this -> destination_start_inuse($agi, $inst_listdestination[1], false);
							    continue 2;
							}
						    }
						} else	{
						    $RateEngine->dialstatus = 'CANCEL';
						}
						$dialstatus = $RateEngine->dialstatus;
						if ((	($dialstatus == "NOANSWER") ||
							($dialstatus == "BUSY") ||
							($dialstatus == "CHANUNAVAIL") ||
							($dialstatus == "CONGESTION")) &&
							$keytotal > $callcount) {
						    if ($key !== false /*&& is_null($this->voicebox) && is_null($didvoicebox)*/) {
						        if ($dialstatus  == "BUSY") {
						    	$this -> let_stream_listening($agi);
						    	$agi-> stream_file('prepaid-isbusy', '#');
						        } elseif ($dialstatus == "NOANSWER") {
						    	$this -> let_stream_listening($agi);
						    	$agi-> stream_file('prepaid-noanswer', '#');
						        } elseif ($dialstatus == "CHANUNAVAIL" || $dialstatus == "CONGESTION") {
						    	$this -> let_stream_listening($agi);
						    	$agi-> stream_file('prepaid-dest-unreachable', '#');
						        }
						        continue;
						    }
						    $this -> destination_start_inuse($agi, $inst_listdestination[1], false);
						    continue 2;
						}
						if ($dialstatus != "ANSWER")
						    $this -> destination = $this -> realdestination;
						$this->src = $this->src_peername = $this->calledexten = "NULL";

						// INSERT CDR  & UPDATE SYSTEM
						if ($key === false || $dialstatus == "ANSWER" || $dialstatus == "CANCEL")
							$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
						$RateEngine->rate_engine_updatesystem($this, $agi, $this->destination, $doibill, 2);
						if ($dialstatus == "CANCEL") {
//						    $this -> destination_start_inuse($agi, $inst_listdestination[1], false);
						    break 2;
						}
						$answeredtime = $RateEngine->answeredtime;
						if ($key !== false && $dialstatus != "ANSWER" /*&& is_null($this->voicebox) && is_null($didvoicebox)*/) {
						    if ($dialstatus  == "BUSY") {
							$this -> let_stream_listening($agi);
							$agi-> stream_file('prepaid-isbusy', '#');
						    } elseif ($dialstatus == "NOANSWER") {
							$this -> let_stream_listening($agi);
							$agi-> stream_file('prepaid-noanswer', '#');
						    } elseif ($dialstatus == "CHANUNAVAIL" || $dialstatus == "CONGESTION") {
							$this -> let_stream_listening($agi);
							$agi-> stream_file('prepaid-dest-unreachable', '#');
						    }
						    continue;
						}
					    } elseif ($ast=='2FAX') {
						$answeredtime = $this -> call_fax($agi, 2);
						if ($this->set_inuse_username)
							$this -> callingcard_acct_start_inuse($agi,0);
						$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
					    }
					    // THEN STATUS IS ANSWER
//					    $this -> destination_start_inuse($agi, $inst_listdestination[1], false);
					    break 2;
					}
					$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
				}
		break;
		}// END WHILE(true)
			} // END IF AUTHENTICATE
		if ($dialstatus == "ANSWER" || $dialstatus == "CANCEL" || $agi -> channel_status('',true) == AST_STATE_DOWN) break;
		}// END FOR

		if (!is_null($didvoicebox))
			$this->voicebox = $didvoicebox;
		if ($this->voicemail && !is_null($this->voicebox) && $agi -> channel_status('',true) != AST_STATE_DOWN) {
			if ($dialstatus =="CHANUNAVAIL" || $dialstatus == "NOANSWER" || $dialstatus == "CONGESTION") {
				$this->voicebox .= "|su";
			} elseif ($dialstatus =="BUSY") {
				$this->voicebox .= "|sb";
			} else {
				if ($this -> G_startime > 0 || $answeredtime > 0) {
					$this -> bill_did_aleg ($agi, $inst_listdestination, $answeredtime);
				}
				return;
			}
			// The following section will send the caller to VoiceMail with the unavailable priority.\
			if ($this -> G_startime == 0)
				$this -> G_startime = time();
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[STATUS] CHANNEL ($dialstatus) - GOTO VOICEMAIL ($this->voicebox)");
			$agi-> exec('VoiceMail', $this -> format_parameters ($this->voicebox));
		}
		if ($this -> G_startime > 0 || $answeredtime > 0) {
			$this -> bill_did_aleg ($agi, $inst_listdestination, $answeredtime);
		}
	}

	
    public function call_2did ($agi, &$RateEngine, $listdestination, $alegfree = false)
    {
    	$card_number = $this -> username; // username of the caller
        $nbused = $this -> nbused;
	$res = $doibill = 0;
        $connection_charge = $listdestination[0][8];
        $selling_rate = $this->didsellrate = $listdestination[0][9];

        if ($connection_charge == 0 && $selling_rate == 0) {
		$call_did_free = true;
		$this -> debug( INFO, $agi, __FILE__, __LINE__, "[A2Billing] DID call free ");
	} else {
		$call_did_free = false;
		$this -> debug( INFO, $agi, __FILE__, __LINE__, "[A2Billing] DID call not free: (connection charge:".$connection_charge."|selling_rate:".$selling_rate );
	}
	
	if (($listdestination[0][2]==0) || ($listdestination[0][2]==2)) {
		$doibill = 1;
	}
	$credit = $this -> credit;
        if (!$call_did_free) {
		if ($credit < $this->agiconfig['min_credit_2call']) {
			$time2call = 0;
		} else {
			$credit -= abs($connection_charge);
			if ($credit>0 && $selling_rate!=0 ) {
				$time2call = intval($credit / abs($selling_rate))*60;
			} else {
				$time2call =  $this->agiconfig['max_call_call_2_did'];
			}
		}
        } else {
            $time2call = $this->agiconfig['max_call_call_2_did'];
        }
        $this->timeout = $time2call;
	$accountcode = $this->accountcode;
	$username = $this->username;
	$useralias = $this->useralias;
	$set_inuse_username = $this->set_inuse_username;
	$my_id_card = $this->id_card;
	$didvoicebox = NULL;
	$onetry = true;
	$callcount = 0;
	$keytotal = count($listdestination);

	foreach ($listdestination as $inst_listdestination) {
	    $key = false;
	    $callcount++;
	    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[A2Billing] DID call friend: FOLLOWME=$callcount (cardnumber:".$inst_listdestination[6]."|destination:".$inst_listdestination[4]."|tariff:".$inst_listdestination[3].")\n");
	    $this->agiconfig['cid_enable']			= 0;
	    $initialdestination 				= $inst_listdestination[4];
	    $isvoip				 = $this->is_voip($inst_listdestination[4]);
	    $this->accountcode = $this->username=$new_username	= $inst_listdestination[6];
	    $this->tariff 					= $inst_listdestination[3];
	    $this->destination					= $inst_listdestination[10];
	    $this->useralias					= $inst_listdestination[7];
	    $this->id_card					= $inst_listdestination[28];
	    $this->time_out					= $inst_listdestination[30];
	    $this->id_did					= $inst_listdestination[0];
	    $this->margin					= $inst_listdestination[31];
	    $this->id_diller					= $inst_listdestination[32];
	    $didvoicebox 				= is_null($inst_listdestination[33]) ? NULL : $inst_listdestination[33]."@".$this->username;
	    $file    = preg_replace('/\.[^\.\/]+$/', '', basename($inst_listdestination[29]));
	    $this->margintotal					= $this->margin_calculate();
	    $this->speech2mail					= $inst_listdestination[43];
	    $this->send_text					= $inst_listdestination[44];
	    $this->send_sound					= $inst_listdestination[45];
	    $this->calleesound					= ($inst_listdestination[46]) ? $this->accountcode."/".$inst_listdestination[46] : NULL;

	    // CHECK IF DESTINATION IS SET
	    if (strlen($inst_listdestination[4])==0 || $this->CallerID==$this->destination)
            	continue;
	    if ($inst_listdestination[36]) {
		if ($this -> G_startime == 0)
		    $this -> G_startime = time();
		$agi -> answer();
	    } elseif ($inst_listdestination[29]) {
		$this -> let_stream_listening($agi, true);
	    }
	    if ($this -> destination_start_inuse($agi, $inst_listdestination[1], 1))
            	continue;
	    if ($file) {
		$diraudio = $this->config['webui']['dir_store_audio']."/".$this->accountcode."/".$file;
		if ($this -> resolve($diraudio)!==false) $file = $this->accountcode."/".$file;
		sleep(1);
		$agi -> evaluate("STREAM FILE $file \"#\" 0");
	    }
	    while (true) {
		if (!$this -> ivr($agi,$inst_listdestination[4],$initialdestination,$isvoip)) {
			$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
			$RateEngine->dialstatus = $dialstatus = "CANCEL";
			$RateEngine->rate_engine_updatesystem($this, $agi, $this->destination, $doibill, 2);
			break 2;
		}
            // IF call on did is not free calculate time to call

            // IF VOIP CALL
/*
if ($isvoip)
    $this -> debug( ERROR, $agi, __FILE__, __LINE__, "VOIP=".$inst_listdestination[4]);
else
    $this -> debug( ERROR, $agi, __FILE__, __LINE__, "NOT VOIP=".$inst_listdestination[4]);
*/
            if ($isvoip) {
                // RUN MIXMONITOR TO RECORD CALL
		$monfile = false;
		if ((($this->send_sound || $this->send_text) && $this->speech2mail) || $this->monitor == 1 || $this->agiconfig['record_call'] == 1) {
			$this->dl_short = MONITOR_PATH . "/" . $this->username . "/" . date('Y') . "/" . date('n') . "/" . date('j') . "/";
			$monfile = $dl_short = $this->dl_short . $this->uniqueid . ".";
			if ($this->send_sound || $this -> send_text) {
			    $format_file = 'wav';
			    $monfile .= $format_file;
			} else {
			    $format_file = $this->agiconfig['monitor_formatfile'];
			    $monfile .= ($format_file == 'wav49') ? 'WAV' : $format_file;
			}
			if ($format_file == 'wav' && $this -> send_text) {
			    $command_mixmonitor = "Monitor wav,{$dl_short}wav,b";
			    $stopmon = "StopMonitor";
			} else {
			    $command_mixmonitor = $this -> format_parameters ("MixMonitor {$dl_short}{$format_file}|b");
			    $stopmon = "StopMixMonitor";
			}
			$myres = $agi->exec($command_mixmonitor);
			$this -> debug( INFO, $agi, __FILE__, __LINE__, $command_mixmonitor);
		}
				
		$max_long = 36000000; //Maximum 10 hours
		$dialstr = $inst_listdestination[4];
		if ($call_did_free && $this->extext) {
		    $this -> fct_say_time_2_call($agi,$time2call,0);
		    $dialparams = $this->agiconfig['dialcommand_param_call_2did'];
		    if (substr_count(strtoupper($inst_listdestination[4]),"LOCAL/") > 0) {
			$dialparams.= "m";
		}
                } elseif ($this->extext) {
		    $this -> debug( INFO, $agi, __FILE__, __LINE__, "TIME TO CALL : $time2call");
		    $this -> fct_say_time_2_call($agi,$time2call,$selling_rate);
		    $dialparams = $this->agiconfig['dialcommand_param'];
                }
		if (stripos($dialstr,"QUEUE ") !== 0) {
		    $dialparams = str_replace("%timeout%", min($time2call * 1000, $max_long), $dialparams);
		    $dialstr .= str_replace("%timeoutsec%", min($time2call, $max_long), $dialparams);
		} else {
		    $que = explode(",",$dialstr);
		    if (!isset($que[1]) || stripos($que[1],"r") === false) {
			$this -> let_stream_listening($agi);
		    } else {
			$this -> let_stream_listening($agi,false,true);
		    }
		}
		if ($call_did_free) $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[A2Billing] DID call friend: Dialing '$dialstr' Friend.\n");

		if ($agi -> channel_status('',true) == AST_STATE_DOWN) {
			$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
			break 2;
		}
		if (isset($this->src_exten)) {
			$QUERY = "SELECT 1 FROM cc_card_concat WHERE concat_id = '$this->caller_concat_id' AND concat_card_id = {$this->id_card} LIMIT 1";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			$cid =  (is_array($result) || $this->id_card == $this->card_caller) ? $this->src_exten : $this->src_peername;
			$agi -> set_callerid(translit('"' . trim(preg_replace('/(?<!\d)(' . $cid . ')(?!\d)/', '', $this->CallerID . ' ' . $agi->get_variable('CALLERID(name)', true)), "\x00..\x2F") . '"<' . $cid . '>'));
		} else {
			$CID_handover = $this -> apply_cid_rules ($this->CallerID, $inst_listdestination[34], $inst_listdestination[35]);
			if ($CID_handover != $this->CallerID)
				$agi -> set_variable('CALLERID(num)', $CID_handover);
		}
                $this -> debug( INFO, $agi, __FILE__, __LINE__, "DIAL $dialstr");
                $myres = $this -> run_dial($agi, $dialstr);

		$this -> DbReConnect($agi);

		$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
		$answeredtime			= $agi->get_variable("ANSWEREDTIME", true);
		if ($answeredtime == "")
				  $answeredtime = $agi->get_variable("CDR(billsec)",true);
		if (stripos($dialstr,'QUEUE ') === 0) {
			if ($answeredtime>1000000) {
				$answeredtime	= time() - $answeredtime;
				$dialstatus	= 'ANSWER';
			} else {
				$answeredtime	= 0;
				$dialstatus	= $this -> get_dialstatus_from_queuestatus($agi);
			}
		} else		$dialstatus	= $agi->get_variable("DIALSTATUS", true);

		if (isset($stopmon)) {
			$myres = $agi->exec($stopmon);
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "EXEC $stopmon (".$this->uniqueid.")");
			if (file_exists($monfile) && (filesize($monfile) < 100 || $answeredtime<2)) {
				unlink($monfile);
				$monfile = false;
			}
			if (file_exists($monfile."-in.wav") && (filesize($monfile."-in.wav") < 100 || $answeredtime<2)) {
				unlink($monfile."-in.wav");
				$monfile = false;
			}
			if (file_exists($monfile."-out.wav") && (filesize($monfile."-out.wav") < 100 || $answeredtime<2)) {
				unlink($monfile."-out.wav");
				$monfile = false;
			}
                }

                $this -> debug( INFO, $agi, __FILE__, __LINE__, "[".$inst_listdestination[4]." Friend][followme=$callcount]:[ANSWEREDTIME=".$answeredtime."-DIALSTATUS=".$dialstatus."]");

                //# Ooh, something actually happend!
                if ($dialstatus == "BUSY") {
					$answeredtime = 0;
					if ($keytotal > $callcount) {
						continue 2;
					} else {
						$this -> let_stream_listening($agi);
						if ($this->agiconfig['busy_timeout'] > 0) {
							$agi->exec("Playtones busy");
							sleep($this->agiconfig['busy_timeout']);
						} else	$agi-> stream_file('prepaid-isbusy', '#');
					}
                } elseif ($dialstatus == "NOANSWER") {
					$answeredtime = 0;
					if ($keytotal > $callcount) {
						continue 2;
					} else {
						$this -> let_stream_listening($agi);
						$agi-> stream_file('prepaid-noanswer', '#');
					}
                } elseif ($dialstatus == "CANCEL") {
					$answeredtime = 0;
                } elseif ($dialstatus == "ANSWER") {
					$alegfree = false;
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__,"[A2Billing] DID call friend: dialstatus : $dialstatus, answered time is ".$answeredtime." \n");
                } elseif (($dialstatus == "CHANUNAVAIL") || ($dialstatus == "CONGESTION")) {
					$answeredtime = 0;
					if ($keytotal > $callcount) continue 2;
                } else {
		    $this -> let_stream_listening($agi);
                    $agi-> stream_file('prepaid-callfollowme', '#');
                    if ($keytotal > $callcount) continue 2;
                }

                    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - LOG CC_CALL: FOLLOWME=$callcount - (answeredtime=$answeredtime :: dialstatus=$dialstatus :: call_did_free=$call_did_free)]");

                    if (strlen($this -> dialstatus_rev_list[$dialstatus])>0) {
			$terminatecauseid = $this -> dialstatus_rev_list[$dialstatus];
                    } else {
			$terminatecauseid = 0;
                    }
		    if ($answeredtime == 0) {
			$inst_listdestination[10] = $this -> realdestination;
		    } else {
			$bridgepeer = $agi -> get_variable('CDR(dstchannel)',true);
			preg_match("/([^\/]+)(?=-[^-]*$)/",$bridgepeer,$bridgepeer);
			$QUERY = "SELECT regexten FROM cc_sip_buddies WHERE name = '{$bridgepeer[0]}' LIMIT 1";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			if (is_array($result) && $result[0][0] != "") {
				$uppeer = $bridgepeer[0];
			} else {
				$uppeer = $agi->get_variable('QUEUEDNID', true);
			}
			if ($uppeer) {
				$inst_listdestination[10] = $uppeer;
			}
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "Destination: " . $inst_listdestination[10]);
		    }
		    if (!isset($this->card_caller)) {
			$this->card_caller = $my_id_card;
		    }

		    if (stripos($inst_listdestination[4],"@a3billing") === false) {
			$QUERY = "SELECT regexten FROM cc_sip_buddies WHERE name = '{$this->src_peername}' AND id_cc_card = $this->card_caller AND regexten IS NOT NULL LIMIT 1";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			$src_exten = is_array($result) ? "'".$result[0][0]."'" : 'NULL';

			$QUERY = "SELECT regexten, id_cc_card FROM cc_sip_buddies WHERE name = '{$inst_listdestination[10]}' LIMIT 1";
			$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
			if (is_array($result)) {
			    $this->calledexten = $result[0][0] != "" ? "'".$result[0][0]."'" : 'NULL';
			    $card_called = "'".$result[0][1]."'";
			} else {
			    $this->calledexten = 'NULL';
			    $card_called = "'0'";
			}
			if ($this->src_peername >= pow(10,$this->config['global']['len_aliasnumber'])) $this->src_peername = 'NULL';
                	// A-LEG below to the owner of the DID
                	if ($call_did_free || $answeredtime == 0) {
                    	    //CALL2DID CDR is free
	                    /* CDR A-LEG OF DID CALL */
	                    $QUERY = "INSERT INTO cc_call (uniqueid, sessionid, card_id, card_caller, card_called, nasipaddress, starttime, sessiontime, calledstation, ".
	                            " terminatecauseid, stoptime, sessionbill, id_tariffgroup, id_tariffplan, id_ratecard, id_trunk, src, sipiax, id_did, src_peername, src_exten, calledexten, dnid, callbackid) VALUES ".
	                            "('".$this->uniqueid."', '".$this->channel."',  '".$this->id_card."',  '".$this->card_caller."', $card_called, '".$this->hostname."',";
	                    $QUERY .= " CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND ";
	                    $QUERY .= ", '$answeredtime', '".$inst_listdestination[10]."', '$terminatecauseid', now(), '0', '0', '0', '0', '0', '$this->CallerID', '3', '$this->id_did', $this->src_peername, $src_exten, $this->calledexten, '$this->destination', '$this->callback_id')";

	                    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
	                    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - LOG CC_CALL: SQL: $QUERY]:[result:$result]");
			} else {
                    	    //CALL2DID CDR is not free
                    	    $cost = a2b_round(($answeredtime/60) * abs($selling_rate) + abs($connection_charge));

                    	    /* CDR A-LEG OF DID CALL */
                    	    $QUERY = "INSERT INTO cc_call (uniqueid, sessionid, card_id, card_caller, card_called, nasipaddress, starttime, sessiontime, calledstation, ".
                    	            " terminatecauseid, stoptime, sessionbill, id_tariffgroup, id_tariffplan, id_ratecard, id_trunk, src, sipiax, id_did, src_peername, src_exten, calledexten, dnid, callbackid) VALUES ".
                    	            "('".$this->uniqueid."', '".$this->channel."',  '".$my_id_card."', '".$this->card_caller."', $card_called, '".$this->hostname."',";
                    	    $QUERY .= " CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND ";
                    	    $QUERY .= ", '$answeredtime', '". $listdestination[0][10]."', '$terminatecauseid', now(), '$cost', '0', '0', '0', '0', '$this->CallerID', '3', '$this->id_did', $this->src_peername, $src_exten, $this->calledexten, '$this->destination', '$this->callback_id')";

                    	    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
                    	    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - LOG CC_CALL: SQL: $QUERY]:[result:$result]");
			}
			$this -> send_talk($agi, $this -> speech2mail, $monfile, $answeredtime, $this -> current_language);
			$monfile = false;
		    }
		    monitor_recognize($this);
            // ELSEIF NOT VOIP CALL
            } else {


                $this->agiconfig['use_dnid'] = 1;
                $this->agiconfig['say_timetocall'] = 0;
                $this->myprefix = '';
                $this->dnid = $this->destination;
                $this->extension = $this->destination = $inst_listdestination[4];
                $ast = $this -> callingcard_ivr_authorize($agi, $RateEngine, 0, -2);
//                if ($ast==1 || $ast=='2FAX') {
                if ($ast==1) {
                    // PERFORM THE CALL
		    if ($agi -> channel_status('',true) != AST_STATE_DOWN) {
			$this->agiconfig['dialcommand_param'] = $this->agiconfig['dialcommand_param_call_2did'];
			$result_callperf = $RateEngine->rate_engine_performcall ($agi, $this -> destination, $this, 44+$keytotal-$callcount); // 44 = For not to play announce seconds and call cost
			if (!$result_callperf && $keytotal == $callcount && is_null($didvoicebox) && is_null($this->voicebox)) {
	                    $prompt="prepaid-callfollowme";
	                    $agi-> stream_file($prompt, '#');
			}
		    } else	$RateEngine->dialstatus = 'CANCEL';

                    $dialstatus = $RateEngine->dialstatus;
                    $answeredtime = $RateEngine->answeredtime;
                    if ((($dialstatus == "NOANSWER")    || ($dialstatus == "BUSY") ||
                         ($dialstatus == "CHANUNAVAIL") || ($dialstatus == "CONGESTION")) && $keytotal > $callcount) {
				if ($key !== false /*&& is_null($this->voicebox) && is_null($didvoicebox)*/) {
				    if ($dialstatus  == "BUSY") {
					$this -> let_stream_listening($agi);
					$agi-> stream_file('prepaid-isbusy', '#');
				    } elseif ($dialstatus == "NOANSWER") {
					$this -> let_stream_listening($agi);
					$agi-> stream_file('prepaid-noanswer', '#');
				    } elseif ($dialstatus == "CHANUNAVAIL" || $dialstatus == "CONGESTION") {
					$this -> let_stream_listening($agi);
					$agi-> stream_file('prepaid-dest-unreachable', '#');
				    }
				    continue;
				}
				$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
				continue 2;
		    }
		    if ($dialstatus != "ANSWER") $this -> destination = $this -> realdestination;
		    if ($key === false || $dialstatus == "ANSWER" || $dialstatus == "CANCEL")
			$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
					
                    // INSERT CDR  & UPDATE SYSTEM
                    $RateEngine->rate_engine_updatesystem($this, $agi, $this-> destination, $doibill, 2);
                    if (!$result_callperf && $keytotal > $callcount) {
			if ($key !== false && $dialstatus != "ANSWER" /*&& is_null($this->voicebox) && is_null($didvoicebox)*/) {
			    if ($dialstatus  == "BUSY") {
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-isbusy', '#');
			    } elseif ($dialstatus == "NOANSWER") {
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-noanswer', '#');
			    } elseif ($dialstatus == "CHANUNAVAIL" || $dialstatus == "CONGESTION") {
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-dest-unreachable', '#');
			    }
			    continue;
			}
			if ($dialstatus != "CANCEL")
			    $this -> destination_start_inuse($agi, $inst_listdestination[1], false);
			continue 2;
                    }
		    if ($key !== false && $dialstatus != "ANSWER" /*&& is_null($this->voicebox) && is_null($didvoicebox)*/) {
			    if ($dialstatus  == "BUSY") {
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-isbusy', '#');
			    } elseif ($dialstatus == "NOANSWER") {
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-noanswer', '#');
			    } elseif ($dialstatus == "CHANUNAVAIL" || $dialstatus == "CONGESTION") {
				$this -> let_stream_listening($agi);
				$agi-> stream_file('prepaid-dest-unreachable', '#');
			    }
			    continue;
		    }
		} elseif ($ast=='2FAX') {
			$answeredtime = $this -> call_fax($agi, 2);
			if ($this->set_inuse_username) {
				$this -> callingcard_acct_start_inuse($agi,0);
			}
			$this -> destination_start_inuse($agi, $inst_listdestination[1], false);
//		  }
		} else {
		    $this -> destination_start_inuse($agi, $inst_listdestination[1], false);
		}
	    }
	    break;
	    }// END WHILE(true)
	    if ($dialstatus == "ANSWER" || $dialstatus == "CANCEL" || $agi -> channel_status('',true) == AST_STATE_DOWN) break;
	}// END FOREACH

                $this->username = $username;

		if (!is_null($didvoicebox))
			$this->voicebox = $didvoicebox;
		if ($this->voicemail && !is_null($this->voicebox) && $agi -> channel_status('',true) != AST_STATE_DOWN) {
			if ($dialstatus =="CHANUNAVAIL" || $dialstatus == "NOANSWER" || $dialstatus == "CONGESTION") {
				$this->voicebox .= "|su";
			} elseif ($dialstatus =="BUSY") {
				$this->voicebox .= "|sb";
			} else {
				if (($this -> G_startime > 0 || $answeredtime > 0) && !$alegfree) {
					$this -> bill_did_aleg ($agi, $inst_listdestination, $answeredtime);
				}
				return;
			}
			// The following section will send the caller to VoiceMail with the unavailable priority.\
			if ($this -> G_startime == 0)
				$this -> G_startime = time();
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[STATUS] CHANNEL ($dialstatus) - GOTO VOICEMAIL ($this->voicebox)");
			$agi-> exec('VoiceMail', $this -> format_parameters ($this->voicebox));
		}

		if (($this -> G_startime > 0 || $answeredtime > 0) && !$alegfree) {
			$this -> bill_did_aleg ($agi, $inst_listdestination, $answeredtime);
		}
		$this->accountcode		= $accountcode;
		$this->username 		= $username;
		$this->useralias		= $useralias;
		$this->set_inuse_username	= $set_inuse_username;
		$this->id_card			= $my_id_card;
    }

	public function send_talk(&$agi,$mailaddr,$audioFile,$answeredtime,$languageCode='not_set',$calldestination=NULL)
	{
		if ($answeredtime<2 || $audioFile=="" || $mailaddr=="") return false;
		if ($calldestination) {
		    $QUERY = "SELECT destination FROM cc_prefix WHERE prefix LIKE '$calldestination'";
		    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		    $calldestination = is_array($result) ? $result[0][0] : NULL;
		}
		if ($languageCode=='not_set')
			$languageCode = $this -> current_language;
		switch($languageCode) {
		    case 'de':	if (stripos($calldestination,'SWITZERLAND')===0)	$languageCode = 'de-CH';
				else							$languageCode = 'de-DE'; break;
		    case 'en':	if (stripos($calldestination,'UNITED KINGDOM')===0)	$languageCode = 'en-GB';
				else if (stripos($calldestination,'CANADA')===0)	$languageCode = 'en-CA';
				else if (stripos($calldestination,'AUSTRALIA')===0)	$languageCode = 'en-AU';
				else if (stripos($calldestination,'INDIA')===0) 	$languageCode = 'en-IN';
				else if (stripos($calldestination,'NEW ZELAND')===0)	$languageCode = 'en-NZ';
				else							$languageCode = 'en-US'; break;
		    case 'ru': $alternateLangCode = array('uk-UA');			$languageCode = 'ru-RU'; break;
		    case 'uk': $alternateLangCode = array('ru-RU');			$languageCode = 'uk-UA'; break;
		    case 'ua': $alternateLangCode = array('ru-RU');			$languageCode = 'uk-UA'; break;
		    case 'ja':								$languageCode = 'ja-JP'; break;
		    case 'iw':								$languageCode = 'iw-IL'; break;
		}
		$src = ($this->src != "NULL")?$this->src:$this->CallerID;
		$save_audio = ($this -> monitor == 0 && $this -> agiconfig['record_call'] == 0) ? 0 : 1 ;

		$QUERY = "INSERT INTO `cc_recognize_spool` (`id_cc_card`,`mailaddr`,`audiofile`,`answeredtime`,`languagecode`,`send_sound`,`send_text`,`save_sound`,`src`,`destination`) VALUES ".
                	"('".$this->id_card."','".$mailaddr."','".$audioFile."','".$answeredtime."','".$languageCode."','".$this->send_sound."','".$this->send_text."','".$save_audio."','".$src."','".$this->destination."')";
		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
//		$this -> debug( INFO, $agi, __FILE__, __LINE__, "[Recognize spool inserted]");
	}

	public function google_recognize($idcard,$send_sound,$send_text,$save_sound,$src,$dst,$mailaddr,$audioFile,$answeredtime,$languageCode)
	{
		$retcode = 1;
		if (strlen($languageCode)<5 || $languageCode=='not_set') {
		    $languageCode = 'en-US';
		    $alternateLangCode = array('ru-RU','uk-UA');
		}
		$dl_path_to = $audioFile;
		$audioFileIn  = $audioFile."-in.wav";
		$audioFileOut = $audioFile."-out.wav";
		$audioFileMix = $audioFile."-mix.wav";
		if (is_file($audioFile) || is_file($audioFileOut) || is_file($audioFileIn) || is_file($audioFileMix)) {
		    if (is_file($audioFileOut) || is_file($audioFileIn)) {
			$sox = "/usr/bin/sox -M ".$audioFileOut." ".$audioFileIn." ".$audioFileMix;
			exec($sox);
			try {
			    unlink($audioFileIn);
			} catch (Exception $e) {
			    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Erasing file $audioFileIn error]: ".$e->getMessage());
			}
			try {
			    unlink($audioFileOut);
			} catch (Exception $e) {
			    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Erasing file $audioFileOut error]: ".$e->getMessage());
			}
		    } else if (!is_file($audioFileMix)) $audioFileMix = $audioFile;
		    $path_parts = pathinfo($audioFileMix);
		    $objectName = $path_parts['basename'];
		    $path_parts = pathinfo($audioFile);
		    if (is_file($audioFileMix) && $path_parts['extension']=='wav') {
			$dl_path_to = $path_parts['dirname']."/".$path_parts['filename'].".mp3";
			$lame = "/usr/bin/lame -m m -h -b 16 ".$audioFileMix." ".$dl_path_to;
			exec($lame." >/dev/null 2>&1");
		    }
		
		// CHECK IF THE EMAIL ADDRESS IS CORRECT
		    if ($send_sound || $send_text)
		    if ($mailaddr && preg_match("/^[_A-Za-z0-9-]+(\\.[_A-Za-z0-9-]+)*@[A-Za-z0-9]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$/i", $mailaddr)) {

			switch(substr($languageCode, 0, 2)) {
				case 'de':	$speaker = "Sprecher";	$duration = "Dauer";		break;
				case 'ru':	$speaker = "Сторона";	$duration = "Продолжительность";break;
				default  :	$speaker = "Speaker";	$duration = "Duration";
			}
			$transcript = $duration . ": " . sprintf ( "%02d", intval ( $answeredtime / 60 ) ) . ":" . sprintf ( "%02d", intval ( $answeredtime % 60 ) );

			$keyFilePath = $this->config['global']['google_cloud_credential'];
			if ($send_text && is_file($keyFilePath)) {
//			    $projectId = '';

			    putenv("GOOGLE_APPLICATION_CREDENTIALS=".$keyFilePath);

			    // The audio file's encoding, sample rate and language
			    // http://googleapis.github.io/google-cloud-php/#/docs/google-cloud/v0.141.0/speech/v1p1beta1/recognitionconfig
			    $config = new RecognitionConfig([
				'encoding' => AudioEncoding::LINEAR16,
				'sample_rate_hertz' => 8000,
				'language_code' => $languageCode,
				'audio_channel_count' => 2,
				'enable_separate_recognition_per_channel' => true,
//				'diarization_speaker_count' => 2,
//				'enable_speaker_diarization' => true,
				'use_enhanced' => true,
//				'model'=> 'phone_call',
				'enable_automatic_punctuation' => true
			    ]);
			    if (array_search($languageCode, array('en-GB','en-US','ru-RU','es-US')) !== false)	$config->setModel('phone_call');
//			    if (isset($alternateLangCode)) $config->setAlternativeLanguageCodes($alternateLangCode);

			    if (isset($alternateLangCode))
				$languageCode .= ", " . implode(", ", $alternateLangCode);
			    $transcript = $languageCode . PHP_EOL . $transcript;

			    if ($answeredtime > 1) {
				    // Instantiates a client
				    $client = new SpeechClient();
				    if ($answeredtime < 60) {
					$content = file_get_contents($audioFileMix);
					$audio = (new RecognitionAudio())
					    ->setContent($content);
					try {
					    $response = $client->recognize($config, $audio);
					} catch (Exception $e) {
					    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Recognize Speech-to-text error]: ".var_export($e->getMessage(),true));
					    $retcode = 2;
					}
					$speakertag = 100;
					foreach ($response->getResults() as $result) {
					    $alternatives = $result->getAlternatives();
					    $mostLikely   = $alternatives[0];
					    if ($speakertag != $result->getChannelTag()) {
						$speakertag  = $result->getChannelTag();
						if ($speakertag>0) $transcript .= PHP_EOL."<u>".$speaker.$speakertag.":</u> ";
					    }
					    if ($speakertag>0) $transcript .= $mostLikely->getTranscript();
					}
					if (strpos($transcript,$speaker) === false) foreach ($response->getResults() as $result) {
					    $alternatives = $result->getAlternatives();
					    $mostLikely   = $alternatives[0];
					    $transcript .= PHP_EOL.$mostLikely->getTranscript();
					}
				    } else {
					$storage = new StorageClient(/*[
						'keyFile' => json_decode(file_get_contents($keyFilePath), true),
						'keyFilePath' => $keyFilePath,
						'projectId' => $projectId
					]*/);

					$bucketName = strtolower($this->config['global']['google_storage_bucketname']);
					$bucketLocation = $this->config['global']['bucket_location'];
					$bucket = $storage->bucket($bucketName);
					if (!$bucket->exists()) {
					    $bucket = $storage->createBucket($bucketName, [ 'location' => $bucketLocation ]);
					}

					// Upload a file to the bucket.
					$object = $bucket->upload(
					    fopen($audioFileMix, 'r'), ['name' => $objectName]
					);

					// set string as audio content
					$audio = (new RecognitionAudio())
					    ->setUri("gs://".$bucketName."/".$objectName);

					try {
					    // create the asyncronous recognize operation
					    $operation = $client->longRunningRecognize($config, $audio);
					    $operation->pollUntilComplete();
					} catch (Exception $e) {
					    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [LongRunningRecognize Speech-to-text error]: ".var_export($e->getMessage(),true));
					    $retcode = 2;
					}

					if ($operation->operationSucceeded()) {
					    // Detects speech in the audio file
					    $response = $operation->getResult();

					    // Save most likely transcription
					    $speakertag = 100;
					    foreach ($response->getResults() as $result) {
						$alternatives = $result->getAlternatives();
						$mostLikely   = $alternatives[0];
						if ($speakertag != $result->getChannelTag()) {
						    $speakertag  = $result->getChannelTag();
						    if ($speakertag>0) $transcript .= PHP_EOL."<u>".$speaker.$speakertag.":</u> ";
						}
						if ($speakertag>0) $transcript .= $mostLikely->getTranscript();
					    }
					    if (strpos($transcript,$speaker) === false) foreach ($response->getResults() as $result) {
						$alternatives = $result->getAlternatives();
						$mostLikely   = $alternatives[0];
						$transcript .= PHP_EOL.$mostLikely->getTranscript();
					    }
					} else {
					    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [LongRunningRecognize Speech-to-text error]: ".$operation->getError());
					    $retcode = 2;
					}
					$object->delete();
				    }
				    $client->close();
			    }
			}
			include_once (dirname(__FILE__)."/Class.Mail.php");

			try {
				$this->DBHandle->Execute("SET NAMES 'UTF8'");
				$mail = new Mail(Mail::$TYPE_SPEECH_SUCCESS,$idcard,null,null,null,$this->DBHandle);
				if ($send_sound && file_exists($dl_path_to) && is_file($dl_path_to)) {
				    $mail->AddAttachment($dl_path_to);
				}
				$mail->replaceInEmail(Mail::$SPEECH_CID_NUMBER,$src);
				$mail->replaceInEmail(Mail::$SPEECH_DEST_EXTEN,$dst);
				$mail->replaceInEmail(Mail::$SPEECH_DATETIME,date('d F Y - H:i:s'));
				$mail->replaceInEmail(Mail::$SPEECH_TEXT,$transcript);
				$mail->send($mailaddr);
			} catch (A2bMailException $e) {
				write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Speech2Text for cardID $idcard ($src -> $dst)]: ERROR NO EMAIL TEMPLATE FOUND -> ".$e->getMessage());
				$retcode+=4;
			}
		    } else {
			write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Speech2Text for cardID $idcard ($src -> $dst)]: no valid email !!!");
			$retcode+=8;
		    }
		}
		if ($audioFile != $dl_path_to && is_file($audioFile))
		try {
		    unlink($audioFile);
		} catch (Exception $e) {
		    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Erasing file $audioFile error]: ".$e->getMessage());
		    $retcode+=16;
		}
		if ($audioFileMix != $dl_path_to && is_file($audioFileMix))
		try {
		    unlink($audioFileMix);
		} catch (Exception $e) {
		    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Erasing file $audioFileMix error]: ".$e->getMessage());
		    $retcode+=32;
		}
		if ($save_sound == 0 && is_file($dl_path_to))
		try {
		    unlink($dl_path_to);
		} catch (Exception $e) {
		    write_log(LOGFILE_API_CALLBACK, basename(__FILE__) . ' line:' . __LINE__ . " [Erasing file $dl_path_to error]: ".$e->getMessage());
		    $retcode+=64;
		}

		return $retcode;
	}
	/**
	 *	Function call_fax
	 *
     *  @return answered time
	**/
	public function call_fax (&$agi, $didcall=0)
	{
		if ($agi -> channel_status('',true) == AST_STATE_DOWN) return 0;
		$calleridname = $agi->get_variable('CALLERID(name)', true);
		$uniqueid = microtime(TRUE);
		$tempfaxfile = FAX_PATH . "/tmp/" . $uniqueid . ".tif";

//		if ($this->localheaderinfo) $agi->set_variable('LOCALHEADERINFO',$this->localheaderinfo);
		if ($this->localstationid)  $agi->set_variable('LOCALSTATIONID',$this->localstationid);
		$myres = $agi->exec('ReceiveFAX', $tempfaxfile);

		$answeredtime	= ceil (microtime(TRUE) - $uniqueid);
//		$faxstatus	= ($agi->get_variable("FAXSTATUS", true) == 'FAILED')?0:1;
		$remotefaxid	= $agi->get_variable("REMOTESTATIONID", true);
		$faxpages	= $agi->get_variable("FAXPAGES", true);
		$faxbitrate	= $agi->get_variable("FAXBITRATE", true);
		$faxresolution	= $agi->get_variable("FAXRESOLUTION", true);
		$faxerror	= $agi->get_variable("FAXERROR", true);
//		if (!isset($this->CallerIDext)) $this->CallerIDext = $this->src;
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "================================= CallerIDext: {$this->CallerIDext}");
		$this->CallerIDext = ($this->CallerIDext)?$this->CallerIDext:$this->src;
		$this->CallerIDext = ($this->CallerIDext == "NULL")?$this->CallerIDext:"'{$this->CallerIDext}'";
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "================================= CallerIDext: {$this->CallerIDext}");
/**
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "ANSWEREDTIME: ".$answeredtime);
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "FAXSTATUS: ".$faxstatus);
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "REMOTESTATIONID: ".$remotefaxid);
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "FAXPAGES: ".$faxpages);
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "FAXBITRATE: ".$faxbitrate);
$this -> debug( ERROR, $agi, __FILE__, __LINE__, "FAXRESOLUTION: ".$faxresolution);


		if ((($this->send_sound || $this->send_text) && $this->speech2mail) || $this->monitor == 1 || $this -> agiconfig['record_call'] == 1) {
			$myres = $agi->exec($this -> format_parameters ("StopMixMonitor"));
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "EXEC StopMixMonitor (".$uniqueid.")");
		}
**/
		if (file_exists($tempfaxfile)) {
			$faxstatus = 1;
			if ($faxpages == 0) $faxpages = 1;
		} else	$faxstatus = 0;
		if ($this->src_peername >= pow(10,$this->config['global']['len_aliasnumber']))
			$this->src_peername = 'NULL';

		$QUERY  = "INSERT INTO cc_call (uniqueid, sessionid, card_id, card_caller, card_called, nasipaddress, starttime, sessiontime, calledstation, destination, terminatecauseid, stoptime, sessionbill, ".
				"id_tariffgroup, id_tariffplan, id_ratecard, id_trunk, src, sipiax, src_peername, src_exten, calledexten, dnid, faxstatus, remotefaxid, faxpages, faxbitrate, faxresolution, callbackid) VALUES ";
		$QUERY .= "('$uniqueid', '$this->channel', '$this->id_card', '$this->id_card', '$this->faxidcard', '$this->hostname', ";
		$QUERY .= "CURRENT_TIMESTAMP - INTERVAL $answeredtime SECOND, ";
		$QUERY .= "'$answeredtime', '$this->destination', '-2', '1', now(), '0', '0', '0', '0', '0', '$this->CallerID', $didcall, $this->src_peername, $this->CallerIDext, '$this->destination', '$this->dnid', ";
		$QUERY .= "'$faxstatus', '$remotefaxid', '$faxpages', '$faxbitrate', '$faxresolution', '$this->callback_id')";

		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);

		if (file_exists($tempfaxfile)) {
			$patharray = array("/".$this->username, "/".date('Y'), "/".date('n'), "/".date('j'));
			$faxfile = FAX_PATH;
			for ($i = 0; $i < 4; $i++) {
				$faxfile .= $patharray[$i];
				if (!file_exists($faxfile)) mkdir($faxfile);
			}
			$faxfile .= "/" . $uniqueid . ".pdf";
			exec("tiff2pdf -p letter -f -o " . $faxfile . " " . $tempfaxfile);
			unlink($tempfaxfile);
		}

		// CHECK IF THE EMAIL ADDRESS IS CORRECT
		if ($this->fax2mail) {
		    if (preg_match("/^[_A-Za-z0-9-]+(\\.[_A-Za-z0-9-]+)*@[A-Za-z0-9]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$/i", $this->faxemail)) {

			include_once (dirname(__FILE__)."/Class.Mail.php");
			try {
				$this->DBHandle->Execute("SET NAMES 'UTF8'");
				if (isset($faxfile) && file_exists($faxfile)) {
					$mail = new Mail(Mail::$TYPE_FAX_SUCCESS,$this->faxidcard,null,null,null,$this->DBHandle);
					if (is_file($faxfile)) $mail->AddAttachment($faxfile);
				} else {
					$mail = new Mail(Mail::$TYPE_FAX_FAILED,$this->faxidcard,null,null,null,$this->DBHandle);
				}
				if (isset($this->cidextafter)) $this->src = $this->cidextafter;
				$mail->replaceInEmail(Mail::$FAX_CID_NUMBER,($this->src != "NULL")?$this->src:$this->CallerID);
				$mail->replaceInEmail(Mail::$FAX_CID_NAME,($this->src != $calleridname && $this->CallerID != $calleridname)?$calleridname:'');
				$mail->replaceInEmail(Mail::$FAX_COUNT,$faxpages);
				$mail->replaceInEmail(Mail::$FAX_DEST_EXTEN,$this->destination);
				$mail->replaceInEmail(Mail::$FAX_DATETIME,date('d F Y - H:i:s'));
				$mail->replaceInEmail(Mail::$FAX_RESULT,gettext($faxerror));
				$mail->replaceInEmail(Mail::$FAX_FORMAT,"PDF");
				$mail->send($this->faxemail);
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[SENDING FAX EMAIL TO CUSTOMER] ".$this->faxemail);
/**
				if ($this->faxemail != $this->config['global']['admin_email']) {
					$mail->setTitle("COPY FOR ADMIN : ".$mail->getTitle());
					$mail->AddToMessage("\n\nOriginal sent to ".$this->faxemail);
					$this -> debug( INFO, $agi, __FILE__, __LINE__, "[SENDING FAX EMAIL TO ADMIN] ".$this->config['global']['admin_email']);
					$mail->send($this->config['global']['admin_email']);
				}
**/
			} catch (A2bMailException $e) {
				$this -> debug( INFO, $agi, __FILE__, __LINE__, "[Fax for cardID $this->id_card to ext.$this->destination]: ERROR NO EMAIL TEMPLATE FOUND");
			}
		    } else {
			$this -> debug( INFO, $agi, __FILE__, __LINE__, "[Fax for cardID $this->id_card to ext.$this->destination]: no valid email !!!");
		    }
		}
//		$agi -> set_variable('__ONFORWARDCID1', $this->backafter);
//		$agi -> set_variable('__ONFORWARDCIDEXT1', $this->backextafter);
		return $answeredtime;
	}
	
	
	/*
	 * Function to bill the A-Leg on DID Calls
	 */
	public function bill_did_aleg ($agi, $inst_listdestination, $b_leg_answeredtime = 0)
	{
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "========================== channel_status= ".$agi -> channel_status('',true));
//	    while ($agi -> channel_status('',true) != AST_STATE_DOWN) {
//		sleep(1);
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "========================== channel_status= ".$agi -> channel_status('',true));
//	    }
	    $start_time = ($this -> G_startime == 0) ? time()-$b_leg_answeredtime : $this -> G_startime;
	    $stop_time = time();
	    $timeinterval = $inst_listdestination[19];
	    $aleg_real_answeredtime = $stop_time - $start_time;
	    if ($aleg_real_answeredtime == 0) $aleg_real_answeredtime = 1;
	    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[bill_did_aleg]: START TIME peak=".$this -> calculate_time_condition($start_time,$timeinterval, "peak")." ,offpeak=".$this -> calculate_time_condition($start_time,$timeinterval, "offpeak")." ");
	    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[bill_did_aleg]: STOP TIME peak=".$this -> calculate_time_condition($stop_time,$timeinterval, "peak")." ,offpeak=".$this -> calculate_time_condition($stop_time,$timeinterval, "offpeak")." ");
	
	    //TO DO - for now we use peak values only if whole call duration is inside a peak time interval. Should be devided in two parts - peek and off peak duration. May be later.
	    if ((($this -> calculate_time_condition($start_time,$timeinterval, "peak")) && 
                !($this -> calculate_time_condition($start_time,$timeinterval, "offpeak"))) && 
                (($this -> calculate_time_condition($stop_time,$timeinterval, "peak")) && 
                !($this -> calculate_time_condition($stop_time,$timeinterval, "offpeak")))) {
            # We have PEAK time
		    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[bill_did_aleg]: We have PEAK time.");
		    $aleg_carrier_connect_charge = $inst_listdestination[11];
		    $aleg_carrier_cost_min = $inst_listdestination[12];
		    $aleg_retail_connect_charge = $inst_listdestination[13];
		    $aleg_retail_cost_min = $inst_listdestination[14];
		
		    $aleg_carrier_initblock = $inst_listdestination[15];
		    $aleg_carrier_increment = ($inst_listdestination[16]<=0) ? 1 : $inst_listdestination[16];
		    $aleg_retail_initblock = $inst_listdestination[17];
		    $aleg_retail_increment = $inst_listdestination[18];
		    #TODO use the above variables to define the time2call
	    } else {
	        #We have OFF-PEAK time
		    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[bill_did_aleg]: We have OFF-PEAK time.");
		    $aleg_carrier_connect_charge = $inst_listdestination[20];
		    $aleg_carrier_cost_min = $inst_listdestination[21];
		    $aleg_retail_connect_charge = $inst_listdestination[22];
		    $aleg_retail_cost_min = $inst_listdestination[23];
		
		    $aleg_carrier_initblock = $inst_listdestination[24];
		    $aleg_carrier_increment = ($inst_listdestination[25]<=0) ? 1: $inst_listdestination[25];
		    $aleg_retail_initblock = $inst_listdestination[26];
		    $aleg_retail_increment = $inst_listdestination[27];
		    #TODO use the above variables to define the time2call
	    }
/*
        $this -> debug( INFO, $agi, __FILE__, __LINE__, "[bill_did_aleg]:[aleg_carrier_connect_charge=$aleg_carrier_connect_charge;\
                                                                          aleg_carrier_cost_min=$aleg_carrier_cost_min;\
                                                                          aleg_retail_connect_charge=$aleg_retail_connect_charge;\
                                                                          aleg_retail_cost_min=$aleg_retail_cost_min;\
                                                                          aleg_carrier_initblock=$aleg_carrier_initblock;\
                                                                          aleg_carrier_increment=$aleg_carrier_increment;\
                                                                          aleg_retail_initblock=$aleg_retail_initblock;\
                                                                          aleg_retail_increment=$aleg_retail_increment - b_leg_answeredtime=$b_leg_answeredtime]");
*/
        $this -> dnid = $inst_listdestination[10];

	# Carrier Minimum Duration and Billing Increment
	$aleg_carrier_callduration = $aleg_real_answeredtime;

	if ($aleg_carrier_callduration < $aleg_carrier_initblock) {
	    $aleg_carrier_callduration = $aleg_carrier_initblock;
	}

	if (($aleg_carrier_increment > 0) && ($aleg_carrier_callduration > $aleg_carrier_initblock)) {
	    $mod_sec = $aleg_carrier_callduration % $aleg_carrier_increment; // 12 = 30 % 18
	    if ($mod_sec > 0) $aleg_carrier_callduration += ($aleg_carrier_increment - $mod_sec); // 30 += 18 - 12
	}

	# Retail Minimum Duration and Billing Increment
	$aleg_retail_callduration = $aleg_real_answeredtime;

	if ($aleg_retail_callduration < $aleg_retail_initblock) {
	    $aleg_retail_callduration = $aleg_retail_initblock;
	}

	if (($aleg_retail_increment > 0) && ($aleg_retail_callduration > $aleg_retail_initblock)) {
	    $mod_sec = $aleg_retail_callduration % $aleg_retail_increment; // 12 = 30 % 18
	    if ($mod_sec > 0) $aleg_retail_callduration += ($aleg_retail_increment - $mod_sec); // 30 += 18 - 12
	}

	// CC_DID & CC_DID_DESTINATION - cc_did.id, cc_did_destination.id
	$QUERY = "UPDATE cc_did SET secondusedreal = secondusedreal + $aleg_carrier_callduration WHERE id='$this->id_did'";
	$result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY, 0);
	$this -> debug( INFO, $agi, __FILE__, __LINE__, "[UPDATE DID]:[result:$result]");

	$QUERY = "UPDATE cc_did_destination SET secondusedreal = secondusedreal + $aleg_retail_callduration WHERE id='".$inst_listdestination[1]."'";
	$result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY, 0);
	$this -> debug( INFO, $agi, __FILE__, __LINE__, "[UPDATE DID_DESTINATION]:[result:$result]");

        # if we add a new CDR for A-Leg
        if (($aleg_carrier_connect_charge != 0) || ($aleg_carrier_cost_min != 0) || ($aleg_retail_connect_charge != 0) || ($aleg_retail_cost_min != 0)) {
            # duration of the call for the A-Leg is since the start date

	    $terminatecauseid = 1; // ANSWERED

	    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL]:[A-Leg -> dnid=".$this -> dnid."; answeredtime=".$aleg_real_answeredtime."]");

            $aleg_carrier_cost = 0;
            $aleg_carrier_cost += $aleg_carrier_connect_charge;
            $aleg_carrier_cost += ($aleg_carrier_callduration / 60) * $aleg_carrier_cost_min;

	    $aleg_retail_cost = 0;
            $aleg_retail_cost += $aleg_retail_connect_charge;
            $aleg_retail_cost += ($aleg_retail_callduration / 60) * $aleg_retail_cost_min;

            $cost		= ($this -> margintotal > 0) ? $aleg_retail_cost / $this -> margintotal : 0 ;
            $margindillers	= $cost * ($this -> margintotal - 1);
            $commission 	= $this -> margin * ($cost + $margindillers) / ($this -> margin + 100);
            if ($cost == 0)
			$cost	= $aleg_retail_cost;

            $QUERY_COLUMN = " uniqueid, sessionid, card_id, nasipaddress, starttime, sessiontime, real_sessiontime, calledstation, ".
                            " terminatecauseid, stoptime, sessionbill, buycost, margindillers, margindiller, id_tariffgroup, id_tariffplan, id_ratecard, " .
                            " id_trunk, src, sipiax, dnid, id_did, destination";
            $calltype = '7'; // DID-ALEG 
            $QUERY = "INSERT INTO cc_call ($QUERY_COLUMN) VALUES (".
                        "'".substr($this->uniqueid, 0, strpos($this->uniqueid, '.' ))."', ".
                        "'".$this->channel."',".
                        "'".$this->id_card."',".
                        "'".$this->hostname."',".
                        "SUBDATE(CURRENT_TIMESTAMP, INTERVAL $aleg_real_answeredtime SECOND), ".
                        "'$aleg_retail_callduration', ".
                        "'$aleg_real_answeredtime', ".
                    		"'".$listdestination[0][10]."', ".
                    		"$terminatecauseid, ".
                    		"now(), ".
                        (a2b_round($aleg_retail_cost)-a2b_round($margindillers)).", ".a2b_round($aleg_carrier_cost).", ".a2b_round($margindillers).", ".a2b_round($commission).", ".
		                "'0', '0', '0', '0', ".
		                "'".$this->CallerID."', ".
		                "'$calltype', ".
		                "'".$this -> dnid."', ".
		                "'".$this -> id_did."', ".
		                "'-3')";

            $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
            $this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - LOG CC_CALL: SQL: $QUERY]:[result:$result]");

            if ($aleg_retail_cost != 0) {
                // update card
                $QUERY = "UPDATE cc_card SET commission=commission+".a2b_round($commission).", credit=credit-".a2b_round($aleg_retail_cost)." WHERE username='".$this->username."'";
                $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
                $this -> debug( INFO, $agi, __FILE__, __LINE__, "[DID CALL - (id_card=".$this->id_card.") UPDATE CARD: SQL: $QUERY]:[result:$result]");
            }

	    $id_diller = $this->id_diller;
	    if ($margindillers && $id_diller && $cost>0) do {
		if ($commission > 0) {
			$QUERY = "UPDATE cc_card SET credit= credit+".a2b_round($commission)." WHERE id=$id_diller";
			$result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY, 0);
			$margindillers -= $commission;
		}
		$QUERY = "SELECT id_diller, margin FROM cc_card WHERE id=$id_diller";
		$result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY);
		$id_diller_next = $result[0][0];
		$margin 	= $result[0][1];
		if ($id_diller_next) {
			$commission = $margin * (abs($cost) + $margindillers) / ($margin + 100);
			if ($commission > 0) {
				$QUERY = "UPDATE cc_card SET commission= commission+".a2b_round($commission)." WHERE id=$id_diller";
				$result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY, 0);
			}
		}
		$id_diller = $id_diller_next;
	    } while ($id_diller);

        }
        return;
    }


    public function fct_say_time_2_call($agi,$timeout,$rate=0,$addtimeout=0)
    {
         // set destination and timeout
        // say 'you have x minutes and x seconds'
        $timeout = $timeout + $addtimeout;
        $minutes = intval($timeout / 60);
        $seconds = $timeout % 60;
		
        $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "TIMEOUT::> ".$this->timeout." : minutes=$minutes - seconds=$seconds");
        if (!($minutes>0) && !($seconds>10)) {
            $this -> let_stream_listening($agi);
            $prompt="prepaid-no-enough-credit";
            $agi-> stream_file($prompt, '#');
            return -1;
        }
		
        if ($this->agiconfig['say_rateinitial']==1) {
		$this -> let_stream_listening($agi);
		$this -> fct_say_rate ($agi, $rate);
        }
		
        if ($this->agiconfig['say_timetocall']==1) {
	    $this -> let_stream_listening($agi);
            $agi-> stream_file('prepaid-you-have', '#');
            if ($minutes>0) {
                if ($minutes==1) {
                    if ((strtolower($this ->current_language)=='ru')) {
                    $agi-> stream_file('digits/1f', '#');
                    } else {
						$agi->say_number($minutes);
                    }
                    $agi-> stream_file('prepaid-minute', '#');
                } else {
                    $agi->say_number($minutes);
                    if ((strtolower($this ->current_language)=='ru')&& ( ( $minutes%10==2) || ($minutes%10==3 )|| ($minutes%10==4))) {
	                    // test for the specific grammatical rules in RUssian
	                    $agi-> stream_file('prepaid-minute2', '#');
                    } else {
						$agi-> stream_file('prepaid-minutes', '#');
                    }
                }
            }
            if ($seconds>0 && ($this->agiconfig['disable_announcement_seconds']==0)) {
                if ($minutes>0) {
                	$agi-> stream_file('vm-and', '#');
                }
                if ($seconds==1) {
                    if ((strtolower($this ->current_language)=='ru')) {
						$agi-> stream_file('digits/1f', '#');
                    } else {
						$agi-> say_number($seconds);
						$agi-> stream_file('prepaid-second', '#');
                    }
                } else {
                    $agi->say_number($seconds);
                    if ((strtolower($this ->current_language)=='ru')&& ( ( $seconds%10==2) || ($seconds%10==3 )|| ($seconds%10==4))) {
                        // test for the specific grammatical rules in RUssian
                        $agi-> stream_file('prepaid-second2', '#');
                    } else {
                         $agi-> stream_file('prepaid-seconds', '#');
                    }
                }
            }
        }
	}
	
	/**
	 *	Function to play the balance
	 * 	format : "you have 100 dollars and 28 cents"
	 *
	 *  @param object $agi
     *  @param float $credit
     *  @return nothing
	**/
	public function fct_say_balance ($agi, $credit, $fromvoucher = 0)
	{
		global $currencies_list;
		$ed = '0123456789#';

		if (isset($this->agiconfig['agi_force_currency']) && strlen($this->agiconfig['agi_force_currency'])==3) {
			$this->currency = $this->agiconfig['agi_force_currency'];
		}

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CURRENCY : $this->currency]");
		if (!isset($currencies_list[strtoupper($this->currency)][2]) || !is_numeric($currencies_list[strtoupper($this->currency)][2])) {
			$mycur = 1;
		} else {
			$mycur = $currencies_list[strtoupper($this->currency)][2];
		}

		$credit_cur = $credit / $mycur;
		list($units, $cents)=preg_split('/[.]/', sprintf('%01.2f', $credit_cur));

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[BEFORE: $credit_cur SPRINTF : ".sprintf('%01.2f', $credit_cur)."]");

		if (isset($this->agiconfig['currency_association_internal'][strtolower($this->currency)])) {
			$units_audio = $this->agiconfig['currency_association_internal'][strtolower($this->currency)];
			// substract the last character ex: dollars -> dollar
			$unit_audio = substr($units_audio,0,-1);
		} else {
			$units_audio = $this->agiconfig['currency_association_internal']['all'];
			$unit_audio = $units_audio;
		}
		
		if (isset($this->agiconfig['currency_cents_association_internal'][strtolower($this->currency)])) {
			$cents_audio = $this->agiconfig['currency_cents_association_internal'][strtolower($this->currency)];
		} else {
			$cents_audio = "prepaid-cents";
		}
		
		switch ($cents_audio) {	
			case 'prepaid-pence':
				$cent_audio = 'prepaid-penny';
				break;
			default:
				$cent_audio = substr($cents_audio,0,-1);
		}
		do {
		    $this -> let_stream_listening($agi);
		    sleep(1);
		    // say 'you have x dollars and x cents'
		    if ($fromvoucher!=1) {
			$result = $agi-> stream_file('prepaid-you-have', $ed);
		    } else {
			$result = $agi-> stream_file('prepaid-account_refill', $ed);
		    }
		    if ($result['result'] > 0) break;
		    if ($units==0 && $cents==0) {
			$result = $agi->say_number(0, $ed);
			if ($result['result'] > 0) break;
			if (($this ->current_language=='ru') && (strtolower($this->currency)=='usd') ) {
				$result = $agi-> stream_file($units_audio, $ed);
			} else {
				$result = $agi-> stream_file($unit_audio, $ed);
			}
		    } else {
			if ($units > 1) {
				$result = $agi->say_number($units, $ed);
				if ($result['result'] > 0) break;
				if (($this ->current_language=='ru') && (strtolower($this->currency)=='usd') && (  ($units%10==0) ||( $units%10==2) || ($units%10==3 ) || ($units%10==4)) ) {
					// test for the specific grammatical rules in Russian
					$result = $agi-> stream_file('dollar2', $ed);
				} elseif (($this ->current_language=='ru') && (strtolower($this->currency)=='usd') && ( $units%10==1)) {
					// test for the specific grammatical rules in Russian
					$result = $agi-> stream_file($unit_audio, $ed);
				} else {
					$result = $agi-> stream_file($units_audio, $ed);
				}
			} else {
				$result = $agi->say_number($units, $ed);
				if ($result['result'] > 0) break;
				if (($this ->current_language=='ru') && (strtolower($this->currency)=='usd') && ($units == 0)) {
					$result = $agi-> stream_file($units_audio, $ed);	
				} else {				
					$result = $agi-> stream_file($unit_audio, $ed);
				}
			}
			if ($result['result'] > 0) break;
			if ($units > 0 && $cents > 0) {
				$result = $agi-> stream_file('vm-and', $ed);
				if ($result['result'] > 0) break;
			}
			if ($cents>0) {
				$result = $agi->say_number($cents, $ed);
				if ($result['result'] > 0) break;
				if ($cents>1) {
					if ((strtolower($this->currency)=='usd')&&($this ->current_language=='ru')&& ( ($cents%10==2) || ($cents%10==3)|| ($cents%10==4)) ) {
						// test for the specific grammatical rules in RUssian
						$result = $agi-> stream_file('prepaid-cent2', $ed);
					} elseif ((strtolower($this->currency)=='usd')&&($this ->current_language=='ru')&& ($cents%10==1) ) {
						// test for the specific grammatical rules in RUssian
						$result = $agi-> stream_file($cent_audio, $ed);
					} else {
						$result = $agi-> stream_file($cents_audio, $ed);
					}
				} else {
					$result = $agi-> stream_file($cent_audio, $ed);
				}

			}
		    }
		} while (true==false);
		if ($result['result'] > 0) {
			$this -> first_dtmf = chr($result['result']);
			if (!is_numeric($this -> first_dtmf))
				$this -> first_dtmf = '';
		}
	}


	/**
	 * 	Function to play the initial rate
	 *  format : "the cost of the call is 7 dollars and 50 cents per minutes"
	 *
	 *  @param object $agi
	 *  @param float $rate
	 *  @return nothing
	 **/
	public function fct_say_rate ($agi, $rate)
	{
		global $currencies_list;

		if (isset($this->agiconfig['agi_force_currency']) && strlen($this->agiconfig['agi_force_currency'])==3) {
			$this->currency = $this->agiconfig['agi_force_currency'];
		}
		
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CURRENCY : $this->currency]");
		if (!isset($currencies_list[strtoupper($this->currency)][2]) || !is_numeric($currencies_list[strtoupper($this->currency)][2])) $mycur = 1;
		else $mycur = $currencies_list[strtoupper($this->currency)][2];
		$credit_cur = round($rate * $this->margintotal / $mycur, 2);
//		$credit_cur = (string) $credit_cur;
//		list($units,$cents)=preg_split('/[.]/', $credit_cur);
		$units = floor($credit_cur);
		$cents = round(($credit_cur-$units)*100);
//		$units = (string) $units;
//		$cents = (string) $cents;
		if (substr($cents, 2) > 0) $point = substr($cents, 2, 1);
		if (strlen($cents)>2) $cents=substr($cents, 0, 2);
		if ($units=='') $units=0;
		if ($cents=='') $cents=0;
		if (!isset($point) || $point=='') $point=0;
		elseif (strlen($cents)==1) $cents.= '0';

		if (isset($this->agiconfig['currency_association_internal'][strtolower($this->currency)])) {
			$units_audio = $this->agiconfig['currency_association_internal'][strtolower($this->currency)];
			// leave the last character ex: dollars -> dollar
			$unit_audio = substr($units_audio,0,-1);
		} else {
			$units_audio = $this->agiconfig['currency_association_internal']['all'];
			$unit_audio = $units_audio;
		}
		$cent_audio = 'prepaid-cent';
		$cents_audio = 'prepaid-cents';
        
		// say 'the cost of the call is '
		$agi-> stream_file('prepaid-cost-call', '#');

		if ($units==0 && $cents==0 && $this->agiconfig['play_rate_cents_if_lower_one']==0 && !($this->agiconfig['play_rate_cents_if_lower_one']==1 && $point==0)) {
			$agi -> say_number(0);
			$agi -> stream_file($unit_audio, '#');
		} else {
			if ($units >= 1) {
				$agi -> say_number($units);
			    
				if (($this ->current_language=='ru')&&(strtolower($this->currency)=='usd')&& ( ( $units%10==2) || ($units%10==3 )|| ($units%10==4)) ) {
					// test for the specific grammatical rules in RUssian
					$agi-> stream_file('dollar2', '#');
				} elseif (($this ->current_language=='ru')&&(strtolower($this->currency)=='usd')&& ( $units%10==1)) {
					// test for the specific grammatical rules in RUssian
					$agi-> stream_file($unit_audio, '#');
				} else {
					$agi-> stream_file($units_audio, '#');
				}
					
			} elseif ($this->agiconfig['play_rate_cents_if_lower_one']==0) {
				$agi -> say_number($units);
				$agi -> stream_file($unit_audio, '#');
			}
            
			if ($units > 0 && $cents > 0) {
				$agi -> stream_file('vm-and', '#');
			}
			if ($cents>0 || ($point>0 && $this->agiconfig['play_rate_cents_if_lower_one']==1)) {
				$agi -> say_number($cents);
				if ($point>0 && $this->agiconfig['play_rate_cents_if_lower_one']==1) {
					$this -> debug( INFO, $agi, __FILE__, __LINE__, "point");
    				$agi-> stream_file('prepaid-point', '#');
				    $agi -> say_number($point);
				}
				if ($cents>1) {
					if ((strtolower($this->currency)=='usd')&&($this ->current_language=='ru')&& ( ( $cents%10==2) || ($cents%10==3 )|| ($cents%10==4)) ) {
						// test for the specific grammatical rules in RUssian
						$agi-> stream_file('prepaid-cent2', '#');
					} elseif ((strtolower($this->currency)=='usd')&&($this ->current_language=='ru')&&  ( $cents%10==1)  ) {
						// test for the specific grammatical rules in RUssian
						$agi-> stream_file($cent_audio, '#');
					} else {
						$agi-> stream_file($cents_audio, '#');
					}
				} else {
					$agi-> stream_file($cent_audio, '#');
				}
			}
		}
		// say 'per minutes'
		$agi-> stream_file('prepaid-per-minutes', '#');
	}

	/**
	 *	Function refill_card_with_voucher
	 *
	 *  @param object $agi
	 *  @param object $RateEngine
	 *  @param object $voucher number

     *  @return 1 if Ok ; -1 if error
	**/
	public function refill_card_with_voucher ($agi, $try_num)
	{
		global $currencies_list;

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[VOUCHER REFILL CARD LOG BEGIN]");
		if (isset($this->agiconfig['agi_force_currency']) && strlen($this->agiconfig['agi_force_currency'])==3) {
			$this -> currency = $this->agiconfig['agi_force_currency'];
		}

		if (!isset($currencies_list[strtoupper($this->currency)][2]) || !is_numeric($currencies_list[strtoupper($this->currency)][2])) {
			$mycur = 1;
		} else {
			$mycur = $currencies_list[strtoupper($this->currency)][2];
		}
		$timetowait = ($this->config['global']['len_voucher'] < 6) ? 8000 : 20000;
		$res_dtmf = $agi->get_data('prepaid-voucher_enter_number', $timetowait, $this->config['global']['len_voucher'], '#');
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "VOUCHERNUMBER RES DTMF : ".$res_dtmf ["result"]);
		$this -> vouchernumber = $res_dtmf ["result"];
		if ($this -> vouchernumber <= 0) {
			return -1;
		}

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "VOUCHER NUMBER : ".$this->vouchernumber);

		$QUERY = "SELECT voucher, credit, activated, tag, currency, expirationdate FROM cc_voucher WHERE expirationdate >= CURRENT_TIMESTAMP AND activated='t' AND voucher='".$this -> vouchernumber."'";

		$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[VOUCHER SELECT: $QUERY]\n".print_r($result,true));

		if ($result[0][0]==$this->vouchernumber) {
			if (!isset ($currencies_list[strtoupper($result[0][4])][2])) {
				$this -> debug( ERROR, $agi, __FILE__, __LINE__, "System Error : No currency table complete !!!");
				return -1;
			} else {
				// DISABLE THE VOUCHER
				$this -> add_credit = $result[0][1] * $currencies_list[strtoupper($result[0][4])][2];
				$QUERY = "UPDATE cc_voucher SET activated='f', usedcardnumber='".$this->accountcode."', used=1, usedate=now() WHERE voucher='".$this->vouchernumber."'";
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "QUERY UPDATE VOUCHER: $QUERY");
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);

				// UPDATE THE CARD AND THE CREDIT PROPERTY OF THE CLASS
				$QUERY = "UPDATE cc_card SET credit=credit+'".$this ->add_credit."' WHERE username='".$this->accountcode."'";
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
				$this -> credit += $this -> add_credit;

				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "QUERY UPDATE CARD: $QUERY");
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, ' The Voucher '.$this->vouchernumber.' has been used, We added '.$this ->add_credit/$mycur.' '.strtoupper($this->currency).' of credit on your account!');
				$this->fct_say_balance ($agi, $this->add_credit, 1);
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[VOUCHER REFILL CARD: $QUERY]");
				return 1;
			}
		}
		else
		{
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[VOUCHER REFILL ERROR: ".$this->vouchernumber." Voucher not avaible or dosn't exist]");
			$agi-> stream_file('voucher_does_not_exist');
			return -1;
		}
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[VOUCHER REFILL CARD LOG END]");
		return 1;
	}


	/*
	 * Function to generate a cardnumber
	 */
	public function MDP( $chrs = 10 )
	{
		$pwd = "";
		 mt_srand ((double) microtime() * 1000000);
		 while (strlen($pwd) < $chrs)
		 {
			$chr = chr(mt_rand (0,255));
			if (preg_match("/^[0-9]$/i", $chr))
				$pwd = $pwd.$chr;
		 };
		 return $pwd;
	}

	/** Function to retrieve the number of used package Free call for a customer
	 * according to billingtype (Monthly ; Weekly) & Startday
	 *
	 *  @param object $DBHandle
	 *  @param integer $id_cc_card
	 *  @param integer $id_cc_package_offer
	 *  @param integer $billingtype
	 *  @param integer $startday
	 *  @return integer number of seconds used of FT2C package so far in this period
	 **/

   public function number_free_calls_used($DBHandle, $id_cc_card, $id_cc_package_offer, $billingtype, $startday) {
   	
   		if ($billingtype == 0) {
			// PROCESSING FOR MONTHLY
			// if > last day of the month
			if ($startday > date("t")) $startday = date("t");
			if ($startday <= 0 ) $startday = 1;

			// Check if the startday is upper that the current day
			if ($startday > date("j")) $year_month = date('Y-m', strtotime('-1 month'));
			else $year_month = date('Y-m');

			$yearmonth = sprintf("%s-%02d",$year_month,$startday);
			$CLAUSE_DATE=" TIMESTAMP(date_consumption) >= TIMESTAMP('$yearmonth')";
		} else {
			// PROCESSING FOR WEEKLY
			$startday = $startday % 7;
			$dayofweek = date("w"); // Numeric representation of the day of the week 0 (for Sunday) through 6 (for Saturday)
			if ($dayofweek==0) $dayofweek=7;
			if ($dayofweek < $startday) $dayofweek = $dayofweek + 7;
			$diffday = $dayofweek - $startday;
			$CLAUSE_DATE = "date_consumption >= DATE_SUB(CURRENT_DATE, INTERVAL $diffday DAY) ";
		}
		$QUERY = "SELECT  COUNT(*) AS number_calls FROM cc_card_package_offer ".
				 "WHERE $CLAUSE_DATE AND id_cc_card = '$id_cc_card' AND id_cc_package_offer = '$id_cc_package_offer' ";
		$pack_result = $DBHandle -> Execute($QUERY);
		if ($pack_result && ($pack_result -> RecordCount() > 0)) {
			$result = $pack_result -> fetchRow();
			$number_calls_used = $result[0];
		} else {
			$number_calls_used = 0;
		}
		return $number_calls_used;
   	
   }


	/** Function to retrieve the amount of used package FT2C seconds for a customer
	 * according to billingtype (Monthly ; Weekly) & Startday
	 *
	 *  @param object $DBHandle
	 *  @param integer $id_cc_card
	 *  @param integer $id_cc_package_offer
	 *  @param integer $billingtype
	 *  @param integer $startday
	 *  @return integer number of seconds used of FT2C package so far in this period
	 **/
	public function FT2C_used_seconds($DBHandle, $id_cc_card, $id_cc_package_offer, $billingtype, $startday)
	{
		if ($billingtype == 0) {
			// PROCESSING FOR MONTHLY
			// if > last day of the month
			if ($startday > date("t"))
				$startday = date("t");
			if ($startday <= 0)
				$startday = 1;

			// Check if the startday is upper that the current day
			if ($startday > date("j"))
				$year_month = date('Y-m', strtotime('-1 month'));
			else
				$year_month = date('Y-m');

			$yearmonth = sprintf("%s-%02d",$year_month, $startday);
			$CLAUSE_DATE=" TIMESTAMP(date_consumption) >= TIMESTAMP('$yearmonth')";
		} else {
			// PROCESSING FOR WEEKLY
			$startday = $startday % 7;
			$dayofweek = date("w"); // Numeric representation of the day of the week 0 (for Sunday) through 6 (for Saturday)
			if ($dayofweek==0) $dayofweek = 7;
			if ($dayofweek < $startday) $dayofweek = $dayofweek + 7;
			$diffday = $dayofweek - $startday;
			$CLAUSE_DATE = "date_consumption >= DATE_SUB(CURRENT_DATE, INTERVAL $diffday DAY) ";
		}
		$QUERY = "SELECT sum(used_secondes) AS used_secondes FROM cc_card_package_offer ".
				 "WHERE $CLAUSE_DATE AND id_cc_card = '$id_cc_card' AND id_cc_package_offer = '$id_cc_package_offer' ";

		$this->instance_table = new Table();
		$pack_result = $this->instance_table -> SQLExec ($DBHandle, $QUERY);
		
		if ($pack_result && is_array($pack_result)) {
			$result = $pack_result[0];
			$freetimetocall_used = $result[0];
		} else {
			$freetimetocall_used = 0;
		}
		return $freetimetocall_used;
	}
	
	
	/*
	 * Function apply_rules to the phonenumber : Remove internation prefix
	 */
	public function apply_rules ($phonenumber)
	{
		if ($phonenumber != '-1') {
		    $phonenumber = strpbrk(substr($phonenumber,0,1),"+") . preg_replace ("/(^[a-z].*)|[^\d]/i","$1",$phonenumber);
		    if (is_array($this->agiconfig['international_prefixes']) && (count($this->agiconfig['international_prefixes'])>0)) {
			foreach ($this->agiconfig['international_prefixes'] as $testprefix) {
				if (substr($phonenumber,0,strlen($testprefix))==$testprefix) {
					$this->myprefix = $testprefix;
					return substr($phonenumber,strlen($testprefix));
				}
			}
		    }
		    $this->myprefix='';
		}
		return $phonenumber;
	}

	public function apply_cid_rules ($cidnumber,$remadd,$addint)
	{
		$cidbirth = $cidnumber;
		$remadd = explode(",",$remadd);
		if (is_array($remadd) && count($remadd)>0) {
			for ($i=0;$i<count($remadd);$i=$i+2) {
				if ($remadd[$i]!="" && substr($cidnumber,0,strlen($remadd[$i]))==$remadd[$i]) {
					$cidnumber = @ $remadd[$i+1] . substr($cidnumber,strlen($remadd[$i]));
					break;
				}
			}
		}
		if ($cidbirth == $cidnumber && strlen($cidbirth)>6 && !preg_match("/^[a-zA-Z0].{2,}$/",$cidbirth))
			$cidnumber = $addint . $cidnumber;
		return $cidnumber;
	}

	public function did_apply_add_countryprefixfrom($result,$phonenumber)
	{
	    if (is_numeric($phonenumber)) {
		$areaprefix	= $result[0];
		$citylength	= $result[1];
		$countryprefix	= $result[2];
		if (strlen($countryprefix)>0) {
//			if (substr($phonenumber,0,1)=="0" && substr($phonenumber,1,1)!="0") {}
			if (substr($phonenumber,0,1)=='0' && strpos($phonenumber,'11')!=1 && substr($phonenumber,1,1)!='0') {
				$phonenumber = $countryprefix.substr($phonenumber,1);
			} elseif (!is_null($areaprefix) && !is_null($citylength)) {
				if (substr($phonenumber,0,strlen($areaprefix))==$areaprefix && strlen($phonenumber)==strlen($areaprefix)+$citylength) {
					$phonenumber = $countryprefix.$phonenumber;
				} elseif (strlen($phonenumber)==$citylength) {
					$phonenumber = $countryprefix.$areaprefix.$phonenumber;
				}
			}
		}
	    }
	    return $phonenumber;
	}

	/*
	 * Function apply_add_countryprefixto the phonenumber 
	 */
	public function apply_add_countryprefixto ($phonenumber)
	{
		if ($this->agiconfig['local_dialing_addcountryprefix']==1) {

/**			if ((strlen($this->countryprefix)>0) && (substr($phonenumber,0,1)=="0") && (substr($phonenumber,1,1)!="0")) {
				return $this->countryprefix.substr($phonenumber,1);
			}
**/

			if (strlen($this->countryprefix)>0) {
//				if (substr($phonenumber,0,1)=="0" && substr($phonenumber,1,1)!="0") {}
				if (substr($phonenumber,0,1)=='0' && strpos($phonenumber,'11')!=1 && substr($phonenumber,1,1)!='0') {
					$phonenumber = $this->countryprefix.substr($phonenumber,1);
				} elseif (!is_null($this->areaprefix) && !is_null($this->citylength)) {
					if (substr($phonenumber,0,strlen($this->areaprefix))==$this->areaprefix && strlen($phonenumber)==strlen($this->areaprefix)+$this->citylength) {
						$phonenumber = $this->countryprefix.$phonenumber;
					} elseif (strlen($phonenumber)==$this->citylength) {
						$phonenumber = $this->countryprefix.$this->areaprefix.$phonenumber;
					}
				}
			}

		}
		return $phonenumber;
	}

	/*
	 * Function callingcard_cid_sanitize : Ensure the caller is allowed to use their claimed CID.
	 * Returns: clean CID value, possibly empty.
	 */
	public function callingcard_cid_sanitize($agi)
	{
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_SANITIZE - CID:".$this->CallerID."]");

		if (strlen($this->CallerID)==0) {
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_SANITIZE - CID: NO CID]");
			return '';
		}
		$QUERY="";
		if ($this->agiconfig['cid_sanitize']=="CID" || $this->agiconfig['cid_sanitize']=="BOTH") {
			$QUERY .=  "SELECT cc_callerid.cid ".
				  " FROM cc_callerid ".
				  " JOIN cc_card ON cc_callerid.id_cc_card=cc_card.id ".
				  " WHERE (cc_callerid.activated=1 OR cc_callerid.activated='t') AND blacklist = 0 AND cc_card.username='".$this -> username."' ";
			$QUERY .= "ORDER BY 1";
			$result1 = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, print_r($result1,true));
		}
		
		$QUERY="";
		if ($this->agiconfig['cid_sanitize']=="DID" || $this->agiconfig['cid_sanitize']=="BOTH") {
			$QUERY .= "SELECT cc_did.did ".
				  "FROM cc_did ".
				  "JOIN cc_did_destination ON cc_did_destination.id_cc_did=cc_did.id ".
				  "JOIN cc_card ON cc_did_destination.id_cc_card=cc_card.id ".
				  "WHERE (cc_did.activated=1 OR cc_did.activated='t') AND cc_did_destination.activated=1 AND cc_did.startingdate <= NOW() AND cc_did.expirationdate >= NOW() ".
				  "AND cc_card.username='".$this->username."' ".
				  "AND cc_did_destination.validated=1 ".
				  "ORDER BY 1";
			$result2 = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, print_r($result2,true));
		}
		if (count($result1)>0 || count($result2)>0)
			$result = array_merge ((array) $result1, (array) $result2);

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RESULT MERGE -> ".print_r($result,true));

		if ( !is_array($result)) {
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_SANITIZE - CID: NO DATA]");
			return '';
		}
		for ($i=0;$i<count($result);$i++) {
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_SANITIZE - CID COMPARING: ".substr($result[$i][0],strlen($this->CallerID)*-1)." to ".$this->CallerID."]");
			if (substr($result[$i][0],strlen($this->CallerID)*-1)==$this->CallerID) {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_SANITIZE - CID: ".$result[$i][0]."]");
				return $result[$i][0];
			}
		}
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_SANITIZE - CID UNIQUE RESULT: ".$result[0][0]."]");
		return $result[0][0];
	}


	public function callingcard_auto_setcallerid($agi)
	{
		// AUTO SetCallerID
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[AUTO SetCallerID]");
		if ($this->agiconfig['auto_setcallerid']==1) {
			if ( strlen($this->agiconfig['force_callerid']) >=1 ) {
				$this -> CID_handover = $this -> agiconfig['force_callerid'];
				$agi -> set_callerid($this -> CID_handover);
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[EXEC SetCallerID : ".$this->agiconfig['force_callerid']."]");
			} elseif ( strlen($this->CallerID) >=1 ) {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[REQUESTED SetCallerID : ".$this->CallerID."]");

			// IF REQUIRED, VERIFY THAT THE CALLERID IS LEGAL
				$cid_sanitized = $this->CallerID;
				if ($this->agiconfig['cid_sanitize']=='DID' || $this->agiconfig['cid_sanitize']=='CID' || $this->agiconfig['cid_sanitize']=='BOTH') {
					$cid_sanitized = $this -> callingcard_cid_sanitize($agi);
					$this -> debug( WRITELOG, $agi, __FILE__, __LINE__, "[TRY : callingcard_cid_sanitize]");
					if ($this->agiconfig['debug']>=1)
						$agi->verbose('CALLERID SANITIZED: "'.$cid_sanitized.'"');
				}

				if (strlen($cid_sanitized)>0) {
					$this -> CID_handover = $cid_sanitized;
					$agi -> set_callerid($cid_sanitized);
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[EXEC SetCallerID : ".$cid_sanitized."]");
				} else {
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CANNOT SetCallerID : cid_san is empty]");
				}
			}
		}

		// Let the Caller set his CallerID
		if ($this->agiconfig['callerid_update']==1) {
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[UPDATE CallerID]");

			$res_dtmf = $agi->get_data('prepaid-enter-cid', 6000, 20);
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES DTMF : ".$res_dtmf ["result"]);

			if ( strlen($res_dtmf ["result"]) > 0 && is_numeric($res_dtmf ["result"]) ) {
				$this -> CID_handover = $res_dtmf ["result"];
				$agi->set_callerid($this -> CID_handover);
			}
		}
	}


	public function update_callback_campaign($agi)
	{
		$now = time();
		$username = $agi->get_variable("USERNAME", true);
		$userid= $agi->get_variable("USERID", true);
		$called= $agi->get_variable("CALLED", true);
		$phonenumber_id= $agi->get_variable("PHONENUMBER_ID", true);
		$campaign_id= $agi->get_variable("CAMPAIGN_ID", true);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[MODE CAMPAIGN CALLBACK: USERNAME=$username  USERID=$userid ]");

		$query_rate = "SELECT cc_campaign_config.flatrate, cc_campaign_config.context FROM cc_card,cc_card_group,cc_campaignconf_cardgroup,cc_campaign_config , cc_campaign WHERE cc_card.id = $userid AND cc_card.id_group = cc_card_group.id AND cc_campaignconf_cardgroup.id_card_group = cc_card_group.id  AND cc_campaignconf_cardgroup.id_campaign_config = cc_campaign_config.id AND cc_campaign.id = $campaign_id AND cc_campaign.id_campaign_config = cc_campaign_config.id";
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY SEARCH CAMPAIGN CONFIG : ".$query_rate);

		$result_rate = $this->instance_table -> SQLExec ($this -> DBHandle, $query_rate);	

		$cost = 0;
		if ($result_rate) {
			$cost = $result_rate[0][0];
			$context = $result_rate[0][1];
		}

		if (empty($context)) {
			$context =  $this -> config["callback"]['context_campaign_callback'];
		}

		if ($cost>0) {
			$signe='-';
		} else {
			$signe='+';
		}
		//update balance	
		$QUERY = "UPDATE cc_card SET credit= credit $signe ".a2b_round(abs($cost))." ,  lastuse=now() WHERE username='".$username."'";
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[UPDATE CARD : ".$QUERY);
		$this->instance_table -> SQLExec ($this -> DBHandle, $QUERY);

		//dial other context
		$agi -> set_variable('CALLERID(name)', $phonenumber_id.','.$campaign_id);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CONTEXT TO CALL : ".$context."]");
		$agi->exec_dial("local","1@".$context);
		$this -> DbReConnect($agi);
		$duration = time() - $now;
		///create campaign cdr
		$QUERY_CALL = "INSERT INTO cc_call (uniqueid, sessionid, card_id, calledstation, sipiax, sessionbill, sessiontime, stoptime, starttime, callbackid) ".
					"VALUES ('".$this->uniqueid."', '".$this->channel."', '".$userid."', '".$called."', 6, ".$cost.", ".$duration.", CURRENT_TIMESTAMP, ".
						"DATE_SUB(CURRENT_TIMESTAMP, INTERVAL $duration SECOND), '$this->callback_id')";

		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[INSERT CAMPAIGN CALL : ".$QUERY_CALL);
		$this->instance_table -> SQLExec ($this -> DBHandle, $QUERY_CALL);
	}

	public function callingcard_ivr_authenticate($agi,$accountback=0)
	{
		global $currencies_list;

		$authentication 			= false;
		$prompt					= '';
		$language 				= 'en';
		$callerID_enable 			= $this->agiconfig['cid_enable'];
		$this -> auth_through_accountcode	= false;
		$ed					= '0123456789#';
		$this -> add_credit = $res = $retries	= 0;
		$currency				= $this->config['global']['base_currency'];

		// 		  -%-%-%-%-%-%-		FIRST TRY WITH THE CALLERID AUTHENTICATION 	-%-%-%-%-%-%-
		if ($accountback>0) {
			$this->accountcode	= $accountback;
		} elseif ($callerID_enable == 1 && $this->CallerID != "") {
//		if ($callerID_enable==1 && is_numeric($this->CallerID) && $this->CallerID>0) {}
		    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_ENABLE - CID_CONTROL - CID:".$this->CallerID."]");
		    do {
			$repeat = false;
			// NOT USE A LEFT JOIN HERE - In case the callerID is alone without card bound
			$QUERY =    "SELECT cc_callerid.cid, cc_callerid.id_cc_card, cc_callerid.activated, cc_card.credit, ".
				    " cc_card.tariff, cc_card.activated, cc_card.inuse, cc_card.simultaccess, cc_card.typepaid, cc_card.creditlimit, " .
				    " cc_card.language, cc_card.username, removeinterprefix, cc_card.redial, enableexpire, UNIX_TIMESTAMP(expirationdate), " .
				    " expiredays, nbused, UNIX_TIMESTAMP(firstusedate), UNIX_TIMESTAMP(cc_card.creationdate), cc_card.currency, " .
				    " cc_card.lastname, cc_card.firstname, cc_card.email, cc_card.uipass, cc_card.id_campaign, cc_card.id, useralias, " .
				    " cc_card.status, cc_card.voicemail_permitted, cc_card.voicemail_activated, cc_card.restriction, cc_country.countryprefix, " .
				    " cc_card.monitor, phonenumber, warning_threshold, say_rateinitial, say_balance_after_call, margin, id_diller, ".
				    " blacklist, areaprefix, citylength, concat_id, speech2mail, send_text, send_sound, say_dialednumber".
				    " FROM cc_callerid ".
				    " LEFT JOIN cc_card ON cc_callerid.id_cc_card=cc_card.id ".
				    " LEFT JOIN cc_tariffgroup ON cc_card.tariff=cc_tariffgroup.id ".
				    " LEFT JOIN cc_country ON cc_card.country=cc_country.countrycode ".
				    " LEFT JOIN cc_card_concat ON concat_card_id=cc_card.id ".
				    " WHERE cc_callerid.cid LIKE '$this->CallerID'";
			$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, print_r($result,true));
			
			if (!is_array($result)) {
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_CONTROL - NO CALLERID]");

				if ($this -> agiconfig['cid_auto_create_card']==1) {
				    $prompt = "prepaid-auth-fail";
				    $this->tariff = $this->agiconfig['cid_auto_create_card_tariffgroup'];
				    if ($this -> agiconfig['cid_askpincode_ifnot_callerid']==1) {
//					$this -> let_stream_listening($agi);
					$result = $agi-> stream_file('prepaid-welcome', $ed);
					for ($k=0; $k < $this -> agiconfig['number_try']; $k++) {
					    $res_dtmf = $agi->get_data('prepaid-enter_voucher_or_pin_number', 12000, max($this->config['global']['len_voucher'], CARDNUMBER_LENGTH_MAX), '#');
					    $this -> vouchernumber = $res_dtmf ["result"];
					    if ($this -> vouchernumber <= 0)
						if ($k == 9)	{
							$agi-> stream_file($prompt, '#');
							return -2;
						} else	continue;
					    if ($result['result'] > 0 && $k == 0 && chr($result['result']) != '#') {
						$this -> vouchernumber = chr($result['result']) . $this -> vouchernumber;
					    }
					    if (strlen($this -> vouchernumber) != $this->config['global']['len_voucher'] && (strlen($this->vouchernumber) > CARDNUMBER_LENGTH_MAX || strlen($this->vouchernumber) < CARDNUMBER_LENGTH_MIN)) {
						$agi-> stream_file("prepaid-invalid-digits", '#');
						if ($k == 9)
							return -2;
						else	continue;
					    }
					    $username = $this -> vouchernumber;
					    $QUERY = "SELECT bb.voucher, bb.credit, IF(bb.activated='t' AND bb.expirationdate >= CURRENT_TIMESTAMP AND bb.used = '0',bb.activated,'f') activated, bb.tag, bb.currency, bb.expirationdate, bb.usedcardnumber, bb.callplan, aa.voucher
".							"FROM cc_voucher aa, cc_voucher bb WHERE bb.voucher='".$this -> vouchernumber."' AND (aa.usedcardnumber=bb.usedcardnumber OR bb.usedcardnumber IS NULL) ORDER BY aa.usedate DESC LIMIT 1";
					    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
					    if ($result[0][0] == $this->vouchernumber) {
						if (!isset ($currencies_list[strtoupper($result[0][4])][2])) {
						    return -2;
						} elseif ($result[0][2] == 't') {
						    $this -> add_credit = $result[0][1] * $currencies_list[strtoupper($result[0][4])][2];
						    $currency = $result[0][4];
						    $this->tariff = $result[0][7];
						    break;
						} elseif ($result[0][6] >= CARDNUMBER_LENGTH_MIN && $result[0][0] == $result[0][8]) {
						    $username = $result[0][6];
						} elseif ($k == 9)	{
							$agi-> stream_file($prompt, '#');
							return -2;
						} else	continue;
					    }
					    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_CONTROL - NO CALLERID - ASK PIN CODE]");
					    $QUERY = "SELECT id FROM cc_card WHERE username='".$username."' LIMIT 1";
					    $result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
					    if (is_array($result)) {
						$QUERY_FIELS = 'cid, id_cc_card';
						$QUERY_VALUES = "'".$this->CallerID."','{$result[0][0]}'";
						$result = $this->instance_table -> Add_table ($this->DBHandle, $QUERY_VALUES, $QUERY_FIELS, 'cc_callerid');
//						$this -> accountcode = $this->vouchernumber;
//						$this -> vouchernumber = $this -> add_credit;
//						$isused = $simultaccess = $callerID_enable = 0;
						$prompt = '';
						$repeat = true;
						continue 2;
					    }
					    if ($k == 9)
						return -2;
					}
				    }
				    for ($k=0 ; $k <= 100 ; $k++) {
					if ($k == 100) {
					    $this -> debug( WARN, $agi, __FILE__, __LINE__, "ERROR : Impossible to generate a cardnumber not yet used!");
					    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[StreamFile : $prompt]");
					    $agi-> stream_file($prompt, '#');
					    return -2;
					}
					$card_gen	= MDP ($this->agiconfig['cid_auto_create_card_len']);
					$card_alias	= MDP ($this -> config["global"]['len_aliasnumber']);
					$numrow 	= 0;
					$resmax 	= $this->DBHandle -> Execute("SELECT cc_card.username, cc_sip_buddies.name, cc_iax_buddies.name FROM cc_card, cc_sip_buddies, cc_iax_buddies WHERE cc_card.username='$card_gen' OR useralias='$card_alias' OR cc_sip_buddies.name='$card_alias' OR cc_iax_buddies.name='$card_alias'");
					if ($resmax)
					    $numrow	= $resmax -> RecordCount();
					if ($numrow!=0) continue;
					break;
				    }
				    $QUERY = "SELECT cc_country.countrycode, cc_timezone.id FROM cc_country LEFT JOIN cc_timezone ON cc_timezone.countrycode LIKE concat('%',cc_country.countrycode,'%') WHERE '{$this->CallerID}' LIKE concat(countryprefix,'%') ORDER BY countryprefix DESC LIMIT 1";
				    $result = $this->instance_table -> SQLExec ($this -> DBHandle, $QUERY);
				    $country = is_array($result) ? $result[0][0] : "";
				    $timezone = is_array($result) ? $result[0][1] : "";
				    $uipass = MDP_STRING(10);
				    $ttcard = ($this->agiconfig['cid_auto_create_card_typepaid']=="POSTPAID") ? 1 : 0;
				    $this -> credit = ($this -> add_credit) ? $this -> add_credit : $this->agiconfig['cid_auto_create_card_credit'];
//				    $this -> add_credit = 0;
				    $this->creditlimit = $this->agiconfig['cid_auto_create_card_credit_limit'];
				    //CREATE A CARD
				    $QUERY_FIELS = 'username, useralias, uipass, credit, language, tariff, activated, typepaid, creditlimit, inuse, status, currency, country, phone, id_timezone';
				    $QUERY_VALUES = "'$card_gen', '$card_alias', '$uipass', '".$this->credit."', '$language', '".$this->tariff."', 't','$ttcard', '".$this->creditlimit."', '0', '1', '".$currency."', '$country', '{$this->CallerID}', '$timezone'";
				
				    if ($this ->groupe_mode) {
						$QUERY_FIELS .= ", id_group";
						$QUERY_VALUES .= " , '$this->group_id'";
				    }
				
				    $id_card = $this->instance_table -> Add_table ($this->DBHandle, $QUERY_VALUES, $QUERY_FIELS, 'cc_card', 'id');
				    $this -> debug( INFO, $agi, __FILE__, __LINE__, "[CARDNUMBER: $card_gen]:[CARDID CREATED : $result]");
				    if (!$id_card) {
					$agi-> stream_file($prompt, '#');
					return -2;
				    }
				    //CREATE A CARD AND AN INSTANCE IN CC_CALLERID
				    $QUERY_FIELS = 'cid, id_cc_card';
				    $QUERY_VALUES = "'".$this->CallerID."','$id_card'";

				    $result = $this->instance_table -> Add_table ($this->DBHandle, $QUERY_VALUES, $QUERY_FIELS, 'cc_callerid');
				    if (!$result) {
					$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[CALLERID CREATION ERROR TABLE cc_callerid]");
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
					$result = $this->instance_table -> Delete_table ($this -> DBHandle, "id='$id_card' AND username='$card_gen'", "cc_card");
					$agi-> stream_file($prompt, '#');
					return -2;
				    }
				    if ($this -> add_credit) {
					$result = $this->instance_table -> Update_table ($this -> DBHandle, "activated='f', usedcardnumber='".$card_gen."', used=1, usedate=now()", "voucher='".$this->vouchernumber."'", "cc_voucher");
					if (!$result) {
					    $result = $this->instance_table -> Delete_table ($this -> DBHandle, "cid='".$this->CallerID."' AND id_cc_card='$id_card'", "cc_callerid");
					    $result = $this->instance_table -> Delete_table ($this -> DBHandle, "id='$id_card' AND username='$card_gen'", "cc_card");
					    $agi-> stream_file($prompt, '#');
					    return -2;
					}
				    }

//				    $this->fct_say_balance ($agi, $this->credit, 1);
//				    $this->active = $this->status = 1;
//				    $isused = $simultaccess = $callerID_enable = 0;
//				    $this->typepaid = $ttcard;
				    $this->accountcode = $card_gen;
				    $prompt = '';

//				    if ($this->typepaid==1)
//				    	$this->credit = $this->credit + $this->creditlimit;

				    $repeat = true;
				    continue;
				} else {
				    if ($this -> agiconfig['cid_askpincode_ifnot_callerid']==1) {
					    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CID_CONTROL - NO CALLERID - ASK PIN CODE]");
					    $this -> accountcode = '';
					    $callerID_enable = 0;
				    } else {
					    $prompt="prepaid-auth-fail";
				    }
				}
			} else {
				// authenticate OK using the callerID

				$this->credit				=  $result[0][3];
				$this->tariff				=  $result[0][4];
				$this->active				=  $result[0][5];
				$isused 				=  $result[0][6];
				$simultaccess				=  $result[0][7];
				$this->typepaid 			=  $result[0][8];
				$this->creditlimit			=  $result[0][9];
				$language				=  $result[0][10];
				$this->accountcode			=  $result[0][11];
				$this->username 			=  $result[0][11];
				$this->removeinterprefix 		=  $result[0][12];
				$this->redial				=  $result[0][13];
				$this->enableexpire			=  $result[0][14];
				$this->expirationdate			=  $result[0][15];
				$this->expiredays			=  $result[0][16];
				$this->nbused				=  $result[0][17];
				$this->firstusedate			=  $result[0][18];
				$this->creationdate			=  $result[0][19];
				$this->currency 			=  $result[0][20];
				$this->cardholder_lastname		=  $result[0][21];
				$this->cardholder_firstname		=  $result[0][22];
				$this->cardholder_email 		=  $result[0][23];
				$this->cardholder_uipass 		=  $result[0][24];
				$this->id_campaign			=  $result[0][25];
				$this->id_card = $this->card_caller	=  $result[0][26];
				$this->useralias			=  $result[0][27];
				$this->status				=  $result[0][28];
				$this->voicemail			= ($result[0][29] && $result[0][30]) ? 1 : 0;
				$this->restriction			=  $result[0][31];
				$this->countryprefix			=  $result[0][32];
				$this->monitor				=  $result[0][33];
				$this->cidphonenumber			=  $result[0][34];
				$this->warning_threshold	=  is_null($result[0][35]) ? -2 : $result[0][35];
				$this->say_rateinitial			= ($result[0][36]) ? 1 : 0 ;
				$this->say_balance_after_call		= ($result[0][37]) ? 1 : 0 ;
				$this->margin				=  $result[0][38];
				$this->id_diller			=  $result[0][39];
				$blacklist				=  $result[0][40];
				$this->areaprefix			=  $result[0][41];
				$this->citylength			=  $result[0][42];
				$this->caller_concat_id 		=  $result[0][43];
				$this->speech2mail	 		=  $result[0][44];
				$this->send_text			=  $result[0][45];
				$this->send_sound			=  $result[0][46];
				$this->dtmf_destination 		=  $result[0][47];

				if (strlen($language)==2 && !($this->languageselected>=1)) {

					$lg_var_set = 'CHANNEL(language)';
					$agi -> set_variable($lg_var_set, $language);
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SET $lg_var_set $language]");
				}
				$this->current_language = $agi -> get_variable('CHANNEL(language)', true);
				if ($this->typepaid==1)
					$this->credit = $this->credit + $this->creditlimit;

				// CHECK credit < min_credit_2call / you have zero balance
				if (!$this -> enough_credit_to_call()) {
					$prompt = "prepaid-no-enough-credit-stop";
				}
				// CHECK IF CALLERID ACTIVATED OR CARD NOT ACTIVE, CONTACT CUSTOMER SUPPORT
//				elseif ( ($result[0][2] == "t" && $blacklist == 1) || $result[0][2] != "t" || $this->status != "1")
				elseif ($blacklist == 1 || $result[0][2] != "t" || $this->status != "1")
					$prompt = "prepaid-auth-fail";

				// CHECK IF THE CARD IS USED
				if ($isused>0 && ($isused>$simultaccess || $simultaccess==0) && $simultaccess!=1)
					$prompt="prepaid-card-in-use";
				
				// CHECK FOR EXPIRATION  -  enableexpire ( 0 : none, 1 : expire date, 2 : expire days since first use, 3 : expire days since creation)
				if ($this->enableexpire>0) {
					if ($this->enableexpire==1  && $this->expirationdate!='00000000000000' && strlen($this->expirationdate)>5) {
						// expire date
						if (intval($this->expirationdate-time())<0) // CARD EXPIRED :(
						    $prompt = "prepaid-card-expired";

					} elseif ($this->enableexpire==2  && $this->firstusedate!='00000000000000' && strlen($this->firstusedate)>5 && ($this->expiredays>0)) {
						// expire days since first use
						$date_will_expire = $this->firstusedate+(60*60*24*$this->expiredays);
						if (intval($date_will_expire-time())<0) // CARD EXPIRED :(
						    $prompt = "prepaid-card-expired";

					} elseif ($this->enableexpire==3  && $this->creationdate!='00000000000000' && strlen($this->creationdate)>5 && ($this->expiredays>0)) {
						// expire days since creation
						$date_will_expire = $this->creationdate+(60*60*24*$this->expiredays);
						if (intval($date_will_expire-time())<0)	// CARD EXPIRED :(
						    $prompt = "prepaid-card-expired";
					}
					//Update card status to Expired
					if ($prompt == "prepaid-card-expired") {
					    $this->status =5;
					    $QUERY = "UPDATE cc_card SET status='5' WHERE id='".$this->id_card."'";
					    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY UPDATE : $QUERY]");
					    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
					}

				}

				if (strlen($prompt)>0 || $blacklist) {
				    if ($blacklist == 0) {
					$this -> let_stream_listening($agi);
					$agi-> stream_file($prompt, '#'); // Added because was missing the prompt
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[ERROR CHECK CARD : $prompt (cardnumber:".$this->cardnumber.")]");
					
					if ($this->agiconfig['jump_voucher_if_min_credit']==1 && !$this -> enough_credit_to_call()) {
						
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - refill_card_withvoucher] ");
						$vou_res = $this -> refill_card_with_voucher($agi,2);
						if ($vou_res==1) {
							return 0;
						} else {
							$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - refill_card_withvoucher fail] ");
						}
					}
				    }
					if ($prompt == "prepaid-no-enough-credit-stop" && $this->agiconfig['notenoughcredit_cardnumber']==1) {
						$this->accountcode = '';
						$callerID_enable = 0;
						$this->agiconfig['cid_auto_assign_card_to_cid'] = 0;
						
						if ($this->agiconfig['notenoughcredit_assign_newcardnumber_cid']==1) { 
							$this -> ask_other_cardnumber = 1;
							$this -> update_callerid = 1;
						}
					} elseif ($prompt == "prepaid-card-expired") {
					    $this -> accountcode='';
					    $callerID_enable=0;
					    $this -> ask_other_cardnumber = 1;
					    $this -> update_callerid = 1;
					} else {
						return -2;
					}
				} else {
					if ($this -> agiconfig['say_balance_after_auth'] == 0 && (($this -> warning_threshold >= 0 && $this -> credit < $this -> warning_threshold) || $this -> warning_threshold == -1)) {
						$this -> agiconfig['say_balance_after_auth']	= 1;
					}
					if ($this -> agiconfig['say_rateinitial'] == 0)
						$this -> agiconfig['say_rateinitial']		= $this->say_rateinitial;
					if ($this -> agiconfig['say_balance_after_call'] == 0)
						$this -> agiconfig['say_balance_after_call']	= $this->say_balance_after_call;
					$authentication = true;
				}

			} // elseif We -> found a card for this callerID
		    } while ($repeat);
		} else {
			// NO CALLERID AUTHENTICATION
			$callerID_enable=0;
// Тут можно присвоить известный номер:
//			$this -> CID_handover = '';
		}
		// 		 -%-%-%-%-%-%-		CHECK IF WE CAN AUTHENTICATE THROUGH THE "ACCOUNTCODE" 	-%-%-%-%-%-%-
		
		$prompt_entercardnum = "prepaid-enter-pin-number";
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, ' - Account code ::> '.$this -> accountcode);
		if (strlen ($this->accountcode)>=1 && !$authentication) {
			for ($i=0;$i<=1;$i++) {
				$this->username = $this->cardnumber = $this->accountcode;
				if ($callerID_enable!=1 || !is_numeric($this->CallerID) || $this->CallerID<=0 || $accountback>0) {
					$QUERY = "SELECT credit, tariff, cc_card.activated, inuse, simultaccess, typepaid, creditlimit, cc_sip_buddies.language, removeinterprefix," .
								" redial, enableexpire, UNIX_TIMESTAMP(expirationdate), expiredays, nbused, UNIX_TIMESTAMP(firstusedate)," .
								" UNIX_TIMESTAMP(cc_card.creationdate), currency, lastname, firstname, email, uipass, id_campaign, cc_card.id," .
								" useralias, status, voicemail_permitted, voicemail_activated, restriction, countryprefix, monitor, " .
								" cc_sip_buddies.warning_threshold, cc_sip_buddies.say_rateinitial, cc_sip_buddies.say_balance_after_call," .
								" margin, id_diller, cc_callerid.activated, blacklist, areaprefix, citylength, speech2mail, send_text, send_sound" .
								" FROM cc_card" .
								" LEFT JOIN cc_tariffgroup ON tariff = cc_tariffgroup.id" .
								" LEFT JOIN cc_country ON country = countrycode" .
								" LEFT JOIN cc_sip_buddies ON cc_sip_buddies.id_cc_card = cc_card.id AND cc_sip_buddies.name = '{$this->src_peername}'" .
								" LEFT JOIN cc_callerid ON cid LIKE '$this->CallerID' AND (cc_callerid.id_cc_card = cc_card.id OR cc_callerid.id_cc_card = -1)" .
								" WHERE cc_card.username = '".$this->cardnumber."' LIMIT 1";
					$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, ' - Retrieve account info SQL ::> '.$QUERY);

					if ( !is_array($result)) {
						$prompt="prepaid-auth-fail";
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
						$res = -2;
						break;
					}
					$this->credit 			= $result[0][0];
					$this->tariff 			= $result[0][1];
					$this->active 			= $result[0][2];
					$isused 			= $result[0][3];
					$simultaccess 			= $result[0][4];
					$this->typepaid 		= $result[0][5];
					$this->creditlimit 		= $result[0][6];
//					$language 			= $result[0][7];
					$this->current_language		= $agi -> get_variable('CHANNEL(language)', true);
					$this->removeinterprefix 	= $result[0][8];
					$this->redial 			= $result[0][9];
					$this->enableexpire 		= $result[0][10];
					$this->expirationdate 		= $result[0][11];
					$this->expiredays 		= $result[0][12];
					$this->nbused 			= $result[0][13];
					$this->firstusedate 		= $result[0][14];
					$this->creationdate 		= $result[0][15];
					$this->currency 		= $result[0][16];
					$this->cardholder_lastname 	= $result[0][17];
					$this->cardholder_firstname	= $result[0][18];
					$this->cardholder_email 	= $result[0][19];
					$this->cardholder_uipass 	= $result[0][20];
					$this->id_campaign  		= $result[0][21];
					$this->id_card			= $result[0][22];
					$this->useralias 		= $result[0][23];
					$this->status 			= $result[0][24];
					$this->voicemail		=($result[0][25] && $result[0][26]) ? 1 : 0;
					$this->restriction		= $result[0][27];
					$this->countryprefix		= $result[0][28];
					$this->monitor 			= $result[0][29];
					$this->warning_threshold =is_null($result[0][30]) ? -2 : $result[0][30];
					$this->say_rateinitial		=($result[0][31]) ? 1 : 0 ;
					$this->say_balance_after_call	=($result[0][32]) ? 1 : 0 ;
					$this->margin			= $result[0][33];
					$this->id_diller		= $result[0][34];
					$this->cidactivated		= $result[0][35];
					$blacklist			= $result[0][36];
					$this->areaprefix		= $result[0][37];
					$this->citylength		= $result[0][38];
					$this->speech2mail		= $result[0][39];
					$this->send_text		= $result[0][40];
					$this->send_sound		= $result[0][41];
					
					if ($this->typepaid==1) $this->credit = $this->credit + $this->creditlimit;
				}
/**
				if (strlen($language)==2 && !($this->languageselected>=1)) {

					if ($this->agiconfig['asterisk_version'] == "1_2") {
						$lg_var_set = 'LANGUAGE()';
					} else {
						$lg_var_set = 'CHANNEL(language)';
					}
					$agi -> set_variable($lg_var_set, $language);
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SET $lg_var_set $language]");
					$this -> current_language = $language;
				}
**/
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[credit=".$this->credit." :: tariff=".$this->tariff." :: status=".$this->status." :: isused=$isused :: simultaccess=$simultaccess :: typepaid=".$this->typepaid." :: creditlimit=$this->creditlimit :: language=$language]");
				
				$prompt = '';
				// CHECK activated=t / CARD NOT ACTIVE, CONTACT CUSTOMER -%-%- AND CHECK IF THE CALLERID IS CORRECT FOR THIS CARD	-%-%-%-
//				if ( $this->status != "1" || ($this->cidactivated == "t" && $blacklist == 1) || ($this->agiconfig['callerid_authentication_over_cardnumber']==1 && is_null($this->cidactivated))) {
				if ( $this->status != "1" || $blacklist == 1 || ($this->agiconfig['callerid_authentication_over_cardnumber']==1 && is_null($this->cidactivated))) {
					$prompt = "prepaid-auth-fail";	// not expired but inactive.. probably not yet sold.. find better prompt
					$res = -2;
					break;
				}
				// CHECK IF THE CARD IS USED
				if ($isused>0 && ($isused>$simultaccess || $simultaccess==0) && $simultaccess!=1)
					$prompt="prepaid-card-in-use";
				// CHECK FOR EXPIRATION  -  enableexpire ( 0 : none, 1 : expire date, 2 : expire days since first use, 3 : expire days since creation)
				if ($this->enableexpire>0) {
					if ($this->enableexpire==1  && $this->expirationdate!='00000000000000' && strlen($this->expirationdate)>5) {
						// expire date
						if (intval($this->expirationdate-time())<0) // CARD EXPIRED :(
						$prompt = "prepaid-card-expired";
					} elseif ($this->enableexpire==2  && $this->firstusedate!='00000000000000' && strlen($this->firstusedate)>5 && ($this->expiredays>0)) {
					// expire days since first use
						$date_will_expire = $this->firstusedate+(60*60*24*$this->expiredays);
						if (intval($date_will_expire-time())<0) // CARD EXPIRED :(
						$prompt = "prepaid-card-expired";

					} elseif ($this->enableexpire==3  && $this->creationdate!='00000000000000' && strlen($this->creationdate)>5 && ($this->expiredays>0)) {
						// expire days since creation
						$date_will_expire = $this->creationdate+(60*60*24*$this->expiredays);
						if (intval($date_will_expire-time())<0)	// CARD EXPIRED :(
							$prompt = "prepaid-card-expired";
					}
					if ($prompt == "prepaid-card-expired") {
					    $this->status =5;
					    $QUERY = "UPDATE cc_card SET status='5' WHERE id='".$this->id_card."'";
					    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY UPDATE : $QUERY]");
					    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
					}
				}
				if ($i==0) {
					$this->card_caller = $this->id_card;
					if (strlen($this->dnid) >= 1 && $accountback == 0) {
						$did = $this->dnid;
						if ($this->removeinterprefix)
							$this->extension = $did = $this -> apply_rules($did);
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "removeinterprefix=".$this->removeinterprefix."   / did=".$did);
						if ($this->myprefix == "")
							$this->extension = $did = $this -> apply_add_countryprefixto($did);
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "removeinterprefix=".$this->removeinterprefix."   / did=".$did);
						$QUERY = "SELECT username FROM cc_did, cc_card WHERE did LIKE '$did' AND cc_did.activated=1 AND cc_card.id=iduser LIMIT 1";
//						$QUERY = "SELECT username FROM cc_did, cc_card WHERE did LIKE '$did' AND cc_did.activated=1 LIMIT 1";
						$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
						if (is_array($result)) {
							$this->accountcode = $result[0][0];
							$this->agiconfig['number_try'] = 1;
							continue;
						}
					}
				}
				// CHECK credit > min_credit_2call / you have zero balance
/**				if (!$this -> enough_credit_to_call()) {
					$prompt = "prepaid-no-enough-credit-stop";
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "[credit=".$this->credit." :: tariff=".$this->tariff." :: status=".$this->status." :: isused=$isused :: simultaccess=$simultaccess :: typepaid=".$this->typepaid." :: creditlimit=$this->creditlimit :: language=$language]");
				}**/
				if (strlen($prompt)>0) {
					$this -> let_stream_listening($agi);
					$agi -> stream_file($prompt, '#'); // Added because was missing the prompt
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[ERROR CHECK CARD : $prompt (cardnumber:".$this->cardnumber.")]");
					
					if ($this->agiconfig['jump_voucher_if_min_credit']==1 && !$this -> enough_credit_to_call()) {
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - refill_card_withvoucher] ");
						$vou_res = $this -> refill_card_with_voucher($agi,2);
						if ($vou_res==1) {
							return 0;
						} else {
							$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOTENOUGHCREDIT - refill_card_withvoucher fail] ");
						}
					}
					
					if ($prompt == "prepaid-no-enough-credit-stop" && $this->agiconfig['notenoughcredit_cardnumber']==1) {
						$this->accountcode = '';
						$callerID_enable = 0;
						$this->agiconfig['cid_auto_assign_card_to_cid']=0;
						
						if ($this->agiconfig['notenoughcredit_assign_newcardnumber_cid']==1) {
							$this -> ask_other_cardnumber = 1;
							$this -> update_callerid=1;
						}
					} elseif ($prompt == "prepaid-card-expired") {
					    $this -> accountcode = '';
					    $callerID_enable = 0;
					    $this -> ask_other_cardnumber = 1;
					    $this -> update_callerid = 1;
					} else {
						return -2;
					}
				} else {
					if ($this -> agiconfig['say_balance_after_auth'] == 0 && (($this -> warning_threshold >= 0 && $this -> credit < $this -> warning_threshold) || $this -> warning_threshold == -1)) {
						$this -> agiconfig['say_balance_after_auth']	= 1;
						if ($this->agiconfig['use_dnid']) {
							$this->auth_through_accountcode = true;
						}
					}
					if ($this -> agiconfig['say_rateinitial'] == 0)
						$this -> agiconfig['say_rateinitial']		= $this->say_rateinitial;
					if ($this -> agiconfig['say_balance_after_call'] == 0)
						$this -> agiconfig['say_balance_after_call']	= $this->say_balance_after_call;
					$authentication = true;
				}
				break;
			} // For end
			
		}


		if ($callerID_enable==0 && !$authentication) {
			$this -> let_stream_listening($agi);

			// IF NOT PREVIOUS WE WILL ASK THE CARDNUMBER AND AUTHENTICATE ACCORDINGLY
			for ($retries = 0; $retries < 3; $retries++) {
				
				if (($retries>0) && (strlen($prompt)>0)) {
					$agi-> stream_file($prompt, '#');
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "Streamfile : ".strtoupper($prompt));
				}
				if ($res < 0) {
//					$res = -1;
					break;
				}

				$res = 0;
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "Requesting DTMF, CARDNUMBER_LENGTH_MAX ".CARDNUMBER_LENGTH_MAX);
				$res_dtmf = $agi->get_data($prompt_entercardnum, 6000, CARDNUMBER_LENGTH_MAX);
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "RES DTMF : ".$res_dtmf ["result"]);
				$this->cardnumber = $res_dtmf ["result"];

				if ($this->CC_TESTING) $this->cardnumber="2222222222";
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "CARDNUMBER ::> ".$this->cardnumber);

				if ( !isset($this->cardnumber) || strlen($this->cardnumber) == 0) {
					$prompt = "prepaid-no-card-entered";
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
					continue;
				}

				if ( strlen($this->cardnumber) > CARDNUMBER_LENGTH_MAX || strlen($this->cardnumber) < CARDNUMBER_LENGTH_MIN) {
					$prompt = "prepaid-invalid-digits";
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
					continue;
				}
				$this -> accountcode = $this -> username = $this -> cardnumber;
				
				$QUERY = "SELECT credit, tariff, activated, inuse, simultaccess, typepaid, creditlimit, language, removeinterprefix, redial, " .
							" enableexpire, UNIX_TIMESTAMP(expirationdate), expiredays, nbused, UNIX_TIMESTAMP(firstusedate), " .
							" UNIX_TIMESTAMP(cc_card.creationdate), cc_card.currency, cc_card.lastname, cc_card.firstname, cc_card.email, " .
							" cc_card.uipass, cc_card.id, cc_card.id_campaign, cc_card.id, useralias, status, voicemail_permitted, " .
							" voicemail_activated, cc_card.restriction, cc_country.countryprefix, cc_card.monitor, areaprefix, citylength, speech2mail, send_text, send_sound " .
							" FROM cc_card " .
							" LEFT JOIN cc_tariffgroup ON tariff=cc_tariffgroup.id " .
							" LEFT JOIN cc_country ON cc_card.country=cc_country.countrycode ".
							" WHERE username='".$this->cardnumber."'";
				
				$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, print_r($result,true));

				if ( !is_array($result)) {
					$prompt="prepaid-auth-fail";
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
					continue;
				} else {
					// WE ARE GOING TO CHECK IF THE CALLERID IS CORRECT FOR THIS CARD
					if ($this->agiconfig['callerid_authentication_over_cardnumber']==1) {

						if (!is_numeric($this->CallerID) && $this->CallerID<=0) {
							$prompt="prepaid-auth-fail";
							$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
							continue;
						}

						$QUERY = " SELECT cid, id_cc_card, activated FROM cc_callerid "
								." WHERE cc_callerid.cid LIKE '$this->CallerID' AND cc_callerid.id_cc_card='".$result[0][23]."'";

						$result_check_cid = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__, print_r($result_check_cid,true));
						
						if ( !is_array($result_check_cid)) {
							$prompt="prepaid-auth-fail";
							$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
							continue;
						}
					}
				}

				$this->credit 				= $result[0][0];
				$this->tariff 				= $result[0][1];
				$this->active 				= $result[0][2];
				$isused 				= $result[0][3];
				$simultaccess				= $result[0][4];
				$this->typepaid 			= $result[0][5];
				$this->creditlimit			= $result[0][6];
				$language				= $result[0][7];
				$this->removeinterprefix		= $result[0][8];
				$this->redial				= $result[0][9];
				$this->enableexpire			= $result[0][10];
				$this->expirationdate			= $result[0][11];
				$this->expiredays			= $result[0][12];
				$this->nbused				= $result[0][13];
				$this->firstusedate			= $result[0][14];
				$this->creationdate			= $result[0][15];
				$this->currency 			= $result[0][16];
				$this->cardholder_lastname		= $result[0][17];
				$this->cardholder_firstname		= $result[0][18];
				$this->cardholder_email 		= $result[0][19];
				$this->cardholder_uipass 		= $result[0][20];
				$the_card_id 				= $result[0][21];
				$this->id_campaign			= $result[0][22];
				$this->id_card = $this->card_caller	= $result[0][23];
				$this->useralias			= $result[0][24];
				$this->status 				= $result[0][25];
				$this->voicemail 			=($result[0][26] && $result[0][27]) ? 1 : 0;
				$this->restriction 			= $result[0][28];
				$this->countryprefix 			= $result[0][29];
				$this->monitor 				= $result[0][30];
				$this->areaprefix			= $result[0][31];
				$this->citylength			= $result[0][32];
				$this->speech2mail			= $result[0][33];
				$this->send_text			= $result[0][34];
				$this->send_sound			= $result[0][35];
				
				if ($this->typepaid==1) $this->credit = $this->credit + $this->creditlimit;
				
				if (strlen($language)==2  && !($this->languageselected>=1)) {
					if ($this->agiconfig['asterisk_version'] == "1_2") {
						$lg_var_set = 'LANGUAGE()';
					} else {
						$lg_var_set = 'CHANNEL(language)';
					}
					$agi -> set_variable($lg_var_set, $language);
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SET $lg_var_set $language]");
				}
				
				$prompt = '';
				
				// CHECK credit > min_credit_2call / you have zero balance
				if ( !$this -> enough_credit_to_call() ) $prompt = "prepaid-no-enough-credit-stop";
//$this -> debug( ERROR, $agi, __FILE__, __LINE__, "========================================================== prepaid-no-enough-credit-stop");
				// CHECK activated=t / CARD NOT ACTIVE, CONTACT CUSTOMER SUPPORT
				if ( $this->status != "1") 	$prompt = "prepaid-auth-fail";	// not expired but inactive.. probably not yet sold.. find better prompt
				
				// CHECK IF THE CARD IS USED
				if ($isused>0 && ($isused>$simultaccess || $simultaccess==0) && $simultaccess!=1)
					$prompt="prepaid-card-in-use";
				
				// CHECK FOR EXPIRATION  -  enableexpire ( 0 : none, 1 : expire date, 2 : expire days since first use, 3 : expire days since creation)
				if ($this->enableexpire>0) {
					
					if ($this->enableexpire==1  && $this->expirationdate!='00000000000000' && strlen($this->expirationdate)>5) {
						// expire date
						if (intval($this->expirationdate-time())<0) // CARD EXPIRED :(
						$prompt = "prepaid-card-expired";

					} elseif ($this->enableexpire==2  && $this->firstusedate!='00000000000000' && strlen($this->firstusedate)>5 && ($this->expiredays>0)) {
						// expire days since first use
						$date_will_expire = $this->firstusedate+(60*60*24*$this->expiredays);
						if (intval($date_will_expire-time())<0) // CARD EXPIRED :(
						$prompt = "prepaid-card-expired";

					} elseif ($this->enableexpire==3  && $this->creationdate!='00000000000000' && strlen($this->creationdate)>5 && ($this->expiredays>0)) {
						// expire days since creation
						$date_will_expire = $this->creationdate+(60*60*24*$this->expiredays);
						if (intval($date_will_expire-time())<0)	// CARD EXPIRED :(
						$prompt = "prepaid-card-expired";
					}

					//Update card status to Expired
					if ($prompt == "prepaid-card-expired") {
					    $this->status =5;
					    $QUERY = "UPDATE cc_card SET status='5' WHERE id='".$this->id_card."'";
					    $this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY UPDATE : $QUERY]");
					    $result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
					}
				}
				
				//CREATE AN INSTANCE IN CC_CALLERID
				if ($this->agiconfig['cid_enable']==1 && $this->agiconfig['cid_auto_assign_card_to_cid']==1 && is_numeric($this->CallerID) && $this->CallerID>0 && $this -> ask_other_cardnumber!=1 && $this->update_callerid!=1) {

					$QUERY = "SELECT count(*) FROM cc_callerid WHERE id_cc_card='$the_card_id'";
					$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY, 1);
					
					// CHECK IF THE AMOUNT OF CALLERID IS LESS THAN THE LIMIT
					if ($result[0][0] < $this->config["webcustomerui"]['limit_callerid']) {
						
						$QUERY_FIEDLS = 'cid, id_cc_card';
						$QUERY_VALUES = "'".$this->CallerID."','$the_card_id'";
						
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CREATE AN INSTANCE IN CC_CALLERID -  QUERY_VALUES:$QUERY_VALUES, QUERY_FIELS:$QUERY_FIELDS]");
						$result = $this->instance_table -> Add_table ($this->DBHandle, $QUERY_VALUES, $QUERY_FIELDS, 'cc_callerid');
						
						if (!$result) {
							$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[CALLERID CREATION ERROR TABLE cc_callerid]");
							$prompt="prepaid-auth-fail";
							$this -> debug( DEBUG, $agi, __FILE__, __LINE__, strtoupper($prompt));
							$agi-> stream_file($prompt, '#');
							return -2;
						}
					} else {
						$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NOT ADDING NEW CID IN CC_CALLERID : CID LIMIT]");
					}
				}

				//UPDATE THE CARD ASSIGN TO THIS CC_CALLERID
				if ($this->update_callerid==1 && strlen($this->CallerID)>1 && $this -> ask_other_cardnumber==1) {
					$this -> ask_other_cardnumber=0;
					$QUERY = "UPDATE cc_callerid SET id_cc_card='$the_card_id' WHERE cid LIKE '$this->CallerID'";
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[QUERY UPDATE : $QUERY]");
					$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
				}

				if (strlen($prompt)>0) {
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[ERROR CHECK CARD : $prompt (cardnumber:".$this->cardnumber.")]");
					$res = -2;
					break;
				}
				break;
			}//end for
			
		} elseif (!$authentication) {
			$res = -2;
		}
		
		if (($retries < 3) && $res==0) {
			
			$this -> callingcard_acct_start_inuse($agi,1);
			
			if ($this->agiconfig['say_balance_after_auth']==1 && !$this->auth_through_accountcode && $accountback == 0) {
				
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[A2Billing] SAY BALANCE : $this->credit \n");
				$this -> fct_say_balance ($agi, $this->credit);
			}

		} elseif ($res == -2 ) {
			$agi-> stream_file($prompt, '#');
		} else {
			$res = -1;
		}

		$this->margintotal = $this->margin_calculate();
		return $res;
	}


	public function callingcard_ivr_authenticate_light (&$error_msg,$simbalance=0) {
		
		$res=0;
		$QUERY = "SELECT credit, tariff, activated, inuse, simultaccess, typepaid, creditlimit, language, removeinterprefix, redial, enableexpire, " .
					" UNIX_TIMESTAMP(expirationdate), expiredays, nbused, UNIX_TIMESTAMP(firstusedate), UNIX_TIMESTAMP(cc_card.creationdate), " .
					" cc_card.currency, cc_card.lastname, cc_card.firstname, cc_card.email, cc_card.uipass, cc_card.id_campaign, status, " .
					" voicemail_permitted, voicemail_activated, cc_card.restriction, cc_country.countryprefix, cc_card.monitor, cc_card.id, areaprefix, citylength, speech2mail, send_text, send_sound " .
					" FROM cc_card LEFT JOIN cc_tariffgroup ON tariff=cc_tariffgroup.id " .
					" LEFT JOIN cc_country ON cc_card.country=cc_country.countrycode ".
					" WHERE username='".$this->cardnumber."'";
		$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
		
		if ( !is_array($result)) {
			$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Authentication Failed !!!").'</b></font><br>';
			return 0;
		}
		//If we receive a positive value from the rate simulator, we simulate with that initial balance. If we receive <=0 we use the value retrieved from the account
		if ($simbalance>0) {
			$this -> credit 	= $simbalance;
		} else {
			$this->credit 		= $result[0][0];
		}
		$this->tariff 			= $result[0][1];
		$this->active 			= $result[0][2];
		$isused 			= $result[0][3];
		$simultaccess 			= $result[0][4];
		$this->typepaid 		= $result[0][5];
		$this->creditlimit 		= $result[0][6];
		$language 			= $result[0][7];
		$this->removeinterprefix 	= $result[0][8];
		$this->redial 			= $result[0][9];
		$this->enableexpire 		= $result[0][10];
		$this->expirationdate 		= $result[0][11];
		$this->expiredays 		= $result[0][12];
		$this->nbused 			= $result[0][13];
		$this->firstusedate 		= $result[0][14];
		$this->creationdate 		= $result[0][15];
		$this->currency 		= $result[0][16];
		$this->cardholder_lastname 	= $result[0][17];
		$this->cardholder_firstname 	= $result[0][18];
		$this->cardholder_email 	= $result[0][19];
		$this->cardholder_uipass 	= $result[0][20];
		$this->id_campaign 		= $result[0][21];
		$this->status 			= $result[0][22];
		$this->voicemail 		=($result[0][23] && $result[0][24]) ? 1 : 0;
		$this->restriction 		= $result[0][25];
		$this->countryprefix 		= $result[0][26];
		$this->monitor 			= $result[0][27];
		$this->card_id 			= $result[0][28];
		$this->areaprefix		= $result[0][29];
		$this->citylength		= $result[0][30];
		$this->speech2mail		= $result[0][31];
		$this->send_text		= $result[0][32];
		$this->send_sound		= $result[0][33];

		if ($this->typepaid==1)
			$this->credit = $this->credit + $this->creditlimit;
		
		// CHECK IF ENOUGH CREDIT TO CALL
		if ( !$this->enough_credit_to_call()) {
			$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Not enough credit to call !!!").'</b></font><br>';
			return 0;
		}

		// CHECK activated=t / CARD NOT ACTIVE, CONTACT CUSTOMER SUPPORT
		if ( $this->status != "1") {
			$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Card is not active!!!").'</b></font><br>';
			return 0;
		}

		// CHECK IF THE CARD IS USED
		if ($isused>0 && ($isused>$simultaccess || $simultaccess==0) && $simultaccess!=1) {
			$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Card is actually in use!!!").'</b></font><br>';
			return 0;
		}

		// CHECK FOR EXPIRATION  -  enableexpire ( 0 : none, 1 : expire date, 2 : expire days since first use, 3 : expire days since creation)
		if ($this->enableexpire>0) {
			if ($this->enableexpire==1  && $this->expirationdate!='00000000000000' && strlen($this->expirationdate)>5) {
				// expire date
				if (intval($this->expirationdate-time())<0) { // CARD EXPIRED :(
					$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Card have expired!!!").'</b></font><br>';
					return 0;
				}

			} elseif ($this->enableexpire==2  && $this->firstusedate!='00000000000000' && strlen($this->firstusedate)>5 && ($this->expiredays>0)) {
				// expire days since first use
				$date_will_expire = $this->firstusedate+(60*60*24*$this->expiredays);
				if (intval($date_will_expire-time())<0) { // CARD EXPIRED :(
				$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Card have expired!!!").'</b></font><br>';
				return 0;
			}

			} elseif ($this->enableexpire==3  && $this->creationdate!='00000000000000' && strlen($this->creationdate)>5 && ($this->expiredays>0)) {
				// expire days since creation
				$date_will_expire = $this->creationdate+(60*60*24*$this->expiredays);
				if (intval($date_will_expire-time())<0) { // CARD EXPIRED :(
					$error_msg = '<font face="Arial, Helvetica, sans-serif" size="2" color="red"><b>'.gettext("Error : Card have expired!!!").'</b></font><br>';
					return 0;
				}
			}
		}

		return 1;
	}
	
	
	/*
	 * Function deck_switch
	 * to switch the Callplan from a customer : callplan_deck_minute_threshold
	 *
	 */
	public function deck_switch($agi)
	{
		if (strpos($this->agiconfig['callplan_deck_minute_threshold'], ',') === false) 
			return false;
		
		$arr_splitable_deck = explode(",", $this->agiconfig['callplan_deck_minute_threshold']);
		
		foreach ($arr_splitable_deck as $arr_value) {
		
			$arr_value = trim ($arr_value);
			$arr_value_explode = explode(":", $arr_value,2);
			if (count($arr_value_explode) > 1) {
				if (is_numeric($arr_value_explode[0]) && is_numeric($arr_value_explode[1]) ) {
					$arr_value_deck_callplan[] = $arr_value_explode[0];
					$arr_value_deck_minute[] = $arr_value_explode[1];
				}
			} else {
				if (is_numeric($arr_value)) {
					$arr_value_deck_callplan[] = $arr_value;
					$arr_value_deck_minute[] = 0;
				}
			}
		}
		// We have $arr_value_deck_callplan with 1, 2, 3 & we have $arr_value_deck_minute with 5, 1, 0
		if (count($arr_value_deck_callplan) == 0)
			return false;
		
		$QUERY = "SELECT sum(sessiontime), count(*) FROM cc_call WHERE card_id='".$this->id_card."'";
		$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[DECK SWITCH - Start]".print_r($result, true));
		$sessiontime_for_card = $result[0][0];
		$calls_for_card = $result[0][1];
		
		$find_deck = false;
		$accumul_seconds = 0;
		for ($ind_deck = 0 ; $ind_deck < count($arr_value_deck_callplan) ; $ind_deck++) {
			$accumul_seconds += $arr_value_deck_minute[$ind_deck];
			
			if ($arr_value_deck_callplan[$ind_deck] == $this->tariff) {
				if (is_numeric($arr_value_deck_callplan[$ind_deck+1])) {
					$find_deck = true;
				} else {
					$find_deck = false;
				}
				break;
			}
		}
		
		$ind_deck = $ind_deck + 1;
		if ($find_deck) {
			// Check if the sum sessiontime call is more the the accumulation of the parameters seconds & that the amount of calls made is upper than the deck level
			if (($sessiontime_for_card  > $accumul_seconds) && ($calls_for_card > $ind_deck)) {
				// UPDATE CARD
				$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[DECK SWITCH] : UPDATE CARD TO CALLPLAN ID = ".$arr_value_deck_callplan[$ind_deck]);
				$QUERY = "UPDATE cc_card SET tariff='".$arr_value_deck_callplan[$ind_deck]."' WHERE id='".$this->id_card."'";
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
				
				$this->tariff = $arr_value_deck_callplan[$ind_deck];
			}
		}
		return true;
	}
	
	
	/*
	 * Function DbConnect
	 * Returns: true / false if connection has been established
	 */
	public function DbConnect()
	{
		$ADODB_CACHE_DIR = '/tmp';
		/*	$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;	*/
//		require_once('adodb/adodb.inc.php');

		if ($this->config['database']['dbtype'] == "postgres") {
			$datasource = 'pgsql://'.$this->config['database']['user'].':'.$this->config['database']['password'].'@'.$this->config['database']['hostname'].'/'.$this->config['database']['dbname'];
		} else {
			$datasource = 'mysqli://'.$this->config['database']['user'].':'.$this->config['database']['password'].'@'.$this->config['database']['hostname'].'/'.$this->config['database']['dbname'];
		}
		$this->DBHandle = NewADOConnection($datasource);
//		if (!$this->DBHandle) die("Connection failed");
//		
		if (!$this->DBHandle) {
			die("Connection failed");
		}
//		if ($this->config['database']['dbtype'] == "mysqli") {
		if ($this->config['database']['dbtype'] == "mysql") {
			$this->DBHandle -> Execute("SET AUTOCOMMIT=1");
			$this->DBHandle -> Execute("SET NAMES 'UTF8'");
		}
		
		return true;
	}
	
	/*
     * Function DbReConnect
     * Returns: true / false if connection has been established
     */
	public function DbReConnect($agi)
	{
		$res = $this->DBHandle -> Execute("select 1");
		if (!$res) {
		   	$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[DB CONNECTION LOST] - RECONNECT ATTEMPT");	
		   	$this->DBHandle -> Close();
			if ($this->config['database']['dbtype'] == "postgres") {
				$datasource = 'pgsql://'.$this->config['database']['user'].':'.$this->config['database']['password'].'@'.$this->config['database']['hostname'].'/'.$this->config['database']['dbname'];
			} else {
            	$datasource = 'mysqli://'.$this->config['database']['user'].':'.$this->config['database']['password'].'@'.$this->config['database']['hostname'].'/'.$this->config['database']['dbname'];
			}
			$count=1;$sleep=1;
			while ((!$res)&&($count<5)) {
				$this->DBHandle = NewADOConnection($datasource);
				if (!$this->DBHandle) {
					$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[DB CONNECTION LOST]- RECONNECT FAILED ,ATTEMPT $count sleep for $sleep ");
					$count+=1;$sleep=$sleep*2;
					sleep($sleep);
				} else { 
					break;
				}
			}
			if (!$this->DBHandle) {
				$this -> debug( FATAL, $agi, __FILE__, __LINE__, "[DB CONNECTION LOST] CDR NOT POSTED");
				die("Reconnection failed");
			}
			if ($this->config['database']['dbtype'] == "mysql") {
				$this->DBHandle -> Execute('SET AUTOCOMMIT=1');
				$this->DBHandle -> Execute("SET NAMES 'UTF8'");
			}
			
			$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[NO DB CONNECTION] - RECONNECT OK]");

		} else {
			$res -> Close();
		}
		return true;
	}

	/*
	 * Function DbDisconnect
	 */
	public function DbDisconnect()
	{
		$this -> DBHandle -> disconnect();
	}


	/*
	 * function splitable_data
	 * used by parameter like interval_len_cardnumber : 8-10, 12-18, 20
	 * it will build an array with the different interval
	 */
	public function splitable_data ($splitable_value)
	{
		$arr_splitable_value = explode(",", $splitable_value);
		foreach ($arr_splitable_value as $arr_value) {
			$arr_value = trim ($arr_value);
			$arr_value_explode = explode("-", $arr_value,2);
			if (count($arr_value_explode)>1) {
				if (is_numeric($arr_value_explode[0]) && is_numeric($arr_value_explode[1]) && $arr_value_explode[0] < $arr_value_explode[1] ) {
					for ($kk=$arr_value_explode[0];$kk<=$arr_value_explode[1];$kk++) {
						$arr_value_to_import[] = $kk;
					}
				} elseif (is_numeric($arr_value_explode[0])) {
					$arr_value_to_import[] = $arr_value_explode[0];
				} elseif (is_numeric($arr_value_explode[1])) {
					$arr_value_to_import[] = $arr_value_explode[1];
				}

			} else {
				$arr_value_to_import[] = $arr_value_explode[0];
			}
		}

		$arr_value_to_import = array_unique($arr_value_to_import);
		sort($arr_value_to_import);
		return $arr_value_to_import;
	}

	public function save_redial_number($agi, $number)
	{
		if (($this->mode == 'did') || ($this->mode == 'callback')) {
		    return;
		}
		$QUERY = "UPDATE cc_card SET redial = '{$number}' WHERE username='".$this->accountcode."'";
		$result = $this->instance_table -> SQLExec ($this->DBHandle, $QUERY, 0);
		$this -> debug( DEBUG, $agi, __FILE__, __LINE__, "[SAVING DESTINATION FOR REDIAL: SQL: {$QUERY}]:[result: {$result}]");
	}
	
	public function run_dial($agi, $dialstr)
	{
		$dialstr = $this -> format_parameters ($dialstr);
		
		// Run dial command
		if (stripos($dialstr,"QUEUE ") === 0) {
			$queue = explode(",",$dialstr);
			if (count($queue)===1) {
				$queuename = substr($queue[0],6);
				$QUERY = "SELECT argument_options,argument_timeout,argument_gosub FROM cc_queues WHERE name='$queuename' LIMIT 1";
				$result = $this -> instance_table -> SQLExec ($this->DBHandle, $QUERY);
				if (is_array($result)) {
					$dialstr = "QUEUE $queuename,{$result[0][0]},,,{$result[0][1]},,,{$result[0][2]}";
				}
			}
			$res_dial = $agi->exec($dialstr);
		} else	$res_dial = $agi->exec("DIAL $dialstr");
		
		return $res_dial;
	}
	
	/*
	 * This function to set the parameters separator according the asterisk version
	 */
	public function format_parameters ($parameters)
	{
		$sepafter = ($this->config['global']['asterisk_version'] == "1_2" || $this->config['global']['asterisk_version'] == "1_4")?'|':',';
		$sepbefore = ($sepafter == "|")?',':'|';
		$parameters = str_replace($sepbefore, $sepafter, $parameters);
		if (isset($this->time_out) && $this->time_out != "" && !strpos($parameters, $this -> agiconfig['monitor_formatfile']) && $parameters != "StopMixMonitor" && stripos($parameters,"QUEUE ") !== 0) {
			$wer = explode($sepafter, $parameters);
			$wer[1] = $this->time_out;
			$parameters = implode($sepafter, $wer);
			$this->time_out = NULL;
		} elseif ($this->config['webui']['monitor_conversion'] == "1") {
			$parameters = str_replace("." . $this -> agiconfig['monitor_formatfile'] . $sepafter, $sepafter."m", $parameters);
			$parameters = str_replace("MixMonitor ", "Monitor " . $this -> agiconfig['monitor_formatfile'] . $sepafter, $parameters);
			$parameters = str_replace("StopMixMonitor", "StopMonitor", $parameters);
		}
		return $parameters;
	}

	public function calculate_time_condition($now,$timeinterval,$type)
	{
		$week_range=array(
		'mon' => 1,
		'tue' => 2,
		'wed' => 3,
		'thu' => 4,
		'fri' => 5,
		'sat' => 6,
		'sun' => 7
		);
		
		$month_range=array(
		'jan' => 1,
		'feb' => 2,
		'mar' => 3,
		'apr' => 4,
		'may' => 5,
		'jun' => 6,
		'jul' => 7,
		'aug' => 8,
		'sep' => 9,
		'oct' => 10,
		'nov' => 11,
		'dec' => 12
		);

		if (empty($timeinterval)) return 1;

		$cond_result = array();
		$row_conditions=$this->extract_cond_values($timeinterval);
		$x=0;
		$cond_type="";
		foreach ($row_conditions as $conditions){

			/* Options */
			if (!empty($conditions[4])){
				switch ($conditions[4][0]){
					case 0:
						break;
					case 1:
						break;
					case 2:
						switch(strtolower($conditions[4][1])){
							case "p":
								// Peak
								$cond_type = "peak";
								break;
							case "o":
								// Off peak
								$cond_type = "offpeak";
								break;
						}
						break;
					case 3:
						// Default Peak
						$cond_type = "peak";
						break;
					default:
						// Default Peak
						$cond_type = "peak";
						break;
				}
			}
			if ($type == $cond_type){
				$cond_result[$x]=0;
				/* Time */
				switch ($conditions[0][0]){
					case 0:
						$i=0;
						foreach ($conditions[0] as $condition){
							if ($i>0) $conditions[0][$i]=strtotime($condition);
							$i++;
						}
						if ($now >= $conditions[0][1] && $now <= $conditions[0][2]){
							$cond_result[$x] = $cond_result[$x]+1;
						}
						break;
					case 1:
					case 2:
						array_splice($conditions[0], 0, 1);
						if (in_array(date("G:i",$now),$conditions[0])) $cond_result[$x] = $cond_result[$x]+1;
						break;
					case 3:
						$cond_result[$x] = $cond_result[$x]+1;
						break;
				}
				
				/* Day of week */
				switch ($conditions[1][0]){
					case 0:
						$day=date("N",$now);
						if ($day >= $week_range[strtolower($conditions[1][1])] && $day <= $week_range[strtolower($conditions[1][2])]){
							$cond_result[$x] = $cond_result[$x]+2;
						}
						break;
					case 1:
					case 2:
						$day=strtolower(date("D",$now));
						array_splice($conditions[1], 0, 1);
						$i=0;
						foreach ($conditions[1] as $condition){
							$conditions[1][$i]=strtolower($condition);
							$i++;
						}
						if (in_array($day,$conditions[1])) $cond_result[$x] = $cond_result[$x]+2;
						break;
					case 3:
						$cond_result[$x] = $cond_result[$x]+2;
						break;
				}
				
				/* Day of month */
				switch ($conditions[2][0]){
					case 0:
						$month_day=date("j",$now);
						if ($month_day >= $conditions[2][1] && $month_day <= $conditions[2][2] ) {
							$cond_result[$x] = $cond_result[$x]+4;
						}
						break;
					case 1:
					case 2:
						$month_day=date("j",$now);
						array_splice($conditions[2], 0, 1);
						if (in_array($month_day,$conditions[2])) $cond_result[$x] = $cond_result[$x]+4;
						break;
					case 3:
						$cond_result[$x] = $cond_result[$x]+4;
						break;
				}
				
				/* Month */
				switch ($conditions[3][0]){
					case 0:
						$month=strtolower(date("n",$now));
						if ($month >= $month_range[strtolower($conditions[3][1])] && $month <= $month_range[strtolower($conditions[3][2])] ) {
							$cond_result[$x] = $cond_result[$x]+8;
						}
						break;
					case 1:
					case 2:	
						$month=strtolower(date("M",$now));
						array_splice($conditions[3], 0, 1);
						$i=0;
						foreach ($conditions[3] as $condition){
							$conditions[3][$i]=strtolower($condition);
							$i++;
						}
						if (in_array($month,$conditions[3])) $cond_result[$x] = $cond_result[$x]+8;
						break;
					case 3:
						$cond_result[$x] = $cond_result[$x]+8;
						break;
				}
				$x++;
			}
		}
		$i=0;
		$final_result_set=0;
		foreach ($cond_result as $result){
			if ($result == 15){
				$final_result_set=$final_result_set+pow(2,$i);
			}
			$i++;
		}
		return $final_result_set;
	}

	public function extract_cond_values($value){
		$rows=explode("\n",$value);
		$i=0;
		foreach ($rows as $row){
			$items=explode(";",trim($row));
			$x=0;
			foreach ($items as $item){
				if (preg_match('/^([[:alnum:]]+|\d+:\d+)-([[:alnum:]]+|\d+:\d+)$/',$item,$intvals)){
					$output[$i][$x]=array(0 => 0, 1 => $intvals[1], 2 => $intvals[2]);
				} elseif (preg_match('/^([[:alnum:]]+|\d+:\d+)(,[[:alnum:]]+|,\d+:\d+)+$/',$item)){
					$output[$i][$x]=array_merge(array(0 => 1),explode(',',$item));
				} elseif (preg_match('/^([[:alnum:]]+|\d+:\d+)$/',$item)){
					$output[$i][$x]=array(0 => 2, 1 => $item);
				} elseif (preg_match('/^\*$/',$item)){
					$output[$i][$x]=array(0 => 3);
				} else {
					$output[$i][$x]=array(0 => -1);
				}
			$x++;
			}
		$i++;
		}
		return $output;
	}

	public function resolve($name) {
		// reads informations over the path
		$info = pathinfo($name);
		if (!empty($info['extension'])) {
		    // if the file already contains an extension returns it
		    return $name;
		}
		$filename = $info['filename'];
		$len = strlen($filename);
		// open the folder
		$dh = opendir($info['dirname']);
		if (!$dh) {
		    return false;
		}
		// scan each file in the folder
		while (($file = readdir($dh)) !== false) {
		    if (strncmp($file, $filename, $len) === 0) {
			if (strlen($name) > $len) {
			    // if name contains a directory part
			    $name = substr($name, 0, strlen($name) - $len) . $file;
			} else {
			    // if the name is at the path root
			    $name = $file;
			}
			closedir($dh);
			return $name;
		    }
		}
		// file not found
		closedir($dh);
		return false;
	}

};
