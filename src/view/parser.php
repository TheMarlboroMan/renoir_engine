<?php
namespace Renoir_engine\View;

//!Parses an array of tokens to produce Operation objects.

//!Only the first operation is returned from the Parser: the rest of operations
//!are available in the style of a linked list.

class Parser {

	const STOP_AT_END_FUNC='check_stop_at_end';	//!< Symbol for a function used to stop the parser when the tokens are used up.
	const STOP_AT_FOREACHEND_FUNC='check_stop_at_foreachend';	//!< Symbol for a function used to stop the parser when a foreach end token is reached.
	const STOP_AT_ELSE_OR_ENDIF_FUNC='check_stop_at_else_or_endif';	//!< Symbol for a function used to stop the parser when else or endif tokens are found.
	const STOP_AT_ENDIF_FUNC='check_stop_at_endif';	//!< Symbol for a function used to stop the parser when an endif token is found.

	//!Creates a parser. The default parameters will suffice for all client code.
	public function __construct($_psc=self::STOP_AT_END_FUNC, $_name='main', $_dd=0) {
		$this->set_stop_condition($_psc);
		$this->debug_name=$_name;
		$this->debug_depth=$_dd;
	}

	//!Activates debug mode, which outputs a few messages.
	public function activate_debug() {
		$this->debug_active=true;
	}

	//!Entry point to the parser, returns the first Operation.
	public function parse(array $_t) {
		return $this->process($_t);
	}

	//Internals.

	private $process_stop_condition=null;		//!< Name of the function that will stop the parser.
	private $current_operation=null;		//!< Points to the current Operation object.
	private $root_operation=null;			//!< Points to the first Operation object.
	private $debug_active=false;
	private $debug_name;				//!< Name of the parser of debug purposes.
	private $debug_depth;				//!< Number of subparsers spawned for debug purposes.

	//!Sets the function that will stop the parser.
	private function set_stop_condition($_sc) {
		switch($_sc) {
			case self::STOP_AT_END_FUNC:
			case self::STOP_AT_FOREACHEND_FUNC:
			case self::STOP_AT_ELSE_OR_ENDIF_FUNC:
			case self::STOP_AT_ENDIF_FUNC:
				$this->process_stop_condition=$_sc; break;
			default:
				$this->fail('unknown stop condition'); break;
		}

		if(!method_exists($this, $this->process_stop_condition)) {
			$this->fail("stop condition method '".$this->process_stop_condition."' does not exist");
		}
	}

	//!Function that will consume the current token. 

	//!This function works in a recursive way, consuming the first token and
	//!returning the result of a function that will eventually call it.
	private function process(array &$_t) {

		if(call_user_func([$this, $this->process_stop_condition], $_t)) {
			//This is the end condition...
			$this->debug_announce("reached end of parser");
			return $this->root_operation;
		}
		else {
			$tok=$this->shift($_t);

			switch(get_class($tok)) {
				case Token_passthrough::class:
					return $this->do_passthrough($tok, $_t); break;
				case Token_put::class:
					return $this->do_put($tok, $_t); break;
				case Token_foreach::class:
					return $this->do_foreach($tok, $_t); break;
				case Token_if::class:
					return $this->do_if($tok, $_t); break;
				case Token_import::class:
					return $this->do_import($tok, $_t); break;
				default:
					$this->fail('Unexpected token '.get_class($tok)); 
				break;
			}
		}
	}

	private function check_stop_at_end(array &$_t) {
		return !count($_t);
	}

	private function check_stop_at_foreachend(array &$_t) {
		try {
			return $this->check_type($_t, Token_endforeach::class);
		}
		catch(\Exception $e) {
			$this->fail('unexpected end inside foreach loop');
		}
	}

	private function check_stop_at_else_or_endif(array &$_t) {
		try {
			return $this->check_type($_t, Token_endif::class) || $this->check_type($_t, Token_else::class);
		}
		catch(\Exception $e) {
			$this->fail('unexpected end inside conditional evaluation');
		}
	}

	private function check_stop_at_endif(array &$_t) {
		try {
			return $this->check_type($_t, Token_endif::class);
		}
		catch(\Exception $e) {
			$this->fail('unexpected end inside else');
		}
	}
	
