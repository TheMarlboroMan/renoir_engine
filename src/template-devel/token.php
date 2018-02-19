<?php
class Token_passthrough {
	public $contents='';
	public function __construct($_c) {
		$this->contents=$_c;
	}
}

//TODO: Useless...
class Token_code {
	public $contents='';
	public function __construct($_c) {
		$this->contents=$_c;
	}
}

class Token_expression {
	public $expression;
	public function __construct($_c) {
		$this->expression=$_c;
	}
}

class Token_for {

}

class Token_endfor {

}

class Token_as {

}
