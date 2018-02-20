<?php
abstract class Expression {
	public $value;
	public abstract function is_const();
}

class Constant_expression extends Expression{
	public function __construct($_v) {
		$this->value=$_v;
	}
	public function is_const() {return true;}
}

class Solvable_expression extends Expression {
	public function __construct($_v) {
		$this->value=$_v;
	}
	public function is_const() {return false;}
}
