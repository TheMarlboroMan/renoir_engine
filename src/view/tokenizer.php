<?php
namespace Renoir_engine\View;

//!The Tokenizer consumes source code and produces an array of tokens.

//!The tokens produced may or may not make sintactic sense: that is up to the
//!parser. This Tokenizer works in two mutually exclusive modes: it either 
//!interprets code or makes a passthrough of the source. The syntax is simple:
//
//!This text will be passed as it is. Double brackets enter and exit from 
//!code mode. Code mode works with a few easy constructs:
//!
//!"put" outputs a list of constant values or solvable
//!tokens in the View.
//!{{put ["hello"]}} or {{put [path.to.solvable.token]}}
//!
//!"foreach" iterates an array present in the View:
//!{{foreach myarray as localkey}}
//!The variable localkey represents the array value: {{put [localkey]}}
//!{{endforeach}}
//!
//!"if", "then" and "else" control conditional flow. There is no "else if"
//!construct. Comparison operators are ==, !=, >=, <=, < and >. Values on both
//!sides can be constants (integers, strings and null) or solvable by the View.
//!{{if myvar > 3 then put ["My var is greater than 3"] else put ["My var is not greater than 3"] endif}}

//TODO: Test nested foreach and if.

//TODO: Add support for pipes with put. 

//TODO: We need a few functions, like size, that acts on strlen or count depending on the value.
//But these open up a can of worms...

//TODO: Use this alternative put syntax, with the brackets. Makes everything easy.
//{{if var != null then
//	foreach var as item 
//		put [var.quantity] 
//		}} of {{
//		put [var.name | ucfirst, "(", var.price, "euro)"]
//	endforeach
//endif}}

//TODO: Pipes should be writable by end users and added to Views. 

//TODO: Perhaps we could use the import keyword for both inserting
//and feeding templates like {{import name from [file|memory] [var as local]}} or [*] or [null]
//and we would use up less names. I like that idea a lot.

//TODO: Not tokenizer, but maybe we can somewhat serialize the operations in a text string so 
//they can be saved and reused???? That would constitute a program XD!.

class Tokenizer {

	//Public interface

	//!Returns a Tokenizer whose reader is fed from a file.
	public static function from_file($_path) {
		$reader=Reader::from_file($_path);
		return new Tokenizer($reader);
	}

	//!Returns a Tokenizer whose reader is fed from a string.
	public static function from_string($_string) {
		$reader=Reader::from_string($_string);
		return new Tokenizer($reader);
	}

	//!Returns an array of tokens to be interpreted by the Parser.
	public function tokenize() {

		$tokens=[];
		while(!$this->reader->is_eof()) {

			$chunk=$this->next();

			//Here is the crux, our chunk is either
			// 1 - just {{
			// 2 - a literal with {{
			// 3 - a literal that ends with eof.  (may just be a final carriage return)
			// 	- All of these, we know we are in MODE_LITERAL.
			// 4 - just }}
			// 5 - interpreter with }}
			// 6 - interpreter that ends with eof.
			//	- For all of these, we know we are in MODE_INTERPRETER.
			// We need to process the chunk, to see if we will change modes
			// after a token is created.

			$new_mode=$this->extract_mode_delimiter($chunk);

			//This takes care of cases 1, 2, 4 and 5.
			if($new_mode!=self::MODE_UNCHANGED) {
				$chunk=substr($chunk, 0, -2);
			}

			//This discards the chunk of cases 2 and 5, as it is now empty.
			if(strlen($chunk)) {
				$tokens[]=$this->generate_token($chunk);
			}

			if($new_mode!=self::MODE_UNCHANGED) {
				$this->parse_mode=$new_mode;
			}
		}

		if(!count($tokens)) {
			$this->fail("No tokens resulted of input");
		}

		return $tokens;
	}

	//Internals...

	//!Indicates that the parser must not change interpretation mode.
	const MODE_UNCHANGED=0;
	//!Indicates that the parser must change interpretation mode to "passthrough".
	const MODE_LITERAL=1;
	//!Indicates that the parser must change interpretation mode to "code".
	const MODE_INTERPRETER=2;

	//!Specifies the type of string quotation used.
	const QUOTE_STRING='"';

	//!Specifies the tags to open the interpreter.
	const RESERVED_OPEN_INTERPRETER='{{';
	//!Specifies the tags to close the interpreter.
	const RESERVED_CLOSE_INTERPRETER='}}';
	//!Specifies the keyword for put operation.
	const RESERVED_PUT='put';
	//!Specifies the keyword for foreach operation.
	const RESERVED_FOREACH='foreach';
	//!Specifies the keyword for endforeach.
	const RESERVED_ENDFOREACH='endforeach';
	//!Specifies the keyword for as.
	const RESERVED_AS='as';
	//!Specifies the keyword for conditional branching.
	const RESERVED_IF='if';
	//!Specifies the keyword for conditional branching yield.
	const RESERVED_THEN='then';
	//!Specifies the keyword for conditional branching when the test is negative.
	const RESERVED_ELSE='else';
	//!Specifies the keyword for ending conditional branching.
	const RESERVED_ENDIF='endif';
	//!Specifies the keyword for equal comparison.
	const RESERVED_PREDICATE_EQUALS='==';
	//!Specifies the keyword for non-equal comparison.
	const RESERVED_PREDICATE_NOT_EQUALS='!=';
	//!Specifies the keyword for equal or greater than comparison.
	const RESERVED_PREDICATE_GREATER_OR_EQUAL_THAN='>=';
	//!Specifies the keyword for equal or lesser than comparison.
	const RESERVED_PREDICATE_LESSER_OR_EQUAL_THAN='<=';
	//!Specifies the keyword for greater than comparison.
	const RESERVED_PREDICATE_GREATER_THAN='>';
	//!Specifies the keyword for lesser than comparison.
	const RESERVED_PREDICATE_LESSER_THAN='<';
	//!Specifies the keyword for null values.
	const RESERVED_NULL='null';

