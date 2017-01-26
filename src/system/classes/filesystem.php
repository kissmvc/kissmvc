<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

class FileSystem {

	const DIR_ALL = 0;
	const DIR_FILES = 1;
	const DIR_FOLDERS = 2;

	private $path = null;
	
	public function __construct($path = null) {
		$this->mask_regex = '';
		if (isset($path)) {
			$this->path = $path;
		}
	}
	
	public function fromFile($path) {
		$this->path = realpath($path);
		return $this;
	}
	
	public function fromPath($path) {
		$this->path = realpath($path);
		return $this;
	}
	
	public function exists($path = null) {
		$path = $this->checkPath($path);
		return file_exists($path);
	}
	
	public function copy($source = null, $dest, $return = false, $deep = 0) { //dir? array of files?
	
		$source = $this->checkPath($source);
		$dest = $this->normalizePath($dest);
		
		if (is_dir($source)) {
		
			if ($deep == 0) {
				$dest = $dest.basename($source).'/';
			}
			
			if (!is_dir($dest)) { 
				if(!@mkdir($dest)) {
					$ret = false;
				}
			}
			
			$deep++;
			
			$iterator = new DirectoryIterator($source);
			
			foreach ($iterator as $item) {
				if ($item->isFile()) {
					$this->copy($item->getRealPath(), $dest.'/'.$item->getFilename(), false, $deep);
				} else if (!$item->isDot() && $item->isDir()) {
					$this->copy($item->getRealPath().'/', $dest.'/'.$item, false, $deep);
				}
			}
			
		} elseif (is_file($source)) {
		
			$ret = copy($source, $dest);
			
		}
		
		return ($return ? $ret : $this);
	}
	
	public function copyTo($dest, $return = false) {
		return $this->copy(null, $dest, $return);
	}
	
	public function move($source = null, $dest, $return = false, $deep = 0) { //dir? array of files?
	
		if ($deep == 0) {
			$source = $this->checkPath($source);
		} else {
			$source = $this->normalizePath($source);
		}
		$dest = $this->normalizePath($dest);
		
		$last = null;
		
		if (is_dir($source)) {
		
			if ($deep == 0) {
				$dest = $dest.basename($source).'/';
				$last = $source;
			}
			
			if (!is_dir($dest)) { 
				if(!@mkdir($dest)) {
					$ret = false;
				}
			}
			
			$deep++;
			
			$iterator = new DirectoryIterator($source);
			
			foreach ($iterator as $item) {
				if ($item->isFile()) {
					$this->move($item->getRealPath(), $dest.'/'.$item->getFilename(), false, $deep);
				} else if (!$item->isDot() && $item->isDir()) {
					$this->move($item->getRealPath().'/', $dest.'/'.$item, false, $deep);
					@rmdir($item->getRealPath().'/');
				}
			}
			
		} elseif (is_file($source)) {
		
			$ret = rename($source, $dest);
			
		}
		
		if (is_dir($last)) {
			@rmdir($last);
		}
		
		return ($return ? $ret : $this);
	}
	
	public function moveTo($dest, $return = false) {
		return $this->move(null, $dest, $return);
	}
	
	public function rename($oldname = null, $newname, $return = false) { //dir? array of files?
		$oldname = $this->checkPath($oldname);
		$ret = @rename($oldname, $newname);
		return ($return ? $ret : $this);
	}
	
	public function delete($path = null, $return = false) { //add filter/mask, array of files?
		
		$path = $this->checkPath($path);
		
		if (is_file($path)) {
		
			$ret = unlink($path);
			return ($return ? $ret : $this);
			
		} elseif (is_dir($path)) {
		
			$iterator = new FilesystemIterator($path);
			foreach ($iterator as $item) {
				$this->delete($item->getPathname());
			}
			
			$ret = @rmdir($path);
			return ($return ? $ret : $this);
			
		} else {
			//error??
		}
		return $this;
		
	}
	
	public function mkdir($path = null, $mode = 0644, $recursive = false, $return = false) {
		$path = $this->checkPath($path);
		$ret = @mkdir($path, $mode, $recursive);
		return ($return ? $ret : $this);
	}
	
