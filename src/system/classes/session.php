<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

class Session implements ArrayAccess {
	
	private $container = array();
	
	public function __construct() {
	
		if (!session_id()) {
		
			ini_set('session.use_cookies', 'On');
			ini_set('session.use_only_cookies', 'On');
			ini_set('session.use_trans_sid', 'Off');
			ini_set('session.cookie_httponly', 'On');
			
			session_set_cookie_params(0, '/');
			session_start();
		}
		
		$this->container = &$_SESSION;
	}
	
	public function &get($key, $default = null) {
		if (isset($this->container[$key])) {
			return $this->container[$key];
		} else {
			return $default;
		}
	}
	
	public function set($key, $value = null) {
		$this->container[$key] = $value;
	}
	
	public function getId() {
		return session_id();
	}
	
	public function kill() {
		return session_destroy();
	}
	
	public function &__get($key) {
		if (isset($this->container[$key])) {
			return $this->container[$key];
		} else {
			return null;
		}
	}
	
	public function __set($key, $value = null) {
		$this->container[$key] = $value;
	}
	
	public function __invoke($key) {
		if (isset($this->container[$key])) {
			return $this->container[$key];
		} else {
			return $default;
		}
	}
	
	/* AccessArray methods */
	
	public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }
	
    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }
	
    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }
	
    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

}

