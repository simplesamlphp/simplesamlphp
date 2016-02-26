<?php

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Utils\Random;

/**
 * Tests for SimpleSAML\Utils\Random.
 */
class RandomTest extends \PHPUnit_Framework_TestCase
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
