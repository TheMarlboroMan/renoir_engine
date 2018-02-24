<?php
namespace OOT;

require("../../src/orm/autoload.php");

use Renoir_engine\ORM\Database_entity_link_repository;
use Renoir_engine\ORM\Database_entity_link;

//Define entities...
class Thing extends \Renoir_engine\ORM\Database_entity {

	private $id;
	private $name;
	private $description;

	public function get_id() {return $this->id;}
	public function get_name() {return $this->name;}
	public function get_description() {return $this->description;}

	public function set_name($v) {$this->name=$v;}
	public function set_description($v) {$this->description=$v;}

	public function __construct() {

		parent::__construct($this->id);
	}
}

//Register entities.
Database_entity_link_repository::add(Thing::class, 'thing', [
	Database_entity_link::create('id', 'id', Database_entity_link::TYPE_INT, Database_entity_link::IS_ID, Database_entity_link::USE_DEFAULT, Database_entity_link::VOID, Database_entity_link::IS_ID),
	Database_entity_link::create('name', 'name', Database_entity_link::TYPE_STRING),
	Database_entity_link::create('description', 'description', Database_entity_link::TYPE_STRING)
]);


/******************************************************************************/

try {
	$connection=new \Renoir_engine\ORM\Database_connection('localhost', 'oot', 'freeuser');
	$dbio=new \Renoir_engine\ORM\Database_IO($connection);

/*
	$thing=$dbio->from_id(Thing::class, 5);
	var_dump($thing);
*/
/*
	$create=new Thing();
	$create->set_name("New name");
	$create->set_description("New description");

	$dbio->insert($create);
	$id=$create->get_id();
	var_dump($create);

	$update=$dbio->from_id(Thing::class, $id);
	$update->set_name('Change');
	$update->set_description('Change');
	$dbio->update($update);
	var_dump($update);
*/
	echo "==============================================================\n";

	$qd=new \Renoir_engine\ORM\Query_definition;
	$qd->set_where("name LIKE :name AND id > :id")
		->set_order("id", \Renoir_engine\ORM\Query_definition::ORDER_DESC)
		->set_parameter("name", "%chan%")
		->set_parameter("id", 12);

/*	$statement=$dbio->select(Thing::class, $qd);
	while($data=$statement->fetch()) {
		$thing=Thing::from_array($data);
		var_dump($thing);
	}
*/
/*
	$thing=$dbio->select_one(Thing::class, $qd);
	print_r($thing);
	die();
*/
	$things=$dbio->select_many(Thing::class, $qd);
	print_r($things);
	die();


//	$statement->close();
}
catch(\Exception $e){
	die('ERROR: '.$e->getMessage());
}
