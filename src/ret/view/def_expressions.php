<?php
namespace RET\View;

//!Abstract base class for Expressions, used by the View to use static or looked up value.

abstract class Expression {
	public $value;			//!< Represents either the constant expression or lookup key.
	public abstract function is_const();	//!< Will return true if represents a Constant_expression.
}

//!Constant expression, instructs the View to use a static value.

class Constant_expression extends Expression{
	//!Constructs the expression from the constant value.
	public function __construct($_v) {
		$this->value=$_v;
	}
	public function is_const() {return true;}	//!< Will return true if represents a Constant_expression.
}

//!Solvable expression, instructs the View to use a value looked up in its own scope.

class Solvable_expression extends Expression {
	//!Constructs the expression from the lookup key.
	public function __construct($_v) {
		$this->value=$_v;
	}
	public function is_const() {return false;}	//!< Will return true if represents a Constant_expression.
}
