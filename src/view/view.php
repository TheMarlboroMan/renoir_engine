<?php
namespace Renoir_engine\View;

//TODO: Document.

//TODO: Add support for including other views and pass on their variables??
//TODO: Add support for view inheritance.

//Supports:
//	. array key indirection or numeric index indirection...
//	> object property indirection
//	* object method call

class View {

	const MARK_ARRAY_INDEX='.';
	const MARK_OBJECT_PROPERTY='>';
	const MARK_OBJECT_METHOD='*';
	const MARK_REGEXP='\.\>\*';

	private $values=[];
	private $filename=null;

	public function set_template_file($path) {
		$this->filename=$path;
		return $this;
	}

	public function set($key, $value) {
		$this->values[$key]=$value;
		return $this;
	}

	public function render() {
	
		//TODO: Check that the file exists.
		$this->parse(file_get_contents($this->filename));
	}

	//Parses the template file and returns its resolution.
	protected function parse($str_template) {

		//Locate the replacement tags and resolve to their values.
		$regexp="/{{(.+)}}/";
		//TODO: What about the headers?????
		return preg_replace_callback($regexp, [$this, 'resolve_value'], $str_template);
	}

	//Resolves a value by following a path from the $values array.
	private function resolve_value($match) {

		$ref=null;
		$parts=preg_split('/(['.self::MARK_REGEXP.'])/', $match[1], -1, PREG_SPLIT_DELIM_CAPTURE);
		$key=$parts[0];

		if(!array_key_exists($key, $this->values)) {
			throw new View_exception("Key '".$key."' does not exist in view");
		}

		$ref=&$this->values[$key];
		for($i=1; $i<count($parts); $i+=2) {

			$type=$parts[$i];
			$next=$parts[$i+1];

			switch($type) {
				case self::MARK_ARRAY_INDEX:

					if(is_numeric($next)) {
						if($next < 0 || $next >= count($ref)) {
							throw new View_exception("Deep array index '".$next."' is invalid in view");
						}
						$next=(int)$next;
					}
					else {
						if(!array_key_exists($next, $ref)) {
							throw new View_exception("Deep array key '".$next."' does not exist in view");
						}
					}
					$ref=&$ref[$next]; break;

				case self::MARK_OBJECT_PROPERTY:
					if(!property_exists($ref, $next)) {
						throw new View_exception("Property '".$next."' does not exist in object view");
					}
					$ref=&$ref->$next; break;

				case self::MARK_OBJECT_METHOD:
					if(!method_exists($ref, $next)) {
						throw new View_exception("Method '".$next."' does not exist in object view");
					}
					$ref=&call_user_func([$ref, $next]); break;

				default:
					throw new View_exception("'".$type."' is not a valid indirection marker for a template"); break;
			}
		}
	
		if(!is_scalar($ref)) {
			throw new View_exception($match[1].' is not scalar and cannot be resolved to a value');
		}

		return $ref;
	}
};
