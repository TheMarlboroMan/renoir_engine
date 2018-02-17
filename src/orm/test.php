<?php
namespace Renoir_engine\ORM;

class ORM_exception extends \Exception {
	public function __construct($a, $b=0, $c=null) {
		parent::__construct($a, $b, $c);
	}
}

class Database_connection {
	
	private $pdo=null;

	public function get() {return $this->pdo;}

	public function __construct() {

		try {
			$this->pdo=new \PDO("mysql:host=localhost;dbname=oot;", "root", "unmono");
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		catch(\Exception $e) {
			throw $e;
		}
	}

	public function __destruct() {
		echo "BYE!";
		//$this->pdo=null;
	}
}

abstract class Database_entity {

	private static $conn=null;
	private $id=null;

	public static function set_connection(Database_connection $_c) {

		self::$conn=$_c;
	}

	public function __construct(&$_bind_id) {

		$this->id=&$_bind_id;
	}

	public final function insert() {

		if($this->id) {
			throw new ORM_exception("Altrady has an ID!!");
		}

		$texts_fields=[];
		$parameters='';
		$bindings=[];

		$repo=Database_entity_link_repository::get(get_called_class());

		foreach($repo->definitions as &$link) {

			if(!$link->is_id) {
				$paramname=':'.$link->property;
				$texts_fields[]=$link->property;
				$parameters[]=$paramname;
				$bindings[$paramname]=call_user_func([$this, $link->accesor]);
			}
		}

		$qstr="INSERT INTO ".$repo->table." (".implode(',', $texts_fields).") VALUES (".implode(',', $parameters).");";
		echo $qstr;

		if(!self::$conn) {
			throw new ORM_exception("Database_entity connection not armed! Did you forget set_conn()???");
		}

		$statement=null:
		try {
			$statement=self::$conn->get()->prepare($qstr);
			foreach($bindings as $param => $value) {
				$statement->bindValue($param, $value);
			}
			$statement->execute();
			$this->id=self::$conn->get()->lastInsertId();
			$statement=null:
		}
		catch(\Exception $e) {
			$statement=null:
			throw new ORM_exception($e->getMessage, $e->getCode, $e);
		}
	}

	public final function update() {

	}

	public final function delete() {

	}

	public final function set_fields($data) {

	}

	
}

class Database_entity_link {

	public $property;
	public $field;
	public $accesor;
	public $setter;
	public $is_id;

	const VOID=666;
	const IS_ID=333;

	private function __construct($_p, $_f, $_a, $_s, $_i) {
		$this->property=$_p;
		$this->field=$_f;
		$this->accesor=$_a;
		$this->setter=$_s;
		$this->is_id=$_i;
	}

	public static function create($_p, $_f, $_a=null, $_s=null, $_i=false) {

		$_a=$_a ? ($_a == self::VOID ? null : $_a ) : 'get_'.$_p;
		$_s=$_s ? ($_s == self::VOID ? null : $_s ) : 'set_'.$_p;

		return new Database_entity_link($_p, $_f, $_a, $_s, $_i===self::IS_ID);
	}
}

class Database_entity_link_entry {
	public $table;
	public $definitions;
	public function __construct($_t, $_d) {
		$this->table=$_t;
		$this->definitions=$_d;
	}
}

class Database_entity_link_repository {

	private static $links;

	public static function add($classname, $tablename, $links) {
		//TODO: Validate.
		self::$links[$classname]=new Database_entity_link_entry($tablename, $links);
	}

	public static function get($classname) {
		//TODO: Validate.
		return self::$links[$classname];
	}
}

namespace OOT;

\Renoir_engine\ORM\Database_entity_link_repository::add(Song::class, 'oot_songs', [
	\Renoir_engine\ORM\Database_entity_link::create('song_id', 'id', 'get_song_id', \Renoir_engine\ORM\Database_entity_link::VOID, \Renoir_engine\ORM\Database_entity_link::IS_ID),
	\Renoir_engine\ORM\Database_entity_link::create('date', 'date'),
	\Renoir_engine\ORM\Database_entity_link::create('title', 'title'),
	\Renoir_engine\ORM\Database_entity_link::create('slug', 'slug')
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

/******************************************************************************/

\Renoir_engine\ORM\Database_entity::set_connection(new \Renoir_engine\ORM\Database_connection());

$song=new Song;
$song->set_date('2010-01-01');
$song->set_title('Yestab');
$song->set_slug('yestab');
$song->insert();
