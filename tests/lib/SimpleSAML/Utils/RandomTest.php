<?php

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Utils\Random;

/**
 * Tests for SimpleSAML\Utils\Random.
 */
class RandomTest extends TestCase
{

    /**
     * Test for SimpleSAML\Utils\Random::generateID().
     *
     * @covers SimpleSAML\Utils\Random::generateID
     */
    public function testGenerateID()
    {
        // check that it always starts with an underscore
        $this->assertStringStartsWith('_', Random::generateID());

        // check the length
        $this->assertEquals(Random::ID_LENGTH, strlen(Random::generateID()));
    }
}
