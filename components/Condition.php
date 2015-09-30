<?php
/**
 * Condition class
 * @package components
 */
class Condition extends Body {

	public bool $test;
	public bool $negate;

	public function setupRender() {
		if(!isset($this->negate)) {
			$this->negate = false;
		}
		if(!isset($this->test)) {
			$this->test = false;
		}
		return $this->test and !$this->negate or !$this->test and $this->negate;
	}
}
