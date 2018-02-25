<?php
namespace RET\View;

//!An exception that is thrown in all view related matters. 

class View_exception extends \Exception {
	//!Constructs the exception.
	public function __construct($_a, $_b=0, $_c=null) {
		parent::__construct($_a, $_b, $_c);
	}
};

