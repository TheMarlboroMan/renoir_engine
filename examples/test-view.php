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
	$test=<<<R
<h1>Hello!! {{   put  [myvar]}} </h1>
<p>This is something</p>
{{ foreach myarray as value }}
<p>We do stuff to {{put [value]}}</p>
{{ endforeach }}
<p>And we are done!!</p>
{{if myvar != null then}}
<p>Myvar is not null</p>
{{endif

if myvar == "World!" then}}
<p>My var is world, actually</p>
{{else}}
<p>My var is not world</p>
{{endif}}
<p>Finally {{put [myarray.2]}} and {{put [thing.key>val]}}</p>
R;

$test='HELLO {{put[myvar,10 , "const[]{{}}lolol" ]}} kTHNXBYE!';

	class Thing {
		public $val;
		public function __construct($v) {$this->val=$v;}
	}

	$v=new View();
	echo $v->set_template_string($test)->set('thing', ['key' => new Thing('cosa')])->set('myvar', 'World!')->set('myarray', ['each', 'and', 'everyone'])->render();
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage();
}
