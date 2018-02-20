<?php
namespace Renoir_engine\ORM;

//!Represents the base class for a database entity in the manner of active-record techniques.
//
//!Unlike in the previous version of the engine, a class that extends this one 
//!must not implement specific methods but feed its own id field to the parent 
//!constructor and add its class definition to the Database_entity_link_repository.
//
//!Also, unlike the previous version of the engine, to create an entry from an
//!array or a number, the methods from_array or from_id must be used instead
//!!of the constructor. Other differences include the insert and update methods
//!taking into account the entity state instead of the data passed as 
//!parameters.
//!
//!Every database entity class must extend this one, provide its id to the
//!parent constructor, define itself in the Database_entity_link_repository
//!and provide an id field (a primary key).
//!
//!Examples of use can be found in the examples directory.

abstract class Database_entity {

	const DEF_ID_PARAM=':id';	//!< Defines the default variable name for binding the id.
	const KEY_PROPERTY=111;		//!< Defines that keys in an array (when creating an object from it) should be mapped to class properties.
	const KEY_FIELD=112;		//!< Defines that keys in an array (when creating an object from it) should be mapped to database fields.
	const DISALLOW_ID=113;		//!< Indicates that no ID field should be defined when creating an object from an array or setting its properties from it.
	const ALLOW_ID=114;		//!< Indicates that an ID field is allowed when creating an object from an array or setting its properties from it.

	private static $strict_id_find=true;
	private static $conn=null;
	private $id=null;

	//!Disables throwing an exception if no object can be found by "from_id" (throws by default).
	public static function disable_strict_id_find() {self::$strict_id_find=false;}
	//!Enables throwing an exception if no object can be found by "from_id" (enabled by default).
	public static function enable_strict_id_find() {self::$strict_id_find=true;}
	//!Injects the static database component. Must be done after connecting.
	public static function set_connection(Database_connection $_c) {self::$conn=$_c;}

	//!Instantiates an object of the derived class with the given id.
	public static function from_id($id) {

		$classname=get_called_class();
		$result=new $classname;
		$data=$result->fetch_data_by_id($id);
		if(!$data && self::$strict_id_find) {
			throw new ORM_exception("Cannot find object of ".$classname." with id ".$id." (perhaps you wish to disable strict mode?)");
		}
		$result->set_properties($data, self::KEY_FIELD, self::ALLOW_ID);
		return $result;
	}

	//!Instantiates an object of the derived class with the given data. 

	//!The id is not neccesary. Data is a key-value array in which key is 
	//!the property or database field, as indicated by the second parameter. 
	//!Not all values must be present for this to work. If the id is found
	//!it is treated as if it was real: meaning it might overwrite real data on update.
	public static function from_array(array $data, $discriminator=self::KEY_PROPERTY) {

		$classname=get_called_class();
		$result=new $classname;
		$result->set_properties($data, $discriminator, self::ALLOW_ID);
		return $result;
	}

	//!Class constructor.

	//!Every derived class must pass its own id field.
	public function __construct(&$_bind_id) {

		$this->id=&$_bind_id;
	}

	//TODO:
	/*
	TODO: Perhaps this is static as well??? Think about use cases.
	public final function get_query_class() {

		$classname=get_called_class().'_sql';
		if(!class_exists($classname)) {
			//BLAH BLAH
		}

		if(!self::$conn) {
			//BLAH BLAH.
		}

		return new $classname(self::$conn);
	}
	*/

	//!Records the object in the database. 

