<?php
/*
Para usar todo esto...

$id_item_a=1;
$temp_a=new Item_a($id_item_a);
$dummy_item_b=new Item_b;

$relacion=new Abstraccion_relacion_m_n($temp_a, $dummy_item_b, Item_b::TABLA_RELACION);
$array_obtenidos=$relacion->obtener("AND ".Item_b_sql::VER_PUBLICO(), 'orden ASC');

print_r($relacion->array_items()); --> Para obtener el array de items de b.
print_r($relacion->array_items_relacion()); --> Para obtener el array de items de r.

$bah=array('id_entrada' => 112, 'id_a' => 17, 'id_b' => 16);
$test=$relacion->crear_item_r($id_bah);	--> Crear un item a partir de datos...
$test->base_modificar($bah);			--> O modificarlo.

$array_eliminar=array(1,3,9);
$relacion->eliminar($array_eliminar);	--> Eliminar los items con el id b.

$array_insertar=array(1,5,6,4);
print_r($relacion->crear_rapido($array_insertar)); --> Insertar rápidamente items con los ids b.

$array_sincronizar=array(4,9);
print_r($relacion->sincronizar_rapido($array_sincronizar));	--> Sincronización rápida.

$array_crear=array(
	array('id_a'=>$id_item_a, 'id_b'=>1, 'propiedad'=>1),
	array('id_a'=>$id_item_a, 'id_b'=>2, 'propiedad'=>2),
	array('id_a'=>$id_item_a, 'id_b'=>4, 'propiedad'=>5),
	array('id_a'=>2, 'id_b'=>7, 'propiedad'=>8)); //El último no se creará porque no se corresponde el id a.

print_r($relacion->crear($array_crear));	--> Crear con todo detalle.

function test_cmp($a, $b, $c)
{
	return $a->acc_propiedad($c)==$b->acc_propiedad($c) && $a->acc_propiedad('propiedad')==$b->acc_propiedad('propiedad');
}

$array_crear=array(
	array('id_a'=>$id_item_a ,'id_b'=>1, 'propiedad'=>2));

print_r($relacion->crear($array_crear, 'test_cmp'));	--> Crear con función personalizada de comparar.

Todo ello queda mejor siempre si lo encapsulamos dentro de la clase.

*/

class Abstraccion_relacion_m_n
{
	//Esto es un objeto que podemos instanciar para obtener de forma
	//automática relaciones m a n... O algo. Para que funcione lo planteamos
	//como que hay un tipo_a, que es el principal. De este tipo hay varios
	//items del tipo b, que tienen su propia tabla... La relación entre
	//ambos está expresada en una tabla de relación intermedia a la que
	//llamamos aquí la tabla_r... Esta clase es algo así como la "clase_r".
	//Contendrá un array con instancias de una "clase_r" en la que tendremos
	//tanto los datos de la relación como también una instancia del objeto
	//b que hay en la relación.
	//Hay una interfaz "relacionable" que nos dice todo lo que necesitamos.

	private $array_items=array();
	private $bandera_carga=false;

	private $item_a=null;
	private $item_b=null;
	private $tabla_relacion=null;
	private $diccionario_tablas=null;	

	private $tabla_a=null;
	private $tabla_b=null;
	private $tabla_r=null;
	private $id_a=null;
	private $id_b=null;
	private $id_a_relacion=null;	//Id de item a en tabla de relación.
	private $id_b_relacion=null;	//Id de item b en tabla de relación.
	private $clave_tabla_r=null;

	public function &acc_items_relacion() {return $this->array_items;}

	public function &array_items()
	{
		$temp=array();

		foreach($this->array_items as $clave => &$valor)
		{
			$temp[]=$valor->acc_item();
		}

		return $temp;
	}

	public function __construct(&$tipo_a, &$tipo_b, $tabla_r, $extra=null)
	{
		$this->item_a=&$tipo_a;
		$this->item_b=&$tipo_b;
		$this->tabla_relacion=$tabla_r;

		if(!is_object($this->item_a)) die('ERROR: abstraccion_relacion_m_n no reconoce tipo a como clase');
		if(!is_object($this->item_b)) die('ERROR: abstraccion_relacion_m_n no reconoce tipo b como clase');

		$this->diccionario_tablas=Abstraccion_relacion_m_n_tablas::obtener_relacion($this->item_a, $this->item_b, $this->tabla_relacion, $extra);
		$this->preparar_campos();
	}

