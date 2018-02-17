<?php
/*
Esto es un manejador de propiedades... Es una clase abstracta cuyas utilidades
son obtener acceso a las propiedades de objetos que derivan de si mismas y
cargarlos con arrays de datos.
*/
abstract class Manejador_propiedades
{
	public function cargar_instancia(&$datos, &$instancia)
	{
		if(!is_array($datos) || !is_object($instancia)) return false;
		else
		{
			$diccionario=$instancia->DICCIONARIO();

			foreach($datos as $clave => &$valor)
			{
				//Buscamos el nombre en el diccionario... Datos
				//es como viene de BBDD o externo...
				if(isset($diccionario[$clave]))
				{
					$propiedad_instancia=$diccionario[$clave];
	
					if(property_exists($instancia, $propiedad_instancia))
					{
						$instancia->$propiedad_instancia=$valor;
					}				
				}
			}

			return true;
		}
	}

	public function cargar_instancia_array(&$datos, &$instancia, &$array)
	{
		//Esto es igual que el anterior pero en lugar de cargar sobre
		//las propiedades de un objeto carga sobre un array... No tiene
		//mucho sentido en un principio pero es parte del experimento
		//de hacer una clase "contenido_bbdd" independiente de 
		//propiedades de objeto de modo que podamos hacer una fábrica
		//de clases con propiedades dinámicas.

		if(!is_array($datos) || !is_object($instancia) || !is_array($array)) return false;
		else
		{
			$diccionario=$instancia->DICCIONARIO();

			foreach($datos as $clave => &$valor)
			{
				//Buscamos el nombre en el diccionario... Datos
				//es como viene de BBDD o externo...
				if(isset($diccionario[$clave]))
				{
					$propiedad_instancia=$diccionario[$clave];
					$array[$propiedad_instancia]=$valor;
				}
			}

			return true;
		}


	}

	public function __get($nombre)
	{
		if(property_exists($this, $nombre))
		{
			return $this->nombre;
		}
		else
		{
			Herramientas::error('Se intent&oacute; acceder a la propiedad inexistente <b>'.$nombre.'</b> en la clase <b>'.get_class($this).'</b>.<br/>');
		}
	}

	public function __set($nombre,  $value)
	{
		if(property_exists($this, $nombre))
		{
			$this->$nombre=$value;
		}
		else
		{
			Herramientas::error('Se intent&oacute; modificar el valor de la propiedad inexistente <b>'.$nombre.'</b> en la clase <b>'.get_class($this).'</b>.<br/>');
		}
	}
}
?>
