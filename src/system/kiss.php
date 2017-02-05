<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0.1
*/

/* some definitions */
define('NL', PHP_EOL);
define('BR', '<br>');
define('BRX', '<br>'.PHP_EOL);
define('TAB', "\t");

//define root path
if (!defined('SYSTEM_ROOT')) {
	$sys_path = realpath(dirname(__FILE__));
	define('SYSTEM_ROOT', $sys_path.'/');
}

//custom error handler
function error_handler($errno, $errstr, $errfile, $errline) {
	
	switch ($errno) {
		case E_NOTICE:
		case E_USER_NOTICE:
			$error = 'Notice';
			break;
		case E_WARNING:
		case E_USER_WARNING:
			$error = 'Warning';
			break;
		case E_ERROR:
		case E_USER_ERROR:
			$error = 'Fatal Error';
			//send mail to admin??
			break;
		default:
			$error = 'Unknown';
			break;
	}
	
	if (defined('DISPLAY_ERRORS') && DISPLAY_ERRORS == true) {
		echo '<div class="error_message"><strong>' . $error . '</strong>: ' . $errstr . ' in <strong>' . $errfile . '</strong> on line <strong>' . $errline . '</strong></div>';
	}
	
	@file_put_contents(SYSTEM_ROOT.'logs/errors.log', date('Y-m-d H:i:s')."\tPHP ".$error.':  '.$errstr.' in '.$errfile.' on line '.$errline."\n", FILE_APPEND);

	return true;
}

//error settings
if (!defined('ERROR_REPORTING')) {
	define('ERROR_REPORTING', 24565);
}

//custom error reporting
error_reporting(ERROR_REPORTING);
set_error_handler('error_handler');

//fix IIS rewrite
if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
}

//try to setup app folder
if (!defined('APP_FOLDER') && isset($_SERVER['SCRIPT_NAME'])) {
	$app_folder = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	define('APP_FOLDER', $app_folder);
}

if (!defined('APP_FOLDER') && isset($_SERVER['SCRIPT_FILENAME'])) {
	$app_folder = str_replace('\\', '/', substr(dirname($_SERVER['SCRIPT_FILENAME']), strlen($_SERVER['DOCUMENT_ROOT'])));
	define('APP_FOLDER', $app_folder);
}

if (!defined('APP_FOLDER') && isset($_SERVER['SCRIPT_FILENAME'])) {
	$app_folder = str_replace('\\', '/', substr(dirname($_SERVER['PATH_TRANSLATED']), strlen($_SERVER['DOCUMENT_ROOT'])));
	define('APP_FOLDER', $app_folder);
}

//try to setup SITE_URL
if (!defined('SITE_URL')) {
	//$site_url = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'.$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? $_SERVER['SERVER_PORT']: '').'/';
	$site_url = (((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? $_SERVER['SERVER_PORT']: '').'/';
	define('SITE_URL', $site_url);
}

//vendor classes
require_once(SYSTEM_ROOT.'libs/AltoRouter.php');

//preload main base files and classes
require_once(SYSTEM_ROOT.'functions.php');
require_once(SYSTEM_ROOT.'classes/helper.php');
require_once(SYSTEM_ROOT.'classes/app.php');
require_once(SYSTEM_ROOT.'classes/db.php');
require_once(SYSTEM_ROOT.'classes/input.php');
require_once(SYSTEM_ROOT.'classes/router.php');
require_once(SYSTEM_ROOT.'classes/session.php');
require_once(SYSTEM_ROOT.'classes/template.php');
require_once(SYSTEM_ROOT.'classes/log.php');
require_once(SYSTEM_ROOT.'classes/image.php');
require_once(SYSTEM_ROOT.'classes/mail.php');
require_once(SYSTEM_ROOT.'classes/queryhelper.php');