	public function preparar_campos($tabla_a=null, $tabla_b=null, $tabla_r=null, $id_a=null, $id_b=null, $id_a_relacion=null, $id_b_relacion=null, $clave_tabla_r=null)
	{
		$this->tabla_a=strlen(trim($tabla_a)) ? $tabla_a : $this->item_a->TABLA();
		$this->tabla_b=strlen(trim($tabla_b)) ? $tabla_b : $this->item_b->TABLA();
		$this->tabla_relacion=strlen(trim($tabla_r)) ? $tabla_r : $this->tabla_relacion;
		$this->id_a=strlen(trim($id_a)) ? $id_a : $this->item_a->ID();
		$this->id_b=strlen(trim($id_b)) ? $id_b : $this->item_b->ID();
		$this->id_a_relacion=strlen(trim($id_a_relacion)) ? $id_a_relacion : $this->item_a->ID();
		$this->id_b_relacion=strlen(trim($id_b_relacion)) ? $id_b_relacion : $this->item_b->ID();
		$this->clave_tabla_r=strlen(trim($clave_tabla_r)) ? $clave_tabla_r : $this->diccionario_tablas->acc_tabla_r()->acc_campo_clave()->acc_nombre();
	}

	public function &obtener($criterio=null, $orden=null)
	{
		//Al pasar los criterios y orden tendríamos que acompañarlos del nombre de la tabla si
		//hay posibilidad de que coincidan.

		$this->array_items=array();

		$criterio=strlen(trim($criterio)) ? $criterio : "AND true";
		$orden=strlen(trim($orden)) ? $orden : $this->item_b->ID().' ASC';

		$CAMPOS_A=$this->diccionario_tablas->acc_tabla_b()->concatenar_campos_tabla();
		$CAMPOS_R=$this->diccionario_tablas->acc_tabla_r()->concatenar_campos_tabla();
	
		$id_item_a=$this->item_a->ID_INSTANCIA();

		$texto="
SELECT ".$CAMPOS_A.", ".$CAMPOS_R."
FROM ".$this->tabla_b." 
LEFT JOIN ".$this->tabla_relacion." ON (".$this->tabla_b.".".$this->id_b."=".$this->tabla_relacion.".".$this->id_b_relacion.")
WHERE ".$this->tabla_relacion.".".$this->id_a."='".$id_item_a."'
".$criterio."
ORDER BY ".$orden;

		$consulta=new Consulta_Mysql;
		$consulta->consultar($texto);

/*
Cuando hemos llegado a este punto tenemos que juntar dos mundos... Por un lado
el concepto original de una sóla consulta de dónde sacamos todos los datos
que queremos y por otro el concepto de las clases con propiedades dinámicas...
Lo más sensato sería separar aquí las propiedades de cada tabla, cargar el
tipo "r" y luego usar los datos del tipo "b" para crearlo dentro del r.
*/

		$campos_tabla_b=$this->diccionario_tablas->acc_tabla_b()->acc_indice_campos();
		$campos_tabla_r=$this->diccionario_tablas->acc_tabla_r()->acc_indice_campos();
		$limite=count($campos_tabla_b);

		$propiedades_relacion=$this->obtener_plantilla_propiedades_relacion();
		$nombre_clase_b=get_class($this->item_b);

		while($consulta->leer_como_row())
		{
			$datos_obtenidos=$consulta->resultados();
			$datos_separados=array_chunk($datos_obtenidos, $limite, false);

			//Estos datos están ordenados por índices numéricos...
			//Al pasárselos a cualquier clase se rien en toda tu
			//cara por lo que hay que convertirlos ¬¬...
			$datos_clase_b=self::conversion_campos($datos_separados[0], $campos_tabla_b);
			$datos_clase_r=self::conversion_campos($datos_separados[1], $campos_tabla_r);

			$temp=new Item_abstraccion_relacion_m_n($datos_clase_r, $this->tabla_relacion, $this->clave_tabla_r, $this->id_b_relacion, $nombre_clase_b, $propiedades_relacion);
			$nuevo_item_b=new $nombre_clase_b($datos_clase_b);
			$temp->asociar_item_b($nuevo_item_b);
			$this->array_items[]=$temp;
		}

		$this->bandera_carga=true;
		return $this->array_items;
	}

