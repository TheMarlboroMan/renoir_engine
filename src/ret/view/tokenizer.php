<?php
namespace RET\View;

//Dirty trick... autoload won't pick the tokens, as they are all in the same file.
require_once("def_tokens.php"); 

//!The Tokenizer consumes source code and produces an array of tokens.

//!The tokens produced may or may not make sintactic sense: that is up to the
//!parser. This Tokenizer works in two mutually exclusive modes: it either 
//!interprets code or makes a passthrough of the source. The syntax is simple:
//!
//!Text will be passed as it is. Double brackets enter and exit from 
//!code mode. Inside code mode there are only a few data types:
//!"constant strings", 11 (constant numbers) and @solvable.expression 
//!(solvable expressions). Solvable expressions are paths that will be 
//!followed in the "values" array of the view. There are four important
//!symbols: @ begins a solvable path, . indicates array indirection, 
//!> indicates object property indirection and ) indicates object method
//!calls (no parameters are available. So, the path @myarray.1.cosa>val)call
//!will be resolved as the first item in the myarray array, then the "cosa"
//!key of an array, the val property of the object located in such a key
//!and the "call" method from the object present at "val".
//!
//!Code mode works with a few easy constructs (which can be nested)
//!and keywords. Expressions must not be named after keywords!. The list of
//!keywords has been kept to a minimum.
//!
//!"put" outputs a list of constant values or solvable tokens in the View.
//!Elements in the list are comma separated. Constant values can be null,
//!strings or integers. Srings are delimited by double quotes.
//!{{put ["hello"]}} or {{put [@path.to.solvable.token, "ok", 33]}}
//!
//!"foreach" iterates an array present in the View. The left part (the iterable)
//!must always be a solvable symbol. The right part (the local key) can either
//!be a constant expression or a solvable.
//!{{foreach @myarray as "localkey"}}
//!The variable @localkey represents the array value: {{put [@localkey]}}
//!{{endforeach}}
//!
//!"if", "then" and "else" control conditional flow. There is no "else if"
//!construct. Comparison operators are ==, !=, >=, <=, < and >. Values on both
//!sides can be constants (integers, strings and null) or solvable by the View.
//!{{if @myvar > 3 then put ["My var is greater than 3"] else put ["My var is not greater than 3"] endif}}
//
//!"import" is used to insert another template. Its scope can be defined with
//!a list, be left blank of fully inherited with "*". It makes no recursion 
//!checks, so you can easily run out of memory :D. Notice that if you want a 
//!filename, you want it to be a constant expression, hence the string.
//!Templates cannot be imported from a string (as in I have a string that I
//!want to use as the body of the template, like $body='put["text"]';)
//!... I could make them do it, but I don't really see a use for it.
//!{{import file "templatename" [@var as "local", @var2 as @somethingsolvable]}}
//!{{import file @somethingsolvable []}}
//!{{import file @somethingsolvable [*]}}

