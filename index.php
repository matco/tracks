<?php
/**
 * Index page, entry point for application
 * @package core
 */

define('PASTRY_NAMESPACE', 'http://matco.name/schema/pastry.xsd');

//load config
require_once('config'.DIRECTORY_SEPARATOR.'config.config.php');

//check form data
require_once('config'.DIRECTORY_SEPARATOR.'security.config.php');

//loading database and model classes
require_once('tools'.DIRECTORY_SEPARATOR.'db.class.php');
require_once('ConnectionProvider.php');
require_once('Datacontext.php');
require_once('models'.DIRECTORY_SEPARATOR.'Model.php');

//exceptions
require_once('PathException.php');

//application classes
require_once('Logger.php');
require_once('Factory.php');
require_once('Renderer.php');
require_once('Response.php');
require_once('Sessions.php');

//change configuration
set_time_limit(10);
//error_reporting(E_STRICT);

header('Content-type: application/xhtml+xml');

//override errors management
set_error_handler(function($number, $message, $file, $line) {
	throw new ErrorException($message, 0, $number, $file, $line);
});

$GLOBALS['logger'] = new Logger(Logger::LEVEL_WARNING, $config['log']['file_level'], $config['log']['db_level']);
$GLOBALS['datacontext'] = new Datacontext();

try {
	//override sessions management
	session_set_save_handler('Sessions::open', 'Sessions::close', 'Sessions::read', 'Sessions::write', 'Sessions::destroy', 'Sessions::clean');
	session_start();

	//start buffer
	//ob_start();

	$factory = Factory::getInstance();
	/*
	if(isset($_SESSION['factory'])) {
		$factory = unserialize($_SESSION['factory']);
	}
	else {
		$factory = Factory::getInstance();
	}
	*/

	$document = handle_url($factory);

	//a document is returned only when a page must be displayed (but is null when an action is called)
	if(!empty($document)) {
		//inject debug into body
		if($config['debug']['http'] or $config['debug']['db']) {
			$bodies = $document->getElementsByTagName('body');
			if($bodies and $bodies->length > 0) {
				$body = $bodies->item(0);

				$debug = $document->createElement('div');
				$debug->setAttribute('style', $config['debug_style']);
				$body->insertBefore($debug, $body->firstChild);

				if($config['debug']['http']) {
					$debug->appendChild($document->createElement('h2', 'Server variables'));
					$list = $document->createElement('ul');
					$debug->appendChild($list);

					$variables = array(
						'GET' => $_GET,
						'POST'=> $_POST,
						'FILES' => $_FILES,
						'SESSION' => $_SESSION
					);
					foreach($variables as $name => $variable) {
						$item = $document->createElement('li');
						$item->appendChild($document->createElement('pre', $name.': '.print_r($variable, true)));
						$list->appendChild($item);
					}
				}

				if($config['debug']['db']) {
					$debug->appendChild($document->createElement('h2', 'Database queries'));
					$list = $document->createElement('ul');
					$debug->appendChild($list);
					foreach(ConnectionProvider::getConnection()->getQueries() as $query) {
						$list->appendChild($document->createElement('li', $query));
					}
				}
			}
		}

		//write document
		echo $document->saveXML();
	}

	//show buffer
	ob_end_flush();

	//$_SESSION['factory'] = serialize($factory);

	//close session
	session_write_close();
}
catch(Exception $e) {
	$document = Renderer::createHTMLDocument();
	$body = $document->createElement('body');
	$body->setAttribute('style', $config['debug_style']);
	$document->documentElement->appendChild($body);

	$error = get_class($e).' ('.$e->getFile().':'.$e->getLine().'): '.$e->getMessage();
	$body->appendChild($document->createElement('h1', htmlentities($error)));
	$body->appendChild($document->createElement('pre', htmlentities(print_r($e->getTrace(), true))));

	//write document
	echo $document->saveXML();
}

/**
 * Handle URL (executing actions and rendering the page)
 */
function handle_url(Factory $factory) {
	global $config;

	$xhr = isset($_GET['xhr']);

	//retrieve page
	$page_name = !empty($_GET['page']) ? $_GET['page'] : $config['start_page'];
	//pargs can be used in url to pass arguments to the page constructor
	$page_args = !empty($_GET['pargs']) ? explode(',', $_GET['pargs']) : null;

	//instantiate the page
	$page = $factory->getPage($page_name, $page_args);

	//deal with forms and actions
	if(!empty($_GET['form']) or !empty($_GET['action'])) {
		$parts = explode(':', $_GET['form'] ?? $_GET['action']);
		$filter_path = $parts[0];
		//render partial page
		$renderer = new Renderer($factory, Renderer::createHTMLDocument(), $filter_path, false, false);
		$components = $renderer->renderPage($page);
		if(empty($components)) {
			throw new PathException(sprintf('No component related to path "%s"', $filter_path));
		}
		//retrieve the form among the components
		//components included in the form are also included in the list because they need to be rendered
		$action_component = null;
		foreach($components as $component) {
			if($component->getPath() === $filter_path) {
				$action_component = $component;
			}
		}
		if(empty($action_component)) {
			throw new PathException(sprintf('No component found for path "%s"', $filter_path));
		}
		if(!empty($_GET['form'])) {
			if(!$action_component instanceof Form) {
				throw new PathException(sprintf('Unexpected error. Component "%s" must be a form', $filter_path));
			}
			$action_component->executeAction();
		}
		else {
			$action = $parts[1];
			if(!method_exists($action_component, $action)) {
				throw new PathException(sprintf('Component "%s" must implement "%s" method', get_class($action_component), $action));
			}
			//aargs can be used in the url to pass arguments to the action method
			$action_args = !empty($_GET['aargs']) ? explode(',', $_GET['aargs']) : array();
			$result = call_user_func_array(array($action_component, $action), $action_args);
			if($result) {
				//manage response
				//DOMDocument
				if(get_class($result) === 'DOMDocument') {
					if(ob_get_length() > 0) {
						ob_end_flush();
					}
					else {
						header('Content-Type: application/xml');
						echo $result->saveXML();
					}
					return;
				}
				//Response
				if($result instanceof Response) {
					if(ob_get_length() > 0) {
						ob_end_flush();
					}
					else {
						$result->getHTTPStream();
					}
					return;
				}
			}
		}
	}

	//setup render
	if(!$xhr) {
		$page->setupRender();
	}

	//create result document
	$document = Renderer::createHTMLDocument();
	$document->preserveWhiteSpace = $config['preserve_space'];
	$document->formatOutput = $config['format_output'];

	$renderer = new Renderer($factory, $document, null, $config['preserve_comment'], $config['preserve_cdata']);
	$renderer->renderPage($page);

	//after render
	if(!$xhr) {
		$page->afterRender();
	}

	return $document;
}
