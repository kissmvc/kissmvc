<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Description: Main module
*/

defined('SYSTEM_ROOT') OR exit('No direct script access allowed');

class KissApp {

	/* public vars */
	public $log_errors = false;		//enable log errors
	public $log_queries = false;	//enable log queries
	
	public $enable_timer = false;	//enable page duration counter
	public $show_errors = false;	//show errors
	
	public $use_login = false;		//require login
	
	public $root = null;			//setup root
	public $public_dir = null;		//public directory
	
	/* private vars */
	private $page = null;
	private $router = null;
	
	private $folder = '';			//setup folder if app is not in root, without trailing slash
	
	private $start_time = 0;
	private $sysroot = '';
	
	private $isajax = false;

	public function __construct($config = null, &$page = null) {

		//start duration counter
		$this->start_time = microtime(true);
		
		//check PHP version
		if (version_compare(phpversion(), '5.4', '<=')) {
			echo 'PHP 5.4 or above required to work with KISS Framework!';
			exit();
		}
		
		//set timezone
		if (!ini_get('date.timezone')) {
			date_default_timezone_set('UTC');
		}
		
		//encoding
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding('UTF-8');
		}
		
		//clean data - magic quotes fix
		if (ini_get('magic_quotes_gpc')) {
			
			$_GET = $this->clean($_GET, true);
			$_POST = $this->clean($_POST, true);
			$_REQUEST = $this->clean($_REQUEST, true);
			$_COOKIE = $this->clean($_COOKIE, true);
			$_SERVER = $this->clean($_SERVER);
			
		}
		
		//initialize main page object container
		$page = new stdObject();
		$this->page = &$page;
		
		if ($page) {
			$this->page = &$page;
		}
		
		//save start time
		$this->page->start_time = $this->start_time;
		
		//get sysroot
		if (!defined('SYSTEM_ROOT')) {
			$this->sysroot = realpath(dirname(__FILE__).'/../').'/';
		} else {
			$this->sysroot = SYSTEM_ROOT;
		}
		
		//initialize data container for template data
		$this->page->data = new stdObject();
		
		//initialize default page(controller) and action
		$this->page->page = 'index';
		$this->page->action = 'show';
		
		//setup root path
		if ($this->root) {
			$this->page->root = $this->root;
		} else {
			if(defined('ROOT')) {
				$this->page->root = ROOT;
			} else {
				$root_path = realpath(dirname(__FILE__).'/../');
			}
		}
		
		//append redirect function to page object
		$this->page->redirect = function($url, $code = 302, $inc_folder = true) {
			header('Location: '.(str_replace('//', '/', ($inc_folder ? '/'.$this->folder : '').'/'.$url)), true, $code);
			exit();
		};
		
		//append function to get path to assets
		$this->page->assets = function($url) {
			return str_replace('//', '/', $this->folder.'/'.$url);
		};
		
		//set route helper
		$this->page->setRoute = function($_page = 'index', $_action = 'show') {
			$this->page->page = $_page;
			$this->page->action = $_action;
		};
		
		//get routed url
		$this->page->getRoute = function($name, $route) {
		
			$exploded = array();
			if (is_string($route)) {
				parse_str($route, $exploded);
			} else {
				$exploded = &$route;
			}
			return $this->router->getUrl($name, $exploded);
		};
		
		//get 'classic' url with query string
		$this->page->getUrl = function($route = '') {
			$route = str_replace('index.php', '', $route);
			$route = str_replace('?', '', $route);
			return str_replace('//', '/', '/'.$this->folder.(!empty($route) ? '/?'.$route : '/'));
		};
		
		//initialize logger class
		$this->page->log = new Logger($this->sysroot.'logs/log.txt');
		
		//initialize session
		$this->page->session = new Session();
		
		//initialize Input class
		$this->page->from = new Input($page);
		
		//initialize router
		$this->router = new Router();
		$this->page->router = &$this->router;
		
		//initialize db class
		$this->page->db = new Database();
		
		//enable query logging?
		if (defined('DEBUG_QUERY') && DEBUG_QUERY == true) {
			$this->page->db->log_query = true;
			$this->page->db->log_path = $this->sysroot.'logs/';
		}
		
		//connect db if defined
		if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
		
			if (!defined('DB_PREFIX')) {
				define('DB_PREFIX', '');
			}
			
