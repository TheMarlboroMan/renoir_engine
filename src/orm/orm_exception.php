<?php
namespace Renoir_engine\ORM;

//!An exception thrown in everything related to the ORM module.

class ORM_exception extends \Exception {
	public function __construct($a, $b=0, $c=null) { //!< Constructs the exception.

		parent::__construct($a.':'.$b, null, $c);
	}
}
