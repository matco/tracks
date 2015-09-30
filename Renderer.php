<?php
/**
 * Renderer class, rendering components
 * @package core
 */
class Renderer {

	private Factory $factory;
	private DOMDocument $document;
	private ?string $filter;
	private bool $preserveComment;
	private bool $preserveCData;

	public static function createHTMLDocument() {
		$implementation = new DOMImplementation();
		$doctype = $implementation->createDocumentType('html', '-//W3C//DTD XHTML 1.1//EN', 'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd');
		$document = $implementation->createDocument('http://www.w3.org/1999/xhtml', 'html', $doctype);
		$language = $document->createAttribute('xml:lang');
		$document->documentElement->appendChild($language);
		$language->value = 'fr';
		$document->xmlVersion = '1.0';
		$document->xmlStandalone = true;
		$document->encoding = 'UTF-8';
		return $document;
	}

	public function __construct(Factory $factory, DOMDocument $document, ?string $filter, bool $preserve_comment, bool $preserve_cdata) {
		$this->factory = $factory;
		$this->document = $document;
		$this->filter = $filter;
		$this->preserveComment = $preserve_comment;
		$this->preserveCData = $preserve_cdata;
	}

	public function getDocument(): DOMDocument {
		return $this->document;
	}

	public function renderPage(Page $page): array {
		$components = array($page);
		$child_components = $this->renderTemplateRoot($this->document->documentElement, $page->getTemplateRoot(), $page, $page);
		$components = array_merge($components, $child_components);
		return $components;
	}

	public function renderTemplateRoot(DOMElement $element, DOMNode $node, Component $component, Component $context): array {
		//swallow generic containers (they must not been added in the resulting document)
		if($node->localName === 'container' || $node->localName === 'html') {
			return $this->renderNodes($element, $node->childNodes, $component, $context);
		}
		else {
			return $this->renderNode($element, $node, $component, $context);
		}
	}

	public function renderNodes(DOMElement $element, DOMNodeList $nodes, Component $component, Component $context): array {
		$components = array();
		foreach($nodes as $node) {
			$child_components = $this->renderNode($element, $node, $component, $context);
			$components = array_merge($components, $child_components);
		}
		return $components;
	}

	public function renderNode(DOMElement $element, DOMNode $node, Component $component, Component $context): array {
		//log
		$log = sprintf('Rendering node in %s (%s) using %s (%s) as context', get_class($component), $component->getPath(), get_class($context), $context->getPath());
		$GLOBALS['logger']->addLog(Logger::LEVEL_DEBUG, Logger::TYPE_SYSTEM, __METHOD__, null, $log);
		//render
		$components = array();
		//classic HTML node
		if($node->namespaceURI !== PASTRY_NAMESPACE) {
			if($node->nodeType === XML_ELEMENT_NODE) {
				//copy node
				$cloned_node = $this->document->importNode($node);
				//replace text in attributes
				foreach($cloned_node->attributes as $attribute) {
					if(preg_match('/\${(\w*)}/', $attribute->value, $matches)) {
						$attribute->value = htmlentities($context->readProperty($matches[1]));
					}
				}
				$element->appendChild($cloned_node);
				$components = array_merge($components, $this->renderNodes($cloned_node, $node->childNodes, $component, $context));
			}
			elseif($node->nodeType === XML_COMMENT_NODE) {
				if($this->preserveComment) {
					//copy node
					$cloned_node = $this->document->importNode($node);
					$element->appendChild($cloned_node);
				}
			}
			elseif($node->nodeType === XML_CDATA_SECTION_NODE) {
				if($this->preserveCData) {
					//copy node
					$cloned_node = $this->document->importNode($node);
					$element->appendChild($cloned_node);
				}
			}
			elseif($node->nodeType === XML_TEXT_NODE) {
				//copy node
				$cloned_node = $this->document->importNode($node);
				//replace text
				$replacer = function($matches) use($context) {
					return $context->readProperty($matches[1]);
				};
				$cloned_node->nodeValue = preg_replace_callback('/\${(\w*)}/', $replacer, $node->nodeValue);
				$element->appendChild($cloned_node);
			}
		}
		//special node
		else {
			//derive a legible path from the node path
			$path = substr($node->getNodePath(), 1);
			$path = str_replace('/', '.', $path);
			$path = str_replace('p:', '', $path);
			$path = $component->getPath().'.'.$path;
			//if subpath does no match filter, no need to continue
			if(!empty($this->filter) and strpos($this->filter, $path) === false and strpos($path, $this->filter) === false) {
				return array();
			}
			$child_component = $this->factory->getComponent($component, $node->localName, $path);
			$child_component->initialize($node, $context);
			array_push($components, $child_component);
			//check if component must be rendered
			if($child_component->setupRender()) {
				$components = array_merge($components, $child_component->render($this, $element));
				$child_component->afterRender();
			}
		}
		return $components;
	}
}
