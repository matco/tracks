<?php
/**
 * Response sent by the framework
 * Response allows you to create your own kind of response
 * @package core
 */
class Response {

	public $mimetype;
	public $filename;
	public $nocache;
	public $length;
	public $stream;

	public function getHTTPStream() {
		if($this->nocache) {
			header('Expires: Mon, 06 Jul 1985 11:00:00 GMT');
			header('Last-Modified: '.gmdate("D, d M Y H:i:s").' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: post-check=0, pre-check=0', false);
			header('Pragma: no-cache');
		}
		if($this->mimetype) {
			header('Content-Type: '.$this->mimetype);
		}
		if($this->filename) {
			header('Content-Disposition: attachment; filename="'.$this->filename.'"');
		}
		if($this->length) {
			header('Content-transfer-encoding: binary');
			header('Content-length: '.$this->length);
		}
		echo $this->stream;
	}
}
