<?php
namespace Renoir_engine\ORM;

//Defines part of the mapping of an entity class. 
//Each entry defines the mapping between an entity class property and the respective 
//database field, as well as its accessor and setter functions, whether or not
//the field is the primary key and the type of data that must be used when
//loading data when using the "set_properties" method of the class, 
//The only types that can be chosen to map into properties are PHP's primitive
//types int, boolean, float or integer. By default the "string" mapping is
//chosen, as it is basically safe.

class Database_entity_link {

	public $property;
	public $field;
	public $property_type;
	public $is_id;
	public $accesor;
	public $setter;

	const VOID=666;		//Indicates "no accesor" or "no setter".
	const USE_DEFAULT=667;	//Indicates "get name of accesor and getter from property name".
	const IS_ID=668;

	const TYPE_STRING=669;
	const TYPE_INT=670;
	const TYPE_BOOL=671;
	const TYPE_FLOAT=672;

	private function __construct($_p, $_f, $_t, $_i, $_a, $_s) {
		$this->property=$_p;
		$this->field=$_f;
		$this->property_type=$_t;
		$this->is_id=$_i;
		$this->accesor=$_a;
		$this->setter=$_s;
	}

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
}
