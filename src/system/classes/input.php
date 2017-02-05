<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Main "Request" class
*/

defined('SYSTEM_ROOT') OR exit('No direct script access allowed');

class Input implements ArrayAccess {
	
	public $request = null;

	private $value = null;
	private $from = null;
	private $key = '';
	private $page = null;
	private $tmpname = '';
	private $filename = '';
	private $filesize = null;
	private $filetype = null;
	private $error = null;
	
	private $params = array();

	public function __construct(&$page = null) {
	
		$this->page = &$page;
		
		$this->request = new stdClass;
		
		$this->request->get = &$_GET;
		$this->request->post = &$_POST;
		$this->request->request = &$_REQUEST;
		$this->request->files = &$_FILES;
		if (isset($_SESSION)) {
			$this->request->session = &$_SESSION;
		}
		$this->request->cookie = &$_COOKIE;
		$this->request->server = &$_SERVER;
		
	}

	public function __toString() {
		return $this->value;
	}
	
	public function __isset($param) {
		return isset($this->from[$param]);
	}
	
	public function &__get($key) {
		$this->from = &$this->request->{$key};
		return $this;
	}
	
	public function __set($key, $value) {
		$this->from = &$this->request->{$key};
		return $this;
	}
	
	public function method($allow_override = true) {
	
		$method = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET');
		
		if ($allow_override && $method === 'POST') {
		
			if (isset($_SERVER['X_HTTP_METHOD_OVERRIDE'])) {
				$method = (isset($_SERVER['X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['X_HTTP_METHOD_OVERRIDE'] : $method);
			}
			
			$method = strtoupper($method);
		}
		
		return $method;
	}
	
	public function get($key, $default = null, $escape = false) {
	
		$this->from = &$_GET;
		$this->key = $key;
		
		if (isset($_GET[$key])) {
			$this->value = $_GET[$key];
		} else {
			$this->value = $default;
		}
		
		if ($escape) { $this->value = $this->myescape($this->value); }
		
		return $this;
		
	}
	
	public function post($key, $default = null, $escape = false) {
	
		$this->from = &$_POST;
		$this->key = $key;
		
		if (isset($_POST[$key])) {
			$this->value = $_POST[$key];
		} else {
			$this->value = $default;
		}
		
		if ($escape) { $this->value = $this->myescape($this->value); }
		
		return $this;
		
	}
	
	public function files($key, $escape = false) { //add array handling
	
		$this->from = &$_FILES;
		$this->key = $key;
		
		if (!empty($key) && isset($_FILES[$key])) {
			
			$this->value = $_FILES[$key];
			$this->filename = $_FILES[$key]['name'];
			$this->filesize = (float)$_FILES[$key]['size'];
			$this->filetype = $_FILES[$key]['type'];
			$this->tmpname = $_FILES[$key]['tmp_name'];
			$this->error = $_FILES[$key]['error'];
			
			if ($escape === true) { $this->filename = $this->myescape($this->filename); }
			
		}
		
		return $this;
		
	}
	
	public function params($key, $default = null, $escape = false) {
	
		$this->from = &$this->params;
		$this->key = $key;
		
		if (isset($this->params[$key])) {
			$this->value = $this->params[$key];
		} else {
			$this->value = $default;
		}
		
		if ($escape) { $this->value = $this->myescape($this->value); }
		
		return $this;
		
	}
	
	public function setParams($value) {
		$this->params = $value;
	}
	
	public function exists() {
		return isset($this->from[$this->key]);
	}
	
	public function val($escape = false) {
		return ($escape ? $this->myescape($this->value) : $this->value);
	}
	
	public function value($escape = false) {
		return ($escape ? $this->myescape($this->value) : $this->value);
	}
	
	public function filename() {
		if ($this->from != $_FILES) {
			return false;
		}
		return (string)$this->filename;
	}
	
	public function filesize() {
		if ($this->from != $_FILES) {
			return false;
		}
		return $this->filesize;
	}
	
	public function filetype() {
		if ($this->from != $_FILES) {
			return false;
		}
		return $this->filetype;
	}
	
	public function tmpname() {
		if ($this->from != $_FILES) {
			return false;
		}
		return (string)$this->tmpname;
	}
	
	public function fileerror() {
		if ($this->from != $_FILES) {
			return false;
		}
		return $this->error;
	}
	
	public function upload($to, $key = null, $images = false) {
		return $this->copyto($to, $key, $images);
	}
	
	public function uploadAll($to, $images = false) {
		return $this->uploadFiles($to, $images);
	}
	
	public function uploadFiles($to, $images = false) {
	
		$ret = false;
		
		foreach($_FILES as $key => $file) {
		
			$ret = $this->copyto($to, $key, $images);
			
			if ($ret === false) {
				trigger_error('Upload error on file with key: '.$key.', filename: '.$file['name'], E_USER_WARNING);
				return false;
			}
		}
		
		return $ret;
	}
	
	public function uploadImage($to, $key = null) {
		return $this->copyto($to, $key, true);
	}
	
	public function uploadImages($to) {
		return $this->uploadFiles($to, true);
	}
	
	public function copyto($to, $key = null, $images = false) {
		
		if ($this->from != $_FILES) {
			trigger_error('Not called from file()', E_USER_WARNING);
			return false;
		}
		
		if (empty($key)) {
			$key = $this->key;
		}
		
		if (!empty($key) && isset($_FILES[$key]) && $_FILES[$key]['error'] == UPLOAD_ERR_OK) {
		
			if ($images == true && $this->isImage($_FILES[$key]['name']) == false) {
				return (-1);
			}
			
			if (is_dir($to)) {
				$to = $to.'/'.$_FILES[$key]['name'];
			}
			
			if (!is_dir(dirname($to))) {
				trigger_error('Upload directory does not exist!', E_USER_WARNING); //throw error??
				return false;
			}
			
			return move_uploaded_file($_FILES[$key]['tmp_name'], $to);
			
		} else {
			trigger_error('Error on file upload - key: ['.$key.'], isset(key): ['.(isset($_FILES[$key]) ? 'true': 'false').'], Error: ['.($_FILES[$key]['error'] == 0 ? '0' : $_FILES[$key]['error']).']', E_USER_WARNING);
			return false;
		}
		
	}
	
	public function escape($return = false) {
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
	
	public function escaped() {
		return $this->myescape($this->value);
	}
	
	public function quote($return = false) {
		$this->value = '\''.$this->value.'\'';
		if ($return) {
			return $this->value;
		} else {
			return $this;
		}
	}
	
	public function quoted() {
		return '\''.$this->myescape($this->value).'\'';
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
	
	public function isAjax() {
		//return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
	
	public function isSecure() {
		return ($_SERVER['HTTPS'] == true);
	}
	
	public function getUserAgent() {
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
	public function ip() {
		return $_SERVER['REMOTE_ADDR'];
	}
	
	public function uri() {
		return (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/');
	}
	
	public function referer() {
		return (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
	}
	
	/* AccessArray methods */
	
	public function offsetSet($offset, $value) {
		$this->from[$offset] = $value;
    }
	
    public function offsetExists($offset) {
        return isset($this->from[$offset]);
    }
	
    public function offsetUnset($offset) {
        unset($this->from[$offset]);
    }
	
    public function offsetGet($offset) {
        return isset($this->from[$offset]) ? $this->from[$offset] : 'null';
    }
	
	/* private functions */
	
	private function isImage($filename) {
		return (bool) ((preg_match('#\.(gif|jpg|jpeg|jpe|png)$#i', $filename)) ? true : false);
	}
	
}

