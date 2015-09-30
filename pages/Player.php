<?php
/**
 * Player controller
 * @package controllers
 */
final class Player extends Page {

	public function provide($id) {
		$response = new Response();
		$response->nocache = true;
		$file = $GLOBALS['datacontext']->getObject('File', $id);
		$extension = $file->getExtension();
		$mimetype = $extension === 'ogg' ? 'audio/ogg' : 'audio/mpeg';
		$response->filename = $file->name;
		$response->mimetype = $mimetype;
		$response->stream = $file->read();
		$response->length = $file->getSize();
		$file->commit();
		return $response;
	}

	public function search($search, $order, $direction, $count, $offset) {
		$clause = sprintf('name LIKE %s', ConnectionProvider::getConnection()->quote('%'.$search.'%'));
		$files = $GLOBALS['datacontext']->getObjects('File', $clause, $order, $direction, $count, $offset);

		$number = File::getResultsNumber($search);
		$min = $offset + 1;
		$max = ($offset + $count > $number) ? $number : ($offset + $count);

		$document = new DOMDocument('1.0', 'utf-8');
		$root = $document->createElement('files');
		$root->setAttribute('number', $number);
		$root->setAttribute('min', $min);
		$root->setAttribute('max', $max);
		$document->appendChild($root);

		foreach($files as $file) {
			$node = $document->createElement('file');
			$node->appendChild($document->createElement('id', $file->getId()));
			$node->appendChild($document->createElement('name', str_replace('&', '&amp;', $file->name)));
			$node->appendChild($document->createElement('path', str_replace('&', '&amp;', $file->path)));
			$node->appendChild($document->createElement('playcount', $file->playcount));
			$node->appendChild($document->createElement('note', $file->note ? $file->note : 'NA'));
			$root->appendChild($node);
		}
		return $document;
	}

	public function random() {
		$id = rand(0, File::count());
		$file = $GLOBALS['datacontext']->getObject('File', $id);

		$document = new DOMDocument('1.0', 'utf-8');
		$node = $document->createElement('file');
		$node->appendChild($document->createElement('id', $file->getId()));
		$node->appendChild($document->createElement('name', str_replace('&', '&amp;', $file->name)));
		$node->appendChild($document->createElement('note', $file->note ? $file->note : 'NA'));
		$document->appendChild($node);

		return $document;
	}

	public function vote($id, $note) {
		$file = $GLOBALS['datacontext']->getObject('File', $id);
		$file->vote($note);
		$file->commit();

		$document = new DOMDocument('1.0', 'utf-8');
		$node = $document->createElement('file');
		$node->appendChild($document->createElement('id', $file->getId()));
		$node->appendChild($document->createElement('name', str_replace('&', '&amp;', $file->name)));
		$node->appendChild($document->createElement('note', $file->note ? $file->note : 'NA'));
		$document->appendChild($node);

		return $document;
	}
}
