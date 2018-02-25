<?php
namespace RET\Tools;

//!A very simple wrapper around a .ini file. 

//!Ini files can be used to store configuration values such as server paths,
//!database access values and so on.
class Ini_config {

	//!Creates an object from the .ini file path.
	public function	__construct($_path) {
		$this->data=@parse_ini_file($_path);
		if(!$this->data) {
			throw new \Exception("Error parsing ".$_path." for Ini_config");
		}
	}

	//!Returns the key. If the key does not exist, null is returned.
	public function get($_key) {
		return isset($this->data[$_key]) ? $this->data[$_key] : null;
	}

	//!Checks if the key exists.
	public function exists($_key) {
		return isset($this->data[$_key]);
	}

	private $data=null;
}
