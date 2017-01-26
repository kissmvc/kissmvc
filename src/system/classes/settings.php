<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Note: Not finished yet
*/

class Settings {

	public $data = array();
	public $rows = array();
	
	private $page;
	protected $values = array();

	function __construct(&$page = null, $table = 'settings'){
	
		$this->page = &$page;
		
		$query = 'SELECT * FROM '.$table.' ORDER BY name, setting_id';
		$result = $page->db->query($query);
		
		$key = '';
		$prevkey = '';
		$i = 0;
		$arr = array();
		
		foreach ($result->rows as $row) {
			
			$key = $row->name;
			$this->rows[$row->id] = $row;
			
			if ($i != 0 && $prevkey != $key) { 
				
				$this->data[$prevkey] = $arr;
				$arr = array();
				$arr[] = $row;
			
			} else {
			
				//$arr[$row->id] = get_object_vars($row);
				$arr[] = &$this->rows[$row->id];
				//$arr[$row->id] = $row;
				
			}
			
			$prevkey = $row->name;
			$i++;
		}
		
		$this->data[$prevkey] = $arr;
		
	}
	
	function get($name) {
	
		return $this->data[$name][0];
	
	}
	
	function getAll($name) {
	
		return $this->data[$name];
	
	}
	
	function getById($id) {
	
		return $this->rows[$id];
	
	}
	
    function  __get($name) {
	
        if(array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }
		
        return null;
    }
 
    function  __set($name, $value) {
        $this->values[$name] = $value;
    }
 
    function  __isset($name) {
        return array_key_exists($name, $this->values);
    }
 
    function  __unset($name) {
        unset($this->values[$name]);
    }

}
	
