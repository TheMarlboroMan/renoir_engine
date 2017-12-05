<?php
class Idioma
{
	const CLAVE_CAMBIO_IDIOMA='lan';

	private $codigos=array('es', 'en');

	private $idioma=null;
	private $diccionario=array();
	private $almacenaje=null;

	private $seccion_temporal=null;

	public function acc_idioma() {return $this->idioma;}	

	public function seccion_temporal($valor) {$this->seccion_temporal=$valor;}

	public function t()	//Shorcut para texto de sección temporal.
	{
		$profundidad=array($this->seccion_temporal);

		$profundidad=array_merge($profundidad, func_get_args());
		$destino=&$this->diccionario[$this->idioma];
		$traza=null;

		foreach($profundidad as $clave => $valor)
		{
			$destino=&$destino[$valor];
			$traza.=' : '.$valor;
		}
		
		if(isset($destino) && !is_array($destino)) return $destino;
		else return '<span style="color: red">** No se encuentra el texto '.$traza.' **</span>';
	}

	public function __construct(&$p_almacenaje, $p_idioma=null)
	{
		$this->almacenaje=&$p_almacenaje;

		if(!$p_idioma)
		{
			if(isset($_GET[self::CLAVE_CAMBIO_IDIOMA]) && in_array($_GET[self::CLAVE_CAMBIO_IDIOMA], $this->codigos)) 
			{
				$p_idioma=$_GET[self::CLAVE_CAMBIO_IDIOMA];
			}
			else if(strlen($this->almacenaje))
			{
				$p_idioma=$this->almacenaje;
			}
			else 
			{
				$p_idioma='es';			
			}
		}

		$this->idioma=$p_idioma;
		$this->actualizar_idioma($this->idioma);
	}

	private function actualizar_idioma($idioma)
	{
		$this->almacenaje=$idioma;
	}

	public function cargar_idiomas()
	{
		$argumentos=func_get_args();

		if(!isset($this->diccionario[$this->idioma]) || !is_array($this->diccionario[$this->idioma])) $this->diccionario[$this->idioma]=array();

		if(is_array($argumentos))
		{
			$destino=&$this->diccionario[$this->idioma];
			$this->generar($argumentos, $destino);			
		}
	}

	private function incluir($valor)
	{
		$temp=include(Constantes::RUTA_SERVER.'lan/'.$this->idioma.'/'.$valor.'.lan.php');
		return $temp;
	}

	private function generar(&$array_carga, &$destino)
	{
		if(is_array($array_carga))
		{
			foreach($array_carga as $clave => &$valor)
			{
				if(!is_array($valor))		
				{
					$ruta=Constantes::RUTA_SERVER.'lan/'.$this->idioma.'/'.$valor.'.lan.php';
					if(file_exists($ruta) && is_file($ruta))
					{
						$temp=null;
						//Aquí está la trampa... El include ámbito del include sigue siendo el mismo
						//y se acumulan los valores. Por eso lo metemos en otro método.
						$temp=$this->incluir($valor);

						if(is_array($temp)) 
						{
							$destino[$valor]=$temp;
							unset($temp);
						}
						else 
						{
							die('ERROR: No se recupera el valor esperado del idioma '.$valor.'!!!');
						}
					}
					else 
					{
						die('ERROR: Imposible localizar archivo de idioma '.$valor.'!!!');
					}	
				}
				else
				{
					foreach($valor as $clave_int => $valor_int)
					{		
						$destino[$valor_int]=array();
						$this->generar($valor, $destino);
					}
				}

			}	
		}
	}

	public function array_texto()
	{
		$profundidad=func_get_args();
		$destino=&$this->diccionario[$this->idioma];
		$traza=null;

		foreach($profundidad as $clave => $valor)
		{
			$destino=&$destino[$valor];
			$traza.=' : '.$valor;
		}
		
		if(isset($destino) && is_array($destino)) return $destino;
		else return '<span style="color: red">** No se encuentra el texto '.$traza.' **</span>';
	}

	public function texto()
	{
		$profundidad=func_get_args();
		$destino=&$this->diccionario[$this->idioma];
		$traza=null;

		foreach($profundidad as $clave => $valor)
		{
			$destino=&$destino[$valor];
			$traza.=' : '.$valor;
		}
		
		if(isset($destino) && !is_array($destino)) return $destino;
		else return '<span style="color: red">** No se encuentra el texto '.$traza.' **</span>';
	}
}
?>
