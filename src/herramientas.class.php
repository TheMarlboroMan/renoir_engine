<?php
class Herramientas
{
	public static function obtener_url_actual($procesar_get=true, $omitir_esto=null)
	{
		$script=explode('/', $_SERVER['PHP_SELF']);
		$script=$script[count($script)-1].'?';

		if($procesar_get && isset($_GET) && count($_GET))
		{
			if(isset($omitir_esto))
			{
				if(is_array($omitir_esto)) $omitir=$omitir_esto;
				else $omitir=array($omitir_esto);
			}
			else $omitir=array();

			foreach($_GET as $clave => $valor)
			{
				if(!in_array($clave, $omitir))
				{
					$script.='&'.$clave.'='.$valor;
				}
			}
		}

		return $script;
	}

	public static function obtener_extension_archivo($ruta)
	{
		$temp=explode('.', $ruta);
		return $temp[count($temp)-1];
	}

	public static function error($texto_base, $traza=true)
	{
		$texto_error=$texto_base;
		if($traza) $texto_error.='<br />'.self::traza_error();

		trigger_error($texto_error ,E_USER_ERROR);
	}

	public static function traza_error()
	{
		$texto_error=null;
		$traza=debug_backtrace();

		foreach($traza as $clave => $valor)
		{
			$i=$clave + 1;
			$cabecera=$clave==0 ? 'En ' : 'desde ';
			$texto_error.='[#'.$i.'] '.$cabecera.' '.$valor["file"].': '.$valor["line"].'<br/>';
		}

		return $texto_error;
	}

	public static function texto_url($texto, $caracter_no_legible='-')
	{
		$buscar=" \xc0\xc1\xc2\xc3\xc4\xc5\xe0\xe1\xe2\xe3\xe4\xe5\xd2\xd3\xd4\xd5\xd6\xd8\xf2\xf3\xf4\xf5\xf6\xf8\xc8\xc9\xca\xcb\xe8\xe9\xea\xeb\xc7\xe7\xcc\xcd\xce\xcf\xec\xed\xee\xef\xd9\xda\xdb\xdc\xf9\xfa\xfb\xfc\xff\xd1\xf1_.,/:-?!ªº";
		$reempl=$caracter_no_legible."AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn".$caracter_no_legible.$caracter_no_legible.$caracter_no_legible.$caracter_no_legible.$caracter_no_legible.$caracter_no_legible.$caracter_no_legible.$caracter_no_legible."AO";
		return strtr($texto, $buscar, $reempl);
	}

	public static function comprobar_fecha($fecha)	//En formato inglés...
	{
		$valores=explode('-', $fecha);
		return checkdate($valores[1], $valores[2], $valores[0]);
	}

	public static function comprobar_fecha_alt($fecha)	//En formato dd-mm-aaaa...
	{
		return self::comprobar_fecha(self::invertir_fecha($fecha));
	}
		
	public static function invertir_fecha($objeto)
	{
		if($objeto=='') return '';
		else
		{
			$fecha=explode("-", $objeto);
			if(count($fecha)) return $fecha[2].'-'.$fecha[1].'-'.$fecha[0];		
			else return null;
		}
	}

	//Calcula el tiempo unix entre los dos momentos. La fecha debe estar
	//en formato YYYY-MM-DD. La hora en hh:mm:ss.
	public static function calcular_tiempo($fecha_ini, $hora_ini='00:00:00', $fecha_fin, $hora_fin='00:00:00')
	{
		$f_inicio=explode('-', $fecha_ini);
		$h_inicio=explode(':', $hora_ini);
		$f_fin=explode('-', $fecha_fin);
		$h_fin=explode(':', $hora_fin);

		$tiempo_inicio=mktime($h_inicio[0], $h_inicio[1], $h_inicio[2], $f_inicio[1], $f_inicio[2], $f_inicio[0]);
		$tiempo_fin=mktime($h_fin[0], $h_fin[1], $h_fin[2], $f_fin[1], $f_fin[2], $f_fin[0]);

		return $tiempo_fin-$tiempo_inicio;
	}

	public static function calcular_tiempo_fechas($fecha_ini, $fecha_fin)
	{
		$f_inicio=explode('-', $fecha_ini);
		$f_fin=explode('-', $fecha_fin);
		$tiempo_inicio=mktime(0, 0, 0, $f_inicio[1], $f_inicio[2], $f_inicio[0]);
		$tiempo_fin=mktime(0, 0, 0, $f_fin[1], $f_fin[2], $f_fin[0]);

		return $tiempo_fin-$tiempo_inicio;
	}

	//Recibe un tiempo unix y lo devuelve en minutos. El tiempo unix está en segundos.
	public static function unix_a_minutos($tiempo_unix)
	{
		return ceil($tiempo_unix / 60 );
	}

	//En formatos YYYY-MM-DD devuelve una diferencia de años entre dos fechas. Típico "edad desde fecha de nacimiento".
	public static function diferencia_anhos_fechas($mayor, $menor)
	{
		$mayor_e=explode('-', $mayor);
		$menor_e=explode('-', $menor);

		$anho_dif = $mayor_e[0] - $menor_e[0];
		$mes_dif = $mayor_e[1] - $menor_e[1];
		$dia_dif = $mayor_e[2] - $menor_e[2];

		if ($dia_dif < 0 || $mes_dif < 0)
		{
			$anho_dif--;
		}
		
		return $anho_dif;	
	}

