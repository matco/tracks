<?php
/**
 * Field class
 * @package components
 */
abstract class Field extends Component {

	public ?string $id = null;
	public ?string $name = null;
	public ?string $label = null;
	public ?string $value = null;
	public string $type;
	public bool $readonly = false;
	public bool $autofocus = false;
	public array $validators = array();
	public ?string $class = null;
	public ?string $style = null;

	public Form $form;
	public ?string $error;

	public function __construct(Component $container, ?DOMDocument $template, string $path) {
		parent::__construct($container, $template, $path);
		//register the field in the container form
		$form = $this->getContainer(Form::class);
		$form->addField($this);
	}

	public function checkField(): bool {
		$this->error = null;
		foreach($this->validators as $validator) {
			if($validator === 'required' and empty($this->value)) {
				$this->error = sprintf('%s is required', $this->label != null ? $this->label : $this->id);
				return false;
			}
			if($validator === 'string' or $validator === 'int') {
				if(!call_user_func('is_'.$validator, $this->value)) {
					$this->error = sprintf('%s must be a %s', $this->label != null ? $this->label : $this->id, $validator);
				}
				return false;
			}
		}
		return true;
	}

	public function hasError(): bool {
		return !empty($this->error);
	}
}
