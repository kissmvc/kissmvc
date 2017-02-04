<?php
/**
 * KISS Framework - Very simple semi MVC framework with fast learning curve
 *
 * @package		KISS Framework
 * @author		Anton Piták, SOFTPAE.com
 * @copyright	Copyright (c) 2016, Anton Piták
 * @link		http://www.softpae.com
 * @since		Version 1.0.0
 * 
 * @requirements:
 * - php5 >= 5.4
 *
 */

//load config
if (is_file('config.php')) {
	require_once('config.php');
}

require_once('system/kiss.php');

$app = new KissApp();

$app->setSubFolder(APP_FOLDER);

//default route
$app->route('/[a:controller]?/[a:action]?/[i:id]?', function(&$page, $controller = 'index', $action = 'show') {
	$page->page = $controller;
	$page->action = $action;
}, 'default');

$app->run();


?>
