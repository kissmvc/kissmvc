<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Note: Thanks to AltoRouter
*/

class Router extends AltoRouter {

	public function __construct(&$page = null) {
		parent::__construct();
	}

	public function route($url = '/', &$closure, $name = null, $method = 'POST|GET') {
		parent::map($method, $url, $closure, $name);
	}

	public function setRoutes($routes) {
		$this->routes = $routes;
	}
	
	public function run($page = null) {
	
		// match current request url
		$match = $this->match();
		
		//append page object for closure first parameter
		if ($page && $match) {
			if (is_array($match['params'])) {
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