	public function createDir($path = null, $mode = 0644, $recursive = false, $return = false) {
		return $this->mkdir($path, $mode, $recursive, $return);
	}
	
	public function touch($path = null, $time = null, $atime = null, $return = false) {
		$path = $this->checkPath($path);
		if (!isset($time)) {
			$time = time();
		}
		$ret = touch($path, $time, $atime);
		return ($return ? $ret : $this);
	}
	
	public function chmod($path = null, $mod = 0644, $return = false) { //rights
		$path = $this->checkPath($path);
		$ret = chmod($path, $mod);
		return ($return ? $ret : $this);
	}
	
	public function chown($path = null, $user, $return = false) { //owner
		$path = $this->checkPath($path);
		$ret = chown($path, $user);
		return ($return ? $ret : $this);
	}
	
	public function chgrp($path = null, $group, $return = false) { //group
		$path = $this->checkPath($path);
		$ret = chgrp($path, $group);
		return ($return ? $ret : $this);
	}
	
	public function symlink($path = null, $link, $return = false) {
		$path = $this->checkPath($path);
		$ret = symlink($path, $link);
		return ($return ? $ret : $this);
	}
	
	public function getMTime($path = null) {
		$path = $this->checkPath($path);
		return filemtime($path);
	}
	
	public function modified($path = null) {
		return $this->getMTime($path);
	}
	
	public function getCTime($path = null) {
		$path = $this->checkPath($path);
		return filectime($path);
	}
	
	public function created($path = null) {
		return $this->getCTime($path);
	}
	
	public function getSize($path = null) {
		$path = $this->checkPath($path);
		return filesize($path);
	}
	
	public function getExt($path = null) {
		$path = $this->checkPath($path);
		return pathinfo($path, PATHINFO_EXTENSION);
	}
	
	public function getName($path = null) {
		$path = $this->checkPath($path);
		return basename($path);
	}
	
	public function getDirName($path = null) {
		$path = $this->checkPath($path);
		return dirname($path);
	}
	
	public function getPath($path = null) {
		return $this->path;
	}
	
	public function getParent($path = null) {
		$path = $this->checkPath($path);
		return dirname($path);
	}
	
	public function getOwner($path = null) {
		$path = $this->checkPath($path);
		return fileowner($path);
	}
	
	public function getGroup($path = null) {
		$path = $this->checkPath($path);
		return filegroup($path);
	}
	
	public function getPerms($path = null) {
		$path = $this->checkPath($path);
		return fileperms($path);
	}
	
	public function getPermissions($path = null) {
		$path = $this->checkPath($path);
		return fileperms($path);
	}
	
	public function getDirecory($path = null) {
		$path = $this->checkPath($path);
		$this->path = dirname($path);
		return $this;
	}
	
	public function isFile($path = null) {
		$path = $this->checkPath($path);
		return is_file($path);
	}
	
	public function isDir($path = null) { //isDirectory
		$path = $this->checkPath($path);
		return is_dir($path);
	}
	
	public function isWritable($path = null) {
		$path = $this->checkPath($path);
		return is_writable($path);
	}
	
	public function isReadable($path = null) {
		$path = $this->checkPath($path);
		return is_readable($path);
	}
	
	public function formatedSize($path = null) {
	
		$path = $this->checkPath($path);
		
		$size = filesize($path);
		$unit = array('B','kB','MB','GB','TB','PB');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
		
	}
	
	public function humanSize($path = null) {
		return $this->formatedSize($path);
	}