	private static function &conversion_campos(&$datos, &$indices_campos)
	{
		$resultado=array();

		foreach($datos as $clave => $valor)
		{
			$nueva_clave=$indices_campos[$clave]->acc_nombre();
			$resultado[$nueva_clave]=$valor;
		}		

		return $resultado;
	}

	private function &obtener_plantilla_propiedades_relacion()
	{
		$propiedades_relacion=array();
		$tabla_r=$this->diccionario_tablas->acc_tabla_r();
		$campos_tabla_r=$tabla_r->acc_campos();
		foreach($campos_tabla_r as $clave=>&$valor) $propiedades_relacion[$valor->acc_nombre()]=false;
		
		return $propiedades_relacion;
	}

	public function &crear_item_r(&$datos=null, $crear_item=false)
	{
		//Este método genera un item "r" con la plantilla necesaria
		//para las propiedades ya cargada... Se puede usar para
		//generar el item y luego darle el alta en bbdd, por ejemplo.

		$propiedades=$this->obtener_plantilla_propiedades_relacion();
		$nombre_clase_b=get_class($this->item_b);

		$temp=new Item_abstraccion_relacion_m_n($datos, $this->tabla_relacion, $this->clave_tabla_r, $this->id_b_relacion, $nombre_clase_b, $propiedades);	

		if($crear_item) $temp->generar_item_b();

		return $temp;
	}


	public function crear_rapido(&$array_ids_b, $duplicar=false)
	{
		//Crea nuevas relaciones a partir de un array de ids de items 
		//del tipo b... Ignora por completo los posibles atributos de
		//la relación y no construye los items del tipo B... En resumen,
		//lo hace lo más rápido y ligero posible.
		//Al finalizar nos devuelve un array con los ids de entrada
		//que se han creado en la tabla de relación. De este modo 
		//podemos intentar, por ejemplo, componerlos luego con datos
		//de relación. El orden en que los devuelve es el mismo que el
		//orden en que los da.

		if(!count($array_ids_b))
		{
			$resultado=array();
		}
		else	
		{
			if(!$duplicar)
			{
				if(!$this->bandera_carga) die('ERROR: Abstraccion_relacion_m_n no es posible inserción segura sin cargar');
				else
				{
					//Sacamos un array de "ids_b" que tenemos en la relación...
					//Estos arrays van a depender de los criterios que 
					//hemos especificado al cargar la relación, por lo que deberíamos
					//tener algo de cuidado. También es interesante
					//que podemos meter perfectamente ids de items
					//que NO existen.

					if(!count($this->array_items)) $array_final=$array_ids_b;
					else
					{
						$ids_existentes=array();

						foreach($this->array_items as $clave=>&$valor)	
							$ids_existentes[]=$valor->acc_propiedad($this->id_b_relacion);

						foreach($array_ids_b as $clave => $valor)	//Por cada item comparamos...
							if(!in_array($valor, $ids_existentes))	//Sólo si no lo encontramos puede pasar.
								$array_final[]=$valor;
					}
				}
			}
			else $array_final=&$array_ids_b;

			//Ahora recorremos el array final y realizamos las inserciones.
			//Se realizan todas de una tacada, simplemente.

			$resultado=array();

			if(count($array_final))
			{
				$id_a=$this->item_a->ID_INSTANCIA();
				foreach($array_final as $clave => $valor)	
					$resultado[]=Item_abstraccion_relacion_m_n::crear_entrada($this->tabla_relacion, $this->id_a_relacion, $this->id_b_relacion, $id_a, $valor);
			}
		}

		return $resultado;
	}

