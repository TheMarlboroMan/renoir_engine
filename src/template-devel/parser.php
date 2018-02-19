<?php
//This is the parser...
class Parser {

	private $vars=[];
	private $current=0;
	private $tokens=[];

	public function __construct() {

	}

	public function set_var($key, $value) {
		$this->vars[$key]=$value;
	}

	//This should actually parse AND execute...
	public function parse(array $_t) {

		$this->tokens=$_t;
		while($this->current < count($this->tokens)) {
			$this->process($this->tokens[$this->current]);
		}
	}

	private function process($tok) {

		switch(get_class($tok)) {
			case Token_passthrough::class:
				$this->do_passthrough($tok);
			break;
			case Token_put::class:
				$this->do_put();
			break;
			default:
				$this->fail('Unknown token '.get_class($tok)); 
			break;
		}
	}
	
	private function do_passthrough(Token_passthrough $tok) {
		echo $tok->contents;
		$this->advance();
	}

	private function do_put() {
		$this->advance();
		$expr=$this->must_be(Token_expression::class);
		//Echo expression resolve...
		echo $this->resolve_expression($expr->expression);
		$this->advance();
	}

	//Checks and returns.
	private function must_be($type) {
		if($this->current >= count($this->tokens)) {
			$this->fail('must_be found no token');
		}

		$cur=$this->tokens[$this->current];
		if(get_class($cur) != $type) {
			$this->fail('expected '.$type.', got'.get_class($dcur));
		}
		return $cur;
	}

	private function resolve_expression($expr) {

		if(!array_key_exists($expr, $this->vars)) {
			$this->fail('could not resolve expression '.$expr);
		}

		return $this->vars[$expr];
	}

	private function advance() {
		++$this->current;
	}

	private function fail($msg) {
		throw new LangException("Parser error: ".$msg);
	}
}
