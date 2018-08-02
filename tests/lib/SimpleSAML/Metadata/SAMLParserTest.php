<?php

namespace SimpleSAML\Metadata;

use PHPUnit\Framework\TestCase;

/**
 * Test SAML parsing
 */
class SAMLParserTest extends TestCase
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


        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
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

        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
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
        <ServiceName xml:lang="en">Example service 0</ServiceName>
        <ServiceDescription xml:lang="nl">Dit is een voorbeeld voor de unittest 0.</ServiceDescription>

        <RequestedAttribute FriendlyName="eduPersonPrincipalName" Name="urn:mace:dir:attribute-def:eduPersonPrincipalName" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri" isRequired="true"/>
        <RequestedAttribute FriendlyName="mail" Name="urn:mace:dir:attribute-def:mail" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri"/>
        <RequestedAttribute FriendlyName="displayName" Name="urn:mace:dir:attribute-def:displayName" NameFormat="urn:mace:shibboleth:1.0:attributeNamespace:uri"/>
      </AttributeConsumingService>
      <AttributeConsumingService index="1" isDefault="true">
        <ServiceName xml:lang="en">Example service 1</ServiceName>
        <ServiceDescription xml:lang="nl">Dit is een voorbeeld voor de unittest 1.</ServiceDescription>

        <RequestedAttribute FriendlyName="testFN1" Name="testN1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
        <RequestedAttribute FriendlyName="testFN2" Name="testN2" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic" isRequired="true"/>
        <RequestedAttribute FriendlyName="testFN3" Name="testN3" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic"/>
      </AttributeConsumingService>
    </SPSSODescriptor>

  </EntityDescriptor>
</EntitiesDescriptor>
XML
        );

        $entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);

        $metadata = $entities['theEntityID']->getMetadata20SP();

        $expected_AttributeConsumingService = array (
            0 =>
            array (
              'name' =>
              array (
                'en' => 'Example service 0',
              ),
              'description' =>
              array (
                'nl' => 'Dit is een voorbeeld voor de unittest 0.',
              ),
              'attributes' =>
              array (
                0 => 'urn:mace:dir:attribute-def:eduPersonPrincipalName',
                1 => 'urn:mace:dir:attribute-def:mail',
                2 => 'urn:mace:dir:attribute-def:displayName',
              ),
              'attributes.required' =>
              array (
                0 => 'urn:mace:dir:attribute-def:eduPersonPrincipalName',
              ),
              'attributes.NameFormat' => 'urn:mace:shibboleth:1.0:attributeNamespace:uri',
            ),
            1 =>
            array (
              'name' =>
              array (
                'en' => 'Example service 1',
              ),
              'description' =>
              array (
                'nl' => 'Dit is een voorbeeld voor de unittest 1.',
              ),
              'attributes' =>
              array (
                0 => 'testN1',
                1 => 'testN2',
                2 => 'testN3',
              ),
              'attributes.required' =>
              array (
                0 => 'testN1',
                1 => 'testN2',
              ),
              'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
            ),
        );

        $expected_AttributeConsumingService_default = 1;

        $this->assertEquals($expected_AttributeConsumingService, $metadata['AttributeConsumingService']);
        $this->assertEquals($expected_AttributeConsumingService_default, $metadata['AttributeConsumingService.default']);
    }

}
