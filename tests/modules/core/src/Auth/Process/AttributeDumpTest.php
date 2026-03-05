<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Auth\Process;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Module\core\Auth\Process\AttributeDump;
use SimpleSAML\TestUtils\ArrayLogger;

/**
 * Test for the core:AttributeDump filter.
 */
#[CoversClass(AttributeDump::class)]
class AttributeDumpTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected static Configuration $config;


    /**
     * Set up before running the test-suite.
     */
    #[\Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$config = Configuration::loadFromArray(
            [
                'logging.handler' => ArrayLogger::class,
                'errorreporting' => false,
                'module.enable' => ['core' => true],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        Configuration::setPreLoadedConfig(self::$config, 'config.php');
    }


    /**
     * Clean up after each test
     */
    #[\Override]
    protected function tearDown(): void
    {
        Logger::clearCapturedLog();
    }


    /**
     * Helper function to run the filter with a given configuration.
     *
     * @param array $config  The filter configuration.
     * @param array $request  The request state.
     * @return array  The state array after processing.
     */
    private function processFilter(array $config, array $request, string $expected): array
    {
        Logger::setCaptureLog();
        $filter = new AttributeDump($config, null);
        $filter->process($request);
        Logger::setCaptureLog(false);

        // Strip off the timestamp from the beginning of the log message, since those are not deterministic.
        $log = preg_replace('~^\S+\s~', '', Logger::getCapturedLog()[0]);
        $this->assertEquals($expected, $log);

        return $request;
    }


    /**
     * Test the most basic functionality.
     */
    public function testBasic(): void
    {
        $authProcConfig = [
        ];
        $request = [
            'Attributes' => [
                'test' => ['value1', 'value2'],
            ],
        ];

        $expectedLog = 'AttributeDump: ' . var_export(['test' => ['value1', 'value2']], true);

        $this->processFilter(
            $authProcConfig,
            $request,
            $expectedLog,
        );
    }


    /**
     * Test the most basic functionality.
     */
    public function testSingleAttribute(): void
    {
        $authProcConfig = [
            // ArrayLogger doesn't actually do anything with the log level,
            // but we want to test that it is accepted in the configuration.
            'logLevel' => 'debug',
            'logPrefix' => 'TestPrefix',
            'attributes' => ['test'],
        ];
        $request = [
            'Attributes' => [
                'test' => ['value1', 'value2'],
                'test_drop' => ['value3'],
            ],
        ];

        $expectedLog = 'TestPrefix: ' . var_export(['test' => ['value1', 'value2']], true);

        $this->processFilter(
            $authProcConfig,
            $request,
            $expectedLog,
        );
    }


    /**
     * Test the most basic functionality.
     */
    public function testMultipleAttributes(): void
    {
        $authProcConfig = [
            'logPrefix' => 'TestPrefix',
            'attributes' => ['test', 'test_include'],
        ];
        $request = [
            'Attributes' => [
                'test' => ['value1', 'value2'],
                'test_drop' => ['value3'],
                'test_include' => ['value4'],
            ],
        ];

        $expectedLog = 'TestPrefix: ' .
            var_export(['test' => ['value1', 'value2'], 'test_include' => ['value4']], true);

        $this->processFilter(
            $authProcConfig,
            $request,
            $expectedLog,
        );
    }


    /**
     * Test the most basic functionality.
     */
    public function testSingleRegex(): void
    {
        $authProcConfig = [
            'attributesRegex' => ['/^test_/'],
        ];
        $request = [
            'Attributes' => [
                'test' => ['value1', 'value2'],
                'test_next' => ['value3'],
                'test_include' => ['value4'],
            ],
        ];

        $expectedLog = 'AttributeDump: ' .
            var_export(['test_next' => ['value3'], 'test_include' => ['value4']], true);

        $this->processFilter(
            $authProcConfig,
            $request,
            $expectedLog,
        );
    }


    /**
     * Test the most basic functionality.
     */
    public function testMultipleRegex(): void
    {
        $authProcConfig = [
            'attributesRegex' => ['/^test_o/', '/^test_i/'],
        ];
        $request = [
            'Attributes' => [
                'test' => ['value1', 'value2'],
                'test_drop' => ['value3'],
                'test_other' => ['value5'],
                'test_include' => ['value4'],
            ],
        ];

        $expectedLog = 'AttributeDump: ' .
            var_export(['test_other' => ['value5'], 'test_include' => ['value4']], true);

        $this->processFilter(
            $authProcConfig,
            $request,
            $expectedLog,
        );
    }


    /**
     * Test the most basic functionality.
     */
    public function testAttributeAndRegex(): void
    {
        $authProcConfig = [
            'attributes' => ['test'],
            'attributesRegex' => ['/^test_i/'],
        ];
        $request = [
            'Attributes' => [
                'test' => ['value1', 'value2'],
                'test_drop' => ['value3'],
                'test_other' => ['value5'],
                'test_include' => ['value4'],
            ],
        ];

        $expectedLog = 'AttributeDump: ' .
            var_export(['test' => ['value1', 'value2'], 'test_include' => ['value4']], true);

        $this->processFilter(
            $authProcConfig,
            $request,
            $expectedLog,
        );
    }
}
