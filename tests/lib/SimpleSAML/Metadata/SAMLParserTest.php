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
     * @dataProvider acsXmlMetadataProvider
     *
     * Test AttributeConsumerService metadata parsing to ensure attributes, attributes.required are parsed correctly
     */
    public function testAttributeConsumerServiceParsing($acsXmlMetadata, $expectedAttr, $expectedRequiredAttr)
    {
        $document = \SAML2\DOMDocumentFactory::fromString(
            <<<XML
<EntitiesDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata">
  <EntityDescriptor entityID="theEntityID">
    <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    {$acsXmlMetadata}
    </SPSSODescriptor>
  </EntityDescriptor>
</EntitiesDescriptor>
XML
        );

        $entities = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsElement($document->documentElement);
        $entity = $entities['theEntityID']->getMetadata20SP();

        $this->assertEquals($expectedAttr, isset($entity['attributes']) ? $entity['attributes'] : null);
        $this->assertEquals($expectedRequiredAttr, isset($entity['attributes.required']) ? $entity['attributes.required'] : null);
    }


    public function acsXmlMetadataProvider()
    {
        return array(
            array(
                '<AttributeConsumingService index="0">
     <ServiceName xml:lang="en">TEST SP</ServiceName>
     <RequestedAttribute FriendlyName="uid" Name="urn:oid:0.9.2342.19200300.100.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
     <RequestedAttribute FriendlyName="eduPersonAffiliation" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
</AttributeConsumingService>',
                array('urn:oid:0.9.2342.19200300.100.1.1', 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'),
                array('urn:oid:0.9.2342.19200300.100.1.1', 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1')
            ),
            array(
                '<AttributeConsumingService index="0">
     <ServiceName xml:lang="en">TEST SP</ServiceName>
     <RequestedAttribute FriendlyName="uid" Name="urn:oid:0.9.2342.19200300.100.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="false"/>
     <RequestedAttribute FriendlyName="eduPersonAffiliation" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="true"/>
</AttributeConsumingService>',
                array('urn:oid:0.9.2342.19200300.100.1.1', 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'),
                array('urn:oid:1.3.6.1.4.1.5923.1.1.1.1')
            ),
            array(
                '<AttributeConsumingService index="0">
     <ServiceName xml:lang="en">TEST SP</ServiceName>
     <RequestedAttribute FriendlyName="uid" Name="urn:oid:0.9.2342.19200300.100.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="false"/>
     <RequestedAttribute FriendlyName="eduPersonAffiliation" Name="urn:oid:1.3.6.1.4.1.5923.1.1.1.1" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:uri" isRequired="false"/>
</AttributeConsumingService>',
                array('urn:oid:0.9.2342.19200300.100.1.1', 'urn:oid:1.3.6.1.4.1.5923.1.1.1.1'),
                null
            ),
            array(
                '',
                null,
                null
            ),
        );
    }
}