	//!Checks if the first item in the array is of type $type. Does not discard the token. Throws when no more tokens are available.
	private function check_type(array $_t, $type) {

		return $this->get_type($_t)==$type;
	}

	//!Returns the type of the first item in the array.
	private function get_type(array $_t) {
		if(!count($_t)) {
			$this->fail('check type failed: no more tokens');
		}
		return get_class($_t[0]);
	}

	//!Creates a passthrough operation.
	private function do_passthrough(Token_passthrough $tok, array &$_t) {
		$this->new_operation(new Operation_passthrough($tok->contents));
		return $this->process($_t);
	}

	//!Creates an import operation
	private function do_import(Token_import $tok, array &$_t) {

		//We have already discarded "import"... Let us get the "filename".
		$source_expression=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$this->shift_must_be($_t, Token_open_list::class);

		//Three distinct possibilities: we find an asterisk, we find a closing list or a list...
		$import_mode=null;
		$symbol_table=[];

		switch($this->get_type($_t)) {
			case Token_asterisk::class:
				$import_mode=Operation_import::IMPORT_MODE_ALL;
				$this->shift_must_be($_t, Token_asterisk::class); 
				$this->shift_must_be($_t, Token_close_list::class); 
				break;
			case Token_close_list::class:
				$import_mode=Operation_import::IMPORT_MODE_NONE;
				$this->shift_must_be($_t, Token_close_list::class); 
				break;
			default:
				try {
					$import_mode=Operation_import::IMPORT_MODE_SYMBOL;
					$symbol_table=$this->extract_import_table($_t);
				}
				catch(\Exception $e) {
					$this->fail('could not do import symbol table: '.$e->getMessage());
				}break;
		}

		$this->new_operation(new Operation_import($source_expression, $import_mode, $symbol_table));
		return $this->process($_t);
	}

	//!Extract a list of "expression as expression" symbols until "end of list" is found. Croaks if not.
	private function extract_import_table(array &$_t) {
		$result=[];
		$loop=true;

		while($loop){
			if(!count($_t)) {
				$this->fail('unexpected end in import symbol table');
			}
			$expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
			$this->shift_must_be($_t, Token_as::class);
			$local_expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));

			switch($this->get_type($_t)) {
				case Token_comma::class:
					$this->shift($_t); break;
				case Token_close_list::class:
					$this->shift($_t);
					$loop=false;
					break;
				default:
					$this->fail('unexpected '.$this->get_type($_t).' in import symbol list'); break;
			}

