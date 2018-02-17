<?php
class Herramientas_img
{
		//TODO: Bullshit... Esto expone la ruta del motor!!.
	public static function url_escalar($url_script, $ruta, $ancho, $alto)
	{
		return $url_script.'?modo=reescalar&src='.$ruta.'&w='.$ancho.'&h='.$alto;
	}

	public static function url_proporcion($url_script, $ruta, $ancho, $alto)
	{
		return $url_script.'?modo=reescalar_minimo&src='.$ruta.'&w='.$ancho.'&h='.$alto;
	}

	public static function url_recortar($url_script, $ruta, $ancho, $alto)
	{
		return $url_script.'?modo=reescalar_recortar&src='.$ruta.'&w='.$ancho.'&h='.$alto;
	}

	public static function generar_url($url_script, $tipo, $fuente, $ancho, $alto)
	{	
		return $url_script.'?modo='.$tipo.'&src='.$fuente.'&w='.$ancho.'&h='.$alto;
	}

	//Cambia el tamaño de una imagen...

	public static function redimensionar_imagen($ruta, $ancho, $alto, $copia=false, $formato=false, $navegador=false)
	{
		//Comprobar que existe la imagen ruta.
		if($navegador || (file_exists($ruta) && is_file($ruta)) )
		{
			//Obtener su formato.
			if(!$formato)
			{
				$formato=explode('.', $ruta);
				$formato=$formato[count($formato)-1];
			}

			//Generar una imagen en la memoria desde de la ruta.
			switch($formato)
			{
				case 'jpg': $imagen_original=imagecreatefromjpeg($ruta); break;
				case 'gif': $imagen_original=imagecreatefromgif($ruta); break;
				case 'png': $imagen_original=imagecreatefrompng($ruta); break;
				default: $imagen_original=false; break;
			}

			//Si no se ha procesado la imagen dejamos todo aquí...
			if(!$imagen_original)
			{
				$resultado=false;				
			}
			else
			{
				//Medimos la imagen original.
				list($ancho_original, $alto_original) = getimagesize($ruta);

				//Creamos el contenedor de la imagen final...
				$imagen_final=imagecreatetruecolor($ancho, $alto);

				//Redimensionar
				if(!imagecopyresampled($imagen_final, $imagen_original, 0, 0, 0, 0, $ancho, $alto, $ancho_original, $alto_original))
				{
					$resultado=false;					
				}
				else
				{
					if(!$navegador) //Salida a archivo
					{
						//Procesar copia, si procede renombramos la original.
						if($copia)
						{
							$nombre_copia=explode('/', $ruta);

							$ruta_copia=null;							

							for($i=0; $i<count($nombre_copia)-1; $i++)
							{
								$ruta_copia.=$nombre_copia[$i].'/';
							}			
	
							$ruta_copia.='copia_'.$nombre_copia[count($nombre_copia)-1];

							rename($ruta, $ruta_copia);
						}
					}
					else //Salida a navegador...
					{
						//Generamos las headers...
						switch($formato)
						{
							case 'jpg': header('Content-type: image/jpeg'); break;
							case 'gif': header('Content-type: image/gif'); break;
							case 'png': header('Content-type: image/png'); break;						
							default: die('Formato no soportado'); break;
						}

						//Deshabilitamos la ruta...
						$ruta=null; 
					}

					//Guardamos la nueva.
					switch($formato)
					{
						case 'jpg': $resultado=imagejpeg($imagen_final, $ruta); break;
						case 'gif': $resultado=imagegif($imagen_final, $ruta); break;
						case 'png': $resultado=imagepng($imagen_final, $ruta); break;					
					}
					
				}
	
				//Vaciar...
				@imagedestroy($imagen);
				@imagedestroy($imagen_final);
			}
		}
		else
		{
			$resultado=false;
		}
		
		return $resultado;
	}

	//Reescala una imagen manteniendo las proporciones hasta el máximo de
	//ancho o alto permitido. Aumentar indica si permitimos que la imagen se
	//aumente si es más pequeña.

/*
$ruta -> fuente de la imagen, por url o archivo.
$ancho -> ancho máximo deseado
$alto -> alto máximo deseado
$copia -> generar un archivo nuevo y guardar el original en disco como "copia_"
$aumentar -> reescalar aumentando el archivo si es menor que los valores datos
$navegador -> salida a navegador, no es compatible con "copia".
$minimo_domina -> establece si domina el valor menor de modo que la dimension menor será la mínima.
*/	

