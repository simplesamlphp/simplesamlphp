<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Metadata;

use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use SAML2\Constants;
use SimpleSAML\Metadata\MetadataParser;
use SimpleSAML\Metadata\SAMLParser;
use SimpleSAML\Test\SigningTestCase;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\Signer;

/**
 * Test SAML parsing
 */
#[CoversClass(MetadataParser::class)]
class MetadataParserTest extends SigningTestCase
{
    /**
     * Test that DiscoveryResponse is parsed
     */
    public function testDiscoveryResponse(): void
    {
        $expected = [
            0 => [
                'index' => 43,
                'Binding' => Constants::NS_IDPDISC,
                'Location' => 'https://simplesamlphp.org/some/endpoint',
                'isDefault' => false,
            ],
        ];

        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="uri:theEntityID">
  <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <Extensions>
      <idpdisc:DiscoveryResponse xmlns:idpdisc="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Binding="urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol" Location="https://simplesamlphp.org/some/endpoint" index="43" isDefault="false" />
    </Extensions>
    <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
  </SPSSODescriptor>
</EntityDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
        $this->assertEquals(1, count($entities));
        $e = reset($entities);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        // DiscoveryResponse is accessible in the SP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['DiscoveryResponse']);
    }


    /**
     * Test Registration Info is parsed
     */
    public function testRegistrationInfo(): void
    {
        $expected = [
            'authority' => 'https://incommon.org',
        ];

        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
    </Extensions>
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
    </SPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }


    /**
     * Test RegistrationInfo is inherited correctly from parent EntitiesDescriptor.
     * According to the spec overriding RegistrationInfo is not valid. We ignore attempts to override
     */
    public function testRegistrationInfoInheritance(): void
    {
        $expected = [
            'authority' => 'https://incommon.org',
        ];

        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <Extensions>
    <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
  </Extensions>
  <EntityDescriptor entityID="uri:theEntityID">
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
    </SPSSODescriptor>
  </EntityDescriptor>
  <EntitiesDescriptor>
    <EntityDescriptor entityID="uri:subEntityId">
      <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
      </SPSSODescriptor>
    </EntityDescriptor>
    <EntityDescriptor entityID="uri:subEntityIdOverride">
      <Extensions>
        <mdrpi:RegistrationInfo registrationAuthority="overrides-are-ignored"/>
      </Extensions>
      <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
      </SPSSODescriptor>
    </EntityDescriptor>
  </EntitiesDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        $this->assertArrayHasKey('uri:subEntityId', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

        /** @var array $metadata */
        $metadata = $entities['uri:subEntityId']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

        /** @var array $metadata */
        $metadata = $entities['uri:subEntityIdOverride']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }


    /**
     * Test Registration Info is parsed
     */
    public function testRegistrationPolicy(): void
    {
        $expected = [
            'authority' => 'https://safire.ac.za',
            'policies' => [ 'en' => 'https://safire.ac.za/safire/policy/mrps/v20190207.html' ],
        ];

        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://safire.ac.za">
        <mdrpi:RegistrationPolicy xml:lang="en">https://safire.ac.za/safire/policy/mrps/v20190207.html</mdrpi:RegistrationPolicy>
      </mdrpi:RegistrationInfo>
    </Extensions>
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
    </SPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }


    /**
     * Test Registration Info is parsed
     */
    public function testRegistrationInstant(): void
    {
        $expected = [
            'authority' => 'https://safire.ac.za',
            'instant' => '2023-02-08T13:06:55Z'
        ];

        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://safire.ac.za" registrationInstant="2023-02-08T13:06:55Z" />
    </Extensions>
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
    </SPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }


    /**
     * Test AttributeConsumingService is parsed
     */
    public function testAttributeConsumingServiceParsing(): void
    {
        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
    </Extensions>
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
      <AttributeConsumingService index="0">
        <ServiceName xml:lang="en">Example service</ServiceName>
        <ServiceDescription xml:lang="nl">Dit is een voorbeeld voor de unittest.</ServiceDescription>

        <RequestedAttribute FriendlyName="eduPersonPrincipalName" Name="urn:mace:dir:attribute-def:eduPersonPrincipalName" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri" isRequired="true"/>
        <RequestedAttribute FriendlyName="mail" Name="urn:mace:dir:attribute-def:mail" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri"/>
        <RequestedAttribute FriendlyName="displayName" Name="urn:mace:dir:attribute-def:displayName" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri"/>
        <RequestedAttribute FriendlyName="eduPersonEntitlement" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.7" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
          <saml:AttributeValue xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">urn:mace:dir:entitlement:common-lib-terms</saml:AttributeValue>
        </RequestedAttribute>
      </AttributeConsumingService>
    </SPSSODescriptor>

  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);

        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20SP();
        $this->assertEquals("Example service", $metadata['name']['en']);
        $this->assertEquals("Dit is een voorbeeld voor de unittest.", $metadata['description']['nl']);

        $expected_a = [
            "urn:mace:dir:attribute-def:eduPersonPrincipalName",
            "urn:mace:dir:attribute-def:mail",
            "urn:mace:dir:attribute-def:displayName",
            "urn:oid:1.3.6.1.4.1.5923.1.1.1.7" => [
                "urn:mace:dir:entitlement:common-lib-terms",
            ],
        ];
        $expected_r = ["urn:mace:dir:attribute-def:eduPersonPrincipalName"];

        $this->assertEquals($expected_a, $metadata['attributes']);
        $this->assertEquals($expected_r, $metadata['attributes.required']);
    }