	public function formatSize($size) {
		$unit = array('B','kB','MB','GB','TB','PB');
		return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2).' '.$unit[$i];
	}
	
	public function clearCache() {
		return clearstatcache();
	}
	
	public function getTree($path = null, $filter = '*', $which = DIR_ALL, $recursively = false) {
	
		$path = $this->checkPath($path);
		
		$files = array();
		$folders = array();
		
		foreach (new DirectoryIterator($path) as $file) {
		
			if (!$file->isDot()) {
			
				if ($file->isDir() && ($which == self::DIR_FOLDERS || $which == self::DIR_ALL)) {
				
					$item = array();
					
					$item['item'] = $file;
					if ($recursively) {
						$item['childrens'] = $this->getTree($file->getPathname(), $filter, $which, true);
					}
					
					$folders[] = $item;
					
				} else if ($file->isFile() && ($which == self::DIR_FILES || $which == self::DIR_ALL) && $this->checkFilter($filter, $file->getPathname())) {
					
					$files[] = array('item' => $file);
					
				}
			}
		}
		
		return array_merge($folders, $files);
		
	}
	
	public function getFullTree($path = null, $filter = '*') {
		return $this->getTree($path, $filter, self::DIR_ALL, true);
	}
	
	public function dir($path = null, $filter = '*', $which = DIR_ALL, $recursively = false) {
	
		$path = $this->checkPath($path);
		
		$files = array();
		$folders = array();
		
		foreach (new DirectoryIterator($path) as $file) {
		
			if (!$file->isDot()) {
			
				if ($file->isDir() && ($which == self::DIR_FOLDERS || $which == self::DIR_ALL)) {
				
					$item = array();
					
					$item['name'] = @$file->getBasename();
					$item['path'] = @$file->getPathname();
					$item['size'] = null;
					if ($recursively) {
						$item['childrens'] = $this->dir($file->getPathname(), $filter, $which, true);
					}
					
					$folders[] = $item;
					
				} else if ($file->isFile() && ($which == self::DIR_FILES || $which == self::DIR_ALL) && $this->checkFilter($filter, $file->getPathname())) {
				
					$item = array();
					
					$item['name'] = @$file->getBasename();
					$item['path'] = @$file->getPathname();
					$item['size'] = @$file->getSize();
					
					$files[] = $item;
					
				}
			}
		}
		
		return array_merge($folders, $files);

	}
	
	public function files($path = null) {
		return $this->dir($path, $filter, self::DIR_FILES);
	}
	
	public function dirTree($path = null) {
		return $this->dir($path, $filter, self::DIR_ALL, true);
	}
	
	public function getDir($path = null) {
		return $this->dir($path, $filter);
	}
	
	public function getDirs($path = null) {
		return $this->dir($path, $filter, self::DIR_FOLDERS);
	}
	
	public function getFiles($path = null, $filter = '*') { //getList
		return $this->dir($path, $filter, self::DIR_FILES);
	}
	
	private function checkPath(&$path) {
		if (!isset($path)) {
			$path = $this->path;
		} else {
			$this->path = $path;
		}
		return $this->normalizePath($path);
	}
	
	private function checkFilter($filter, $what) {
		if (substr($filter, 0, 1) == '#') {
			return true;
		} else {
			$pieces = explode(';', $filter);
			foreach ($pieces as $piece) {
				if (fnmatch($piece, $what, FNM_CASEFOLD)) { return true; }
			}
			//return fnmatch($filter, $what, FNM_CASEFOLD || FNM_EXTMATCH);
		}
	}
	
	private function deletePath($path) {
		return is_file($path) ? @unlink($path) : array_map(array($this, 'deletePath'), glob($path.'/*')) == @rmdir($path);
	}
	
	private function check_file_uploaded_name($filename) {
		return (bool) ((preg_match("`^[-0-9A-Z_\.]+$`i",$filename)) ? true : false);
	}
	
	private function normalizePath($path) {
	
		$parts = array();
		
		$path = str_replace('\\', '/', $path);
		$path = preg_replace('/\/+/', '/', $path);
		$segments = explode('/', $path);
		$test = '';
		
		foreach($segments as $segment) {
		
			if ($segment != '.') {
			
				$test = array_pop($parts);
				
				if (is_null($test)) {
				
					$parts[] = $segment;
					
				} else if ($segment == '..') {
					
					if ($test == '..') {
						$parts[] = $test;
					}
					
					if ($test == '..' || $test == '') {
						$parts[] = $segment;
					}
					
				} else {
					$parts[] = $test;
					$parts[] = $segment;
				}
			}
			
		}
		return implode('/', $parts);
	}
	
}
