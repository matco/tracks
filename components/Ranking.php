<?php
/**
 * Ranking controller
 * @package controllers
 */
final class Ranking extends Component {

	public $limit;
	public $file;
	public $index;

	public function getFiles() {
		return $GLOBALS['datacontext']->getObjects('File', null, 'playcount', 'desc', $this->limit ? $this->limit : 5, 0);
	}

	public function getFilename() {
		return str_replace('&', '&amp;', $this->file->name);
	}

	public function getPlaycount() {
		return $this->file->playcount;
	}

	public function getClass() {
		return $this->index % 2 === 0 ? 'odd' : 'even';
	}
}
