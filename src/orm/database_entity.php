<?php
namespace Renoir_engine\ORM;

//!Represents the base class for a database entity in the manner of active-record techniques.
//
//!Unlike in the previous version of the engine, a class that extends this one 
//!must not implement specific methods but feed its own id field to the parent 
//!constructor and add its class definition to the Database_entity_link_repository.
//
//!Also, unlike the previous version of the engine, to create an entry from 
//!database information (or just the id) we need to use the database_io object.
//!
//!Every database entity class must extend this one, provide its id to the
//!parent constructor, define itself in the Database_entity_link_repository
//!and provide an id field (a primary key).
//!
//!Examples of use can be found in the examples/orm directory.

abstract class Database_entity {

	const KEY_PROPERTY=111;		//!< Defines that keys in an array (when creating an object from it) should be mapped to class properties.
	const KEY_FIELD=112;		//!< Defines that keys in an array (when creating an object from it) should be mapped to database fields.
	const DISALLOW_ID=113;		//!< Indicates that no ID field should be defined when creating an object from an array or setting its properties from it.
	const ALLOW_ID=114;		//!< Indicates that an ID field is allowed when creating an object from an array or setting its properties from it.

	private $id=null;

	//!Class constructor.

	//!Every derived class must pass its own id field.
	public function __construct(&$_bind_id) {

		$this->id=&$_bind_id;
	}

	//!For use with Database_IO, gets the primary key value, if any.
	public function get_persistent_id() {return $this->id;}
	//!For use with Database_IO, sets the primary key value. Note that we don't check for nasty multiple sets.
	public function set_persistent_id($v) {$this->id=$v;}

	//!Provides a placeholder for derived classes that might want to perform operations before insert.
	public function before_insert() {}
	//!Provides a placeholder for derived classes that might want to perform operations before update.
	public function before_update() {}
	//!Provides a placeholder for derived classes that might want to perform operations before delete.
	public function before_delete() {}

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
}