	private $reader;
	private $parse_mode=self::MODE_LITERAL; //We assume we start out passing through what we read.

	private function __construct(Reader $_r) {
		$this->reader=$_r;
	}

	private function extract_mode_delimiter($chunk) {

		if(!$chunk) {
			$this->fail('Chunk cannot be empty for extract_mode_delimiter');
		}

		if(self::RESERVED_OPEN_INTERPRETER == substr($chunk, -2)) return self::MODE_INTERPRETER;
		else if (self::RESERVED_CLOSE_INTERPRETER == substr($chunk, -2)) return self::MODE_LITERAL;
		else return self::MODE_UNCHANGED;
	}

	//Reads the next chunk and returns it. What a chunk is
	//depends on the tokenizing mode.
	private function next() {

		switch($this->parse_mode) {
			case self::MODE_LITERAL:
				return $this->read_passthrough(); break;
			
			case self::MODE_INTERPRETER:
				return $this->read_interpreter(); break;
			break;
		}
	
		$this->fail("next reached unreachable code");
	}

	//Reads until EOF or the begin-code delimiter are found.
	private function read_passthrough() {

		$buffer='';
		do {
			$buffer.=$this->reader->next();
		}while(!$this->reader->is_eof() && substr($buffer, -2)!=self::RESERVED_OPEN_INTERPRETER);
		return $buffer;

	}

	//Reads the next token, which is either what happens before EOF, a
	//close delimiter or a whitespace...
	private function read_interpreter() {

		$this->skip_whitespace();
		$buffer='';
		$cur='';
		do {
			$cur=$this->reader->next();

			if(!ctype_space($cur)) {
				$buffer.=$cur;
			}
		}while(!$this->reader->is_eof() 
			&& substr($buffer, -2)!=self::RESERVED_CLOSE_INTERPRETER && 
			!ctype_space($cur));

		$this->skip_whitespace();

		return $buffer;
	}

	private function skip_whitespace() {

		while(!$this->reader->is_eof() && ctype_space($this->reader->get())) {
			$this->reader->next();
		}
	}

	private function generate_token($chunk) {

		switch($this->parse_mode) {
			case self::MODE_LITERAL:
				return new Token_passthrough($chunk); break;
			case self::MODE_INTERPRETER: 
				return $this->generate_interpreter_token($chunk); break;
		}

		$this->fail("generate_token reached unreachable code");
	}

	private function generate_interpreter_token($chunk) {

		switch($chunk) {
			case self::RESERVED_PUT:
				return new Token_put; break;
			case self::RESERVED_FOREACH:
				return new Token_foreach; break;
			case self::RESERVED_ENDFOREACH:
				return new Token_endforeach; break;
			case self::RESERVED_AS:
				return new Token_as; break;
			case self::RESERVED_IF:
				return new Token_if; break;
			case self::RESERVED_THEN:
				return new Token_then; break;
			case self::RESERVED_ELSE:
				return new Token_else; break;
			case self::RESERVED_ENDIF:
				return new Token_endif; break;
			case self::RESERVED_PREDICATE_EQUALS:
				return new Token_condition(Token_condition::EQUALS); break;
			case self::RESERVED_PREDICATE_NOT_EQUALS:
				return new Token_condition(Token_condition::NOT_EQUALS); break;
			case self::RESERVED_PREDICATE_GREATER_OR_EQUAL_THAN:
				return new Token_condition(Token_condition::GREATER_OR_EQUAL_THAN); break;
			case self::RESERVED_PREDICATE_LESSER_OR_EQUAL_THAN:
				return new Token_condition(Token_condition::LESSER_OR_EQUAL_THAN); break;
			case self::RESERVED_PREDICATE_GREATER_THAN:
				return new Token_condition(Token_condition::GREATER_THAN); break;
			case self::RESERVED_PREDICATE_LESSER_THAN:
				return new Token_condition(Token_condition::LESSER_OR_EQUAL_THAN); break;
			case self::RESERVED_NULL:
				return new Token_expression(null, Token_expression::CONSTANT); break;
			default:

				if(is_numeric($chunk)) {
					if(is_int($chunk)) {
						return new Token_expression($chunk, Token_expression::CONSTANT);
					}
					//Couldn't care less about non integer numbers :P, even if there's nothing to gain with this.
					else {
						$this->fail('only integer numeric constants are supported');
					}
				}
				else if($this->is_quoted_string($chunk)) {
					return new Token_expression(substr($chunk, 1, -1), Token_expression::CONSTANT);
				}
				else {
					return new Token_expression($chunk, Token_expression::SOLVABLE);
				}
			break;
		}

		$this->fail("generate_interpreter_token reached unreachable code");
	}

	private function is_quoted_string($str) {
		return strlen($str) > 2 && $str[0]==self::QUOTE_STRING && substr($str, -1)==self::QUOTE_STRING;
	}

	private function fail($msg) {
		throw new View_exception("Tokenizer error: ".$msg);
	}
}
