<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

class Cache {

	private $cache_dir = null;
	private $expire = 3600;
	private $prefix = 'cache';

	public function __construct($prefix = 'cache', $expire = null) {
	
		if (!empty($expire)) {
			$this->expire = (int)$expire;
		}
		$this->prefix = $prefix;
		
		$files = glob($this->cache_dir.'/'.$this->prefix.'.*');
		
		if ($files && (int)$this->expire > 0) {
			foreach ($files as $file) {
				if (time() > (filemtime($file) + $this->expire)) {
					unlink($file);
				}
			}
		}
	}
	
	public function get($key) {
	
		$files = glob($this->cache_dir.'/'.$this->prefix.'.'.md5($key));
		
		if ($files) {
			$data = file_get_contents($files[0]);
			return unserialize($data);
		}
		
		return false;
	}
	
	public function set($key, $value) {
	
		$this->delete($key);
		
		$file = $this->cache_dir.'/'.$this->prefix.'.'.md5($key);
		
		file_put_contents($file, $value);
		
	}
	
	public function delete($key) {
	
		$files = glob($this->cache_dir.'/'.$this->prefix.'.'.md5($key));
		
		if ($files) {
			foreach ($files as $file) {
				unlink($file);
			}
		}
	}

}
