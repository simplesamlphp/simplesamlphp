<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils\Config;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SAML2\Constants;
use SAML2\XML\md\ContactPerson;
use SimpleSAML\Utils\Config\Metadata;
use TypeError;

/**
 * Tests related to SAML metadata.
 */
#[CoversClass(Metadata::class)]
class MetadataTest extends TestCase
{
    /**
     * Test contact configuration parsing and sanitizing.
     */
    public function testGetContact(): void
    {
        // test missing type
        $contact = [
            'name' => 'John Doe',
        ];
        try {
            Metadata::getContact($contact);
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('"contactType" is mandatory and must be one of ', $e->getMessage());
        }

        // test invalid type
        $contact = [
            'contactType' => 'invalid',
        ];
        try {
            Metadata::getContact($contact);
        } catch (InvalidArgumentException $e) {
            $this->assertStringStartsWith('"contactType" is mandatory and must be one of ', $e->getMessage());
        }

        // test all valid contact types
        foreach (ContactPerson::CONTACT_TYPES as $type) {
            $contact = [
                'contactType' => $type,
            ];
            $parsed = Metadata::getContact($contact);
            $this->assertArrayHasKey('contactType', $parsed);
            $this->assertArrayNotHasKey('givenName', $parsed);
            $this->assertArrayNotHasKey('surName', $parsed);
        }

        // test givenName
        $contact = [
            'contactType' => 'technical',
        ];
        $invalid_types = [0, [0], 0.1, true, false];
        foreach ($invalid_types as $type) {
            $contact['givenName'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals('"givenName" must be a string and cannot be empty.', $e->getMessage());
            }
        }

        // test surName
        $contact = [
            'contactType' => 'technical',
        ];
        $invalid_types = [0, [0], 0.1, true, false];
        foreach ($invalid_types as $type) {
            $contact['surName'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals('"surName" must be a string and cannot be empty.', $e->getMessage());
            }
        }

        // test company
        $contact = [
            'contactType' => 'technical',
        ];
        $invalid_types = [0, [0], 0.1, true, false];
        foreach ($invalid_types as $type) {
            $contact['company'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals('"company" must be a string and cannot be empty.', $e->getMessage());
            }
        }

        // test emailAddress
        $contact = [
            'contactType' => 'technical',
        ];
        $invalid_types = [0, 0.1, true, false, []];
        foreach ($invalid_types as $type) {
            $contact['emailAddress'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    '"emailAddress" must be a string or an array and cannot be empty.',
                    $e->getMessage(),
                );
            }
        }
        $invalid_types = [["string", true], ["string", 0]];
        foreach ($invalid_types as $type) {
            $contact['emailAddress'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    'Email addresses must be a string and cannot be empty.',
                    $e->getMessage(),
                );
            }
        }
        $valid_types = ['email@example.com', ['email1@example.com', 'email2@example.com']];
        foreach ($valid_types as $type) {
            $contact['emailAddress'] = $type;
            $parsed = Metadata::getContact($contact);
            $this->assertEquals($type, $parsed['emailAddress']);
        }

        // test telephoneNumber
        $contact = [
            'contactType' => 'technical',
        ];
        $invalid_types = [0, 0.1, true, false, []];
        foreach ($invalid_types as $type) {
            $contact['telephoneNumber'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals(
                    '"telephoneNumber" must be a string or an array and cannot be empty.',
                    $e->getMessage(),
                );
            }
        }
        $invalid_types = [["string", true], ["string", 0]];
        foreach ($invalid_types as $type) {
            $contact['telephoneNumber'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (InvalidArgumentException $e) {
                $this->assertEquals('Telephone numbers must be a string and cannot be empty.', $e->getMessage());
            }
        }
        $valid_types = ['1234', ['1234', '5678']];
        foreach ($valid_types as $type) {
            $contact['telephoneNumber'] = $type;
            $parsed = Metadata::getContact($contact);
            $this->assertEquals($type, $parsed['telephoneNumber']);
        }

        // test completeness
        $contact = [];
        foreach (Metadata::$VALID_CONTACT_OPTIONS as $option) {
            $contact[$option] = 'string';
        }
        $contact['contactType'] = 'technical';
        $contact['name'] = 'to_be_removed';
        $contact['attributes'] = ['test' => 'testval'];
        $parsed = Metadata::getContact($contact);
        foreach (array_keys($parsed) as $key) {
            $this->assertEquals($parsed[$key], $contact[$key]);
        }
        $this->assertArrayNotHasKey('name', $parsed);
    }


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
     * Test \SimpleSAML\Utils\Config\Metadata::parseNameIdPolicy().
     * Set to specific arrays.
     */
    public function testParseNameIdPolicy(): void
    {
        $nameIdPolicy = [
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => false,
        ];
        $this->assertEquals([
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => false,
        ], Metadata::parseNameIdPolicy($nameIdPolicy));

        $nameIdPolicy = [
            'Format' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:persistent',
            'AllowCreate' => false,
            'SPNameQualifier' => 'TEST',
        ];
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
            'AllowCreate' => true,
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
