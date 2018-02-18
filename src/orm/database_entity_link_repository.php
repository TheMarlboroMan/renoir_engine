<?php
namespace Renoir_engine\ORM;

//Static repository for ORM class definitions: every single entity must be
//defined inside its $links property with a Database_entity_link_entry, which
//in time contains a few Database_entity_link objects and the table name.

class Database_entity_link_repository {

	private static $links=[];

	public static function add($classname, $tablename, $links) {

		if(array_key_exists($classname, self::$links)) {
			throw new ORM_exception($classname." was already registered with the entity link repository");
		}

		self::$links[$classname]=new Database_entity_link_entry($tablename, $links);
	}

	public static function get($classname) {

		if(!array_key_exists($classname, self::$links)) {
			throw new ORM_exception($classname." was not registered with the entity link repository");
		}

		return self::$links[$classname];
	}
}

//An entry for the Database_entity_link_repository.

class Database_entity_link_entry {
	public $table;
	public $definitions;
	public function __construct($_t, $_d) {
		$this->table=$_t;
		$this->definitions=$_d;
	}
}
