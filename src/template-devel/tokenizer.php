<?php

//This just tokenizes: we don't care if the result makes sense or not because
//that's the job of the parser. Problem here: we need to keep track of the
//mode we are in (true parser or just passing the things we read through). 
//To solve that we have two tokenizer modes: literal (passthrough) and 
//interpreter (produces logic tokens). The modes are mutually exclusive.

class Tokenizer {

	//Public interface

	public static function from_file($_path) {
		$reader=Reader::from_file($_path);
		return new Tokenizer($reader);
	}

	public static function from_string($_string) {
		$reader=Reader::from_string($_string);
		return new Tokenizer($reader);
	}

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

	const MODE_UNCHANGED=0;
	const MODE_LITERAL=1;
	const MODE_INTERPRETER=2;

	const RESERVED_OPEN_INTERPRETER='{{';
	const RESERVED_CLOSE_INTERPRETER='}}';
	const RESERVED_PUT='put';
	const RESERVED_FOREACH='foreach';
	const RESERVED_ENDFOREACH='endforeach';
	const RESERVED_AS='as';

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
		while(ctype_space($this->reader->get())) {
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
				return new Token_endforach; break;
			case self::RESERVED_AS:
				return new Token_as; break;
			default:
				return new Token_expression($chunk); break;
		}

		$this->fail("generate_interpreter_token reached unreachable code");
	}

	private function fail($msg) {
		throw new LangException("Tokenizer error: ".$msg);
	}
}
