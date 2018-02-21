<?php
namespace Renoir_engine\View;

//!The Tokenizer consumes source code and produces an array of tokens.

//!The tokens produced may or may not make sintactic sense: that is up to the
//!parser. This Tokenizer works in two mutually exclusive modes: it either 
//!interprets code or makes a passthrough of the source. The syntax is simple:
//
//!Text will be passed as it is. Double brackets enter and exit from 
//!code mode. Code mode works with a few easy constructs (which can be nested)
//!and keywords. Expressions must not be named after keywords!. The list of
//!keywords has been kept to a minimum.
//!
//!"put" outputs a list of constant values or solvable tokens in the View.
//!Elements in the list are comma separated. Constant values can be null,
//!strings or integers. Srings are delimited by double quotes.
//!{{put ["hello"]}} or {{put [path.to.solvable.token, "ok", 33]}}
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
//
//!"import" is used to insert another template. Its scope can be defined with
//!a list, be left blank of fully inherited with "*". It makes no recursion 
//!checks, so you can easily run out of memory :D. Notice that if you want a 
//!filename, you want it to be a constant expression, hence the string.
//!Templates cannot be imported from a string... I could make them do it, 
//!but I don't really see a use for it.
//!{{import file "templatename" [var as local, var2 as somethingsolvable]}}
//!{{import file somethingsolvable []}}
//!{{import file somethingsolvable [*]}}

//TODO: We'll likely need to add as symbol for solvable shit, like $.
//It is cool: everything that is not a number, null or a quoted string we can
//assume to be an invalid expression :D.

//TODO: Add support for pipes with put. 
//TODO: Pipes should be writable by end users and added to Views. 

//TODO: We need a few functions, like size, that acts on strlen or count depending on the value.
//But these open up a can of worms... maybe just a sizeof keyword that the parser reads
//greedily and converts into a valid constant expression.

