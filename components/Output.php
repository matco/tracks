<?php
/**
 * Output class
 * @package components
 */
class Output extends Component {

	public $value;

	public function render(Renderer $renderer, DOMElement $element): array {
		$document = $renderer->getDocument();
		$content = $document->createElement('span', htmlentities($this->value));
		$element->appendChild($content);
		return array();
	}
}