	public function eliminar($array_ids_b)
	{
		//Parecido al anterior... Simplemente buscamos los items del
		//tipo b que queremos eliminar del a y los borramos. No se 
		//comprueba si existe, simplemente se lanza un "delete" en
		//la tabla de relación con los ids. No se eliminan realmente
		//los items del tipo B.

		if(!is_array($array_ids_b)) die('ERROR: Abstraccion_relacion_m_n no se puede eliminar sin array_ids_b');
		else if(count($array_ids_b))		
		{
			$ids=null;
			$id_a=$this->item_a->ID_INSTANCIA();

			foreach($array_ids_b as $clave => $valor) 
			{
				$ids.="\n'".$valor."',";	//Este /n lo necesitamos... Al entrecomillar los valores los juntaría todos...
			}

			$texto="
DELETE FROM ".$this->tabla_relacion."
WHERE 
".$this->id_a_relacion."='".$id_a."'
AND ".$this->id_b_relacion." IN (".substr($ids, 0, -1).")";

			$consulta=new Consulta_mysql;
			$consulta->consultar($texto);
		}
	}

	public function eliminar_todas_relaciones()
	{
		if(!$this->bandera_carga) die('ERROR: Abstraccion_relacion_m_n no es posible vaciar la relacion sin cargar');
		else
		{
			$texto="
DELETE FROM ".$this->tabla_relacion."
WHERE 
".$this->id_a_relacion."='".$id_a."'";

			$consulta=new Consulta_mysql;
			$consulta->consultar($texto);
		}		
	}

	public function &sincronizar_rapido(&$array_ids_b)
	{
		//Aquí recibimos un array de ids... Este array de ids está
		//representado como "lo que debe quedar" cuando hayamos 
		//terminado. El proceso, por tanto, es:
		//1- Los que estén nuevos y no antiguos van a una cola de añadir.
		//2- Los que estén antiguos y NO estén nuevos van a una cola de eliminar.
		//3- Se llama a los procedimientos correspondientes.
		//Por comodidad devolvemos lo mismo que en crear...

		if(!$this->bandera_carga) die('ERROR: Abstraccion_relacion_m_n no es posible sincronizacion rapida sin cargar');
		else if(!is_array($array_ids_b)) die('ERROR: Abstraccion_relacion_m_n no es posible sincronizacion rapida sin array_ids_b');
		else
		{		
			//Si no hay nada, simplemente array vacío.
			/*if(!count($array_ids_b))
			{
				$resultado=array();
			}
			//Si el array está vacío básicamente vamos a insertar...
			else*/
			if(!count($this->array_items)) 
			{
				$resultado=$this->crear_rapido($array_ids_b);
			}
			else
			{
				$ids_existentes=array();
				foreach($this->array_items as $clave_i=>&$valor_i)	
				{
					$id_item=$valor_i->acc_propiedad($this->id_b_relacion);
					$ids_existentes[]=$id_item;
				}

				//Para cada uno de los nuevos que no estaban, a la cola de añadir.
				$array_insertar=array();
				foreach($array_ids_b as $clave_e => $valor_e)	//Por cada item comparamos...
				{
					if(!in_array($valor_e, $ids_existentes))	//Sólo si no lo encontramos puede pasar.
					{
						$array_insertar[]=$valor_e;
					}
				}

				//Por cada uno antiguo que NO está entre los nuevos, a eliminar.
				$array_eliminar=array();
				foreach($ids_existentes as $clave_c => $valor_c)
				{
					if(!in_array($valor_c, $array_ids_b))
					{
						$array_eliminar[]=$valor_c;
					}
				}

				if(count($array_eliminar)) $this->eliminar($array_eliminar);

				if(count($array_insertar)) $resultado=$this->crear_rapido($array_insertar);		
				else $resultado=array();
			}
		}

		return $resultado;
	}

