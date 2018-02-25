<?php
namespace RET\ORM;

//!An exception thrown in everything related to the ORM module.

class ORM_exception extends \Exception {
	public function __construct($_a, $_b=0, $_c=null) { //!< Constructs the exception.

		parent::__construct($_a.':'.$_b, null, $_c);
	}
}
