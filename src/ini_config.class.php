<?php
namespace Renoir_Engine\tools;

class Ini_config {

	private $data=null;

	public function	__construct($_path) {

		$this->data=@parse_ini_file($_path);
		if(!$this->data) {
			//TODO: This should be a Tools exception.
			throw new \Exception("Error parsing ".$_path." for Ini_config");
		}
	}

	public function get($key) {

		return isset($this->data[$key]) ? $this->data[$key] : null;
	}

	public function exists($key) {

		return isset($this->data[$key]);
	}
}
