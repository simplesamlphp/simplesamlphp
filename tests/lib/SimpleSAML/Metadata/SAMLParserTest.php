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

        $document = \SAML2_DOMDocumentFactory::fromString(
            <<<XML
<EntityDescriptor entityID="theEntityID"
 xmlns="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:mdrpi="urn:oasis:names:tc:SAML:metadata:rpi">
  <Extensions>
    <mdrpi:RegistrationInfo registrationAuthority="https://incommon.org"/>
     </Extensions>
  <SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
  </SPSSODescriptor>
</EntityDescriptor>
XML
        );


        $entities = \SimpleSAML_Metadata_SAMLParser::parseDescriptorsElement($document->documentElement);
        $this->assertArrayHasKey('theEntityID', $entities);
        // RegistrationInfo is accessible in the SP or IDP metadata accessors
        $this->assertEquals($expected, $entities['theEntityID']->getMetadata20SP()['RegistrationInfo']);

    }
}
