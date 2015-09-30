<?php
/**
 * Component class
 * @package components
 */
abstract class Component {

	protected Component $context;
	protected ?Component $container;
	protected ?DOMDocument $template;
	protected string $path;
	protected DOMNodeList $body;

	public function __construct(?Component $container, ?DOMDocument $template, string $path) {
		//log
		$log = sprintf('Creating %s (%s)', get_class($this), $path);
		if($template !== null) {
			$log .= ' with template';
		}
		if($container !== null) {
			$log .= sprintf(' in %s (%s)', get_class($container), $container->path);
		}
		$GLOBALS['logger']->addLog(Logger::LEVEL_DEBUG, Logger::TYPE_SYSTEM, __METHOD__, null, $log);
		//construction
		$this->container = $container;
		$this->template = $template;
		$this->path = $path;
	}

	public function __debugInfo() {
		return [
			'class' => get_class($this),
			'path' => $this->path
		];
	}

	public function __toString() {
		return get_class($this).' '.$this->path;
	}

	public function initialize(DOMNode $node, Component $context): void {
		//log
		$log = sprintf('Initializing %s (%s) using %s (%s) as context', get_class($this), $this->path, get_class($context), $context->path);
		$GLOBALS['logger']->addLog(Logger::LEVEL_DEBUG, Logger::TYPE_SYSTEM, __METHOD__, null, $log);
		//store context
		$this->context = $context;
		//manage body
		if($node->hasChildNodes()) {
			$this->body = $node->childNodes;
		}
		//manage parameters
		foreach($node->attributes as $attribute) {
			//normal parameters
			if(strpos($attribute->value, 'literal:') === false) {
				$this->assignProperty($context, $attribute->value, $attribute->name);
			}
			//literal parameters
			else {
				$literal = substr($attribute->value, strpos($attribute->value, ':') + 1);
				//coerce literal into a primary type
				//boolean
				if($literal === 'true') {
					$value = true;
				}
				elseif($literal === 'false') {
					$value = false;
				}
				//array
				elseif(preg_match('#\[([A-Za-z,]*)\]#', $literal, $matches)) {
					$value = explode(',', $matches[1]);
				}
				//literal
				else {
					$value = $literal;
				}
				$this->writeProperty($attribute->name, $value);
			}
		}
	}

	public function setupRender() {
		return true;
	}

	public function render(Renderer $renderer, DOMElement $element): array {
		//log
		$log = sprintf('Rendering %s (%s)', get_class($this), $this->path);
		$GLOBALS['logger']->addLog(Logger::LEVEL_DEBUG, Logger::TYPE_SYSTEM, __METHOD__, null, $log);
		//render
		if($this->hasTemplate()) {
			return $renderer->renderTemplateRoot($element, $this->getTemplateRoot(), $this, $this, $this->path);
		}
		throw new ErrorException(sprintf('Component "%s" does not have a template and must implement the "render" method', get_class($this)));
	}

	public function readProperty(string $property) {
		//try to get property directly
		if(property_exists($this, $property)) {
			return $this->{$property};
		}
		//try to use a getter
		$getter = 'get'.ucfirst($property);
		if(method_exists($this, $getter)) {
			return $this->{$getter}();
		}
		throw new ErrorException(sprintf('"%s" is missing a property named "%s" or must implement the method "%s"', get_class($this), $property, $getter));
	}

	public function assignProperty(Component $context, string $context_property, string $property) {
		//try to link properties directly
		if(property_exists($context, $context_property)) {
			$this->{$property} = &$context->{$context_property};
			return;
		}
		//try to use a getter
		$getter = 'get'.ucfirst($context_property);
		if(method_exists($context, $getter)) {
			$this->{$property} = $context->{$getter}();
			return;
		}
		throw new ErrorException(sprintf('"%s" is missing a property named "%s" or must implement the method "%s"', get_class($context), $context_property, $getter));
	}

	public function writeProperty(string $property, $value) {
		//try to set property directly
		if(property_exists($this, $property)) {
			$this->{$property} = &$value;
			return;
		}
		//try to use a setter
		$setter = 'set'.ucfirst($property);
		if(method_exists($this, $setter)) {
			$this->{$setter}($value);
			return;
		}
		throw new ErrorException(sprintf('"%s" is missing a property named "%s" or must implement the method "%s"', get_class($this), $property, $setter));
	}

	public function afterRender() {
	}

	public function getContext(): Component {
		return $this->context;
	}

	public function hasTemplate(): bool {
		return !empty($this->template);
	}

	public function getTemplate(): DOMDocument {
		return $this->template;
	}

	public function getTemplateRoot(): DOMElement {
		return $this->template->documentElement;
	}

	public function getPath() {
		return $this->path;
	}

	public function setBody(DOMNodeList $body) {
		$this->body = $body;
	}

	public function getBody(): DOMNodeList {
		return $this->body;
	}

	/**
	 * Retrieve the first parent container matching the parameter class
	 * @param Class $class the class to match for the container
	 * @return Component the first component matching the class
	 */
	public function getContainer($class = null): Component {
		$container = $this->container;
		if($class === null) {
			return $container;
		}
		while($container !== null and !$container instanceof $class) {
			$container = $container->container;
		}
		if($container !== null) {
			return $container;
		}
		throw new Exception('Unable to find a "%s" container', $class);
	}

	public function getContainers(): array {
		$containers = array();
		$container = $this->container;
		while($container !== null) {
			array_unshift($containers, $container);
			$container = $container->container;
		}
		return $containers;
	}

	public function getPage(): Page {
		if($this instanceof Page) {
			return $this;
		}
		return $this->getContainers()[0];
	}
}