	public static function escalar_imagen_proporcion($ruta, $ancho, $alto, $copia=false, $aumentar=false, $navegador=false, $minimo_domina=false)
	{
		//Comprobar que existe la imagen ruta.
		if($navegador || (file_exists($ruta) && is_file($ruta)))
		{
			//Obtenemos formato...
			$formato=explode('.', $ruta);
			$formato=$formato[count($formato)-1];

			//Sólo queremos jpg, png y gif.
			if($formato=='jpg' || $formato=='png' || $formato=='gif')
			{
				//Medimos la imagen original.
				list($ancho_original, $alto_original) = getimagesize($ruta);

				$expresion=!$minimo_domina ? ($ancho_original > $alto_original) : ($ancho_original < $alto_original);

				//Vamos a ver la dimensión que domina para sacar la proporción... No se me ocurre nada mejor ahora.
				if($expresion) //Domina el ancho... o no.
				{					
					if($ancho_original > $ancho) $proporcion=$ancho/$ancho_original; //Reescalaremos para hacer más pequeña.
					else if($aumentar) $proporcion=$ancho / $ancho_original; 	//Reescalaremos para hacer mayor.
					else $proporcion=1; 						//Dejaremos la imagen como esta...
				}
				else //Domina el alto... o no. LOL.
				{
					if($alto_original > $alto) $proporcion=$alto/$alto_original; //Reescalaremos para hacer más pequeña.
					else if($aumentar) $proporcion=$alto / $alto_original; //Reescalaremos para hacer mayor.
					else $proporcion=1; 					//Dejaremos la imagen como esta...
				}

				$ancho=(int) ($ancho_original*$proporcion);
				$alto=(int) ($alto_original*$proporcion);

				//Llamar a redimensionar imagen :P.				
				$resultado=self::redimensionar_imagen($ruta, $ancho, $alto, $copia, $formato, $navegador);
			}
			else
			{			
				$resultado=false;
			}
		}
		else
		{
			$resultado=false;
		}

		return $resultado;
	}

//Genera una imagen al tamaño que se le pasa y la guarda a disco en la ruta 
//establecida por "nombre_final". 
//Nombre final sería el nombre completo, con el formato incluido...

	public static function generar_imagen_tamanho($ruta, $ancho, $alto, $nombre_final)
	{
		//Comprobar que existe la imagen ruta.
		if(file_exists($ruta) && is_file($ruta))
		{
			//Obtenemos formato...
			$formato=explode('.', $ruta);
			$formato=$formato[count($formato)-1];

			//Sólo queremos jpg...
			if($formato=='jpg')
			{
				list($ancho_original, $alto_original) = getimagesize($ruta);
				$imagen_original=imagecreatefromjpeg($ruta);
				//Creamos el contenedor de la imagen final...
				$imagen_final=imagecreatetruecolor($ancho, $alto);

				//Redimensionar
				if(!imagecopyresampled($imagen_final, $imagen_original, 0, 0, 0, 0, $ancho, $alto, $ancho_original, $alto_original))
				{
					$resultado=false;					
				}
				else
				{
					//Guardamos a disco...
					$resultado=imagejpeg($imagen_final, $nombre_final);					
				}
	
				//Vaciar...
				@imagedestroy($imagen_original);
				@imagedestroy($imagen_final);
			}
		}
		else
		{
			$resultado=false;
		}

		return $resultado;
	}

/*
$ruta -> fuente de la imagen, por url o archivo.
$ancho -> ancho máximo deseado
$alto -> alto máximo deseado
$copia -> generar un archivo nuevo y guardar el original en disco como "copia_"
$aumentar -> reescalar aumentando el archivo si es menor que los valores datos
$navegador -> salida a navegador, no es compatible con "copia".
$minimo_domina -> establece si domina el valor menor de modo que la dimension menor será la mínima.
*/	

