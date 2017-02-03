<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Note: Thanks to AltoRouter
*/

defined('SYSTEM_ROOT') OR exit('No direct script access allowed');

class Router extends AltoRouter {
	
	private $_params = array();
	
	public function __construct(&$page = null) {
	
		parent::__construct();
		
		//add some new route definitions
		parent::addMatchTypes(array('is' => '(\d+)-([-\w]+)'));
		
	}
	
	public function route($url = '/', &$closure, $name = null, $method = 'POST|GET') {
		parent::map($method, $url, $closure, $name);
	}
	
	public function setRoutes($routes) {
		$this->routes = $routes;
	}
	
	public function getUrl($name, $params = array()) {
		return parent::generate($name, $params);
	}
	
	public function getParams($key = null) {
		if (!empty($key)) {
			return $this->_params[$key];
		}
		return $this->_params;
	}
	
	public function run(&$page = null) {
	
		// match current request url
		$match = $this->match();
		
		//append page object for closure as first parameter
		if ($page && $match) {
		
			if (is_array($match['params'])) {
			
				$page->params = $this->_params = $match['params'];
				$page->from->setParams($match['params']);
				
				$match['params'] = array('page' => &$page) + $match['params'];
				
			} else {
				$match['params'] = array('page' => &$page);
			}
			
		}
		
		//call closure from route to setup page and action
		if ($match && is_callable($match['target'])) {
			call_user_func_array($match['target'], $match['params']);
			return true;
		} else {
			return false;
		}
		
	}
	
}

