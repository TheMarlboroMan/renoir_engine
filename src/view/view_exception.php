<?php
namespace Renoir_engine\View;

//TODO: Document.

class View_exception extends \Exception {

	public function __construct($a, $b=0, $c=null) {
		parent::__construct($a, $b, $c);
	}
}
