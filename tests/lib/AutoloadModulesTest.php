<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;

class AutoloadModulesTest extends TestCase
{
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
     * @runInSeparateProcess
     */
    public function autoloaderSubstitutesNamespacedXmlSecClassesWhereNonNamespacedClassWasUsed()
    {
        $this->assertTrue(class_exists('XMLSecEnc', true));
    }
}
