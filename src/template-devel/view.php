<?php
class View {

	public function set_template_string($str) {
		if(!strlen($str)) {
			$this->fail("set_template_string must be called with a string!");
	}
		$this->template_source=$str;
		return $this;
	}

	public function set_template_file($filename) {
		if(!file_exists($filename) || !is_file($filename)) {
			$this->fail("'".$filename."' is not a valid template source file!");
		}
		$this->template_source=file_get_contents($filename);
		return $this;
	}

	public function set($key, $value) {
		$this->values[$key]=$value;
		return $this;
	}

	public function render() {
		try {
			$t=Tokenizer::from_string($this->template_source);
			$p=new Parser;
			$op=$p->parse($t->tokenize());

			do{
				$op=$this->process_operation($op);
			}while($op!=null);
		}
		catch(\Exception $e) {
			$this->fail('Render error: '.$e->getMessage());
		}
	}

	//Inner workings...

	private $template_source=null;
	private $values=[];

	const MARK_ARRAY_INDEX='.';
	const MARK_OBJECT_PROPERTY='>';
	const MARK_OBJECT_METHOD='*';
	const MARK_REGEXP='\.\>\*';

	private function process_operation($op) {
		switch(get_class($op)) {
			
			//TODO... Oh god...
			default:
				$this->fail('Unknown token '.get_class($op)); break;
		}

		//TODO: RETURN NEXT NODEEE OR THIS SHIT WILL FUCKING FAIL!!!.
		return null;
	}

	//Resolves a value by following a path from the $values array.
		//TODO: MATCH SHOULD NOT LONGER BE LIKE THAT!!!!! ARRAY AND SHIT!!!.
	private function resolve_scalar($match) {

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
							$this->fail("Deep array index '".$next."' is invalid in view");
						}
						$next=(int)$next;
					}
					else {
						if(!array_key_exists($next, $ref)) {
							$this->fail("Deep array key '".$next."' does not exist in view");
						}
					}
					$ref=&$ref[$next]; break;

				case self::MARK_OBJECT_PROPERTY:
					if(!property_exists($ref, $next)) {
						$this->fail("Property '".$next."' does not exist in object view");
					}
					$ref=&$ref->$next; break;

				case self::MARK_OBJECT_METHOD:
					if(!method_exists($ref, $next)) {
						$this->fail("Method '".$next."' does not exist in object view");
					}
					$ref=&call_user_func([$ref, $next]); break;

				default:
					$this->fail("'".$type."' is not a valid indirection marker for a template"); break;
			}
		}
	
		if(!is_scalar($ref)) {
			$this->fail($match[1].' is not scalar and cannot be resolved to a value');
		}

		return $ref;
	}

	private function fail($msg) {
		throw new View_exception($src);
	}
}
