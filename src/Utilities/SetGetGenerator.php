<?php
namespace Iber\Generator\Utilities;

use \Illuminate\Filesystem\Filesystem;

class SetGetGenerator  {
	
	/**
	 * Lists of attributes to convert
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Returns a template stub for set function
	 * @var string
	 */
	protected $setStub = "";

	/**
	 * Returns a template stub for get function
	 * @var string
	 */
	protected $getStub = "";

	/**
	 * [__construct description]
	 * @param array  $attributes with attributes names
	 * @param string $getStub    set stub template
	 * @param string $setStub    get stub template
	 */
	public function __construct(array $attributes, $getStub, $setStub) 
	{
		$folder = __DIR__ . '/../stubs/';

		$this->attributes = $attributes;
		$this->getStub = $getStub;
		$this->setStub = $setStub;
		
	}

	/**
	 * Returns the get functions in string
	 * @return string
	 */
	public function generateGetFunctions() {
		return $this->generateWithFunction("createGetFunctionFromAttributeName");
	}

	/**
	 * Returns the set functions in string
	 * @return string
	 */
	public function generateSetFunctions() {
		return $this->generateWithFunction("createSetFunctionFromAttributeName");
	}

	/**
	 * Loops the attributes and build the string with given function name
	 * @param  string $function 
	 * @return  string
	 */
	protected function generateWithFunction($function) {
		$string = "";

		foreach ($this->attributes as  $attributeName) {
			$string .= $this->$function($attributeName);
		}

		return $string;
	}

	/**
	 * Bulds the get function for the attribute name
	 * @param  string $attributeName  
	 * @return string                 
	 */
	public function createGetFunctionFromAttributeName($attributeName) {
		return $this->createFunctionFromAttributeName("get", $attributeName, $this->getStub);
	}

	/**
	 * Bulds the set function for the attribute name
	 * @param  string $attributeName  
	 * @return string                 
	 */
	public function createSetFunctionFromAttributeName($attributeName) {
		return $this->createFunctionFromAttributeName("set", $attributeName, $this->setStub);
	}

	/**
	 * Builds the funciton and creates the function from the stub template
	 * @param  string $prefixFunction 
	 * @param  string $attributeName  
	 * @param  string $stubTemplate   
	 * @return string                 
	 */
	protected function createFunctionFromAttributeName($prefixFunction, $attributeName, $stubTemplate) {
		$function = $this->attributeNameToFunction($prefixFunction, $attributeName);

		// change to stub?
		
		return $this->createAttributeFunction($stubTemplate, $function, $attributeName);
	}

	/**
	 * Replaces the stub template with the data
	 * @param  string $stubTemplate 
	 * @param  string $function      
	 * @param  string $attributeName 
	 * @return string                
	 */
	protected function createAttributeFunction($stubTemplate, $function, $attributeName) {
		return str_replace([
				"{{ attribute }}",
				"{{ function }}"
			], [
				$attributeName,
				$function
			],  $stubTemplate); 
	}

	/**
	 * Converts the given string to function. Support database names (underscores)
	 * @param  string $prefixFunction desired function prefix (get/set)
	 * @param  string $str            attribute name
	 * @param  array  $noStrip        
	 * @return string
	 */
	public function attributeNameToFunction($prefixFunction, $str, array $noStrip = array())
    {
        // non-alpha and non-numeric characters become spaces
        $str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', $str);
        $str = trim($str);
        // uppercase the first character of each word
        $str = ucwords($str);
        $str = str_replace(" ", "", $str);
        $str = ucfirst($str);

        return $prefixFunction.$str;
    }
}