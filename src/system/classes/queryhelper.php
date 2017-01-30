<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Super QueryHelper class
*/

 class QueryHelper {

	private $selectables = array();
	private $table;
	private $whereClause;
	private $limit;
	private $offset;

	private $page;
	
	private $data = array();
	private $values = array();
	
	private $validator = null;
	private $sizes = array();
	
	private $_validations = array();
	
	public function __construct(&$page, $table = null) {
	
		if (empty($page)) {
			throw new Exception('QueryHelper constructor require page object!', E_USER_ERROR);
		}
		
		$this->page = $page;
		
		if (!empty($table)) {
			$this->table = $table;
		}
		
	}
	
	public function fromArray(&$data) {
		$this->data = &$data;
		return $this;
	}
	
	public function fromGet() {
		$this->data = &$this->page->from->request->get;
		return $this;
	}
	
	public function fromPost() {
		$this->data = &$this->page->from->request->post;
		return $this;
	}
	
	public function table($table) {
		$this->table = $table;
		return $this;
	}
	
	public function addField($field_name, $value_key_name = null, $default = null, $validator = null, $size = null) {
	
		if (empty($value_key_name)) {
			$value_key_name = $field_name;
		}
		
		if (is_array($field_name) && is_array($value_key_name)) {
		
			$values = array();
			
			foreach ($value_key_name as $key) {
				$values[] = $this->data[$key];
			}
			
			$this->addValue($field_name, $values, $default, $validator, $size);
			
		} else {
		
			$this->addValue($field_name, $this->data[$value_key_name], $default, $validator, $size);
		
		}
		
		return $this;
	}
	
	public function addValue($field_name, $value, $validator = null, $size = null) {
	
		if (is_array($field_name) && is_array($value)) {
		
			$i = 0;
			
			foreach ($field_name as $field) {
			
				$_value = null;
				$_validator = null;
				$_size = null;
				
				if (!empty($value)) {
					$_value = ($this->is_assoc($value) ? (isset($value[$field]) ? $value[$field] : null) : (isset($value[$i]) ? $value[$i] : null));
				}
				
				if (!empty($validator)) {
					$_validator = ($this->is_assoc($validator) ? (isset($validator[$field]) ? $validator[$field] : null) : (isset($validator[$i]) ? $validator[$i] : null));
				}
				
				if (!empty($size)) {
					$_size = ($this->is_assoc($size) ? (isset($size[$field]) ? $size[$field] : null) : (isset($size[$i]) ? $size[$i] : null));
				}
				
				$this->addValue($field, $_value, $_validator, $_size);
				$i++;
			}
			
		} else {
			
			//validate here
			
			if (!empty($validator)) {
			
				if (is_array($validator)) {
				
					foreach($validator as $one) {
					
						$validation = $this->validate($one[1], $value);
						
						if ($validation === false) {
							$this->_validations[$field_name] = $one[1];
							break;
						}
					}
					
				} else {
				
					$validation = $this->validate($validator, $value);
					
					if ($validation === false) {
						$this->_validations[$field_name] = $validator;
					}
				}
				
			}
			
			if (!empty($size)) {
				
				if (is_array($size)) {
					
					if (count($size) == 2) { //min & max
						if (isset($size['min']) && isset($size['max'])) {
							if (!(utf8_strlen($value) >= (float)$size['min'] && utf8_strlen($value) <= (float)$size['max'])) {
								$this->_validations[$field_name] = 'size';
							}
						} else {
							if (!(utf8_strlen($value) >= (float)$size[0] && utf8_strlen($value) <= (float)$size[1])) {
								$this->_validations[$field_name] = 'size';
							}
						}
					} else {
						if (isset($size['min'])) {
							if (utf8_strlen($value) < (float)$size['min']) {
								$this->_validations[$field_name] = 'size';
							}
						} elseif (isset($size['max'])) {
							if (utf8_strlen($value) > (float)$size['max']) {
								$this->_validations[$field_name] = 'size';
							}
						} else {
							if (utf8_strlen($value) > (float)$size[0]) {
								$this->_validations[$field_name] = 'size';
							}
						}
					}
					
				} else { //max only
					if (utf8_strlen($value) > (float)$size) {
						$this->_validations[$field_name] = 'size';
					}
				}
				
			}
			
			$this->values[$field_name] = $value;
			
		}
		
		return $this;
	}
	
	public function addValidator($name, $value) {
		if (empty($this->validator)) {
			$this->validator = new Validator();
		}
		$this->validator->addValidator($name, $value);
		return $this;
	}
	
	public function validate($name, $value) {
		if (empty($this->validator)) {
			$this->validator = new Validator();
		}
		return $this->validator->validate($name, $value);
	}
	
	public function isValid() {
		return (count($this->_validations) > 0 ? false : true);
	}
	
	public function sanitize($name, $value) {
		return false;
	}
	
	public function validations() {
		return $this->_validations;
	}
	
	public function select() {
		$this->selectables = func_get_args();
		return $this;
	}
	
	public function from($table) {
		$this->table = $table;
		return $this;
	}
	
	public function into($table) {
		$this->table = $table;
		return $this;
	}
	
	public function where($where, $what = null) {
		$this->whereClause = $where;
		return $this;
	}
	
	public function byId($id) {
		if (is_int($id) && !empty($this->table)) {
			$schema = $this->page->db->getSchema();
			$this->whereClause = $schema[$this->table].' = '.$id;
		}
		return $this;
	}
	
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}
	
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}
	
	public function insert() {
		if (count($this->values) > 0 && $this->isValid()) {
			return $this->page->db->insert($this->table, $this->values);
		}
		return false;
	}
	
	public function update($id) {
		if (count($this->values) > 0 && $this->isValid()) {
			return $this->page->db->update($this->table, $this->values, $id);
		}
		return false;
	}
	
	public function autoAdd($prefix = 'db', $separator = '_', $from = null, $table = null) { //db_fieldname_validator_size
	
		if (empty($from)) {
			$from = $this->data;
		}
		
		if (empty($table)) {
			$table = $this->table;
		}
		
		foreach($from as $key => $value) {
		
			$temp = explode($separator, $key);
			
			$size = null;
			$validator = null;
			
			//db_fieldname_validator_size
			//db_name_200
			//db_name_string
			//db_name_20_200
			//db_name_string_200
			//db_name_string_10_200
			
			if (count($temp) > 1 && $temp[0] == $prefix) {
			
				$field_name = $temp[1];
				
				if (isset($temp[2])) {
				
					if (is_numeric($temp[2])) {
						$size = (int)$temp[2]; //min
					} else {
						$validator = $temp[2];
					}
					
				}
				
				if (isset($temp[3])) {
				
					if (is_numeric($temp[2]) && is_numeric($temp[3])) {
						$size = array((int)$temp[2], (int)$temp[3]); //min + max
					}
					
				}
				
				if (isset($temp[4])) {
				
					if (is_numeric($temp[3]) && is_numeric($temp[4])) {
						$size = array((int)$temp[3], (int)$temp[4]); //min + max
					}
					
				}
				
				$this->addValue($field_name, $value, $validator, $size);
				
			}
			
		}
		
		return $this;
		
	}
	
	public function autoinsert($prefix = 'db', $separator = '_', $from = null, $table = null) { //db_fieldname_validator_size
	
		$this->autoAdd($prefix, $separator, $from, $table);
		
		if (count($this->values) > 0 && $this->isValid()) {
			return $this->insert();
		}
		return false;
	}
	
	public function autoupdate($id, $separator, $validator = false, $from = null, $table = null) {
	
		$this->autoAdd($prefix, $separator, $from, $table);
		
		if (count($this->values) > 0 && $this->isValid()) {
			return $this->update($id);
		}
		return false;
	}
	
	public function delete($id, $table = null) {
		
		if (empty($table)) {
			$table = $this->table;
		}
		
		return $this->page->db->delete($table, $id);
	}
	
	public function query() {
	
		$query = 'SELECT ';
		
		if (empty($this->selectables)) {
			$query .= ' * ';
		}
		
		$query .= join(', ', $this->selectables). ' FROM '. $this->table;
		
		if (!empty($this->whereClause)) {
			$query .= ' WHERE '. $this->whereClause;
		}
		
		if (!empty($this->limit)) {
			$query .= ' LIMIT '. $this->limit;
		}
		
		if (!empty($this->offset)) {
			$query .= ' OFFSET '. $this->offset;
		}
		
		return $this->page->db->query($query);
		
	}
	
	private function is_assoc($array) {
	
		$keys = array_keys($array);
		
		return array_keys($keys) !== $keys;
		
	}
	
 }
