<?php

namespace SimpleSAML\Test;

use PHPUnit\Framework\TestCase;

class AutoloadModulesTest extends TestCase
{
    /**
     * @test
     */
    public function autoloaderDoesNotRecurseInfinitely()
    {
        $this->assertFalse(class_exists('NonExisting\\ClassThatHasNothing\\ToDoWithXMLSec\\Library', true));
    }
}
