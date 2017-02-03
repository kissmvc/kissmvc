<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Main Database module, MySqli used
*/

defined('SYSTEM_ROOT') OR exit('No direct script access allowed');

class Database { //implements ArrayAccess

	public $log_query = false;
	public $log_path = '';

	private $server = null;
	private $crows = 0;
	
	private $schema = array();
	
	private $host = '127.0.0.1';
	private $user = '';
	private $pass = '';
	private $port = 0;
	private $db = '';
	private $prefix = null;
	
	public function __construct($server = '127.0.0.1', $user = null, $pass = null, $db = null, $port = 0, $prefix = null) {
	
		if (is_array($server)) {
			$this->host = $server['host'];
			$this->user = $server['user'];
			$this->pass = $server['pass'];
			$this->port = (isset($server['port']) ? $server['port']: null);
			$this->db = $server['db'];
			$this->prefix = (isset($server['prefix']) ? $server['prefix']: '');
		} else {
			$this->host = $server;
			$this->user = $user;
			$this->pass = $pass;
			$this->port = $port;
			$this->db = $db;
			$this->prefix = $prefix;
		}
		
		if (isset($this->host) && isset($this->user) && isset($this->pass) && isset($this->db)) {
			$this->connect();
		}
		
  	}
	
	public function connect($host = '127.0.0.1', $user = null, $pass = null, $db = null, $port = 0, $prefix = null) {
	
		$this->host = (isset($host) ? $host : $this->host);
		$this->user = (isset($user) ? $user : $this->user);
		$this->pass = (isset($pass) ? $pass : $this->pass);
		$this->port = (isset($port) ? $port : $this->port);
		$this->db = (isset($db) ? $db : $this->db);
		$this->prefix = (isset($prefix) ? $prefix : $this->prefix);
		
		if ($this->port > 0) {
			$this->server = new mysqli($this->host, $this->user, $this->pass, $this->db, $this->port);
		} else {
			$this->server = new mysqli($this->host, $this->user, $this->pass, $this->db);
		}
		
		if ($this->server->connect_error) {
      		trigger_error('Error: Database connection error - ' . $this->server->connect_errno . ': ' . $this->server->connect_error);
		}
		
		$this->server->query("SET NAMES 'utf8'");
		$this->server->query("SET CHARACTER SET utf8");
		$this->server->query("SET CHARACTER_SET_CONNECTION=utf8");
		$this->server->query("SET SQL_MODE = ''");
		
		$this->fillSchema();
		
		if ($this->log_query == true) {
			$qurl = $_SERVER['REQUEST_URI'];
			if (file_exists($this->log_path.'/queries.txt')) {
				@file_put_contents($this->log_path.'/queries.txt', "\n=== NEW PAGE QUERY ===  ".date("Y-m-d H:i:s.u")."  ===\n\nURL: $qurl\n\n", FILE_APPEND);
			} else { 
				@file_put_contents($this->log_path.'/queries.txt', "=== NEW PAGE QUERY ===  ".date("Y-m-d H:i:s.u")."  ===\n\nURL: $qurl\n\n", FILE_APPEND);
			}
		}
		
		return true;
		
	}
	
