<?php
interface Contrato_emails
{
	function CUERPO_HTML();
}

abstract class Email_base
{
	protected $origen=false;
	protected $cabeceras=false;
	protected $email_origen=false;
	protected $dominio_origen=false;
	protected $asunto=false;
	protected $exitos=0;
	protected $fallos=0;

	protected $array_destinos=array();
	protected $array_adjuntos=array();	//Contendrá directamente los objetos...
	protected $hash=null;

	protected $texto_plano=null;
	protected $html_cuerpo=false;	

	function __construct()
	{
		$this->hash=str_shuffle(md5(date('U')));
	}

	public function establecer_asunto($asunto) {$this->asunto=$asunto;}
	public function establecer_texto_plano($valor) {$this->texto_plano=$valor;}		
	public function acc_texto_ensamblado() {return $this->ensamblar_correo();}	
	public function acc_exitos() {return $this->exitos;}
	public function acc_fallos() {return $this->fallos;}

	public function establecer_destinatario($email)
	{
		if(strlen($email)) $this->array_destinos[]=$email;
	}

	public function establecer_origen($mail, $dominio)
	{
		$this->email_origen=$mail;
		$this->dominio_origen=$dominio;	
	}

	private function montar_cabeceras($tipo)
	{
		switch($tipo)
		{
			case 'mixed':
				$cabecera_tipo=
<<<TIPO
Content-Type: multipart/mixed; boundary="<parte-email-{$this->hash}>"
TIPO;
			break;

			default:
				$cabecera_tipo=
<<<TIPO
Content-Type: multipart/alternative; boundary="<parte-texto-{$this->hash}>"
TIPO;
			break;
		}

		$this->cabeceras=<<<CABECERAS
From:{$this->email_origen}@{$this->dominio_origen}
MIME-Version: 1.0
{$cabecera_tipo}
CABECERAS;
	}

	public function enviar()
	{		
		if(!is_array($this->array_destinos) || count($this->array_destinos)==0)
		{
			return false;
		}
		else
		{
			//Vamos a ensamblar el correo...
			$correo_enviar=$this->ensamblar_correo();

			foreach($this->array_destinos as $clave => $valor)
			{
				if(mail($valor, $this->asunto, $correo_enviar, $this->cabeceras)) $this->exitos++;
				else $this->fallos++;
			}
			
			if($this->exitos==0) {return false;}
			else {return $this->exitos;}
		}		
	}

	private function ensamblar_texto_html()
	{		
		$resultado=<<<TEXTO_HTML
Content-Type: text/html; charset=ISO-8859-1
Content-Transfer-Encoding: 7bit

{$this->html_cuerpo}
TEXTO_HTML;

		return $resultado;
	}

	private function ensamblar_texto_plano()
	{
		$texto=$this->texto_plano!=null ? $this->texto_plano : "Si puede leer esto necesita un lector HTML";

		$resultado=<<<TEXTO_PLANO
Content-Type: text/plain; charset=ISO-8859-1
Content-Transfer-Encoding: 8bit
{$texto}
TEXTO_PLANO;
		
		return $resultado;
	}

	public function adjuntar_archivo_por_ruta($ruta, $nombre_archivo=null)
	{
		$temp=new Adjunto_email();
		if($temp->cargar_por_ruta($ruta, $nombre_archivo))
		{
			$this->array_adjuntos[]=&$temp;
			return true;
		}
		else
		{
			return false;
		}
	}

	private function ensamblar_correo()
	{		
		//Declaración de variables...
		$texto_html=null;
		$texto_adjuntos=null;
		$texto_plano=null;

		//Vamos a hacer un montaje de toda la sección de texto del email
		//El texto plano viene tal cual. El texto HTML se pondrá como
		//plano si no determinamos nada.

		$texto_plano=$this->ensamblar_texto_plano();
			
		if($this->html_cuerpo) $texto_html=$this->ensamblar_texto_html();
		else $texto_html=$this->texto_plano;	//TODO...
		
		$total_textos=<<<TEXTOS
--<parte-texto-{$this->hash}>
{$texto_plano}

--<parte-texto-{$this->hash}>
{$texto_html}

--<parte-texto-{$this->hash}>--
TEXTOS;

		//Una vez ensamblada la parte de texto comprobaremos si hay
		//adjuntos y montaremos las cabeceras como sea conveniente de
		//acuerdo con las piezas que ya tenemos.

		if(count($this->array_adjuntos))
		{	
			$this->montar_cabeceras('mixed');
			foreach($this->array_adjuntos as $clave => $valor)
			{
				$texto_adjuntos.=$valor->devolver_codificado($this->hash);
			}

			//Ensamblaje final con adjuntos... Dado que la cabecera
			//del email llevará el tipo "mixed" ahora ponemos que
			//la parte del mixed del texto es alternative, a mano.
			//Simplemente modificamos el ensamblado final de texto.

			$total_textos=<<<TOTAL_TEXTOS
Content-Type: multipart/alternative; boundary="<parte-texto-{$this->hash}>"
{$total_textos}
TOTAL_TEXTOS;

			$resultado=<<<TEXTO
--<parte-email-{$this->hash}>
{$total_textos}
{$texto_adjuntos}
--<parte-email-{$this->hash}>--
TEXTO;

		}
		//Ensamblaje final sin adjuntos... No hay mucho que hacer...
		else 			
		{
			$this->montar_cabeceras('alternative');
			$resultado=$total_textos;
		}		

		return $resultado;	
	}
};