			$result[]=new Import_symbol($expr, $local_expr);
		};

		return $result;
	}

	//!Creates a put operation.
	private function do_put(Token_put $tok, array &$_t) {

		$this->shift_must_be($_t, Token_open_list::class);
		//Now we should have a comma separated list of expressions...
		$expr=[];
		while(true){
			if(!count($_t)) {
				$this->fail('unexpected end in put statement list');
			}

			$expr[]=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
			if(!$this->check_type($_t, Token_comma::class)) {
				break;
			}
			else {
				$this->shift($_t);
			}
		}
		$this->shift_must_be($_t, Token_close_list::class);
		$this->new_operation(new Operation_put($expr));
		return $this->process($_t);
	}

	//!Creates the sequence for a foreach operation.
	private function do_foreach(Token_foreach $tok, array &$_t) {

		$check_type=function($_e) {
			if(get_class($_e)!=Solvable_expression::class) {
				$this->fail('expressions in foreach loop must be solvable, '.get_class($_e).' found');
			}		
		};

		$iterable_expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$check_type($iterable_expr);

		$this->shift_must_be($_t, Token_as::class);

		//Local expression can either be static or solvable.
		$local_expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));

		//Now we'll need another series of instructions to run 
		$operation_head=$this->process_inner_parser($_t, self::STOP_AT_FOREACHEND_FUNC, 'foreach');
		$this->shift($_t); //Remove endforeach token.
		 
		$this->new_operation(new Operation_foreach($iterable_expr, $local_expr, $operation_head));
		return $this->process($_t);
	}

	//!Creates the sequence for a if operation.
	private function do_if(Token_if $tok, array &$_t) {
		//Extract lhs, condition and rhs (a == b)...
		$lhs=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$tok_condition=$this->shift_must_be($_t, Token_condition::class)->condition;
		$rhs=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$this->shift_must_be($_t, Token_then::class); //Discard then.

		//Let's start whith the code to execute if everything is ok with the condition...
		$ok_head=$this->process_inner_parser($_t, self::STOP_AT_ELSE_OR_ENDIF_FUNC, 'if condition');
		$this->debug_announce("innerparse positive finished");

		//Now we get to see if we have "else" or "endif", the only values allowed for when
		//the inner parser exited.
		$next=$this->shift($_t);

		$nook_head=null;
		switch(get_class($next)) {
			case Token_else::class:
				$this->debug_announce("starting else parser...");
				$nook_head=$this->process_inner_parser($_t, self::STOP_AT_ENDIF_FUNC, 'else condition');
				$this->debug_announce("gonna shift endif...");
				$this->shift_must_be($_t, Token_endif::class);
				$this->debug_announce("innerparse negative finished with endif");
			break;
			case Token_endif::class:
				$this->debug_announce("endif found with no else");
			break;
		}

		$this->new_operation(new Operation_if($lhs, $rhs, $this->if_operation_condition_from_tok_condition($tok_condition), $ok_head, $nook_head));
		return $this->process($_t);
	}

	//!Spawns an inner parser and runs the remaining tokens until its stop function is found, returning the first operation of the subparser. Used by ifs and foreachs.
	private function process_inner_parser(array &$_t, $_stop, $_name) {
		$this->debug_announce("starting inner parser for code '".$_name."'");
		$inner_parser=new Parser($_stop, $_name, $this->debug_depth+1);
		$inner_parser->debug_active=$this->debug_active;
		return $inner_parser->process($_t); //Process, not parse: parse won't remove elements from the array!.
	}

	//!Creates a new operation from the parameter and does the dirty work of setting the pointers.
	private function new_operation($obj) {

		if(null===$this->current_operation) {
			$this->current_operation=$obj;
			$this->root_operation=$this->current_operation;
		}
		else {
			$this->current_operation->next=$obj;
			$this->current_operation=$this->current_operation->next;
		}
	}

	//Consumes and returns the next token.
	private function shift(array &$_t) {
		if(!count($_t)) {
			$this->fail("premature end!");
		}

		$res=array_shift($_t);
		$this->debug_announce('shifted token '.get_class($res));

		return $res;
	}

	//!Consumes and returns the next token, making sure it is of the specified type.
	private function shift_must_be(array &$_t, $type) {
		$tok=$this->shift($_t);
		if(get_class($tok) != $type) {
			$this->fail('shift_must_be expected '.$type.', got '.get_class($tok));
		}
		return $tok;
	}

	//Converts a token if condition to the equivalent operation if condition. So far they are the same, but well, better safe than sorry!.
	private function if_operation_condition_from_tok_condition($cond) {
		switch($cond) {
			case Token_condition::EQUALS:
				return Operation_if::EQUALS; break;
			case Token_condition::GREATER_THAN:
				return Operation_if::GREATER_THAN; break;
			case Token_condition::LESSER_THAN:
				return Operation_if::LESSER_THAN; break;
			case Token_condition::GREATER_OR_EQUAL_THAN:
				return Operation_if::GREATER_OR_EQUAL_THAN; break;
			case Token_condition::LESSER_OR_EQUAL_THAN:
				return Operation_if::LESSER_OR_EQUAL_THAN; break;
			case Token_condition::NOT_EQUALS:
				return Operation_if::NOT_EQUALS; break;
			default:
				$this->fail('unknown if token condition!'); break;
		}
	}

	//TODO: We can have pipes too....This will need to change.
	//!Parses a Token_expression to create the corresponding Expression object.
	private function extract_expression(Token_expression $_t) {
		switch($_t->type) {
			case Token_expression::CONSTANT:
				return new Constant_expression($_t->value); break;
			case Token_expression::SOLVABLE:
				return new Solvable_expression($_t->value); break;
			default:
				$this->fail('unknown expression type in extract_expression'); break;
		}
	}

	private function fail($msg) {
		throw new View_exception("Parser [".$this->debug_name." - ".$this->debug_depth."] error: ".$msg);
	}

	private function debug_announce($msg) {
		if($this->debug_active) {
			echo '['.$this->debug_name.' - '.$this->debug_depth.'] : '.$msg."\n";
		}
	}
}
