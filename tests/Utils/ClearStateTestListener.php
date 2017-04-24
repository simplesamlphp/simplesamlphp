<?php
namespace SimpleSAML\Test\Utils;

use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

/**
 * A PHPUnit test listener that attempts to clear global state and cached SSP configuration between test run
 */
class ClearStateTestListener extends \PHPUnit_Framework_BaseTestListener
{

    /**
     * Global state to restore between test runs
     * @var array
     */
    private static $backups = array();

    /**
     * Class that implement \SimpleSAML\Utils\ClearableState and should have clearInternalState called between tests
     * @var array
     */
    private static $clearableState = array('SimpleSAML_Configuration');

    /**
     * Variables
     * @var array
     */
    private static $vars_to_unset = array('SIMPLESAMLPHP_CONFIG_DIR');

    public function __construct()
    {
        // Backup any state that is needed as part of processing, so we can restore it later.
        // TODO: phpunit's backupGlobals = false, yet we are trying to do a similar thing here. Is that an issue?
        if (!self::$backups) {
            self::$backups['$_COOKIE'] = $_COOKIE;
            self::$backups['$_ENV'] = $_ENV;
            self::$backups['$_FILES'] = $_FILES;
            self::$backups['$_GET'] = $_GET;
            self::$backups['$_POST'] = $_POST;
            self::$backups['$_SERVER'] = $_SERVER;
            self::$backups['$_SESSION'] = isset($_SESSION) ? $_SESSION : [];
            self::$backups['$_REQUEST'] = $_REQUEST;
        }
    }

    /**
     * Clear any state needed prior to a test file running
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        // TODO: decide how to handle tests that want to set suite level settings with setUpBeforeClass()
    }

    /**
     * Clear any state needed prior to a test case
     * @param PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        $_COOKIE = self::$backups['$_COOKIE'];
        $_ENV = self::$backups['$_ENV'];
        $_FILES = self::$backups['$_FILES'];
        $_GET = self::$backups['$_GET'];
        $_POST = self::$backups['$_POST'];
        $_SERVER = self::$backups['$_SERVER'];
        $_SESSION = self::$backups['$_SESSION'];
        $_REQUEST = self::$backups['$_REQUEST'];

        foreach (self::$clearableState as $var) {
            $var::clearInternalState();
        }

        foreach (self::$vars_to_unset as $var) {
            putenv($var);
        }
    }
}
