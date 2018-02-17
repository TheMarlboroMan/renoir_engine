<?php
/*
Todos aquellos items que se relacionen de forma m a n con otros y que tengan
un identificador en una tabla (por ejemplo, noticias y eventos que puedan ser
comentados) tienen cabida aquí.

Antes de poder usar la clase tenemos que "configurarla". Esto se hace pasando
el array de ids_tipo a nombres de clase en un archivo de configuración.

Relaciones_base::configurar($array_de_relaciones);

O bien, sin nos gusta tenerlo todo por clases...

abstract class Relaciones_proyecto extends Relaciones
{
	public function preparar()
	{
		$array=array(
1 => 'Noticia'
);

		parent::configurar($array);
	}
}

Relaciones_proyecto::preparar();

Usaremos siempre los terminos "BASE" para referirnos a la que clase que recibe
adjuntos otros elementos "ADJUNTO".

*/

abstract class Relaciones
{
	protected static $iniciado=false;
	protected static $relaciones=array();

	final public static function configurar(&$array_relaciones)
	{
		/*El array de relaciones se expresa simplemente como
		id_en_tabla => 'Nombre_clase',
		1 => 'Noticia',
		2 => 'Evento*/

		self::$relaciones=$array_relaciones;
		self::$iniciado=true;
	}

	protected static function comprobar($param)
	{		
		if(!self::$iniciado) 
		{
			die('RELACIONES_ABSTRACT_CLASS_PHP No se ha iniciado el controlador de relaciones - '.$param);
		}
	}		

	final public static function obtener_clase_por_id($id)
	{
		self::comprobar(1);

		if(isset(self::$relaciones[$id])) return self::$relaciones[$id];
		else return false;
	}

	final public static function obtener_id_por_clase($nombre_clase)
	{
		self::comprobar(2);

		$clave=array_search($nombre_clase, self::$relaciones);

		if($clave===false) return false;
		else return $clave;
	}

	//Obtiene el tipo BASE cuando le pasas un id_tipo relación y el id_instancia de la base.
	final public static function &obtener_base_por_tipo_e_id($id_tipo, $id_base)
	{
		self::comprobar(3);

		$clase=self::obtener_clase_por_id($id_tipo);

		if($clase===false) die('RELACIONES_ABSTRACT_CLASS_PHP No se ha obtenido una clase preparada');
		else
		{
			$clase_metodo=array($clase, 'relacionable__obtener_item_relacionado');
			$parametros=array($id_base);
			$resultado=call_user_func_array($clase_metodo, $parametros);
			return $resultado;
		}
	}
}

/*
El módulo de relaciones lo extendemos como nos vaya sirviendo, lo configuramos
y lo instanciamos como parte (o no) de la clase "Noticia-evento" que tenga 
varios "Comentarios" con la finalidad de hacer de puente entre las tablas
de relación y demás... Con cada extensión haremos también el modelado del item
de la tabla de relación y de su clase_relacion_sql.
*/

abstract class Modulo_relaciones_motor
{
	protected $item_base=null;

	private $clase_base=null;
	private $id_base=null;

	private $id_tipo=null;

	private $clase_adjunto=null;
	private $id_adjunto=null;

	private $clase_sql=null;

	private $clase_relacion=null;
	private $relacion=null;

	private $clase_relacion_sql=null;

	private $criterio_relacion=null;
	private $orden_relacion=null;
	private $cantidad_relacion=null;
	private $limite_relacion=0;

	private $criterio_adjunto=null;
	private $orden_adjunto=null;
	private $cantidad_adjunto=null;
	private $limite_adjunto=0;

	public abstract function clase_relacion();
	public abstract function clase_adjunto();
	public abstract function clase_sql();
	public abstract function clase_relacion_sql();

	public function &acc_relacion() {return $this->relacion;}

