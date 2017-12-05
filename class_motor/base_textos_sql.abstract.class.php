<?php
interface Interface_clase_sql
{
	public function TABLA();
	public function ORDEN_DEFECTO();
	public function CRITERIO_DEFECTO();
	public function VER_TODO();
	public function VER_VISIBLE();
	public function VER_PUBLICO();

	public function TEXTOS_CREAR_TABLAS();
}

abstract class Base_textos_sql implements Interface_clase_sql
{
	protected function generar_texto($tipo_where, $criterio=null, $criterio_defecto=null, $orden=null, $orden_defecto=null, $cantidad=null, $desplazamiento=0, $campos='*')
	{
		$criterio_enviar=self::generar_criterio($criterio, $criterio_defecto);
		$orden_enviar=self::generar_orden($orden, $orden_defecto);
		$limite=self::generar_limit($cantidad, $desplazamiento);
	
		return $this->generar_texto_orden_criterio($tipo_where, $criterio_enviar, $orden_enviar, $limite, $campos);
	}

	protected static function generar_criterio($opcion, $defecto)
	{
		$criterio=strlen(trim($opcion)) ? $opcion : $defecto;
		return $criterio;
	}

	protected static function generar_orden($opcion, $defecto)
	{
		$orden=strlen(trim($opcion)) ? $opcion : $defecto;
		return $orden;
	}	
	
	protected static function generar_limit($cantidad, $desplazamiento)
	{
		if(strlen(trim($cantidad)))
		{
			$resultado=strlen(trim($desplazamiento)) ? "LIMIT ".$cantidad." OFFSET ".$desplazamiento : "LIMIT ".$cantidad;
		}
		else
		{
			$resultado=null;
		}
	
		return $resultado;
	}

	protected function generar_texto_orden_criterio($tipo_where, $criterio=null, $orden=null, $limite=null, $campos)
	{
		$TABLA=$this->TABLA();

		return "
SELECT ".$campos."
FROM ".$TABLA."
WHERE ".$tipo_where."
".$criterio."
ORDER BY ".$orden."
".$limite;
	}

	protected function obtener_todo($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0) 
	{
		return $this->generar_texto($this->VER_TODO(), $criterio, $this->CRITERIO_DEFECTO(), $orden, $this->ORDEN_DEFECTO(), $cantidad, $desplazamiento);
	}

	protected function obtener_visible($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0) 
	{
		return $this->generar_texto($this->VER_VISIBLE(), $criterio, $this->CRITERIO_DEFECTO(), $orden, $this->ORDEN_DEFECTO(), $cantidad, $desplazamiento);
	}

	protected function obtener_publico($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0) 
	{
		return $this->generar_texto($this->VER_PUBLICO(), $criterio, $this->CRITERIO_DEFECTO(), $orden, $this->ORDEN_DEFECTO(), $cantidad, $desplazamiento);
	}

}
?>
