<?php
namespace RET\View;


//!Token for Parser consumption representing an expression.

//!Token produced by the Tokenizer to be consumed by the Parser. It represents
//!an expression of a value, either constant (string, integer or null) or
//!solvable by the view in execution time.

class Token_expression {
	const CONSTANT=0;		//!< Indicates this token is a constant value.
	const SOLVABLE=1;		//!< Indicates this token is a solvable value.

	public $type;			//!< Represents the type according to the constant.
	public $value;			//!< Represents either the constant value or the path to be resolved.

	//!Constructs an expression from the value and type.
	public function __construct($_v, $_t) {
		$this->type=$_t;
		$this->value=$_v;
	}
}

//!Token for Parser consumption representing a chunk of static text.

//!Token produced by the Tokenizer to be consumed by the Parser. It is produced
//!by all code outside of the code markers {{ and }}.

class Token_passthrough {

	public $contents='';		//!< Represents the static contents.

	//!Constructs an expression from the static contents.
	public function __construct($_c) {
		$this->contents=$_c;
	}
}

class Token_put {}		//!< Token for Parser consumption representing a put operation.
class Token_foreach {}		//!< Token for Parser consumption representing a foreach operation.
class Token_endforeach {}	//!< Token for Parser consumption representing the end of a foreach operation.
class Token_as {}		//!< Token for Parser consumption representing the keyword for local foreach variables.
class Token_if {}		//!< Token for Parser consumption representing the beginning of a conditional check.
class Token_then {}		//!< Token for Parser consumption representing the beginning of code for a positive conditional check.
class Token_else {}		//!< Token for Parser consumption representing the beginning of code for a negative conditional check.
class Token_endif {}		//!< Token for Parser consumption representing the end of a conditional check.
class Token_condition {		//!< Token for Parser consumption representing a condition.

	const EQUALS=1;					//!<Symbol for ==.
	const GREATER_THAN=2;				//!<Symbol for >.
	const LESSER_THAN=3;				//!<Symbol for <.
	const GREATER_OR_EQUAL_THAN=4;			//!<Symbol for >=.
	const LESSER_OR_EQUAL_THAN=5;			//!<Symbol for <=.
	const NOT_EQUALS=6;				//!<Symbol for !=.

	public $condition; //!< An integer representing a symbol for a condition.

	//!Constructs a token with a condition taken from its constants.
	public function __construct($_p) {
		$this->condition=$_p;
	}
}
class Token_open_list {}	//!< Token for opening a list.
class Token_close_list {}	//!< Token for a comma.
class Token_comma {}		//!< Token for a comma.
class Token_import{}		//!< Token for the import keyword.
class Token_import_file{}	//!< Token for importing a file as a template.
class Token_asterisk{}		//!< Token for the asterisk symbol.