	public function __construct(&$item=null)
	{
		//Podemos crearlo con un item
		$this->preparar_item_base($item);
		$this->preparar_adjunto();
		$this->preparar_relacion();

		$criterio_relacion=call_user_func_array(array($this->clase_relacion_sql, 'CRITERIO_DEFECTO'), array());
		$orden_relacion=call_user_func_array(array($this->clase_relacion_sql, 'ORDEN_DEFECTO'), array());

		$criterio_adjunto=call_user_func_array(array($this->clase_sql, 'CRITERIO_DEFECTO'), array());
		$orden_adjunto=call_user_func_array(array($this->clase_sql, 'ORDEN_DEFECTO'), array());

		$this->preparar_criterios_relacion($criterio_relacion, $orden_relacion);
		$this->preparar_criterios_adjunto($criterio_adjunto, $orden_adjunto);
		
	}

	//Estos son métodos de apoyo... El primero es públic por si hay forma
	//de usarlo.

	public function preparar_item_base(&$item=null)
	{
		$this->item_base=&$item;

		if($this->item_base && $this->item_base instanceof Contenido_bbdd)
		{
			$this->clase_base=get_class($this->item_base);
			$this->id_base=$this->item_base->ID_INSTANCIA();
	
			$this->id_tipo=Relaciones::obtener_id_por_clase($this->clase_base);
		}
	}

	private function preparar_adjunto()
	{
		$this->clase_adjunto=$this->clase_adjunto();
		$temp=new $this->clase_adjunto;
		$this->id_adjunto=$temp->ID();
		unset($temp);

		$this->clase_sql=$this->clase_sql();
	}

	private function preparar_relacion()
	{		
		$this->clase_relacion=$this->clase_relacion();
		$this->relacion=new $this->clase_relacion;	

		$this->clase_relacion_sql=$this->clase_relacion_sql();
	}

	public final function relacionar_item(&$item_adjunto, &$datos_relacion=null)
	{
		//A partir del item que hemos recibido tenemos que crear una
		//entrada en la tabla de relación.

		if(!$this->id_tipo || !$this->id_base)
		{
			die('RELACIONES_ABSTRACT_CLASS_PHP El item no esta especificado como relacionable');
		}
		else 
		{
			$this->relacion->cargar_datos_item_base($this->id_tipo, $this->id_base);
		}

		//Estos son los de la tabla "comentario"...
		$this->relacion->cargar_datos_item_relacionado($item_adjunto);
		if($datos_relacion) $this->relacion->cargar_datos_extra_relacion($datos_relacion);

		//Array crear no es más que el array de datos que pasaremos para crear la entrada.
		$array_crear=$this->relacion->generar_array_crear();

		return $this->relacion->crear($array_crear);
	}
	
	public function obtener_criterio_relacion()
	{
		return $this->relacion->generar_criterio($this->id_tipo, $this->id_base);
	}

	//Estos dos métodos nos permiten configurar los criterios para la tabla de relación
	//y para la tabla de adjuntos.
	public function preparar_criterios_relacion($criterio, $orden, $cantidad=null, $limite=0)
	{
		$this->criterio_relacion=$criterio;
		$this->orden_relacion=$orden;
		$this->cantidad_relacion=$cantidad;
		$this->limite_relacion=$limite;
	}

	public function preparar_criterios_adjunto($criterio, $orden, $cantidad=null, $limite=0)
	{
		$this->criterio_adjunto=$criterio;
		$this->orden_adjunto=$orden;
		$this->cantidad_adjunto=$cantidad;
		$this->limite_adjunto=$limite;
	}

	function texto_relacion(){return $this->generar_texto_consulta_clase_relacion();}
	public function texto() 
	{
		$criterio_interno=$this->generar_texto_consulta_clase_relacion($this->id_adjunto);
		return $this->generar_texto_consulta_clase_adjunto($criterio_interno);
	}

	//Obtiene un array de los tipos "relacion".

	public function &relacion_obtener()
	{
		$texto=$this->generar_texto_consulta_clase_relacion();
		$resultado=Contenido_bbdd::obtener_array($this->clase_relacion, $texto);
		return $resultado;
	}

	public function &relacion_consulta()
	{
		$texto=$this->generar_texto_consulta_clase_relacion();
		$resultado=Contenido_bbdd::obtener_consulta($texto);
		return $resultado;
	}

