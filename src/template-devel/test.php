<?php
require("tokenizer.php");
require("token.php");
require("reader.php");
require("exception.php");

/* Okay, good so far, next we are going to try to tokenize a few basic things
we want in the code... i'd like to start with 

for expression as value endfor

expression would be EVERYTHING that is not a keyword. Yeah, that cool.
*/

class UseCase {

	const V='Token_passthrough';
	const C='Token_code';

	public $name=null;
	public $test=null;
	public $proof=null;

	public function __construct($_n, $_c, $_p) {
		$this->name=$_n;
		$this->test=$_c;
		$this->proof=$_p;
	}

	public function prove($tokens) {

		foreach($tokens as $ktok => $tok) {
			if(get_class($tok) != $this->proof[$ktok]) {
				echo "error in case ".$this->name.": ".get_class($tok).' should be '.$this->proof[$ktok]."\n";
				return;
			}
		}
	}
};


$cases=[
	new UseCase("Full", 
		"<p>First</p>{{second}}<p>Third</p>{{fourth}<p>Fifth</p>", 
		[UseCase::V, UseCase::C, UseCase::V, UseCase::C, UseCase::V]),
	new UseCase("Begin code", 
		"{{second}}<p>Third</p>{{fourth}<p>Fifth</p>", 
		[UseCase::C, UseCase::V, UseCase::C, UseCase::V]),
	new UseCase("Begin end code", 
		"{{second}}<p>Third</p>{{fourth}", //This works, but the last } will be tokenized to shit!.
		[UseCase::C, UseCase::V, UseCase::C]),
	new UseCase("Only whitespace", 
		"    ", 
		[UseCase::V]),
	new UseCase("Code and EOF end", 
		"{{aham", 
		[UseCase::C]),
	new UseCase("Just vomit", 
		"Lalalala", 
		[UseCase::V]),
	new UseCase("Useless code open", 
		"Code{{", 
		[UseCase::V]),
];

try {
	foreach($cases as $key => $value) {
		$t=Tokenizer::from_string($value->test);
		$tokens=$t->tokenize();
		$value->prove($tokens);
		echo "PASS [OK] : ".$value->name."\n";
	}
	
	echo 'All is good';
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage();
}
