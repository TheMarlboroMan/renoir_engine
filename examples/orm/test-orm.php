<?php
namespace OOT;

if(php_sapi_name()!==PHP_SAPI){
	die("This script must be run from the console like 'php -f test-orm.php'\n");
}

require("../../src/orm/autoload.php");

use Renoir_engine\ORM\Database_entity_link_repository;
use Renoir_engine\ORM\Database_entity_link;

/*
A table should be created for this entity...

CREATE TABLE thing(
id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(200) NOT NULL,
description TEXT NOT NULL,
date DATETIME NOT NULL,
date_creation DATETIME NOT NULL,
date_last_update DATETIME NULL
)ENGINE=MYISAM;
*/

//Define entities...
class Thing extends \Renoir_engine\ORM\Database_entity {

	private $id;
	private $name;
	private $description;
	private $date=null; 
	private $date_creation=null;
	private $date_last_update=null;

	public function get_id() {return $this->id;}
	public function get_name() {return $this->name;}
	public function get_description() {return $this->description;}
	public function get_date() {return $this->date;}
	public function get_date_creation() {return $this->date_creation;}
	public function get_date_last_update() {return $this->date_last_update;}

	public function set_name($v) {$this->name=$v;}
	public function set_description($v) {$this->description=$v;}
	public function set_date(\DateTime $v) {$this->date=$v;}
	public function set_date_creation(\DateTime $v) {$this->date_creation=$v;}
	public function set_date_last_update(\DateTime $v) {$this->date_last_update=$v;}

	public function __construct() {
		parent::__construct($this->id);
	}

	public function before_insert() {
		$this->date_creation=new \DateTime();
	}

	public function before_update() {
		$this->date_last_update=new \DateTime();
	}

	public function before_delete() {
		//This hook would execute before deletion.
	}
}

//Register entities... First entity name, then table, next [property, field, type, is_id, accessor and setter.
Database_entity_link_repository::add(Thing::class, 'thing', [
	Database_entity_link::create('id', 'id', Database_entity_link::TYPE_INT, Database_entity_link::IS_ID, Database_entity_link::USE_DEFAULT, Database_entity_link::VOID, Database_entity_link::IS_ID),
	Database_entity_link::create('name', 'name', Database_entity_link::TYPE_STRING),
	Database_entity_link::create('description', 'description', Database_entity_link::TYPE_STRING),
	Database_entity_link::create('date', 'date', Database_entity_link::TYPE_DATETIME),
	Database_entity_link::create('date_creation', 'date_creation', Database_entity_link::TYPE_DATETIME),
	Database_entity_link::create('date_last_update', 'date_last_update', Database_entity_link::TYPE_DATETIME),
]);


/******************************************************************************/

try {
	$connection=new \Renoir_engine\ORM\Database_connection('localhost', 'oot', 'root');
	$dbio=new \Renoir_engine\ORM\Database_IO($connection);


	//Basic CRUD operations...
	//Read.
	try {
		$thing=$dbio->from_id(Thing::class, 3);
		$thing->set_description("New");
		$dbio->update($thing);
		var_dump($thing);
	}
	catch(\Exception $e) {
		echo "COULD NOT FIND OBJECT WITH ID 5\n";
	}

	//Create.
	$create=new Thing();
	$create->set_name("New name");
	$create->set_description("New description");
	$create->set_date(\DateTime::createFromFormat('Y-m-d H:i:s', '2017-01-02 12:23:40'));
	$dbio->insert($create);
	$id=$create->get_id();
	var_dump($create);

	//Update...
	$update=$dbio->from_id(Thing::class, $id);
	$update->set_name('Change');
	$update->set_description('Change');
	$update->set_date(\DateTime::createFromFormat('Y-m-d', '2015-04-10'));
	$dbio->update($update);
	var_dump($update);

	//Delete.
	//$dbio->delete($update);

	//Fetching operations.
	echo "==============================================================\n";

	$qd=new \Renoir_engine\ORM\Query_definition;
	$qd->set_where("name LIKE :name AND id > :id")
		->set_order("id", \Renoir_engine\ORM\Query_definition::ORDER_DESC)
		->set_parameter("name", "%chan%")
		->set_parameter("id", 1);

	//Manual fetch. Good idea to close the statement later.
	$statement=$dbio->select(Thing::class, $qd);
	while($data=$statement->fetch()) {
		$thing=Thing::from_array($data);
		var_dump($thing);
	}
	$statement->close();

	//Fetch only the first.
	$thing=$dbio->select_one(Thing::class, $qd);
	var_dump($thing);

	//Fetch an array... optionally specify a limit and offset.
	$things=$dbio->select_many(Thing::class, $qd /*,limit, offset */);
	var_dump($things);
}
catch(\Exception $e){
	die('ERROR: '.$e->getMessage());
}