	public function crear(&$array_datos, $callback_comparar=null)
	{
		//Este es más concienzudo que el crear rápido y lo usaremos
		//para crear items y las propiedades de la relación. En
		//este caso las comprobaciones son totales: se comprueba si
		//el item ya existe en la relación, si el item de la relación
		//realmente existe y demás. Se devuelven los nuevos ids 
		//de relación...
		//Los datos que espera son sets de datos con claves y valores
		//como los que tendría el objeto, lo que supone un trabajo
		//previo con los datos. Un ejemplo de lo que esperamos:
		//[0] => array('id_tipo_b' => 1, 'orden_relacion' => 10),
		//[1] => array('id_tipo_b' => 4, 'orden_relacion' => 20),
		//[2] => array('id_tipo_b' => 3, 'orden_relacion' => 30),
		//Para los callbacks...
		//$callback='funcion_global';
		//$callback=array(&$objeto, 'metodo_publico_del_objeto');
		//$callback3=array('clase', 'metodo_estatico_de_la_clase');

		if(!count($array_datos))
		{
			$resultado=array();

		}
		else	
		{
			$callback_crear=array('Abstraccion_relacion_m_n', 'crear_item_callback');
			$resultado=$this->recorrer_items_proceso($array_datos, $callback_comparar, $callback_crear);	
		}//Fin de else: hay datos en el array que recibe.

		return $resultado;

	}

	public function sincronizar(&$array_datos, $callback_comparar=null)
	{
		/*Para una explicación de cómo espera los datos podemos 
		referirnos al método "crear", porque es más o menos lo mismo...
		Aquí el procedimiento es "añadir" -> "modificar -> "eliminar"...

		Recorreremos todo el array de cosas que vienen y, insertando
		los nuevos y modificando los existentes. A la hora de modificar
		se usará el mismo método callback para ver si existe o no. Luego
		se hará una segunda pasada para eliminar todos aquellos que
		estando el array original NO están en el array nuevo. Aquí
		de nuevo usaremos el mismo método para comparar PERO dado que
		podríamos modificar cosas importantes NO pasaremos por los
		items que han sido modificados con anterioridad.
		*/

		if(!count($array_datos))
		{
			$resultado=array();
		}
		else	
		{
			//Fase 1: creamos y modificamos.
			$callback_comparar=strlen(trim($callback_comparar)) ? $callback_comparar : array('Abstraccion_relacion_m_n', 'comparar_items_r');
			$callback_crear=array('Abstraccion_relacion_m_n', 'crear_item_callback');
			$callback_modificar=array('Abstraccion_relacion_m_n', 'modificar_item_callback');

			$resultado=$this->recorrer_items_proceso($array_datos, $callback_comparar, $callback_crear, $callback_modificar);	

			//Fase 2: localizamos los items que están en el array
			//del objeto y NO estan en el array de "resultado"...
			//El proceso anterior nos ha devuelto los ids de los
			//creados nuevos y de los modificados y todos los items
			//que hemos pasado han ido a un sitio u otro de modo que
			//es seguro borrar (en otras palabras, no es posible que
			//un item de los que hayamos pasado no se haya creado
			//o modificado).

			//Podemos usar los métodos de comparación que ya tenemos
			//generando un nuevo array de items_r a partir de
			//"resultado"...

			$array_resultado=array();
			
			foreach($resultado as $clave => &$valor)
			{
				//No vamos a cargar los datos del item b, no los usaremos para comparar
				//aún en las funciones de callback...
				$array_resultado[]=$this->crear_item_r($valor, false);	

			}

			foreach($this->array_items as $clave => &$valor)
			{
				$item_repetido=self::existe_item_en_array($valor, $array_resultado, $this->id_b_relacion, $callback_comparar);
					
				//Si no encontramos el item es que no estaba 
				//entre los items a sincronizar...
				if(!$item_repetido) 
				{
					$valor->eliminar();
				}		
			}

			unset($array_resultado);

		}//Fin de else: hay datos en el array que recibe.

		return $resultado;
	}

