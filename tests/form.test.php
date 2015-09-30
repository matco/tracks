<?php

define('PASTRY_NAMESPACE', 'http://matco.name/schema/pastry.xsd');

require_once('../Logger.php');
require_once('../Factory.php');
require_once('../Renderer.php');

$GLOBALS['logger'] = new Logger(Logger::LEVEL_WARNING);

final class Test extends Page {

	public string $search = 'one';

	public function action(): void {
		echo 'Success';
	}
}

//create document
$document = new DOMDocument();
$document->loadXML(<<<XML
<p:form id="literal:search-form" method="literal:post" xmlns:p="http://matco.name/schema/pastry.xsd">
	<p:inputfield id="literal:search-input" name="literal:search" value="search" />
</p:form>
XML);

$result = Renderer::createHTMLDocument();

$factory = Factory::getInstance();
$renderer = new Renderer($factory, $result, null, true, true);

$component = new Test($document, 'test', 'test');
$components = $component->render($renderer, $result->documentElement);

echo $result->saveXML();

$form = $components[0];
$input = $components[1];

//a component and its child share the same reference
assert('one' === $component->search);
assert('one' === $input->value);

//changing the parent value means the child value is updated as well
$component->search = 'two';
assert('two' === $component->search);
assert('two' === $input->value);

//changing the child value means the parent value is updated as well
$value = &$component->search;
$value = 'three';
$input->value = $value;
assert('three' === $input->value);
assert('three' === $component->search);

$data = array('search' => 'four');
$form->retrieveData($data);
assert('four' === $input->value);
echo $component->search.PHP_EOL;
assert('four' === $component->search);
