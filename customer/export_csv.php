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


include ("lib/customer.defines.php");
include ("lib/customer.module.access.php");
require_once ("lib/iam_csvdump.php");

if (! has_rights (ACX_CALL_HISTORY)) {
	Header ("HTTP/1.0 401 Unauthorized");
	Header ("Location: PP_error.php?c=accessdenied");
	die();
}

getpost_ifset(array ( 'var_export', 'var_export_type', 'id', 'filename' ));

if (strlen($var_export) == 0) {
	$var_export = 'pr_sql_export';
}

#  Set the parameters: SQL Query, hostname, databasename, dbuser and password
$dumpfile = new iam_csvdump;

#  Call the CSV Dumping function and THAT'S IT!!!!  A file named dump.csv is sent to the user for download

if (strlen($_SESSION[$var_export]) < 10) {
	echo gettext("ERROR CSV EXPORT");
} else {
	$log = new Logger();
	$myfileName = "Dump_" . date("Y-m-d");
	$QUERY = $_SESSION[$var_export];
	if (strcmp($var_export, "pr_export_entity_ringup") == 0) {
	    $myfileName = $filename . "_" . date("Y-m-d");
	    if (is_numeric($id))
		$QUERY .= $id." ORDER BY channelstatedesc DESC, lastattempt";
	    else
		$log->insertLog($_SESSION["card_id"], 2, "FILE EXPORT FAILED", "A File in CSV Format was not exported by User, File Name= " . $myfileName . ".csv", '', $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], '');
	}
	if (strcmp($var_export_type, "type_csv") == 0) {
		$log->insertLog($_SESSION["card_id"], 2, "FILE EXPORTED", "A File in CSV Format is exported by User, File Name= " . $myfileName . ".csv", '', $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], '');
		$dumpfile->dump($QUERY, $myfileName, "csv", DBNAME, USER, PASS, HOST, DB_TYPE);
	}
	elseif (strcmp($var_export_type, "type_xml") == 0) {
		$log->insertLog($_SESSION["card_id"], 2, "FILE EXPORTED", "A File in XML Format is exported by User, File Name= " . $myfileName . ".xml", '', $_SERVER['REMOTE_ADDR'], $_SERVER['REQUEST_URI'], '');
		$dumpfile->dump($QUERY, $myfileName, "xml", DBNAME, USER, PASS, HOST, DB_TYPE);
	}
	$log = null;
}
