#!/usr/bin/php -q
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

/***************************************************************************
 *            currencies_update.php
 *
 *  ADD THIS SCRIPT IN A CRONTAB JOB
 *
	crontab -e
	0 6 * * * php /usr/local/a2billing/Cronjobs/currencies_update.php
	
	field	 allowed values
	-----	 --------------
	minute	 		0-59
	hour		 	0-23
	day of month	1-31
	month	 		1-12 (or names, see below)
	day of week	 	0-7 (0 or 7 is Sun, or use names)
	
	The sample above will run the script every day at 6AM

****************************************************************************/

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit(1);
}

set_time_limit(120);
error_reporting(E_ALL);

$lock_file = '/var/run/a2billing/currencies_update.lock';
$lock = fopen($lock_file, 'c');
if (!$lock) {
    fwrite(STDERR, "Cannot open currency update lock: $lock_file\n");
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB, $would_block)) {
    fwrite(STDERR, $would_block ? "Currency update is already running.\n" : "Cannot lock currency update.\n");
    exit($would_block ? 0 : 1);
}
// The open handle keeps the lock until PHP exits, including on fatal errors.
$success = false;
register_shutdown_function(function () use (&$success) {
    // Legacy configuration loading can terminate with exit() on failure.
    if (!$success) exit(1);
});

require_once __DIR__ . '/lib/vendor/autoload.php';
require_once __DIR__ . '/lib/interface/constants.php';
require_once __DIR__ . '/lib/Class.Table.php';
require_once __DIR__ . '/lib/Class.A2Billing.php';
require_once __DIR__ . '/lib/Misc.php';

$agi = null;
$A2B = new A2Billing();
if (!$A2B->load_conf($agi, DEFAULT_A2BILLING_CONFIG)) {
    fwrite(STDERR, "Cannot load A2Billing configuration.\n");
    exit(1);
}
define('BASE_CURRENCY', strtoupper($A2B->config['global']['base_currency']));

write_log(LOGFILE_CRONT_CURRENCY_UPDATE, basename(__FILE__) . ' [START CURRENCY UPDATE]');
if (!$A2B->DbConnect()) {
    $message = 'Cannot connect to the database.';
    write_log(LOGFILE_CRONT_CURRENCY_UPDATE, basename(__FILE__) . ' ' . $message);
    fwrite(STDERR, $message . "\n");
    exit(1);
}

$instance_table = new Table();
$message = currencies_update($A2B->DBHandle, $instance_table, $success);
write_log(LOGFILE_CRONT_CURRENCY_UPDATE, basename(__FILE__) . ' ' . $message);
fwrite($success ? STDOUT : STDERR, $message . "\n");
exit($success ? 0 : 1);
