<?php
namespace Renoir_engine\View;

//!A class that represents a stream reader.

//!It supports the Tokenizer by feeding it the stream character by character,
//!even if the stream is just a string underneath.

class Reader {

	//Public interface.

	//!Returns a reader whose stream points to the contents of a file.
	public static function from_file($_path) {
		if(!file_exists($_path) || !is_file($_path)) {
			throw new View_exception("The file ".$_path." does not exist!!");
		}

		return new Reader(file_get_contents($_path));
	}

	//!Creates a reader whose stream points to a string.
	public static function from_string($_string) {
		if(!strlen($_string)) {
			throw new View_exception("The string '".$_string."' has no length!!");
		}

		return new Reader($_string);
	}

	//!Returns true if the stream has reached its end.
	public function is_eof() {
		return $this->curpos >= $this->length;
	}

	//!Returns the next character in the stream and advances the position.
	public function next() {
		$res=$this->get();
		++$this->curpos;
		return $res;
	}

	//!Returns the next character in the stream without advancing.
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
