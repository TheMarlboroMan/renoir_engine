<?php
namespace Renoir_engine\ORM;

//!A wrapper container for a PDO database connection.

//!This class is not designed to hand over statements and so on: keeping
//!track and freeing them is still managed by the client code.
//!If everything goes right, the database connection should be closed and 
//!destroyed by the time this object goes out of scope.

//TODO: Check if the connection is really closed when this thing exists.

class Database_connection {
	
	//!Returns the underlying PDO object.
	public function get() {return $this->pdo;}

	//!Creates the object. If parameters are specified, attempts to connect.
	public function __construct($host=null, $dbname=null, $user=null, $pass=null) {

		if($host && $dbname && $user) {
			$this->connect($host, $dbname, $user, $pass);
		}
	}

	//!Tries to connect to the database.
	public function connect($host, $dbname, $user, $pass=null) {

		if(null!==!$this->pdo) {
			throw new ORM_exception("Database_connection was already connected");
		}

		try {
			$this->pdo=new \PDO("mysql:host=$host;dbname=$dbname;", $user, $pass);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		catch(\Exception $e) {
			throw $e;
		}
	}

	//!Cleans up.
	public function __destruct() {
		$this->pdo=null;
	}

	private $pdo=null;
}
