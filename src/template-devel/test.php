<?php
require("tokenizer.php");
require("token.php");
require("reader.php");
require("exception.php");
require("parser.php");

/* Okay, good so far, next we are going to try to tokenize a few basic things
we want in the code... i'd like to start with putting stuff out... Now, If
I parse "expression" as EVERYTHING that is not a keyword, by default
expression would... print its result. Maybe I would do better with a reserved 
word like "put".. So lets do

{{put value}}

{{
for expression as value 
}}
	<p>This is {{value}}</p>
{{
endfor
}}

That teaches me that every single token must be separated by some whitespace.
Good... I read until I reach a whitespace. Then skip the rest of whitespace.

{{if value is shit then value else "shit" endif}}


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

/*
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
*/

try {
/*	$test=<<<R
<h1>Hello!!</h1>
{{  for myvar as var put   var endfor}}
<p>This is the end</p>
R;
*/

	$test=<<<R
<h1>Hello!!</h1>
{{   put   myvar}}
<p>This is the end</p>
R;

	$t=Tokenizer::from_string($test);

	$p=new Parser;
	$p->set_var('myvar', 'this is a variable!!!');
	$p->parse($t->tokenize());
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage();
}
