<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;

class AutoloadModulesTest extends TestCase
{
    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $config = Configuration::loadFromArray([], '[ARRAY]', 'simplesaml');
    }

    /**
     * @runInSeparateProcess
     * @return void
     */
    public function autoloaderDoesNotRecurseInfinitely()
    {
        $this->assertFalse(class_exists('NonExisting\\ClassThatHasNothing\\ToDoWithXMLSec\\Library', true));
    }

    /**
     * @return void
     */
    public function autoloaderSubstitutesNamespacedXmlSecClassesWhereNonNamespacedClassWasUsed()
    {
        $this->assertTrue(class_exists('XMLSecEnc', true));
    }
}
