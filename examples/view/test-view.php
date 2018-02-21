<?php
require("../../src/view/autoload.php");
//require("use-case-testsuite.php");

set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
	if($err_severity!==E_DEPRECATED) {
		debug_print_backtrace();
		throw new Exception("Api error handler (".$err_severity."): ".$err_msg.' ['.$err_file.':'.$err_line.'] ', $err_severity);
	}
});

//execute_testsuite();
use Renoir_engine\View\View;

try{

	class Thing {
		public $val;
		private $data=[];
		public function __construct($v, array $d) {
			$this->val=$v;
			$this->data=$d;
		}
		public function get_data() {
			return $this->data;
		}
	}

	$things=[new Thing("a value", [1, 2, 3]), new Thing("another value",[])];

	$v=new View();
	echo $v->set_template_file("base-template.tpl")
		->set('header', 'My header!')
		->set('things', $things)
		->set('words', ['each', 'and', 'every', 'word'])
		->set('number', 199)
		->set('templatefilenamesymbols', 'imported-template-symbols.tpl')
		->render();
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage();
}