	private function recorrer_items_proceso(&$array_datos, $callback_comparar, $callback_no_existe, $callback_existe=null)
	{
		if(!$this->bandera_carga) die('ERROR: Abstraccion_relacion_m_n no es posible proceso completo sin cargar');
		else
		{
			$callback_comparar=strlen(trim($callback_comparar)) ? $callback_comparar : array('Abstraccion_relacion_m_n', 'comparar_items_r');

			if(!is_callable($callback_comparar)) 
			{
				die('ERROR: Abstraccion_relacion_m_n, imposible generar callback');
			}

			$intentar_crear=is_callable($callback_no_existe);
			$intentar_modificar=is_callable($callback_existe);

			//Aquí las comprobaciones las hacemos creando
			//el item_r de turno y comparando si está en los
			//que tenemos. Si no está y tiene el valor del
			//item_b entonces lo marcamos para insertar.
			//El item b sólo tiene que existir, no se
			//comprueba si está borrado o no (eso si se 
			//puede hacer al cargar, con los criterios).

			$nombre_clase_b=get_class($this->item_b);

			foreach($array_datos as $clave => &$valor)
			{
				$temp=$this->crear_item_r($valor, true);
				$existe_item=$temp->acc_item()->ID_INSTANCIA();
				$item_se_corresponde=$temp->acc_propiedad($this->id_a_relacion)==$this->item_a->ID_INSTANCIA();

				//La comprobación de la repetición puede ser más complicada, puesto que el
				//concepto de qué es distinto puede variar... En este caso, para ser
				//iguales basta que tengan el campo de id_b igual... Al hacer esto así
				//evitamos que dos items b aparezcan con atributos diferentes (lo que 
				//sería posible en ciertos contextos) y posibilitamos la acción de 
				//sincronizar. Es una posibilidad añadir una función callback de usuario 
				//para comparar pero por defecto se usa la propia.
				//Vamos a comprobar también si este item que creamos se corresponde con nuestro item a;

				if($existe_item && $item_se_corresponde)
				{
					$item_repetido=self::existe_item_en_array($temp, $this->array_items, $this->id_b_relacion, $callback_comparar);

					//Ahora vamos a usar callbacks porque
					//este método que usamos para iterar lo
					//comparten "crear" y "sincronizar".

					if(!$item_repetido)
					{
						if($intentar_crear)
						{
							$resultado[]=call_user_func($callback_no_existe, $temp);
						}
					}
					else
					{
						if($intentar_modificar)
						{
							//Aquí aprovechamos que "item_repetido" es realmente la
							//instancia de item que existe y, por tanto, vamos a modificar.
							$resultado[]=call_user_func($callback_existe, $temp, $item_repetido);
						}
					}
				}

			}//Fin de foreach($datos que recibe).
		}//Fin de else: estamos cargados.		

		return $resultado;
	}

	private static function crear_item_callback(&$temp)
	{
		//No hay array de inserción... Simplemente creamos
		//el item "b" con sus propios datos.

		$temp->crear($temp->acc_array_propiedades());
		return $temp->ID_INSTANCIA();
	}

	private static function modificar_item_callback(&$temp, &$original)
	{
		//El item de callback ha llegado desde unos datos que se pasan
		//como parámetros... Es posible que esos datos NO sean los que
		//haya en base de datos, de modo que sacamos el array de 
		//propiedades y actualizamos el item b.
			
//		$temp->modificar($temp->acc_array_propiedades());
		$original->modificar($temp->acc_array_propiedades());
		return $original->ID_INSTANCIA();
	}

	private static function &existe_item_en_array(&$temp, &$array, $id_b_relacion, $callback_comparar)
	{
		//Compara cada item del array del objeto con el item temp 
		//con la función callback_comparar... Si existe devuelve ese
		//mismo objeto, si no devuelve falso.

		if(!count($array))
		{
			return false;
		}
		else
		{
			foreach($array as $clave => &$valor)
			{
				$son_iguales=call_user_func($callback_comparar, $temp, $valor, $id_b_relacion);
				if($son_iguales)
				{
					return $valor;
				}
			}

			return false;
		}
	}

	public static function comparar_items_r($objeto_nuevo, $objeto_original, $propiedad_id_b_relacion)
	{
		//Este es, por defecto, el método de comparar items... Recibe dos
		//objetos del tipo r y un nombre de propiedad, que es el nombre
		//del id de tipo b en la base de datos. Simplemente compara.
		//Este método se usa como callback y puede usarse otro si
		//se especifica en la función de turno.

		return $objeto_nuevo->acc_propiedad($propiedad_id_b_relacion)==$objeto_original->acc_propiedad($propiedad_id_b_relacion);
	}
}

