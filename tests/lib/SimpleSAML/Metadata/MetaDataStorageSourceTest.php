<?php


/**
 * Class SimpleSAML_Metadata_MetaDataStorageSourceTest
 */
class SimpleSAML_Metadata_MetaDataStorageSourceTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test SimpleSAML_Metadata_MetaDataStorageSourceTest::getConfig XML bad source
     * @expectedException Exception
     */
    public function testBadXMLSource() {
        SimpleSAML_Metadata_MetaDataStorageSource::getSource(["type"=>"xml", "foo"=>"baa"]);
    }

    /**
     * Test SimpleSAML_Metadata_MetaDataStorageSourceTest::getConfig invalid static XML source
     * @expectedException Exception
     */
    public function testInvalidStaticXMLSource() {
        $strTestXML = "
<EntityDescriptor ID=\"_12345678-90ab-cdef-1234-567890abcdef\" entityID=\"https://saml.idp/entityid\" xmlns=\"urn:oasis:names:tc:SAML:2.0:metadata\">
</EntityDescriptor>
";
        SimpleSAML_Metadata_MetaDataStorageSource::getSource(["type"=>"xml", "xml"=>$strTestXML]);
    }

    /**
     * Test SimpleSAML_Metadata_MetaDataStorageSourceTest::getConfig XML static XML source
     */
    public function testStaticXMLSource() {
        $testEntityId = "https://saml.idp/entityid";
        $strTestXML = "
<EntityDescriptor ID=\"_12345678-90ab-cdef-1234-567890abcdef\" entityID=\"$testEntityId\" xmlns=\"urn:oasis:names:tc:SAML:2.0:metadata\">
<RoleDescriptor xsi:type=\"fed:ApplicationServiceType\"
protocolSupportEnumeration=\"http://docs.oasis-open.org/ws-sx/ws-trust/200512 http://schemas.xmlsoap.org/ws/2005/02/trust http://docs.oasis-open.org/wsfed/federation/200706\"
ServiceDisplayName=\"SimpleSAMLphp Test\"
xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
xmlns:fed=\"http://docs.oasis-open.org/wsfed/federation/200706\">
<NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:persistent</NameIDFormat>
<SingleSignOnService Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect\" Location=\"https://saml.idp/sso/\"/>
<SingleLogoutService Binding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect\" Location=\"https://saml.idp/logout/\"/>
</RoleDescriptor>
<IDPSSODescriptor protocolSupportEnumeration=\"urn:oasis:names:tc:SAML:2.0:protocol\"/>
</EntityDescriptor>
";
        // The primary test here is that - in contrast to the others above - this loads without error
        // As a secondary thing, check that the entity ID from the static source provided can be extracted
        $source = SimpleSAML_Metadata_MetaDataStorageSource::getSource(["type"=>"xml", "xml"=>$strTestXML]);
        $idpSet = $source->getMetadataSet("saml20-idp-remote");
        $this->assertArrayHasKey($testEntityId, $idpSet,  "Did not extract expected IdP entity ID from static XML source");
	// Finally verify that a different entity ID does not get loaded
        $this->assertCount(1, $idpSet, "Unexpectedly got metadata for an alternate entity than that defined");
    }
}
