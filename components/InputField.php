<?php
/**
 * InputField class
 * @package components
 */
class InputField extends Field {

	public bool $password = false;
	public bool $autocomplete = false;
	public string $placeholder = '';
	public ?int $size = null;
	public ?int $length = null;

	public function render(Renderer $renderer, DOMElement $element): array {
		$document = $renderer->getDocument();
		$content = $document->createElement('p');
		$element->appendChild($content);

		//create error
		if($this->hasError()) {
			$error = $document->createElement('span', $this->error);
			$error->setAttribute('class', 'error');
			$content->appendChild($error);
			$content->appendChild($document->createElement('br'));
		}

		//create label
		if(!empty($this->label)) {
			$label = $document->createElement('label', $this->label);
			$label->setAttribute('for', $this->id);
			$content->appendChild($label);
			if(array_key_exists('required', $this->validators)) {
				$error->setAttribute('class', 'required');
			}
		}

		//create input
		$input = $document->createElement('input');
		$content->appendChild($input);

		$input->setAttribute('type', $this->password ? 'password' : 'text');
		$input->setAttribute('id', $this->id);
		if(!empty($this->name)) {
			$input->setAttribute('name', $this->name);
		}
		if(!empty($this->value)) {
			$input->setAttribute('value', $this->value);
		}
		if(!empty($this->length)) {
			$input->setAttribute('size', $this->length);
			$input->setAttribute('maxlength', $this->length);
		}
		if(!empty($this->placeholder)) {
			$input->setAttribute('placeholder', $this->placeholder);
		}
		if(!empty($this->class)) {
			$input->setAttribute('class', $this->class);
		}
		if(!empty($this->style)) {
			$input->setAttribute('style', $this->style);
		}
		if($this->autocomplete != null) {
			$input->setAttribute('autocomplete', $this->autocomplete ? 'on' : 'off');
		}
		if($this->autofocus != null) {
			$input->setAttribute('autofocus', $this->autofocus ? 'true' : 'false');
		}
		if($this->readonly) {
			$input->setAttribute('readonly', 'readonly');
		}
		return array();
	}
}
