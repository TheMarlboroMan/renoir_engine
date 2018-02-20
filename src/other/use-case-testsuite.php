<?php
class UseCase {

	public $name=null;
	public $test=null;
	public $proof=null;

	public function __construct($_n, $_c, $_p) {
		$this->name=$_n;
		$this->test=$_c;
		$this->proof=$_p;
	}

	public static function generate_result_expectation($str) {

		$result=[];

		for($i=0; $i < strlen($str); $i++) {
			switch($str[$i]) {
				case 'p': $result[]=Token_passthrough::class; break;
				case 'P': $result[]=Token_put::class; break;
				case 'E': $result[]=Token_expression::class; break;
				case 'F': $result[]=Token_foreach::class; break;
				case 'f': $result[]=Token_endforeach::class; break;
				case 'A': $result[]=Token_as::class; break;
			}
		}
		return $result;
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

function execute_testsuite() {

	$cases=[
		new UseCase("Full", "<p>First</p>{{put second}}<p>Third</p>{{put fourth}<p>Fifth</p>", 
			UseCase::generate_result_expectation("pPEpPEp")),
		new UseCase("Begin code", "{{put second}}<p>Third</p>{{put fourth}<p>Fifth</p>", 
			UseCase::generate_result_expectation("PEpPEp")),
		new UseCase("Begin end code", "{{put second}}<p>Third</p>{{put fourth}", //This works, but the last } will be tokenized to shit!.
			UseCase::generate_result_expectation("PEpPEp")),
		new UseCase("Only whitespace", "    ", UseCase::generate_result_expectation("p")),
		new UseCase("Code and EOF end", "{{put aham", UseCase::generate_result_expectation("PE")),
		new UseCase("Just vomit", "Lalalala", UseCase::generate_result_expectation("p")),
		new UseCase("Useless code open", "Code{{", UseCase::generate_result_expectation("p")),
		new UseCase("Last thing is close ", "Code{{put hello}}", UseCase::generate_result_expectation("pPE")),
		new UseCase("Simple put ", "{{put var}}", UseCase::generate_result_expectation("PE")),
		new UseCase("Simple foreach ", "{{foreach arr as val put val endforeach}}", UseCase::generate_result_expectation("FEAEPEf")),
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
}
