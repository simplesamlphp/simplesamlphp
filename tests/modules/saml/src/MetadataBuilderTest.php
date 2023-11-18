<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Module\saml\Auth\Source\SP;
use SimpleSAML\Module\saml\MetadataBuilder;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\XML\md\EntityDescriptor;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XMLSecurity\TestUtils\PEMCertificatesMock;

use function array_keys;
use function count;

/**
 * Class MetadataBuilderTest
 *
 * @covers \SimpleSAML\Module\saml\MetadataBuilder
 */
final class MetadataBuilderTest extends TestCase
{
    private const ENTITY_SP = 'urn:x-simplesamlphp:serviceprovider';
    private const ENTITY_IDP = 'urn:x-simplesamlphp:identityprovider';

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
     */
    private function getEntityDescriptor(array $info, array $metadata): EntityDescriptor
    {
        $serviceProvider = new SP($info, $metadata);
        $hostedMetadata = $serviceProvider->getHostedMetadata();
        $builder = new MetadataBuilder(Configuration::getInstance(), Configuration::loadFromArray($hostedMetadata));
        return $builder->buildDocument();
    }


    /**
     * Test certificates
     */
    public function testCertificates(): void
    {
        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID' => 'urn:x-simplesamlphp:example-sp',
            'certificate' => PEMCertificatesMock::buildCertsPath('selfsigned.simplesamlphp.org.crt'),
            'privatekey' => PEMCertificatesMock::buildKeysPath('selfsigned.simplesamlphp.org.key'),
        ];

        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];

        $keyDescriptors = $spSSODescriptor->getKeyDescriptor();
        $this->assertCount(2, $keyDescriptors);
        $this->assertEquals(0, $spSSODescriptor->toXML()->getElementsByTagName("KeyName")->length);

        // Add key name.
        $metadata['key_name'] = 'my-key-name';

        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];

        $keyDescriptors = $spSSODescriptor->getKeyDescriptor();
        $this->assertCount(2, $keyDescriptors);

        $keyNames = $spSSODescriptor->toXML()->getElementsByTagName("KeyName");
        $this->assertEquals(2, $keyNames->length);
        $this->assertEquals('my-key-name', $keyNames->item(0)->textContent);
        $this->assertEquals('my-key-name', $keyNames->item(1)->textContent);

        // Add rollover configuration.
        $metadata['new_certificate'] = PEMCertificatesMock::buildCertsPath('other.simplesamlphp.org.crt');
        $metadata['new_privatekey'] = PEMCertificatesMock::buildKeysPath('other.simplesamlphp.org.key');
        $metadata['new_key_name'] = 'my-new-key-name';

        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];

        $keyDescriptors = $spSSODescriptor->getKeyDescriptor();
        $this->assertCount(3, $keyDescriptors);

        $keyNames = $spSSODescriptor->toXML()->getElementsByTagName("KeyName");
        $this->assertEquals(3, $keyNames->length);
        $this->assertEquals('my-new-key-name', $keyNames->item(0)->textContent);
        $this->assertEquals('my-new-key-name', $keyNames->item(1)->textContent);
        $this->assertEquals('my-key-name', $keyNames->item(2)->textContent);
    }


    /**
     * Test adding contacts
     */
    public function testContacts(): void
    {
        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID' => self::ENTITY_SP,
            'metadata-set' => 'saml20-sp-remote',
            'contacts' => [
                [
                    'ContactType'       => 'other',
                    'EmailAddress'      => ['mailto:csirt@example.com'],
                    'SurName'           => 'CSIRT',
                    'TelephoneNumber'   => ['+31SECOPS'],
                    'Company'           => 'Acme Inc',
                    'attributes' => [
                        [
                            'namespaceURI' => 'http://refeds.org/metadata',
                            'namespacePrefix' => 'remd',
                            'attrName' => 'contactType',
                            'attrValue' => 'http://refeds.org/metadata/contactType/security',
                        ],
                    ],
                ],
                [
                    'ContactType'       => 'administrative',
                    'EmailAddress'      => ['mailto:j.doe@example.edu'],
                    'GivenName'         => 'Jane',
                    'SurName'           => 'Doe',
                ],
            ],
        ];

        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $contacts = $entityDescriptor->getContactPerson();
        $this->assertCount(2, $contacts);
        $this->assertEquals($metadata['contacts'][0], $contacts[0]->toArray());
        $this->assertEquals($metadata['contacts'][1], $contacts[1]->toArray());
    }


    /**
     * Test custom Extensions
     */
    public function testExtensions(): void
    {
        $dom = DOMDocumentFactory::create();
        $republishRequest = $dom->createElementNS(
            'http://eduid.cz/schema/metadata/1.0',
            'eduidmd:RepublishRequest'
        );
        $republishTargetContent = 'http://edugain.org/';
        $republishTarget = $dom->createElementNS(
            'http://eduid.cz/schema/metadata/1.0',
            'eduidmd:RepublishTarget',
            $republishTargetContent
        );
        $republishRequest->appendChild($republishTarget);
        $ext = [new Chunk($republishRequest)];

        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID'     => self::ENTITY_SP,
            'metadata-set' => 'saml20-sp-remote',
            'saml:Extensions' => $ext,
        ];

        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];
        /** @psalm-var \SimpleSAML\SAML2\XML\samlp\Extensions $extensions */
        $extensions = $spSSODescriptor->getExtensions();
        $this->assertCount(1, $extensions->getList());

        $rt = $extensions->toXML()->getElementsByTagNameNS('http://eduid.cz/schema/metadata/1.0', 'RepublishTarget');

        /** @var \DOMElement $rt1 */
        $rt1 = $rt->item(0);
        $this->assertEquals($republishTargetContent, $rt1->textContent);
    }


    /**
     * Test the required protocolSupportEnumeration in AttributeAuthorityDescriptor
     */
    public function testProtocolSupportEnumeration(): void
    {
        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID'     => self::ENTITY_SP,
            'name'         => ['en' => 'Test AA'],
            'metadata-set' => 'attributeauthority-remote',
        ];

        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $attributeAuthority = $roleDescriptors[0];

        $protocolSupportEnumeration = $attributeAuthority->getProtocolSupportEnumeration();
        $this->assertEquals($protocolSupportEnumeration, [C::NS_SAMLP]);
    }


    /**
     * @dataProvider nameFormatProvider
     */
    public function testAttributesNameFormat(?string $format): void
    {
        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID' => self::ENTITY_SP,
            'metadata-set' => 'saml20-sp-remote',
            'name' => ['en' => 'Test SP'],
            'attributes' => [
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
            ],
            'attributes.NameFormat' => $format,
        ];

        // Ensure that 'attributes.NameFormat' is honored and that the default is to not set any
        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];

        /** @psalm-var \SimpleSAML\SAML2\XML\md\SPSSODescriptor $spSSODescriptor */
        $attributeConsumingServices = $spSSODescriptor->getAttributeConsumingService();
        $this->assertCount(1, $attributeConsumingServices);
        $attributeConsumingService = $attributeConsumingServices[0];

        $requestedAttributes = $attributeConsumingService->getRequestedAttribute();
        foreach ($requestedAttributes as $attr) {
            $this->assertEquals($format, $attr->getNameFormat());
        }
    }


    /**
     */
    public static function nameFormatProvider(): array
    {
        return [
            'null' => [null],
            'basic' => [C::NAMEFORMAT_BASIC],
            'uri' => [C::NAMEFORMAT_URI],
            'unspecified' => [C::NAMEFORMAT_UNSPECIFIED],
            'other' => ['urn:x-simplesamlphp:nameformat'],
        ];
    }


    /**
     */
    public function testAttributes(): void
    {
        $info = ['AuthId' => 'default-sp'];
        $metadata = [
            'entityID' => self::ENTITY_SP,
            'metadata-set' => 'saml20-sp-remote',
            'attributes' => [
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'urn:oid:0.9.2342.19200300.100.1.3',
                'Common Name' => 'urn:oid:2.5.4.3',
            ],
            'attributes.index' => 999,
            'attributes.isDefault' => true,
            'attributes.required' => [
                'urn:oid:2.5.4.3',
            ],
        ];

        // Ensure that without a 'name' set in metadata, the entire attributes-array is ignored
        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];

        /** @psalm-var \SimpleSAML\SAML2\XML\md\SPSSODescriptor $spSSODescriptor */
        $attributeConsumingServices = $spSSODescriptor->getAttributeConsumingService();
        $this->assertCount(0, $attributeConsumingServices);

        // Ensure normal operation
        $metadata['name'] = ['en' => 'Test SP'];
        $entityDescriptor = $this->getEntityDescriptor($info, $metadata);

        $roleDescriptors = $entityDescriptor->getRoleDescriptor();
        $this->assertCount(1, $roleDescriptors);
        $spSSODescriptor = $roleDescriptors[0];

        /** @psalm-var \SimpleSAML\SAML2\XML\md\SPSSODescriptor $spSSODescriptor */
        $attributeConsumingServices = $spSSODescriptor->getAttributeConsumingService();
        $this->assertCount(1, $attributeConsumingServices);
        $attributeConsumingService = $attributeConsumingServices[0];
        $this->assertTrue($attributeConsumingService->getIsDefault());
        $this->assertEquals(999, $attributeConsumingService->getIndex());

        $requestedAttributes = $attributeConsumingService->getRequestedAttribute();
        $this->assertCount(4, $requestedAttributes);

        for ($c = 0; $c < count($requestedAttributes); $c++) {
            $attrName = $requestedAttributes[$c]->getName();
            $this->assertEquals($attrName, $metadata['attributes'][array_keys($metadata['attributes'])[$c]]);

            switch ($attrName) {
                case 'urn:oid:2.5.4.3':
                    $this->assertTrue($requestedAttributes[$c]->getIsRequired());
                    $this->assertEquals('Common Name', $requestedAttributes[$c]->getFriendlyName());
                    break;
                default:
                    $this->assertNull($requestedAttributes[$c]->getIsRequired());
                    $this->assertNull($requestedAttributes[$c]->getFriendlyName());
            }
            $this->assertEmpty($requestedAttributes[$c]->getAttributeValues());
        }
    }
}
