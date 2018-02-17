<?php
class Paginacion_motor
{
	private $pagina_actual=null;
	private $registros_a_mostrar=10;
	private $margen_paginas=3;
	private $texto=null;
	private $consulta=null; 

	private $total_registros=null;

	public function registros($registros) {$this->registros_a_mostrar=$registros;}
	public function margen($margen) {$this->margen_paginas=$margen;}
	public function &acc_consulta() {return $this->consulta;}

	public function __construct(&$memoria, $pagina_actual=null)
	{
		//Comprobamos la integridad del contenedor...
		if(!is_array($memoria))
		{
			$memoria=array();
		}

		if(!isset($memoria['pagina_actual'])) $memoria['pagina_actual']=null;	

		//Por orden, vemos si recibimos alguna página...
		if(isset($pagina_actual)) $this->pagina_actual=$pagina_actual;
		else if(isset($memoria['pagina_actual'])) $this->pagina_actual=$memoria['pagina_actual'];
		else $this->pagina_actual=1;

		$memoria['pagina_actual']=$this->pagina_actual;
	}

	public function preparar($texto)
	{
		$this->preparar_texto($texto);

		//Tiramos las consultas.
		$this->consulta=new Consulta_mysql;
		$this->consulta->consultar($this->texto);

		//Actualizamos el total de registros.
		$texto_cantidad="SELECT FOUND_ROWS() AS cantidad";
		$consulta_total_registros=new Consulta_mysql;
		$consulta_total_registros->consultar($texto_cantidad);
		$consulta_total_registros->leer();
		$this->total_registros=$consulta_total_registros->resultados('cantidad');
	}

	private function preparar_texto($texto)
	{									
		//Reemplazamos y completamos el texto...
		$desplazamiento=($this->pagina_actual-1)*$this->registros_a_mostrar;
	
		$this->texto=Herramientas::reemplazar_primera_ocurrencia("SELECT", "SELECT SQL_CALC_FOUND_ROWS", $texto);
		$this->texto.=" LIMIT ".$this->registros_a_mostrar." OFFSET ".$desplazamiento;		
	}

	public function &generar()
	{
		$resultado=new Bloque_paginacion();

		$total_registros=$this->total_registros;
		$total_paginas=ceil($total_registros / $this->registros_a_mostrar);

		$resultado->mut_total_registros($total_registros);
		$resultado->mut_total_paginas($total_paginas);

		//Necesitamos saber el margen de páginas: ¿cual es la primera que debe salir?.
		$pagina_primera=$this->pagina_actual-$this->margen_paginas < 1 ? 1 : $this->pagina_actual-$this->margen_paginas;
		$pagina_ultima=$this->pagina_actual+$this->margen_paginas > $total_paginas ? $total_paginas : $this->pagina_actual+$this->margen_paginas;

		$resultado->mut_pagina_inicio($pagina_primera);
		$resultado->mut_pagina_actual($this->pagina_actual);
		$resultado->mut_pagina_fin($pagina_ultima);

		$primer_resultado=$this->pagina_actual*$this->registros_a_mostrar;
		$ultimo_resultado=$primer_resultado+$this->registros_a_mostrar-1;
		if($ultimo_resultado > $total_registros) $ultimo_resultado=$total_registros;

		$resultado->mut_primer_resultado($primer_resultado);
		$resultado->mut_ultimo_resultado($ultimo_resultado);

    		return $resultado;
	}	
}

class Bloque_paginacion
{
	private $pagina_inicio=null;
	private $pagina_actual=null;
	private $pagina_fin=null;

	private $primer_resultado=null;
	private $ultimo_resultado=null;
	private $total_registros=null;
	private $total_paginas=null;

	public function mut_pagina_inicio($valor) {$this->pagina_inicio=$valor;}
	public function mut_pagina_actual($valor) {$this->pagina_actual=$valor;}
	public function mut_pagina_fin($valor) {$this->pagina_fin=$valor;}

	public function mut_primer_resultado($valor) {$this->primer_resultado=$valor;}
	public function mut_ultimo_resultado($valor) {$this->ultimo_resultado=$valor;}
	public function mut_total_registros($valor) {$this->total_registros=$valor;}
	public function mut_total_paginas($valor) {$this->total_paginas=$valor;}

	public function acc_pagina_inicio() {return $this->pagina_inicio;}
	public function acc_pagina_actual() {return $this->pagina_actual;}
	public function acc_pagina_fin() {return $this->pagina_fin;}

	public function acc_primer_resultado() {return $this->primer_resultado;}
	public function acc_ultimo_resultado() {return $this->ultimo_resultado;}
	public function acc_total_registros() {return $this->total_registros;}
	public function acc_total_paginas() {return $this->total_paginas;}
}
?>
