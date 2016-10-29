<?php

class TypedPHPException extends Exception {
	function __construct($msg,$file,$line)
	{
		parent::__construct($msg);
		$this->file=$file;
		$this->line=$line;
	}
}
class TypeMistmatchException extends TypedPHPException {};
class TypeNotFoundException extends TypedPHPException {};
#TODO: support typed properties and getter/setters
#can't encapsulate the typed object, otherwise type invariants fail
class TypedPHP
{
	/**
	 * List of PHP types
	 * @var array
	 */
	protected static $types=array(
		"bool"=>"bool","true"=>"bool","false"=>"bool","boolean"=>"bool"
		,"void"=>"null","null"=>"null"
		,"float"=>"float","double"=>"float"
		,"int"=>"int","integer"=>"int"
		,"string"=>"string"
		,"array"=>"array"
		,"object"=>"object"
		,"mixed"=>"mixed");
	/**
	 * Convert a typestring (e.g. int|string|array(bool) ) to an array of types
	 * @param  string $typestring [description]
	 * @param  string $file       [description]
	 * @param  string|int $line       [description]
	 * @return array             [description]
	 */
	private static function typestring_to_list($typestring,$file,$line)
	{
		$list=explode("|",$typestring);
		$out=[];
		foreach ($list as $t)
		{
			if (substr($t,-2)=="[]")
			{
				$out[]=self::typestring_to_list(substr($t,1,-3)); //array of types, e.g. (int|string)[]
				continue;
			}
			if (substr($t,6)=="array(" and strlen($t)>7)
			{
				$out[]=self::typestring_to_list(substr($t,6,-1)); //array of types, e.g. array(int|string)
				continue;
			}
			if (!array_key_exists(strtolower($t), self::$types) and !class_exists($t))
			{
				throw new TypeNotFoundException("Type '{$t}' not found.",$file,$line);
				continue;
			}
			$out[]=$t;
		}
		return $out;

	}
	/**
	 * Checks whether a specific type is in a typedef list
	 * @param  string $_type [description]
	 * @param  array $types [description]
	 * @return bool        [description]
	 */
	protected static function check_type_in_types($_type,$types)
	{
		$arg=$_type;
		foreach ($types as $t)
		{
			$type=strtolower($t);

			if ($type=="mixed") return true;
			elseif (is_array($type) and check_type_in_types($arg,$type)) return true; //array
			elseif ((is_object($arg) and strtolower(get_class($arg))==strtolower($type))) return true;
			elseif (strtolower(gettype($arg))==$type) return true;
		}
	}

	public static function check_args_type($class,$method_name,$args)
	{
		#TODO: need arg names to match via doc, change arg{$i} names to the actual arg names.
		$r=new ReflectionMethod($class,$method_name);
		$doc=$r->getDocComment();
		$line=$r->getStartLine();
		$file=$r->getFileName();
		$endline=$r->getEndLine();
		var_dump($doc);
		if (!preg_match("/@return\s+(.*?)\s+/", $doc,$match)) 
			return false;
		$types=self::typestring_to_list($match[1],$file,$line);
		foreach ($types as $type)
			if ((is_object($arg) and strtolower(get_class($arg))==strtolower($type))) return true;
			elseif (strtolower(gettype($arg))==strtolower($type)) return true;

		throw new TypeMistmatchException("Type of return argument '".gettype($arg).
			"' does not match any of the allowed types: '".implode(" or ",$types)."'",$file,$endline);
		return false;
	}
	
