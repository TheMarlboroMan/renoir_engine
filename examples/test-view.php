<?php
require("../src/view/autoload.php");
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
	$template_str=<<<R
<h1>{{put[header]}}</h1>
<p>I have a few things:</p>
<ul>
{{foreach things as item}}
	<li>
		{{put [item>val, " has a few things of its own:"]}}
		<ul>
			{{foreach item*get_data as inner}}
				<li>{{put [inner]}}</li>
			{{endforeach}}
		</ul>
	</li>
{{endforeach}}
<ul>
<p>I also have some words!!!</p>
{{foreach words as word}}
<p><b>{{put [word]}}</b></p>
{{endforeach}}
<p>Finally I also have a number, {{put [number]}} which: 
{{if number > 10 then 
	put ["is larger than 10"]
	if number > 100 then
		put [" and is also larger than 100"]
		if number > 1000 then 
			put [" and is larger than 1000"]
		else
			put [" but is not larger than 1000"]
		endif
	else
		put [" but is not larger than 100"]
	endif
else
	put ["is not larger than 10 "]
endif
}}
R;

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
	echo $v->set_template_string($template_str)
		->set('header', 'My header!')
		->set('things', $things)
		->set('words', ['each', 'and', 'every', 'word'])
		->set('number', 199)
		->render();
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage();
}
