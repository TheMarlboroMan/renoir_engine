<?php
namespace Renoir_engine\View;

class Reader {

	//Public interface.

	public static function from_file($_path) {
		if(!file_exists($_path) || !is_file($_path)) {
			throw new View_exception("The file ".$_path." does not exist!!");
		}

		return new Reader(file_get_contents($_path));
	}

	public static function from_string($_string) {
		if(!strlen($_string)) {
			throw new View_exception("The string '".$_string."' has no length!!");
		}

		return new Reader($_string);
	}

	public function is_eof() {
		return $this->curpos >= $this->length;
	}

	public function next() {
		$res=$this->get();
		++$this->curpos;
		return $res;
	}

	public function get() {
		return $this->stream[$this->curpos];
	}
	
	//Internals.

	private $stream;
	private $curpos;
	private $lenght;

	private function __construct($_s) {

		$this->stream=$_s;
		$this->curpos=0;
		$this->length=strlen($this->stream);
	}
	
}
