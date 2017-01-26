<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

function echo_nl($data) {
	echo $data.PHP_EOL;
}

function echo_br($data) {
	echo $data.'<br>'.PHP_EOL;
}

function is_blank($value) {
    return empty($value) && !is_numeric($value);
}

function is_empty($value) {
    return empty($value) && !is_numeric($value);
}

function stripslashes_deep($value) {
	$value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
	return $value;
}

function gethtml($data) {
    return html_entity_decode(stripslashes($data), ENT_QUOTES, 'UTF-8');
}

function left($str, $length) {
    return substr($str, 0, $length);
}

function right($str, $length) {
    return substr($str, -$length);
}

function utf8_strlen($str) {
	if (function_exists(mb_strlen)) {
		return mb_strlen($str, 'UTF-8');
	}
	if (function_exists(iconv_strlen)) {
		return iconv_strlen($str, 'UTF-8');
	}
	return strlen(utf8_decode($str));
}

function iif($condion, $true, $false ) {
    return ($condition ? $true : $false);
}

function ifdef($def) {
	if (defined($def) && !is_blank(constant($def))) {
		return true;
	} else {
		return false;
	}
}

function __autoload($className) {

	$filename = strtolower($className).'.php';
	
	if (defined('ROOT')) {

		$file = ROOT.'/app/classes/'.$filename;
		
		if (!file_exists($file)) {
			$file = ROOT.'/system/classes/'.$filename;
			if (!file_exists($file)) {
				$file = ROOT.'/system/libs/'.$filename;
				if (!file_exists($file)) {
					return false;
				}
			}
		}
		
		require_once($file);
		return true;
		
	} else {
	
		$path = realpath(dirname(__FILE__));
		$file = $path.'/classes/'.$filename;
		
		if (!file_exists($file)) {
			$file = $path.'/libs/'.$filename;
			if (!file_exists($file)) {
				return false;
			}
		}
		
		require_once($file);
		return true;
	}
}

if (!function_exists('fnmatch')) {

    function fnmatch($pattern, $string) {
        return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
    }

}

