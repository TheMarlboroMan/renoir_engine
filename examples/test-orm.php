<?php
namespace OOT;

require("../autoload.php");

use Renoir_engine\ORM\Database_entity_link_repository;
use Renoir_engine\ORM\Database_entity_link;

Database_entity_link_repository::add(Song::class, 'oot_songs', [
	Database_entity_link::create('song_id', 'id', Database_entity_link::TYPE_INT, Database_entity_link::IS_ID, 'get_song_id', Database_entity_link::VOID),
	Database_entity_link::create('date', 'date', Database_entity_link::TYPE_STRING),
	Database_entity_link::create('title', 'title', Database_entity_link::TYPE_STRING),
	Database_entity_link::create('slug', 'slug', Database_entity_link::TYPE_STRING)
]);

class Song extends \Renoir_engine\ORM\Database_entity {

	private $song_id;
	private $date;
	private $title;
	private $slug;

	public function get_song_id() {return $this->song_id;}
	public function get_date() {return $this->date;}
	public function get_title() {return $this->title;}
	public function get_slug() {return $this->slug;}

	public function set_date($v) {$this->date=$v;}
	public function set_title($v) {$this->title=$v;}
	public function set_slug($v) {$this->slug=$v;}

	public function __construct() {

		parent::__construct($this->song_id);
	}
}

Database_entity_link_repository::add(Thing::class, 'thing', [
	Database_entity_link::create('id', 'id', Database_entity_link::TYPE_INT, Database_entity_link::IS_ID, Database_entity_link::USE_DEFAULT, Database_entity_link::VOID, Database_entity_link::IS_ID),
	Database_entity_link::create('name', 'name', Database_entity_link::TYPE_STRING),
	Database_entity_link::create('description', 'description', Database_entity_link::TYPE_STRING)
]);

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

/******************************************************************************/

$connection=new \Renoir_engine\ORM\Database_connection('localhost', 'oot', 'freeuser');
\Renoir_engine\ORM\Database_entity::set_connection($connection);

try {
	$thing=Thing::from_id(5);
	var_dump($thing);
}
catch(\Exception $e){
	die('ERROR: '.$e->getMessage());
}
