<?php

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Utils\Config;

/**
 * Tests for SimpleSAML\Utils\Config
 */
class ConfigTest extends TestCase
{

    /**
     * Test default config dir with not environment variable
     */
    public function testDefaultConfigDir()
    {
        // clear env var
        putenv('SIMPLESAMLPHP_CONFIG_DIR');
        $configDir = Config::getConfigDir();

        $this->assertEquals($configDir, dirname(dirname(dirname(dirname(__DIR__)))).'/config');
    }


    /**
     * Test valid dir specified by env var overrides default config dir
     */
    public function testEnvVariableConfigDir()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR='.__DIR__);
        $configDir = Config::getConfigDir();

        $this->assertEquals($configDir, __DIR__);
    }

    /**
     * Test valid dir specified by env redirect var overrides default config dir
     */
    public function testEnvRedirectVariableConfigDir()
    {
        putenv('REDIRECT_SIMPLESAMLPHP_CONFIG_DIR='.__DIR__);
        $configDir = Config::getConfigDir();

        $this->assertEquals($configDir, __DIR__);
    }

    /**
     * Test which directory takes precedence
     */
    public function testEnvRedirectPriorityVariableConfigDir()
    {
        putenv('SIMPLESAMLPHP_CONFIG_DIR='.dirname(__DIR__));
        putenv('REDIRECT_SIMPLESAMLPHP_CONFIG_DIR='.__DIR__);
        $configDir = Config::getConfigDir();

        $this->assertEquals($configDir, dirname(__DIR__));
    }


    /**
     * Test invalid dir specified by env var results in a thrown exception
     */
    public function testInvalidEnvVariableConfigDirThrowsException()
    {
        // I used a random hash to ensure this test directory is always invalid
        $invalidDir = __DIR__.'/e9826ad19cbc4f5bf20c0913ffcd2ce6';
        putenv('SIMPLESAMLPHP_CONFIG_DIR='.$invalidDir);

        $this->setExpectedException(
            'InvalidArgumentException',
            'Config directory specified by environment variable SIMPLESAMLPHP_CONFIG_DIR is not a directory.  '.
            'Given: "'.$invalidDir.'"'
        );

        Config::getConfigDir();
    }
}