	public function fillSchema(/*$cached = true*/) {
	
		if (!empty($this->prefix)) {
			$sql = 'SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.key_column_usage WHERE table_schema = schema() AND table_name LIKE \''.$this->prefix.'%\' AND  CONSTRAINT_NAME = \'PRIMARY\'';
		} else {
			$sql = 'SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.key_column_usage WHERE table_schema = schema() AND CONSTRAINT_NAME = \'PRIMARY\'';
		}
		$result = $this->server->query($sql);
		
		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			$this->schema[$row[0]] = $row[1];
		}
		
	}
	
	public function getSchema() {
		return $this->schema;
	}
	
	public function debugQuery($sql) {
		
		if ($this->log_query == true) {
		
			static $qcount;
			$qcount++;
			
			if (version_compare(phpversion(), '5.4', '<=')) {
				$trace = debug_backtrace(true);
			} else {
				$trace = debug_backtrace(true, 2);
			}
			$trace = $trace[1];
			$str_trace = '('.$qcount.') Query From: '.$trace['file'].' Line: '.$trace['line'].PHP_EOL.$sql.PHP_EOL.PHP_EOL;
			
			@file_put_contents($this->log_path.'/queries.txt', $str_trace, FILE_APPEND);
			
		}
	}
	
  	public function query($sql/*, $cached = true*/) { //add limit offset -- , $cached = false
	
		$this->crows = 0;
		
		try {
			
			$this->debugQuery($sql);
			
			$result = $this->server->query($sql);
			
			if (!$this->server->errno) {
			
				if (is_object($result)) {
				
					return $this->convertResult($result);
					
				} else {
				
					return true;
					
				}
				
			} else {
			
				trigger_error('Database error - '.$this->server->errno.': '.$this->server->error.' - '.$sql, E_USER_ERROR);
				exit();
				
			}
			
		} catch (Exception $e) {
		
			if (!$this->check()) {
				trigger_error($e->getMessage(), E_USER_ERROR);
			}
			
		}
  	}
	
	public function select($table, $fieldsorid = '*', $idorwhere = null, $limit = null, $offset = null, $order = '') {
		
		$fields = '*';
		
		if (is_array($fieldsorid)) {
			$fields = '`'.implode('`, `', $fieldsorid).'`';
		}
		
		if (empty($fieldsorid)) {
			$fields = '*';
		}
		
		$where = null;
		$table = $this->prefix.$table;
		
		if (is_int($fieldsorid)) {
			$where = $this->schema[$table].' = '.$fieldsorid;
		}
		
		if (is_int($idorwhere)) {
			$where = $this->schema[$table].' = '.$idorwhere;
		} elseif (is_string($idorwhere)) {
			$where = $idorwhere;
		}
		
		$sql = '';
		$sql .= 'SELECT '.$fields.' FROM '.$table;
		$sql .= (!empty($where) ? ' WHERE '.$where : '');
		$sql .= (!empty($order) ? ' ORDER BY '.$order.' ': '');
		$sql .= (!empty($limit) ? ' LIMIT '.(int)$limit.' ': '');
		$sql .= (!empty($offset) ? ' OFFSET '.(int)$offset.' ': '');
		
		$this->debugQuery($sql);
		
		$result = $this->server->query($sql);
		
		return $this->convertResult($result);
		
  	}
	
	public function insert($table, $values) {
	
		if (is_array($values)) {
		
			$table = $this->prefix.$table;
			
			$values = $this->escape($values);
			
			$sql = 'INSERT INTO '.$table.' (`'.implode('`, `', array_keys($values)).'`) VALUES (\''.implode('\', \'', $values).'\')';
			
			$this->debugQuery($sql);
			
			$result = $this->server->query($sql);
			
			if (!$this->server->errno) {
				return ($result === true ? $this->server->insert_id : false);
			} else {
				trigger_error('Database error - Insert failed - '.$this->server->errno.': '.$this->server->error, E_USER_ERROR);
			}
			
		} else {
			trigger_error('Database error - Values must be array!', E_USER_ERROR);
		}
		
  	}
	
	public function insertupdate($table, $values, $id) {
	
		if (is_array($values)) {
			
			if ((int)$id > 0) {
				return $this->update($table, $values, $id);
			} else {
				return $this->insert($table, $values);
			}
			
		} else {
			trigger_error('Database error - Values must be array!', E_USER_ERROR);
		}
  	}
	
	public function update($table, $values, $id) {
	
		if (is_array($values)) {
		
			$table = $this->prefix.$table;
			$id_field = $this->schema[$table];
			
			$values = $this->escape($values);
			
			$vals = '';
			foreach ($values as $k=>$v) { 
				if ($vals !== '') {
					$vals .= ',';
				}
				$vals .= "{$k}='{$v}'";
			}
			
			$sql = 'UPDATE '.$table.' SET '.$vals.' WHERE `'.$id_field.'` = '.(int)$id;
			
			$this->debugQuery($sql);
			
			$result = $this->server->query($sql);
			
			if (!$this->server->errno) {
				return $result;
			} else {
				trigger_error('Database error - Update failed - '.$this->server->errno.': '.$this->server->error, E_USER_ERROR);
			}
			
		} else {
			trigger_error('Database error - Values must be array!', E_USER_ERROR);
		}
  	}
	
	public function delete($table, $idorwhere = '') {
	
		$table = $this->prefix.$table;
		$id_field = $this->schema[$table];
		
		if (is_int($idorwhere)) {
			$where = '`'.$this->schema[$table].'` = '.(int)$idorwhere;
		} else {
			$where = $idorwhere;
		}
		
		$sql = 'DELETE FROM '.$table.' WHERE '.$where;
		
		$this->debugQuery($sql);
		
		$result = $this->server->query($sql);
		
		if (!$this->server->errno) {
			return $result;
		} else {
			trigger_error('Database error - Delete failed - '.$this->server->errno.': '.$this->server->error, E_USER_ERROR);
		}
  	}
	
  	public function check() {
	
		if ($this->server->ping() == false) { //->query('SELECT 1');
		
			@$this->server->close();
			return $this->connect();
			
		} else {
			return true;
		}
		
  	}
	
  	public function escape($data, $quote = false) {
		
    	if (is_array($data)) {
		
	  		foreach ($data as $key => $value) {
	    		$data[$key] = $this->escape($value);
	  		}
			
		} else {
			
			if (is_numeric($data)) {
				return ($quote ? $this->quote($data) : $data);
			} elseif (is_bool($data)) {
				$data = $data ? 1 : 0;
				return ($quote ? $this->quote($data) : $data);
			} elseif (is_null($data)) {
				$data = 'NULL';
				return ($quote ? $this->quote($data) : $data);
			} else {
				if (is_object($this->server)) {
					if (method_exists($this->server, 'real_escape_string')) {
						$data = $this->server->real_escape_string($data);
					} else {
						$data = addslashes(htmlspecialchars($data));
					}
				} else {
					$data = addslashes(htmlspecialchars($data));
				}
				//return $data;
				return ($quote ? $this->quote($data) : $data);
			}
			
		}
		
		//return $data;
		return ($quote ? $this->quote($data) : $data);
	}
	
	public function quote($value) {
		return '\''.$value.'\'';
	}
	
	public function connected() {
		return $this->server->ping();
	}
	
	public function datetime($date = null) {
		if (empty($date)) {
			return date('Y-m-d H:i:s');
		} else {
			return date('Y-m-d H:i:s', strtotime($date));
		}
	}
	
	public function date($date = null) {
		if (empty($date)) {
			return date('Y-m-d');
		} else {
			return date('Y-m-d', strtotime($date));
		}
	}
	
	public function now() {
		return date('Y-m-d H:i:s');
	}
	
  	public function count() {
    	return $this->crows;
  	}
	
  	public function affected() {
    	return $this->server->affected_rows;
  	}

  	public function lastId() {
    	return $this->server->insert_id;
  	}	

  	public function getLastId() {
    	return $this->server->insert_id;
  	}	

  	public function &getMySqli() {
    	return $this->server;
  	}	

  	public function getLastError() {
    	return $this->server->errno;
  	}	
	
	public function __destruct() {
		if (isset($this->server)) {
			@$this->server->close();
		}
	}

	private function convertResult($result) {
	
		if (is_object($result)) {
		
			$i = 0;
			
			$data = array();
			
			while ($row = $result->fetch_object()) {
				$data[$i] = $row;
				
				$i++;
			}
			
			$output = new stdObject();
			$output->row = isset($data[0]) ? $data[0] : array();
			$output->rows = $data;
			$output->count = $result->num_rows;
			
			$output->setDefault($output->rows);
			
			$result->close();
			
			$this->crows = $i;
			
			unset($data);
			
			return $output;
			
		} else {
			return false;
		}
		
	}
}