class Adjunto_email
{
	private $tipo_mime='';
	private $nombre_archivo='';
	private $archivo_base_64='';

	public function __construct() {}

	public function cargar_por_ruta($ruta, $nombre_final=null)
	{
		$archivo=fopen($ruta, 'rb');
		
		if(!$archivo)
		{
			$resultado=false;
		}
		else
		{
			$this->tipo_mime=self::tipo_mime($ruta);
			
			if($nombre_final)
			{
				$this->nombre_archivo=$nombre_final;
			}
			else
			{
				$partido=explode("/", $ruta);
				$this->nombre_archivo=$partido[count($partido)-1];
			}

			$tamano=filesize($ruta);
			$this->archivo_base_64=chunk_split(base64_encode(fread($archivo, $tamano)));
			fclose($archivo);		
	
			$resultado=true;
		}

		return $resultado;
	}
	
	public function &devolver_codificado($hash)
	{
		$charset=$this->tipo_mime=='text/plain' ? 'charset=iso-8859-1; ' : null;

		$resultado=<<<ADJUNTO

--<parte-email-$hash>
Content-Type: {$this->tipo_mime}; {$charset}name="{$this->nombre_archivo}"
Content-Disposition: attachment; filename="{$this->nombre_archivo}"
Content-Transfer-Encoding: base64

{$this->archivo_base_64}
ADJUNTO;

		return $resultado;
	}	

	public static function tipo_mime($archivo)
	{
		if(strlen($archivo) > 4)
		{
			$partido=explode(".", $archivo);
			$ext=$partido[count($partido)-1];
		}
		else
		{
			$ext=$archivo;
		}
	
		return Herramientas::determinar_xtype($ext);
	}
};

class Email_test extends Email_base implements Contrato_emails
{
	//Estos son los datos que tendrá este email, por ejemplo...
	private $html_title=false;
	private $html_titulo=false;
	private $html_destacado=false;
	private $html_texto=false;

	//Esto realmente no hace falta puesto que podemos manipular los valores
	//desde los métodos de la clase...
	private function establecer_html_title($valor) {$this->html_title=$valor;}
	private function establecer_html_titulo($valor) {$this->html_titulo=$valor;}
	private function establecer_html_destacado($valor) {$this->html_destacado=$valor;}
	private function establecer_html_texto($valor) {$this->html_texto=$valor;}

	public function CUERPO_HTML() 
	{	
		$destacado=null;
		if($this->html_destacado)
		{
			$destacado=<<<DESTACADO
		<div id="destacado" style="margin:20px auto; width:700px; background-color: #DDCCAA; font-weight: bold">
			{$this->html_destacado}
		</div>
DESTACADO;
		}


		$this->html_cuerpo=<<<MAQUETACION
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="es" lang="es">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<title>{$this->html_title}</title>
</head>
<body bgcolor="#cbd0d0">
	<div id="email" style="margin:20px auto; width:700px; background-color: gray;">
		<div id="titulo" style="margin:20px auto; width:700px; background-color: #DDCCDD;">
			{$this->html_titulo}
		</div>

		{$destacado}

		<div class="main" style="margin:20px auto; width:700px; background-color: #DDCCDD;">
			{$this->html_texto}
		</div>
		<div class="pie">
		Pie...
		</div>
	</div>
</body>
</html>
MAQUETACION;
	}

	public function test_adjunto()
	{
		$this->establecer_html_title('TITLE HTML');
		$this->establecer_html_titulo('TITULO HTML');
		$this->establecer_html_destacado('destacado');
		$this->establecer_html_texto('texto texto texto...');
		$this->establecer_texto_plano("ESTE ES EL TEXTO PLANO");
		$this->establecer_origen('no-reply', 'dominio');
		$this->establecer_asunto('TEST ASUNTO');
		$this->establecer_destinatario('email@email.com');

		$this->adjuntar_archivo_por_ruta('test_2.txt');
		$this->adjuntar_archivo_por_ruta('test.txt');

		$this->CUERPO_HTML();
		$this->enviar();
	}

	public function test_no_adjunto()
	{
		$this->establecer_html_title('TITLE HTML');
		$this->establecer_html_titulo('SIN ADJUNTO');
		$this->establecer_html_destacado('destacado');
		$this->establecer_html_texto('texto texto texto...');
		$this->establecer_texto_plano("ESTE ES EL TEXTO PLANO");
		$this->establecer_origen('no-reply', 'dominio');
		$this->establecer_asunto('SIN ADJUNTO');
		$this->establecer_destinatario('email@email.com');

		$this->CUERPO_HTML();
		$this->enviar();
	}
}
?>
