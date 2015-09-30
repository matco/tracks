<?php
/**
 * Body class
 * @package components
 */
class Body extends Component {

	public function render(Renderer $renderer, DOMElement $element): array {
		return $renderer->renderNodes($element, $this->body, $this, $this->context, $this->path);
	}
}
