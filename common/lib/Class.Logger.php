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

class Logger
{
	public $do_debug = 0;
	
	//constructor
	public function __construct()
	{
	
	}

	//Funtion deleteLog
	//Delete the log from table
	function deleteLog($id = 0)
	{
		$DB_Handle = DBConnect();
		$table_log = new Table();
		$QUERY = "DELETE FROM cc_system_log WHERE id = ".$id;		
		if ($this -> do_debug) echo $QUERY;		
		$table_log -> SQLExec($DB_Handle, $QUERY);
	}

	//Function insertLog
	// Inserts the Log into table
	function insertLog_Add($userID, $logLevel, $actionPerformed, $description, $tableName, $ipAddress, $pageName, $param_add_fields, $param_add_value, $agent=0)
	{
		$DB_Handle = DBConnect();
		$table_log = new Table();
		$pageName    = explode('?', basename($pageName));
		$pageName    = array_shift($pageName);
/**		$QUERY = "SELECT id,loglevel,pagename FROM cc_system_log WHERE agent = ".$agent." AND iduser = " . $userID . " ORDER BY id DESC LIMIT 1";
		$resmax = $table_log -> SQLExec ($DB_Handle, $QUERY, 1);
		if ($resmax && $resmax[0][1]==1 && $resmax[0][2]==$pageName) {
			$this -> deleteLog($resmax[0][0]);
		}
**/		$description = str_replace("'", "", $description);
		$str_submitted_fields = explode(',', $param_add_fields);
		$str_submitted_values = explode(',', $param_add_value);
		$num_records = count($str_submitted_fields);
		for($num = 0; $num < $num_records; $num++)
		{
			$str_name_value_pair .= $str_submitted_fields[$num]." = ".str_replace("'",'',$str_submitted_values[$num]);
			if($num != $num_records -1)
			{
				$str_name_value_pair .= "|";
			}
		}
		$QUERY = "INSERT INTO cc_system_log (iduser, loglevel, action, description, tablename, pagename, ipaddress, data, agent) ";
		$QUERY .= " VALUES('".$userID."','".$logLevel."','".$actionPerformed."','".$description."','".$tableName."','".$pageName."','".$ipAddress."','".$str_name_value_pair."','".$agent."')";
		if ($this -> do_debug) echo $QUERY;

		$table_log -> SQLExec($DB_Handle, $QUERY);		
	}
/**
	function insertLog_noBlob($userID, $logLevel, $actionPerformed, $description, $tableName, $ipAddress, $pageName, $param_update, $agent=0)
	{
		$DB_Handle = DBConnect();
		$table_log = new Table();
		$pageName    = explode('?', basename($pageName));
		$pageName    = array_shift($pageName);
		$description = str_replace("'", "", $description) . implode(',', $param_update);
		$QUERY = "INSERT INTO cc_system_log (iduser, loglevel, action, description, tablename, pagename, ipaddress, agent) ";
		$QUERY .= " VALUES('".$userID."','".$logLevel."','".$actionPerformed."','".$description."','".$tableName."','".$pageName."','".$ipAddress."','".$agent."')";
		
		if ($this -> do_debug) echo $QUERY;

		$table_log -> SQLExec($DB_Handle, $QUERY);
	}
**/
	function insertLog_Update($userID, $logLevel, $actionPerformed, $description, $tableName, $ipAddress, $pageName, $param_update, $agent=0)
	{
		$DB_Handle = DBConnect();
		$table_log = new Table();
		$pageName    = explode('?', basename($pageName));
		$pageName    = array_shift($pageName);
		$QUERY = "SELECT id,loglevel,pagename FROM cc_system_log WHERE iduser = ".$userID." AND agent = ".$agent." ORDER BY id DESC LIMIT 1";
		$resmax = $table_log -> SQLExec ($DB_Handle, $QUERY, 1);
		if ($resmax && $resmax[0][1]==1 && $resmax[0][2]==$pageName) {
			$this -> deleteLog($resmax[0][0]);
		}
		$description = str_replace("'", "", $description);
		$str_submitted_fields = explode(',', $param_update);
		$num_records = count($str_submitted_fields);
		for($num = 0; $num < $num_records; $num++)
		{
			$str_name_value_pair .= str_replace("'","",$str_submitted_fields[$num]);
			if($num != $num_records -1)
			{
				$str_name_value_pair .= "|";
			}
		}
		$QUERY = "INSERT INTO cc_system_log (iduser, loglevel, action, description, tablename, pagename, ipaddress, data, agent) ";
		$QUERY .= " VALUES('".$userID."','".$logLevel."','".$actionPerformed."','".$description."','".$tableName."','".$pageName."','".$ipAddress."','".$str_name_value_pair."','".$agent."')";
		
		if ($this -> do_debug) echo $QUERY;

		$table_log -> SQLExec($DB_Handle, $QUERY);		
	}	
	
	function insertLog($userID, $logLevel, $actionPerformed, $description, $tableName, $ipAddress, $pageName, $data='', $agent=0)
	{
	    if ($agent==0 || $logLevel!=1 || !isset($_SESSION["admin_ip"] ) || $_SESSION["admin_ip"] != $_SERVER["REMOTE_ADDR"]) {
		$DB_Handle = DBConnect();
		$table_log = new Table();
		$pageName    = explode('?', basename($pageName));
		$pageName    = array_shift($pageName);
		$QUERY = "SELECT pagename FROM cc_system_log WHERE iduser = ".$userID." AND agent = ".$agent." AND (loglevel = 1 OR pagename = '".$pageName."') ORDER BY id DESC LIMIT 1";
		$resmax = $table_log -> SQLExec ($DB_Handle, $QUERY, 1);
		if ($logLevel==1 && $resmax && $resmax[0][0] && $resmax[0][0]==$pageName) {
			return;
		}
		$description = str_replace("'", "", $description);
		
		$QUERY = "INSERT INTO cc_system_log (iduser, loglevel, action, description, tablename, pagename, ipaddress, data, agent) ";
		$QUERY .= " VALUES('".$userID."','".$logLevel."','".$actionPerformed."','".$description."','".$tableName."','".$pageName."','".$ipAddress."','".$data."','".$agent."')";
		if ($this -> do_debug) echo $QUERY;

		$table_log -> SQLExec($DB_Handle, $QUERY);
	    }
	}
	
	function insertLogAgent($userID, $logLevel, $actionPerformed, $description, $tableName, $ipAddress, $pageName, $data='')
	{
		$DB_Handle = DBConnect();
		$table_log = new Table();		
		$pageName    = explode('?', basename($pageName));
		$pageName    = array_shift($pageName);
		$description = str_replace("'", "", $description);
		
		$QUERY = "INSERT INTO cc_system_log (iduser, loglevel, action, description, tablename, pagename, ipaddress, data, agent) ";
		$QUERY .= " VALUES('".$userID."','".$logLevel."','".$actionPerformed."','".$description."','".$tableName."','".$pageName."','".$ipAddress."','".$data."', 1)";
		if ($this -> do_debug) echo $QUERY;

		$table_log -> SQLExec($DB_Handle, $QUERY);		
	}
}
