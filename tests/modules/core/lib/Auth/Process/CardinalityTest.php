<?php
// Alias the PHPUnit 6.0 ancestor if available, else fall back to legacy ancestor
if (class_exists('\PHPUnit\Framework\TestCase', true) and !class_exists('\PHPUnit_Framework_TestCase', true)) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase', true);
}

/**
 * Test for the core:Cardinality filter.
 */
class Test_Core_Auth_Process_CardinalityTest extends \PHPUnit_Framework_TestCase
{
    private $http;

    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param  array $config The filter configuration.
     * @param  array $request The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request)
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $filter = new sspmod_core_Auth_Process_Cardinality($config, null, $this->http);
        $filter->process($request);
        return $request;
    }

    protected function setUp()
    {
        \SimpleSAML_Configuration::loadFromArray(array(), '[ARRAY]', 'simplesaml');
        $this->http = $this->getMockBuilder('SimpleSAML\Utils\HTTPAdapter')
                           ->setMethods(array('redirectTrustedURL'))
                           ->getMock();
    }

    /*
     * Test where a minimum is set but no maximum
     */
    public function testMinNoMax()
    {
        $config = array(
            'mail' => array('min' => 1),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('mail' => array('joe@example.com', 'bob@example.com'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /*
     * Test where a maximum is set but no minimum
     */
    public function testMaxNoMin()
    {
        $config = array(
            'mail' => array('max' => 2),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('mail' => array('joe@example.com', 'bob@example.com'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /*
     * Test in bounds within a maximum an minimum
     */
    public function testMaxMin()
    {
        $config = array(
            'mail' => array('min' => 1, 'max' => 2),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = array('mail' => array('joe@example.com', 'bob@example.com'));
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /**
     * Test maximum is out of bounds results in redirect
     */
    public function testMaxOutOfBounds()
    {
        $config = array(
            'mail' => array('max' => 2),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com', 'fred@example.com'),
            ),
        );

        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }

    /**
     * Test minimum is out of bounds results in redirect
     */
    public function testMinOutOfBounds()
    {
        $config = array(
            'mail' => array('min' => 3),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );

        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }

    /**
     * Test missing attribute results in redirect
     */
    public function testMissingAttribute()
    {
        $config = array(
            'mail' => array('min' => 1),
        );
        $request = array(
            'Attributes' => array( ),
        );

        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }

    /*
     * Configuration errors
     */

    /**
     * Test invalid minimum values
     * @expectedException SimpleSAML_Error_Exception
     * @expectedExceptionMessageRegExp /Minimum/
     */
    public function testMinInvalid()
    {
        $config = array(
            'mail' => array('min' => false),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $this->processFilter($config, $request);
    }

    /**
     * Test invalid minimum values
     * @expectedException SimpleSAML_Error_Exception
     * @expectedExceptionMessageRegExp /Minimum/
     */
    public function testMinNegative()
    {
        $config = array(
            'mail' => array('min' => -1),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $this->processFilter($config, $request);
    }

    /**
     * Test invalid maximum values
     * @expectedException SimpleSAML_Error_Exception
     * @expectedExceptionMessageRegExp /Maximum/
     */
    public function testMaxInvalid()
    {
        $config = array(
            'mail' => array('max' => false),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $this->processFilter($config, $request);
    }

    /**
     * Test maximum < minimum
     * @expectedException SimpleSAML_Error_Exception
     * @expectedExceptionMessageRegExp /less than/
     */
    public function testMinGreaterThanMax()
    {
        $config = array(
            'mail' => array('min' => 2, 'max' => 1),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        $this->processFilter($config, $request);
    }

    /**
     * Test invalid attribute name
     * @expectedException SimpleSAML_Error_Exception
     * @expectedExceptionMessageRegExp /Invalid attribute/
     */
    public function testInvalidAttributeName()
    {
        $config = array(
            array('min' => 2, 'max' => 1),
        );
        $request = array(
            'Attributes' => array(
                'mail' => array('joe@example.com', 'bob@example.com'),
            ),
        );
        self::processFilter($config, $request);
    }
}