//TODO: Fix whitescape space after }} like {{put ["hello"]}} world.

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

	const MODE_UNCHANGED=0;		//!< Indicates that the parser must not change interpretation mode.
	const MODE_LITERAL=1;		//!< Indicates that the parser must change interpretation mode to "passthrough".
	const MODE_INTERPRETER=2;	//!< Indicates that the parser must change interpretation mode to "code".

	const QUOTE_STRING='"';		//!< Specifies the type of string quotation used.

	const RESERVED_OPEN_INTERPRETER='{{'; 	//!< Specifies the tags to open the interpreter.
	const RESERVED_CLOSE_INTERPRETER='}}';	//!< Specifies the tags to close the interpreter.
	const RESERVED_PUT='put';	//!< Specifies the keyword for put operation.
	const RESERVED_FOREACH='foreach';	//!< Specifies the keyword for foreach operation.
	const RESERVED_ENDFOREACH='endforeach';	//!< Specifies the keyword for endforeach.
	const RESERVED_AS='as';	//!< Specifies the keyword for as.
	const RESERVED_IF='if';	//!< Specifies the keyword for conditional branching.
	const RESERVED_THEN='then';	//!< Specifies the keyword for conditional branching yield.
	const RESERVED_ELSE='else';	//!< Specifies the keyword for conditional branching when the test is negative.
	const RESERVED_ENDIF='endif';	//!< Specifies the keyword for ending conditional branching.
	const RESERVED_PREDICATE_EQUALS='==';	//!< Specifies the keyword for equal comparison.
	const RESERVED_PREDICATE_NOT_EQUALS='!=';	//!< Specifies the keyword for non-equal comparison.
	const RESERVED_PREDICATE_GREATER_OR_EQUAL_THAN='>=';	//!< Specifies the keyword for equal or greater than comparison.
	const RESERVED_PREDICATE_LESSER_OR_EQUAL_THAN='<=';	//!< Specifies the keyword for equal or lesser than comparison.
	const RESERVED_PREDICATE_GREATER_THAN='>';	//!< Specifies the keyword for greater than comparison.
	const RESERVED_PREDICATE_LESSER_THAN='<';	//!< Specifies the keyword for lesser than comparison.
	const RESERVED_NULL='null';	//!< Specifies the keyword for null values.
	const RESERVED_OPEN_LIST='[';	//!< Specifies the character to open lists.
	const RESERVED_CLOSE_LIST=']';	//!< Specifies the character to close lists.
	const RESERVED_COMMA=',';	//!< Specifies a list separator.
	const RESERVED_IMPORT='import';	//!< Specifies the Keyword for template import.
	const RESERVED_FILE='file';	//!< Specifies the keyword for template import from file.
	const RESERVED_ASTERISK='*';	//!< Specifies the keyword for importing all template data.

	private $reader;		//!< A Reader object.
	private $parse_mode=self::MODE_LITERAL; //!< Parser mode, either literal or interpreter. We assume we start out passing through what we read.

	//!Creates an object from a reader.
	private function __construct(Reader $_r) {
		$this->reader=$_r;
	}

	//!Returns the parser mode we must use after $chunk is processed. It might return "no changes".
	private function extract_mode_delimiter($chunk) {

		if(!$chunk) {
			$this->fail('Chunk cannot be empty for extract_mode_delimiter');
		}

		if(self::RESERVED_OPEN_INTERPRETER == substr($chunk, -2)) return self::MODE_INTERPRETER;
		else if (self::RESERVED_CLOSE_INTERPRETER == substr($chunk, -2)) return self::MODE_LITERAL;
		else return self::MODE_UNCHANGED;
	}

	//!Reads the next chunk and returns it. What a chunk is depends on the tokenizing mode.

	//!Passthrough mode reads chunks until EOF or {{ is found. Interpreter mode
	//!reads chunks separated by whitespace.
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

	//!Reads until EOF or the begin-code delimiter are found.
	private function read_passthrough() {

		$buffer='';
		do {
			$buffer.=$this->reader->next();
		}while(!$this->reader->is_eof() && substr($buffer, -2)!=self::RESERVED_OPEN_INTERPRETER);
		return $buffer;

	}

	//!Reads the next token, which happen in a number of ways.

	//!A new token is produced when EOF is reached, or any of the following
	//!are found: whitespace, close code delimiter, comma, open or close
	//!lists. A special case is made when a quoted string is found.
	private function read_interpreter() {

		$this->skip_whitespace();
		if(self::QUOTE_STRING==$this->reader->get()) {
			return $this->read_string_literal();
		}
		else {
			$buffer='';
			$cur='';

			while(!$this->reader->is_eof()){ //While true may cause us to go out of bounds...

				$cur=$this->reader->get();

				//We just found [ ] or ,....
				if($this->is_non_whitespace_token($cur)) {
					//It is the first thing we find: so it constitutes its own token.
					if(!strlen($buffer)) {
						$buffer=$this->reader->next(); //Same as .=$cur
						break;
					}
					//It is not separated by whitespace, but must be its own token!.
					else {
						//By breaking out we force the previous case in the next iteration.
						break;
					}
				}
				//Anything else.
				else {
					if($this->reader->is_eof() 
						|| substr($buffer, -2)==self::RESERVED_CLOSE_INTERPRETER 
						|| ctype_space($cur)) {
						break;
					}
					$buffer.=$this->reader->next();
				}
			};

			$this->skip_whitespace(); //This might take us to EOF.
			return $buffer;
		}
	}

	//!Determines if the string is a special token that needs no whitespace to separate it from the others.

	//!The cases are things like put[hello ,again] where , must be read as its own token.
	//!or put [hello, where [ must be read as its own.
	private function is_beginning_of_non_whitespace_token($buffer) {
		return strlen($buffer)==1 && $this->is_non_whitespace_token($buffer);
	}

	//!Checks if the parameter is any of the special tokens that exist without surrounding whitespace.

	//!This function mostly accomodates syntactic ease like put["hello"] instead of {{ put ["hello"] }}
	private function is_non_whitespace_token($str) {
		return $str==self::RESERVED_COMMA ||
			$str==self::RESERVED_OPEN_LIST ||
			$str==self::RESERVED_CLOSE_LIST;
	}

	//!Reads a string literal from " to ".
	private function read_string_literal() {
		$res="";
		do{
			if($this->reader->is_eof()) {
				$this->fail("unterminated string literal '".$res."'");
			}

			$res.=$this->reader->next();
		}while(self::QUOTE_STRING!=$this->reader->get());
		$res.=$this->reader->next(); //End quote...
		return $res;
	}

	//!Skips all whitespace from the reader, used only in interpreter mode.
	private function skip_whitespace() {
		while(!$this->reader->is_eof() && ctype_space($this->reader->get())) {
			$this->reader->next();
		}
	}

	//!Returns a token from the $chunk: entry point for specific functions.
	private function generate_token($chunk) {

		switch($this->parse_mode) {
			case self::MODE_LITERAL:
				return new Token_passthrough($chunk); break;
			case self::MODE_INTERPRETER: 
				return $this->generate_interpreter_token($chunk); break;
		}

		$this->fail("generate_token reached unreachable code");
	}

	//!Returns an interpreter token.
	private function generate_interpreter_token($chunk) {

			//TODO... okay, this is getting ridiculous... Can we get an array and do lookup????
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
			case self::RESERVED_OPEN_LIST:
				return new Token_open_list; break;
			case self::RESERVED_CLOSE_LIST:
				return new Token_close_list; break;
			case self::RESERVED_COMMA:
				return new Token_comma; break;
			case self::RESERVED_IMPORT:
				return new Token_import; break;
			case self::RESERVED_FILE:
				return new Token_import_file; break;
			case self::RESERVED_ASTERISK:
				return new Token_asterisk; break;
			default:
				if(is_numeric($chunk)) {
					//TODO: Kind of redundant, right??
					if(is_int((int)$chunk)) {
						return new Token_expression($chunk, Token_expression::CONSTANT);
					}
					//Couldn't care less about non integer numbers :P, even if there's nothing to gain with this.
					else {
						$this->fail('only integer numeric constants are supported');
					}
				}
				else if($this->is_quoted_string($chunk)) {
					//Remove quotes from literals.
					return new Token_expression(substr($chunk, 1, -1), Token_expression::CONSTANT);
				}
				else {
					return new Token_expression($chunk, Token_expression::SOLVABLE);
				}
			break;
		}

		$this->fail("generate_interpreter_token reached unreachable code");
	}

	//!Returns true is $str is between double quotes.
	private function is_quoted_string($str) {
		return strlen($str) > 2 && $str[0]==self::QUOTE_STRING && substr($str, -1)==self::QUOTE_STRING;
	}

	//!Throws an exception.
	private function fail($msg) {
		throw new View_exception("Tokenizer error: ".$msg);
	}
}
