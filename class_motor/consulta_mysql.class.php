<?php
/*
Una consulta para mysql... Pasa por un filtrado todos los textos para detectar
posibles inyecciones siempre que se cumplan las condiciones (cada where, por 
ejemplo, en una línea...).
*/
class Consulta_mysql
{
	protected $conexion=null;
	protected $texto=null;			
	protected $consulta;			
	protected $array_resultados=array();
	protected $filas=-1;
	protected $tipo;

	public function acc_conexion() {return $this->conexion;}
	public function acc_texto() {return $this->texto;}
	public function acc_consulta() {return $this->consulta;}

	public static function &conectar($h, $u, $p, $bbdd)
	{
		$conexion=mysql_connect($h, $u, $p) or die('ERROR: No se puede establecer conexion con la base de datos');
		mysql_select_db($bbdd, $conexion) or die('ERROR: No se encuentra la base de datos especificada');

		return $conexion;
	}

	public function __construct($conexion=null)
	{
		if($conexion) $this->conexion=$conexion;
	}

	public function limpiar()
	{
		if($this->tipo=='q' && $this->consulta) mysql_free_result($this->consulta);

		$this->texto=null;
		$this->consulta=null;
		$this->array_resultados=array();
		$this->filas=-1;
	}

	public function texto($texto)
	{
		$this->limpiar();

		//Sacamos el tipo de consulta...
		if(strtoupper(substr($texto, 0, 6))=='SELECT')
		{
			$this->tipo='q';
			$this->texto=self::validar_texto($texto);
		}
		else
		{
			$this->tipo='e';
//			$this->texto=$texto; 
			$this->texto=self::validar_texto($texto);
		}		
	}

	public function consultar(&$texto=null)
	{
		if(strlen($texto)) $this->texto=$this->validar_texto($texto);

		if($this->conexion)
		{
			$this->consulta=mysql_query($this->texto, $this->conexion);
		}
		else
		{
			$this->consulta=mysql_query($this->texto);
		}

		//Controlamos el resultado...
		if(!$this->consulta) 
		{	
			$this->filas=-1;

			$error=mysql_error().' en '.$this->texto;
			$codigo=mysql_errno();

			throw new Excepcion_consulta_mysql($error, $codigo);
		}
		else
		{
			//Asignamos las filas...
			if($this->tipo=='q')
			{
				$this->filas=mysql_num_rows($this->consulta);
			}
			else
			{
				$this->filas=mysql_affected_rows();
			}

			return true;
		}
	}

	public function leer()
	{		
		if($this->consulta)
		{			
			unset($this->array_resultados);
			$this->array_resultados=mysql_fetch_assoc($this->consulta);

			if($this->array_resultados) return true;
			else return false;
		}
		else return false;
	}

	public function leer_como_row()
	{		
		if($this->consulta)
		{			
			unset($this->array_resultados);
			$this->array_resultados=mysql_fetch_row($this->consulta);

			if($this->array_resultados) return true;
			else return false;
		}
		else return false;
	}

	public function resultados($indice=null)
	{
		if(!$indice)
		{
			return $this->array_resultados;
		}
		else
		{
			return $this->array_resultados[$indice];
		}
	}

	public static function ultimo_id()
	{
		$texto="
SELECT LAST_INSERT_ID() AS id";

		$temp=mysql_query($texto);
		$datos=mysql_fetch_assoc($temp);
		return $datos['id'];
	}

	private static function validar_texto($texto)
	{
		$texto=trim($texto);
		$texto_separado=explode("\n", $texto);
	
		//Trimeamos cada parte...
		foreach($texto_separado as $clave => $valor)
		{
			$texto_separado[$clave]=trim($valor);
			if(!strlen($texto_separado[$clave])) unset($texto_separado[$clave]);
		}

		foreach($texto_separado as $indice => $valor)
		{
			//Buscamos la primera y la última...
			$primera=strpos($valor, "'");
			$ultima=strrpos($valor, "'");

			//Si hay comillas partimos la cadena entre el antes, el despúes y lo que hay que escapar.
			if($primera !== false && $ultima !== false && $primera!=$ultima)
			{
				$antes=substr($valor, 0, $primera+1);	//Hasta la primera comilla...
				$despues=substr($valor, $ultima, strlen($valor)-$ultima);	//A partir de la última comilla.

				$cadena_valor=substr($valor, $primera+1, $ultima-$primera-1);
				if(get_magic_quotes_gpc()) $cadenavalor=stripslashes($cadena_valor);
				$escapar=mysql_real_escape_string($cadena_valor);		

				//$escapar=mysql_real_escape_string(substr($valor, $primera+1, $ultima-$primera-1)); 	//Esto es lo que va enmedio... Sin las comillas primera ni última.				

				$cadena=$antes.$escapar.$despues;
			}
			else
			{
				$cadena=$valor;
			}

			$texto_separado[$indice]=$cadena;

		}

		$texto_final=null;
	
		foreach($texto_separado as $clave => $valor)
		{
			$texto_final.=$valor." ";
		}

		//El texto ha sido preparado.
		return $texto_final;
	}

	public function filas() {return $this->filas;}
	public function puntero($i) {return mysql_data_seek($this->consulta, $i);}

	public static function determinar_tabla($tabla)
	{
		$texto="SHOW TABLES LIKE '{$tabla}'";

		$consulta=new Consulta_mysql;
		$consulta->consultar($texto);
		return $consulta->filas();
	}
}
?>
