<?php
abstract class Herramientas_HTML
{
	//Hojas de estilo para pantalla. Especificar sin extensión.
	public static function cargar_css_screen($url_web, array $hojas)
	{
		$resultado=null;
		foreach($hojas as $clave => $valor)
		{
			$resultado.='
	<link rel="stylesheet" href="'.$url_web.'css/'.$valor.'.css" type="text/css" media="screen" charset="iso-8859-1" />';
		}

		return $resultado;
	}

	public static function cargar_css_screen_externo(array $hojas)
	{
		$resultado=null;
		foreach($hojas as $clave => $valor)
		{
			$resultado.='
	<link rel="stylesheet" href="'.$valor.'" type="text/css" media="screen" charset="iso-8859-1" />';
		}	

		return $resultado;
	}

	public static function cargar_css_print_externo(array $hojas)
	{
		$resultado=null;
		foreach($hojas as $clave => $valor)
		{
			$resultado.='
<link rel="stylesheet" href="'.$valor.'" type="text/css" media="print" charset="iso-8859-1" />';
		}	

		return $resultado;
	}

	//Hojas de estilo para impresora. Especificar sin extensión.
	public static function cargar_css_print($url_web, array $hojas)
	{
		foreach($hojas as $clave => $valor)
		{
			$resultado.='
<link rel="stylesheet" href="'.$url_web.'css/'.$valor.'.css" type="text/css" media="print" charset="iso-8859-1" />';
		}


		return $resultado;
	}

	public static function cargar_js_motor($url_web, array $hojas)
	{
		$resultado=null;
		foreach($hojas as $clave => $valor)
		{
			$resultado.='
	<script type="text/javascript" src="'.$url_web.'js_motor/'.$valor.'"></script>';
		}
	
		return $resultado;
	}


	//Archivos de JS dentro de su directorio /js... Especificar con extensión.
	public static function cargar_js($url_web, array $scripts)
	{
		$resultado=null;
		foreach($scripts as $clave => $valor)
		{
			$resultado.='
<script type="text/javascript" src="'.$url_web.'js/'.$valor.'"></script>';
		}
	
		return $resultado;
	}

	//Archivos de JS en cualquier lugar. Especificar con extensión.
	public static function cargar_js_externo(array $scripts)
	{
		$resultado=null;
		foreach($scripts as $clave => $valor)
		{
			$resultado.='
<script type="text/javascript" src="'.$valor.'"></script>';
		}
	
		return $resultado;
	}	

	public static function decodificar_entidades($cadena, $codificar=false, $poner_tags=true)
	{
		$array_entidades=array(
		"&amp;" => "&",
		"&iexcl;" => "¡",
		"&cent;" => "¢",
		"&pound;" => "£",
		"&curren;" => "€",
		"&yen;" => "¥",
		"&brvbar;" => "Š",
		"&sect;" => "§",
		"&uml;" => "š",
		"&copy;" => "©",
		"&ordf;" => "ª",
		"&laquo;" => "«",
		"&not;" => "¬",
		"&shy;" => "­",
		"&reg;" => "®",
		"&macr;" => "¯",
		"&deg;" => "°",
		"&plusmn;" => "±",
		"&sup2;" => "²",
		"&sup3;" => "³",
		"&acute;" => "Ž",
		"&micro;" => "µ",
		"&para;" => "¶",
		"&middot;" => "·",
		"&cedil;" => "ž",
		"&sup1;" => "¹",
		"&ordm;" => "º",
		"&raquo;" => "»",
		"&frac14;" => "Œ",
		"&frac12;" => "œ",
		"&frac34;" => "Ÿ",
		"&iquest;" => "¿",
		"&times;" => "×",
		"&divide;" => "÷",
		"&Agrave;" => "À",
		"&Aacute;" => "Á",
		"&Acirc;" => "Â",
		"&Atilde;" => "Ã",
		"&Auml;" => "Ä",
		"&Aring;" => "Å",
		"&AElig;" => "Æ",
		"&Ccedil;" => "Ç",
		"&Egrave;" => "È",
		"&Eacute;" => "É",
		"&Ecirc;" => "Ê",
		"&Euml;" => "Ë",
		"&Igrave;" => "Ì",
		"&Iacute;" => "Í",
		"&Icirc;" => "Î",
		"&Iuml;" => "Ï",
		"&ETH;" => "Ð",
		"&Ntilde;" => "Ñ",
		"&Ograve;" => "Ò",
		"&Oacute;" => "Ó",
		"&Ocirc;" => "Ô",
		"&Otilde;" => "Õ",
		"&Ouml;" => "Ö",
		"&Oslash;" => "Ø",
		"&Ugrave;" => "Ù",
		"&Uacute;" => "Ú",
		"&Ucirc;" => "Û",
		"&Uuml;" => "Ü",
		"&Yacute;" => "Ý",
		"&THORN;" => "Þ",
		"&szlig;" => "ß",
		"&agrave;" => "à",
		"&aacute;" => "á",
		"&acirc;" => "â",
		"&atilde;" => "ã",
		"&auml;" => "ä",
		"&aring;" => "å",
		"&aelig;" => "æ",
		"&ccedil;" => "ç",
		"&egrave;" => "è",
		"&eacute;" => "é",
		"&ecirc;" => "ê",
		"&euml;" => "ë",
		"&igrave;" => "ì",
		"&iacute;" => "í",
		"&icirc;" => "î",
		"&iuml;" => "ï",
		"&eth;" => "ð",
		"&ntilde;" => "ñ",
		"&ograve;" => "ò",
		"&oacute;" => "ó",
		"&ocirc;" => "ô",
		"&otilde;" => "õ",
		"&ouml;" => "ö",
		"&oslash;" => "ø",
		"&ugrave;" => "ù",
		"&uacute;" => "ú",
		"&ucirc;" => "û",
		"&uuml;" => "ü",
		"&yacute;" => "ý",
		"&thorn;" => "þ",
		"&yuml;" => "ÿ");

		if($poner_tags)
		{
			$array_entidades['&lt;']="<";
			$array_entidades['&gt;']=">";
			$array_entidades['&quot;']='"';
			$array_entidades['&ldquo;']='"';
			$array_entidades['&rdquo;']='"';
			$array_entidades['&apos;']="'";
			$array_entidades['&nbsp;']=" ";
		}

		if($codificar) $array_entidades=array_flip($array_entidades);

		$cadena=strtr($cadena, $array_entidades);
		return $cadena;
	}
}
?>
