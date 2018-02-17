<?php
/*
Un contenido básico del motor, apoyado en el manejador de propiedades y que
implemente las necesidades para relacionarse con la base de datos. Lo extendemos
a todos aquellos elementos en BBDD que tengamos.
*/

abstract class Contenido_bbdd extends Manejador_propiedades implements Contrato_bbdd
{
	protected static $consulta_contenido_bbdd=null;
	private static $strict_id_loading=true;

	//TODO TODO TODO TODO
	protected $id_bbdd=null;
	//TODO TODO TODO TODO

	private $diccionario;

	public function &DICCIONARIO() {return $this->diccionario;}
	public function &CONSULTA() {return self::$consulta_contenido_bbdd;}

	//TODO TODO TODO TODO
	public function ID_INSTANCIA() {return $this->id_bbdd;}
	public function MUT_ID($id) {$this->id_bbdd=$id;}
	//TODO TODO TODO TODO

	public static function disable_strict_loading() {self::$strict_id_loading=false;}

	public function __construct(&$datos=null, &$otro_diccionario)
	{
		$this->diccionario=&$otro_diccionario;

		if(!self::$consulta_contenido_bbdd) 
		{
			self::$consulta_contenido_bbdd=new Consulta_mysql;
		}

		if(is_numeric($datos))
		{
			$interfaz=new Comunicacion_bbdd($this);
			$datos=$interfaz->obtener_datos_por_id($datos);
			if(!$datos && self::$strict_id_loading)
			{
				throw new Exception("contenido_bbdd_dinamico: Unretrievable data with id. Deactivate if needed");
			}
			$this->cargar($datos);
			unset($interfaz);
		}
		else if(is_array($datos))
		{
			$this->cargar($datos);
		}
	}

	public function recargar(&$interfaz=null)
	{
		if(!is_object($interfaz))
		{
			$interfaz=new Comunicacion_bbdd($this);
	//TODO TODO TODO TODO
			$this->cargar($interfaz->obtener_datos_por_id($this->id_bbdd));
	//TODO TODO TODO TODO
			unset($interfaz);
		}
		else
		{
	//TODO TODO TODO TODO
			$this->cargar($interfaz->obtener_datos_por_id($this->id_bbdd));
	//TODO TODO TODO TODO
		}
	}

	public function base_crear(&$datos=null, $extra_campos=null, $extra_valores=null)
	{
		if($this->ID_INSTANCIA()) return false;
		else
		{
			$interfaz=new Comunicacion_bbdd($this);
			$resultado=$interfaz->crear($datos, $extra_campos, $extra_valores);
			$this->recargar($interfaz);
			unset($interfaz);
			return $resultado;
		}		
	}

	public function base_modificar(&$datos=null, $extra_campos=NULL)
	{
		if(!$this->ID_INSTANCIA()) $resultado=false;
		else
		{
			$interfaz=new Comunicacion_bbdd($this);
			$resultado=$interfaz->modificar($datos, $extra_campos);
			$this->recargar($interfaz);
			unset($interfaz);
			return $resultado;
		}
	}

	public function base_eliminar(&$datos=null)
	{
		if(!$this->ID_INSTANCIA()) $resultado=false;
		else
		{
			$interfaz=new Comunicacion_bbdd($this);
			$resultado=$interfaz->eliminar_logico($datos);
			unset($interfaz);
			return $resultado;
		}
	}

	public function base_eliminar_fisico(&$datos=null)
	{
		if(!$this->ID_INSTANCIA()) $resultado=false;
		else
		{
			$interfaz=new Comunicacion_bbdd($this);
			$resultado=$interfaz->eliminar_fisico($datos);
			unset($interfaz);
			return $resultado;
		}
	}

	public function cargar(&$datos)
	{
		if(!is_array($datos))
		{
			$interfaz=new Comunicacion_bbdd($this);
			$datos=$interfaz->obtener_datos_por_id($datos);
			unset($interfaz);
		}
		parent::cargar_instancia($datos, $this);
	//TODO TODO TODO TODO
		$this->id_bbdd=$datos[$this->ID()];
	//TODO TODO TODO TODO
	}


	final public static function obtener_consulta($texto)
	{
		$resultado=new Consulta_mysql();
		$resultado->consultar($texto);
		return $resultado;
	}

	final public function &obtener_array_objetos($texto)
	{
		$resultado=array();
		$consulta=self::obtener_consulta($texto);
		$nombre_clase=$this->NOMBRE_CLASE();

		while($consulta->leer())
		{
			$datos=$consulta->resultados();
			$resultado[]=new $nombre_clase($datos);
		}

		return $resultado;
	}

	final public function &obtener_objeto_por_texto($texto)
	{
		$consulta=self::obtener_consulta($texto);
		
		if(!$consulta->filas()) 
		{
			$resultado=null;
		}
		else
		{
			$consulta->leer();
			$datos=$consulta->resultados();
			$nombre_clase=$this->NOMBRE_CLASE();
			$resultado=new $nombre_clase($datos);
		}

		return $resultado;
	}
}
?>
