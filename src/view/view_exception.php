<?php
namespace Renoir_engine\View;

//Exception that will be thrown when something of the View namespace fails.

class View_exception extends \Exception {

	public function __construct($a, $b=0, $c=null) {
		parent::__construct($a, $b, $c);
	}
}
