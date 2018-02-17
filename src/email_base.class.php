<?php
abstract class Email_base
{
	const NL="\r\n";

	protected $origen=false;
	protected $cabeceras=false;
	protected $email_origen=false;
	protected $nombre_remitente=false;
	protected $dominio_origen=false;
	protected $email_reply_to=false;
	protected $asunto=false;
	protected $exitos=0;
	protected $fallos=0;

	protected $array_destinos=array();
	protected $array_adjuntos=array();	//Contendrá directamente los objetos...
	protected $hash=null;

	private $texto_plano=null;
	private $html_cuerpo=false;

	//Cosas para SMTP... Lo sé, lo sé...
	private $autenticado=true;
	private $smtp_servidor="mail.server.com";
	private $smtp_usuario="email@server.com";
	private $smtp_pass="****";
	private $smtp_puerto=587;

	function __construct(){
		$this->hash=str_shuffle(md5(date('U')));
	}

	public function get_html_cuerpo() {return $this->html_cuerpo;}

	public function establecer_asunto($asunto) {$this->asunto=$asunto;}
	public function establecer_html_cuerpo($datos) {$this->html_cuerpo=$datos;}
	public function establecer_texto_plano($valor) {$this->texto_plano=$valor;}
	public function establecer_nombre_remitente($valor) {$this->nombre_remitente=$valor;}

	protected function establecer_smtp_puerto($puerto){$this->smtp_puerto=$puerto;}
	protected function establecer_smtp_servidor($servidor){$this->smtp_servidor=$servidor;}
	protected function establecer_smtp_usuario($usuario){$this->smtp_usuario=$usuario;}
	protected function establecer_smtp_pass($pass){$this->smtp_pass=$pass;}

	protected function establecer_autenticado($valor) {$this->autenticado=$valor;}
	protected function acc_texto_ensamblado() {return $this->ensamblar_correo();}
	protected function acc_exitos() {return $this->exitos;}
	protected function acc_fallos() {return $this->fallos;}

	public function establecer_destinatario($email)
	{
		if($email!='')
		{
			$this->array_destinos[]=$email;
		}
	}

	public function establecer_origen($mail, $dominio=null)
	{
		if($mail && $dominio)
		{
			$this->email_origen=$mail;
			$this->dominio_origen=$dominio;	
		}
		else
		{
			$completo=explode('@', $mail);
			$this->email_origen=$completo[0];
			$this->dominio_origen=$completo[1];
		}
	}