	public static function escalar_imagen_recortar($imagen, $ancho_max_deseado, $alto_max_deseado, $ruta_en_disco=null, $a_navegador=true)
	{
		//Comprobar que existe la imagen ruta.
		if($imagen)
		{
			$proporcion=null;
			$recorte_ancho=0;
			$recorte_alto=0;
	
			$formato=explode('.', $imagen);
			$formato=$formato[count($formato)-1];
	
			switch(strtolower($formato))
			{
				case 'gif':
					$vieja_imagen =imagecreatefromgif($imagen);
				break;
	
				case 'jpg':
					$vieja_imagen = imagecreatefromjpeg($imagen);
				break;		
					default:
					die('ERROR');		
				break;
			}

			//Calcular proporcion...
			$viejo_ancho=imagesx($vieja_imagen);
			$viejo_alto=imagesy($vieja_imagen);

			$proporcion=$viejo_alto / $viejo_ancho;
			$nueva_imagen=imagecreatetruecolor($ancho_max_deseado,$alto_max_deseado);

			//Calcular qué proporción aplicar...
			if($viejo_ancho > $viejo_alto) //Apaisada
			{
				$alto_final=$alto_max_deseado;
				$ancho_final=$alto_final/$proporcion;
			}
			else if ($viejo_ancho < $viejo_alto)	//Alargada...
			{
				$ancho_final=$ancho_max_deseado;
				$alto_final=$ancho_final*$proporcion;
			}

			imagecopyresampled($nueva_imagen,$vieja_imagen,0,0,0,0,$ancho_final,$alto_final,$viejo_ancho,$viejo_alto);
			
			if($a_navegador)
			{
				switch(strtolower($formato))
				{
					case 'gif': 
						imagegif($nueva_imagen, $ruta_en_disco); 
					break;
					case 'jpg':
						imagejpeg($nueva_imagen, $ruta_en_disco); 
					break;
				}
			}
		}
		else
		{
			$resultado=false;
		}
		
		return $nueva_imagen;
	}

	public static function marcar_agua_desde_recurso($recurso_imagen, $marca_agua, $alpha=50)
	{
		$formato=explode('.', $marca_agua);
		$formato=$formato[count($formato)-1];
	
		switch(strtolower($formato))
		{
			case 'jpg':	
				$marca=imagecreatefromjpeg($marca_agua);
			break;

			case 'gif':	
				$marca=imagecreatefromgif($marca_agua);
			break;		

			case 'png':	
				$marca=imagecreatefrompng($marca_agua);
			break;		

			default:
				die('ERROR');		
			break;
		}

		//Calcular proporcion...
		$marca_ancho=imagesx($marca);
		$marca_alto=imagesy($marca);
		
		$imagen_ancho=imagesx($recurso_imagen);
		$imagen_alto=imagesy($recurso_imagen);

		$marca_compuesta=imagecreatetruecolor($imagen_ancho,$imagen_alto);

		for($x=0; $x<$imagen_ancho; $x+=$marca_ancho)
		{
			for($y=0; $y<$imagen_alto; $y+=$marca_alto)
			{
				imagecopy($marca_compuesta, $marca, $x, $y, 0, 0, $marca_ancho, $marca_alto);
			}
		}

		$blanco=imagecolorallocate($marca_compuesta, 255, 255, 255);
		imagecolortransparent($marca_compuesta, $blanco);

		$final_ancho=imagesx($marca_compuesta);
		$final_alto=imagesy($marca_compuesta);

		imagecopymerge($recurso_imagen, $marca_compuesta, 0, 0, 0, 0, $final_ancho, $final_alto, $alpha);
		
		return $recurso_imagen;
	}

	public static function recurso_desde_ruta($ruta)
	{
		$resultado=null;

		//Comprobar que existe la imagen ruta.
		if($ruta)
		{
			$formato=explode('.', $ruta);
			$formato=$formato[count($formato)-1];
	
			switch(strtolower($formato))
			{
				case 'gif':
					$resultado=imagecreatefromgif($ruta);
				break;
	
				case 'jpg':
					$resultado=imagecreatefromjpeg($ruta);
				break;		
					default:
					die('ERROR');		
				break;
			}
		}

		return $resultado;
	}

	public static function marcar_agua($ruta, $marca_agua)
	{
		$imagen=self::recurso_desde_ruta($ruta);
		if(!$imagen)
		{
			return false;
		}
		else
		{
			$resultado=self::marcar_agua_desde_recurso($imagen, $marca_agua);
		
			$formato=explode('.', $ruta);
			$formato=$formato[count($formato)-1];
	
			switch(strtolower($formato))
			{
				case 'gif':
					$resultado=imagegif($resultado, $ruta);
				break;
	
				case 'jpg':
					$resultado=imagejpeg($resultado, $ruta, 100);
				break;		
					return false;
				break;
			}
		}

		return true;
	}
};
?>
