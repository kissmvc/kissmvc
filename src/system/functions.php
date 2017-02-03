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

function srt_left($str, $length) {
	if (function_exists(mb_substr)) {
		return mb_substr($str, 0, $length);
	}
    return substr($str, 0, $length);
}

function str_right($str, $length) {
	if (function_exists(mb_substr)) {
		return mb_substr($str, -$length);
	}
    return substr($str, -$length);
}

function str_truncate($text, $max = 200, $append = '&hellip;') {

	if (function_exists(mb_strlen)) {
		if (mb_strlen($text) <= $max) { 
			return $text;
		}
		
		$out = mb_substr($text, 0, $max);
		
		if (mb_strpos($text, ' ') === false) { 
			return $out.$append;
		}
		return preg_replace('/\w+$/','',$out).$append;
	}
	
	if (strlen($text) <= $max) { 
		return $text;
	}
	
	$out = substr($text, 0, $max);
	
	if (strpos($text, ' ') === false) { 
		return $out.$append;
	}
	
	return preg_replace('/\w+$/','',$out).$append;
}

function str_contains($str, $what) {
	if (function_exists(mb_stripos)) {
		return mb_stripos($str, $what) !== false;
	}
    return stripos($str, $what) !== false;
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

function iif($condion, $true, $false) {
    return ($condition ? $true : $false);
}

function ifdef($def) {
	if (defined($def) && !is_blank(constant($def))) {
		return true;
	} else {
		return false;
	}
}

function diverse_array($vector) { 
    $result = array(); 
    foreach($vector as $key1 => $value1) {
        foreach($value1 as $key2 => $value2) {
            $result[$key2][$key1] = $value2;
		}
	}
    return $result; 
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
				
					$path = realpath(dirname(__FILE__));
					$file = $path.'/classes/'.$filename;
					
					if (!file_exists($file)) {
						$file = $path.'/libs/'.$filename;
						if (!file_exists($file)) {
							return false;
						}
					}
					
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

function slug($value) {
	return seoize($value);
}

function idslug($id, $value) {
	return $id.'-'.seoize($value);
}

function seoize($value) {

	$badchar = array("ā","Ā","ī","Ī","ū","Ū","ṃ","Ṃ","ḥ","Ḥ","ṇ","Ṇ","ṣ","Ṣ","ś","Ś","ṛ","Ṛ","ṝ","Ṝ","ḷ","Ḷ","ṭ","Ṭ","ṭh","Ṭh","ḍ","Ḍ","ḍh","Ḍh","ṅ","Ṅ","ñ","Ñ","ḹ","Ḹ","ľ","š","č","ť","ž","ý","á","í","é","ú","ä","ô","ň","ř","ě","ď","Ř","Ť","Š","Ď","Ě","Č","Ž","Ň","Ľ","Á","É","Í","Ó","Ú","ĺ","Ĺ");
	$newchar = array("a","A","i","I","u","U","m","M","h","H","n","N","s","S","s","S","r","R","r","R","l","L","t","T","th","Th","d","D","dh","Dh","n","N","n","N","l","L","l","s","c","t","z","y","a","i","e","u","a","o","n","r","e","d","R","T","S","D","E","C","Z","N","L","A","E","I","O","U","l","L");
	
	$value = str_replace($badchar, $newchar, $value); 
	$value = preg_replace("@[^A-Za-z0-9\-_\s]+@i", "", $value);
	$value = strtolower($value);
	$value = str_replace("  ", " ", $value);
	$value = str_replace(" ", "-", $value);
	
	return $value;
}

if (!function_exists('fnmatch')) {

    function fnmatch($pattern, $string) {
        return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $string);
    }

}

