<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils\Config;

use PHPUnit\Framework\TestCase;
use SimpleSAML\SAML2\XML\samlp\NameIDPolicy;
use SimpleSAML\Utils\Config\Metadata;
use TypeError;

/**
 * Tests related to SAML metadata.
 *
 * @covers \SimpleSAML\Utils\Config\Metadata
 */
class MetadataTest extends TestCase
{
    /**
     * Test \SimpleSAML\Utils\Config\Metadata::isHiddenFromDiscovery().
     */
    public function testIsHiddenFromDiscovery(): void
    {
        // test for success
        $metadata = [
            'EntityAttributes' => [
                Metadata::$ENTITY_CATEGORY => [
                    Metadata::$HIDE_FROM_DISCOVERY,
                ],
            ],
        ];
        $this->assertTrue(Metadata::isHiddenFromDiscovery($metadata));

        // test for failure
        $this->assertFalse(Metadata::isHiddenFromDiscovery([
            'EntityAttributes' => [
                Metadata::$ENTITY_CATEGORY => [],
            ],
        ]));

        // test for failures
        $this->expectException(TypeError::class);
        Metadata::isHiddenFromDiscovery(['foo']);

        $this->assertFalse(Metadata::isHiddenFromDiscovery([
            'EntityAttributes' => 'bar',
        ]));
        $this->assertFalse(Metadata::isHiddenFromDiscovery([
            'EntityAttributes' => [],
        ]));
        $this->assertFalse(Metadata::isHiddenFromDiscovery([
            'EntityAttributes' => [
                Metadata::$ENTITY_CATEGORY => '',
            ],
        ]));
    }


    /**
     * @covers \SimpleSAML\Utils\Config\Metadata::parseNameIdPolicy
     */
    public function testParseNameIdPolicy(): void
    {
        $this->assertNull(Metadata::parseNameIdPolicy([]));
        $this->assertInstanceOf(NameIDPolicy::class, Metadata::parseNameIdPolicy());

        $nameIdPolicy = [
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => false,
            'SPNameQualifier' => 'TEST'
        ];
        $this->assertInstanceOf(NameIDPolicy::class, Metadata::parseNameIdPolicy($nameIdPolicy));
    }
}
