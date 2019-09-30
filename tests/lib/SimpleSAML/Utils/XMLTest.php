<?php

namespace SimpleSAML\Test\Utils;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Utils\XML;

/**
 * Tests for SimpleSAML\Utils\XML.
 */
class XMLTest extends TestCase
{
    /**
     * @covers \SimpleSAML\Utils\XML::isDOMNodeOfType
     * @test
     * @return void
     */
    public function testIsDomNodeOfTypeBasic()
    {
        $name = 'name';
        $namespace_uri = 'ns';
        $element = new \DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $name, $namespace_uri);

        $this->assertTrue($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isDOMNodeOfType
     * @test
     * @return void
     */
    public function testIsDomNodeOfTypeMissingNamespace()
    {
        $this->expectException(\InvalidArgumentException::class);
        $name = 'name';
        $namespace_uri = '@missing';
        $element = new \DOMElement($name, 'value', $namespace_uri);

        XML::isDOMNodeOfType($element, $name, $namespace_uri);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isDOMNodeOfType
     * @test
     * @return void
     */
    public function testIsDomNodeOfTypeEmpty()
    {
        $name = 'name';
        $namespace_uri = '';
        $element = new \DOMElement($name);

        $res = XML::isDOMNodeOfType($element, $name, $namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isDOMNodeOfType
     * @test
     * @return void
     */
    public function testIsDomNodeOfTypeShortcut()
    {
        $name = 'name';
        $namespace_uri = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $short_namespace_uri = '@md';
        $element = new \DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $name, $short_namespace_uri);

        $this->assertTrue($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isDOMNodeOfType
     * @test
     * @return void
     */
    public function testIsDomNodeOfTypeIncorrectName()
    {
        $name = 'name';
        $bad_name = 'bad name';
        $namespace_uri = 'ns';
        $element = new \DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $bad_name, $namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isDOMNodeOfType
     * @test
     * @return void
     */
    public function testIsDomNodeOfTypeIncorrectNamespace()
    {
        $name = 'name';
        $namespace_uri = 'ns';
        $bad_namespace_uri = 'bad name';
        $element = new \DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $name, $bad_namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::getDOMText
     * @test
     * @return void
     */
    public function testGetDomTextBasic()
    {
        $data = 'root value';
        $dom = new \DOMDocument();
        $element = $dom->appendChild(new \DOMElement('root'));
        $element->appendChild(new \DOMText($data));

        $res = XML::getDOMText($element);
        $expected = $data;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::getDOMText
     * @test
     * @return void
     */
    public function testGetDomTextMulti()
    {
        $data1 = 'root value 1';
        $data2 = 'root value 2';
        $dom = new \DOMDocument();
        $element = $dom->appendChild(new \DOMElement('root'));
        $element->appendChild(new \DOMText($data1));
        $element->appendChild(new \DOMText($data2));

        $res = XML::getDOMText($element);
        $expected = $data1 . $data2 . $data1 . $data2;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::getDOMText
     * @test
     * @return void
     */
    public function testGetDomTextIncorrectType()
    {
        $this->expectException(\SimpleSAML\Error\Exception::class);
        $dom = new \DOMDocument();
        $element = $dom->appendChild(new \DOMElement('root'));
        $element->appendChild(new \DOMComment(''));

        XML::getDOMText($element);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::getDOMChildren
     * @test
     * @return void
     */
    public function testGetDomChildrenBasic()
    {
        $name = 'name';
        $namespace_uri = 'ns';
        $dom = new \DOMDocument();
        $element = new \DOMElement($name, 'value', $namespace_uri);
        $dom->appendChild($element);

        $res = XML::getDOMChildren($dom, $name, $namespace_uri);
        $expected = [$element];

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::getDOMChildren
     * @test
     * @return void
     */
    public function testGetDomChildrenIncorrectType()
    {
        $dom = new \DOMDocument();
        $text = new \DOMText('text');
        $comment = new \DOMComment('comment');
        $dom->appendChild($text);
        $dom->appendChild($comment);

        $res = XML::getDOMChildren($dom, 'name', 'ns');

        $this->assertEmpty($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::getDOMChildren
     * @test
     * @return void
     */
    public function testGetDomChildrenIncorrectName()
    {
        $name = 'name';
        $bad_name = 'bad name';
        $namespace_uri = 'ns';
        $dom = new \DOMDocument();
        $element = new \DOMElement($name, 'value', $namespace_uri);
        $dom->appendChild($element);

        $res = XML::getDOMChildren($dom, $bad_name, $namespace_uri);

        $this->assertEmpty($res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::formatDOMElement
     * @test
     * @return void
     */
    public function testFormatDomElementBasic()
    {
        $dom = new \DOMDocument();
        $root = new \DOMElement('root');
        $dom->appendChild($root);
        $root->appendChild(new \DOMText('text'));

        XML::formatDOMElement($root);
        $res = $dom->saveXML();
        $expected = <<<'NOWDOC'
<?xml version="1.0"?>
<root>text</root>

NOWDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::formatDOMElement
     * @test
     * @return void
     */
    public function testFormatDomElementNested()
    {
        $dom = new \DOMDocument();
        $root = new \DOMElement('root');
        $nested = new \DOMElement('nested');
        $dom->appendChild($root);
        $root->appendChild($nested);
        $nested->appendChild(new \DOMText('text'));

        XML::formatDOMElement($root);
        $res = $dom->saveXML();
        $expected = <<<'NOWDOC'
<?xml version="1.0"?>
<root>
  <nested>text</nested>
</root>

NOWDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::formatDOMElement
     * @test
     * @return void
     */
    public function testFormatDomElementIndentBase()
    {
        $indent_base = 'base';
        $dom = new \DOMDocument();
        $root = new \DOMElement('root');
        $nested = new \DOMElement('nested');
        $dom->appendChild($root);
        $root->appendChild($nested);
        $nested->appendChild(new \DOMText('text'));

        XML::formatDOMElement($root, $indent_base);
        $res = $dom->saveXML();
        $expected = <<<HEREDOC
<?xml version="1.0"?>
<root>
$indent_base  <nested>text</nested>
$indent_base</root>

HEREDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::formatDOMElement
     * @test
     * @return void
     */
    public function testFormatDomElementTextAndChild()
    {
        $dom = new \DOMDocument();
        $root = new \DOMElement('root');
        $dom->appendChild($root);
        $root->appendChild(new \DOMText('text'));
        $root->appendChild(new \DOMElement('child'));

        XML::formatDOMElement($root);
        $res = $dom->saveXML();
        $expected = <<<HEREDOC
<?xml version="1.0"?>
<root>text<child/></root>

HEREDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::formatXMLString
     * @test
     * @return void
     */
    public function testFormatXmlStringBasic()
    {
        $xml = '<root><nested>text</nested></root>';

        $res = XML::formatXMLString($xml);
        $expected = <<<'NOWDOC'
<root>
  <nested>text</nested>
</root>
NOWDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::formatXMLString
     * @test
     * @return void
     */
    public function testFormatXmlStringMalformedXml()
    {
        $this->expectException(\DOMException::class);
        $xml = '<root><nested>text';

        XML::formatXMLString($xml);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isValid
     * @test
     * @return void
     */
    public function testIsValidMalformedXml()
    {
        $xml = '<root><nested>text';

        $res = XML::isValid($xml, 'unused');
        $expected = 'Failed to parse XML string for schema validation';

        $this->assertContains($expected, $res);
    }


    /**
     * @covers \SimpleSAML\Utils\XML::isValid
     * @return void
     */
    public function testIsValidMetadata()
    {
        $schema = 'saml-schema-metadata-2.0.xsd';

        $xml = <<<'XMLDOC'
<?xml version="1.0"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" entityID="https://idp.example.org/saml2/idp/metadata.php" ID="_e3369c45cf941d5ace90fbf936604c2409fd8bf1ca9bec5607c30e18169fd73d"><ds:Signature>
  <ds:SignedInfo><ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
    <ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
  <ds:Reference URI="#_e3369c45cf941d5ace90fbf936604c2409fd8bf1ca9bec5607c30e18169fd73d"><ds:Transforms><ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/><ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/></ds:Transforms><ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/><ds:DigestValue>dM5kF0HTkW9fnJOS77yNgTAwBj4=</ds:DigestValue></ds:Reference></ds:SignedInfo><ds:SignatureValue>i5u6pKkDLYEX9KAV7Z0dd/jEgJ6KoOePF2NcrkIcz492OxDImS9il43Y0W3KRZPdq9fq6BQKzNifPhCN8wk8VhoceM/1Am3Nxv8d6hx+1IOeVmJT5kBMcRO8GFee6CnbwtsMH1TkU37vXt7isf237Pzi2hxDCVaKOPbNmm6lTS8=</ds:SignatureValue>
<ds:KeyInfo><ds:X509Data><ds:X509Certificate>MIIDEjCCAnugAwIBAgIJANIdJROXilWcMA0GCSqGSIb3DQEBCwUAMIGgMQswCQYDVQQGEwJVUzELMAkGA1UECAwCSEkxETAPBgNVBAcMCEhvbm9sdWx1MRYwFAYDVQQKDA1TaW1wbGVTQU1McGhwMRQwEgYDVQQLDAtEZXZlbG9wbWVudDEfMB0GA1UEAwwWc2VsZnNpZ25lZC5leGFtcGxlLm9yZzEiMCAGCSqGSIb3DQEJARYTbm9yZXBseUBleGFtcGxlLm9yZzAgFw0xOTA3MTgxNjMwMTZaGA8yMTE5MDYyNDE2MzAxNlowgaAxCzAJBgNVBAYTAlVTMQswCQYDVQQIDAJISTERMA8GA1UEBwwISG9ub2x1bHUxFjAUBgNVBAoMDVNpbXBsZVNBTUxwaHAxFDASBgNVBAsMC0RldmVsb3BtZW50MR8wHQYDVQQDDBZzZWxmc2lnbmVkLmV4YW1wbGUub3JnMSIwIAYJKoZIhvcNAQkBFhNub3JlcGx5QGV4YW1wbGUub3JnMIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwjX17mpEFt9pdka/jnseuSNosBqTRH6Pw9sQkuTH19xrRMdlGigfgdOQqvwUN1LcOF+zvFNQnZ3yTqpRxjGGcTtExKPeRI5Avef6aFO6AiDCKt831b95pnZuRsC0XweojS1xkEyiplzFZ0UjGTEG06QYvPYXJwDrTqSZuTOZGAQIDAQABo1AwTjAdBgNVHQ4EFgQUdWqf6TRDUhnJNM7vZB60oZ6bEgIwHwYDVR0jBBgwFoAUdWqf6TRDUhnJNM7vZB60oZ6bEgIwDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQsFAAOBgQBS+7YLLJJiDJ7OIg4PQb1bK2JpSNAimT1ZuhcgeeApM81OTh9AAS5OchRcjYmf4u1nJmfXk5RnJUHpFGGzjXoTtCrdwTUFV+u0WEkM+bB1nfuQHaHqr1UC6H956keHpedQ0N/9+0/hMoqwERQiaQLfoH9tIHv83Lq3iTc8uuJ/XA==</ds:X509Certificate></ds:X509Data></ds:KeyInfo></ds:Signature>
  <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:KeyDescriptor use="signing">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>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</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:KeyDescriptor use="encryption">
      <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>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</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://idp.example.org/saml2/idp/SingleLogoutService.php"/>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:2.0:nameid-format:transient</md:NameIDFormat>
    <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://idp.example.org/saml2/idp/SSOService.php"/>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>
XMLDOC;

        $dom = new \DOMDocument('1.0');
        $dom->loadXML($xml, LIBXML_NONET);

        $res = XML::isValid($dom, $schema);
        $this->assertTrue($res === true);
    }

    /**
     * @covers \SimpleSAML\Utils\XML::checkSAMLMessage()
     * @return void
     */
    public function testCheckSAMLMessageInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        XML::checkSAMLMessage('<test></test>', 'blub');
    }
}
