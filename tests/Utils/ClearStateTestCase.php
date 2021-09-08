<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;

/**
 * A base SSP test case that takes care of removing global state prior to test runs
 */
class ClearStateTestCase extends TestCase
{
    /**
     * Used for managing and clearing state
     * @var \SimpleSAML\Test\Utils\StateClearer|null
     */
    protected static ?StateClearer $stateClearer = null;


    /**
     */
    public static function setUpBeforeClass(): void
    {
        if (self::$stateClearer === null) {
            self::$stateClearer = new StateClearer();
            self::$stateClearer->backupGlobals();
        }
    }


    /**
     */
    protected function setUp(): void
    {
        self::clearState();
    }


    /**
     */
    public static function tearDownAfterClass(): void
    {
        self::clearState();
    }


    /**
     * Clear any SSP global state to reduce spill over between tests.
     */
    public static function clearState(): void
    {
        self::$stateClearer->clearGlobals();
        self::$stateClearer->clearSSPState();
    }
}
