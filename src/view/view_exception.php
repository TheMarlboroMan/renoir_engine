<?php
namespace Renoir_engine\View;

//!An exception that is thrown in all view related matters. 

class View_exception extends \Exception {
	//!Constructs the exception.
	public function __construct($a, $b=0, $c=null) {parent::__construct($a, $b, $c);}
};

