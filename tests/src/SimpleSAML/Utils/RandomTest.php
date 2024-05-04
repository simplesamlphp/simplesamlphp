<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Utils;

/**
 * Tests for SimpleSAML\Utils\Random.
 */
#[CoversClass(Utils\Random::class)]
class RandomTest extends TestCase
{
    /**
     * Test for SimpleSAML\Utils\Random::generateID().
     */
    public function testGenerateID(): void
    {
        $randomUtils = new Utils\Random();

        // check that it always starts with an underscore
        $this->assertStringStartsWith('_', $randomUtils->generateID());

        // check the length
        $this->assertEquals($randomUtils::ID_LENGTH, strlen($randomUtils->generateID()));
    }
}
