<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\SAML2\XML\samlp\NameIDPolicy;
use SimpleSAML\Utils\Config\Metadata;
use TypeError;

/**
 * Tests related to SAML metadata.
 */
#[CoversClass(Metadata::class)]
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
     */
    public function testParseNameIdPolicy(): void
    {
        $this->assertNull(Metadata::parseNameIdPolicy([]));
        $this->assertInstanceOf(NameIDPolicy::class, Metadata::parseNameIdPolicy());

        $nameIdPolicy = [
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => false,
            'SPNameQualifier' => 'TEST',
        ];

        $this->assertInstanceOf(NameIDPolicy::class, Metadata::parseNameIdPolicy($nameIdPolicy));
        $this->assertEquals([
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => false,
            'SPNameQualifier' => 'TEST',
        ], Metadata::parseNameIdPolicy($nameIdPolicy));
    }


    /**
     * Test \SimpleSAML\Utils\Config\Metadata::parseNameIdPolicy().
     * Test with settings that produce the fallback defaults.
     */
    public function testParseNameIdPolicyDefaults(): void
    {
        // Test null or unset
        $nameIdPolicy = null;
        $this->assertEquals([
            'Format' => Constants::NAMEID_TRANSIENT,
            'AllowCreate' => false,
        ], Metadata::parseNameIdPolicy($nameIdPolicy));

        $nameIdPolicy = [
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
        ];
        $this->assertEquals([
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => true,
        ], Metadata::parseNameIdPolicy($nameIdPolicy));

        $nameIdPolicy = [
            'AllowCreate' => false,
        ];
        $this->assertEquals([
            'Format' => Constants::NAMEID_TRANSIENT,
            'AllowCreate' => false,
        ], Metadata::parseNameIdPolicy($nameIdPolicy));
    }


    /**
     * Test \SimpleSAML\Utils\Config\Metadata::parseNameIdPolicy().
     * Test with setting to empty array (meaning to not send any NameIdPolicy).
     */
    public function testParseNameIdPolicyEmpty(): void
    {
        $nameIdPolicy = [];
        $this->assertEquals(
            [],
            Metadata::parseNameIdPolicy($nameIdPolicy),
        );
    }
}
