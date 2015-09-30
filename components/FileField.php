<?php
/**
 * FileField class
 * @package components
 */
class FileField extends Field {

	public function render(Renderer $renderer, DOMElement $element): array {
		$document = $renderer->getDocument();
		$content = $document->createElement('p');
		$element->appendChild($content);

		//create error
		if($this->hasError()) {
			$class = 'error';
			$error = $document->createElement('span', $this->error);
			$error->setAttribute('class', 'error');
			$content->appendChild($error);
			$content->appendChild($document->createElement('br'));
		}

		$label = $document->createElement('label', $this->label);
		$label->setAttribute('for', $this->id);
		$content->appendChild($label);
		if(array_key_exists('required', $this->validators)) {
			$error->setAttribute('class', 'required');
		}

		$input = $document->createElement('input');
		$label->appendChild($input);

		$input->setAttribute('type', 'file');
		$input->setAttribute('id', $this->id);
		$input->setAttribute('name', $this->id);
		$input->setAttribute('value', $this->value);
		if($this->length != null) {
			$input->setAttribute('maxlength', $length);
		}
		if($this->placeholder != null) {
			$input->setAttribute('placeholder', $this->placeholder);
		}
		if($this->class != null) {
			$input->setAttribute('class', $this->class);
		}
		if($this->style != null) {
			$input->setAttribute('style', $this->style);
		}
		if($readonly) {
			$input->setAttribute('readonly', 'readonly');
		}
		return array();
	}
}
