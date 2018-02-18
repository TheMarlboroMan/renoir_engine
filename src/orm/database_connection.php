<?php
namespace Renoir_engine\ORM;

//A thin container for a PDO database connection that should destroy it
//at the end.

class Database_connection {
	
	private $pdo=null;

	public function get() {return $this->pdo;}

	public function __construct($host=null, $dbname=null, $user=null, $pass=null) {

		if($host && $dbname && $user) {
			$this->connect($host, $dbname, $user, $pass);
		}
	}

	public function connect($host, $dbname, $user, $pass) {

		try {
			$this->pdo=new \PDO("mysql:host=$host;dbname=$dbname;", $user, $pass);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		catch(\Exception $e) {
			throw $e;
		}
	}

	public function __destruct() {
		$this->pdo=null;
	}
}
