<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Helper classes
*/

class stdObject extends stdClass implements ArrayAccess, Iterator { //, Iterator

	private $_default = array();

	public function __call($closure, $args) {
		if (isset($this->{$closure}) && is_callable($this->{$closure})) {
			return call_user_func_array($this->{$closure}, $args);
        } else {
			return call_user_func_array($this->{$closure}->bindTo($this), $args);
        }
	}

	public function __toString() {
		return call_user_func($this->{"__toString"}->bindTo($this));
	}
	
	public function __invoke() {
		$args = func_get_args();
		if (isset($args[0]) && isset($this->{$args[0]})) {
			return $this->{$args[0]};
		}
		
		return null;
	}
	
	public function setDefault(&$value) {
		if (is_array($value)) {
			$this->_default = $value;
		} else {
			$this->_default = array($value);
		}
	}
	
	/* AccessArray methods */
	
	public function offsetSet($offset, $value) {
		$this->{$offset} = $value;
    }
	
    public function offsetExists($offset) {
        return isset($this->{$offset});
    }
	
    public function offsetUnset($offset) {
        unset($this->{$offset});
    }
	
    public function offsetGet($offset) {
        return isset($this->{$offset}) ? $this->{$offset} : null;
    }
	
	/* Iterator methods */
 
    public function rewind() {
        reset($this->_default);
    }
 
    public function valid() {
		return key($this->_default) !== null;
    }
 
    public function key() {
        return key($this->_default);
    }
 
    public function current() {
        return current($this->_default);
    }
 
    public function next() {
        next($this->_default);
    }
	
}

