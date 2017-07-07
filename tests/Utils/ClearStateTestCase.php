<?php

namespace SimpleSAML\Test\Utils;

include(dirname(__FILE__) . '/StateClearer.php');

/**
 * A base SSP test case that takes care of removing global state prior to test runs
 */
class ClearStateTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Used for managing and clearing state
     * @var StateClearer
     */
    protected static $stateClearer;

    public static function setUpBeforeClass()
    {
        if (!self::$stateClearer) {
            self::$stateClearer = new StateClearer();
            self::$stateClearer->backupGlobals();
        }
    }


    protected function setUp()
    {
        self::clearState();
    }

    public static function tearDownAfterClass()
    {
        self::clearState();
    }


    /**
     * Clear any SSP global state to reduce spill over between tests.
     */
    public static function clearState()
    {
        self::$stateClearer->clearGlobals();
        self::$stateClearer->clearSSPState();
    }
}
