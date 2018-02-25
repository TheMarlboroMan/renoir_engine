<?php
namespace RET\ORM;

//!Static repository for ORM class definitions.

//!Every single database entity must be defined inside the $links property 
//!through a Database_entity_link_entry, which in time contains a few 
//!Database_entity_link objects and the table name.
//!This repository can be populated in the same file the database entity is
//!declared in, or somewhere else. 
//!
//!An example of its use to add an entity Song, with the table songs:
//!
//!Database_entity_link_repository::add(Song::class, 'songs', [
//!	Database_entity_link::create('song_id', 'id', Database_entity_link::TYPE_INT, Database_entity_link::IS_ID, 'get_song_id', Database_entity_link::VOID),
//!	Database_entity_link::create('date', 'date', Database_entity_link::TYPE_STRING),
//!	Database_entity_link::create('title', 'title', Database_entity_link::TYPE_STRING),
//!	Database_entity_link::create('slug', 'slug', Database_entity_link::TYPE_STRING)]);

class Database_entity_link_repository {

	//!Adds a class definition. $classname must be the classname, links is an array of Database_entity_link_entry, which can be created with calls to Database_entity_link::create.
	public static function add($_classname, $_tablename, array $_links) {

		if(array_key_exists($_classname, self::$links)) {
			throw new ORM_exception($_classname." was already registered with the entity link repository");
		}

		self::$links[$_classname]=new Database_entity_link_entry($_tablename, $_links);
	}

	//!Returns the class definition.
	public static function get($_classname) {

		if(!array_key_exists($_classname, self::$links)) {
			throw new ORM_exception($_classname." was not registered with the entity link repository");
		}

		return self::$links[$_classname];
	}

	private static $links=[];
}

//!An entry for the Database_entity_link_repository.

//!Defines the table and an array of Database_entity_link.

class Database_entity_link_entry {
	public $table;		//!< Table name.
	public $definitions;	//!< array of Database_entity_link object, containing definitions for all fields.

	//!Creates the entry.
	public function __construct($_t, array $_d) {
		$this->table=$_t;
		$this->definitions=$_d;
	}
}