//TODO: Add support for pipes with put. If need be, add the pipe to the list of allowed characters after a @path expression.
//TODO: Pipes should be writable by end users and added to Views. 
//TODO: Add size as a pipe, so we can myarray | size > 0.
//TODO: Fix whitescape space after }} like {{put ["hello"]}} world: it should output it.

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

	const RESERVED_QUOTE_STRING='"';		//!< Specifies the type of string quotation used.
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
	const RESERVED_SOLVABLE='@';	//!< Specifies the keyword for solvable paths.
	const RESERVED_UNDERSCORE='_';	//!< Specifies the underscore symbol.
	const RESERVED_ARRAY_INDIRECTION='.';
	const RESERVED_PROPERTY_INDIRECTION='>';
	const RESERVED_METHOD_INDIRECTION=')';

	private $reader;		//!< A Reader object.
	private $parse_mode=self::MODE_LITERAL; //!< Parser mode, either literal or interpreter. We assume we start out passing through what we read.

	//!Creates an object from a reader.
	private function __construct(Reader $_r) {
		$this->reader=$_r;
	}

	//!Returns the parser mode we must use after $chunk is processed. It might return "no changes".
	private function extract_mode_delimiter($_chunk) {

		if(!$_chunk) {
			$this->fail('Chunk cannot be empty for extract_mode_delimiter');
		}

		if(self::RESERVED_OPEN_INTERPRETER == substr($_chunk, -2)) return self::MODE_INTERPRETER;
		else if (self::RESERVED_CLOSE_INTERPRETER == substr($_chunk, -2)) return self::MODE_LITERAL;
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

		$cur=$this->reader->get();

		if(self::RESERVED_QUOTE_STRING==$cur) {
			return $this->read_string_literal();
		}
		else if(self::RESERVED_SOLVABLE==$cur) {
			return $this->read_path_literal();
		}
		else {
			$buffer='';

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
	private function is_beginning_of_non_whitespace_token($_buffer) {
		return strlen($_buffer)==1 && $this->is_non_whitespace_token($_buffer);
	}

	//!Checks if the parameter is any of the special tokens that exist without surrounding whitespace.

	//!This function mostly accomodates syntactic ease like put["hello"] instead of {{ put ["hello"] }}
	private function is_non_whitespace_token($_str) {
		return $_str==self::RESERVED_COMMA ||
			$_str==self::RESERVED_OPEN_LIST ||
			$_str==self::RESERVED_CLOSE_LIST;
	}

	//!Checks if the character is a letter, underscore, number or any of the path solving symbols.
	private function is_valid_path_character($_chr) {
		return ctype_alnum($_chr) || 
			self::RESERVED_UNDERSCORE == $_chr ||
			self::RESERVED_ARRAY_INDIRECTION == $_chr ||
			self::RESERVED_PROPERTY_INDIRECTION == $_chr ||
			self::RESERVED_METHOD_INDIRECTION == $_chr;
	}

	//!Reads a string literal from " to ".
	private function read_string_literal() {
		$res="";
		do{
			if($this->reader->is_eof()) {
				$this->fail("unterminated string literal '".$res."'");
			}

			$res.=$this->reader->next();
		}while(self::RESERVED_QUOTE_STRING!=$this->reader->get());
		$res.=$this->reader->next(); //End quote...
		return $res;
	}

	//!Reads from @ to whitespace, comma or close list.
	private function read_path_literal() {
		$res="";
		do{
			if($this->reader->is_eof()) {
				$this->fail("unterminated path literal '".$res."'");
			}

			$res.=$this->reader->next();
		}while($this->is_valid_path_character($this->reader->get()));
		return $res;
	}

	//!Skips all whitespace from the reader, used only in interpreter mode.
	private function skip_whitespace() {
		while(!$this->reader->is_eof() && ctype_space($this->reader->get())) {
			$this->reader->next();
		}
	}

	//!Returns a token from the $chunk: entry point for specific functions.
	private function generate_token($_chunk) {

		switch($this->parse_mode) {
			case self::MODE_LITERAL:
				return new Token_passthrough($_chunk); break;
			case self::MODE_INTERPRETER: 
				return $this->generate_interpreter_token($_chunk); break;
		}

		$this->fail("generate_token reached unreachable code");
	}

	//!Returns an interpreter token.
	private function generate_interpreter_token($_chunk) {

		//TODO... okay, this is getting ridiculous... Can we get an array and do lookup????
		switch($_chunk) {
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
				if(is_numeric($_chunk)) {
					//TODO: Kind of redundant, right??
					if(is_int((int)$_chunk)) {
						return new Token_expression($_chunk, Token_expression::CONSTANT);
					}
					//Couldn't care less about non integer numbers :P, even if there's nothing to gain with this.
					else {
						$this->fail('only integer numeric constants are supported');
					}
				}
				else if($this->is_quoted_string($_chunk)) {
					//Remove quotes from literals.
					return new Token_expression(substr($_chunk, 1, -1), Token_expression::CONSTANT);
				}
				else if($this->is_path_string($_chunk)) {
					//Remove solvable marker.
					return new Token_expression(substr($_chunk, 1), Token_expression::SOLVABLE);
				}
				else {
					$this->fail("unknown expression ".$_chunk);
				}
			break;
		}

		$this->fail("generate_interpreter_token reached unreachable code");
	}

	//!Returns true is $str begins with @.
	private function is_path_string($_str) {
		return strlen($_str) > 2 && $_str[0]==self::RESERVED_SOLVABLE;
	}

	//!Returns true is $str is between double quotes.
	private function is_quoted_string($_str) {
		return strlen($_str) > 2 && $_str[0]==self::RESERVED_QUOTE_STRING && substr($_str, -1)==self::RESERVED_QUOTE_STRING;
	}

	//!Throws an exception.
	private function fail($_msg) {
		throw new View_exception("Tokenizer error: ".$_msg);
	}
}
