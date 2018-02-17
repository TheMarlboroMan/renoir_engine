<?php
/*
Una clase cuya utilidad es recogar una instancia de un objeto que se adhiere a
este motor de contenidos y realizar todas las comunicaciones con la base de 
datos que componen el set mínimo (crear, eliminar, cargar...).
*/

class Comunicacion_bbdd
{
	private $instancia=null;	
	private static $diccionario_palabras_clave=array('CURDATE()', 'CURTIME()');

	public function __construct(&$elemento)	
	{
		if(!is_object($elemento) || !is_subclass_of($elemento, 'Manejador_propiedades'))
		{
			Herramientas::error('El elemento no es un objeto o no extiende a la clase base');
		}
			
		$this->instancia=&$elemento;
	}

	public function obtener_ultimo_id()
	{
		$texto="SELECT ".$this->instancia->ID()." AS id
			FROM ".$this->instancia->TABLA()."
			ORDER BY ".$this->instancia->ID()." DESC
			LIMIT 1 OFFSET 0";
	
		$this->instancia->CONSULTA()->consultar($texto);

		if($this->instancia->CONSULTA()->leer())
		{
			$resultado=$this->instancia->CONSULTA()->resultados('id');
			return $resultado;
		}
		else return false;		
	}

	public function &obtener_datos_por_id($id)
	{
		$texto="SELECT *
			FROM ".$this->instancia->TABLA()."
			WHERE ".$this->instancia->ID()."='".$id."'";

		$this->instancia->CONSULTA()->consultar($texto);

		if($this->instancia->CONSULTA()->leer())
		{
			$resultado=$this->instancia->CONSULTA()->resultados();
		}
		else 
		{
			$resultado=false;
		}
	
		return $resultado;
	}

	public function crear(&$datos, $extra_campos=null, $extra_valores=null)
	{					
		$campos=null;
		$valores=null;

		foreach($this->instancia->DICCIONARIO() as $clave => $valor)
		{
			if(isset($datos[$clave]))
			{
				$campos.="\n".$valor.",";

				if(get_magic_quotes_gpc()) $datos[$clave]=stripslashes($datos[$clave]);

				if(in_array($datos[$clave], self::$diccionario_palabras_clave))
				{
					//Creo que esto no tiene sentido, pero...
//					$valores.="\n".mysql_real_escape_string($datos[$clave]).",";
					$valores.="\n".$datos[$clave].",";
				}
				else
				{
//					$valores.="\n'".mysql_real_escape_string($datos[$clave])."',";
					$valores.="\n\"".mysql_real_escape_string($datos[$clave])."\",";
				}
			}
		}

		if(strlen($campos))
		{
			$campos=substr($campos, 0, -1);
			$valores=substr($valores, 0, -1);
		}	
		
		if($extra_campos && strlen($campos)) $extra_campos.=',';
		if($extra_valores && strlen($valores)) $extra_valores.=',';

		$texto="INSERT INTO ".$this->instancia->TABLA()."
			(
				".$extra_campos."
				".$campos."				
			)
			VALUES
			(
				".$extra_valores."
				".$valores."				
			)";

		if($this->instancia->CONSULTA()->consultar($texto))
		{
			//Recuperamos el id usando el método de la clase
			$id_elemento=$this->obtener_ultimo_id();
			$this->instancia->MUT_ID($id_elemento);

			return true;
		}
		else return false;	
	}
	
	public function modificar(&$datos, $extra_campos)
	{
		$campos=null;

		foreach($this->instancia->DICCIONARIO() as $clave => $valor)
		{
			if(isset($datos[$clave]))
			{
				if(get_magic_quotes_gpc()) $datos[$clave]=stripslashes($datos[$clave]);

				if(in_array($datos[$clave], self::$diccionario_palabras_clave))
				{
					$campos.="\n".$valor."=".$datos[$clave].",";
				}
				else
				{
					$campos.="\n".$valor."=\"".mysql_real_escape_string($datos[$clave])."\",";
				}
			}
		}

		if(!strlen($campos))
		{
			return false;
		}
		else 
		{
			if($campos!='') $campos=substr($campos, 0, -1);
			if($extra_campos && strlen($campos)) $extra_campos.=',';

			$texto="UPDATE ".$this->instancia->TABLA()." SET
				".$extra_campos."
				".$campos."
				WHERE ".$this->instancia->ID()."='".$this->instancia->ID_INSTANCIA()."'";

			return $this->instancia->CONSULTA()->consultar($texto);
		}
	}

	public function eliminar_fisico($datos)
	{
		$texto="DELETE FROM ".$this->instancia->TABLA()."
			WHERE ".$this->instancia->ID()."='".mysql_real_escape_string($this->instancia->ID_INSTANCIA())."'";

		return $this->instancia->CONSULTA()->consultar($texto);
	}

	public function eliminar_logico($datos, $campo_logico='borrado_logico', $valor_borrado='1')
	{
		if(!$campo_logico) return false;
		else
		{
			$texto="UPDATE ".$this->instancia->TABLA()." SET
				".$campo_logico."='".$valor_borrado."'
				WHERE ".$this->instancia->ID()."='".$this->instancia->ID_INSTANCIA()."'";

			return $this->instancia->CONSULTA()->consultar($texto);
		}
	}
};
?>
