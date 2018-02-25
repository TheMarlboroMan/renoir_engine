<?php
namespace RET\View;

//TODO: Document move to its own file.
class Expression_chunk {
	public $key;
	public $indirection;
	public function __construct($_k=null, $_i=null) {
		$this->key=$_k;
		$this->indirection=$_i;
	}
}

//TODO: Add different outputs: to file, to string, to standard.

//!Defines the combination of a template and a set of variables that will be used on it.

//!TODO: Extended comment.
//!TODO: Document path syntax.

class View { 

	//!Sets the template code from a string.
	public function set_template_string($_str) {
		if(!strlen($_str)) {
			$this->fail("set_template_string must be called with a string!");
	}
		$this->template_source=$_str;
		return $this;
	}

	//!Sets the template code from a file.
	public function set_template_file($_filename) {
		if(!file_exists($_filename) || !is_file($_filename)) {
			$this->fail("'".$_filename."' is not a valid template source file!");
		}
		$this->template_source=file_get_contents($_filename);
		return $this;
	}

	//!Sets a variable in the local scope.
	public function set($_key, $_value) {
		$this->values[$_key]=$_value;
		return $this;
	}

	//!Causes the template to be output.
	public function render() {
		try {
			$t=Tokenizer::from_string($this->template_source);
			$p=new Parser;
			//TODO: this should be some Ini constant...
			//$p->activate_debug();
			$tokens=$t->tokenize();
//print_r($tokens);
			$op=$p->parse($tokens);

			$this->do_operation_sequence($op);
			return $this->buffer;
		}
		catch(\Exception $e) {
			$this->fail('Render error: '.$e->getMessage());
		}
	}

	//Inner workings...
	private $template_source=null;
	private $values=[];
	private $buffer='';

	private function do_operation_sequence($_op) {

		do{
			$_op=$this->process_operation($_op);
		}while($_op!=null);
	}

	private function process_operation($_op) {

		switch(get_class($_op)) {
			case Operation_passthrough::class:
				$this->to_output($_op->value); 
				return $_op->next;
			break;
			case Operation_put::class:
				return $this->do_put($_op);
			break;
			case Operation_foreach::class:
				return $this->do_foreach($_op); break;
			case Operation_if::class:
				return $this->do_if($_op); break;
			case Operation_import::class:
				return $this->do_import($_op); break;
			default:
				$this->fail('Unknown token '.get_class($_op)); break;
		}
	} 

	private function do_import(Operation_import $_op) {
		$v=new View;

		//Getting the template...
		$filename=$this->expression_value($_op->source);
		try {
			$v->set_template_file($filename);
		}
		catch(\Exception $e) {
			$this->fail('unable to import template: '.$e->getMessage());
		}

		switch($_op->import_mode) {
			case Operation_import::IMPORT_MODE_ALL:
				$v->values=$this->values; break;
			case Operation_import::IMPORT_MODE_NONE: break;
			case Operation_import::IMPORT_MODE_SYMBOL:
				foreach($_op->symbol_list as $symbol) {
					$v->values[$symbol->local_expression->value]=$this->expression_value($symbol->expression);
				}
			break;
			default:
				$this->fail('unknown import mode in do_import!'); break;
		}

		$this->to_output($v->render());
		return $_op->next;
	}

	//!Executes the put operation.
	private function do_put(Operation_put $_op) {
		foreach($_op->expressions as &$e) {
			$this->to_output($this->expression_value($e));
		}
		return $_op->next; 
	}

	//Foreach only works with available values, not for constant values.
	//This could do with better scope management...
	private function do_foreach(Operation_foreach $_op) {

		//TODO: This should work with every iterable thing, not only arrays.
		$it=$this->expression_value($_op->iterable_expression);
		if(!is_array($it)) {
			$this->fail($_op->iterable_expression->value.' is not an array');
		}

		//Now... if the local expression exists in the larger scope, let's save it...
		$original=$this->value_exists($_op->local_expression->value) ? $this->get_value($_op->local_expression->value) : null;

		foreach($it as $value) {
			$this->set($_op->local_expression->value, $value);
			//TODO: do_operation_sequence could actually inherit a scope.
			$this->do_operation_sequence($_op->inner_operation_head);
		}

		//Restore the original key.
		if(null!==$original) {
			$this->set($_op->local_expression->value, $original);
		}

		return $_op->next;
	}

