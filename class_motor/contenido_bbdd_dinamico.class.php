<?php
/*
Una clase parecida a Contenido_bbdd sólo que es para clases que no tengan
especificadas sus propiedades de por si... Como eso no existe normalmente esto
lo usamos para "fábricas" de clases dinámicas, por así decirlo. Las clases
que extiendan a esto tienen un array diccionario de datos y también un array
que recoge las propiedades de este diccionario.

El diccionario de datos puede ser perfectamente dinámico.
*/
abstract class Contenido_bbdd_dinamico extends Contenido_bbdd
{
	protected $array_propiedades=array();

	public function &acc_array_propiedades() {return $this->array_propiedades;}

	//Aquí tenemos "propiedades" como el array de todas las propiedades posibles del objeto.
	//Luego está "datos", como el array con los datos que vamos a cargar sobre
	//estas propiedades. Por defecto todas las propiedades se cargan como null...
	//La relación entre "diccionario", "propiedades" y "datos" es 
	//compleja. El diccionario se usa en para comparar los
	//campos en bbdd y los recibidos por parámetros de modo que debería
	//ser parecido a "propiedades".

	public function __construct(&$datos=null, &$otro_diccionario, $tabla, $id, &$propiedades)
	{
		$this->generar_propiedades($propiedades);
		parent::__construct($datos, $otro_diccionario, $tabla, $id);
	}

	public function cargar(&$datos)
	{
		if(!is_array($datos))
		{
			$interfaz=new Comunicacion_bbdd($this);
			$datos=$interfaz->obtener_datos_por_id($datos);
			unset($interfaz);
		}

		parent::cargar_instancia_array($datos, $this, $this->array_propiedades);
//		$this->id_bbdd=$datos[$this->ID()];
	}	

	public function crear(&$datos=null) {return parent::base_crear($datos);}
	public function modificar(&$datos=null) {return parent::base_modificar($datos);}
	public function eliminar(&$datos=null) 
	{
		try
		{
			$resultado=parent::base_eliminar($datos);
		}
		catch(Exception $e)
		{
			$resultado=parent::base_eliminar_fisico($datos);
		}

		return $resultado;
	}

	public function acc_propiedad($propiedad)
	{
		if(isset($this->array_propiedades[$propiedad])) return $this->array_propiedades[$propiedad];
		else return null;
	}

	private function generar_propiedades(&$propiedades=null)
	{
		if(!is_array($propiedades)) die('ERROR: Contenido_bbdd_dinamico se esperaba array como propiedades');	
		else foreach($propiedades as $clave => $valor) $this->array_propiedades[$clave]=null;
	}
}
?>
