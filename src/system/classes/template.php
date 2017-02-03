<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Main template class
	Note: implement auto header & footer
*/

class Template {

	public $data = array();
	public $page = null;
	
	public function __construct(&$page = null) {
		
		if (isset($page)) {
			$this->page = &$page;
			$this->data = &$page->data;
		}
		
	}
	
	public function render($tpl, &$page = null) {
    
		if (file_exists($tpl)) {
			
			if (!isset($page)) {
				$page = &$this->page;
			}
			
			$data = &$page->data;
			
			ob_start();
			
			include($tpl);
			
			$html = ob_get_contents();
			
			ob_end_clean();
			
			return $html;
			
    	} else {
		
			trigger_error('Error: Template file '.$tpl.' not found!', E_USER_ERROR);
			exit();
			
    	}	
	}
}

