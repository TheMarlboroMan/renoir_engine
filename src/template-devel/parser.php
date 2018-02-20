<?php
class Parser {

	const STOP_AT_END_FUNC='check_stop_at_end';
	const STOP_AT_FOREACH_END_FUNC='check_stop_at_foreach_end';
	const STOP_AT_IF_END_FUNC='check_stop_at_if_end';
	const STOP_AT_ELSE_END_FUNC='check_stop_at_else_end';

	private $process_stop_condition=null;
	private $current_operation=null;
	private $root_operation=null;
	private $debug_active=false;
	private $debug_name;
	private $debug_depth;

	public function __construct($_psc=self::STOP_AT_END_FUNC, $_name='main', $_dd=0) {
		$this->set_stop_condition($_psc);
		$this->debug_name=$_name;
		$this->debug_depth=$_dd;
	}

	public function activate_debug($v) {
		$this->debug_active=true;
	}

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

	//This should actually just parse and create an execution tree, not execute.
	//We'd need another component that does the execution.
	public function parse(array $_t) {
		return $this->process($_t);
	}

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
		if(!count($_t)) {
			$this->fail('unexpected end inside foreach loop');
		}
		return get_class($_t[0])==Token_endforeach::class;
	}

	private function check_stop_at_if_end(array $_t) {
		if(!count($_t)) {
			$this->fail('unexpected end inside conditional evaluation');
		}

		$type=get_class($_t[0]);
		return $type==Token_endif::class || $type==Token_else::class;
	}

	private function check_stop_at_else_end(array $_t) {
		if(!count($_t)) {
			$this->fail('unexpected end inside else');
		}
		return get_class($_t[0])==Token_endif::class;
	}
	
	private function do_passthrough(Token_passthrough $tok, array &$_t) {
		$this->new_operation(new Operation_passthrough($tok->contents));
		return $this->process($_t);
	}

	private function do_put(Token_put $tok, array &$_t) {
		$expr=$this->extract_expression($this->shift_must_be($_t, Token_expression::class));
		$this->new_operation(new Operation_put($expr));
		return $this->process($_t);
	}

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

	private function process_inner_parser(array &$_t, $_stop, $_name) {
		$this->debug_announce("starting new parser '".$_name."'");
		$inner_parser=new Parser($_stop, $_name, $this->debug_depth+1);
		return $inner_parser->process($_t); //Process, not parse: parse won't remove elements from the array!.
	}

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

	private function shift(array &$_t) {
		if(!count($_t)) {
			$this->fail("premature end!");
		}

		$res=array_shift($_t);
		$this->debug_announce('shifted token '.get_class($res));

		return $res;
	}

	private function shift_must_be(array &$_t, $type) {
		$tok=$this->shift($_t);
		if(get_class($tok) != $type) {
			$this->fail('expected '.$type.', got'.get_class($tok));
		}
		return $tok;
	}

	//Converts a token if condition to the equivalent operation if condition.
	//So far they are the same, but well, better safe than sorry!.
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
