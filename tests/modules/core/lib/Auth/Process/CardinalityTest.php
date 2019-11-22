<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use SimpleSAML\Error\Exception as SspException;
use SimpleSAML\Utils\HttpAdapter;

/**
 * Test for the core:Cardinality filter.
 */
class CardinalityTest extends \PHPUnit\Framework\TestCase
{
    /** @var \SimpleSAML\Utils\HttpAdapter|\PHPUnit_Framework_MockObject_MockObject */
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

        /** @var \SimpleSAML\Utils\HttpAdapter $http */
        $http = $this->http;

        $filter = new \SimpleSAML\Module\core\Auth\Process\Cardinality($config, null, $http);
        $filter->process($request);
        return $request;
    }


    /**
     * @return void
     */
    protected function setUp()
    {
        \SimpleSAML\Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
        $this->http = $this->getMockBuilder(HttpAdapter::class)
                           ->setMethods(['redirectTrustedURL'])
                           ->getMock();
    }


    /**
     * Test where a minimum is set but no maximum
     * @return void
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


    /**
     * Test where a maximum is set but no minimum
     * @return void
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


    /**
     * Test in bounds within a maximum an minimum
     * @return void
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
     * @return void
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

        /** @psalm-suppress UndefinedMethod   It's a mock-object */
        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }


    /**
     * Test minimum is out of bounds results in redirect
     * @return void
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

        /** @psalm-suppress UndefinedMethod   It's a mock-object */
        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }


    /**
     * Test missing attribute results in redirect
     * @return void
     */
    public function testMissingAttribute()
    {
        $config = [
            'mail' => ['min' => 1],
        ];
        $request = [
            'Attributes' => [],
        ];

        /** @psalm-suppress UndefinedMethod   It's a mock-object */
        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }


    /*
     * Configuration errors
     */


    /**
     * Test invalid minimum values
     * @return void
     */
    public function testMinInvalid()
    {
        $this->expectException(SspException::class);
        $this->expectExceptionMessageRegExp('/Minimum/');
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
     * @return void
     */
    public function testMinNegative()
    {
        $this->expectException(SspException::class);
        $this->expectExceptionMessageRegExp('/Minimum/');
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
     * @return void
     */
    public function testMaxInvalid()
    {
        $this->expectException(SspException::class);
        $this->expectExceptionMessageRegExp('/Maximum/');
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
     * @return void
     */
    public function testMinGreaterThanMax()
    {
        $this->expectException(SspException::class);
        $this->expectExceptionMessageRegExp('/less than/');
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
     * @return void
     */
    public function testInvalidAttributeName()
    {
        $this->expectException(SspException::class);
        $this->expectExceptionMessageRegExp('/Invalid attribute/');
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
