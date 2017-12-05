<?php
class Contenedor_variables
{
	const CLAVE_VACIAR='vaciar_contenedor';
	const CLAVE_POBLAR='poblar_contenedor';

	private $contenedor=array();
	private $como_cadena=null;
	
	private static $claves_ignorar=array('pag');

	public function __construct(&$contenedor) {$this->contenedor=&$contenedor;}
	public function acc_contenedor() {return $this->contenedor;}
	public function acc_como_cadena() {return $this->como_cadena;}

	public function limpiar(&$contenedor=null)
	{
		if(!$contenedor) $contenedor=&$this->contenedor;
		foreach($contenedor as $clave => $valor) unset($contenedor[$clave]);
	}

	public function poblar(&$datos=null, &$contenedor=null)
	{
		if(!is_array($contenedor)) $contenedor=&$this->contenedor;
		$this->limpiar($contenedor);

		foreach($datos as $clave => $valor)
		{
			if(!is_array($valor))
			{
				if(isset($contenedor[$clave])) unset($contenedor[$clave]);

				//Por alguna razón 0 es igual a cualquier cadena si lo expresamos como número...
				if(strlen($valor) && !in_array((string)$clave, self::$claves_ignorar))
				{		
					$valor=trim($valor);
					$contenedor[$clave]=$valor;
					$this->como_cadena.='&'.$clave.'='.$valor;
				}
			}
			else
			{
				$contenedor[$clave]=array();
				$this->poblar_contenedor($valor, $contenedor[$clave]);
			}
		}
	}	
}
?>
