<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Metadata;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Metadata\SAMLBuilder;
use SimpleSAML\Module\saml\Auth\Source\SP;

/**
 * Class SAMLBuilderTest
 */
#[CoversClass(SAMLBuilder::class)]
class SAMLBuilderTest extends TestCase
{
    private const SECURITY = 'vendor/simplesamlphp/xml-security/resources';

    /**
     */
    protected function setUp(): void
    {
        Configuration::loadFromArray([], '', 'simplesaml');
    }

    /**
     */
    protected function tearDown(): void
    {
        Configuration::clearInternalState();
    }

    /**
     * Test the requested attributes are valued correctly.
     */
    public function testAttributes(): void
    {
        $entityId = 'https://entity.example.com/id';

        //  test SP20 array parsing, no friendly name
        $set = 'saml20-sp-remote';
        $metadata = [
            'entityid'     => $entityId,
            'name'         => ['en' => 'Test SP'],
            'metadata-set' => $set,
            'attributes'   => [
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'urn:oid:0.9.2342.19200300.100.1.3',
                'urn:oid:2.5.4.3',
            ],
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        /** @psalm-var \DOMNodeList $acs */
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");
        $this->assertEquals(1, $acs->length);

        /** @psalm-var \DOMElement $first */
        $first = $acs->item(0);
        $attributes = $first->getElementsByTagName("RequestedAttribute");
        $this->assertEquals(4, $attributes->length);
        for ($c = 0; $c < $attributes->length; $c++) {
            /** @psalm-var \DOMElement $curAttribute */
            $curAttribute = $attributes->item($c);
            $this->assertTrue($curAttribute->hasAttribute("Name"));
            $this->assertFalse($curAttribute->hasAttribute("FriendlyName"));
            /** @psalm-suppress InvalidArrayOffset */
            $this->assertEquals($metadata['attributes'][$c], $curAttribute->getAttribute("Name"));
        }

        // test SP20 array parsing, no friendly name
        $set = 'saml20-sp-remote';
        $metadata = [
            'entityid'     => $entityId,
            'name'         => ['en' => 'Test SP'],
            'metadata-set' => $set,
            'attributes'   => [
                'eduPersonTargetedID'    => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'eduPersonPrincipalName' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'eduPersonOrgDN'         => 'urn:oid:0.9.2342.19200300.100.1.3',
                'cn'                     => 'urn:oid:2.5.4.3',
            ],
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        /** @var \DOMNodeList $acs */
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");
        $this->assertEquals(1, $acs->length);

        /** @psalm-var \DOMElement $first */
        $first = $acs->item(0);
        $attributes = $first->getElementsByTagName("RequestedAttribute");
        $this->assertEquals(4, $attributes->length);
        $keys = array_keys($metadata['attributes']);
        for ($c = 0; $c < $attributes->length; $c++) {
            /** @psalm-var \DOMElement $curAttribute */
            $curAttribute = $attributes->item($c);
            $this->assertTrue($curAttribute->hasAttribute("Name"));
            $this->assertTrue($curAttribute->hasAttribute("FriendlyName"));
            $this->assertEquals($metadata['attributes'][$keys[$c]], $curAttribute->getAttribute("Name"));
            $this->assertEquals($keys[$c], $curAttribute->getAttribute("FriendlyName"));
        }
    }


    /**
     * Test the working of the isDefault config option
     */
    public function testAttributeConsumingServiceDefault(): void
    {
        $entityId = 'https://entity.example.com/id';
        $set = 'saml20-sp-remote';

        $metadata = [
            'entityid'     => $entityId,
            'name'         => ['en' => 'Test SP'],
            'metadata-set' => $set,
            'attributes'   => [
                'eduPersonTargetedID'    => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'eduPersonPrincipalName' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
            ],
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        /** @var \DOMNodeList $acs */
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");

        /** @psalm-var \DOMElement $acs1 */
        $acs1 = $acs->item(0);
        $this->assertFalse($acs1->hasAttribute("isDefault"));

        $metadata['attributes.isDefault'] = true;

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);
        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");

        /** @var \DOMElement $acs1 */
        $acs1 = $acs->item(0);
        $this->assertTrue($acs1->hasAttribute("isDefault"));
        $this->assertEquals("true", $acs1->getAttribute("isDefault"));

        $metadata['attributes.isDefault'] = false;

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);
        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");

        /** @var \DOMElement $acs1 */
        $acs1 = $acs->item(0);
        $this->assertTrue($acs1->hasAttribute("isDefault"));
        $this->assertEquals("false", $acs1->getAttribute("isDefault"));
    }


    /**
     * Test the index option is used correctly.
     */
    public function testAttributeConsumingServiceIndex(): void
    {
        $entityId = 'https://entity.example.com/id';
        $set = 'saml20-sp-remote';

        $metadata = [
            'entityid'     => $entityId,
            'name'         => ['en' => 'Test SP'],
            'metadata-set' => $set,
            'attributes'   => [
                'eduPersonTargetedID'    => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'eduPersonPrincipalName' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
            ],
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");

        /** @var \DOMElement $acs1 */
        $acs1 = $acs->item(0);
        $this->assertTrue($acs1->hasAttribute("index"));
        $this->assertEquals("0", $acs1->getAttribute("index"));

        $metadata['attributes.index'] = 15;

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");

        /** @var \DOMElement $acs1 */
        $acs1 = $acs->item(0);
        $this->assertTrue($acs1->hasAttribute("index"));
        $this->assertEquals("15", $acs1->getAttribute("index"));
    }


    /**
     * Test the required protocolSupportEnumeration in AttributeAuthorityDescriptor
     */
    public function testProtocolSupportEnumeration(): void
    {
        $entityId = 'https://entity.example.com/id';
        $set = 'attributeauthority-remote';

        // without protocolSupportEnumeration fallback to default: urn:oasis:names:tc:SAML:2.0:protocol
        $metadata = [
            'entityid'     => $entityId,
            'name'         => ['en' => 'Test AA'],
            'metadata-set' => $set,
            'AttributeService' =>
                [
                    0 =>
                        [
                            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
                            'Location' => 'https://entity.example.com:8443/idp/profile/SAML2/SOAP/AttributeQuery',
                        ],
                ],
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);
        $entityDescriptorXml = $samlBuilder->getEntityDescriptorText();

        $this->assertMatchesRegularExpression(
            '/<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">/',
            $entityDescriptorXml,
        );

        // explicit protocols
        $metadata['protocols'] =
            [
                0 => 'urn:oasis:names:tc:SAML:1.1:protocol',
                1 => 'urn:oasis:names:tc:SAML:2.0:protocol',
            ];
        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);
        $entityDescriptorXml = $samlBuilder->getEntityDescriptorText();

        $protocols = implode(' ', $metadata['protocols']);
        $this->assertMatchesRegularExpression(
            '/<md:AttributeAuthorityDescriptor protocolSupportEnumeration="' . $protocols . '">/',
            $entityDescriptorXml,
        );
    }

    /**
     * Test custom metadata extension (saml:Extensions).
     */
    public function testCustomMetadataExtension(): void
    {
        $entityId = 'https://entity.example.com/id';
        $set = 'saml20-idp-remote';

        $dom = \SAML2\DOMDocumentFactory::create();
        $republishRequest = $dom->createElementNS(
            'http://eduid.cz/schema/metadata/1.0',
            'eduidmd:RepublishRequest',
        );
        $republishTargetContent = 'http://edugain.org/';
        $republishTarget = $dom->createElementNS(
            'http://eduid.cz/schema/metadata/1.0',
            'eduidmd:RepublishTarget',
            $republishTargetContent,
        );
        $republishRequest->appendChild($republishTarget);
        $ext = [new \SAML2\XML\Chunk($republishRequest)];

        $metadata = [
            'entityid' => $entityId,
            'name' => ['en' => 'Test IdP'],
            'metadata-set' => $set,
            'saml:Extensions' => $ext,
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $idpDesc = $samlBuilder->getEntityDescriptor();
        $rt = $idpDesc->getElementsByTagNameNS('http://eduid.cz/schema/metadata/1.0', 'RepublishTarget');

        /** @var \DOMElement $rt1 */
        $rt1 = $rt->item(0);
        $this->assertEquals($republishTargetContent, $rt1->textContent);
    }

    /**
     * Test adding contacts to metadata
     */
    public function testContacts(): void
    {
        $entityId = 'https://entity.example.com/id';

        //  test SP20 array parsing, no friendly name
        $set = 'saml20-sp-remote';
        $metadata = [
            'entityid'     => $entityId,
            'name'         => ['en' => 'Test SP'],
            'metadata-set' => $set,
            'contacts' => [
                [
                   'contactType'       => 'other',
                   'emailAddress'      => 'csirt@example.com',
                   'surName'           => 'CSIRT',
                   'telephoneNumber'   => '+31SECOPS',
                   'company'           => 'Acme Inc',
                   'attributes'        => [
                       'xmlns:remd'        => 'http://refeds.org/metadata',
                       'remd:contactType'  => 'http://refeds.org/metadata/contactType/security',
                   ],
                ],
                [
                   'contactType'       => 'administrative',
                   'emailAddress'      => 'j.doe@example.edu',
                   'givenName'         => 'Jane',
                   'surName'           => 'Doe',
                ],
            ],
        ];

        $samlBuilder = new SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        /** @psalm-var \DOMNodeList $acs */
        $contacts = $spDesc->getElementsByTagName("ContactPerson");
        $this->assertEquals(2, $contacts->length);

        /** @psalm-var \DOMElement $first */
        $first = $contacts->item(0);
        $this->assertTrue($first->hasAttribute("contactType"));
        $this->assertEquals("other", $first->getAttribute("contactType"));
        $this->assertTrue($first->hasAttribute("remd:contactType"));
        $this->assertEquals(
            "http://refeds.org/metadata/contactType/security",
            $first->getAttribute("remd:contactType"),
        );
        $mail = $first->getElementsByTagName("EmailAddress");
        $this->assertEquals(1, $mail->length);
        // Mailto: prefix added by builder
        $this->assertEquals("mailto:csirt@example.com", $mail->item(0)->nodeValue);

        $gn = $first->getElementsByTagName("GivenName");
        $this->assertEquals(0, $gn->length);

        $sn = $first->getElementsByTagName("SurName");
        $this->assertEquals(1, $sn->length);
        $this->assertEquals("CSIRT", $sn->item(0)->nodeValue);

        $telnr = $first->getElementsByTagName("TelephoneNumber");
        $this->assertEquals(1, $telnr->length);
        $this->assertEquals("+31SECOPS", $telnr->item(0)->nodeValue);

        $company = $first->getElementsByTagName("Company");
        $this->assertEquals(1, $company->length);
        $this->assertEquals("Acme Inc", $company->item(0)->nodeValue);

        /** @psalm-var \DOMElement $second */
        $second = $contacts->item(1);
        $this->assertTrue($second->hasAttribute("contactType"));
        $this->assertEquals("administrative", $second->getAttribute("contactType"));
        $this->assertFalse($second->hasAttribute("remd:contactType"));
        $mail = $second->getElementsByTagName("EmailAddress");
        $this->assertEquals(1, $mail->length);
        // Mailto: prefix added by builder
        $this->assertEquals("mailto:j.doe@example.edu", $mail->item(0)->nodeValue);

        $gn = $second->getElementsByTagName("GivenName");
        $this->assertEquals(1, $gn->length);
        $this->assertEquals("Jane", $gn->item(0)->nodeValue);

        $sn = $second->getElementsByTagName("SurName");
        $this->assertEquals(1, $sn->length);
        $this->assertEquals("Doe", $sn->item(0)->nodeValue);
    }

    /*
     * Test certificate data.
     */
    public function testCertificateData(): void
    {
        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID' => 'urn:x-simplesamlphp:example-sp',
            'certificate' => '../' . self::SECURITY . '/certificates/selfsigned.simplesamlphp.org.crt',
            'privatekey' => '../' . self::SECURITY . '/certificates/selfsigned.simplesamlphp.org.key',
        ];

        // Without a key name, it should have KeyDescriptors but no KeyNames.
        $samlBuilder = new SAMLBuilder('default-sp');
        $sp = new SP($info, $metadata);
        $samlBuilder->addMetadataSP20($sp->getHostedMetadata());
        $spDesc = $samlBuilder->getEntityDescriptor();

        $this->assertEquals(2, $spDesc->getElementsByTagName("KeyDescriptor")->length);
        $this->assertEquals(0, $spDesc->getElementsByTagName("KeyName")->length);

        // Add key name.
        $metadata['key_name'] = 'my-key-name';

        // It should now also have 2 KeyNames.
        $samlBuilder = new SAMLBuilder('default-sp');
        $sp = new SP($info, $metadata);
        $samlBuilder->addMetadataSP20($sp->getHostedMetadata());
        $spDesc = $samlBuilder->getEntityDescriptor();

        $this->assertEquals(2, $spDesc->getElementsByTagName("KeyDescriptor")->length);
        $keyNames = $spDesc->getElementsByTagName("KeyName");
        $this->assertEquals(2, $keyNames->length);
        $this->assertEquals('my-key-name', $keyNames->item(0)->textContent);
        $this->assertEquals('my-key-name', $keyNames->item(1)->textContent);

        // Add rollover configuration.
        $metadata['new_certificate'] = '../' . self::SECURITY . '/certificates/other.simplesamlphp.org.crt';
        $metadata['new_privatekey'] = '../' . self::SECURITY . '/certificates/other.simplesamlphp.org.key';
        $metadata['new_key_name'] = 'my-new-key-name';

        // It should now have 3 KeyNames.
        $samlBuilder = new SAMLBuilder('default-sp');
        $sp = new SP($info, $metadata);
        $samlBuilder->addMetadataSP20($sp->getHostedMetadata());
        $spDesc = $samlBuilder->getEntityDescriptor();

        $this->assertEquals(3, $spDesc->getElementsByTagName("KeyDescriptor")->length);
        $keyNames = $spDesc->getElementsByTagName("KeyName");
        $this->assertEquals(3, $keyNames->length);
        $this->assertEquals('my-new-key-name', $keyNames->item(0)->textContent);
        $this->assertEquals('my-new-key-name', $keyNames->item(1)->textContent);
        $this->assertEquals('my-key-name', $keyNames->item(2)->textContent);
    }
}
