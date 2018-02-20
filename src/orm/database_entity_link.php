<?php
namespace Renoir_engine\ORM;

//!Defines part of the mapping of an entity class (a single field).

//!Each entry defines the mapping between an entity class property and the respective 
//!database field, as well as its accessor and setter functions, whether or not
//!the field is the primary key and the type of data that must be used when
//!loading data when using the "set_properties" method of the class, 
//!The only types that can be chosen to map into properties are PHP's primitive
//!types int, boolean, float or integer. By default the "string" mapping is
//!chosen, as it is basically safe.
//!All mapped entities MUST have a primary key. This leaves out some uses, 
//!but we have designed this for simplicity.
//!See Database_entity_link_repository for examples.

class Database_entity_link {

	public $property;		//!<	Property name in the class.
	public $field;			//!<	Corresponding field name in the database.
	public $property_type;		//!<	Datatype to be used in the class when retrieving data.
	public $is_id;			//!<	Indicates if the field is the primary key. 
	public $accesor;		//!<	Defines the name of the accesor (getter) function of the class.
	public $setter;			//!<	Defines the name of the setter function of the class.

	const VOID=666;		//!< Indicates "no accesor" or "no setter".
	const USE_DEFAULT=667;	//!< Indicates "get name of accesor and getter from property name".
	const IS_ID=668;	//!< Indicates that the field is the primary key.

	const TYPE_STRING=669;	//!< Indicates a data type of string.
	const TYPE_INT=670;	//!< Indicates a data type of integer.
	const TYPE_BOOL=671;	//!< Indicates a data type of boolean.
	const TYPE_FLOAT=672;	//!< Indicates a data type of float.

	//!Creates an object. The two first parameters map to the property and field name. The rest use default values that can be changed if needed.
	public static function create($_p, $_f, $_t=self::TYPE_STRING, $_i=false, $_a=self::USE_DEFAULT, $_s=self::USE_DEFAULT) {

		$proc=function($var, $prop, $def_name) {

			switch($var) {
				case self::VOID: return null; break;
				case self::USE_DEFAULT: return $def_name.$prop; break;
				default:
					if(!is_string($var)) {
						throw new ORM_exception("Getter or setter name must be string in link definition");
					}
					return $var;
				break;
			}
		};

		if($_t < self::TYPE_STRING || $_t > self::TYPE_FLOAT) {
			throw new ORM_exception("Property type (".$_t.") for ".$_p."'s mapping is invalid");
		}

		$_a=$proc($_a, $_p, 'get_');
		$_s=$proc($_s, $_p, 'set_');

		return new Database_entity_link($_p, $_f, $_t, $_i===self::IS_ID, $_a, $_s);
	}

	//Creates the object. Parameter map to the properties of the object in order.
	private function __construct($_p, $_f, $_t, $_i, $_a, $_s) {
		$this->property=$_p;
		$this->field=$_f;
		$this->property_type=$_t;
		$this->is_id=$_i;
		$this->accesor=$_a;
		$this->setter=$_s;
	}

}