	public static function check_return_type($class,$method_name,$arg)
	{
		$r=new ReflectionMethod($class,$method_name);
		$doc=$r->getDocComment();
		$line=$r->getStartLine();
		$file=$r->getFileName();
		$endline=$r->getEndLine();
		if (!preg_match("/@return\s+(.*?)\s+/", $doc,$match)) 
			return false;
		$types=self::typestring_to_list($match[1],$file,$line);
		if (self::check_type_in_types($arg,$types)) return true;

		throw new TypeMistmatchException("Type of return argument '".gettype($arg).
			"' does not match any of the allowed types: '".implode(" or ",$types)."'",$file,$endline);
		return false;
	}
	/**
	 * Create a typed version of a class named Typed{$class}
	 * @param  string $class [description]
	 * @return bool        success
	 */
	protected static function typed_class($class)
	{
		$reflection = new ReflectionClass($class);
		$filename = $reflection->getFileName();
		$startline = $reflection->getStartLine(); // getStartLine() seems to start after the {
		$endline = $reflection->getEndLine();

		$newclass="Typed".$class;
		if (class_exists($newclass))


		if(file_exists($filename))
		{
		    $contents = file($filename);
		    $class_code=array_slice($contents, $startline,$endline-$startline);
		}
		$methods=[];
		foreach ($reflection->getMethods() as $method_reflection)
		{
			$method_code=$method_reflection."";
			$method_ispublic=preg_match("/Method \[(.*?) public method .*? \]/",$method_code);
			if (!$method_ispublic) continue;
			$method_isref=preg_match("/Method \[(.*?) method &.*? \]/",$method_code);
			preg_match("/- Parameters\s+\[(.*?)]\s+/",$method_code,$match);
			if ($match)
				$paramcount=$match[1];
			else
				$paramcount=0;
			$method_class=$method_reflection->class;
			$method_name=$method_reflection->name;
			$args=$argsSig=[];
			for ($i=0;$i<$paramcount;++$i)
			{
				$isref=preg_match("/Parameter #{$i} \[.*?&.*?\]/", $method_code);
				$optional=preg_match("/Parameter #{$i} \[ <optional> .*? = (.*?) \]/", $method_code,$optional_val);
				if ($optional)
					$optional_val=$optional_val[1];
				// echo "isref:";
				// var_dump($isref);
				// echo "optional:";
				// var_dump($optional);
				// if ($optional)
				// 	var_dump("optional value:",$optional_val);
				$args[]=["name"=>"arg{$i}","isref"=>$isref,"optional"=>$optional,"optional_val"=>$optional?$optional_val:null];
				$sig="";
				if ($isref)
					$sig="&";
				$sig.="\$arg{$i}";
				if ($optional)
					$sig.="={$optional_val}";
				$argsSig[]=$sig;
			}
			// var_dump($paramcount);
			// var_dump($args);
			// echo($method_code);
			if ($paramcount)
				$args_forward=implode(",",array_map(function($x){ return "\$arg".$x;}, range(0,$paramcount-1)));
			else
				$args_forward="";
			if ($method_isref)
				$method_isref="&";
			else
				$method_isref="";
			$signature="function {$method_isref}{$method_name}(".implode(",",$argsSig).") {
				\$this->type_check_args('{$method_class}','{$method_name}',[$args_forward]);
				\$r={$method_class}::{$method_name}({$args_forward});
				\$this->type_check_return('{$method_class}','{$method_name}',\$r);
				return \$r;
			}";
			$methods[]=["class"=>$method_class,"name"=>$method_name,"signature"=>$signature];
		}
		$methods_signature=implode("\n\t\t",array_map(function($x){return $x['signature'];}, $methods));
		$source_code=("class Typed{$class} extends $class {
			function type_check_return(\$class,\$method_name,\$arg) 
			{
				".__CLASS__."::check_return_type(\$class,\$method_name,\$arg);
			}
			function type_check_args(\$class,\$method_name,\$args) 
			{
				".__CLASS__."::check_args_type(\$class,\$method_name,\$args);
			}

			{$methods_signature}
		}");
		// var_dump($source_code);
		eval($source_code);
		return true;
	}
	/**
	 * The autoloader that creates typed classes if they don't exist
	 * @param  string $class [description]
	 * @return bool        [description]
	 */
	public static function autoload($class)
	{
		if (strtolower(substr($class,0,5))=="typed")
		{
			$class=substr($class,5);
		 	if (!class_exists($class))
		 	{
				trigger_error("class {$class} not found");
				return false;
		 	}
		}
		else
			return false;

		return self::typed_class($class);
	}

	/**
	 * Typed function call
	 * @param  string $name [description]
	 * @param  array(mixed) $args [description]
	 * @return mixed       [description]
	 */
	public function __call($name,$args)
	{
		if (function_exists("typed_{$name}"))
			return call_user_func_array("typed_{$name}", $args);

	}

}
spl_autoload_register("TypedPHP::autoload");
