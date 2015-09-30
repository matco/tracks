<?php
/**
 * Submit class
 * @package components
 */
class Submit extends Field {

	public function render(Renderer $renderer, DOMElement $element): array {
		$document = $renderer->getDocument();
		$content = $document->createElement('p');
		$element->appendChild($content);

		$submit = $document->createElement('input');
		$content->appendChild($submit);

		$submit->setAttribute('type', 'submit');
		$submit->setAttribute('name', $this->name);
		$submit->setAttribute('value', $this->value);
		return array();
	}
}
