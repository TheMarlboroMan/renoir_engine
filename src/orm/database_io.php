<?php
namespace Renoir_engine\ORM;

//!Performs all database input and output operations through Database_entity objects.
class Database_IO {

	const DEF_ID_PARAM=':id';	//!< Defines the default variable name for binding the id.

	private $conn=null;
	private $strict_id_find=true;

	//TODO: Comment.
	public function __construct(Database_connection $_c) {
		$this->conn=$_c;
	}

	//!Disables throwing an exception if no object can be found by "from_id" (throws by default).
	public final function disable_strict_id_find() {$this->strict_id_find=false;}

	//!Enables throwing an exception if no object can be found by "from_id" (enabled by default).
	public final function enable_strict_id_find() {$this->strict_id_find=true;}

	//!Records the entity in the database. 
	public final function insert(Database_entity $_e) {
		if($_e->get_persistent_id()) {
			throw new ORM_exception("Cannot perform insert in database entity which has an assigned id");
		}

		$_e->before_insert();

		$texts_fields=[];
		$parameters='';
		$bindings=[];

		$repo=Database_entity_link_repository::get(get_class($_e));

		foreach($repo->definitions as &$link) {

			if(!$link->is_id) {
				$paramname=':'.$link->field;
				$texts_fields[]=$link->field;
				$parameters[]=$paramname;
				$bindings[$paramname]=call_user_func([$_e, $link->accesor]);
			}
		}

		$statement=null;
		try {
			$statement=$this->conn->get()->prepare("INSERT INTO ".$repo->table." (".implode(',', $texts_fields).") VALUES (".implode(',', $parameters).");");
			foreach($bindings as $param => $value) {
				$statement->bindValue($param, $value);
			}
			$statement->execute();
			$_e->set_persistent_id($this->conn->get()->lastInsertId());
			$statement=null;
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	//!Updates the entity in the database.
	public function update(Database_entity $_e) {

		if(!$_e->get_persistent_id()) {
			throw new ORM_exception("Cannot perform update in database entity which has no id");
		}

		$_e->before_update();

		$updates=[];
		$bindings=[];
		$field_id=null;

		$repo=Database_entity_link_repository::get(get_class($_e));

		foreach($repo->definitions as &$link) {

			if(!$link->is_id) {
				$paramname=':'.$link->property;
				$updates[]=$link->property.' = '.$paramname;
				$bindings[$paramname]=call_user_func([$_e, $link->accesor]);
			}
			else {
				$field_id=$link->field;
				$bindings[self::DEF_ID_PARAM]=$_e->get_persistent_id();
			}
		}

		if(!$field_id) {
			throw new ORM_exception(get_class($_e).' does not define a id field');
		}

		$statement=null;
		try {
			$statement=$this->conn->get()->prepare("UPDATE ".$repo->table." SET ".implode(',', $updates)." WHERE ".$field_id."=".self::DEF_ID_PARAM);
			foreach($bindings as $param => $value) {
				$statement->bindValue($param, $value);
			}
			$statement->execute();
			$statement=null;
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	//!Removes an entity from the database.
	public final function delete(Database_entity $_e) {

		if(!$_e->get_persistent_id()) {
			throw new ORM_exception("Cannot perform delete in database entity which has no id");
		}

		$_e->before_delete();

		$statement=null;
		try {
			$classname=get_class($_e);
			$statement=$this->conn->get()->prepare("DELETE FROM ".Database_entity_link_repository::get($classname)->table." WHERE ".$this->get_id_definition($classname)->field."=".self::DEF_ID_PARAM);
			$statement->bindValue(self::DEF_ID_PARAM, $_e->get_persistent_id());
			$statement->execute();
			$statement=null;
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	//!Returns a Renoir_engine\ORM\Statement from the classname and query definition.
	public function select($_classname, Query_definition $_q) {

		if(!class_exists($_classname)) {
			throw new ORM_exception($_classname." was not defined for its use by 'select'.");
		}		

		$statement=null;
		try {
			$qstr=$_q->build_query_string(Database_entity_link_repository::get($_classname)->table, $this->get_id_definition($_classname)->field);
			$statement=$this->conn->get()->prepare($qstr);
			$_q->bind_parameters($statement);
			$statement->execute();
			return new Statement($statement);
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function select_one($_classname, Query_definition $_q) {
		try {
			$_q->set_limit(1)->clear_offset();
			$statement=$this->select($_classname, $_q);
			return $_classname::from_array($statement->fetch());
		}
		catch(\Exception $e) {
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function select_many($_classname, Query_definition $_q, $_limit=null, $_offset=null) {
		try {
			if($_limit) $_q->set_limit($_limit);
			if($_offset) $_q->set_offset($_offset);
			$result=[];
			$statement=$this->select($_classname, $_q);
			while($data=$statement->fetch()) {
				$result[]=$_classname::from_array($data);
			}
			return $result;
		}
		catch(\Exception $e) {
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	//!Instantiates an object of the derived class with the given id.
	public function from_id($_classname, $_id) {
	
		if(!class_exists($_classname)) {
			throw new ORM_exception($_classname." was not defined for its use by 'from_id'.");
		}

		$result=new $_classname;
		$data=$this->fetch_data_by_id($result, $_id);
		if(!$data && $this->strict_id_find) {
			throw new ORM_exception("Cannot find object of ".$_classname." with id ".$_id." (perhaps you wish to disable strict mode?)");
		}
		$result->set_properties($data, Database_entity::KEY_FIELD, Database_entity::ALLOW_ID);
		return $result;
	}

	//!Returns the Database_entity_link object that corresponds to the id. The parameter can either be the classname or an entity.
	private final function get_id_definition($_e) {

		if(is_string($_e)) {
			$classname=$_e;
		}
		else if(is_subclass_of($_e, Database_entity::class)) {
			$classname=get_class($_e);
		}
		else {
			throw new ORM_exception('Get id_definition must be called with a class name or instance');
		}

		$id_definition=array_filter(Database_entity_link_repository::get($classname)->definitions, function(Database_entity_link $l) {return $l->is_id;});
		if(count($id_definition)!==1) {
			throw new ORM_exception($classname.' must define one and just one id field');
		}
		return $id_definition[0];
	}

	//!Fetchs all columns from the database that correspond to the given id and returns them as an array.
	private final function fetch_data_by_id(Database_entity $_e, $id) {
		$statement=null;
		try {
			$classname=get_class($_e);
			$statement=$this->conn->get()->prepare("SELECT * FROM ".Database_entity_link_repository::get($classname)->table." WHERE ".$this->get_id_definition($classname)->field."=".self::DEF_ID_PARAM);
			$statement->bindValue(self::DEF_ID_PARAM, $id);
			$statement->execute();
			$data=$statement->fetch(\PDO::FETCH_ASSOC);
			$statement->closeCursor();
			$statement=null;
			return $data;
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}
}
