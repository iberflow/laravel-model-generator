<?php

use \Illuminate\Filesystem\Filesystem;

class SetGetGeneratorTest extends PHPUnit_Framework_TestCase
{
	protected $generator;

	protected $setFunctionStub;
    protected $getFunctionStub;

	public function __construct() {
		// load only once
		$this->getFunctionStub = file_get_contents("src/stubs/getFunction.stub");
		$this->setFunctionStub = file_get_contents("src/stubs/setFunction.stub");
	}

	public function setUp() {
		$this->generator = new Iber\Generator\Utilities\SetGetGenerator([
        	"attribute_name",
        	"test"
    	], $this->getFunctionStub, $this->setFunctionStub);
	}


    public function testCamelCaseFunction()
    {
        $this->assertEquals("getTest",$this->generator->attributeNameToFunction("get", "test"));

        $this->assertEquals("getTestName",$this->generator->attributeNameToFunction("get", "test_name"));

        $this->assertEquals("getTestname",$this->generator->attributeNameToFunction("get", "testname"));

        $this->assertEquals("getTestName",$this->generator->attributeNameToFunction("get", "test name"));
    }


    public function testCreateGetFunctionFromAttributeName() {
    	$function = $this->generator->createGetFunctionFromAttributeName("test");
    	$this->assertContains("getTest", $function);
    	$this->assertContains('$this->test', $function);
    }

    public function testCreateSetFunctionFromAttributeName() {
    	$function = $this->generator->createSetFunctionFromAttributeName("test");
    	$this->assertContains("setTest", $function);
    	$this->assertContains('$this->attributes', $function);
    	$this->assertContains('"test"', $function);
    }

    public function testGenerateGet() {
    	$text = $this->generator->generateGetFunctions();
    	$this->assertNotEquals("", $text);
    	$this->assertNotContains("setTest", $text);
    	$this->assertContains("getTest", $text);
    	$this->assertContains("getAttributeName", $text);
    }

    public function testGenerateSet() {
    	$text = $this->generator->generateSetFunctions();
    	$this->assertNotEquals("", $text);
    	$this->assertNotContains("getTest", $text);
    	$this->assertContains("setTest", $text);
    	$this->assertContains("setAttributeName", $text);
    }
}