	public function establecer_reply_to($valor)
	{
		$this->email_reply_to=$valor;
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

		$cabecera_reply_to=null;

		if(strlen($this->email_reply_to))
		{
			$cabecera_reply_to=<<<R

Reply-To: <{$this->email_reply_to}>
R;
		}

		$this->cabeceras=<<<CABECERAS
From: {$this->nombre_remitente} <{$this->email_origen}@{$this->dominio_origen}>{$cabecera_reply_to}
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
			$stream_enviar=$this->ensamblar_stream_correo();

			foreach($this->array_destinos as $clave => $valor)
			{
				if(!$this->autenticado)
				{
					fseek($stream_enviar, 0);
					$texto_enviar=null;
					while(!feof($stream_enviar)) $texto_enviar.=fread($stream_enviar, 1024);

					if($this->enviar_clasico($valor, $texto_enviar)) $this->exitos++;
					else $this->fallos++;
				}
				else
				{
					if($this->enviar_smtp($valor, $stream_enviar)) $this->exitos++;
					else $this->fallos++;
				}
			}
			
			if($this->exitos==0) {return false;}
			else {return $this->exitos;}

			fclose($stream_enviar);
		}		
	}

	protected function enviar_clasico($email, &$correo_enviar)
	{			
		if(mail($email, $this->asunto, $correo_enviar, $this->cabeceras)) return true;
		else return false;
	}

	private static function enviar_comando_smtp(&$SOCKET, $texto)
	{
		fputs($SOCKET, $texto);
	}

	private static function recibir_respuesta_smtp(&$SOCKET, &$verbosidad)
	{
	        while(is_resource($SOCKET) && !feof($SOCKET)) 
		{
			$temp=fgets($SOCKET);
			$verbosidad.=$temp.self::NL;
			if ((isset($temp[3]) && $temp[3] == ' ')) break;
		};
	}

	protected function enviar_smtp($destinatario, &$stream_enviar)
	{
		$puerto=$this->smtp_puerto;
		$servidor=$this->smtp_servidor;
		$pass=$this->smtp_pass;
		$usuario=$this->smtp_usuario;

		$usuario_64=base64_encode($usuario);
		$pass_64=base64_encode($pass);

//		$SOCKET=fsockopen($servidor, $puerto);

		$opciones=array(); //.... Para mirarlo en el futuro.
		$contexto = stream_context_create($opciones);

		$errno=0;
		$errstr='';
		$timeout=30;

		$SOCKET=stream_socket_client(
			$servidor.":".$puerto,
			$errno,
			$errstr,
			$timeout,
			STREAM_CLIENT_CONNECT,
			$contexto);

		//Diversión con sockets. Como en los viejos tiempos.
		if($SOCKET)
		{
			$respuestas=null;

			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, "EHLO ".$servidor.self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, "STARTTLS".self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			if(!stream_socket_enable_crypto($SOCKET, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			{
				return false;
			}

			self::enviar_comando_smtp($SOCKET, "EHLO ".$servidor.self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, "AUTH LOGIN".self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, $usuario_64.self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, $pass_64.self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, "MAIL FROM: <".$usuario.">".self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			self::enviar_comando_smtp($SOCKET, "RCPT TO: <".$destinatario.">".self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			//Solicitamos envío de datos.
			self::enviar_comando_smtp($SOCKET, "DATA".self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			//Enviamos los datos...
			$CABECERA_COMPUESTA="To: <".$destinatario.">".self::NL."Subject:".$this->asunto.self::NL.$this->cabeceras.self::NL.self::NL;
			self::enviar_comando_smtp($SOCKET, $CABECERA_COMPUESTA);

			fseek($stream_enviar, 0);
			while(!feof($stream_enviar))
			{
				$TROZO=fread($stream_enviar, 1024);
				fwrite($SOCKET, $TROZO, 1024);
			}

			//Fin de la comunicación de datos...
			self::enviar_comando_smtp($SOCKET, self::NL.self::NL.'.'.self::NL);
			self::recibir_respuesta_smtp($SOCKET, $respuestas);

			//Cerramos y nos largamos.
			self::enviar_comando_smtp($SOCKET, "QUIT".self::NL);
			fclose($SOCKET);

			$resultado=true;
		}
		else
		{
			$resultado=false;
		}

		return $resultado;
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

//Intenta ensamblar un correo con las partes que se hayan dado.
//Parámetros: ninguno.
//Retorno: Un stream con el correo.

	private function &ensamblar_stream_correo()
	{		
		//Abrimos el stream.
		$stream=fopen('data://text/plain,', 'w');

		$texto_html=null;
		if($this->html_cuerpo) $texto_html=$this->ensamblar_texto_html();
		else $texto_html=$this->texto_plano;

		//Si tenemos adjuntos necesitamos un tipo de cabeceras
		//y una estructura. Si no es otra...

		if(!count($this->array_adjuntos))
		{
			//Si no hay adjuntos tan sólo tenemos que poner el texto.

			$this->montar_cabeceras('alternative');

			$total_textos=<<<TEXTOS
--<parte-texto-{$this->hash}>
{$this->ensamblar_texto_plano()}

--<parte-texto-{$this->hash}>
{$texto_html}

--<parte-texto-{$this->hash}>--
TEXTOS;

			fputs($stream, $total_textos);
		}
		else
		{
			//Si hay adjuntos es algo más complejo.
			$this->montar_cabeceras('mixed');

			$total_textos=<<<TOTAL_TEXTOS
--<parte-email-{$this->hash}>
Content-Type: multipart/alternative; boundary="<parte-texto-{$this->hash}>"
--<parte-texto-{$this->hash}>
{$this->ensamblar_texto_plano()}

--<parte-texto-{$this->hash}>
{$texto_html}

--<parte-texto-{$this->hash}>--

TOTAL_TEXTOS;

			fputs($stream, $total_textos);

			//Ahora vienen los adjuntos...
			foreach($this->array_adjuntos as $clave => $valor)
			{
				$valor->stream_archivo($stream, $this->hash);
			}

			//Y ahora cerrar.
			$cierre=<<<TEXTO

--<parte-email-{$this->hash}>--

TEXTO;
			fputs($stream, $cierre);
		}

		return $stream;
	}
};

class Adjunto_email
{
	private $archivo=null;
	private $tipo_mime='';
	private $nombre_archivo='';
	private $ruta=null;

	const TAM_PEDAZO=1024;

	public function __construct()
	{

	}

	public function __destruct()
	{
		if($this->archivo) fclose($this->archivo);
	}

	public function cargar_por_ruta($ruta, $nombre_final=null)
	{
		$this->archivo=fopen($ruta, 'rb');
		
		if(!$this->archivo)
		{
			$resultado=false;
		}
		else
		{
			$this->ruta=$ruta;
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
	
			$resultado=true;
		}

		return $resultado;
	}
	
	public function stream_archivo(&$stream, $hash)
	{
		if(!$this->archivo)
		{
			return;
		}
		else
		{
			$charset=$this->tipo_mime=='text/plain' ? 'charset=iso-8859-1; ' : null;

			$CABECERA=<<<ADJUNTO

--<parte-email-$hash>
Content-Type: {$this->tipo_mime}; {$charset}name="{$this->nombre_archivo}"
Content-Disposition: attachment; filename="{$this->nombre_archivo}"
Content-Transfer-Encoding: base64


ADJUNTO;

			fputs($stream, $CABECERA);
			$tamano=filesize($this->ruta);
			fputs($stream, chunk_split(base64_encode(fread($this->archivo, $tamano))));
		}
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
?>
