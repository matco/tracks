<?php
//for all simple class
spl_autoload_register(function($class) {
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.$class.'.php';
	if(is_file($file)) {
		require_once($file);
		return;
	}
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$class.'.php';
	if(is_file($file)) {
		require_once($file);
		return;
	}
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$class.'.php';
	if(is_file($file)) {
		require_once($file);
		return;
	}
	$file = dirname(__FILE__).DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$class.'.php';
	if(is_file($file)) {
		require_once($file);
		return;
	}
});

/**
 * Factory class, building instance for all classes
 * Factory class is the most important class. All others instances used in the application are created by this class. It allows to be sure to only have one instance for each class at the same time
 * @package core
 */
class Factory {

	const PAGES_DIRECTORY = 'pages';
	const COMPONENTS_DIRECTORY = 'components';

	private static Factory $_instance;

	private array $pageDescriptions = array();
	private array $componentDescriptions = array();

	private array $pages = array();
	private array $components = array();

	/**
	 * Construct method
	 */
	private function __construct() {
		//build pages list
		$directory = dirname(__FILE__).DIRECTORY_SEPARATOR.self::PAGES_DIRECTORY;
		$handle = opendir($directory);
		while(false !== ($filename = readdir($handle))) {
			$file = $directory.DIRECTORY_SEPARATOR.$filename;
			if(!is_dir($file) and $filename != '.' and $filename != '..') {
				$description = ['code' => $file];
				$basename = basename($filename, '.php');
				$template = $directory.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$basename.'.xml';
				if(file_exists($template)) {
					$description['template'] = $template;
				}
				$this->pageDescriptions[strtolower($basename)] = $description;
			}
		}
		//build components list
		$directory = dirname(__FILE__).DIRECTORY_SEPARATOR.self::COMPONENTS_DIRECTORY;
		$handle = opendir($directory);
		while(false !== ($filename = readdir($handle))) {
			$file = $directory.DIRECTORY_SEPARATOR.$filename;
			if(!is_dir($file) and $filename != '.' and $filename != '..') {
				$description = ['code' => $file];
				$basename = basename($filename, '.php');
				$template = $directory.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$basename.'.xml';
				if(file_exists($template)) {
					$description['template'] = $template;
				}
				$this->componentDescriptions[strtolower($basename)] = $description;
			}
		}
	}

	public function __sleep() {
		return array('pageDescriptions', 'componentDescriptions', 'pages', 'components');
	}

	/**
	 * Singleton method
	 * @return Factory the current instantiation of the class or a new one
	 */
	public static function getInstance() {
		if(!isset(self::$_instance)) {
			self::$_instance = new Factory();
		}
		return self::$_instance;
	}

	/**
	 * Clone method
	 */
	public function __clone() {
		throw new Exception('Clone is not allowed');
	}

	/**
	 * Load a page class from a name
	 * @param string $page name of the page
	 * @param array $args arguments for the page
	 * @return Page instance of the page
	 */
	public function getPage(string $name, $args = null) {
		$id = strtolower($name);
		if(!array_key_exists($id, $this->pages)) {
			//check if page exists
			if(!array_key_exists($id, $this->pageDescriptions)) {
				throw new Exception(sprintf('No page with name "%s"', $id));
			}
			$description = $this->pageDescriptions[$id];
			require_once($description['code']);
			$template = null;
			if(array_key_exists('template', $description)) {
				$template = new DOMDocument();
				$template->load($description['template']);
			}
			$this->pages[$id] = new $id($template, $id, $args);
		}
		return $this->pages[$id];
	}

	/**
	 * Load a component class from a path
	 * @param Component $container container of the component
	 * @param string $name name of the component
	 * @param string $path path of the component
	 * @return Component instance of the component
	 */
	public function getComponent(Component $container, string $name, string $path) {
		if(!array_key_exists($path, $this->components)) {
			$id = strtolower($name);
			//check if component exists
			if(!array_key_exists($id, $this->componentDescriptions)) {
				throw new Exception(sprintf('No component with name "%s"', $id));
			}
			$description = $this->componentDescriptions[$id];
			require_once($description['code']);
			$template = null;
			if(array_key_exists('template', $description)) {
				$template = new DOMDocument();
				$template->load($description['template']);
			}
			$this->components[$path] = new $id($container, $template, $path);
		}
		return $this->components[$path];
	}
}