    /**
     * @return \DOMDocument
     */
    public function makeTestDocument(): DOMDocument
    {
        $doc = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata">
  <EntityDescriptor entityID="uri:theEntityID">
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://ServiceProvider.com/SAML/SSO/POST" index="1"/>
    </SPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        /** @psalm-var \DOMElement $entities_root */
        $entities_root = $doc->getElementsByTagName('EntitiesDescriptor')->item(0);
        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($entities_root, $entities_root);

        return $doc;
    }


    /**
     * Test RoleDescriptor/Extensions is parsed
     */
    public function testRoleDescriptorExtensions(): void
    {
        $expected = [
            'scope' => [
                [
                    'scope' => 'example.org',
                    'isRegexpScope' => false,
                ],
                [
                    'scope' => 'example.net',
                    'isRegexpScope' => false,
                ],
            ],
            'UIInfo' => [
                'DisplayName' => ['en' => 'DisplayName', 'af' => 'VertoonNaam'],
                'Description' => ['en' => 'Description'],
                'InformationURL' => ['en' => 'https://localhost/information'],
                'PrivacyStatementURL' => ['en' => 'https://localhost/privacypolicy'],
                'Logo' => [
                    [
                        'url' => 'https://localhost/logo',
                        'height' => 16,
                        'width' => 17,
                    ],
                    [
                        'url' => 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==',
                        'height' => 2,
                        'width' => 1,
                    ],
                ],
            ],
            'DiscoHints' => [
                'IPHint' => ['127.0.0.1/32', '127.0.0.2/32'],
                'DomainHint' => ['example.net', 'example.org'],
                'GeolocationHint' => ['geo:-29.00000,24.00000;u=830000'],
            ],
            'name' => ['en' => 'DisplayName', 'af' => 'VertoonNaam'],
        ];

        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi" xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
  <EntityDescriptor entityID="uri:theEntityID">
    <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <Extensions>
          <shibmd:Scope regexp="false">example.org</shibmd:Scope>
          <shibmd:Scope regexp="false">example.net</shibmd:Scope>
          <mdui:UIInfo>
            <mdui:DisplayName xml:lang="en">DisplayName</mdui:DisplayName>
            <mdui:DisplayName xml:lang="af">VertoonNaam</mdui:DisplayName>
            <mdui:Description xml:lang="en">Description</mdui:Description>
            <mdui:PrivacyStatementURL xml:lang="en">https://localhost/privacypolicy</mdui:PrivacyStatementURL>
            <mdui:InformationURL xml:lang="en">https://localhost/information</mdui:InformationURL>
            <mdui:Logo width="17" height="16">https://localhost/logo</mdui:Logo>
            <mdui:Logo width="1" height="2">data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==</mdui:Logo>
          </mdui:UIInfo>
          <mdui:DiscoHints>
            <mdui:IPHint>127.0.0.1/32</mdui:IPHint>
            <mdui:IPHint>127.0.0.2/32</mdui:IPHint>
            <mdui:DomainHint>example.net</mdui:DomainHint>
            <mdui:DomainHint>example.org</mdui:DomainHint>
            <mdui:GeolocationHint>geo:-29.00000,24.00000;u=830000</mdui:GeolocationHint>
          </mdui:DiscoHints>
        </Extensions>
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://IdentityProvider.com/SAML/SSO/Browser"/>
    </IDPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        // Various MDUI elements are accessible
        /** @var array $metadata */
        $metadata = $entities['uri:theEntityID']->getMetadata20IdP();
        $this->assertEquals(
            $expected['scope'],
            $metadata['scope'],
            'shibmd:Scope elements not reflected in parsed metadata',
        );
        $this->assertEquals(
            $expected['UIInfo'],
            $metadata['UIInfo'],
            'mdui:UIInfo elements not reflected in parsed metadata',
        );
        $this->assertEquals(
            $expected['DiscoHints'],
            $metadata['DiscoHints'],
            'mdui:DiscoHints elements not reflected in parsed metadata',
        );
        $this->assertEquals($expected['name'], $metadata['name']);
    }