	//Obtiene los tipos "adjunto"...
	public function &obtener()
	{
		$criterio_interno=$this->generar_texto_consulta_clase_relacion($this->id_adjunto);
		$texto=$this->generar_texto_consulta_clase_adjunto($criterio_interno);
		$resultado=Contenido_bbdd::obtener_array($this->clase_adjunto, $texto);

echo '<div style="font-size: 10px">'.$texto.'</div>';

		return $resultado;
	}

	public function &consulta()
	{
		$criterio_interno=$this->generar_texto_consulta_clase_relacion($this->id_adjunto);
		$texto=$this->generar_texto_consulta_clase_adjunto($criterio_interno);
		$resultado=Contenido_bbdd::obtener_consulta($texto);
		return $resultado;
	}

	//Texto para obtener datos de la clase relación.
	private function generar_texto_consulta_clase_relacion($campos="*")
	{
		$criterio_relacion=$this->obtener_criterio_relacion();
		$clase_metodo=array($this->clase_relacion_sql, 'obtener');
		$parametros=array($this->criterio_relacion.$criterio_relacion, $this->orden_relacion, $this->cantidad_relacion, $this->limite_relacion, $campos);
		$texto=call_user_func_array($clase_metodo, $parametros);
		return $texto;
	}

	private function generar_texto_consulta_clase_adjunto($criterio_interno)
	{
		$criterio_interno_final="
AND ".$this->id_adjunto." IN 
(
".$criterio_interno."
)";

		$clase_metodo=array($this->clase_sql, 'obtener');
		$parametros=array($this->criterio_adjunto.$criterio_interno_final, $this->orden_adjunto, $this->cantidad_adjunto, $this->limite_adjunto);
		$texto=call_user_func_array($clase_metodo, $parametros);

		return $texto;
	}

	//Cuando pasamos un adjunto nos devuelve un array con todas las bases a las que pertenece.
	public function &obtener_bases_desde_adjunto(&$adjunto)
	{
		$resultado=array();
		$texto=$this->relacion->texto_destinatarios_desde_item($adjunto->ID_INSTANCIA(), $this->id_adjunto);
		$consulta=Contenido_bbdd::obtener_consulta($texto);

		while($consulta->leer())
		{
			$id_tipo=$consulta->resultados('id_tipo');
			$id_base=$consulta->resultados('id_elemento');
			$resultado[]=Relaciones::obtener_base_por_tipo_e_id($id_tipo, $id_base);
		}

		return $resultado;
	}

	//Con el "id_relacion" e "id_elemento" y un item se puede obtener la tupla de relación...
	public function &obtener_entrada_tabla_desde_item_y_relacion($clase_adjunto, &$base, &$adjunto)
	{
		$id_base=$base->ID_INSTANCIA();	//Este es id_noticia
		$id_adjunto=$adjunto->ID_INSTANCIA();	//Este es id_comentario
		$id_tipo=Relaciones::obtener_id_por_clase(get_class($item));	//Este es id_tipo...
		$id_primario=$adjunto->ID();	//Esto es "id_comentario" en base de datos...

		//Con esos tres datos podemos localizar la entrada...
		$this->relacion->cargar_desde_datos_basicos($id_primario, $id_base, $id_adjunto, $id_tipo);

		return $this->relacion;
	}
}

//Ojo con esto... Por un "fallo" de diseño en el motor, los campos deben 
//llamarse EXACTAMENTE IGUAL en base de datos y como propiedades, siendo la
//tarea del diccionario establecer las equivalencias desde los arrays de datos
//que se pasen... Todo lo que usemos tendrá que tener los tres campos del 
//diccionario tal cual.

abstract class Modulo_relaciones_motor_entrada_tabla extends Contenido_bbdd
{
	private static $diccionario=array(
	'id_entrada' => 'id_entrada',	
	'id_tipo' => 'id_tipo',
	'id_elemento' => 'id_elemento');

	protected $id_entrada;
	protected $id_tipo;
	protected $id_elemento;

	public function __construct(&$datos=null, &$otro_diccionario, $tabla, $id)
	{
		$diccionario_final=array_merge(self::$diccionario, $otro_diccionario);
		parent::__construct($datos, $diccionario_final, $tabla, $id);
	}

