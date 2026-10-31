<?php

/* - - - - - - - - - - - - - - - - - - - - -

 Title : PHP Quick Profiler MySQL Class
 Author : Created by Ryan Campbell
 URL : http://particletree.com/features/php-quick-profiler/

 Last Updated : April 22, 2009

 Description : A simple database wrapper that includes
 logging of queries.

- - - - - - - - - - - - - - - - - - - - - */

class MySqlDatabase {

	private $host;			
	private $user;		
	private $password;	
	private $database;	
	public $queryCount = 0;
	public $queries = array();
	public $conn;
	
	/*------------------------------------
	          CONFIG CONNECTION
	------------------------------------*/
	
	function __construct($host, $user, $password) {
		$this->host = $host;
		$this->user = $user;
		$this->password = $password;
	}
	
	function connect($new = false) {
		if (!$new && $this->conn) return;
		$host = $this->host;
		$port = ini_get('mysqli.default_port');
		$socket = ini_get('mysqli.default_socket');
		// Preserve the legacy host:port and host:/socket constructor syntax.
		if (preg_match('/^(.+):([0-9]+)$/', $host, $parts)) {
			$host = $parts[1];
			$port = (int)$parts[2];
		} elseif (preg_match('#^(.*):(/.*)$#', $host, $parts)) {
			$host = $parts[1] === '' ? 'localhost' : $parts[1];
			$socket = $parts[2];
		}
		try {
			$this->conn = mysqli_connect($host, $this->user, $this->password, null, (int)$port, $socket);
		} catch (mysqli_sql_exception $e) {
			throw new Exception('We\'re working on a few connection issues.');
		}
		if(!$this->conn) {
			throw new Exception('We\'re working on a few connection issues.');
		}
	}
	
	function changeDatabase($database) {
		$this->database = $database;
		if($this->conn) {
			try {
				$selected = mysqli_select_db($this->conn, $database);
			} catch (mysqli_sql_exception $e) {
				$selected = false;
			}
			if(!$selected) {
				throw new Exception('We\'re working on a few connection issues.');
			}
		}
	}
	
	function lazyLoadConnection() {
		$this->connect(true);
		if($this->database) $this->changeDatabase($this->database);
	}
	
	/*-----------------------------------
	   				QUERY
	------------------------------------*/
	
	function query($sql) {
		if(!$this->conn) $this->lazyLoadConnection();
		$start = $this->getTime();
		try {
			$rs = mysqli_query($this->conn, $sql);
		} catch (mysqli_sql_exception $e) {
			$rs = false;
		}
		$this->queryCount += 1;
		$this->logQuery($sql, $start);
		if(!$rs) {
			throw new Exception('Could not execute query.');
		}
		return $rs;
	}
	
	/*-----------------------------------
	          	DEBUGGING
	------------------------------------*/
	
	function logQuery($sql, $start) {
		$query = array(
				'sql' => $sql,
				'time' => ($this->getTime() - $start)*1000
			);
		array_push($this->queries, $query);
	}
	
	function getTime() {
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		return $start;
	}
	
	public function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret,3,'.','') . ' ' . $formats[$formatter];
		return $ret;
	}
	
	function __destruct()  {
		if ($this->conn instanceof mysqli) mysqli_close($this->conn);
	}
	
}

?>