    /**
     * Test entity category hidden from discovery is parsed
     */
    public function testHiddenFromDiscovery(): void
    {
        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
          <saml:AttributeValue>https://example.org/some-category</saml:AttributeValue>
        </saml:Attribute>
        <saml:Attribute Name="http://macedir.org/entity-category" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
          <saml:AttributeValue>http://refeds.org/category/hide-from-discovery</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </Extensions>
    <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://IdentityProvider.com/SAML/SSO/Browser"/>
    </IDPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        $metadata = $entities['uri:theEntityID']->getMetadata20IdP();
        $this->assertArrayHasKey('hide.from.discovery', $metadata);
        $this->assertTrue($metadata['hide.from.discovery']);
    }


    /**
     * Test entity category hidden from discovery is not returned when not present
     */
    public function testHiddenFromDiscoveryNotHidden(): void
    {
        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://safire.ac.za">
        <mdrpi:RegistrationPolicy xml:lang="en">https://safire.ac.za/safire/policy/mrps/v20190207.html</mdrpi:RegistrationPolicy>
      </mdrpi:RegistrationInfo>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
          <saml:AttributeValue>https://example.org/some-category</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </Extensions>
    <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://IdentityProvider.com/SAML/SSO/Browser"/>
    </IDPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('uri:theEntityID', $entities);
        $metadata = $entities['uri:theEntityID']->getMetadata20IdP();
        $this->assertArrayNotHasKey('hide.from.discovery', $metadata);
    }


    /**
     * Test entity category hidden from discovery is not returned when no mace dir entity categories present
     */
    public function testHiddenFromDiscoveryNotHiddenNoMaceDirEC(): void
    {
        $document = DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:mdattr="urn:oasis:names:tc:SAML:metadata:attribute">
  <EntityDescriptor entityID="uri:theEntityID">
    <Extensions>
      <mdattr:EntityAttributes>
        <saml:Attribute Name="http://macedir.org/entity-category-support" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri">
          <saml:AttributeValue>https://example.org/some-supported-category</saml:AttributeValue>
        </saml:Attribute>
      </mdattr:EntityAttributes>
    </Extensions>
    <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://IdentityProvider.com/SAML/SSO/Browser"/>
    </IDPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML,
        );

