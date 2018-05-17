<?php
// Alias the PHPUnit 6.0 ancestor if available, else fall back to legacy ancestor
if (class_exists('\PHPUnit\Framework\TestCase', true) and !class_exists('\PHPUnit_Framework_TestCase', true)) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase', true);
}

/**
 * Test for the core:CardinalitySingle filter.
 */
class Test_Core_Auth_Process_CardinalitySingleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param  array $config The filter configuration.
     * @param  array $request The request state.
     * @return array  The state array after processing.
     */
    private static function processFilter(array $config, array $request)
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $filter = new sspmod_core_Auth_Process_CardinalitySingle($config, null);
        $filter->process($request);
        return $request;
    }

    protected function setUp()
    {
        \SimpleSAML_Configuration::loadFromArray(array(), '[ARRAY]', 'simplesaml');
    }

    /**
     * Test singleValued
     */
    public function testSingleValuedUnchanged()
    {
        $config = array(
            'singleValued' => array('eduPersonPrincipalName')
        );
        $request = array(
            'Attributes' => array(
                'eduPersonPrincipalName' => array('joe@example.com'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('eduPersonPrincipalName' => array('joe@example.com'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /**
     * Test first value extraction
     */
    public function testFirstValue()
    {
        $config = array(
            'firstValue' => array('eduPersonPrincipalName')
        );
        $request = array(
            'Attributes' => array(
                'eduPersonPrincipalName' => array('joe@example.com', 'bob@example.net'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('eduPersonPrincipalName' => array('joe@example.com'));
        $this->assertEquals($expectedData, $attributes, "Only first value should be returned");
    }

    public function testFirstValueUnchanged()
    {
        $config = array(
            'firstValue' => array('eduPersonPrincipalName')
        );
        $request = array(
            'Attributes' => array(
                'eduPersonPrincipalName' => array('joe@example.com'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('eduPersonPrincipalName' => array('joe@example.com'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /**
     * Test flattening
     */
    public function testFlatten()
    {
        $config = array(
            'flatten' => array('eduPersonPrincipalName'),
            'flattenWith' => '|',
        );
        $request = array(
            'Attributes' => array(
                'eduPersonPrincipalName' => array('joe@example.com', 'bob@example.net'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('eduPersonPrincipalName' => array('joe@example.com|bob@example.net'));
        $this->assertEquals($expectedData, $attributes, "Flattened string should be returned");
    }

    public function testFlattenUnchanged()
    {
        $config = array(
            'flatten' => array('eduPersonPrincipalName'),
            'flattenWith' => '|',
        );
        $request = array(
            'Attributes' => array(
                'eduPersonPrincipalName' => array('joe@example.com'),
            ),
        );
        $result = self::processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('eduPersonPrincipalName' => array('joe@example.com'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /**
     * Test abort
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionMessageRegExp /REQUEST_URI/
     */
    public function testAbort()
    {
        $config = array(
            'singleValued' => array('eduPersonPrincipalName'),
        );
        $request = array(
            'Attributes' => array(
                'eduPersonPrincipalName' => array('joe@example.com', 'bob@example.net'),
            ),
        );
        self::processFilter($config, $request);
    }
}