	public final function cargar_datos_item_base($a, $b)
	{
		$this->id_tipo=$a;
		$this->id_elemento=$b;
	}

	public final function &generar_array_crear()
	{
		$resultado=array();
		$diccionario=$this->DICCIONARIO();

		foreach($diccionario as $clave => $valor)
		{
			if(property_exists($this, $clave) && !is_object($this->$clave) && strlen($this->$clave))
				$resultado[$valor]=$this->$clave;
		}

		return $resultado;
	}	

	//public final function generar_criterio($campo_id_primario, $id_tipo, $id_elemento)
	//Esto devuelve el criterio por el que encontramos los items que queremos
	//de la tabla de relación.
	public final function generar_criterio($id_tipo, $id_elemento)
	{
		return "
AND id_tipo='".$id_tipo."'
AND id_elemento='".$id_elemento."'";	
	}

	public final function texto_destinatarios_desde_item($id_instancia, $campo_id_primario)
	{
		$tabla=$this->TABLA();
		
		return "
SELECT *
FROM ".$tabla."
WHERE ".$campo_id_primario."='".$id_instancia."'";
	}

	public function cargar_desde_datos_basicos($id_primario, $id_elemento, $id_relacionado, $id_tipo)
	{
		$tabla=$this->TABLA();
		$texto="
SELECT *
FROM ".$tabla."
WHERE ".$id_primario."='".$id_relacionado."'
AND id_elemento='".$id_elemento."'
AND id_tipo='".$id_tipo."'";
	
		$consulta=self::obtener_consulta($texto);
		$consulta->leer();

		$this->cargar($consulta->resultados());
	}

	public function crear(&$datos=null){return parent::base_crear($datos);}
	public function modificar(&$datos=null) {return parent::base_modificar($datos);}
	public function eliminar(&$datos=null) 
	{
		$resultado=parent::eliminar_fisico($datos);
		
		if($resultado)
			$this->limpiar_referencias();

		return $resultado;
	}

	public abstract function cargar_datos_item_relacionado(&$a);	
	public abstract function limpiar_referencias();
}

