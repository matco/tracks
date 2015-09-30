<?php
/**
 * File model
 * @package models
 */
class File extends Model {

	public static function count() {
		$query = 'SELECT COUNT(*) FROM files';
		$results = ConnectionProvider::getConnection()->query($query);
		return $results->fetchFirstColumn();
	}

	public static function getResultsNumber($search) {
		$query = sprintf('SELECT COUNT(*) FROM files WHERE name LIKE %s', ConnectionProvider::getConnection()->quote('%'.$search.'%'));
		$results = ConnectionProvider::getConnection()->query($query);
		return $results->fetchFirstColumn();
	}

	public function read() {
		$this->playcount++;
		$filename = $this->getDecodedPath();
		if(!is_file($filename)) {
			throw new Exception('Invalid file name');
		}
		if(!$handle = fopen($filename, "rb")) {
			throw new Exception('Unable to open file');
		}
		$content = fread($handle, filesize($filename));
		fclose($handle);
		return $content;
	}

	public function vote($note) {
		$this->note = ($this->note * $this->vote + $note) / ($this->vote + 1);
		$this->vote++;
	}

	public function getSize() {
		return filesize($this->getDecodedPath());
	}

	public function getExtension() {
		return pathinfo($this->name)['extension'];
	}

	public function getDecodedPath() {
		return utf8_decode($this->path).DIRECTORY_SEPARATOR.utf8_decode($this->name);
	}
}
