<?php
class Abstraccion_archivo
{
	private $url_archivo=null;
	private $ruta_archivo=null;
	private $extension_archivo=null;

	private $id_item=null;
	private $ruta=null;
	private $ruta_server=null;
	private $url_web=null;
	private $clave=null;
	private $tamano=null;
	private $extension=null;

	public function comprobar() {return is_file($this->ruta_archivo);}
	public function acc_url_archivo() {return $this->url_archivo;}
	public function acc_ruta_archivo() {return $this->ruta_archivo;}
	public function acc_extension_archivo() {return $this->extension_archivo;}

	public function __construct($id_item, $ruta_server, $url_web, $ruta, $clave, $tamano=300, $extension=null)
	{
		$this->id_item=$id_item;
		$this->ruta=$ruta;
		$this->ruta_server=$ruta_server;
		$this->url_web=$url_web;
		$this->clave=$clave;
		$this->tamano=$tamano;
		$this->extension=$extension;

		$this->cargar_archivo();
	}

	public function refrescar() {$this->cargar_archivo();}
	public function sincronizar_id($id) {$this->id_item=$id;}

	private function cargar_archivo()
	{
		$ruta=$this->ruta.$this->id_item.'.'.$this->extension;

		if(file_exists($this->ruta_server.$ruta) && is_file($this->ruta_server.$ruta))
		{
			$this->url_archivo=$this->url_web.$ruta;
			$this->ruta_archivo=$this->ruta_server.$ruta;
			$this->extension_archivo=Herramientas::obtener_extension_archivo($this->ruta_archivo);

		}
		else
		{
			$this->url_archivo=null;
			$this->ruta_archivo=null;
			$this->extension_archivo=null;
		}
	}

	public function subir_archivo(&$archivos)
	{
		$ruta_adjunto=$this->ruta_server.$this->ruta.$this->id_item;

		//Subir archivo libre...
		if(!$this->extension)
		{
			$extension=Herramientas::subir_archivo_libre($archivos[$this->clave], $ruta_adjunto, $this->tamano);
		}
		//Forzar extension...
		else
		{
			$extension=Herramientas::subir_archivo_extension($archivos[$this->clave], $ruta_adjunto, $this->extension, $this->tamano);
		}

		//Eliminar el antiguo...
		if($extension && $extension!=$this->extension_archivo && $this->ruta_archivo)
		{
			$this->eliminar_archivo();
		}

		$this->extension=$extension;
		$this->cargar_archivo();
	}

	public function eliminar_archivo()
	{
		if($this->ruta_archivo)
		{
			unlink($this->ruta_archivo);
			$this->cargar_archivo();
		}
	}
}
?>
