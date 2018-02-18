<?php
namespace Renoir_engine\ORM;

//This exception can be caught when errors relating to the ORM module 
//must be specifically treated.

class ORM_exception extends \Exception {
	public function __construct($a, $b=0, $c=null) {
		parent::__construct($a, $b, $c);
	}
}
