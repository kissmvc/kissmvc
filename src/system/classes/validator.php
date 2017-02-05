<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Main Validation & Sanitization class
*/

defined('SYSTEM_ROOT') OR exit('No direct script access allowed');

class Validator {

	private $validators = array();
	private $sanitizators = array();

	public function __construct() {
		//
	}
	
	public function addValidator($name, $value) {
		$this->validators[$name] = $value;
		return $this;
	}
	
	public function validate($name, $value) {
	
		if (method_exists($this, $name)) {
			return call_user_func('Validator::'.$name, $value); //return $this->{$name}($value);
		}
		
		if (is_int($name)) {
			
			return filter_var($value, $name) !== false;
			
		} else {
			
			if (isset($this->validators[$name])) {
				
				if (is_callable($this->validators[$name])) {
					//return call_user_func($this->validators[$name], $name, $value);
					return call_user_func($this->validators[$name], $value);
				} elseif ($name[0] == '/') {
					return preg_match($this->validators[$name], $value) ? true : false;
				}
				
			}
			
		}
		
		return false;
	}
	
	public function addSanitizator($name, $value) {
		$this->sanitizators[$name] = $value;
		return $this;
	}
	
	public function sanitize($name, $value, $args = null) {
	
		if (method_exists($this, 'sanitize_'.$name)) {
			return call_user_func('Validator::sanitize_'.$name, $value);
		}
		
		if (is_int($name)) {
			
			if (!empty($args)) {
				return filter_var($value, $name, $args);
			} else {
				return filter_var($value, $name);
			}
			
		} else {
			
			if (isset($this->sanitizators[$name])) {
				
				if (is_callable($this->sanitizators[$name])) {
					return call_user_func($this->sanitizators[$name], $value);
				} elseif ($name[0] == '/') {
					return preg_match($this->sanitizators[$name], $value) ? true : false;
				}
				
			}
			
		}
		
		return false;

	}
	
	/* base validators */
	
    public static function min($value, $min) {
		return (utf8_strlen($value) > (float)$min);
    }
	
    public static function max($value, $max) {
		return (utf8_strlen($value) < (float)$max);
    }
	
    public static function size($value, $size) {
	
		if (is_array($size)) {
			
			if (count($size) == 2) { //min & max
				if (isset($size['min']) && isset($size['max'])) {
					return (utf8_strlen($value) >= (float)$size['min'] && utf8_strlen($value) <= (float)$size['max']);
				} else {
					return (utf8_strlen($value) >= (float)$size[0] && utf8_strlen($value) <= (float)$size[1]);
				}
			} else {
				if (isset($size['min'])) {
					return (utf8_strlen($value) > (float)$size['min']);
				} elseif (isset($size['max'])) {
					return (utf8_strlen($value) < (float)$size['max']);
				} else {
					return (utf8_strlen($value) < (float)$size[0]);
				}
			}
			
		} else { //max only
			return (utf8_strlen($value) < (float)$size);
		}
		
		return false;
    }
	
    public static function boolean($value) {
        $values = [true, false, 0, 1, '0', '1'];
        return in_array($value, $values, true);
    }
	
    public static function bool($value) {
        return is_bool($value);
    }
	
    public static function number($value) {
        return is_int($value) || is_float($value);
    }
	
    public static function int($value) {
		//return filter_var($value, FILTER_VALIDATE_INT) !== false;
        return is_int($value);
    }
	
    public static function float($value) {
        return is_float($value);
    }
	
    public static function numeric($value) {
        return is_numeric($value);
    }
	
    public static function string($value) {
        return is_string($value);
    }
	
    public static function null($value) {
        return is_null($value);
    }
	
    public static function digit($value) {
        return ctype_digit($value);
    }
	
	public static function alpha($value) {
		//return ctype_alpha($value);
		return preg_match('/^[\pL\pM]+$/u', $value);
	}
	
	public static function alphanum($value) {
		//return ctype_alnum((string) $value);
		return preg_match('/^[\pL\pM\pN]+$/u', $value);
	}
	
    public static function email($value) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
	
    public static function url($value) {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
	
    public static function ip($value) {
        //return ((false !== ip2long($value)) && (long2ip(ip2long($value)) === $value));
		return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
	
    public static function date($date) {
        return preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $date) ? true : false;
    }
	
    public static function datetime($datetime, $format = 'Y-m-d H:i:s') {
        return ($time = strtotime($datetime)) && ($datetime == date($format, $time));
    }
	
    public static function password($value) {
		return preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[a-zA-Z0-9]{8,}/', $value) ? true : false;
    }

}

