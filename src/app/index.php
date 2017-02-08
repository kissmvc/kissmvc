<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: Main ModelController File
	Version: 1.0
*/

class IndexPage {

	function show(&$page) {
	
		$page->data->hello = 'Hello from KISS Framework application';
		
	}

	function about(&$page) {
	
		$page->data->hello = 'This is from About action!';
	
	}

}

?>