       $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
//        $entities = SAMLParser::parseDescriptorsElement($document->documentElement);
//        $this->assertArrayHasKey('uri:theEntityID', $entities);
        $metadata = $entities['uri:theEntityID']->getMetadata20IdP();
        $this->assertArrayNotHasKey('hide.from.discovery', $metadata);
    }


    public function testKeyInfo(): void
    {
        $expected = "MIICxDCCAi2gAwIBAgIUZ9QDx+SBFHednUWDFGm9tyVKrgQwDQYJKoZIhvcNAQELBQAwczElMCMGA1UEAwwcc2VsZnNpZ25lZC5zaW1wbGVzYW1scGhwLm9yZzEZMBcGA1UECgwQU2ltcGxlU0FNTHBocCBIUTERMA8GA1UEBwwISG9ub2x1bHUxDzANBgNVBAgMBkhhd2FpaTELMAkGA1UEBhMCVVMwIBcNMjIxMjAzMTAzNTQwWhgPMjEyMjExMDkxMDM1NDBaMHMxJTAjBgNVBAMMHHNlbGZzaWduZWQuc2ltcGxlc2FtbHBocC5vcmcxGTAXBgNVBAoMEFNpbXBsZVNBTUxwaHAgSFExETAPBgNVBAcMCEhvbm9sdWx1MQ8wDQYDVQQIDAZIYXdhaWkxCzAJBgNVBAYTAlVTMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDessdFRVDTMQQW3Na81B1CjJV1tmY3nopoIhZrkbDxLa+pv7jGDRcYreyu1DoQxEs06V2nHLoyOPhqJXSFivqtUwVYhR6NYgbNI6RRSsIJCweH0YOdlHna7gULPcLX0Bfbi4odStaFwG9yzDySwSEPtsKxm5pENPjNVGh+jJ+H/QIDAQABo1MwUTAdBgNVHQ4EFgQUvV75t8EoQo2fVa0E9otdtIGK5X0wHwYDVR0jBBgwFoAUvV75t8EoQo2fVa0E9otdtIGK5X0wDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOBgQANQUeiwPJXkWMXuaDHToEBKcezYGqGEYnGUi9LMjeb+Kln7X8nn5iknlz4k77rWCbSwLPC/WDr0ySYQA+HagaeUaFpoiYFJKS6uFlK1HYWnM3W4PUiGHg1/xeZlMO44wTwybXVo0y9KMhchfB5XNbDdoJcqWYvi6xtmZZNRbxUyw==";

        $document = DOMDocumentFactory::fromString(
            <<<XML
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="urn:x-simplesamlphp:example-sp">
  <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>MIICxDCCAi2gAwIBAgIUZ9QDx+SBFHednUWDFGm9tyVKrgQwDQYJKoZIhvcNAQELBQAwczElMCMGA1UEAwwcc2VsZnNpZ25lZC5zaW1wbGVzYW1scGhwLm9yZzEZMBcGA1UECgwQU2ltcGxlU0FNTHBocCBIUTERMA8GA1UEBwwISG9ub2x1bHUxDzANBgNVBAgMBkhhd2FpaTELMAkGA1UEBhMCVVMwIBcNMjIxMjAzMTAzNTQwWhgPMjEyMjExMDkxMDM1NDBaMHMxJTAjBgNVBAMMHHNlbGZzaWduZWQuc2ltcGxlc2FtbHBocC5vcmcxGTAXBgNVBAoMEFNpbXBsZVNBTUxwaHAgSFExETAPBgNVBAcMCEhvbm9sdWx1MQ8wDQYDVQQIDAZIYXdhaWkxCzAJBgNVBAYTAlVTMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDessdFRVDTMQQW3Na81B1CjJV1tmY3nopoIhZrkbDxLa+pv7jGDRcYreyu1DoQxEs06V2nHLoyOPhqJXSFivqtUwVYhR6NYgbNI6RRSsIJCweH0YOdlHna7gULPcLX0Bfbi4odStaFwG9yzDySwSEPtsKxm5pENPjNVGh+jJ+H/QIDAQABo1MwUTAdBgNVHQ4EFgQUvV75t8EoQo2fVa0E9otdtIGK5X0wHwYDVR0jBBgwFoAUvV75t8EoQo2fVa0E9otdtIGK5X0wDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOBgQANQUeiwPJXkWMXuaDHToEBKcezYGqGEYnGUi9LMjeb+Kln7X8nn5iknlz4k77rWCbSwLPC/WDr0ySYQA+HagaeUaFpoiYFJKS6uFlK1HYWnM3W4PUiGHg1/xeZlMO44wTwybXVo0y9KMhchfB5XNbDdoJcqWYvi6xtmZZNRbxUyw==</ds:X509Certificate>
        </ds:X509Data>
        <ds:KeyName>my-key-name</ds:KeyName>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:KeyDescriptor use="encryption">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>MIICxDCCAi2gAwIBAgIUZ9QDx+SBFHednUWDFGm9tyVKrgQwDQYJKoZIhvcNAQELBQAwczElMCMGA1UEAwwcc2VsZnNpZ25lZC5zaW1wbGVzYW1scGhwLm9yZzEZMBcGA1UECgwQU2ltcGxlU0FNTHBocCBIUTERMA8GA1UEBwwISG9ub2x1bHUxDzANBgNVBAgMBkhhd2FpaTELMAkGA1UEBhMCVVMwIBcNMjIxMjAzMTAzNTQwWhgPMjEyMjExMDkxMDM1NDBaMHMxJTAjBgNVBAMMHHNlbGZzaWduZWQuc2ltcGxlc2FtbHBocC5vcmcxGTAXBgNVBAoMEFNpbXBsZVNBTUxwaHAgSFExETAPBgNVBAcMCEhvbm9sdWx1MQ8wDQYDVQQIDAZIYXdhaWkxCzAJBgNVBAYTAlVTMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDessdFRVDTMQQW3Na81B1CjJV1tmY3nopoIhZrkbDxLa+pv7jGDRcYreyu1DoQxEs06V2nHLoyOPhqJXSFivqtUwVYhR6NYgbNI6RRSsIJCweH0YOdlHna7gULPcLX0Bfbi4odStaFwG9yzDySwSEPtsKxm5pENPjNVGh+jJ+H/QIDAQABo1MwUTAdBgNVHQ4EFgQUvV75t8EoQo2fVa0E9otdtIGK5X0wHwYDVR0jBBgwFoAUvV75t8EoQo2fVa0E9otdtIGK5X0wDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOBgQANQUeiwPJXkWMXuaDHToEBKcezYGqGEYnGUi9LMjeb+Kln7X8nn5iknlz4k77rWCbSwLPC/WDr0ySYQA+HagaeUaFpoiYFJKS6uFlK1HYWnM3W4PUiGHg1/xeZlMO44wTwybXVo0y9KMhchfB5XNbDdoJcqWYvi6xtmZZNRbxUyw==</ds:X509Certificate>
        </ds:X509Data>
        <ds:KeyName>my-key-name</ds:KeyName>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="http://localhost/simplesaml/module.php/saml/sp/saml2-logout.php/default-sp"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="http://localhost/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" index="0"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="http://localhost/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" index="1"/>
  </md:SPSSODescriptor>
</md:EntityDescriptor>
XML,
        );

        $entities = MetadataParser::parseDescriptorsElement($document->documentElement);
        $metadata = $entities['urn:x-simplesamlphp:example-sp']->getMetadata20SP();

        $this->assertEquals(2, count($metadata['keys']));
        $this->assertEquals($expected, $metadata['keys'][0]['X509Certificate']);
    }
    
}
