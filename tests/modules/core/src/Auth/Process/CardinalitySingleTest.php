<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\core\Auth\Process\CardinalitySingle;
use SimpleSAML\Utils;

/**
 * Test for the core:CardinalitySingle filter.
 *
 * @covers \SimpleSAML\Module\core\Auth\Process\CardinalitySingle
 */
class CardinalitySingleTest extends TestCase
{
    /** @var \SimpleSAML\Utils\HTTP|\PHPUnit\Framework\MockObject\MockObject */
    private object $httpUtils;


    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param  array $config The filter configuration.
     * @param  array $request The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request): array
    {
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        /** @var \SimpleSAML\Utils\HTTP $httpUtils */
        $httpUtils = $this->httpUtils;

        $filter = new CardinalitySingle($config, null, $httpUtils);
        $filter->process($request);
        return $request;
    }


    /**
     */
    protected function setUp(): void
    {
        Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
        $this->httpUtils = $this->createStub(Utils\HTTP::class);
    }


    /**
     * Test singleValued
     */
    public function testSingleValuedUnchanged(): void
    {
        $config = [
            'singleValued' => ['eduPersonPrincipalName'],
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
     */
    public function testFirstValue(): void
    {
        $config = [
            'firstValue' => ['eduPersonPrincipalName'],
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
     */
    public function testFirstValueUnchanged(): void
    {
        $config = [
            'firstValue' => ['eduPersonPrincipalName'],
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
     */
    public function testFlatten(): void
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
     */
    public function testFlattenUnchanged(): void
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
     */
    #[DoesNotPerformAssertions]
    public function testAbort(): void
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
        $this->httpUtils->expects($this->once())
            ->method('redirectTrustedURL');

        $this->processFilter($config, $request);
    }
}
