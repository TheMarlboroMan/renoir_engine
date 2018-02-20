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

class Parser {

	private $current=0;
	private $root_operation=null;
	private $current_operation=null;

	public function __construct() {
		$this->root_operation=new Operation_noop;
		$this->current_operation=$this->root_operation;
	}

	//This should actually just parse and create an execution tree, not execute.
	//We'd need another component that does the execution.
	public function parse(array $_t) {
		return $this->process($_t);
	}

	private function process(array &$_t) {

		if(!count($_t)) {
			//This is the end condition...
			return $this->root_operation;
		}
		else {
			switch(get_class($_t[0])) {
				case Token_passthrough::class:
					return $this->do_passthrough($_t); break;
				case Token_put::class:
					return $this->do_put($_t); break;
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
					$this->fail('Unknown token '.get_class($tok)); 
				break;
			}
		}
	}
	
	private function do_passthrough(array &$_t) {
		$this->new_operation(new Operation_passthrough($this->shift($_t)->contents));
		return $this->process($_t);
	}

	private function do_put(array &$_t) {
		$this->shift($_t); //Remove Token_put
		$this->new_operation(new Operation_put($this->shift_must_be($_t, Token_expression::class)->expression));
		return $this->process($_t);
	}

	private function new_operation($obj) {
		$this->current_operation->next=$obj;
		$this->current_operation=$this->current_operation->next;
	}

	private function shift(array &$_t) {
		if(!count($_t)) {
			$this->fail("premature end!");
		}
		return array_shift($_t);
	}

	private function shift_must_be(array &$_t, $type) {
		$tok=$this->shift($_t);
		if(get_class($tok) != $type) {
			$this->fail('expected '.$type.', got'.get_class($tok));
		}
		return $tok;
	}

	private function fail($msg) {
		throw new LangException("Parser error: ".$msg);
	}
}
