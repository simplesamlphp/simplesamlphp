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
}
