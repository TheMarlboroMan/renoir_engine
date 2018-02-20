<?php
namespace Renoir_engine\View;

//!Base class for an operation. 

//!Operations are consumed by the View, which transforms them into output.
abstract class Operation {
	public $next=null;	//!< Points to the next Operation, if any.
}

//!An Operation that outputs through static text.
class Operation_passthrough extends Operation {
	public $value='';	//!< Text to be output.
	public function __construct($val) {	//!< Constructs the object from the static text.
		$this->value=$val;
	}
}

//!An Operation that outputs an Expression.
class Operation_put extends Operation {
	public $expression;	//!< Expression to be output.
	public function __construct(Expression $exp) {	//!< Constructs the object from the given expression.
		$this->expression=$exp;
	}
}

//!An operation that will begin a foreach loop.
class Operation_foreach extends Operation {
	public $iterable_expression;	//!< Solvable_expression that represents the iterable value.
	public $local_expression;	//!< Solvable expression that names the local variable for the loop body.
	public $inner_operation_head;	//!< Points to the first Operation of the loop.

	//!Constructs the operation.
	public function __construct(Solvable_expression $_i, Solvable_expression $_l, $_h) {
		$this->iterable_expression=$_i;
		$this->local_expression=$_l;
		$this->inner_operation_head=$_h;
	}
}

//!An operation that will begin an if operation, with or without else.
class Operation_if extends Operation {
	public $lhs;			//!< Left hand side expression.
	public $rhs;			//!< Right hand side expression
	public $condition;		//!< Numeric symbol for the comparison.
	public $true_operation_head;	//!< Points to the first operation if the check passes.
	public $false_operation_head;	//!< Points to the first operation if the check fails, if any.

	//The same as in the tokens, just in case.
	const EQUALS=1;			//!< Represents ==.
	const GREATER_THAN=2;		//!< Represents >.
	const LESSER_THAN=3;		//!< Represents <.
	const GREATER_OR_EQUAL_THAN=4;	//!< Represents >=
	const LESSER_OR_EQUAL_THAN=5;	//!< Represents <=
	const NOT_EQUALS=6;		//!< Represents !=.

	//!Constructs the operation.
	public function __construct(Expression $_l, Expression $_r, $_c, $_to, $_fo) {
		$this->lhs=$_l;
		$this->rhs=$_r;
		$this->condition=$_c;
		$this->true_operation_head=$_to;
		$this->false_operation_head=$_fo;
	}
}
