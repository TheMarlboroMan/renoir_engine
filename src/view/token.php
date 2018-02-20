<?php
namespace Renoir_engine\View;

class Token_expression {
	const CONSTANT=0;
	const SOLVABLE=1;

	public $type;
	public $value;
	public function __construct($_v, $_t) {
		$this->type=$_t;
		$this->value=$_v;
	}
}

class Token_passthrough {
	public $contents='';
	public function __construct($_c) {
		$this->contents=$_c;
	}
}

class Token_put {}
class Token_foreach {}
class Token_endforeach {}
class Token_as {}

class Token_if {}
class Token_then {}
class Token_else {}
class Token_endif {}

class Token_condition {
	const EQUALS=1;
	const GREATER_THAN=2;
	const LESSER_THAN=3;
	const GREATER_OR_EQUAL_THAN=4;
	const LESSER_OR_EQUAL_THAN=5;
	const NOT_EQUALS=6;

	public $condition;
	public function __construct($_p) {
		$this->condition=$_p;
	}
}