			if (!defined('DB_PORT')) {
				$this->page->db->connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PREFIX);
			} else {
				$this->page->db->connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, DB_PREFIX);
			}
			
		}
		
		//get page and action from request, otherwise defaults
		$this->page->page = $this->page->from->get('page', 'index')->val();
		$this->page->action = $this->page->from->get('action', 'show')->val();
		
		//default template is same as page, can be overwriten later in app action method e.g. to load edit page template etc.
		$this->page->tpl = $this->page->page;
		
	}
	
	public function run() {
		
		$appclass = null;
		$tpl = null;
		
		$page = &$this->page;
		
		//fill uri
		$this->page->uri = str_replace('index.php', '', str_replace($this->folder, '', strtok($_SERVER['REQUEST_URI'], '?')));
		
		//fill params from uri
		$this->page->parts = explode('/', trim($this->page->uri, '/'));
		
		//try router
		if (!($this->page->from->get('page')->exists() || $this->page->from->get('action')->exists() || $page->uri == '/')) {
			$found = $this->router->run($page);
		}
		
		//login handler - redirect to login page if enabled
		if ($page->session->get('kiss_loged') != 1 && $page->page != 'login' && (defined('DO_LOGIN') && DO_LOGIN == true)) {
			$this->redirect('index.php?page=login');
		}
		
		//load main app functions file
		if (is_readable($page->root.'/app/_functions.php')) {
			include_once($page->root.'app/_functions.php');
		}
		
		//load main app init handler
		if (is_readable($page->root.'/app/_init.php')) {
			include_once($page->root.'app/_init.php');
		}
		
		//front controller/router - page handler
		if (is_readable($page->root.'/app/'.$page->page.'.php')) {
			
			include_once($page->root.'/app/'.$page->page.'.php');
			
			$class = ucfirst(strtolower($page->page));
			$action = strtolower($page->action);
			
			//build class name, append 'Page', e.g. IndexPage
			$class = $class.'Page';
			
			//initialize class
			$appclass = new $class();
			
			//call action
			if (method_exists($appclass, $action)) {
				
				$isajax = $appclass->$action($page);
				
			} else { //page does not exist!
				
				unset($appclass);
				$this->printErrorPage();
				exit();
			
			}
			
			//do not show template - this is raw/ajax response
			if ($isajax === false) {
				unset($appclass);
				exit();
			}
			
		} else {
			
			$this->printErrorPage();
			exit();
			
		}
		
		//cleanup $page object before pass to view
		unset($page->router);
		unset($page->db);
		unset($page->log);
		unset($page->from);
		
		//view - process template
		if (is_readable($page->root.'/theme/'.$page->tpl.'.tpl')) {
			
			$tpl = new Template($page);
			
			echo $tpl->render($page->root.'/theme/'.$page->tpl.'.tpl');
			
		} else {
			
			$this->printErrorPage();
			exit();
			
		}
		
		if ($this->enable_timer == true) {
			$page->end_time = microtime(true);
			@file_put_contents($this->sysroot.'logs/durations.log', date('Y-m-d H:i:s')."\tDuration: ".($page->end_time - $page->start_time)."\t".$_SERVER['REQUEST_URI'].PHP_EOL, FILE_APPEND);
		}
		
		//cleanup
		unset($page);
		unset($tpl);
		unset($appclass);
		
	}
	
	//router
	public function route($url, $closure, $name = null, $method = 'POST|GET') {
		$this->router->route($url, $closure, $name, $method);
	}
	
	//if you need use page object
	public function &getPage() {
		return $this->page;
	}
	
	//if you need alto router object
	public function &getRouter() {
		return $this->router;
	}
	
	//if you need alto router object
	public function getSubFolder() {
		return $this->folder;
	}
	
	//if you need alto router object
	public function setSubFolder($folder) {
		$this->router->setBasePath($folder);
		$this->folder = $folder;
		$this->page->folder = &$this->folder;
		$this->page->urlroot = &$this->folder;
	}
	
	//get path to theme folder
	public function assets($url) {
		return str_replace('//', '/', $this->folder.'/'.$url);
	}
	
	public function redirect($url, $code = 302, $inc_folder = true) {
		header('Location: '.(str_replace('//', '/', ($inc_folder ? '/'.$this->folder : '').'/'.$url)), true, $code);
		exit();
	}
	
	//clean magic quotes & specialchars
	private function clean($data, $clearspecchar = false) {
	
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				unset($data[$key]);
				$data[$this->clean($key)] = $this->clean($value);
			}
		} else {
			$data = stripslashes($data);
			if ($clearspecchar) {
				$data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
			}
		}
		
		return $data;
	}
	
	private function printErrorPage() {
	
		header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
		echo '<html>';
		echo '	<head><title>Page not found!</title></head>';
		echo '	<body>';
		echo '		<h1 style="width: 50%; margin: 50px auto; padding: 20px; text-align: center; background: #eee; border-radius: 10px; border: 1px solid #ccc;">Whoops, seems like page does not exist!</h1>';
		echo '	</body>';
		echo '</html>';
		
	}

}