	//!Executes the if operation.
	private function do_if(Operation_if $_op) {

		//Extract lhs and rhs final values, either constant or resolved.
		$lhs=$this->expression_value($_op->lhs);
		$rhs=$this->expression_value($_op->rhs);

		$res=true;
		switch($_op->condition) {
			case Operation_if::EQUALS:
				$res=$lhs==$rhs; break;
			case Operation_if::GREATER_THAN:
				$res=$lhs>$rhs; break;
			case Operation_if::LESSER_THAN:
				$res=$lhs<$rhs; break;
			case Operation_if::GREATER_OR_EQUAL_THAN:
				$res=$lhs>=$rhs; break;
			case Operation_if::LESSER_OR_EQUAL_THAN:
				$res=$lhs<=$rhs; break;
			case Operation_if::NOT_EQUALS:
				$res=$lhs!=$rhs; break;
			default:
				$this->fail('undefined condition on if operation'); break;
		}
		
		if($res) {
			$this->do_operation_sequence($_op->true_operation_head);
		}
		else {
			if(null!==$_op->false_operation_head) {
				$this->do_operation_sequence($_op->false_operation_head);
			}
		}

		return $_op->next;
	}

	//!Checks if a value exists in the local scope.
	private function value_exists($_val) {
		return array_key_exists($_val, $this->values);
	}

	//!Returns a value in the $values array from the local scope.
	private function get_value($_val) {
		if(!array_key_exists($_val, $this->values)) {
			$this->fail("Value '".$_val."' does not exist");
		}
		return $this->values[$_val];
	}

	//!Given an expression object, returns its value, either constant or by resolving a given path to a scalar value.
	private function expression_value(Expression $_e) {

		if($_e->is_const()) {
			return $_e->value;
		}
		else {
			return $this->resolve_scalar($_e->value);
		}
	}

	//TODO: Oh, yeah, we want to.
	//TODO: In fact, this should be a "pointer to function" 
	//whenever we call it... 
	//!Just in case we want to output to somewhere else in the future.
	private function to_output($_str) {

		if(!is_scalar($_str)) {
			$this->fail($_str.' resolves to non-scalar and cannot be output');
		}

		$this->buffer.=$_str;
	}

	/* private function output_std($str)
	private function output_str($str)
	private function output_file($str)
	*/

	//!Resolves a value by following a path from the $values array.
	private function resolve_scalar($_expression) {

		//A little hack: we normalize the expression so the first elements it a 
		//dot, as it will point to $this->values, which is an array.
		$_expression=".".$_expression;
		$index=0;

		$reaches_indirection=function($cur) {
			return Tokenizer::RESERVED_ARRAY_INDIRECTION == $cur ||
				Tokenizer::RESERVED_PROPERTY_INDIRECTION == $cur ||
				Tokenizer::RESERVED_METHOD_INDIRECTION == $cur;
		};

		//Extracts the next indirection and key.
		$extract_chunk=function($_expression, &$index) use ($reaches_indirection) {

			$res=new Expression_chunk;
			$cur=null;
			while($index < strlen($_expression)) {
				$cur=$_expression[$index];
				if(strlen($res->key) > 1 && $reaches_indirection($cur)) {
					break;
				}

				$res->key.=$cur;
				++$index;
			}

			$res->indirection=$res->key[0];
			$res->key=substr($res->key, 1);
			return $res;
		};

		$chunk=null;
		$ref=&$this->values;

		do {
			$chunk=$extract_chunk($_expression, $index);
			$ref=&$this->solve_indirection($chunk->indirection, $chunk->key, $ref);
		}while($index < strlen($_expression));

		return $ref;
	}

	private function &solve_indirection($_indirection, $_key, &$_ref) {

		switch($_indirection) {
			case Tokenizer::RESERVED_ARRAY_INDIRECTION:
				if(is_numeric($_key)) {
					if($_key < 0 || $_key >= count($_ref)) {
						$this->fail("Deep array index '".$_key."' is invalid in view");
					}
					$_key=(int)$_key;
				}
				else {
					if(!array_key_exists($_key, $_ref)) {
						$this->fail("Deep array key '".$_key."' does not exist in view");
					}
				}
				return $_ref[$_key];
			break;
			case Tokenizer::RESERVED_PROPERTY_INDIRECTION:
				if(!is_object($_ref)) {
					$this->fail("Property '".$_key."' does not exist in object view, which is not class");
				}
				else if(!property_exists($_ref, $_key)) {
					$this->fail("Property '".$_key."' does not exist in object view");
				}
				return $_ref->$_key; 
			break;
			case Tokenizer::RESERVED_METHOD_INDIRECTION:
				if(!is_object($_ref)) {
					$this->fail("Method '".$_key."' does not exist in object view, which is not class");
				}
				else if(!method_exists($_ref, $_key)) {
					$this->fail("Method '".$_key."' does not exist in object view");
				}
				$res=call_user_func([$_ref, $_key]);
				return $res;
			break;
			default:
				$this->fail("Unknown and unreachable indirection '".$_indirection."'"); break;
		}
	}

	//!Throws an exception.
	private function fail($_msg) {
		throw new View_exception($_msg);
	}
}
