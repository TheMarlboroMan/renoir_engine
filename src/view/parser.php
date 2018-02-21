<?php
namespace Renoir_engine\View;

//!Parses an array of tokens to produce Operations.

//!Only the first operation is returned from the Parser: the rest of operations
//!are available in the style of a linked list.

class Parser {

	const STOP_AT_END_FUNC='check_stop_at_end';	//!< Symbol for a function used to stop the parser when the tokens are used up.
	const STOP_AT_FOREACH_END_FUNC='check_stop_at_foreach_end';	//!< Symbol for a function used to stop the parser when a foreach end token is reached.
	const STOP_AT_IF_END_FUNC='check_stop_at_if_end';	//!< Symbol for a function used to stop the parser when else or endif tokens are found.
	const STOP_AT_ELSE_END_FUNC='check_stop_at_else_end';	//!< Symbol for a function used to stop the parser when an endif token is found.

	//!Creates a parser. The default parameters will suffice for all client code.
	public function __construct($_psc=self::STOP_AT_END_FUNC, $_name='main', $_dd=0) {
		$this->set_stop_condition($_psc);
		$this->debug_name=$_name;
		$this->debug_depth=$_dd;
	}

	//!Activates debug mode, which outputs a few messages.
	public function activate_debug($v) {
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
			case self::STOP_AT_FOREACH_END_FUNC:
			case self::STOP_AT_IF_END_FUNC:
			case self::STOP_AT_ELSE_END_FUNC:
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
				default:
					$this->fail('Unexpected token '.get_class($tok)); 
				break;
			}
		}
	}

	private function check_stop_at_end(array &$_t) {
		return !count($_t);
	}

	private function check_stop_at_foreach_end(array &$_t) {
		try {
			return $this->check_type($_t, Token_endforeach::class);
		}
		catch(\Exception $e) {
			$this->fail('unexpected end inside foreach loop');
		}
	}

	private function check_stop_at_if_end(array $_t) {
		try {
			return $this->check_type($_t, Token_endif::class) || $this->check_type($_t, Token_else::class);
		}
		catch(\Exception $e) {
			$this->fail('unexpected end inside conditional evaluation');
		}
	}

	private function check_stop_at_else_end(array $_t) {
		try {
			return $this->check_type($_t, Token_endif::class);
		}
		catch(\Exception $e) {
			$this->fail('unexpected end inside else');
		}
	}
	
	//!Checks if the first item in the array is of type $type. Does not discard the token. Throws when no more tokens are available.
	private function check_type(array $_t, $type) {
		if(!count($_t)) {
			$this->fail('check type failed: no more tokens');
		}
		return get_class($_t[0])==$type;
	}

	//!Creates a passthrough operation.
	private function do_passthrough(Token_passthrough $tok, array &$_t) {
		$this->new_operation(new Operation_passthrough($tok->contents));
		return $this->process($_t);
	}

	//!Creates a put operation.
	private function do_put(Token_put $tok, array &$_t) {

		$this->shift_must_be($_t, Token_open_list::class);
		//Now we should have a comma separated list of expressions...
		$expr=[];
		while(true){
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
				$this->fail('expressions in foreach loop must be solvable');
			}		
		};

		$iterable_expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$check_type($iterable_expr);

		$this->shift_must_be($_t, Token_as::class);

		$local_expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$check_type($local_expr);

		//Now we'll need another series of instructions to run 
		$operation_head=$this->process_inner_parser($_t, self::STOP_AT_FOREACH_END_FUNC, 'foreach');
		$this->shift($_t); //Remove endforeach token.
		 
		$this->new_operation(new Operation_foreach($iterable_expr, $local_expr, $operation_head));
		return $this->process($_t);
	}

	//!Creates the sequence for a if operation.
	private function do_if(Token_if $tok, array $_t) {
		//Extract lhs, condition and rhs (a == b)...
		$lhs=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$tok_condition=$this->shift_must_be($_t, Token_condition::class)->condition;
		$rhs=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$this->shift_must_be($_t, Token_then::class); //Discard then.

		//Let's start whith the code to execute if everything is ok with the condition...
		$ok_head=$this->process_inner_parser($_t, self::STOP_AT_IF_END_FUNC, 'if');

		//Now we get to see if we have "else" or "endif", the only values allowed for when
		//the inner parser exited.
		$next=$this->shift($_t);
		$nook_head=null;
		if(get_class($next)==Token_else::class) {
			$nook_head=$this->process_inner_parser($_t, self::STOP_AT_ELSE_END_FUNC, 'else');
			$this->shift_must_be($_t, Token_endif::class);
		}

		$this->new_operation(new Operation_if($lhs, $rhs, $this->if_operation_condition_from_tok_condition($tok_condition), $ok_head, $nook_head));
		return $this->process($_t);
	}

	//!Spawns an inner parser and runs the remaining tokens until its stop function is found, returning the first operation of the subparser. Used by ifs and foreachs.
	private function process_inner_parser(array &$_t, $_stop, $_name) {
		$this->debug_announce("starting new parser '".$_name."'");
		$inner_parser=new Parser($_stop, $_name, $this->debug_depth+1);
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