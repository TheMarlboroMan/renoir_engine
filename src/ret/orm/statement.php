<?php
namespace RET\ORM;

//TODO: Comment.
class Statement {

	private $statement=null;
	private $closed=false;

	public function __construct(\PDOStatement $_s) {
		$this->statement=$_s;
	}

	public function __destruct() {
		$this->close();
	}

	public function fetch($_mode=\PDO::FETCH_ASSOC) {
		return $this->statement->fetch($_mode);
	}

	public function close() {
		if($this->closed) return;
		$this->statement->closeCursor();
		$this->statement=null;
		$this->closed=true;
	}
}
