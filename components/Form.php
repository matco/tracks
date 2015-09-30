<?php
/**
 * Form class
 * @package components
 */
class Form extends Component {

	const POST = 'post';
	const GET = 'get';

	public string $id;
	public string $method;
	public ?string $class = null;

	private array $fields = array();
	public bool $valid;

	public function getAction(): string {
		return 'index.php?page='.$this->getPage()->getPath().'&form='.$this->path;
	}

	public function executeAction() {
		//retrieve data
		if($this->method === self::POST) {
			$this->retrieveDataFromPost();
		}
		else {
			$this->retrieveDataFromGet();
		}
		//check fields
		$this->checkFields();
		if(!$this->valid) {
			return;
		}
		//execute callbacks
		foreach($this->fields as $field) {
			if($field instanceof Submit) {
				$this->context->{$field->name}();
			}
		}
	}

	public function setupRender() {
		if(!isset($this->id)) {
			throw new ErrorException('Form requires an id');
		}
		return true;
	}

	public function render(Renderer $renderer, DOMElement $element): array {
		$document = $renderer->getDocument();
		$form = $document->createElement('form');
		$element->appendChild($form);

		$form->setAttribute('id', $this->id);
		$form->setAttribute('method', $this->method);
		$form->setAttribute('action', $this->getAction());
		if(!empty($this->class)) {
			$form->setAttribute('class', $this->class);
		}

		return $renderer->renderNodes($form, $this->body, $this, $this->context, $this->path);
	}

	public function addField(Field $field) {
		foreach($this->fields as $f) {
			if($f->name === $field->name) {
				throw new ErrorException(sprintf('A field named "%s" already exists in form "%s"', $field->name, $this->id));
			}
		}
		$field->form = $this;
		array_push($this->fields, $field);
	}

	public function getField($name) {
		foreach($this->fields as $field) {
			if($field->name === $name) {
				return $field;
			}
		}
		throw new ErrorException(sprintf('No field named "%s" in form "%s"', $name, $this->id));
	}

	public function isSubmitted() {
		foreach($_REQUEST as $property) {
			if($property === $this->id) {
				return true;
			}
		}
		return false;
	}

	public function checkFields() {
		$this->valid = true;
		foreach($this->fields as $field) {
			if(!$field->checkField()) {
				$this->valid = false;
			}
		}
		return $this->valid;
	}

	public function retrieveDataFromPost() {
		$this->retrieveData($_POST);
	}

	public function retrieveDataFromGet() {
		$this->retrieveData($_GET);
	}

	public function retrieveData(&$data) {
		foreach($data as $property => $value) {
			$this->getField($property)->value = $value;
		}
	}
}
