<?php
class Excepcion_consulta_mysql extends Exception
{
	public function __construct($message, $code, Exception $previous = null) 
	{
		parent::__construct($message, $code, $previous);
	}
}
?>
