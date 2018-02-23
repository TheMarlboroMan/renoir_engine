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
	public $expressions;	//!< Array of expressions to be output.
	public function __construct(array $exp) {	//!< Constructs the object from the given array of expressions.
		$this->expressions=$exp;
	}
}

//!An operation that will begin a foreach loop.
class Operation_foreach extends Operation {
	public $iterable_expression;	//!< Solvable_expression that represents the iterable value.
	public $local_expression;	//!< Solvable or constant expression that names the local variable for the loop body.
	public $inner_operation_head;	//!< Points to the first Operation of the loop.

	//!Constructs the operation.
	public function __construct(Solvable_expression $_i, Expression $_l, $_h) {
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

class Import_symbol {
	public $expression;		//!< Expression to resolve.
	public $local_expression;	//!< New name of the symbol in the imported template.
	public function __construct(Expression $_e, Expression $_l) {
		$this->expression=$_e;
		$this->local_expression=$_l;
	}
}

class Operation_import extends Operation {

	const IMPORT_MODE_ALL=1;	//!< Imports all symbols from the current template.
	const IMPORT_MODE_NONE=2;	//!< Imports no symbols from the current template.
	const IMPORT_MODE_SYMBOL=3;	//!< Imports only the specified 

	public $source;			//!< An Expression to figure out the source.
	public $import_mode;		//!< One of the IMPORT_MODE_ constants.
	public $symbol_list;		//!< Array of Import Symbol.

	public function __construct(Expression $_s, $_i, $_l=[]) {
		$this->source=$_s;
		$this->import_mode=$_i;
		$this->symbol_list=$_l;
	}
}
