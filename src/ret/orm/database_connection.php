<?php
namespace RET\ORM;

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
	public function __construct($_host=null, $_dbname=null, $_user=null, $_pass=null) {

		if($_host && $_dbname && $_user) {
			$this->connect($_host, $_dbname, $_user, $_pass);
		}
	}

	//!Tries to connect to the database.
	public function connect($_host, $_dbname, $_user, $_pass=null) {

		if(null!==$this->pdo) {
			throw new ORM_exception("Database_connection was already connected");
		}

		try {
			//TODO: So far we only allow for Mysql connections... We should change that, right???
			$this->pdo=new \PDO("mysql:host=$_host;dbname=$_dbname;", $_user, $_pass);
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
