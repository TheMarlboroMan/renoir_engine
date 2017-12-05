<?php

class Email extends \Email_base implements \Contrato_emails
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
		This is my footer!!
		</div>
	</div>
</body>
</html>
MAQUETACION;
	}

/*
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
*/
	public function send_validation_email(User $user)
	{
		$this->establecer_html_title('Verify your account!');
		$this->establecer_html_titulo('Verify your account!');
		$this->establecer_html_destacado('We need you to activate your account!');
		$this->establecer_html_texto('Please, use the activation code '.$user->get_verification_code());
		$this->establecer_texto_plano("You need a proper");
//		$this->establecer_origen('dani', 'caballorenoir.net');
		$this->establecer_asunto('Phoodo - Verify your account!');
		$this->establecer_destinatario($user->get_email());

		$this->CUERPO_HTML();
		$this->enviar();
	}
}
?>
