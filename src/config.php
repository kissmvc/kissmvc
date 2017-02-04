<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

//setup root
if (!defined('ROOT')) {
	$root_path = realpath(dirname(__FILE__));
	define('ROOT', $root_path.'/');
}

//define database connection
define('DB_HOST','localhost');
define('DB_USER','user');
define('DB_PASS','password');
define('DB_NAME','db_test');
//define('DB_PREFIX','');

define('DO_LOGIN', false);

//log sql queries to log file
define('DEBUG_QUERY', true);

//show errors
define('DISPLAY_ERRORS', false);

//define site URL
//define('SITE_URL', 'http://localhost/');

//define subfolder manually if you have problem with autodetection, without trailing slash
//define('APP_FOLDER', '/kiss');

?>
