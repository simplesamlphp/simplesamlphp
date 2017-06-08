<?php


/**
 * Class SimpleSAML_Metadata_SAMLBuilderTest
 */
class SimpleSAML_Metadata_SAMLBuilderTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test the requested attributes are valued correctly.
     */
    public function testAttributes()
    {
        $entityId = 'https://entity.example.com/id';

        //  test SP20 array parsing, no friendly name
        $set = 'saml20-sp-remote';
        $metadata = array(
            'entityid'     => $entityId,
            'name'         => array('en' => 'Test SP'),
            'metadata-set' => $set,
            'attributes'   => array(
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'urn:oid:0.9.2342.19200300.100.1.3',
                'urn:oid:2.5.4.3',
            ),
        );

        $samlBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");
        $this->assertEquals(1, $acs->length);
        $attributes = $acs->item(0)->getElementsByTagName("RequestedAttribute");
        $this->assertEquals(4, $attributes->length);
        for ($c = 0; $c < $attributes->length; $c++) {
            $curAttribute = $attributes->item($c);
            $this->assertTrue($curAttribute->hasAttribute("Name"));
            $this->assertFalse($curAttribute->hasAttribute("FriendlyName"));
            $this->assertEquals($metadata['attributes'][$c], $curAttribute->getAttribute("Name"));
        }

        // test SP20 array parsing, no friendly name
        $set = 'saml20-sp-remote';
        $metadata = array(
            'entityid'     => $entityId,
            'name'         => array('en' => 'Test SP'),
            'metadata-set' => $set,
            'attributes'   => array(
                'eduPersonTargetedID'    => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'eduPersonPrincipalName' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'eduPersonOrgDN'         => 'urn:oid:0.9.2342.19200300.100.1.3',
                'cn'                     => 'urn:oid:2.5.4.3',
            ),
        );

        $samlBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");
        $this->assertEquals(1, $acs->length);
        $attributes = $acs->item(0)->getElementsByTagName("RequestedAttribute");
        $this->assertEquals(4, $attributes->length);
        $keys = array_keys($metadata['attributes']);
        for ($c = 0; $c < $attributes->length; $c++) {
            $curAttribute = $attributes->item($c);
            $this->assertTrue($curAttribute->hasAttribute("Name"));
            $this->assertTrue($curAttribute->hasAttribute("FriendlyName"));
            $this->assertEquals($metadata['attributes'][$keys[$c]], $curAttribute->getAttribute("Name"));
            $this->assertEquals($keys[$c], $curAttribute->getAttribute("FriendlyName"));
        }

        //  test SP13 array parsing, no friendly name
        $set = 'shib13-sp-remote';
        $metadata = array(
            'entityid'     => $entityId,
            'name'         => array('en' => 'Test SP'),
            'metadata-set' => $set,
            'attributes'   => array(
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'urn:oid:0.9.2342.19200300.100.1.3',
                'urn:oid:2.5.4.3',
            ),
        );

        $samlBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");
        $this->assertEquals(1, $acs->length);
        $attributes = $acs->item(0)->getElementsByTagName("RequestedAttribute");
        $this->assertEquals(4, $attributes->length);
        for ($c = 0; $c < $attributes->length; $c++) {
            $curAttribute = $attributes->item($c);
            $this->assertTrue($curAttribute->hasAttribute("Name"));
            $this->assertFalse($curAttribute->hasAttribute("FriendlyName"));
            $this->assertEquals($metadata['attributes'][$c], $curAttribute->getAttribute("Name"));
        }

        // test SP20 array parsing, no friendly name
        $set = 'shib13-sp-remote';
        $metadata = array(
            'entityid'     => $entityId,
            'name'         => array('en' => 'Test SP'),
            'metadata-set' => $set,
            'attributes'   => array(
                'eduPersonTargetedID'    => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
                'eduPersonPrincipalName' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',
                'eduPersonOrgDN'         => 'urn:oid:0.9.2342.19200300.100.1.3',
                'cn'                     => 'urn:oid:2.5.4.3',
            ),
        );

        $samlBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);

        $spDesc = $samlBuilder->getEntityDescriptor();
        $acs = $spDesc->getElementsByTagName("AttributeConsumingService");
        $this->assertEquals(1, $acs->length);
        $attributes = $acs->item(0)->getElementsByTagName("RequestedAttribute");
        $this->assertEquals(4, $attributes->length);
        $keys = array_keys($metadata['attributes']);
        for ($c = 0; $c < $attributes->length; $c++) {
            $curAttribute = $attributes->item($c);
            $this->assertTrue($curAttribute->hasAttribute("Name"));
            $this->assertTrue($curAttribute->hasAttribute("FriendlyName"));
            $this->assertEquals($metadata['attributes'][$keys[$c]], $curAttribute->getAttribute("Name"));
            $this->assertEquals($keys[$c], $curAttribute->getAttribute("FriendlyName"));
        }
    }

    /**
     * Test the required protocolSupportEnumeration in AttributeAuthorityDescriptor
     */
    public function testProtocolSupportEnumeration()
    {
        $entityId = 'https://entity.example.com/id';
        $set = 'attributeauthority-remote';

        // without protocolSupportEnumeration fallback to default: urn:oasis:names:tc:SAML:2.0:protocol
        $metadata = array(
            'entityid'     => $entityId,
            'name'         => array('en' => 'Test AA'),
            'metadata-set' => $set,
            'AttributeService' =>
                array (
                    0 =>
                        array (
                            'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:SOAP',
                            'Location' => 'https://entity.example.com:8443/idp/profile/SAML2/SOAP/AttributeQuery',
                        ),
                ),
            );

        $samlBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);
        $entityDescriptorXml = $samlBuilder->getEntityDescriptorText();

        $this->assertRegExp(
            '/<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">/',
            $entityDescriptorXml
        );

        // explicit protocols
        $metadata['protocols'] =
            array(
                0 => 'urn:oasis:names:tc:SAML:1.1:protocol',
                1 => 'urn:oasis:names:tc:SAML:2.0:protocol',
            );
        $samlBuilder = new SimpleSAML_Metadata_SAMLBuilder($entityId);
        $samlBuilder->addMetadata($set, $metadata);
        $entityDescriptorXml = $samlBuilder->getEntityDescriptorText();

        $this->assertRegExp(
            '/<md:AttributeAuthorityDescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:1.1:protocol urn:oasis:names:tc:SAML:2.0:protocol">/',
            $entityDescriptorXml
        );
    }
}