/*
DROP TABLE IF EXISTS app_notas;
CREATE TABLE app_notas
(
id_nota INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
fecha DATE NOT NULL,
hora TIME NOT NULL,
texto TEXT NOT NULL,
visible BOOLEAN NOT NULL DEFAULT TRUE,
borrado_logico BOOLEAN NOT NULL DEFAULT FALSE
)ENGINE=MYISAM;

class Nota extends Contenido_bbdd
{
	const TABLA='app_notas';
	const ID='id_nota';

	private static $diccionario=array(
'id_nota' => 'id_nota',
'fecha' => 'fecha',
'hora' => 'hora',
'texto' => 'texto',
'visible' => 'visible',
'borrado_logico' => 'borrado_logico'
	);

	protected $id_nota=null;
	protected $fecha=null;
	protected $hora=null;
	protected $texto=null;
	protected $visible=null;
	protected $borrado_logico=null;

	public function acc_id_nota() {return $this->id_nota;}
	public function acc_fecha() {return $this->fecha;}
	public function acc_hora() {return $this->hora;}
	public function acc_texto() {return $this->texto;}
	public function es_visible() {return $this->visible;}
	public function es_borrado_logico() {return $this->borrado_logico;}
	
	public function __construct(&$datos=null)
	{
		parent::__construct($datos, self::$diccionario, self::TABLA, self::ID);
	}

	public function crear(&$datos=null)
	{
		$resultado=parent::base_crear($datos, 'fecha, hora', 'CURDATE(), CURTIME()');
		return $resultado;
	}

	public function modificar(&$datos=null)
	{
		$resultado=parent::base_modificar($datos);
		return $resultado;
	}

	public function eliminar(&$datos=null)
	{
		$resultado=parent::base_eliminar($datos);
		return $resultado;
	}
}

class Modulo_notas extends Modulo_relaciones_motor
{
	const CLASE_ADJUNTO='Nota';
	const CLASE_SQL='Nota_sql';
	const CLASE_RELACION_SQL='Nota_relacion_sql';
	const CLASE_RELACION='Nota_relacion';

	public function clase_relacion() {return self::CLASE_RELACION;}
	public function clase_adjunto() {return self::CLASE_ADJUNTO;}
	public function clase_sql() {return self::CLASE_SQL;}
	public function clase_relacion_sql() {return self::CLASE_RELACION_SQL;}
}

class Nota_relacion extends Modulo_relaciones_motor_entrada_tabla
{
	//Esto es la representación de una tupla en la tabla...
	const TABLA='app_notas_relacion';
	const ID='id_entrada';

	protected static $diccionario=array(
'id_nota' => 'id_nota');

	protected $id_nota=false;
	
	public function acc_id_nota() {return $this->id_nota;}

	public function __construct(&$datos=null){parent::__construct($datos, self::$diccionario, self::TABLA, self::ID);}
	public function cargar_datos_item_relacionado(&$nota){$this->id_nota=$nota->ID_INSTANCIA();}
	public function cargar_datos_extra_relacion(&$datos){}
	public function limpiar_referencias()
	{
		$nota=new Nota($this->id_nota);
		$modulo=new Modulo_notas();
		$modulo->obtener_bases_desde_adjunto($nota);
		
		if(!count($relaciones))
		{
			$nota->eliminar();
		}		
	}
}

abstract class Nota_relacion_sql extends Base_textos_sql implements Interface_clase_sql
{
	const VER_TODO='TRUE';
	const VER_VISIBLE='TRUE';
	const VER_PUBLICO='TRUE';
	const ORDEN_DEFECTO='id_entrada ASC';
	const CRITERIO_DEFECTO='';
	const NOMBRE_CLASE='Nota_relacion_sql';

	public static function TABLA() {return Nota_relacion::TABLA;}
	public static function ID_TABLA() {return Nota_relacion::ID;}

	public static function ID($alias=false) {return parent::procesar_reemplazar_por_alias($alias, self::ID_TABLA(), self::TABLA());}	
	public static function ORDEN_DEFECTO($alias=false) {return parent::procesar_reemplazar_por_alias($alias, self::ORDEN_DEFECTO, self::TABLA());}
	public static function CRITERIO_DEFECTO($alias=false) {return parent::procesar_reemplazar_por_alias($alias, self::CRITERIO_DEFECTO, self::TABLA());}
	public static function VER_TODO($alias=false) {return parent::procesar_reemplazar_por_alias($alias, self::VER_TODO, self::TABLA());}
	public static function VER_VISIBLE($alias=false) {return parent::procesar_reemplazar_por_alias($alias, self::VER_VISIBLE, self::TABLA());}
	public static function VER_PUBLICO($alias=false) {return parent::procesar_reemplazar_por_alias($alias, self::VER_PUBLICO, self::TABLA());}

	//Interface...
	public static function obtener($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0, $campos='*') {return self::generar_texto(self::TABLA(), "TRUE", $criterio, self::CRITERIO_DEFECTO(), $orden, self::ORDEN_DEFECTO(), $cantidad, $desplazamiento, $campos);}
	public static function obtener_todo($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0, $campos='*') {return self::generar_texto(self::TABLA(), self::VER_TODO(), $criterio, self::CRITERIO_DEFECTO(), $orden, self::ORDEN_DEFECTO(), $cantidad, $desplazamiento, $campos);}
	public static function obtener_visible($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0, $campos='*') {return self::generar_texto(self::TABLA(), self::VER_VISIBLE(), $criterio, self::CRITERIO_DEFECTO(), $orden, self::ORDEN_DEFECTO(), $cantidad, $desplazamiento, $campos);}
	public static function obtener_publico($criterio=null, $orden=null, $cantidad=null, $desplazamiento=0, $campos='*') {return self::generar_texto(self::TABLA(), self::VER_PUBLICO(), $criterio, self::CRITERIO_DEFECTO(), $orden, self::ORDEN_DEFECTO(), $cantidad, $desplazamiento, $campos);}
}
*/
?>