	public static function cadena_max($cadena, $max=30, $suspensivos=false, $estricto=false)	//Suspensivos es lo que pone para limitar el texto como [mas], [...]... Estricto es si corta exactamente al número de caracteres "max".
	{	
		if(!$estricto)
		{
			//Vamos a localizar donde, a partir de max, se encuentra el primer espacio o al fin de la cadena...
			for($max; $max < strlen($cadena) && ord($cadena[$max])!=32; $max++);
		}

		if(!$suspensivos) $suspensivos='...';	
		
		if(strlen($cadena) > $max) 
		{
			$resultado=substr($cadena, 0, $max).$suspensivos;
		}
		else 
		{
			$resultado=$cadena;
		}
	
		return $resultado;
	}	

	public static function reemplazar_primera_ocurrencia($aguja, $reemplazo, $pajar)
	{   
		$posicion=strpos($pajar, $aguja);

		if ($posicion === false) 
		{       
			return $pajar;
		}
		else
		{
			return substr_replace($pajar, $reemplazo, $posicion, strlen($aguja));
		}
	}  

	public static function subir_archivo_ext($archivo, $destino, $ext, $tamanho, $estricto=false){return self::subir_archivo_extension($archivo, $destino, $ext, $tamanho, $estricto);}
	public static function subir_archivo_extension($archivo, $destino, $ext, $tamanho, $estricto=false)	//Extensiones debe ser una cadena con cada extensión separada por un punto 'jpg.bmp.png';.. subir_archivo_ext($_FILES['imagen'], $archivo, 'jpg.bmp.gif', '300');
	{	
		if(isset($archivo) && ($archivo['name'] != '' || $archivo['size']))
		{								
			if($archivo['error']) throw new Excepcion_fichero(-3);
			else
			{
				$extensiones=explode('.', $ext);	//Separamos las extensiones en un array.
				foreach($extensiones as $clave => $valor)
				{
					$extensiones[$clave]=strtolower($valor);
				}
				
				$tamanho=$tamanho*1024;	//El tamaño viene expresado en kb.
				$extension=explode('.', $archivo['name']);	//Obtenemos la extensión del archivo.
				$extension=strtolower($extension[count($extension)-1]);
	
				if($archivo['size']>0 && $archivo['size']<$tamanho) //Comprobamos que hay archivo (>0) y que es menor del tamaño.
				{	
					if(in_array(strtolower($extension), $extensiones))											
					{

						$destino.='.'.$extension;	//Añadimos la extension...

						if(move_uploaded_file($archivo['tmp_name'] , $destino)) //Hacemos el intento de subir.
						{							
							chmod($destino, 0777);
							$resultado=strtolower($extension);	//Si podemos subir devolvemos algo, en este caso la extensión.
						} 
						else throw new Excepcion_fichero(-1);
					}			
					else throw new Excepcion_fichero(-5);	
				}		
				else throw new Excepcion_fichero(-2);
			}
		}	
		else if($estricto) throw new Excepcion_fichero(-4);
		else $resultado=null;
		
		return $resultado;
	}

	static public function subir_archivo_libre($archivo, $destino, $tamanho, $estricto=false)
	{		
		//Comprobamos que existe...
		if($archivo['name'] != '' || $archivo['size'])					
		{								
			if($archivo['error']) throw new Excepcion_fichero(-3);
			else
			{
				$tamanho=$tamanho*1024;	//El tamaño viene expresado en kb.
				$extension=explode('.', $archivo['name']);	//Obtenemos la extensión del archivo.
				$extension=$extension[count($extension)-1];
	
				if($archivo['size']>0 && $archivo['size']<$tamanho) //Comprobamos que hay archivo (>0) y que es menor del tamaño.
				{				
					$destino.='.'.$extension;	//Añadimos la extensión al archivo...								
				
					if(move_uploaded_file($archivo['tmp_name'] , $destino)) //Hacemos el intento de subir.
					{				
						chmod($destino, 0777);
						$resultado=$extension;	//Si podemos subir devolvemos algo, en este caso la extensión.
					} 		
					else throw new Excepcion_fichero(-1);
				}	
				else throw new Excepcion_fichero(-2);
			}
		}
		else if($estricto) throw new Excepcion_fichero(-4);
		else $resultado=null;

		return $resultado; 
	}

	public static function redireccionar($ruta)
	{
		header('location: '.$ruta);
		die();
	}

	
	public static function evalua_selected($expresion, $marca='selected')
	{	
		if($expresion===true) 
		{
			return $marca;
		}
		else 
		{
			return '';
		}
	}	

	public static function obtener_ip_usuario()
	{
		//Sacamos la ip del usuario...
		if($_SERVER) 
		{
			if(isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && $_SERVER["HTTP_X_FORWARDED_FOR"])
			{
				$resultado=explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"]);
				$resultado=trim($resultado[0]);
			}
			else if(isset($_SERVER["HTTP_CLIENT_IP"]) && $_SERVER["HTTP_CLIENT_IP"]) 
			{
				$resultado=$_SERVER["HTTP_CLIENT_IP"];		
			}
			else 	
			{
				$resultado=$_SERVER["REMOTE_ADDR"];		
			}
		} 
		else 
		{
			if(getenv('HTTP_X_FORWARDED_FOR')) 
			{
				//$resultado=getenv('HTTP_X_FORWARDED_FOR');
				$resultado=explode(",", getenv('HTTP_X_FORWARDED_FOR'));
				$resultado=trim($resultado[0]);
			}		
			else if(getenv('HTTP_CLIENT_IP')) 
			{
				$resultado=getenv('HTTP_CLIENT_IP');
			}
			else 
			{
				$$resultado=getenv('REMOTE_ADDR');
			}
		}

		return $resultado;
	}	

