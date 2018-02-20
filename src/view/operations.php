<?php
namespace Renoir_engine\View;

abstract class Operation {
	public $next=null;
}

class Operation_passthrough extends Operation {
	public $value='';
	public function __construct($val) {
		$this->value=$val;
	}
}

class Operation_put extends Operation {
	public $expression;
	public function __construct(Expression $exp) {
		$this->expression=$exp;
	}
}

class Operation_foreach extends Operation {
	public $iterable_expression;
	public $local_expression;
	public $inner_operation_head;

	public function __construct(Solvable_expression $_i, Solvable_expression $_l, $_h) {
		$this->iterable_expression=$_i;
		$this->local_expression=$_l;
		$this->inner_operation_head=$_h;
	}
}

class Operation_if extends Operation {
	public $lhs;
	public $rhs;
	public $condition;
	public $true_operation_head;
	public $false_operation_head;

	//The same as in the tokens, just in case.
	const EQUALS=1;
	const GREATER_THAN=2;
	const LESSER_THAN=3;
	const GREATER_OR_EQUAL_THAN=4;
	const LESSER_OR_EQUAL_THAN=5;
	const NOT_EQUALS=6;

	public function __construct(Expression $_l, Expression $_r, $_c, $_to, $_fo) {
		$this->lhs=$_l;
		$this->rhs=$_r;
		$this->condition=$_c;
		$this->true_operation_head=$_to;
		$this->false_operation_head=$_fo;
	}
}
