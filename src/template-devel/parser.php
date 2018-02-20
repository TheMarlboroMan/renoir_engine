<?php
abstract class Operation {
	public $next=null;
}

//TODO: This should be the root, actually, perhaps we can dispose of it.
class Operation_noop extends Operation {

}

class Operation_passthrough extends Operation {
	public $value='';
	public function __construct($val) {$this->value=$val;}
}

class Operation_put extends Operation {
	public $expression;
	public function __construct($exp) {$this->expression=$exp;}
}

class Operation_foreach extends Operation {
	public $iterable_expression;
	public $local_expression;
	public $inner_operation_head;
	public function __construct($_i, $_l, $_h) {
		$this->iterable_expression=$_i;
		$this->local_expression=$_l;
		$this->inner_operation_head=$_h;
	}
}

class Parser {

	const STOP_AT_END=0;
	const STOP_AT_FOREACH_END=1;

	const STOP_AT_END_FUNC='check_stop_at_end';
	const STOP_AT_FOREACH_END_FUNC='check_stop_at_foreach_end';

	private $current=0;
	private $root_operation=null;
	private $current_operation=null;
	private $process_stop_condition=null;
	private $debug_name;
	private $debug_depth;

	public function __construct($_psc=self::STOP_AT_END, $_name='main', $_dd=0) {
		$this->root_operation=new Operation_noop;
		$this->current_operation=$this->root_operation;
		$this->set_stop_condition($_psc);
		$this->debug_name=$_name;
		$this->debug_depth=$_dd;
	}

	private function set_stop_condition($_sc) {

		switch($_sc) {
			case self::STOP_AT_END:
				$this->process_stop_condition=self::STOP_AT_END_FUNC; break;
			case self::STOP_AT_FOREACH_END:
				$this->process_stop_condition=self::STOP_AT_FOREACH_END_FUNC; break;
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
				/*
				case Token if
					$lhe=this->must_follow(expr); 
					$pred¡cate=$this->must_follow(predicate);
					$rhe=this->must_be(expr);
					$this->must_follow(then);
					//TODO: What now????
					$this->must_follow(endif);
				*/
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
	
	private function do_passthrough(Token_passthrough $tok, array &$_t) {
		$this->new_operation(new Operation_passthrough($tok->contents));
		return $this->process($_t);
	}

	private function do_put(Token_put $tok, array &$_t) {
		$this->new_operation(new Operation_put($this->shift_must_be($_t, Token_expression::class)->expression));
		return $this->process($_t);
	}

	private function do_foreach(Token_foreach $tok, array &$_t) {
		$iterable_expr=$this->shift_must_be($_t, Token_expression::class)->expression;
		$this->shift_must_be($_t, Token_as::class);
		$local_expr=$this->shift_must_be($_t, Token_expression::class)->expression;

		//Now we'll need another series of instructions to run 
		$operation_head=$this->process_inner_parser($_t, self::STOP_AT_FOREACH_END, 'foreach');
		$this->shift($_t); //Remove endforeach token.
		
		$this->new_operation(new Operation_foreach($iterable_expr, $local_expr, $operation_head));
		return $this->process($_t);
	}

	private function process_inner_parser(array &$_t, $_stop, $_name) {
		$this->debug_announce("starting new parser '".$_name."'");
		$inner_parser=new Parser($_stop, $_name, $this->debug_depth+1);
		return $inner_parser->process($_t); //Process, not parse: parse won't remove elements from the array!.
	}

	private function new_operation($obj) {
		$this->current_operation->next=$obj;
		$this->current_operation=$this->current_operation->next;
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

	private function fail($msg) {
		throw new View_exception("Parser [".$this->debug_name." - ".$this->debug_depth."] error: ".$msg);
	}

	private function debug_announce($msg) {
		echo '['.$this->debug_name.' - '.$this->debug_depth.'] : '.$msg."\n";
	}
}