	public static function determinar_xtype($ext)
	{
		switch($ext)
		{
			case "dwg": return "application/acad"; break;
			case "arj": return "application/arj"; break;
			case "mm": return "application/base64"; break;
			case "mme": return "application/base64"; break;
			case "hqx": return "application/binhex"; break;
			case "hqx": return "application/binhex4"; break;
			case "boo": return "application/book"; break;
			case "book": return "application/book"; break;
			case "cdf": return "application/cdf"; break;
			case "ccad": return "application/clariscad"; break;
			case "dp": return "application/commonground"; break;
			case "drw": return "application/drafting"; break;
			case "tsp": return "application/dsptype"; break;
			case "dxf": return "application/dxf"; break;
			case "evy": return "application/envoy"; break;
			case "xl": return "application/excel"; break;
			case "xla": return "application/excel"; break;
			case "xlb": return "application/excel"; break;
			case "xlc": return "application/excel"; break;
			case "xld": return "application/excel"; break;
			case "xlk": return "application/excel"; break;
			case "xll": return "application/excel"; break;
			case "xlm": return "application/excel"; break;
			case "xls": return "application/excel"; break;
			case "xlt": return "application/excel"; break;
			case "xlv": return "application/excel"; break;
			case "xlw": return "application/excel"; break;
			case "fif": return "application/fractals"; break;
			case "frl": return "application/freeloader"; break;
			case "spl": return "application/futuresplash"; break;
			case "tgz": return "application/gnutar"; break;
			case "vew": return "application/groupwise"; break;
			case "hlp": return "application/hlp"; break;
			case "hta": return "application/hta"; break;
			case "unv": return "application/i-deas"; break;
			case "iges": return "application/iges"; break;
			case "igs": return "application/iges"; break;
			case "inf": return "application/inf"; break;
			case "clas": return "application/java"; break;
			case "clas": return "application/java-byte-code"; break;
			case "lha": return "application/lha"; break;
			case "lzx": return "application/lzx"; break;
			case "bin": return "application/mac-binary"; break;
			case "hqx": return "application/mac-binhex"; break;
			case "hqx": return "application/mac-binhex40"; break;
			case "cpt": return "application/mac-compactpro"; break;
			case "bin": return "application/macbinary"; break;
			case "mrc": return "application/marc"; break;
			case "mbd": return "application/mbedlet"; break;
			case "mcd": return "application/mcad"; break;
			case "aps": return "application/mime"; break;
			case "pot": return "application/mspowerpoint"; break;
			case "pps": return "application/mspowerpoint"; break;
			case "ppt": return "application/mspowerpoint"; break;
			case "ppz": return "application/mspowerpoint"; break;
			case "doc": return "application/msword"; break;
			case "dot": return "application/msword"; break;
			case "w6w": return "application/msword"; break;
			case "wiz": return "application/msword"; break;
			case "word": return "application/msword"; break;
			case "wri": return "application/mswrite"; break;
			case "mcp": return "application/netmc"; break;
			case "a": return "application/octet-stream"; break;
			case "arc": return "application/octet-stream"; break;
			case "arj": return "application/octet-stream"; break;
			case "bin": return "application/octet-stream"; break;
			case "com": return "application/octet-stream"; break;
			case "dump": return "application/octet-stream"; break;
			case "exe": return "application/octet-stream"; break;
			case "lha": return "application/octet-stream"; break;
			case "lhx": return "application/octet-stream"; break;
			case "lzh": return "application/octet-stream"; break;
			case "lzx": return "application/octet-stream"; break;
			case "o": return "application/octet-stream"; break;
			case "psd": return "application/octet-stream"; break;
			case "save": return "application/octet-stream"; break;
			case "uu": return "application/octet-stream"; break;
			case "zoo": return "application/octet-stream"; break;
			case "oda": return "application/oda"; break;
			case "pdf": return "application/pdf"; break;
			case "p12": return "application/pkcs-12"; break;
			case "crl": return "application/pkcs-crl"; break;
			case "p10": return "application/pkcs10"; break;
			case "p7c": return "application/pkcs7-mime"; break;
			case "p7m": return "application/pkcs7-mime"; break;
			case "p7s": return "application/pkcs7-signature"; break;
			case "cer": return "application/pkix-cert"; break;
			case "crt": return "application/pkix-cert"; break;
			case "crl": return "application/pkix-crl"; break;
			case "text": return "application/plain"; break;
			case "ai": return "application/postscript"; break;
			case "eps": return "application/postscript"; break;
			case "ps": return "application/postscript"; break;
			case "ppt": return "application/powerpoint"; break;
			case "part": return "application/pro_eng"; break;
			case "prt": return "application/pro_eng"; break;
			case "rng": return "application/ringing-tones"; break;
			case "rtf": return "application/rtf"; break;
			case "rtx": return "application/rtf"; break;
			case "sdp": return "application/sdp"; break;
			case "sea": return "application/sea"; break;
			case "set": return "application/set"; break;
			case "stl": return "application/sla"; break;
			case "smi": return "application/smil"; break;
			case "smil": return "application/smil"; break;
			case "sol": return "application/solids"; break;
			case "sdr": return "application/sounder"; break;
			case "step": return "application/step"; break;
			case "stp": return "application/step"; break;
			case "ssm": return "application/streamingmedia"; break;
			case "tbk": return "application/toolbook"; break;
			case "vda": return "application/vda"; break;
			case "fdf": return "application/vnd.fdf"; break;
			case "hgl": return "application/vnd.hp-hpgl"; break;
			case "hpg": return "application/vnd.hp-hpgl"; break;
			case "hpgl": return "application/vnd.hp-hpgl"; break;
			case "pcl": return "application/vnd.hp-pcl"; break;
			case "xlb": return "application/vnd.ms-excel"; break;
			case "xlc": return "application/vnd.ms-excel"; break;
			case "xll": return "application/vnd.ms-excel"; break;
			case "xlm": return "application/vnd.ms-excel"; break;
			case "xls": return "application/vnd.ms-excel"; break;
			case "xlw": return "application/vnd.ms-excel"; break;
			case "sst": return "application/vnd.ms-pki.certstore"; break;
			case "pko": return "application/vnd.ms-pki.pko"; break;
			case "cat": return "application/vnd.ms-pki.seccat"; break;
			case "stl": return "application/vnd.ms-pki.stl"; break;
			case "pot": return "application/vnd.ms-powerpoint"; break;
			case "ppa": return "application/vnd.ms-powerpoint"; break;
			case "pps": return "application/vnd.ms-powerpoint"; break;
			case "ppt": return "application/vnd.ms-powerpoint"; break;
			case "pwz": return "application/vnd.ms-powerpoint"; break;
			case "mpp": return "application/vnd.ms-project"; break;
			case "ncm": return "application/vnd.nokia.configuration-message"; break;
			case "rng": return "application/vnd.nokia.ringing-tone"; break;
			case "rm": return "application/vnd.rn-realmedia"; break;
			case "rnx": return "application/vnd.rn-realplayer"; break;
			case "wmlc": return "application/vnd.wap.wmlc"; break;
			case "wmls": return "application/vnd.wap.wmlscriptc"; break;
			case "web": return "application/vnd.xara"; break;
			case "vmd": return "application/vocaltec-media-desc"; break;
			case "vmf": return "application/vocaltec-media-file"; break;
			case "wp": return "application/wordperfect"; break;
			case "wp5": return "application/wordperfect"; break;
			case "wp6": return "application/wordperfect"; break;
			case "wpd": return "application/wordperfect"; break;
			case "w60": return "application/wordperfect6.0"; break;
			case "wp5": return "application/wordperfect6.0"; break;
			case "w61": return "application/wordperfect6.1"; break;
			case "wk1": return "application/x-123"; break;
			case "aim": return "application/x-aim"; break;
			case "aab": return "application/x-authorware-bin"; break;
			case "aam": return "application/x-authorware-map"; break;
			case "aas": return "application/x-authorware-seg"; break;
			case "bcpi": return "application/x-bcpio"; break;
			case "bin": return "application/x-binary"; break;
			case "hqx": return "application/x-binhex40"; break;
			case "bsh": return "application/x-bsh"; break;
			case "sh": return "application/x-bsh"; break;
			case "shar": return "application/x-bsh"; break;
			case "elc": return "application/x-bytecode.elisp"; break;
			case "pyc": return "application/x-bytecode.python"; break;
			case "bz": return "application/x-bzip"; break;
			case "boz": return "application/x-bzip2"; break;
			case "bz2": return "application/x-bzip2"; break;
			case "cdf": return "application/x-cdf"; break;
			case "vcd": return "application/x-cdlink"; break;
			case "cha": return "application/x-chat"; break;
			case "chat": return "application/x-chat"; break;
			case "ras": return "application/x-cmu-raster"; break;
			case "cco": return "application/x-cocoa"; break;
			case "cpt": return "application/x-compactpro"; break;
			case "z": return "application/x-compress"; break;
			case "gz": return "application/x-compressed"; break;
			case "tgz": return "application/x-compressed"; break;
			case "z": return "application/x-compressed"; break;
			case "zip": return "application/x-compressed"; break;
			case "nsc": return "application/x-conference"; break;
			case "cpio": return "application/x-cpio"; break;
			case "cpt": return "application/x-cpt"; break;
			case "csh": return "application/x-csh"; break;
			case "deep": return "application/x-deepv"; break;
			case "dcr": return "application/x-director"; break;
			case "dir": return "application/x-director"; break;
			case "dxr": return "application/x-director"; break;
			case "dvi": return "application/x-dvi"; break;
			case "elc": return "application/x-elc"; break;
			case "env": return "application/x-envoy"; break;
			case "evy": return "application/x-envoy"; break;
			case "es": return "application/x-esrehber"; break;
			case "xla": return "application/x-excel"; break;
			case "xlb": return "application/x-excel"; break;
			case "xlc": return "application/x-excel"; break;
			case "xld": return "application/x-excel"; break;
			case "xlk": return "application/x-excel"; break;
			case "xll": return "application/x-excel"; break;
			case "xlm": return "application/x-excel"; break;
			case "xls": return "application/x-excel"; break;
			case "xlt": return "application/x-excel"; break;
			case "xlv": return "application/x-excel"; break;
			case "xlw": return "application/x-excel"; break;
			case "mif": return "application/x-frame"; break;
			case "pre": return "application/x-freelance"; break;
			case "gsp": return "application/x-gsp"; break;
			case "gss": return "application/x-gss"; break;
			case "gtar": return "application/x-gtar"; break;
			case "gz": return "application/x-gzip"; break;
			case "gzip": return "application/x-gzip"; break;
			case "hdf": return "application/x-hdf"; break;
			case "help": return "application/x-helpfile"; break;
			case "hlp": return "application/x-helpfile"; break;
			case "imap": return "application/x-httpd-imap"; break;
			case "ima": return "application/x-ima"; break;
			case "ins": return "application/x-internett-signup"; break;
			case "iv": return "application/x-inventor"; break;
			case "ip": return "application/x-ip2"; break;
			case "clas": return "application/x-java-class"; break;
			case "jcm": return "application/x-java-commerce"; break;
			case "js": return "application/x-javascript"; break;
			case "skd": return "application/x-koan"; break;
			case "skm": return "application/x-koan"; break;
			case "skp": return "application/x-koan"; break;
			case "skt": return "application/x-koan"; break;
			case "ksh": return "application/x-ksh"; break;
			case "late": return "application/x-latex"; break;
			case "ltx": return "application/x-latex"; break;
			case "lha": return "application/x-lha"; break;
			case "lsp": return "application/x-lisp"; break;
			case "ivy": return "application/x-livescreen"; break;
			case "wq1": return "application/x-lotus"; break;
			case "scm": return "application/x-lotusscreencam"; break;
			case "lzh": return "application/x-lzh"; break;
			case "lzx": return "application/x-lzx"; break;
			case "hqx": return "application/x-mac-binhex40"; break;
			case "bin": return "application/x-macbinary"; break;
			case "mc$": return "application/x-magic-cap-package-1.0"; break;
			case "mcd": return "application/x-mathcad"; break;
			case "mm": return "application/x-meme"; break;
			case "mid": return "application/x-midi"; break;
			case "midi": return "application/x-midi"; break;
			case "mif": return "application/x-mif"; break;
			case "nix": return "application/x-mix-transfer"; break;
			case "asx": return "application/x-mplayer2"; break;
			case "xla": return "application/x-msexcel"; break;
			case "xls": return "application/x-msexcel"; break;
			case "xlw": return "application/x-msexcel"; break;
			case "ppt": return "application/x-mspowerpoint"; break;
			case "ani": return "application/x-navi-animation"; break;
			case "nvd": return "application/x-navidoc"; break;
			case "map": return "application/x-navimap"; break;
			case "stl": return "application/x-navistyle"; break;
			case "cdf": return "application/x-netcdf"; break;
			case "nc": return "application/x-netcdf"; break;
			case "pkg": return "application/x-newton-compatible-pkg"; break;
			case "aos": return "application/x-nokia-9000-communicator-add-on-softw"; break;
			case "omc": return "application/x-omc"; break;
			case "omcd": return "application/x-omcdatamaker"; break;
			case "omcr": return "application/x-omcregerator"; break;
			case "pm4": return "application/x-pagemaker"; break;
			case "pm5": return "application/x-pagemaker"; break;
			case "pcl": return "application/x-pcl"; break;
			case "plx": return "application/x-pixclscript"; break;
			case "p10": return "application/x-pkcs10"; break;
			case "p12": return "application/x-pkcs12"; break;
			case "spc": return "application/x-pkcs7-certificates"; break;
			case "p7r": return "application/x-pkcs7-certreqresp"; break;
			case "p7c": return "application/x-pkcs7-mime"; break;
			case "p7m": return "application/x-pkcs7-mime"; break;
			case "p7a": return "application/x-pkcs7-signature"; break;
			case "css": return "application/x-pointplus"; break;
			case "pnm": return "application/x-portable-anymap"; break;
			case "mpc": return "application/x-project"; break;
			case "mpt": return "application/x-project"; break;
			case "mpv": return "application/x-project"; break;
			case "mpx": return "application/x-project"; break;
			case "wb1": return "application/x-qpro"; break;
			case "rtf": return "application/x-rtf"; break;
			case "sdp": return "application/x-sdp"; break;
			case "sea": return "application/x-sea"; break;
			case "sl": return "application/x-seelogo"; break;
			case "sh": return "application/x-sh"; break;
			case "sh": return "application/x-shar"; break;
			case "shar": return "application/x-shar"; break;
			case "swf": return "application/x-shockwave-flash"; break;
			case "sit": return "application/x-sit"; break;
			case "spr": return "application/x-sprite"; break;
			case "spri": return "application/x-sprite"; break;
			case "sit": return "application/x-stuffit"; break;
			case "sv4c": return "application/x-sv4cpio"; break;
			case "sv4c": return "application/x-sv4crc"; break;
			case "tar": return "application/x-tar"; break;
			case "sbk": return "application/x-tbook"; break;
			case "tbk": return "application/x-tbook"; break;
			case "tcl": return "application/x-tcl"; break;
			case "tex": return "application/x-tex"; break;
			case "texi": return "application/x-texinfo"; break;
			case "roff": return "application/x-troff"; break;
			case "t": return "application/x-troff"; break;
			case "tr": return "application/x-troff"; break;
			case "man": return "application/x-troff-man"; break;
			case "me": return "application/x-troff-me"; break;
			case "ms": return "application/x-troff-ms"; break;
			case "avi": return "application/x-troff-msvideo"; break;
			case "usta": return "application/x-ustar"; break;
			case "vsd": return "application/x-visio"; break;
			case "vst": return "application/x-visio"; break;
			case "vsw": return "application/x-visio"; break;
			case "mzz": return "application/x-vnd.audioexplosion.mzz"; break;
			case "xpix": return "application/x-vnd.ls-xpix"; break;
			case "vrml": return "application/x-vrml"; break;
			case "src": return "application/x-wais-source"; break;
			case "wsrc": return "application/x-wais-source"; break;
			case "hlp": return "application/x-winhelp"; break;
			case "wtk": return "application/x-wintalk"; break;
			case "svr": return "application/x-world"; break;
			case "wrl": return "application/x-world"; break;
			case "wpd": return "application/x-wpwin"; break;
			case "wri": return "application/x-wri"; break;
			case "cer": return "application/x-x509-ca-cert"; break;
			case "crt": return "application/x-x509-ca-cert"; break;
			case "der": return "application/x-x509-ca-cert"; break;
			case "crt": return "application/x-x509-user-cert"; break;
			case "zip": return "application/x-zip-compressed"; break;
			case "xml": return "application/xml"; break;
			case "zip": return "application/zip"; break;
			case "aif": return "audio/aiff"; break;
			case "aifc": return "audio/aiff"; break;
			case "aiff": return "audio/aiff"; break;
			case "au": return "audio/basic"; break;
			case "snd": return "audio/basic"; break;
			case "it": return "audio/it"; break;
			case "funk": return "audio/make"; break;
			case "my": return "audio/make"; break;
			case "pfun": return "audio/make"; break;
			case "pfun": return "audio/make.my.funk"; break;
			case "rmi": return "audio/mid"; break;
			case "kar": return "audio/midi"; break;
			case "mid": return "audio/midi"; break;
			case "midi": return "audio/midi"; break;
			case "mod": return "audio/mod"; break;
			case "m2a": return "audio/mpeg"; break;
			case "mp2": return "audio/mpeg"; break;
			case "mpa": return "audio/mpeg"; break;
			case "mpg": return "audio/mpeg"; break;
			case "mpga": return "audio/mpeg"; break;
			case "mp3": return "audio/mpeg3"; break;
			case "la": return "audio/nspaudio"; break;
			case "lma": return "audio/nspaudio"; break;
			case "s3m": return "audio/s3m"; break;
			case "tsi": return "audio/tsp-audio"; break;
			case "tsp": return "audio/tsplayer"; break;
			case "qcp": return "audio/vnd.qcelp"; break;
			case "voc": return "audio/voc"; break;
			case "vox": return "audio/voxware"; break;
			case "wav": return "audio/wav"; break;
			case "snd": return "audio/x-adpcm"; break;
			case "aif": return "audio/x-aiff"; break;
			case "aifc": return "audio/x-aiff"; break;
			case "aiff": return "audio/x-aiff"; break;
			case "au": return "audio/x-au"; break;
			case "gsd": return "audio/x-gsm"; break;
			case "gsm": return "audio/x-gsm"; break;
			case "jam": return "audio/x-jam"; break;
			case "lam": return "audio/x-liveaudio"; break;
			case "mid": return "audio/x-mid"; break;
			case "midi": return "audio/x-mid"; break;
			case "mid": return "audio/x-midi"; break;
			case "midi": return "audio/x-midi"; break;
			case "mod": return "audio/x-mod"; break;
			case "mp2": return "audio/x-mpeg"; break;
			case "mp3": return "audio/x-mpeg-3"; break;
			case "m3u": return "audio/x-mpequrl"; break;
			case "la": return "audio/x-nspaudio"; break;
			case "lma": return "audio/x-nspaudio"; break;
			case "ra": return "audio/x-pn-realaudio"; break;
			case "ram": return "audio/x-pn-realaudio"; break;
			case "rm": return "audio/x-pn-realaudio"; break;
			case "rmm": return "audio/x-pn-realaudio"; break;
			case "rmp": return "audio/x-pn-realaudio"; break;
			case "ra": return "audio/x-pn-realaudio-plugin"; break;
			case "rmp": return "audio/x-pn-realaudio-plugin"; break;
			case "rpm": return "audio/x-pn-realaudio-plugin"; break;
			case "sid": return "audio/x-psid"; break;
			case "ra": return "audio/x-realaudio"; break;
			case "vqf": return "audio/x-twinvq"; break;
			case "vqe": return "audio/x-twinvq-plugin"; break;
			case "vql": return "audio/x-twinvq-plugin"; break;
			case "mjf": return "audio/x-vnd.audioexplosion.mjuicemediafile"; break;
			case "voc": return "audio/x-voc"; break;
			case "wav": return "audio/x-wav"; break;
			case "xm": return "audio/xm"; break;
			case "pdb": return "chemical/x-pdb"; break;
			case "xyz": return "chemical/x-pdb"; break;
			case "dwf": return "drawing/x-dwf"; break;
			case "ivr": return "i-world/i-vrml"; break;
			case "bm": return "image/bmp"; break;
			case "bmp": return "image/bmp"; break;
			case "ras": return "image/cmu-raster"; break;
			case "rast": return "image/cmu-raster"; break;
			case "fif": return "image/fif"; break;
			case "flo": return "image/florian"; break;
			case "turb": return "image/florian"; break;
			case "g3": return "image/g3fax"; break;
			case "gif": return "image/gif"; break;
			case "ief": return "image/ief"; break;
			case "iefs": return "image/ief"; break;
			case "jfif": return "image/jpeg"; break;
			case "jpe": return "image/jpeg"; break;
			case "jpeg": return "image/jpeg"; break;
			case "jpg": return "image/jpeg"; break;
			case "jut": return "image/jutvision"; break;
			case "nap": return "image/naplps"; break;
			case "napl": return "image/naplps"; break;
			case "pic": return "image/pict"; break;
			case "pict": return "image/pict"; break;
			case "jfif": return "image/pjpeg"; break;
			case "jpe": return "image/pjpeg"; break;
			case "jpeg": return "image/pjpeg"; break;
			case "jpg": return "image/pjpeg"; break;
			case "png": return "image/png"; break;
			case "x-pn": return "image/png"; break;
			case "tif": return "image/tiff"; break;
			case "tiff": return "image/tiff"; break;
			case "mcf": return "image/vasa"; break;
			case "dwg": return "image/vnd.dwg"; break;
			case "dxf": return "image/vnd.dwg"; break;
			case "svf": return "image/vnd.dwg"; break;
			case "fpx": return "image/vnd.fpx"; break;
			case "fpx": return "image/vnd.net-fpx"; break;
			case "rf": return "image/vnd.rn-realflash"; break;
			case "rp": return "image/vnd.rn-realpix"; break;
			case "wbmp": return "image/vnd.wap.wbmp"; break;
			case "xif": return "image/vnd.xiff"; break;
			case "ras": return "image/x-cmu-raster"; break;
			case "dwg": return "image/x-dwg"; break;
			case "dxf": return "image/x-dwg"; break;
			case "svf": return "image/x-dwg"; break;
			case "ico": return "image/x-icon"; break;
			case "art": return "image/x-jg"; break;
			case "jps": return "image/x-jps"; break;
			case "nif": return "image/x-niff"; break;
			case "niff": return "image/x-niff"; break;
			case "pcx": return "image/x-pcx"; break;
			case "pct": return "image/x-pict"; break;
			case "pnm": return "image/x-portable-anymap"; break;
			case "pbm": return "image/x-portable-bitmap"; break;
			case "pgm": return "image/x-portable-graymap"; break;
			case "pgm": return "image/x-portable-greymap"; break;
			case "ppm": return "image/x-portable-pixmap"; break;
			case "qif": return "image/x-quicktime"; break;
			case "qti": return "image/x-quicktime"; break;
			case "qtif": return "image/x-quicktime"; break;
			case "rgb": return "image/x-rgb"; break;
			case "tif": return "image/x-tiff"; break;
			case "tiff": return "image/x-tiff"; break;
			case "bmp": return "image/x-windows-bmp"; break;
			case "xbm": return "image/x-xbitmap"; break;
			case "xbm": return "image/x-xbm"; break;
			case "pm": return "image/x-xpixmap"; break;
			case "xpm": return "image/x-xpixmap"; break;
			case "xwd": return "image/x-xwd"; break;
			case "xwd": return "image/x-xwindowdump"; break;
			case "xbm": return "image/xbm"; break;
			case "xpm": return "image/xpm"; break;
			case "mht": return "message/rfc822"; break;
			case "mhtm": return "message/rfc822"; break;
			case "mime": return "message/rfc822"; break;
			case "iges": return "model/iges"; break;
			case "igs": return "model/iges"; break;
			case "dwf": return "model/vnd.dwf"; break;
			case "vrml": return "model/vrml"; break;
			case "wrl": return "model/vrml"; break;
			case "wrz": return "model/vrml"; break;
			case "pov": return "model/x-pov"; break;
			case "gzip": return "multipart/x-gzip"; break;
			case "usta": return "multipart/x-ustar"; break;
			case "zip": return "multipart/x-zip"; break;
			case "mid": return "music/crescendo"; break;
			case "midi": return "music/crescendo"; break;
			case "kar": return "music/x-karaoke"; break;
			case "pvu": return "paleovu/x-pv"; break;
			case "asp": return "text/asp"; break;
			case "css": return "text/css"; break;
			case "acgi": return "text/html"; break;
			case "htm": return "text/html"; break;
			case "html": return "text/html"; break;
			case "htx": return "text/html"; break;
			case "shtm": return "text/html"; break;
			case "mcf": return "text/mcf"; break;
			case "pas": return "text/pascal"; break;
			case "c": return "text/plain"; break;
			case "c++": return "text/plain"; break;
			case "cc": return "text/plain"; break;
			case "com": return "text/plain"; break;
			case "conf": return "text/plain"; break;
			case "cxx": return "text/plain"; break;
			case "def": return "text/plain"; break;
			case "f": return "text/plain"; break;
			case "f90": return "text/plain"; break;
			case "for": return "text/plain"; break;
			case "g": return "text/plain"; break;
			case "h": return "text/plain"; break;
			case "hh": return "text/plain"; break;
			case "idc": return "text/plain"; break;
			case "jav": return "text/plain"; break;
			case "java": return "text/plain"; break;
			case "list": return "text/plain"; break;
			case "log": return "text/plain"; break;
			case "lst": return "text/plain"; break;
			case "m": return "text/plain"; break;
			case "mar": return "text/plain"; break;
			case "pl": return "text/plain"; break;
			case "sdml": return "text/plain"; break;
			case "text": return "text/plain"; break;
			case "txt": return "text/plain"; break;
			case "rt": return "text/richtext"; break;
			case "rtf": return "text/richtext"; break;
			case "rtx": return "text/richtext"; break;
			case "wsc": return "text/scriplet"; break;
			case "sgm": return "text/sgml"; break;
			case "sgml": return "text/sgml"; break;
			case "tsv": return "text/tab-separated-values"; break;
			case "uni": return "text/uri-list"; break;
			case "unis": return "text/uri-list"; break;
			case "uri": return "text/uri-list"; break;
			case "uris": return "text/uri-list"; break;
			case "abc": return "text/vnd.abc"; break;
			case "flx": return "text/vnd.fmi.flexstor"; break;
			case "rt": return "text/vnd.rn-realtext"; break;
			case "wml": return "text/vnd.wap.wml"; break;
			case "wmls": return "text/vnd.wap.wmlscript"; break;
			case "htt": return "text/webviewhtml"; break;
			case "asm": return "text/x-asm"; break;
			case "s": return "text/x-asm"; break;
			case "aip": return "text/x-audiosoft-intra"; break;
			case "c": return "text/x-c"; break;
			case "cc": return "text/x-c"; break;
			case "cpp": return "text/x-c"; break;
			case "htc": return "text/x-component"; break;
			case "f": return "text/x-fortran"; break;
			case "f77": return "text/x-fortran"; break;
			case "f90": return "text/x-fortran"; break;
			case "for": return "text/x-fortran"; break;
			case "h": return "text/x-h"; break;
			case "hh": return "text/x-h"; break;
			case "jav": return "text/x-java-source"; break;
			case "java": return "text/x-java-source"; break;
			case "lsx": return "text/x-la-asf"; break;
			case "m": return "text/x-m"; break;
			case "p": return "text/x-pascal"; break;
			case "hlb": return "text/x-script"; break;
			case "csh": return "text/x-script.csh"; break;
			case "el": return "text/x-script.elisp"; break;
			case "scm": return "text/x-script.guile"; break;
			case "ksh": return "text/x-script.ksh"; break;
			case "lsp": return "text/x-script.lisp"; break;
			case "pl": return "text/x-script.perl"; break;
			case "pm": return "text/x-script.perl-module"; break;
			case "py": return "text/x-script.phyton"; break;
			case "rexx": return "text/x-script.rexx"; break;
			case "scm": return "text/x-script.scheme"; break;
			case "sh": return "text/x-script.sh"; break;
			case "tcl": return "text/x-script.tcl"; break;
			case "tcsh": return "text/x-script.tcsh"; break;
			case "zsh": return "text/x-script.zsh"; break;
			case "shtm": return "text/x-server-parsed-html"; break;
			case "ssi": return "text/x-server-parsed-html"; break;
			case "etx": return "text/x-setext"; break;
			case "sgm": return "text/x-sgml"; break;
			case "sgml": return "text/x-sgml"; break;
			case "spc": return "text/x-speech"; break;
			case "talk": return "text/x-speech"; break;
			case "uil": return "text/x-uil"; break;
			case "uu": return "text/x-uuencode"; break;
			case "uue": return "text/x-uuencode"; break;
			case "vcs": return "text/x-vcalendar"; break;
			case "xml": return "text/xml"; break;
			case "afl": return "video/animaflex"; break;
			case "avi": return "video/avi"; break;
			case "avs": return "video/avs-video"; break;
			case "dl": return "video/dl"; break;
			case "fli": return "video/fli"; break;
			case "gl": return "video/gl"; break;
			case "m1v": return "video/mpeg"; break;
			case "m2v": return "video/mpeg"; break;
			case "mp2": return "video/mpeg"; break;
			case "mp3": return "video/mpeg"; break;
			case "mpa": return "video/mpeg"; break;
			case "mpe": return "video/mpeg"; break;
			case "mpeg": return "video/mpeg"; break;
			case "mpg": return "video/mpeg"; break;
			case "avi": return "video/msvideo"; break;
			case "moov": return "video/quicktime"; break;
			case "mov": return "video/quicktime"; break;
			case "qt": return "video/quicktime"; break;
			case "vdo": return "video/vdo"; break;
			case "viv": return "video/vivo"; break;
			case "vivo": return "video/vivo"; break;
			case "rv": return "video/vnd.rn-realvideo"; break;
			case "viv": return "video/vnd.vivo"; break;
			case "vivo": return "video/vnd.vivo"; break;
			case "vos": return "video/vosaic"; break;
			case "xdr": return "video/x-amt-demorun"; break;
			case "xsr": return "video/x-amt-showrun"; break;
			case "fmf": return "video/x-atomic3d-feature"; break;
			case "dl": return "video/x-dl"; break;
			case "dif": return "video/x-dv"; break;
			case "dv": return "video/x-dv"; break;
			case "fli": return "video/x-fli"; break;
			case "gl": return "video/x-gl"; break;
			case "isu": return "video/x-isvideo"; break;
			case "mjpg": return "video/x-motion-jpeg"; break;
			case "mp2": return "video/x-mpeg"; break;
			case "mp3": return "video/x-mpeg"; break;
			case "mp2": return "video/x-mpeq2a"; break;
			case "asf": return "video/x-ms-asf"; break;
			case "asx": return "video/x-ms-asf"; break;
			case "asx": return "video/x-ms-asf-plugin"; break;
			case "avi": return "video/x-msvideo"; break;
			case "qtc": return "video/x-qtc"; break;
			case "scm": return "video/x-scm"; break;
			case "movi": return "video/x-sgi-movie"; break;
			case "mv": return "video/x-sgi-movie"; break;
			case "wmf": return "windows/metafile"; break;
			case "mime": return "www/mime"; break;
			case "ice": return "x-conference/x-cooltalk"; break;
			case "mid": return "x-music/x-midi"; break;
			case "midi": return "x-music/x-midi"; break;
			case "3dm": return "x-world/x-3dmf"; break;
			case "3dmf": return "x-world/x-3dmf"; break;
			case "qd3": return "x-world/x-3dmf"; break;
			case "qd3d": return "x-world/x-3dmf"; break;
			case "svr": return "x-world/x-svr"; break;
			case "vrml": return "x-world/x-vrml"; break;
			case "wrl": return "x-world/x-vrml"; break;
			case "wrz": return "x-world/x-vrml"; break;
			case "vrt": return "x-world/x-vrt"; break;
			case "xgz": return "xgl/drawing"; break;
			case "xmz": return "xgl/movie"; break;
			default: return "application/force-download"; break;
		}
	}

	public static function porcentaje($cantidad, $porcentaje)
	{		
		$resultado=($cantidad * $porcentaje) / 100;
		return $resultado;
	}

	public static function porcentaje_de($parte, $total, $tipo_porcentaje=100)
	{
		if($cantidad==0) return 0;
		else return ($parte*$tipo_porcentaje) / $cantidad;
	}
	
	public static function respuesta_xml($resultado, $mensaje, $datos=null, $att_resultado=null, $att_mensaje=null, $att_datos=null)
	{
		return <<<R
<?xml version="1.0" encoding="iso-8859-1" ?>
<r>
	<a {$att_resultado}>{$resultado}</a>
	<b {$att_mensaje}><![CDATA[{$mensaje}]]></b>
	<c {$att_datos}>{$datos}</c>
</r>
R;
	}
}		
?>
