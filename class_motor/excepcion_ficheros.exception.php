<?php
class Excepcion_fichero extends Exception
{
	public function acc_mensaje() {return $this->message;}
	public function acc_codigo() {return $this->code;}

	public function __construct($codigo)
	{
		$this->code=$codigo;

		switch($this->code)
		{
			case -1:
				$this->message = "No se pudo subir el archivo adjunto";
			break;
			case -2:
				$this->message = "El archivo sobrepasa el tama&ntilde;o m&aacute;ximo permitido";
			break;
			case -3:
				$this->message = "Se ha encontrado un error en el archivo";
			break;
			case -4:
				$this->message = "No se ha adjuntado archivo";
			break;
			case -5:
				$this->message = "La extensión del archivo no se encuentra entre las permitidas";
			break;
			default:
				$this->message = "Se ha generado un error no especificado";
			break;
		}	
	}
}
?>
