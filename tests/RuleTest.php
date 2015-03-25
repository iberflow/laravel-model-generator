<?php

class RuleTest extends PHPUnit_Framework_TestCase
{
    public function testStartMethod()
    {
        $processor = new Iber\Generator\Utilities\RuleProcessor();
        $this->assertTrue($processor->starts(['user'], 'user_id'));
    }

    public function testEndMethod()
    {
        $processor = new Iber\Generator\Utilities\RuleProcessor();
        $this->assertTrue($processor->ends(['_at'], 'created_at'));
        $this->assertTrue($processor->ends(['_at'], 'updated_at'));
    }

    public function testEqualsMethod()
    {
        $processor = new Iber\Generator\Utilities\RuleProcessor();
        $this->assertTrue($processor->equals(['id'], 'id'));
    }
}
