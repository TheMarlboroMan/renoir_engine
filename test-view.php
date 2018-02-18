<?php
use Renoir_engine\View;
require("autoload.php");

class Inner {
	public $val=null;
	public function call_me() {return "CALLED!";}
	public function __construct($v) {
		$this->val=$v;
	}
}

class Thing {

	public $cosa="hola";
	public $arr=['key' => 'value', 'objs' => []];
	private $var="private";
	public function get_var() {return $this->var;}
	public function __construct() {
		$this->arr['objs'][]=new Inner("first");
		$this->arr['objs'][]=new Inner("second");
	}
}

$v=new Renoir_engine\View\View();
$v->set_template_file("template-view.rtp")
	->set('strvar1', 'Hola')
	->set('strvar2', 'Adios')
	->set('arrvar1', ['k1' => 'Key 1', 's_1' => ['k2' => 'Key2', 'thing' => new Thing()]])
	->render();
