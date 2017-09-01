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
     */
    public function testSetState()
    {
        $_SERVER['QUERY_STRING'] = 'a=b';
        \SimpleSAML_Configuration::loadFromArray(array('a' => 'b'), '[ARRAY]', 'simplesaml');
        $this->assertEquals('b', \SimpleSAML_Configuration::getInstance()->getString('a'));
        putenv('SIMPLESAMLPHP_CONFIG_DIR=' . __DIR__);
    }

    /**
     * Confirm global state removed prior to next test
     */
    public function testStateRemoved()
    {

        $this->assertArrayNotHasKey('QUERY_STRING', $_SERVER);
        $this->assertFalse(getenv('SIMPLESAMLPHP_CONFIG_DIR'));
        try {
            \SimpleSAML_Configuration::getInstance();
            $this->fail('Expected config configured in other tests to no longer be valid');
        } catch (\SimpleSAML\Error\ConfigurationError $error) {
            // Expected error
        }
    }
}