class Item_abstraccion_relacion_m_n extends Contenido_bbdd_dinamico
{
	//Esto representa a un item r, es decir, una clase que contiene las 
	//propiedades de una relación entre dos objetos y además tiene una copia
	//del objeto "b" en si. Lo usamos a la hora de "Obtener los objetos b
	//que tiene el objeto a".

//No se cargan los datos que se pasan. El problema puede estar en la clase
//contenido_bbdd_dinamico.

	private $item=null;
	private $clave_item_b=null;
	private $nombre_clase_b=null;
	public function &acc_item() {return $this->item;}

	//TODO: Bullshit... otra vez. Con razón se eliminó esto del motor...
	public function NOMBRE_CLASE() {return null;}
	public function TABLA() {return null;}
	public function ID() {return null;}

	public function __construct(&$datos=null, $tabla, $id, $clave_item_b, $nombre_clase_b, $propiedades=null)
	{
		//Generamos el diccionario...
		$diccionario=array();

		$this->clave_item_b=$clave_item_b;
		$this->nombre_clase_b=$nombre_clase_b;

		if(!is_null($propiedades)) 
		{
			if(!is_array($propiedades)) die('ERROR: Item_abstraccion_relacion_m_n esperaba propiedades como array');
			else foreach($propiedades as $clave => $valor) 	
			$diccionario[$clave]=$clave;
		}

		parent::__construct($datos, $diccionario, $tabla, $id, $propiedades);
	}

	public static function crear_entrada($tabla, $campo_id_a, $campo_id_b, $id_a, $id_b)
	{
		//Eso sólo tiene sentido como una forma de crear la entrada
		//en la base de datos que no hace nada, nada más, y necesita
		//que le pasemos todos los parámetros.

		$texto_insertar="
INSERT INTO ".$tabla."
(".$campo_id_a.", ".$campo_id_b.")
VALUES
(
'".$id_a."', 
'".$id_b."'
)";

		$consulta=new Consulta_mysql;
		$consulta->consultar($texto_insertar);

		return Consulta_mysql::ultimo_id();	
	}

	public function asociar_item_b(&$item_b)
	{
		//Simplemente carga el item b...
		$this->item=$item_b;
	}

	public function generar_item_b()
	{
		$id_b=isset($this->array_propiedades[$this->clave_item_b]) ? $this->array_propiedades[$this->clave_item_b] : 0;
		$this->item=new $this->nombre_clase_b($id_b);	
	}
}

////////////////////////////////////////////////////////////////////////////////
//	Aquí hay utilidades y más utilidades...
////////////////////////////////////////////////////////////////////////////////

abstract class Abstraccion_relacion_m_n_tablas
{
	//Se guardarán aquí las definiciones de las diferentes relaciones
	//activas de forma que si hay tres objetos del mismo tipo no haya
	//tres definiciones. Cada definición se guarda como una clave que es
	//la concatenación de los objetos "a" y "b" más un parámetro extra,
	//por si lo queremos.

	private static $diccionario=array();

	public static function obtener_relacion(&$clase_a, &$clase_b, $tabla_r, $extra=null)
	{
		$nombre_clave=get_class($clase_a).'_'.get_class($clase_b).'_'.$tabla_r.$extra;

		if(!isset(self::$diccionario[$nombre_clave]))
		{
			self::crear_clave($clase_a, $clase_b, $tabla_r, $extra);
		}

		if(!isset(self::$diccionario[$nombre_clave])) die('ERROR: abstraccion_relacion_m_n_tablas imposible encontrar clave '.$nombre_clave);
		else return self::$diccionario[$nombre_clave];
	}

