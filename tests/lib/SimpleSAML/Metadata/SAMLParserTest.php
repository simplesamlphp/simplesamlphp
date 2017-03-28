<?php
namespace SimpleSAML\Metadata;

/**
 * Test SAML parsing
 */
class SAMLParserTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Test Registration Info is parsed
     */
    public function testRegistrationInfo()
    {
        $expected = array(
            'registrationAuthority' => 'https://incommon.org',
        );

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


        $entities = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        $metadata = $entities['theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

    }

    /**
     * Test RegistrationInfo is inherited correctly from parent EntitiesDescriptor.
     * According to the spec overriding RegistrationInfo is not valid. We ignore attempts to override
     */
    public function testRegistrationInfoInheritance()
    {
        $expected = array(
            'registrationAuthority' => 'https://incommon.org',
        );

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

        $entities = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);
        $this->assertArrayHasKey('subEntityId', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        $metadata = $entities['theEntityID']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

        $metadata = $entities['subEntityId']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);

        $metadata = $entities['subEntityIdOverride']->getMetadata20SP();
        $this->assertEquals($expected, $metadata['RegistrationInfo']);
    }

    /**
     * Test AttributeConsumingService is parsed
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

        $entities = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);

        $metadata = $entities['theEntityID']->getMetadata20SP();

        $this->assertEquals("Example service", $metadata['name']['en']);
        $this->assertEquals("Dit is een voorbeeld voor de unittest.", $metadata['description']['nl']);

        $expected_a = array("urn:mace:dir:attribute-def:eduPersonPrincipalName", "urn:mace:dir:attribute-def:mail", "urn:mace:dir:attribute-def:displayName");
        $expected_r = array("urn:mace:dir:attribute-def:eduPersonPrincipalName");

        $this->assertEquals($expected_a, $metadata['attributes']);
        $this->assertEquals($expected_r, $metadata['attributes.required']);
    }

}