	//!This function takes no parameters: the data is taken from the
	//!object itself and fed to the database.
	public final function insert() {

		if($this->id) {
			throw new ORM_exception("Cannot perform insert in database entity which has an assigned id");
		}

		$texts_fields=[];
		$parameters='';
		$bindings=[];

		$repo=Database_entity_link_repository::get(get_called_class());

		foreach($repo->definitions as &$link) {

			if(!$link->is_id) {
				$paramname=':'.$link->field;
				$texts_fields[]=$link->field;
				$parameters[]=$paramname;
				$bindings[$paramname]=call_user_func([$this, $link->accesor]);
			}
		}

		if(!self::$conn) {
			throw new ORM_exception("Database_entity connection not armed in insert! Did you forget set_conn()???");
		}

		$statement=null;
		try {
			$statement=self::$conn->get()->prepare("INSERT INTO ".$repo->table." (".implode(',', $texts_fields).") VALUES (".implode(',', $parameters).");");
			foreach($bindings as $param => $value) {
				$statement->bindValue($param, $value);
			}
			$statement->execute();
			$this->id=self::$conn->get()->lastInsertId();
			$statement=null;
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	//!Updates an object in the database.

	//!This function takes no parameters: values are taken directly
	//!from the object.
	public final function update() {

		if(!$this->id) {
			throw new ORM_exception("Cannot perform update in database entity which has no id");
		}

		$updates=[];
		$bindings=[];
		$field_id=null;

		$repo=Database_entity_link_repository::get(get_called_class());

		foreach($repo->definitions as &$link) {

			if(!$link->is_id) {
				$paramname=':'.$link->property;
				$updates[]=$link->property.' = '.$paramname;
				$bindings[$paramname]=call_user_func([$this, $link->accesor]);
			}
			else {
				$field_id=$link->field;
				$bindings[self::DEF_ID_PARAM]=$this->id;
			}
		}

		if(!$field_id) {
			throw new ORM_exception(get_called_class().' does not define a id field');
		}

		if(!self::$conn) {
			throw new ORM_exception("Database_entity connection not armed in update! Did you forget set_conn()???");
		}

		$statement=null;
		try {
			$statement=self::$conn->get()->prepare("UPDATE ".$repo->table." SET ".implode(',', $updates)." WHERE ".$field_id."=".self::DEF_ID_PARAM);
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

	//!Removes an object from the database.
	public final function delete() {

		if(!$this->id) {
			throw new ORM_exception("Cannot perform delete in database entity which has no id");
		}

		if(!self::$conn) {
			throw new ORM_exception("Database_entity connection not armed in delete! Did you forget set_conn()???");
		}

		$statement=null;
		try {
			$statement=self::$conn->get()->prepare("DELETE FROM ".Database_entity_link_repository::get(get_called_class())->table." WHERE ".$this->get_id_definition()->field."=".self::DEF_ID_PARAM);
			$statement->bindValue(self::DEF_ID_PARAM, $this->id);
			$statement->execute();
			$statement=null;
		}
		catch(\Exception $e) {
			$statement=null;
			throw new ORM_exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	//!Fetchs all columns from the database that correspond to the given id.
	private final function fetch_data_by_id($id) {

		if(!self::$conn) {
			throw new ORM_exception("Database_entity connection not armed to fetch data by id! Did you forget set_conn()???");
		}

		$statement=null;
		try {
			$statement=self::$conn->get()->prepare("SELECT * FROM ".Database_entity_link_repository::get(get_called_class())->table." WHERE ".$this->get_id_definition()->field."=".self::DEF_ID_PARAM);
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

	//!Uses the setters to set all properties in the object from the data array.

	//!The data array consists of key and value pairs (where key is the 
	//!database field or the property name, as the discriminator parameter
	//!dictates, property by default). If the id is included in the 
	//!data it may throw unless the third parameter is set to ALLOW_ID.

	public final function set_properties(array $data, $discriminator=self::KEY_PROPERTY, $allow_id=self::DISALLOW_ID) {

		switch($discriminator) {
			case self::KEY_PROPERTY:
			case self::KEY_FIELD: break;
			default: throw new ORM_exception("Discriminator must be field or property"); break;
		}

		switch($allow_id) {
			case self::ALLOW_ID:
			case self::DISALLOW_ID: break;
			default: throw new ORM_exception("Allow_id must be either allow or disallow"); break;
		}

		foreach(Database_entity_link_repository::get(get_called_class())->definitions as &$link) {
			$key=self::KEY_PROPERTY ? $link->property : $link->field;
			if(array_key_exists($key, $data)) {

				$value=null;
				switch($link->property_type) {
					case Database_entity_link::TYPE_STRING:	$value=(string)$data[$key]; break;
					case Database_entity_link::TYPE_BOOL:	$value=(bool)$data[$key]; break;
					case Database_entity_link::TYPE_INT:	$value=(int)$data[$key]; break;
					case Database_entity_link::TYPE_FLOAT:	$value=(float)$data[$key];  break;
				}

				if(!$link->is_id) {
					call_user_func([$this, $link->setter], $value);
				}
				else {
					if($allow_id===self::DISALLOW_ID) {
						throw new ORM_exception("Id was included in data when allow_id was disabled in call to set_properties");
					}
					$this->id=$value;
				}
			}
		}
	}

	//!Returns the Database_entity_link object that corresponds to the id.
	private function get_id_definition() {
		
		$id_definition=array_filter(Database_entity_link_repository::get(get_called_class())->definitions, function(Database_entity_link $l) {return $l->is_id;});
		if(count($id_definition)!==1) {
			throw new ORM_exception(get_called_class().' must define one and just one id field');
		}

		return $id_definition[0];
	}
}
