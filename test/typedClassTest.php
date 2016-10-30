<?php
class World 
{
	function &b()
	{

	}
	function a()
	{

	}
}
class Hello extends World {
	public $x;
	/**
	 * [__construct description]
	 * @param int &$t [description]
	 */
	function __construct(&$t)
	{
		$this->x=$t;
	}
	private function p() {}
	/**
	 * [a description]
	 * @param  string $x [description]
	 * @return int    [description]
	 */
	function a($x='hello')
	{
		echo $this->x++;
	}
	function return_not_defined()
	{
		return "abc";
	}
	/**
	 * [return_param description]
	 * @param  mixed $param [description]
	 * @return int        [description]
	 */
	function return_param($param)
	{
		return $param;
	}
}

class TypedTest extends PHPUnit_Framework_TestCase
{
	function setUp()
	{
		$this->world=new TypedWorld;
		$x=5;
		$this->hello=new TypedHello($x);
	}
    function testReturnMatch()
    {
    	$this->assertTrue(is_int($this->hello->return_param(123)));
    	$this->expectException(TypeMistmatchException::class);
    	$this->assertTrue(is_int($this->hello->return_param("123")));
    }
    function testReturnTypeNotDefined()
    {
    	$this->assertNotNull($this->hello->return_not_defined());
    }
    function testReturnTypeMistmatch()
    {
    	$this->expectException(TypeMistmatchException::class);
    	$this->assertNull($this->hello->a());
    }
    function testClassDeclaration()
    {
    	$this->assertTrue(class_exists("TypedWorld"));
    }
    function testClassInheritance()
    {
    	$this->assertTrue(is_a($this->world, "World",true));
    }
}

// $t=5;
// $x=new TypedHello($t);
// $x->a();
// $x->a();
