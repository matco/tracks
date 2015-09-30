<?php
/**
 * Page controller
 * @package controllers
 */
abstract class Page extends Component {

	protected $args;

	public function __construct(DOMDocument $template, string $name, $args) {
		parent::__construct(null, $template, $name);
		$this->args = $args;
	}
}
