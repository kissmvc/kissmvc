<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Main "Request" class
*/

class Input {
	
	public $request = null;

	private $value = null;
	private $from = '';
	private $page = null;
	private $tmpname = '';
	private $filename = '';
	private $filesize = null;
	private $filetype = null;
	private $error = null;

	public function __construct(&$page = null) {
	
		$this->page = &$page;
		
		$this->request = new stdClass;
		
		$this->request->get = $_GET;
		$this->request->post = $_POST;
		$this->request->request = $_REQUEST;
		$this->request->files = $_FILES;
		if (isset($_SESSION)) {
			$this->request->session = $_SESSION;
		}
		$this->request->cookie = $_COOKIE;
		$this->request->server = $_SERVER;
		
	}

	public function __toString() {
		return $this->value;
	}
	
	public function get($from, $default = null, $escape = false) {
	
		$this->from = 'GET';
		
		if (isset($_GET[$from])) {
			$this->value = $_GET[$from];
		} else {
			$this->value = $default;
		}
		
		if ($escape) { $this->value = $this->myescape($this->value); }
		
		return $this;
		
	}
	
	public function post($from, $default = null, $escape = false) {
	
		$this->from = 'POST';
		
		if (isset($_POST[$from])) {
			$this->value = $_POST[$from];
		} else {
			$this->value = $default;
		}
		
		if ($escape) { $this->value = $this->myescape($this->value); }
		
		return $this;
		
	}
	
	public function files($from, $escape = false) { //add array handling
	
		$this->from = 'FILES';
		
		if (isset($_FILES[$from])) {
			
			$this->value = $_FILES[$from];
			$this->filename = $_FILES[$from]['name'];
			$this->filesize = (float)$_FILES[$from]['size'];
			$this->filetype = $_FILES[$from]['type'];
			$this->tmpname = $_FILES[$from]['tmp_name'];
			$this->error = $_FILES[$from]['error'];
			
			if ($escape) { $this->filename = $this->myescape($this->filename); }
			
		}
		
		return $this;
		
	}
	
	public function is_set() {
		return isset($this->value);
	}
	
	public function val($escape = false) {
		return ($escape ? $this->myescape($this->value) : $this->value);
	}
	
	public function value($escape = false) {
		return ($escape ? $this->myescape($this->value) : $this->value);
	}
	
	public function filename() {
		return (string)$this->filename;
	}
	
	public function copyto($to) {
		if ($this->filename) {
			return move_uploaded_file($this->filename, $to);
		} else {
			return false;
		}
	}
	
	public function escape($return = true) {
		$this->value = $this->myescape($this->value);
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	private function myescape($data) {
		
    	if (is_array($data)) {
		
	  		foreach ($data as $key => $value) {
	    		$data[$key] = $this->myescape($value);
	  		}
			
		} else {
		
			if (is_object($this->page->db) && $this->page->db->connected()) {
			
				return $this->page->db->escape($data);
				
			} else {
			
				if (is_numeric($data)) {
					return $data;
				} elseif (is_bool($data)) {
					return $data ? 1 : 0;
				} elseif (is_null($data)) {
					return 'NULL';
				} else {
					return addslashes(htmlspecialchars($data));
				}
				
			}
			
		}
		
		return $data;
	}
	
	public function as_int($escape = true) {
		return ($escape ? $this->myescape($this->to_int(true)) : $this->to_int(true));
	}
	
	public function as_float($escape = true) {
		return ($escape ? $this->myescape($this->to_float(true)) : $this->to_float(true));
	}
	
	public function as_bool($escape = true) {
		return ($escape ? $this->myescape($this->to_bool(true)) : $this->to_bool(true));
	}
	
	public function as_string($escape = true) {
		return ($escape ? $this->myescape($this->to_string(true)) : $this->to_string(true));
	}
	
	public function as_str($escape = true) {
		return ($escape ? $this->myescape($this->to_string(true)) : $this->to_string(true));
	}
	
	public function as_text($escape = true) {
		return ($escape ? $this->myescape($this->to_string(true)) : $this->to_string(true));
	}
	
	public function as_date($escape = true) {
		return ($escape ? $this->myescape($this->to_date(true)) : $this->to_date(true));
	}
	
	public function as_datetime($escape = true) {
		return ($escape ? $this->myescape($this->to_datetime(true)) : $this->to_datetime(true));
	}
	
	public function as_time($escape = true) {
		return ($escape ? $this->myescape($this->to_time(true)) : $this->to_time(true));
	}
	
	public function check($return = false) {
		if ($return) {
			$this->value = (isset($this->value) ? 1 : 0);
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function now() {
		return date('Y-m-d H:i:s');
	}
	
	public function to_int($return = false) {
		$this->value = (int)$this->value;
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function to_float($return = false) {
		$this->value = (float)$this->value;
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function to_bool($return = false) {
		$this->value = (bool)$this->value;
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function to_string($return = false) {
		$this->value = (string)$this->value;
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function to_str($return = false) {
		return $this->to_string($return );
	}
	
	public function to_text($return = false) {
		return $this->to_string($return );
	}
	
	public function to_lower($return = false) {
		if ($return) {
			return strtolower($this->value);
		} else {
			$this->value = strtolower($this->value);
			return $this;
		}
	}
	
	public function to_upper($return = false) {
		if ($return) {
			return strtoupper($this->value);
		} else {
			$this->value = strtoupper($this->value);
			return $this;
		}
	}
	
	public function to_date($return = false) {
		$this->value = date('Y-m-d', strtotime($this->value));
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function to_datetime($return = false) {
		$this->value = date('Y-m-d H:i:s', strtotime($this->value));
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function to_time($return = false) {
		$this->value = date('H:i:s', strtotime($this->value));
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
}