	private static function crear_clave(&$clase_a, &$clase_b, $tabla_r, $extra)
	{
		$nombre_clave=get_class($clase_a).'_'.get_class($clase_b).'_'.$tabla_r.$extra;
		
		//Aquí lo que pretendemos es obtener todos los datos de la tabla
		//b y todos los datos de la tabla r, que sería la tabla de 
		//relación.

		$tabla_b=$clase_b->TABLA();

		self::$diccionario[$nombre_clave]=new Abstraccion_relacion_m_n_par_tablas($tabla_b, $tabla_r);		
	}
}

class Abstraccion_relacion_m_n_par_tablas
{
	//Esto representa a un par de tablas. Usaremos estos pares para rellenar
	//los campos diferentes de las relaciones.

	private $tabla_b=null;
	private $tabla_r=null;

	public function &acc_tabla_b() {return $this->tabla_b;}
	public function &acc_tabla_r() {return $this->tabla_r;}

	public function __construct($tabla_b, $tabla_r)
	{
		$this->tabla_b=new Abstraccion_relacion_m_n_tabla($tabla_b);
		$this->tabla_r=new Abstraccion_relacion_m_n_tabla($tabla_r);
	}
}

class Abstraccion_relacion_m_n_tabla
{
	//Esta es la definición de una tabla: un nombre y un array de campos
	//por cada uno de los que tuviera.
	
	private $tabla=null;
	private $campos=array();
	private $indice_campos=array();
	private $campo_clave=null;

	public function acc_tabla() {return $this->tabla;}
	public function &acc_campos() {return $this->campos;}
	public function &acc_indice_campos() {return $this->indice_campos;}
	public function &acc_campo_clave() {return $this->campo_clave;}

	public function __construct($nombre_tabla)
	{
		$this->tabla=$nombre_tabla;
		$this->campos=self::obtener_campos_tabla($this->tabla);

		$i=0;

		foreach($this->campos as $clave => &$valor)
		{
			if($valor->acc_clave()=='PRI') $this->campo_clave=&$valor;
			$this->indice_campos[$i++]=&$valor;
		}
	}

	private static function &obtener_campos_tabla($tabla)
	{
		$array=array();

		$texto="
DESC ".$tabla;

		$consulta=new Consulta_Mysql;
		$consulta->consultar($texto);

		while($consulta->leer())
		{
			$temp=new Abstraccion_relacion_m_n_campo($consulta->resultados());
			$array[$temp->acc_nombre()]=$temp;
		}
	
		return $array;
	}

	public function concatenar_campos_tabla()
	{
		$resultado=null;

		foreach($this->campos as $clave => &$valor) 
		{
			$resultado.=$this->tabla.'.'.$valor->acc_nombre().',';
		
		}

		return substr($resultado, 0, -1);
	}
}

class Abstraccion_relacion_m_n_campo
{
	//Esto es un campo de una tabla...

//	const CLAVE_NOMBRE=0;
//	const CLAVE_TIPO=1;
//	const CLAVE_NULO=2;
//	const CLAVE_CLAVE=3;
//	const CLAVE_DEFECTO=4;
//	const CLAVE_EXTRA=5;

	const CLAVE_NOMBRE='Field';
	const CLAVE_TIPO='Type';
	const CLAVE_NULO='Null';
	const CLAVE_CLAVE='Key';
	const CLAVE_DEFECTO='Default';
	const CLAVE_EXTRA='Extra';

	private $nombre=null;
	private $tipo=null;
	private $nulo=null;
	private $clave=null;
	private $defecto=null;
	private $extra=null;

	public function acc_nombre() {return $this->nombre;}
	public function acc_tipo() {return $this->tipo;}
	public function acc_nulo() {return $this->nulo;}
	public function acc_clave() {return $this->clave;}
	public function acc_defecto() {return $this->defecto;}
	public function acc_extra() {return $this->extra;}

	public function __construct($tupla)
	{
		$this->nombre=$tupla[self::CLAVE_NOMBRE];
		$this->tipo=$tupla[self::CLAVE_TIPO];
		$this->nulo=$tupla[self::CLAVE_NULO];
		$this->clave=$tupla[self::CLAVE_CLAVE];
		$this->defecto=$tupla[self::CLAVE_DEFECTO];
		$this->extra=$tupla[self::CLAVE_EXTRA];
	}
}
?>
