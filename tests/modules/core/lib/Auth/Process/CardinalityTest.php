<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

// Alias the PHPUnit 6.0 ancestor if available, else fall back to legacy ancestor
if (class_exists('\PHPUnit\Framework\TestCase', true) and !class_exists('\PHPUnit_Framework_TestCase', true)) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase', true);
}

/**
 * Test for the core:Cardinality filter.
 */
class CardinalityTest extends \PHPUnit_Framework_TestCase
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
        $filter = new \SimpleSAML\Module\core\Auth\Process\Cardinality($config, null, $this->http);
        $filter->process($request);
        return $request;
    }

    protected function setUp()
    {
        \SimpleSAML\Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
        $this->http = $this->getMockBuilder('SimpleSAML\Utils\HTTPAdapter')
                           ->setMethods(['redirectTrustedURL'])
                           ->getMock();
    }

    /*
     * Test where a minimum is set but no maximum
     */
    public function testMinNoMax()
    {
        $config = [
            'mail' => ['min' => 1],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['mail' => ['joe@example.com', 'bob@example.com']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /*
     * Test where a maximum is set but no minimum
     */
    public function testMaxNoMin()
    {
        $config = [
            'mail' => ['max' => 2],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['mail' => ['joe@example.com', 'bob@example.com']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /*
     * Test in bounds within a maximum an minimum
     */
    public function testMaxMin()
    {
        $config = [
            'mail' => ['min' => 1, 'max' => 2],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['mail' => ['joe@example.com', 'bob@example.com']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }

    /**
     * Test maximum is out of bounds results in redirect
     */
    public function testMaxOutOfBounds()
    {
        $config = [
            'mail' => ['max' => 2],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com', 'fred@example.com'],
            ],
        ];

        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }

    /**
     * Test minimum is out of bounds results in redirect
     */
    public function testMinOutOfBounds()
    {
        $config = [
            'mail' => ['min' => 3],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];

        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }

    /**
     * Test missing attribute results in redirect
     */
    public function testMissingAttribute()
    {
        $config = [
            'mail' => ['min' => 1],
        ];
        $request = [
            'Attributes' => [],
        ];

        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }

    /*
     * Configuration errors
     */

    /**
     * Test invalid minimum values
     * @expectedException \SimpleSAML\Error\Exception
     * @expectedExceptionMessageRegExp /Minimum/
     */
    public function testMinInvalid()
    {
        $config = [
            'mail' => ['min' => false],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $this->processFilter($config, $request);
    }

    /**
     * Test invalid minimum values
     * @expectedException \SimpleSAML\Error\Exception
     * @expectedExceptionMessageRegExp /Minimum/
     */
    public function testMinNegative()
    {
        $config = [
            'mail' => ['min' => -1],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $this->processFilter($config, $request);
    }

    /**
     * Test invalid maximum values
     * @expectedException \SimpleSAML\Error\Exception
     * @expectedExceptionMessageRegExp /Maximum/
     */
    public function testMaxInvalid()
    {
        $config = [
            'mail' => ['max' => false],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $this->processFilter($config, $request);
    }

    /**
     * Test maximum < minimum
     * @expectedException \SimpleSAML\Error\Exception
     * @expectedExceptionMessageRegExp /less than/
     */
    public function testMinGreaterThanMax()
    {
        $config = [
            'mail' => ['min' => 2, 'max' => 1],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $this->processFilter($config, $request);
    }

    /**
     * Test invalid attribute name
     * @expectedException \SimpleSAML\Error\Exception
     * @expectedExceptionMessageRegExp /Invalid attribute/
     */
    public function testInvalidAttributeName()
    {
        $config = [
            ['min' => 2, 'max' => 1],
        ];
        $request = [
            'Attributes' => [
                'mail' => ['joe@example.com', 'bob@example.com'],
            ],
        ];
        $this->processFilter($config, $request);
    }
}
