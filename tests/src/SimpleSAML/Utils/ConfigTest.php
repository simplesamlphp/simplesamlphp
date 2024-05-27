<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Utils;

/**
 * Tests for SimpleSAML\Utils\Config
 */
#[CoversClass(Utils\Config::class)]
class ConfigTest extends TestCase
{
    /** @var \SimpleSAML\Utils\Config */
    protected $configUtils;


    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->configUtils = new Utils\Config();
    }


    /**
     * Test default config dir with not environment variable
     */
    public function testDefaultConfigDir(): void
    {
        // clear env var
        putenv('SIMPLESAMLPHP_CONFIG_DIR');
        $configDir = $this->configUtils->getConfigDir();

        $this->assertEquals($configDir, dirname(__DIR__, 4) . '/config');
    }


    /**
     * Test valid dir specified by env var overrides default config dir
     */
    public function testEnvVariableConfigDir(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__);
        $configDir = $this->configUtils->getConfigDir();

        $this->assertEquals($configDir, __DIR__);
    }

    /**
     * Test valid dir specified by env redirect var overrides default config dir
     */
    public function testEnvRedirectVariableConfigDir(): void
    {
        putenv('REDIRECT_SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__);
        $configDir = $this->configUtils->getConfigDir();

        $this->assertEquals($configDir, __DIR__);
    }


    /**
     * Test which directory takes precedence
     */
    public function testEnvRedirectPriorityVariableConfigDir(): void
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . dirname(__DIR__));
        putenv('REDIRECT_SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__);
        $configDir = $this->configUtils->getConfigDir();

        $this->assertEquals($configDir, dirname(__DIR__));
    }


    /**
     * Test invalid dir specified by env var results in a thrown exception
     */
    public function testInvalidEnvVariableConfigDirThrowsException(): void
    {
        // I used a random hash to ensure this test directory is always invalid
        $invalidDir = __DIR__ . '/e9826ad19cbc4f5bf20c0913ffcd2ce6';
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . $invalidDir);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Config directory specified by environment variable SIMPLESAMLPHP_CONFIG_DIR is not a directory.  ' .
            'Given: "' . $invalidDir . '"',
        );

        $this->configUtils->getConfigDir();
    }
}
