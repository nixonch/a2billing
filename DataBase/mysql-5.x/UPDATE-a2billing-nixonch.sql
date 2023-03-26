ALTER TABLE cc_trunk ADD dialprefixmain char(30) COLLATE utf8_bin NOT NULL AFTER `creationdate`;
ALTER TABLE cc_trunk ADD cid_handover int(11) DEFAULT '0' AFTER `if_max_use`;
ALTER TABLE cc_trunk ADD outbound_cidgroup_id int(11) DEFAULT '-1';
ALTER TABLE cc_trunk ADD wrapuptime VARCHAR( 20 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_trunk ADD wrapnexttime timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';

ALTER TABLE cc_trunk ADD dialprefixa char(30) COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_trunk ADD periodcounta int(11) DEFAULT '0';
ALTER TABLE cc_trunk ADD periodexpirya datetime NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `cc_trunk` CHANGE `periodexpirya` `periodexpirya` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD timelefta int(11) NOT NULL DEFAULT '60';
ALTER TABLE cc_trunk ADD perioda int(11) DEFAULT '0';
ALTER TABLE cc_trunk ADD startdatea timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD stopdatea timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD billblockseca int(11) NOT NULL DEFAULT '1';
ALTER TABLE cc_trunk ADD maxsecperperioda int(11) DEFAULT '-1';
ALTER TABLE cc_trunk ADD lastcallstoptimea timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD failover_trunka int(11) NULL DEFAULT NULL;

ALTER TABLE cc_trunk ADD dialprefixb char(30) COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_trunk ADD periodcountb int(11) DEFAULT '0';
ALTER TABLE cc_trunk ADD periodexpiryb datetime NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE `cc_trunk` CHANGE `periodexpiryb` `periodexpiryb` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD timeleftb int(11) NOT NULL DEFAULT '60';
ALTER TABLE cc_trunk ADD periodb int(11) DEFAULT '0';
ALTER TABLE cc_trunk ADD startdateb timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD stopdateb timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD billblocksecb int(11) NOT NULL DEFAULT '1';
ALTER TABLE cc_trunk ADD maxsecperperiodb int(11) DEFAULT '-1';
ALTER TABLE cc_trunk ADD lastcallstoptimeb timestamp NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD failover_trunkb int(11) NULL DEFAULT NULL;

ALTER TABLE cc_trunk ADD operator_credit DECIMAL( 15, 5 ) NULL DEFAULT NULL;
ALTER TABLE cc_trunk ADD ussd_credit VARCHAR( 10 ) NULL DEFAULT NULL;
ALTER TABLE cc_trunk ADD credit_posix_extract VARCHAR( 30 ) NULL DEFAULT NULL;
ALTER TABLE cc_trunk ADD ussd_rest_package VARCHAR( 10 ) NULL DEFAULT NULL;
ALTER TABLE cc_trunk ADD ussd_vaucher_prefix VARCHAR( 10 ) NULL DEFAULT NULL;
ALTER TABLE cc_trunk ADD ussd_check_time TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_trunk ADD lastdial VARCHAR( 50 ) COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_trunk ADD attract int(3) NOT NULL DEFAULT '0';
ALTER TABLE cc_trunk ADD id_cust bigint(20) NULL DEFAULT NULL AFTER `id_trunk`;
ALTER TABLE cc_trunk
  CHANGE `stopdatea`      `stopdatea`      DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  CHANGE `stopdateb`      `stopdateb`      DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  CHANGE `removeprefix`   `removeprefix`   CHAR(30) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `dialprefixmain` `dialprefixmain` CHAR(30) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  CHANGE `dialprefixa`    `dialprefixa`    CHAR(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  CHANGE `dialprefixb`    `dialprefixb`    CHAR(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  CHANGE `lastdial`       `lastdial`       VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

ALTER TABLE `cc_trunk` ADD UNIQUE `trunkcode` (`trunkcode`);
INSERT IGNORE INTO cc_trunk (trunkcode,providertech,providerip,failover_trunk,addparameter,id_provider) VALUES ('*DEFAULT','SIP','%dialingnumber%','-1','htk','-1');
INSERT IGNORE INTO cc_trunk (trunkcode,providertech,providerip,failover_trunk,id_provider) VALUES ('-INFOLINE-','Local','%dialingnumber%@loopvirtual/n','-1','-1');
INSERT IGNORE INTO cc_trunk (trunkcode,providertech,providerip,removeprefix,failover_trunk,id_provider,cid_handover) VALUES ('-VOICEMAIL-','Local','%dialingnumber%%cardnumber%@tovoicemailu','9','-1','-1','1');

ALTER TABLE cc_voucher ADD callplan INT(11) NOT NULL;

ALTER TABLE cc_card ADD monitor int(11) DEFAULT '0';
ALTER TABLE cc_card ADD recalldays int(11) NOT NULL DEFAULT '10';
ALTER TABLE cc_card ADD recalltime int(11) NOT NULL DEFAULT '7200';
ALTER TABLE cc_card ADD cbtimeoutunavailable int(11) NOT NULL DEFAULT '5';
ALTER TABLE cc_card ADD cbattemptunavailable int(11) NOT NULL DEFAULT '20';
ALTER TABLE cc_card ADD cbtimeoutbusy int(11) NOT NULL DEFAULT '20';
ALTER TABLE cc_card ADD cbattemptbusy int(11) NOT NULL DEFAULT '3';
ALTER TABLE cc_card ADD cbtimeoutnoanswer int(11) NOT NULL DEFAULT '10';
ALTER TABLE cc_card ADD cbattemptnoanswer int(11) NOT NULL DEFAULT '3';
ALTER TABLE cc_card ADD cbtimeoutmax int(11) NOT NULL DEFAULT '600';
ALTER TABLE cc_card ADD extmin int(11) NOT NULL DEFAULT '701' AFTER `fax`;
ALTER TABLE cc_card ADD extmax int(11) NOT NULL DEFAULT '799' AFTER `extmin`;
ALTER TABLE cc_card ADD extquantity int(11) NOT NULL DEFAULT '24' AFTER `extmax`;
ALTER TABLE cc_card ADD paypal int(1) NOT NULL DEFAULT '0';
ALTER TABLE cc_card ADD id_diller bigint(20) NOT NULL DEFAULT 0 AFTER `id`;
ALTER TABLE cc_card ADD margin int(3) NOT NULL DEFAULT 0 AFTER `id_diller`;
ALTER TABLE cc_card ADD margin_diller int(3) NOT NULL DEFAULT 10 AFTER `margin`;
ALTER TABLE cc_card ADD commission DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `margin_diller`;
ALTER TABLE cc_card ADD areaprefix SMALLINT( 6 ) NULL DEFAULT NULL AFTER `country`;
ALTER TABLE cc_card ADD citylength SMALLINT( 6 ) NULL DEFAULT NULL AFTER `areaprefix`;
ALTER TABLE cc_card ADD removeaddprefix char(30) COLLATE utf8_bin NOT NULL AFTER `citylength`;
ALTER TABLE cc_card ADD addprefixinternational char(30) COLLATE utf8_bin NOT NULL AFTER `removeaddprefix`;
ALTER TABLE cc_card ADD showcallstypedefault INT(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_card ADD dillertariffs varchar(60) COLLATE utf8_bin NOT NULL AFTER `tariff`;
ALTER TABLE cc_card ADD dillergroups varchar(60) COLLATE utf8_bin NOT NULL AFTER `id_group`;
ALTER TABLE cc_card ADD max_concurrent INT(11) NOT NULL DEFAULT '10';
ALTER TABLE cc_card ADD speech2mail varchar(70) COLLATE utf8_bin NOT NULL AFTER `notify_email`;
ALTER TABLE cc_card ADD send_text int(11) DEFAULT '0' AFTER `speech2mail`;
ALTER TABLE cc_card ADD send_sound int(11) DEFAULT '0' AFTER `send_text`;
ALTER TABLE cc_card ADD last_sms timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' AFTER last_notification;

ALTER TABLE cc_card CHANGE id_campaign id_campaign  INT( 11 ) NULL DEFAULT '-1';
ALTER TABLE cc_card CHANGE id_timezone id_timezone CHAR( 40 ) NULL DEFAULT '0';

ALTER TABLE cc_timezone ADD countrycode CHAR(80) COLLATE utf8_bin NOT NULL;

ALTER TABLE cc_logrefill ADD diller_id bigint(20) NULL DEFAULT NULL;
ALTER TABLE cc_logrefill CHANGE `agent_id` `sendsms` INT(11) NOT NULL DEFAULT '0';

ALTER TABLE cc_logpayment ADD fee DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `payment`;

ALTER TABLE cc_invoice_item ADD fee DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `price`;

CREATE TABLE IF NOT EXISTS `cc_trunk_rand` (
  `trunk_id` int(11) NOT NULL DEFAULT '0',
  `trunk_dependa` int(11) NOT NULL DEFAULT '0',
  `trunk_dependb` int(11) NOT NULL DEFAULT '0',
  `trunkpercentage` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trunk_id`,`trunk_dependa`,`trunk_dependb`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE cc_invoice_conf CHANGE value value VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
INSERT IGNORE INTO cc_invoice_conf (key_val) VALUES ('comments');

ALTER TABLE cc_sip_buddies ADD `external` INT( 11 ) NOT NULL DEFAULT '0' AFTER `id_cc_card`;
ALTER TABLE cc_sip_buddies ADD `callbackextension` varchar( 40 ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `directmedia` enum('yes', 'no', 'nonat', 'update', 'update,nonat', 'outgoing') NULL DEFAULT 'yes' AFTER `nat`;
ALTER TABLE cc_sip_buddies ADD `avpf` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `force_avp` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `rtcp_mux` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `icesupport` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dtlsenable` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dtlsverify` enum( 'yes', 'no', 'fingerprint', 'certificate' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dtlscertfile` VARCHAR( 80 ) NULL DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dtlsprivatekey` VARCHAR( 80 ) NULL DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dtlscafile` VARCHAR( 80 ) NULL DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dtlssetup` enum( 'actpass', 'active', 'passive' ) DEFAULT NULL;

ALTER TABLE cc_sip_buddies ADD `encryption` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `encryption_taglen` enum('32','80') DEFAULT NULL AFTER `encryption`;
ALTER TABLE cc_sip_buddies ADD `ignorecryptolifetime` enum( 'yes', 'no' ) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `transport` enum('tls','udp','tcp','udp,tcp','tcp,udp','tls,tcp,udp','udp,tls,ws,wss','tls,wss') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `remotesecret` VARCHAR( 40 ) NULL DEFAULT NULL AFTER `secret`;
ALTER TABLE cc_sip_buddies ADD `trustrpid` enum('yes','no') DEFAULT NULL AFTER `insecure`;
ALTER TABLE cc_sip_buddies ADD `progressinband` enum('yes','no','never') DEFAULT NULL AFTER `trustrpid`;
ALTER TABLE cc_sip_buddies ADD `promiscredir` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `useclientcode` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `callcounter` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `busylevel` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `allowoverlap` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `allowsubscribe` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `videosupport` enum('yes','no','always') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `rfc2833compensate` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `session-timers` enum('accept','refuse','originate') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `session-expires` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `session-minse` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `session-refresher` enum('uac','uas') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `t38pt_udptl` enum('yes','yes,fec','yes,redundancy','yes,none','yes,fec,maxdatagram=400','yes,redundancy,maxdatagram=400') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `t38pt_usertpsource` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `sendrpid` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `timert1` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `timerb` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `qualifyfreq` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `constantssrc` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `contactpermit` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `contactdeny` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `textsupport` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `faxdetect` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `buggymwi` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `fullname` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `trunkname` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `mohinterpret` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `parkinglot` varchar(40) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `hasvoicemail` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `call-limit` int(11) DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `g726nonstandard` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `ignoresdpversion` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `dynamic` enum('yes','no') DEFAULT NULL;
ALTER TABLE cc_sip_buddies ADD `namedcallgroup` varchar(40) DEFAULT NULL AFTER `callgroup`;
ALTER TABLE cc_sip_buddies ADD `namedpickupgroup` varchar(40) DEFAULT NULL AFTER `namedcallgroup`;
ALTER TABLE cc_sip_buddies ADD warning_threshold INT( 11 ) NOT NULL DEFAULT '-2';
ALTER TABLE cc_sip_buddies ADD say_dialednumber SMALLINT( 6 ) NOT NULL DEFAULT '0' AFTER `warning_threshold`;
ALTER TABLE cc_sip_buddies ADD say_rateinitial SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_sip_buddies ADD say_timetotalk SMALLINT( 6 ) NOT NULL DEFAULT '0' AFTER `say_rateinitial`;
ALTER TABLE cc_sip_buddies ADD say_balance_after_call SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_sip_buddies ADD translit SMALLINT( 6 ) NOT NULL DEFAULT '0' AFTER `external`;

ALTER TABLE cc_sip_buddies CHANGE `transport` `transport` enum( 'tls', 'udp', 'tcp', 'udp,tcp', 'tcp,udp', 'tls,tcp,udp', 'udp,tls,ws,wss', 'tls,wss' ) NULL DEFAULT NULL;

ALTER TABLE cc_sip_buddies
  CHANGE `canreinvite` `canreinvite` varchar( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ,
  CHANGE `nat` `nat` enum( 'force_rport,comedia','no','force_rport','comedia','auto_force_rport','auto_comedia','auto_force_rport,auto_comedia' ) NULL DEFAULT 'comedia' ,
  CHANGE `directmedia` `directmedia` enum( 'yes','no','nonat','update','update,nonat','outgoing' ) NULL DEFAULT 'update,nonat' ,
  CHANGE `qualify` `qualify` varchar( 40 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ,
  CHANGE `regseconds` `regseconds` int( 11 ) NULL DEFAULT NULL ,
  CHANGE `rtpholdtimeout` `rtpholdtimeout` int( 11 ) NULL DEFAULT NULL ,
  CHANGE `rtpkeepalive` `rtpkeepalive` int( 11 ) NULL DEFAULT NULL ,
  CHANGE `outboundproxy` `outboundproxy` varchar( 40 ) NULL DEFAULT NULL ,
  CHANGE `callbackextension` `callbackextension` varchar( 40 ) NULL DEFAULT NULL ,
  CHANGE `encryption` `encryption` enum( 'yes', 'no' ) NULL DEFAULT NULL ,
  CHANGE `callgroup` `callgroup` varchar( 40 ) NULL DEFAULT NULL ,
  CHANGE `pickupgroup` `pickupgroup` varchar( 40 ) NULL DEFAULT NULL ,
  CHANGE `ignorecryptolifetime` `ignorecryptolifetime` enum( 'yes', 'no' ) NULL DEFAULT NULL ,
  CHANGE `deny` `deny` varchar(40) DEFAULT NULL ,
  CHANGE `auth` `auth` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ,
  CHANGE `dtmfmode` `dtmfmode` ENUM( 'rfc2833', 'info', 'shortinfo', 'inband', 'auto' ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT 'rfc2833' ,
  CHANGE `md5secret` `md5secret` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ,
  CHANGE `fullcontact` `fullcontact` VARCHAR( 80 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ,
  CHANGE `type` `type` ENUM( 'friend', 'user', 'peer' ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT 'friend' ,
  CHANGE `callingpres` `callingpres` ENUM( 'allowed_not_screened', 'allowed_passed_screen', 'allowed_failed_screen', 'allowed', 'prohib_not_screened', 'prohib_passed_screen', 'prohib_failed_screen', 'prohib' ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL ,
  CHANGE `usereqphone` `usereqphone` ENUM('yes','no') CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `DEFAULTip` `defaultip` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `allowtransfer` `allowtransfer` ENUM('yes','no') CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `subscribemwi` `subscribemwi` ENUM('yes','no') CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `videosupport` `videosupport` ENUM('yes','no','always') CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `ipaddr` `ipaddr` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  CHANGE `regexten` `regexten` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;
UPDATE `cc_sip_buddies` SET `regexten` = NULL WHERE `regexten`='';

ALTER TABLE cc_iax_buddies ADD `forceencryption` varchar(20) COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_iax_buddies ADD `external` INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_iax_buddies CHANGE `DEFAULTip` `defaultip` CHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;

ALTER TABLE cc_call ADD callbackid BIGINT(20) NOT NULL DEFAULT 0;
ALTER TABLE cc_call ADD card_caller BIGINT(20) NOT NULL AFTER `card_id`;
ALTER TABLE cc_call ADD card_called BIGINT(20) NOT NULL AFTER `card_caller`;
ALTER TABLE cc_call ADD card_seller BIGINT(20) NOT NULL AFTER `card_called`;
ALTER TABLE cc_call ADD src_peername INT(11) NULL DEFAULT NULL AFTER `src`;
ALTER TABLE cc_call ADD src_exten VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `src_peername`;
ALTER TABLE cc_call ADD calledexten INT( 11 ) NULL DEFAULT NULL AFTER `calledstation`;
ALTER TABLE cc_call ADD faxstatus SMALLINT( 6 ) NULL DEFAULT NULL;
ALTER TABLE cc_call ADD remotefaxid VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_call ADD faxpages SMALLINT( 6 ) NULL DEFAULT NULL;
ALTER TABLE cc_call ADD faxbitrate SMALLINT( 6 ) NULL DEFAULT NULL;
ALTER TABLE cc_call ADD faxresolution SMALLINT( 6 ) NULL DEFAULT NULL;
ALTER TABLE cc_call ADD margindillers DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `sessionbill`;
ALTER TABLE cc_call ADD margindiller DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `margindillers`;
ALTER TABLE cc_call CHANGE `destination` `destination` BIGINT(20) NULL DEFAULT '0';
ALTER TABLE cc_call ADD INDEX `uniqueid` (`uniqueid`);
ALTER TABLE cc_call CHANGE `card_caller` `card_caller` BIGINT(20) NOT NULL DEFAULT '0';
ALTER TABLE cc_call CHANGE `card_seller` `card_seller` BIGINT(20) NOT NULL DEFAULT '0';
ALTER TABLE cc_call CHANGE `remotefaxid` `remotefaxid` VARCHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

ALTER TABLE cc_tariffplan ADD id_seller BIGINT(20) NOT NULL AFTER `id`;
ALTER TABLE cc_tariffplan ADD sellerdialprefix char(20) COLLATE utf8_bin NULL DEFAULT NULL AFTER `id_seller`;
ALTER TABLE cc_tariffplan ADD tariff_lcr INT( 11 ) NOT NULL DEFAULT '1' AFTER `sellerdialprefix`;

ALTER TABLE cc_ratecard ADD buyrateconnectcharge DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `buyrateincrement`;
ALTER TABLE cc_ratecard ADD ratecarddialprefix char(20) COLLATE utf8_bin NULL DEFAULT NULL AFTER `id_trunk`;
ALTER TABLE cc_ratecard ADD out_of_intern_prefix_for_sure INT( 11 ) NOT NULL DEFAULT '0' AFTER `ratecarddialprefix`;
ALTER TABLE cc_ratecard ADD length_range_from INT( 11 ) NOT NULL DEFAULT '1';
ALTER TABLE cc_ratecard ADD length_range_till INT( 11 ) NOT NULL DEFAULT '100';
UPDATE `cc_ratecard` SET `length_range_from` = '12', `length_range_till` = '12' WHERE `dialprefix` LIKE '380%';
ALTER TABLE cc_ratecard CHANGE `destination` `destination` BIGINT( 20 ) NULL DEFAULT '0';
ALTER TABLE cc_ratecard CHANGE `musiconhold` `musiconhold` CHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

ALTER TABLE cc_callerid ADD callback INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD phonenumber VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_callerid ADD verify INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD warning_threshold INT( 11 ) NOT NULL DEFAULT '-1';
ALTER TABLE cc_callerid ADD say_dialednumber SMALLINT( 6 ) NOT NULL DEFAULT '0' AFTER `warning_threshold`;
ALTER TABLE cc_callerid ADD say_rateinitial SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD say_timetotalk SMALLINT( 6 ) NOT NULL DEFAULT '0' AFTER `say_rateinitial`;
ALTER TABLE cc_callerid ADD say_balance_after_call SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD cli_replace SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD cli_localreplace SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD cli_otherreplace SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD cli_prefixreplace CHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE cc_callerid ADD blacklist SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callerid ADD INDEX `id_cc_card` (`id_cc_card`);

ALTER TABLE cc_did_destination ADD answer INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did_destination ADD playsound VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_did_destination ADD calleesound VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `playsound`;
ALTER TABLE cc_did_destination ADD timeout VARCHAR( 3 ) NOT NULL ;
ALTER TABLE cc_did_destination ADD destinuse int(11) NOT NULL DEFAULT '0' AFTER `activated`;
ALTER TABLE cc_did_destination ADD destmaxuse int(11) NOT NULL DEFAULT '-1' AFTER `destinuse`;
ALTER TABLE cc_did_destination ADD calleridname VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `destination`;

ALTER TABLE cc_did ADD verify_did INT( 11 ) NOT NULL DEFAULT '0' AFTER `did`;
ALTER TABLE cc_did ADD id_trunk INT( 11 ) NOT NULL DEFAULT '-1';
ALTER TABLE cc_did ADD allciduse INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD continuewithdid INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did CHANGE expirationdate expirationdate DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';
ALTER TABLE cc_did ADD areaprefix SMALLINT( 6 ) NULL DEFAULT NULL;
ALTER TABLE cc_did ADD citylength SMALLINT( 6 ) NULL DEFAULT NULL;
ALTER TABLE cc_did ADD verify_callerid SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_did ADD voicebox VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL;
ALTER TABLE cc_did ADD chanlang CHAR(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT 'not_set';
ALTER TABLE cc_did ADD buyrate DECIMAL( 15, 5 ) NOT NULL DEFAULT 0 AFTER `fixrate`;
ALTER TABLE cc_did ADD billblock INT(11) NOT NULL DEFAULT '1' AFTER `buyrate`;
ALTER TABLE cc_did ADD spamfilter INT( 11 ) NOT NULL DEFAULT '0' AFTER `verify_did`;
ALTER TABLE cc_did ADD secondtimedays INT(11) NOT NULL DEFAULT '10' AFTER `spamfilter`;
ALTER TABLE cc_did ADD callbackprefixallow CHAR( 30 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '' AFTER `voicebox`;
ALTER TABLE cc_did ADD callbacksound VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `callbackprefixallow`;
ALTER TABLE cc_did ADD aftercallbacksound VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `callbacksound`;
ALTER TABLE cc_did ADD digitaftercallbacksound VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `aftercallbacksound`;

CREATE TABLE IF NOT EXISTS `cc_sheduler_ratecard` (
  `id_ratecard` BIGINT(20) NOT NULL DEFAULT '0',
  `id_tariffplan` BIGINT(20) NOT NULL DEFAULT '0',
  `weekdays` varchar(13) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `timefrom` TIME NOT NULL DEFAULT '0',
  `timetill` TIME NOT NULL DEFAULT '0',
  INDEX `id` ( `id_ratecard` , `id_tariffplan` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
ALTER TABLE `cc_sheduler_ratecard` ADD `id_ringup` BIGINT(20) NOT NULL DEFAULT '0' AFTER `id_tariffplan`;
ALTER TABLE `cc_sheduler_ratecard`
  DROP INDEX `id`,
  ADD `ids` BIGINT(20) NOT NULL AUTO_INCREMENT FIRST,
  ADD `id_callback` BIGINT(20) NOT NULL DEFAULT '0' AFTER `id_ringup`,
  ADD `inputa` SMALLINT(6) NOT NULL DEFAULT '0' AFTER `id_callback`,
  ADD `inputb` SMALLINT(6) NOT NULL DEFAULT '0' AFTER `inputa`,
  ADD `inputc` SMALLINT(6) NOT NULL DEFAULT '0' AFTER `inputb`,
  ADD PRIMARY KEY(`ids`),
  ADD INDEX `id` ( `id_ratecard` , `id_tariffplan` , `id_ringup`, `id_callback` );
ALTER TABLE `cc_sheduler_ratecard` ADD `id_did_destination` BIGINT(20) NOT NULL DEFAULT '0' AFTER `id_ringup`;
ALTER TABLE `cc_sheduler_ratecard` ADD `id_ivr` BIGINT(20) NOT NULL DEFAULT '0' AFTER `id_did_destination`;

#ALTER TABLE `cc_ratecard`
#  DROP `starttime`,
#  DROP `endtime`;

CREATE TABLE IF NOT EXISTS `cc_ivr` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `id_cc_card` INT(11) NOT NULL DEFAULT '0',
  `ivrname` VARCHAR(40) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `repeats` SMALLINT(1) NOT NULL DEFAULT '1',
  `waitsecsfordigits` SMALLINT(2) NOT NULL DEFAULT '6',
  PRIMARY KEY (`id`),
  INDEX (`id_cc_card`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `cc_ivr_destinations` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `id_cc_ivr` INT(11) NOT NULL DEFAULT '0',
  `waitdigits` VARCHAR(6) NULL DEFAULT NULL,
  `destinationnum` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
  `playsoundcallee` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`id_cc_ivr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `cc_ivr_sounds` CHANGE `id_cc_ivr_start` `id_cc_ivr` INT(11) NOT NULL DEFAULT '0';
CREATE TABLE IF NOT EXISTS `cc_ivr_sounds` (
  `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `id_cc_ivr` INT(11) NOT NULL DEFAULT '0',
  `id_cc_ivr_dest` INT(11) NOT NULL DEFAULT '0',
  `timeout` VARCHAR(3) NOT NULL,
  `playsound` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `id_ivrs` ( `id_cc_ivr`,`id_cc_ivr_dest` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS cc_voicemail_users (
	uniqueid BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	customer_id BIGINT(20) NOT NULL default '0',
	sip_buddy_id BIGINT(20) NOT NULL default '0',
	context CHAR(80) NOT NULL DEFAULT 'default',
	mailbox CHAR(80) NOT NULL,
	password CHAR(80) NOT NULL,
	fullname CHAR(80),
	email CHAR(80),
	pager CHAR(80),
	attach CHAR(3),
	attachfmt CHAR(10),
	serveremail CHAR(80),
	language CHAR(20),
	tz CHAR(30),
	deletevoicemail CHAR(3),
	saycid CHAR(3),
	sendvoicemail CHAR(3),
	review CHAR(3),
	tempgreetwarn CHAR(3),
	operator CHAR(3),
	envelope CHAR(3),
	sayduration CHAR(3),
	saydurationm INT(3),
	forcename CHAR(3),
	forcegreetings CHAR(3),
	callback CHAR(80),
	dialout CHAR(80),
	exitcontext CHAR(80),
	maxmsg INT(5),
	volgain DECIMAL(5,2),
	imapuser VARCHAR(80),
	imappassword VARCHAR(80),
	imapsever VARCHAR(80),
	imapport VARCHAR(8),
	imapflags VARCHAR(80),
	stamp timestamp,
	KEY `mailbox_context` (`mailbox`,`context`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS cc_voicemail_data (
	filename CHAR(255) NOT NULL PRIMARY KEY,
	origmailbox CHAR(80),
	context CHAR(80),
	macrocontext CHAR(80),
	exten CHAR(80),
	priority CHAR(5),
	callerchan CHAR(80),
	callerid CHAR(80),
	origdate CHAR(30),
	origtime CHAR(11),
	category CHAR(30),
	duration CHAR(5)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_voicemail_data;
    if a=0 then
	insert into cc_voicemail_users(customer_id, context, mailbox, password, fullname, email, language)
	select S.id_cc_card, S.accountcode, S.regexten, '0000', concat(C.lastname,' ',C.firstname) fullname, C.email, S.language
	from cc_sip_buddies S, cc_card C
	where S.regexten IS NOT NULL AND S.regexten<>'' AND S.id_cc_card=C.id;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='prefix_required' and config_group_title='agi-conf1';
    if a=0 then
        select count(*) into a from cc_config where `config_key` LIKE 'description' GROUP BY `config_key`;
        WHILE a > 0 DO
            INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
            VALUES (NULL, 'Prefix required', 'prefix_required', '0', 'Is the prefix required for international calls', '1', 'yes,no', CONCAT('agi-conf',a));
            SET a = a - 1;
        END WHILE;
        elseif a>1 then
        select id into a from cc_config where config_key='prefix_required' and config_group_title='agi-conf1' order by id limit 0,1;
        delete from cc_config where config_key='prefix_required' and config_group_title='agi-conf1' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='group_id_list';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL , 'Group_ID List', 'group_id_list', '1', 'ID list of Customer Groups which will be listed by default.', '0', NULL , 'signup');
    elseif a>1 then
	select id into a from cc_config where config_key='group_id_list' order by id limit 0,1;
	delete from cc_config where config_key='group_id_list' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='waitup_sorting';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL , 'WaitUp Sorting', 'waitup_sorting', '0', '', '1', 'yes,no', 'webui');
    elseif a>1 then
	select id into a from cc_config where config_key='waitup_sorting' order by id limit 0,1;
	delete from cc_config where config_key='waitup_sorting' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='fax_path';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL , 'Fax Path', 'fax_path', '/var/spool/asterisk/fax', 'Path to link the stored fax files.', '0', NULL , 'webui');
    elseif a>1 then
	select id into a from cc_config where config_key='fax_path' order by id limit 0,1;
	delete from cc_config where config_key='fax_path' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='date_timezone';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL , 'Time Zone', 'date_timezone', '0', 'Defines the default timezone used by the date functions, eg Europe/Kiev', '0', NULL , 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='date_timezone' order by id limit 0,1;
	delete from cc_config where config_key='date_timezone' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='main_title';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL , 'Main Title', 'main_title', '..:: A2Billing Portal ::..', 'HTML title Attribute', '0', NULL , 'webui');
    elseif a>1 then
	select id into a from cc_config where config_key='main_title' order by id limit 0,1;
	delete from cc_config where config_key='main_title' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='monitor_conversion';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'Conversion MixMonitor to Monitor', 'monitor_conversion', '0', 'Use Asterisk command Monitor instead MixMonitor to right sync in/out channels', '1', 'yes,no', 'webui');
    elseif a>1 then
	select id into a from cc_config where config_key='monitor_conversion' order by id limit 0,1;
	delete from cc_config where config_key='monitor_conversion' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

DELETE FROM cc_config WHERE config_key='google_speech_key';

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='google_tts_key';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES	(NULL, 'Google Cloud Text-to-Speech API Key', 'google_tts_key', '', 'Your API Key for Google Cloud Text-to-Speech', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='google_tts_key' order by id limit 0,1;
	delete from cc_config where config_key='google_tts_key' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='google_cloud_credential';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES	(NULL, 'Google Cloud Credentials Path', 'google_cloud_credential', '/etc/credentials.json', 'Path to Google Cloud credentials.json', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='google_cloud_credential' order by id limit 0,1;
	delete from cc_config where config_key='google_cloud_credential' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='google_storage_bucketname';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES	(NULL, 'Google Cloud Storage Bucket Name', 'google_storage_bucketname', 'wav_folder', 'Name of the Cloud Storage Bucket', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='google_storage_bucketname' order by id limit 0,1;
	delete from cc_config where config_key='google_storage_bucketname' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='bucket_location';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES	(NULL, 'Google Cloud Storage Bucket Location', 'bucket_location', 'eu', 'The location of the Cloud Storage Bucket. US - USA. EU - Europe. EUROPE-WEST3 - Frankfurt, ASIA - some regions of Asia. EUR4 - Finland and Niderlands. NAM4 - Iowa and South Carolina.', 0,'us,eu,europe-west3,asia,eur4,nam4', 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='bucket_location' order by id limit 0,1;
	delete from cc_config where config_key='bucket_location' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='d7_api_token';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'D7 Networks SMS API Token', 'd7_api_token', '', 'D7Networks.com: Online SMS Gateway & Messaging Services', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='d7_api_token' order by id limit 0,1;
	delete from cc_config where config_key='d7_api_token' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

DELETE FROM cc_config WHERE config_key='google_speech_key';

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='startup_time';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'System startup time', 'startup_time', '0', 'Numbers in seconds since 1970-01-01 (Unix epoch)', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='startup_time' order by id limit 0,1;
	delete from cc_config where config_key='startup_time' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='shortcut_icon';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'Web favicon path', 'shortcut_icon', 'images/ico/sipde-icon-32x32.ico', 'Path to the shortcut icon of your whole system', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='shortcut_icon' order by id limit 0,1;
	delete from cc_config where config_key='shortcut_icon' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='logo_path';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'System logo path', 'logo_path', 'images/logo/sipde-transparent.png', 'Path to the Logo of your whole system', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='logo_path' order by id limit 0,1;
	delete from cc_config where config_key='logo_path' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='certfile';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'Cert file path', 'certfile', '/etc/letsencrypt/live/my.domain/fullchain.pem', 'Tell Asterisk where your cert file is', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='certfile' order by id limit 0,1;
	delete from cc_config where config_key='certfile' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='privatekey';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'Private key path', 'privatekey', '/etc/letsencrypt/live/my.domain/privkey.pem', 'Tell Asterisk where your private key is', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='privatekey' order by id limit 0,1;
	delete from cc_config where config_key='privatekey' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='cafile';
    if a=0 then
	INSERT INTO cc_config (id, config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES (NULL, 'Authority certificate path', 'cafile', '/etc/letsencrypt/live/my.domain/chain.pem', 'Path to certificate authority certificate', 0, NULL, 'global');
    elseif a>1 then
	select id into a from cc_config where config_key='cafile' order by id limit 0,1;
	delete from cc_config where config_key='cafile' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='slidebar';
    if a=0 then
	INSERT INTO cc_config (config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES ('Enabling slidebar', 'slidebar', '0', 'Show slidebar for commercial using', '1', 'yes,no', 'webcustomerui');
    elseif a>1 then
	select id into a from cc_config where config_key='slidebar' order by id limit 0,1;
	delete from cc_config where config_key='slidebar' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='mailto';
    if a=0 then
	INSERT INTO cc_config (config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES ('E-mail to contact us', 'mailto', 'support@my.domain', 'Customers support email address', 0, NULL, 'webcustomerui');
    elseif a>1 then
	select id into a from cc_config where config_key='mailto' order by id limit 0,1;
	delete from cc_config where config_key='mailto' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_config where config_key='address';
    if a=0 then
	INSERT INTO cc_config (config_title, config_key, config_value, config_description, config_valuetype, config_listvalues, config_group_title)
	VALUES ('Address to contact us', 'address', 'ATTN: John Doe, CEO, Global Co, 90210 Broadway Blvd, Nashville, TN 37011-5678, USA', 'Customers support address', 0, NULL, 'webcustomerui');
    elseif a>1 then
	select id into a from cc_config where config_key='address' order by id limit 0,1;
	delete from cc_config where config_key='address' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

UPDATE `cc_config` SET `config_description` = 'Asterisk Version Information, 1_1, 1_2, 1_4, 1_6, 1_8, 1_12, 1_16', `config_listvalues` = '1_1,1_2,1_4,1_6,1_8,1_12,1_16' WHERE `config_key` LIKE 'asterisk_version';
UPDATE `cc_config` SET `config_group_title` = 'epayment_method' WHERE `config_group_title` = '5';

ALTER TABLE cc_callback_spool ADD `next_attempt_time` timestamp NULL DEFAULT NULL;
ALTER TABLE cc_callback_spool ADD `reason` int(11) DEFAULT NULL;
ALTER TABLE cc_callback_spool ADD `num_attempts_unavailable` int(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `num_attempts_busy` int(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `num_attempts_noanswer` int(11) NOT NULL DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `exten_leg_a` varchar(60) COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_callback_spool ADD `leg_a` varchar(60) COLLATE utf8_bin NOT NULL;
ALTER TABLE cc_callback_spool ADD `last_status` varchar(80) COLLATE utf8_bin DEFAULT NULL;
ALTER TABLE cc_callback_spool ADD `surveillance` INT( 11 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `max_attempt` int(11) NOT NULL DEFAULT '-1' AFTER `num_attempt`;
ALTER TABLE cc_callback_spool ADD `timeout1` SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `sound1` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE cc_callback_spool ADD `timeout2` SMALLINT( 6 ) NOT NULL DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `flagringup` int(11) DEFAULT '0';
ALTER TABLE cc_callback_spool ADD `calleridprefix` CHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE cc_callback_spool ADD `calleridlength` SMALLINT( 6 ) NOT NULL DEFAULT '13';
ALTER TABLE cc_callback_spool ADD `localtz` CHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL;

ALTER TABLE `cc_callback_spool` ADD INDEX `status` (`status`);

DELETE FROM `cc_payment_methods` WHERE `payment_method` LIKE 'iridium';

ALTER TABLE `cc_payment_methods` ADD UNIQUE `SECONDARY` ( `payment_method` );

INSERT IGNORE INTO cc_payment_methods (`payment_method`, `payment_filename`) VALUES
	('WebMoney', 'webmoney.php'),
	('WebMoneyCreditCard', 'webmoneycreditcard.php'),
	('PayPalCreditCard', 'paypalcreditcard.php');

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_payment_methods where payment_method='WebMoney';
    if a>1 then
	select id into a from cc_payment_methods where payment_method='WebMoney' order by id limit 0,1;
	delete from cc_payment_methods where payment_method='WebMoney' and id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

ALTER TABLE `cc_configuration` ADD UNIQUE `SECONDARY` (`configuration_key`);

UPDATE `cc_configuration` SET `configuration_key` = 'MODULE_PAYMENT_PAYPAL_BASIC_STATUS' WHERE `configuration_id`='7' AND `configuration_key` ='MODULE_PAYMENT_PAYPAL_STATUS';

INSERT IGNORE INTO `cc_configuration` (`configuration_title`, `configuration_key`, `configuration_value`, `configuration_description`, `configuration_type`, `use_function`, `set_function`) VALUES
	('Enable WebMoney Module', 'MODULE_PAYMENT_WM_STATUS', 'False', 'Do you want to accept webmoney payments?', 0, NULL, 'tep_cfg_select_option(array(''True'', ''False''),'),
	('Provider WMID', 'MODULE_PAYMENT_WM_WMID', '111111111111', '', 0, NULL, NULL),
	('WME Purse', 'MODULE_PAYMENT_WM_PURSE_WME', 'E222222222222', 'Euro (EUR)', 0, NULL, NULL),
	('WMP purse', 'MODULE_PAYMENT_WM_PURSE_WMR', 'P333333333333', 'Russian Rouble (RUB)', 0, NULL, NULL),
	('WMZ purse', 'MODULE_PAYMENT_WM_PURSE_WMZ', 'Z444444444444', 'U.S. Dollar (USD)', 0, NULL, NULL),
	('WMU Purse', 'MODULE_PAYMENT_WM_PURSE_WMU', 'U555555555555', 'Ukraine Hryvnia (UAH)', 0, NULL, NULL),
	('WebMoney', 'MODULE_PAYMENT_WM_CACERT', './WebMoneyTransferRootCA.crt', 'root certificate path, in PEM-format', 0, NULL, NULL),
	('Secret Key', 'MODULE_PAYMENT_WM_LMI_SECRET_KEY', 'Secret Key', 'Known to seller and WM Merchant Interface service on', 0, NULL, NULL),
	('Extra field for testmode', 'MODULE_PAYMENT_WM_LMI_SIM_MODE', '0', '0 - simulate success; 1 - simulate fail; 2 - simulate 80% success, 20% fail', 0, NULL, NULL),
	('Hash method', 'MODULE_PAYMENT_WM_LMI_HASH_METHOD', 'MD 5', 'Method of forming control signature', 0, NULL, 'tep_cfg_select_option(array(''MD 5'', ''SHA256'', ''SIGN''),'),

	('Enable WebMoney Credit Card payments Module', 'MODULE_PAYMENT_WM_STATUS_10', 'False', 'Do you want to accept separate method for jump directly to interface for paying by Credit Card?', 0, NULL, 'tep_cfg_select_option(array(''True'', ''False''),'),
	('Provider WMID', 'MODULE_PAYMENT_WM_WMID_10', '111111111111', '', 0, NULL, NULL),
	('WMU Purse', 'MODULE_PAYMENT_WM_PURSE_WMU_10', 'U555555555555', 'Ukraine Hryvnia (UAH)', 0, NULL, NULL),
	('WMP purse', 'MODULE_PAYMENT_WM_PURSE_WMR_10', 'P333333333333', 'Russian Rouble (RUB)', 0, NULL, NULL),
	('WebMoney', 'MODULE_PAYMENT_WM_CACERT_10', './WebMoneyTransferRootCA.crt', 'root certificate path, in PEM-format', 0, NULL, NULL),
	('Secret Key', 'MODULE_PAYMENT_WM_LMI_SECRET_KEY_10', 'Secret Key', 'Known to seller and WM Merchant Interface service on', 0, NULL, NULL),
	('Extra field for testmode', 'MODULE_PAYMENT_WM_LMI_SIM_MODE_10', '0', '0 - simulate success; 1 - simulate fail; 2 - simulate 80% success, 20% fail', 0, NULL, NULL),
	('Hash method', 'MODULE_PAYMENT_WM_LMI_HASH_METHOD_10', 'MD 5', 'Method of forming control signature', 0, NULL, 'tep_cfg_select_option(array(''MD 5'', ''SHA256'', ''SIGN''),'),

	('Enable PayPal Module', 'MODULE_PAYMENT_PAYPAL_STATUS', 'False', 'Do you want to accept PayPal Express Checkout payments?', 0, NULL, 'tep_cfg_select_option(array(''True'', ''False''), '),
	('API Username', 'MODULE_PAYMENT_PAYPAL_USER', '', '', 0, NULL, NULL),
	('API Password', 'MODULE_PAYMENT_PAYPAL_PWD', '', '', 0, NULL, NULL),
	('Signature', 'MODULE_PAYMENT_PAYPAL_SIGNATURE', '', '', 0, NULL, NULL),
	('Transaction Currency', 'MODULE_PAYMENT_PAYPAL_CURRENCY', 'EUR, USD', 'The default currencies for the payment transactions', 0, NULL, '_selectOptions(array(''EUR'', ''USD'', ''GBP'', ''HKD'', ''SGD'', ''JPY'', ''CAD'', ''AUD'', ''CHF'', ''DKK'', ''SEK'', ''NOK'', ''ILS'', ''MYR'', ''NZD'', ''TWD'', ''THB'', ''CZK'', ''HUF'', ''SKK'', ''ISK'', ''INR'', ''RUB''), ');

UPDATE `cc_configuration` SET `set_function` = 'tep_cfg_select_option(array(''MD 5'', ''SHA256'', ''SIGN''),' WHERE `configuration_key` ='MODULE_PAYMENT_WM_LMI_HASH_METHOD';

delimiter //

create procedure a2b_trf_check()
begin
    declare a int;
    select count(*) into a from cc_configuration where configuration_key='MODULE_PAYMENT_WM_STATUS';
    if a>1 then
	select configuration_id into a from cc_configuration where configuration_key='MODULE_PAYMENT_WM_LMI_HASH_METHOD' order by configuration_id limit 0,1;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_LMI_HASH_METHOD' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_LMI_SIM_MODE' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_LMI_SECRET_KEY' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_CACERT' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_PURSE_WMU' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_PURSE_WMZ' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_PURSE_WMR' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_PURSE_WME' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_WMID' and configuration_id>a;
	delete from cc_configuration where configuration_key='MODULE_PAYMENT_WM_STATUS' and configuration_id>a;
    end if;
end //

delimiter ;

call a2b_trf_check;

drop procedure if exists a2b_trf_check;

CREATE TABLE IF NOT EXISTS `cc_card_concat` (
  `concat_id` int(11) NOT NULL DEFAULT '0',
  `concat_card_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`concat_card_id`),
  INDEX `union` ( `concat_id` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `cc_card_concat`
DROP PRIMARY KEY ,
ADD PRIMARY KEY ( `concat_card_id` ),
ADD INDEX `union` ( `concat_id` );

ALTER TABLE `cc_card_concat`
ADD `root_manager` TINYINT NOT NULL DEFAULT '0',
ADD `foreignvoipconf` TINYINT NOT NULL DEFAULT '0',
ADD `foreignlogs` TINYINT NOT NULL DEFAULT '0';
ALTER TABLE `cc_card_concat`
ADD `mylogs` TINYINT NOT NULL DEFAULT '0',
ADD `foreignrecords` TINYINT NOT NULL DEFAULT '0',
ADD `myrecords` TINYINT NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `cc_recognize_spool` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `status` smallint(6) NOT NULL DEFAULT '0',
  `id_cc_card` bigint(20) NOT NULL,
  `mailaddr` varchar(80) COLLATE utf8_bin NOT NULL,
  `audiofile` varchar(80) COLLATE utf8_bin NOT NULL,
  `answeredtime` smallint(6) NOT NULL,
  `languagecode` varchar(30) COLLATE utf8_bin NOT NULL,
  `send_sound` smallint(6) NOT NULL,
  `send_text` smallint(6) NOT NULL,
  `save_sound` smallint(6) NOT NULL,
  `src` varchar(40) COLLATE utf8_bin NOT NULL,
  `destination` varchar(40) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  INDEX (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `cc_ringup` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tag` varchar(60) COLLATE utf8_bin NOT NULL UNIQUE,
  `trunks` varchar(60) COLLATE utf8_bin NOT NULL,
  `simult` smallint(6) NOT NULL DEFAULT '0',
  `inuse` smallint(6) NOT NULL DEFAULT '0',
  `processed` int(11) NOT NULL DEFAULT '0',
  `lefte` int(11) NOT NULL DEFAULT '0',
  `status` smallint(6) NOT NULL DEFAULT '0',
  `action` smallint(6) NOT NULL DEFAULT '0',
  `account_id` int(11) NOT NULL DEFAULT '0',
  `id_server` int(11) NOT NULL DEFAULT '1',
  `id_server_group` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
ALTER TABLE `cc_ringup`
ADD `destination` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
ADD `localtz` CHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
ADD `callerids` VARCHAR(250) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL,
ADD `maxduration` SMALLINT(6) NOT NULL DEFAULT '600';
ALTER TABLE `cc_ringup` DROP INDEX `tag`;
ALTER TABLE `cc_ringup` ADD UNIQUE `tag-card_id` (`tag`, `account_id`);

CREATE TABLE IF NOT EXISTS `cc_ringup_list` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_ringup` int(11) NOT NULL DEFAULT '0',
  `tonum` varchar(40) COLLATE utf8_bin NOT NULL,
  `try` smallint(6) NOT NULL DEFAULT '0',
  `attempt` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  `inuse` smallint(6) NOT NULL DEFAULT '0',
  `dialstatus` smallint(6) NOT NULL DEFAULT '0',
  `channelstatedesc` VARCHAR( 40 ) NOT NULL,
  `passed` smallint(6) NOT NULL DEFAULT '0',
  `result` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE `ringseria` (`id_ringup`, `tonum`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
ALTER TABLE `cc_ringup_list`
ADD `name` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL AFTER `tonum`,
ADD `info` TINYTEXT CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL AFTER `name`,
ADD `keyspressed` TINYTEXT CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
CHANGE `attempt` `lastattempt` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00';

CREATE TABLE IF NOT EXISTS `cc_sms` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_cc_card` int(11) NOT NULL DEFAULT '0',
  `num` varchar(40) COLLATE utf8_bin NOT NULL,
  `email` varchar(80) COLLATE utf8_bin NOT NULL,
  `notify_email` smallint(6) NOT NULL DEFAULT '1',
  `go_on` smallint(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`id_cc_card`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `cc_sms_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_card_from` int(11) NOT NULL DEFAULT '0',
  `id_card_to` int(11) NOT NULL DEFAULT '0',
  `receivedtime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fromnum` varchar(40) COLLATE utf8_bin NOT NULL,
  `tonum` varchar(40) COLLATE utf8_bin NOT NULL,
  `sent` smallint(6) NOT NULL DEFAULT '0',
  `smstext` varchar(256) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`,`id_card_from`,`id_card_to`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `cc_fax` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_cc_card` int(11) NOT NULL DEFAULT '0',
  `ext_num` varchar(20) COLLATE utf8_bin NOT NULL,
  `localstationid` varchar(20) COLLATE utf8_bin NOT NULL,
  `localheaderinfo` varchar(50) COLLATE utf8_bin NOT NULL,
  `store` smallint(6) NOT NULL DEFAULT '1',
  `email` varchar(70) COLLATE utf8_bin NOT NULL,
  `notify_email` smallint(6) NOT NULL DEFAULT '1',
  `printer_type` varchar(60) COLLATE utf8_bin NOT NULL,
  `printer_ip` varchar(60) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`,`ext_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `cc_greeting_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_cc_card` int(11) NOT NULL DEFAULT '0',
  `no_browther_cache` smallint(6) NOT NULL DEFAULT '1',
  `technology` varchar(20) COLLATE utf8_bin NOT NULL,
  `lang_locale` varchar(20) COLLATE utf8_bin NOT NULL,
  `voice_name` varchar(30) COLLATE utf8_bin NOT NULL,
  `gender` varchar(20) COLLATE utf8_bin NOT NULL,
  `speed` varchar(4) COLLATE utf8_bin NOT NULL,
  `greet_text` varchar(200) COLLATE utf8_bin NOT NULL,
  `greet_filename` varchar(100) COLLATE utf8_bin NOT NULL,
  `download_payed` smallint(6) NOT NULL DEFAULT '0',
  `updatetime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE `unikey` (`id_cc_card`, `greet_filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `cc_prefix` CHANGE `prefix` `prefix` BIGINT( 20 ) NOT NULL;
INSERT IGNORE INTO cc_prefix (`prefix`, `destination`) VALUES ('0', 'Internal');
INSERT IGNORE INTO cc_prefix (`prefix`, `destination`) VALUES ('-1', '');
INSERT IGNORE INTO cc_prefix (`prefix`, `destination`) VALUES ('-2', '<b>FAX</b>');
INSERT IGNORE INTO cc_prefix (`prefix`, `destination`) VALUES ('-3', '<b>Incoming</b>');
INSERT IGNORE INTO cc_prefix (`prefix`, `destination`) VALUES ('-4', '<b>VoiceMail</b>');

INSERT IGNORE INTO cc_templatemail (`id_language`, `mailtype`, `fromemail`, `fromname`, `subject`, `messagetext`, `messagehtml`) VALUES
('en', 'fax_success', 'fax_robot@my.domain', 'Fax Robot', 'FAX received from $cid_number$', 'You have just received a $count$ page fax from $cid_number$ $cid_name$, at phone number $dest_exten$, on $datetime$.\r\nThe original fax document is attached in $format$ format.\r\n\r\nYou may also download copy of fax from your personal web page <a href="$urlcustomerinterface$/fax-history.php">$urlcustomerinterface$/fax-history</a>\r\n\r\nKind regards,\r\nTeam <a href="http://www.my.domain/">TEAM.LTD</a>', NULL),
('en', 'fax_failed', 'fax_robot@my.domain', 'Fax Robot', 'FAX receiving failed from $cid_number$', 'You have just received a fax attempt from $cid_number$ $cid_name$, at phone number $dest_exten$, on $datetime$.\r\nThere was a problem receiving the fax:  $fax_result$.\r\n\r\nYou may download copy of faxes from your personal web page <a href="$urlcustomerinterface$/fax-history.php">$urlcustomerinterface$/fax-history</a>\r\n\r\nKind regards,\r\nTeam <a href="http://www.my.domain/">TEAM.LTD</a>', NULL),
('ru', 'fax_success', 'fax_robot@my.domain', 'Fax Robot', 'Факс от $cid_number$', 'Получен факс из $count$ страниц/ы от $cid_number$ $cid_name$, на номер $dest_exten$.\r\n Время получения $datetime$.\r\nФакс в формате $format$ прикреплен к этому письму как обычный файл.\r\n\r\nВы так же можете загрузить копию этого факса с вашей персональной страницы <a href="$urlcustomerinterface$/fax-history">кликнув на эту ссылку</a>.\r\n\r\nСпасибо за Ваш интерес к сервису <a href="http://www.my.domain/">TEAM.LTD</a> !', NULL),
('ru', 'fax_failed', 'fax_robot@my.domain', 'Fax Robot', 'Попытка получения факса от $cid_number$', 'Факс от $cid_number$ $cid_name$ для $dest_exten$ не был получен по причине: $fax_result$.\r\n\r\nВы можете загружать копии полученных факсов с вашей персональной страницы <a href="$urlcustomerinterface$/fax-history">кликнув на эту ссылку</a>.\r\nНастроить получение факсов можно в разделе МОЯ АТС => ФАКС МАШИНА.\r\n\r\nСпасибо за Ваш интерес к сервису <a href="http://www.my.domain/">TEAM.LTD</a> !', NULL),
('ru', 'signup', 'info@my.domain', 'No Reply', 'АКТИВАЦИЯ УЧЁТНОЙ ЗАПИСИ', 'Спасибо за регистрацию в сервисе TEAM.LTD\r\n\r\nЧтобы активировать Вашу учётную запись кликните по ссылке:\r\n<a href="$urlcustomerinterface$/activate.php?key=$loginkey$">$urlcustomerinterface$/activate.php?key=$loginkey$</a>\r\n\r\nЧтобы начать пользоваться сервисами пополните после активации балланс Вашего аккаунта с помощью кредитной карты, систем PayPal или WebMoney со <a href="https://customer.my.domain/checkout_payment.php?ui_language=russian">страницы управления</a> Вашим аккаунтом.\r\n\r\n--\r\nС уважением.\r\nКоманда <a href="http://www.my.domain/">TEAM.LTD</a>', NULL),
('ru', 'signupconfirmed', 'info@my.domain', 'No Reply', 'ПОДТВЕРЖДЕНИЕ РЕГИСТРАЦИИ', 'Благодарим за регистрацию!\r\n\r\nНачинайте экономить на связи уже сейчас и упрощайте свою жизнь с помощью нашего сервиса. Ведь теперь, Вы сам себе оператор!\r\n\r\nИнформация для доступа к управлению <u>Вашей учётной записью</u>:\r\nНомер учётной записи: $cardnumber$\r\nЛогин для входа: $login$\r\nПароль: $password$\r\nСсылка для входа: <a href="$urlcustomerinterface$/?ui_language=russian">$urlcustomerinterface$</a>\r\n\r\nРекомендуется удалить данное письмо после прочтения.\r\n\r\n--\r\nСпасибо за ваш интерес к сервису <a href="http://www.my.domain/">TEAM.LTD</a> !', NULL),
('ru', 'did_paid', 'info@my.domain', 'TEAM.LTD', 'DID уведомление - ($did$)', '\r\nОСТАТОК НА БАЛАНСЕ ПОСЛЕ ОПЛАТЫ: $balance_remaining$ $base_currency$\r\n\r\nАвтоматически списано $did_cost$ $base_currency$ с Вашего счёта чтобы оплатить ежемесячный взнос за Ваш DID номер ($did$).\r\n\r\nЕжемесячный взнос за DID номер : $did_cost$ $base_currency$\r\n\r\n--\r\n<a href="http://www.my.domain/">TEAM.LTD</a>', NULL),
('ru', 'did_unpaid', 'info@my.domain', 'TEAM.LTD', 'DID уведомление - ($did$)', '\r\nОСТАТОК НА БАЛАНСЕ: $balance_remaining$ $base_currency$\r\n\r\nУ Вас не хватает средств чтобы оплатить Ваш DID номер ($did$), ежемесячный платёж составляет: $did_cost$ $base_currency$\r\n\r\nПроизведено резервирование номера на дополнительные $days_remaining$ дней чтобы Вы могли оплатить выставленный счёт (REF: $invoice_ref$). После истечения этого срока DID номер станет свободен для заказа в обычном порядке.\r\n\r\n--\r\n<a href="http://www.my.domain/">TEAM.LTD</a>', NULL),
('ru', 'did_released', 'info@my.domain', 'TEAM.LTD', 'DID уведомление - ($did$)', '\r\nУ Вас не хватило средств чтобы оплатить Ваш DID номер ($did$), ежемесячный платёж составлял: $did_cost$ $base_currency$\r\n\r\nDID номер $did$ был автоматически отключен и переведен в свободную продажу!\r\nВы можете заказать его вновь в обычном порядке.\r\n\r\n--\r\n<a href="http://www.my.domain/">TEAM.LTD</a>', NULL);
INSERT IGNORE INTO cc_templatemail (`id_language`, `mailtype`, `fromemail`, `fromname`, `subject`, `messagetext`, `messagehtml`) VALUES
('en', 'call_success', 'speech_robot@my.domain', 'Speech Robot', 'Call $cid_number$->$dest_exten$', '<font face="Verdana"><i>$text$</i></font>\r\n---------------------------------------------------------\r\nKind regards,\r\nTeam <a href="http://www.my.domain/">IP Ltd</a>', NULL);
INSERT IGNORE INTO cc_templatemail (`id_language`, `mailtype`, `fromemail`, `fromname`, `subject`, `messagetext`, `messagehtml`) VALUES
('en', 'forgetpassword', 'info@my.domain', 'No Reply', 'Login Information', '<font face="Verdana"><u>Your login information is as below:</u>\r\n\r\nYour account is: $cardnumber$\r\n\r\n<i>Login link</i>: <a href="$urlcustomerinterface$">$urlcustomerinterface$</a>\r\n<i>Your cardalias</i> is: $login$\r\n<i>Your password</i> is: $password$\r\n<form name="form1" method="post" action="$customerinterface$userinfo.php"><input type="hidden" name="done" value="submit_log"><input type="hidden" name="pr_login" value="$login$"><input type="hidden" name="pr_password" value="$password$"><input name="" type="submit" value="SIGN IN"/></form>\r\n\r\n--\r\nKind regards,\r\n<a href="http://www.my.domain/">TEAM.LTD</a></font>', NULL),
('ru', 'forgetpassword', 'info@my.domain', 'No Reply', 'Информация для входа', '<font face="Verdana">Это напоминание о персональной информации было отправлено\r\nавтоматически в ответ на Ваш запрос со страницы входа.\r\nРекомендуем хранить данное письмо в надёжном месте.\r\n\r\n<u>Информация для доступа к управлению Вашей учётной записью</u>:\r\n<i>Номер учётной записи</i>: $cardnumber$\r\n\r\n<i>Ссылка для входа</i>: <a href="$customerinterface$">$customerinterface$</a>\r\n<i>Логин</i>: $login$\r\n<i>Пароль</i>: $password$\r\n<form name="form1" method="post" action="$customerinterface$userinfo.php"><input type="hidden" name="done" value="submit_log"><input type="hidden" name="pr_login" value="$login$"><input type="hidden" name="pr_password" value="$password$"><input name="" type="submit" value="ВОЙТИ"/></form>\r\n\r\n--\r\nСпасибо за ваш интерес к сервису <a href="http://www.my.domain/">TEAM.LTD</a> !</font>', NULL);
INSERT IGNORE INTO cc_templatemail (`id_language`, `mailtype`, `fromemail`, `fromname`, `subject`, `messagetext`, `messagehtml`) VALUES
('en', 'smserror', 'info@my.domain', 'No Reply', 'SMS NOT SENT', '<font face="Verdana">SMS to $phoneto$ have not been sent.\r\n\r\n<b>ERROR DETAILS</b>\r\n\r\n$errors$\r\n\r\n--\r\nKind regards, Robot of <a href="http://www.my.domain/">TEAM.LTD</a></font>', NULL);

CREATE TABLE IF NOT EXISTS `cc_trunk_credit` (
  `trunk_id` int(11) NOT NULL,
  `checktime` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `operator_credit` DECIMAL( 15, 5 ) NOT NULL,
  PRIMARY KEY (`trunk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cc_callplan_lcr` AS SELECT
`cc_ratecard`.`id` AS `id`,
`cc_prefix`.`destination` AS `destination`,
`cc_ratecard`.`dialprefix` AS `dialprefix`,
`cc_ratecard`.`buyrate` AS `buyrate`,
`cc_ratecard`.`rateinitial` AS `rateinitial`,
`cc_ratecard`.`startdate` AS `startdate`,
`cc_ratecard`.`stopdate` AS `stopdate`,
`cc_ratecard`.`initblock` AS `initblock`,
`cc_ratecard`.`connectcharge` AS `connectcharge`,
`cc_ratecard`.`id_trunk` AS `id_trunk`,
`cc_ratecard`.`idtariffplan` AS `idtariffplan`,
`cc_ratecard`.`id` AS `ratecard_id`,
`cc_tariffgroup`.`id` AS `tariffgroup_id`,
`cc_tariffplan`.`tariffname` AS `tariffname`
FROM `cc_tariffgroup_plan`
left join `cc_tariffgroup` on `cc_tariffgroup_plan`.`idtariffgroup` = `cc_tariffgroup`.`id`
join `cc_tariffplan` on `cc_tariffplan`.`id` = `cc_tariffgroup_plan`.`idtariffplan`
left join `cc_ratecard` on `cc_ratecard`.`idtariffplan` = `cc_tariffplan`.`id`
left join `cc_prefix` on `cc_prefix`.`prefix` = `cc_ratecard`.`destination`
where (`cc_ratecard`.`id` is not null);

CREATE TABLE IF NOT EXISTS `cc_queues` (
    `name` VARCHAR(128) NOT NULL,
    musiconhold VARCHAR(128),
    announce VARCHAR(128),
    `context` VARCHAR(128),
    timeout INTEGER,
    ringinuse ENUM('yes','no'),
    setinterfacevar ENUM('yes','no'),
    setqueuevar ENUM('yes','no'),
    setqueueentryvar ENUM('yes','no'),
    monitor_format VARCHAR(8),
    membermacro VARCHAR(512),
    membergosub VARCHAR(512),
    queue_youarenext VARCHAR(128),
    queue_thereare VARCHAR(128),
    queue_callswaiting VARCHAR(128),
    queue_quantity1 VARCHAR(128),
    queue_quantity2 VARCHAR(128),
    queue_holdtime VARCHAR(128),
    queue_minutes VARCHAR(128),
    queue_minute VARCHAR(128),
    queue_seconds VARCHAR(128),
    queue_thankyou VARCHAR(128),
    queue_callerannounce VARCHAR(128),
    queue_reporthold VARCHAR(128),
    announce_frequency INTEGER,
    announce_to_first_user ENUM('yes','no'),
    min_announce_frequency INTEGER,
    announce_round_seconds INTEGER,
    announce_holdtime VARCHAR(128),
    announce_position VARCHAR(128),
    announce_position_limit INTEGER,
    periodic_announce VARCHAR(50),
    periodic_announce_frequency INTEGER,
    relative_periodic_announce ENUM('yes','no'),
    random_periodic_announce ENUM('yes','no'),
    retry INTEGER,
    wrapuptime INTEGER,
    penaltymemberslimit INTEGER,
    autofill ENUM('yes','no'),
    monitor_type VARCHAR(128),
    autopause ENUM('yes','no','all'),
    autopausedelay INTEGER,
    autopausebusy ENUM('yes','no'),
    autopauseunavail ENUM('yes','no'),
    maxlen INTEGER,
    servicelevel INTEGER,
    strategy ENUM('ringall','leastrecent','fewestcalls','random','rrmemory','linear','wrandom','rrordered'),
    joinempty VARCHAR(128),
    leavewhenempty VARCHAR(128),
    reportholdtime ENUM('yes','no'),
    memberdelay INTEGER,
    weight INTEGER,
    timeoutrestart ENUM('yes','no'),
    defaultrule VARCHAR(128),
    timeoutpriority VARCHAR(128),
    PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

ALTER TABLE `cc_queues`
ADD `argument_timeout` INTEGER NOT NULL DEFAULT '600' AFTER `name`,
ADD `argument_options` VARCHAR(128) COLLATE utf8_bin DEFAULT 'cikt' AFTER `argument_timeout`,
ADD `argument_gosub` VARCHAR(128) COLLATE utf8_bin DEFAULT 'getdnid' AFTER `argument_options`,
ADD `id_cc_card` INTEGER NOT NULL DEFAULT '0' AFTER `argument_gosub`,
ADD INDEX `id_cc_card` ( `id_cc_card` );

CREATE TABLE IF NOT EXISTS cc_queue_members (
    uniqueid INTEGER NOT NULL AUTO_INCREMENT UNIQUE,
    queue_name VARCHAR(80) NOT NULL,
    interface VARCHAR(80) NOT NULL,
    membername VARCHAR(80),
    state_interface VARCHAR(80),
    penalty INTEGER,
    paused INTEGER,
    ringinuse ENUM('yes','no'),
    wrapuptime INTEGER,
    PRIMARY KEY (queue_name, interface)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS cc_queue_rules (
    ruleid INTEGER NOT NULL AUTO_INCREMENT UNIQUE,
    rule_name VARCHAR(80) NOT NULL,
    `time` VARCHAR(32) NOT NULL,
    min_penalty VARCHAR(32) NOT NULL,
    max_penalty VARCHAR(32) NOT NULL,
    raise_penalty VARCHAR(32) NOT NULL,
    PRIMARY KEY (rule_name, `time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
ALTER TABLE `cc_queue_rules`
ADD `id_cc_card` INTEGER NOT NULL DEFAULT '0' AFTER `rule_name`,
ADD INDEX `id_cc_card` ( `id_cc_card` );
