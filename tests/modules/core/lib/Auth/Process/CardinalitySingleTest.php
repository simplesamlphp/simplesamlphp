<?php

namespace SimpleSAML\Test\Module\core\Auth\Process;

use SimpleSAML\Utils\HttpAdapter;

/**
 * Test for the core:CardinalitySingle filter.
 */
class CardinalitySingleTest extends \PHPUnit\Framework\TestCase
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

        $filter = new \SimpleSAML\Module\core\Auth\Process\CardinalitySingle($config, null, $http);
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
     * Test singleValued
     * @return void
     */
    public function testSingleValuedUnchanged()
    {
        $config = [
            'singleValued' => ['eduPersonPrincipalName']
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['eduPersonPrincipalName' => ['joe@example.com']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }


    /**
     * Test first value extraction
     * @return void
     */
    public function testFirstValue()
    {
        $config = [
            'firstValue' => ['eduPersonPrincipalName']
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe@example.com', 'bob@example.net'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['eduPersonPrincipalName' => ['joe@example.com']];
        $this->assertEquals($expectedData, $attributes, "Only first value should be returned");
    }


    /**
     * @return void
     */
    public function testFirstValueUnchanged()
    {
        $config = [
            'firstValue' => ['eduPersonPrincipalName']
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['eduPersonPrincipalName' => ['joe@example.com']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }


    /**
     * Test flattening
     * @return void
     */
    public function testFlatten()
    {
        $config = [
            'flatten' => ['eduPersonPrincipalName'],
            'flattenWith' => '|',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe@example.com', 'bob@example.net'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['eduPersonPrincipalName' => ['joe@example.com|bob@example.net']];
        $this->assertEquals($expectedData, $attributes, "Flattened string should be returned");
    }


    /**
     * @return void
     */
    public function testFlattenUnchanged()
    {
        $config = [
            'flatten' => ['eduPersonPrincipalName'],
            'flattenWith' => '|',
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe@example.com'],
            ],
        ];
        $result = $this->processFilter($config, $request);
        $attributes = $result['Attributes'];
        $expectedData = ['eduPersonPrincipalName' => ['joe@example.com']];
        $this->assertEquals($expectedData, $attributes, "Assertion values should not have changed");
    }


    /**
     * Test abort
     * @return void
     */
    public function testAbort()
    {
        $config = [
            'singleValued' => ['eduPersonPrincipalName'],
        ];
        $request = [
            'Attributes' => [
                'eduPersonPrincipalName' => ['joe@example.com', 'bob@example.net'],
            ],
        ];

        /** @psalm-suppress UndefinedMethod */
        $this->http->expects($this->once())
                   ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }
}
