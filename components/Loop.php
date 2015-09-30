<?php
/**
 * Loop class
 * @package components
 */
class Loop extends Body {

	public $items;
	public $item;
	public $index;

	public function setupRender() {
		return !empty($this->items);
	}

	public function render(Renderer $renderer, DOMElement $element): array {
		$components = array();
		for($this->index = 0; $this->index < count($this->items); $this->index++) {
			$this->item = $this->items[$this->index];
			$child_components = $renderer->renderNodes($element, $this->body, $this, $this->context, $this->path);
			$components = array_merge($components, $child_components);
		}
		return $components;
	}
}
