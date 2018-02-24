<?php
namespace Renoir_engine\ORM;

class Query_parameter {
	public $name;
	public $value;
	public function __construct($_n, $_v) {
		$this->name=$_n;
		$this->value=$_v;
	}
}

class Query_definition {

	const ORDER_ASC=0;
	const ORDER_DESC=1;

	private $fields="*";
	private $where="TRUE";
	private $parameters=[];
	private $order_fields=null;
	private $order_type=self::ORDER_ASC;
	private $limit=null;
	private $offset=null;

	public function __construct() {

	}

	public function set_order($_fields, $_type) {

		switch($_type) {
			case self::ORDER_ASC: 
			case self::ORDER_DESC: 
				$this->order_type=$_type; break;
			default: throw new ORM_exception("Unknown order type");
		}

		$this->order_fields=$_fields;
		return $this;
	}

	public function set_fields($_f) {
		$this->fields=$_f;
		return $this;
	}

	public function set_where($_w) {
		$this->where=$_w;
		return $this;
	}

	public function set_limit($_v) {
		if(!is_numeric($_v)) {
			throw new ORM_exception("set_limit must be called with a numeric parameter, ".$_v." given");
		}
		$this->limit=$_v;
		return $this;
	}

	public function set_offset($_v) {
		if(!is_numeric($_v)) {
			throw new ORM_exception("set_offset must be called with a numeric parameter, ".$_v." given");
		}
		$this->offset=$_v;
		return $this;
	}

	public function clear_offset() {
		$this->offset=null;
		return $this;
	}

	//!$_name will not include a colon.
	public function set_parameter($_name, $_value) {
		$this->parameters[]=new Query_parameter($_name, $_value);
		return $this;
	}

	//!This function must be called by Database IO.
	public function bind_parameters(\PDOStatement $_s) {
		foreach($this->parameters as $k => $v) {
			$_s->bindValue(":".$v->name, $v->value);
		}
	}

	//!This function must be called by Database IO.
	public function build_query_string($_table, $_id_field)
	{
		$order_fields=null===$this->order_fields ? $_id_field : $this->order_fields;
		switch($this->order_type) {
			case self::ORDER_ASC: $order_type="ASC"; break;
			case self::ORDER_DESC: $order_type="DESC"; break;
		}

		$limit=null===$this->limit ? null : "LIMIT ".$this->limit;
		$offset=null===$this->offset ? null : "OFFSET ".$this->offset;

		return "SELECT ".$this->fields." FROM ".$_table." WHERE ".$this->where." ORDER BY ".$order_fields." ".$order_type." ".$limit." ".$offset;
	}

}
