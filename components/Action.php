<?php
/**
 * Action class
 * @package components
 */
class Action extends Body {

	public ?string $link;
	public ?string $action;
	public ?string $active;

	public function render(Renderer $renderer, DOMElement $element): array {
		//retrieve current page
		global $config;
		$current_page = array_key_exists('page', $_GET) ? $_GET['page'] : $config['start_page'];

		$document = $renderer->getDocument();
		$link = $document->createElement('a');
		$page = $this->link ?? $this->getPage()->getPath();
		$href = 'index.php?page='.$page;
		if(!empty($this->action)) {
			$href .= '&action='.$this->context->getPath().':'.$this->action;
		}
		$link->setAttribute('href', $href);
		if(strcasecmp($current_page, $page) === 0) {
			$link->setAttribute('class', 'active');
		}
		$element->appendChild($link);
		return parent::render($renderer, $link);
	}
}
