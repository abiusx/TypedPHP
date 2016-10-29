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
}

class TypedTest extends PHPUnit_Framework_TestCase
{
	function setUp()
	{
		$this->obj=new TypedWorld;
	}
    function testClassDeclaration()
    {
    	$this->assertTrue(class_exists("TypedWorld"));
    }
    function testClassInheritance()
    {
    	$this->assertTrue(is_a($this->obj, "World",true));
    }
}

// $t=5;
// $x=new TypedHello($t);
// $x->a();
// $x->a();
