<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;

class AutoloadModulesTest extends TestCase
{
    /**
     * Set up for each test.
     */
    protected function setUp()
    {
        parent::setUp();
        $config = Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function autoloaderDoesNotRecurseInfinitely()
    {
        $this->assertFalse(class_exists('NonExisting\\ClassThatHasNothing\\ToDoWithXMLSec\\Library', true));
    }

    /**
     * @test
     */
    public function autoloaderSubstitutesNamespacedXmlSecClassesWhereNonNamespacedClassWasUsed()
    {
        $this->assertTrue(class_exists('XMLSecEnc', true));
    }
}
