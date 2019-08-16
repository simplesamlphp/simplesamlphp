<?php

namespace SimpleSAML\Test\Metadata;

require_once(__DIR__.'/../../../SigningTestCase.php');

use PHPUnit\Framework\TestCase;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use SimpleSAML\XML\Signer;
use SimpleSAML\Metadata\SAMLParser;

/**
 * Test SAML parsing
 */
class SAMLParserTest extends \SimpleSAML\Test\SigningTestCase
{
    /**
     * Test Registration Info is parsed
     * @return void
     */
    public function testRegistrationInfo()
    {
        $expected = [
            'registrationAuthority' => 'https://incommon.org',
        ];

        $document = \SAML2\DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
    </Extensions>
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"/>
  </EntityDescriptor>
</EntitiesDescriptor>
XML
        );


        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }


    /**
     * Test RegistrationInfo is inherited correctly from parent EntitiesDescriptor.
     * According to the spec overriding RegistrationInfo is not valid. We ignore attempts to override
     * @return void
     */
    public function testRegistrationInfoInheritance()
    {
        $expected = [
            'registrationAuthority' => 'https://incommon.org',
        ];

        $document = \SAML2\DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <Extensions>
    <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
  </Extensions>
  <EntityDescriptor entityID="theEntityID">
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"/>
  </EntityDescriptor>
  <EntitiesDescriptor>
    <EntityDescriptor entityID="subEntityId">
      <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"/>
    </EntityDescriptor>
    <EntityDescriptor entityID="subEntityIdOverride">
      <Extensions>
        <mdrpi:RegistrationInfo registrationAuthority="overrides-are-ignored"/>
      </Extensions>
      <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"/>
    </EntityDescriptor>
  </EntitiesDescriptor>
</EntitiesDescriptor>
XML
        );

        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);
        $this->assertArrayHasKey('subEntityId', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        /** @var array $metadata */
        $metadata = $entities['theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

        /** @var array $metadata */
        $metadata = $entities['subEntityId']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

        /** @var array $metadata */
        $metadata = $entities['subEntityIdOverride']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }


    /**
     * Test AttributeConsumingService is parsed
     * @return void
     */
    public function testAttributeConsumingServiceParsing()
    {
        $document = \SAML2\DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <EntityDescriptor entityID="theEntityID">
    <Extensions>
      <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
    </Extensions>
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
      <AttributeConsumingService index="0">
        <ServiceName xml:lang="en">Example service</ServiceName>
        <ServiceDescription xml:lang="nl">Dit is een voorbeeld voor de unittest.</ServiceDescription>

        <RequestedAttribute FriendlyName="eduPersonPrincipalName" Name="urn:mace:dir:attribute-def:eduPersonPrincipalName" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri" isRequired="true"/>
        <RequestedAttribute FriendlyName="mail" Name="urn:mace:dir:attribute-def:mail" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri"/>
        <RequestedAttribute FriendlyName="displayName" Name="urn:mace:dir:attribute-def:displayName" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri"/>
      </AttributeConsumingService>
    </SPSSODescriptor>

  </EntityDescriptor>
</EntitiesDescriptor>
XML
        );

        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);

        /** @var array $metadata */
        $metadata = $entities['theEntityID']->getMetadata20SP();

        $this->assertEquals("Example service", $metadata['name']['en']);
        $this->assertEquals("Dit is een voorbeeld voor de unittest.", $metadata['description']['nl']);

        $expected_a = [
            "urn:mace:dir:attribute-def:eduPersonPrincipalName",
            "urn:mace:dir:attribute-def:mail",
            "urn:mace:dir:attribute-def:displayName"
        ];
        $expected_r = ["urn:mace:dir:attribute-def:eduPersonPrincipalName"];

        $this->assertEquals($expected_a, $metadata['attributes']);
        $this->assertEquals($expected_r, $metadata['attributes.required']);
    }


    /**
     * @return \DOMDocument
     */
    public function makeTestDocument()
    {
        $doc = new \DOMDocument();
        $doc->loadXML(
            <<<XML
<?xml version="1.0"?>
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata">
  <EntityDescriptor entityID="theEntityID">
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"/>
  </EntityDescriptor>
</EntitiesDescriptor>
XML
        );

        $entities_root = $doc->getElementsByTagName('EntitiesDescriptor')->item(0);
        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($entities_root, $entities_root);

        return $doc;
    }


    /**
     * Test RoleDescriptor/Extensions is parsed
     * @return void
     */
    public function testRoleDescriptorExtensions()
    {
        $expected = [
            'scope' => [
                'example.org',
                'example.net',
            ],
            'UIInfo' => [
                'DisplayName' => ['en' => 'DisplayName', 'af' => 'VertoonNaam'],
                'Description' => ['en' => 'Description',],
                'InformationURL' => ['en' => 'https://localhost/information',],
                'PrivacyStatementURL' => ['en' => 'https://localhost/privacypolicy',],
                'Logo' => [
                    [
                        'url' => 'https://localhost/logo',
                        'height' => 16,
                        'width' => 17,
                    ],
                    [
                        'url' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
                        'height' => 2,
                        'width' => 1,
                    ],
                ],
            ],
            'DiscoHints' => [
                'IPHint' => ['127.0.0.1', '127.0.0.2',],
                'DomainHint' => ['example.net', 'example.org',],
                'GeolocationHint' => ['geo:-29.00000,24.00000;u=830000',],
            ],
            'name' => ['en' => 'DisplayName', 'af' => 'VertoonNaam'],
        ];

        $document = \SAML2\DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi" xmlns:shibmd="urn:mace:shibboleth:metadata:1.0" xmlns:mdui="urn:oasis:names:tc:SAML:metadata:ui">
  <EntityDescriptor entityID="theEntityID">
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
            <mdui:Logo width="1" height="2">data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=</mdui:Logo>
          </mdui:UIInfo>
          <mdui:DiscoHints>
            <mdui:IPHint>127.0.0.1</mdui:IPHint>
            <mdui:IPHint>127.0.0.2</mdui:IPHint>
            <mdui:DomainHint>example.net</mdui:DomainHint>
            <mdui:DomainHint>example.org</mdui:DomainHint>
            <mdui:GeolocationHint>geo:-29.00000,24.00000;u=830000</mdui:GeolocationHint>
          </mdui:DiscoHints>
        </Extensions>
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://IdentityProvider.com/SAML/SSO/Browser"/>
    </IDPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML
        );

        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);
        // Various MDUI elements are accessible
        /** @var array $metadata */
        $metadata = $entities['theEntityID']->getMetadata20IdP();
        $this->assertEquals($expected['scope'], $metadata['scope'], 'shibmd:Scope elements not reflected in parsed metadata');
        $this->assertEquals($expected['UIInfo'], $metadata['UIInfo'], 'mdui:UIInfo elements not reflected in parsed metadata');
        $this->assertEquals($expected['DiscoHints'], $metadata['DiscoHints'], 'mdui:DiscoHints elements not reflected in parsed metadata');
        $this->assertEquals($expected['name'], $metadata['name']);
    }
}
