<?php

namespace SimpleSAML\Test\Utils;

/**
 * Test that ensures state doesn't spill over between tests
 * @package SimpleSAML\Test\Utils
 */
class ReduceSpillOverTest extends ClearStateTestCase
{
    /**
     * Set some global state
     * @return void
     */
    public function testSetState()
    {
        $_SERVER['QUERY_STRING'] = 'a=b';
        \SimpleSAML\Configuration::loadFromArray(['a' => 'b'], '[ARRAY]', 'simplesaml');
        $this->assertEquals('b', \SimpleSAML\Configuration::getInstance()->getString('a'));
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__);
    }


    /**
     * Confirm global state removed prior to next test
     * @return void
     * @throws \SimpleSAML\Error\ConfigurationError
     */
    public function testStateRemoved()
    {
        $this->assertArrayNotHasKey('QUERY_STRING', $_SERVER);
        /** @var false $env */
        $env = getenv('SIMPLESAMLPHP_CONFIG_DIR');
        $this->assertFalse($env);
        try {
            \SimpleSAML\Configuration::getInstance();
            $this->fail('Expected config configured in other tests to no longer be valid');
        } catch (\SimpleSAML\Error\ConfigurationError $error) {
            // Expected error
        }
    }
}
