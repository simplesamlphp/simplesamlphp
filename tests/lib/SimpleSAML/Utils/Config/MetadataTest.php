<?php

namespace SimpleSAML\Test\Utils\Config;

use SimpleSAML\Utils\Config\Metadata;

/**
 * Tests related to SAML metadata.
 */
class MetadataTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test contact configuration parsing and sanitizing.
     */
    public function testGetContact()
    {
        // test invalid argument
        try {
            Metadata::getContact('string');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid input parameters', $e->getMessage());
        }

        // test missing type
        $contact = array(
            'name' => 'John Doe'
        );
        try {
            Metadata::getContact($contact);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('"contactType" is mandatory and must be one of ', $e->getMessage());
        }

        // test invalid type
        $contact = array(
            'contactType' => 'invalid'
        );
        try {
            Metadata::getContact($contact);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringStartsWith('"contactType" is mandatory and must be one of ', $e->getMessage());
        }

        // test all valid contact types
        foreach (Metadata::$VALID_CONTACT_TYPES as $type) {
            $contact = array(
                'contactType' => $type
            );
            $parsed = Metadata::getContact($contact);
            $this->assertArrayHasKey('contactType', $parsed);
            $this->assertArrayNotHasKey('givenName', $parsed);
            $this->assertArrayNotHasKey('surName', $parsed);
        }

        // test basic name parsing
        $contact = array(
            'contactType' => 'technical',
            'name'        => 'John Doe'
        );
        $parsed = Metadata::getContact($contact);
        $this->assertArrayNotHasKey('name', $parsed);
        $this->assertArrayHasKey('givenName', $parsed);
        $this->assertArrayHasKey('surName', $parsed);
        $this->assertEquals('John', $parsed['givenName']);
        $this->assertEquals('Doe', $parsed['surName']);

        // test comma-separated names
        $contact = array(
            'contactType' => 'technical',
            'name'        => 'Doe, John'
        );
        $parsed = Metadata::getContact($contact);
        $this->assertArrayHasKey('givenName', $parsed);
        $this->assertArrayHasKey('surName', $parsed);
        $this->assertEquals('John', $parsed['givenName']);
        $this->assertEquals('Doe', $parsed['surName']);

        // test long names
        $contact = array(
            'contactType' => 'technical',
            'name'        => 'John Fitzgerald Doe Smith'
        );
        $parsed = Metadata::getContact($contact);
        $this->assertArrayNotHasKey('name', $parsed);
        $this->assertArrayHasKey('givenName', $parsed);
        $this->assertArrayNotHasKey('surName', $parsed);
        $this->assertEquals('John Fitzgerald Doe Smith', $parsed['givenName']);

        // test comma-separated long names
        $contact = array(
            'contactType' => 'technical',
            'name'        => 'Doe Smith, John Fitzgerald'
        );
        $parsed = Metadata::getContact($contact);
        $this->assertArrayNotHasKey('name', $parsed);
        $this->assertArrayHasKey('givenName', $parsed);
        $this->assertArrayHasKey('surName', $parsed);
        $this->assertEquals('John Fitzgerald', $parsed['givenName']);
        $this->assertEquals('Doe Smith', $parsed['surName']);

        // test givenName
        $contact = array(
            'contactType' => 'technical',
        );
        $invalid_types = array(0, array(0), 0.1, true, false);
        foreach ($invalid_types as $type) {
            $contact['givenName'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals('"givenName" must be a string and cannot be empty.', $e->getMessage());
            }
        }

        // test surName
        $contact = array(
            'contactType' => 'technical',
        );
        $invalid_types = array(0, array(0), 0.1, true, false);
        foreach ($invalid_types as $type) {
            $contact['surName'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals('"surName" must be a string and cannot be empty.', $e->getMessage());
            }
        }

        // test company
        $contact = array(
            'contactType' => 'technical',
        );
        $invalid_types = array(0, array(0), 0.1, true, false);
        foreach ($invalid_types as $type) {
            $contact['company'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals('"company" must be a string and cannot be empty.', $e->getMessage());
            }
        }

        // test emailAddress
        $contact = array(
            'contactType' => 'technical',
        );
        $invalid_types = array(0, 0.1, true, false, array());
        foreach ($invalid_types as $type) {
            $contact['emailAddress'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals(
                    '"emailAddress" must be a string or an array and cannot be empty.',
                    $e->getMessage()
                );
            }
        }
        $invalid_types = array(array("string", true), array("string", 0));
        foreach ($invalid_types as $type) {
            $contact['emailAddress'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals(
                    'Email addresses must be a string and cannot be empty.',
                    $e->getMessage()
                );
            }
        }
        $valid_types = array('email@example.com', array('email1@example.com', 'email2@example.com'));
        foreach ($valid_types as $type) {
            $contact['emailAddress'] = $type;
            $parsed = Metadata::getContact($contact);
            $this->assertEquals($type, $parsed['emailAddress']);
        }

        // test telephoneNumber
        $contact = array(
            'contactType' => 'technical',
        );
        $invalid_types = array(0, 0.1, true, false, array());
        foreach ($invalid_types as $type) {
            $contact['telephoneNumber'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals(
                    '"telephoneNumber" must be a string or an array and cannot be empty.',
                    $e->getMessage()
                );
            }
        }
        $invalid_types = array(array("string", true), array("string", 0));
        foreach ($invalid_types as $type) {
            $contact['telephoneNumber'] = $type;
            try {
                Metadata::getContact($contact);
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals('Telephone numbers must be a string and cannot be empty.', $e->getMessage());
            }
        }
        $valid_types = array('1234', array('1234', '5678'));
        foreach ($valid_types as $type) {
            $contact['telephoneNumber'] = $type;
            $parsed = Metadata::getContact($contact);
            $this->assertEquals($type, $parsed['telephoneNumber']);
        }

        // test completeness
        $contact = array();
        foreach (Metadata::$VALID_CONTACT_OPTIONS as $option) {
            $contact[$option] = 'string';
        }
        $contact['contactType'] = 'technical';
        $contact['name'] = 'to_be_removed';
        $contact['attributes'] = array('test' => 'testval');
        $parsed = Metadata::getContact($contact);
        foreach (array_keys($parsed) as $key) {
            $this->assertEquals($parsed[$key], $contact[$key]);
        }
        $this->assertArrayNotHasKey('name', $parsed);
    }


    /**
     * Test \SimpleSAML\Utils\Config\Metadata::isHiddenFromDiscovery().
     */
    public function testIsHiddenFromDiscovery()
    {
        // test for success
        $metadata = array(
            'EntityAttributes' => array(
                Metadata::$ENTITY_CATEGORY => array(
                    Metadata::$HIDE_FROM_DISCOVERY,
                ),
            ),
        );
        $this->assertTrue(Metadata::isHiddenFromDiscovery($metadata));

        // test for failures
        $this->assertFalse(Metadata::isHiddenFromDiscovery(array('foo')));
        $this->assertFalse(Metadata::isHiddenFromDiscovery(array(
            'EntityAttributes' => 'bar',
        )));
        $this->assertFalse(Metadata::isHiddenFromDiscovery(array(
            'EntityAttributes' => array(),
        )));
        $this->assertFalse(Metadata::isHiddenFromDiscovery(array(
            'EntityAttributes' => array(
                Metadata::$ENTITY_CATEGORY => '',
            ),
        )));
        $this->assertFalse(Metadata::isHiddenFromDiscovery(array(
            'EntityAttributes' => array(
                Metadata::$ENTITY_CATEGORY => array(),
            ),
        )));
    }
}
