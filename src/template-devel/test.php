<?php
require("expression.php");
require("tokenizer.php");
require("token.php");
require("reader.php");
require("exception.php");
require("parser.php");
require("view.php");
require("operations.php");
require("use-case-testsuite.php");

set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
			if($err_severity!==E_DEPRECATED) 
				debug_print_backtrace();
				throw new Exception("Api error handler (".$err_severity."): ".$err_msg.' ['.$err_file.':'.$err_line.'] ', $err_severity);
		});

/*
{{put value}}

{{if expression predicate expression then}}
{{endif}

{{if expression predicate [eq|geqt|leqt|gt|lt] expression then}}
{{else}}
{{endif}

{{foreach expression as value}}
{{endforeach}}
*/

//execute_testsuite();

try{
	$test=<<<R
<h1>Hello!! {{   put   myvar}} </h1>
<p>This is something</p>
{{ foreach myarray as value }}
<p>We do shit to {{put value}}</p>
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
<p>Finally {{put myarray.2}} and {{put thing.key>val}}</p>

R;

	class Thing {
		public $val;
		public function __construct($v) {$this->val=$v;}
	}

	$v=new View();
	//TODO: Test objects and paths and shit!!!.
	echo $v->set_template_string($test)->set('thing', ['key' => new Thing('cosa')])->set('myvar', 'World!')->set('myarray', ['each', 'and', 'everyone'])->render();
}
catch(Exception $e) {
	echo 'Error: '.$e->getMessage();
}
