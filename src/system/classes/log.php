<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

class Logger {

	private $handle = null;

	public function __construct($filename) {
		$this->handle = fopen($filename, 'a');
	}

	public function __destruct() {
		fclose($this->handle);
	}

	public function debug($msg) {
		fwrite($this->handle, date('Y-m-d H:i:s').PHP_EOL.'--------'.PHP_EOL.print_r($msg, true).PHP_EOL.'--------'.PHP_EOL);
	}

	public function write($msg) {
		fwrite($this->handle, date('Y-m-d H:i:s')."\t".$msg.PHP_EOL);
	}
	
}

