<?php

namespace Iber\Generator\Utilities;

/**
 * Class VariableGenerator
 * @package Iber\Generator\Utilities
 */
class VariableGenerator
{
    
    /**
     * Lists of attributes to convert
     * @var array
     */
    protected $attributes = [];
    
    /**
     * Returns a template stub for set function
     * @var string
     */
    protected $commentStub = "";
    
    /**
     * Returns a template stub for get function
     * @var string
     */
    protected $variableStub = "";
    
    /**
     * [__construct description]
     *
     * @param array  $attributes with attributes names
     * @param string $varStub    set stub template
     * @param string $comStub    get stub template
     */
    public function __construct(array $attributes, $varStub, $comStub)
    {
        $this->attributes   = $attributes;
        $this->variableStub = $varStub;
        $this->commentStub  = $comStub;
        
    }
    
    /**
     * Returns the get functions in string
     * @return string
     */
    public function generateVarProperties()
    {
        return $this->generateWithFunction("createVarPropertyFromAttributeName");
    }
    
    /**
     * Returns the set functions in string
     * @return string
     */
    public function generateComProperties()
    {
        return $this->generateWithFunction("createComPropertyFromAttributeName");
    }
    
    /**
     * Loops the attributes and build the string with given property name
     *
     * @param  string $function
     *
     * @return  string
     */
    protected function generateWithFunction($function)
    {
        $string = "";
        
        foreach ($this->attributes as $attribute) {
            $string .= $this->$function($attribute->name, $attribute->data_type);
        }
        
        return $string;
    }
    
    /**
     * Bulds the get function for the attribute name
     *
     * @param  string  $attributeName
     *
     * @param   string $dataType
     *
     * @return string
     */
    public function createVarPropertyFromAttributeName($attributeName, $dataType)
    {
        return $this->createPropertyFromAttributeName($attributeName, $dataType, $this->variableStub);
    }
    
    /**
     * Bulds the set function for the attribute name
     *
     * @param  string  $attributeName
     *
     * @param   string $dataType
     *
     * @return string
     */
    public function createComPropertyFromAttributeName($attributeName, $dataType)
    {
        return $this->createPropertyFromAttributeName($attributeName, $dataType, $this->commentStub);
    }
    
    /**
     * Builds the property and creates the property from the stub template
     *
     * @param  string $attributeName
     * @param  string $dataType
     * @param  string $stubTemplate
     *
     * @return string
     * @internal param string $prefixFunction
     */
    protected function createPropertyFromAttributeName($attributeName, $dataType, $stubTemplate)
    {
        return $this->createAttributeProperty($stubTemplate, $attributeName, $dataType);
    }
    
    /**
     * Replaces the stub template with the data
     *
     * @param  string $stubTemplate
     * @param  string $attributeName
     *
     * @param  string $dataType
     *
     * @return string
     * @internal param string $function
     */
    protected function createAttributeProperty($stubTemplate, $attributeName, $dataType)
    {
        /*
         * We can handle only unquoted field names
         */
        $attributeName = preg_replace('/[^0-9a-zA-Z\$_]+/i', '', $attributeName);
        return str_replace(["{{colname}}", "{{returndt}}", "{{datatype}}",], ['$' . $attributeName, $this->dataType($dataType) . '|', $dataType,], $stubTemplate);
    }
    
    /**
     * To assist in matching various data types to php data types
     *
     * @param $data_type
     *
     * @return int|string
     */
    protected function dataType($data_type)
    {
        $mapping = ['string'  => ['varchar', 'varbinary', 'binary', 'set', 'enum', 'longtext', 'longblob', 'mediumtext', 'text', 'datetime', 'timestamp', 'time', 'char', 'tinyblob', 'tinytext',
                                  'blob', 'mediumblob']
                    , 'date'  => []
                    , 'int'   => ['tinyint', 'smallint', 'mediumint', 'bigint', 'year']
                    , 'float' => ['double', 'decimal']
                    , 'bool'  => ['boolean'],
        ];
        foreach ($mapping as $dt => $alias) {
            if (in_array($data_type, $alias) || $dt == $data_type) return $dt;
        }
        return 'string';
    }
}