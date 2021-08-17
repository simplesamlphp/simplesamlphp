<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMText;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Utils;

/**
 * Tests for SimpleSAML\Utils\XML.
 *
 * @covers \SimpleSAML\Utils\XML
 */
class XMLTest extends TestCase
{
    private const FRAMEWORK = 'vendor/simplesamlphp/saml2/tests/resources/xml';

    /**
     * @test
     */
    public function testIsDomNodeOfTypeBasic(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $namespace_uri = 'ns';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = $xmlUtils->isDOMNodeOfType($element, $name, $namespace_uri);

        $this->assertTrue($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeMissingNamespace(): void
    {
        $xmlUtils = new Utils\XML();

        $this->expectException(InvalidArgumentException::class);
        $name = 'name';
        $namespace_uri = '@missing';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $xmlUtils->isDOMNodeOfType($element, $name, $namespace_uri);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeEmpty(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $namespace_uri = '';
        $element = new DOMElement($name);

        $res = $xmlUtils->isDOMNodeOfType($element, $name, $namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeShortcut(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $namespace_uri = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $short_namespace_uri = '@md';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = $xmlUtils->isDOMNodeOfType($element, $name, $short_namespace_uri);

        $this->assertTrue($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeIncorrectName(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $bad_name = 'bad name';
        $namespace_uri = 'ns';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = $xmlUtils->isDOMNodeOfType($element, $bad_name, $namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeIncorrectNamespace(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $namespace_uri = 'ns';
        $bad_namespace_uri = 'bad name';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = $xmlUtils->isDOMNodeOfType($element, $name, $bad_namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @test
     */
    public function testGetDomTextBasic(): void
    {
        $xmlUtils = new Utils\XML();

        $data = 'root value';
        $dom = new DOMDocument();
        $element = $dom->appendChild(new \DOMElement('root'));
        $element->appendChild(new DOMText($data));

        $res = $xmlUtils->getDOMText($element);
        $expected = $data;

        $this->assertEquals($expected, $res);
    }


    /**
     * @test
     */
    public function testGetDomTextMulti(): void
    {
        $xmlUtils = new Utils\XML();

        $data1 = 'root value 1';
        $data2 = 'root value 2';
        $dom = new DOMDocument();
        $element = $dom->appendChild(new DOMElement('root'));
        $element->appendChild(new DOMText($data1));
        $element->appendChild(new DOMText($data2));

        $res = $xmlUtils->getDOMText($element);
        $expected = $data1 . $data2 . $data1 . $data2;

        $this->assertEquals($expected, $res);
    }


    /**
     * @test
     */
    public function testGetDomTextIncorrectType(): void
    {
        $xmlUtils = new Utils\XML();

        $this->expectException(Error\Exception::class);
        $dom = new DOMDocument();
        $element = $dom->appendChild(new DOMElement('root'));
        $element->appendChild(new DOMComment(''));

        $xmlUtils->getDOMText($element);
    }


    /**
     * @test
     */
    public function testGetDomChildrenBasic(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $namespace_uri = 'ns';
        $dom = new DOMDocument();
        $element = new DOMElement($name, 'value', $namespace_uri);
        $dom->appendChild($element);

        $res = $xmlUtils->getDOMChildren($dom, $name, $namespace_uri);
        $expected = [$element];

        $this->assertEquals($expected, $res);
    }


    /**
     * @test
     */
    public function testGetDomChildrenIncorrectType(): void
    {
        $xmlUtils = new Utils\XML();

        $dom = new DOMDocument();
        $text = new DOMText('text');
        $comment = new DOMComment('comment');
        $dom->appendChild($text);
        $dom->appendChild($comment);

        $res = $xmlUtils->getDOMChildren($dom, 'name', 'ns');

        $this->assertEmpty($res);
    }


    /**
     * @test
     */
    public function testGetDomChildrenIncorrectName(): void
    {
        $xmlUtils = new Utils\XML();

        $name = 'name';
        $bad_name = 'bad name';
        $namespace_uri = 'ns';
        $dom = new DOMDocument();
        $element = new DOMElement($name, 'value', $namespace_uri);
        $dom->appendChild($element);

        $res = $xmlUtils->getDOMChildren($dom, $bad_name, $namespace_uri);

        $this->assertEmpty($res);
    }


    /**
     * @test
     */
    public function testFormatDomElementBasic(): void
    {
        $xmlUtils = new Utils\XML();

        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $dom->appendChild($root);
        $root->appendChild(new \DOMText('text'));

        $xmlUtils->formatDOMElement($root);
        $res = $dom->saveXML();
        $expected = <<<'NOWDOC'
<?xml version="1.0"?>
<root>text</root>

NOWDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @test
     */
    public function testFormatDomElementNested(): void
    {
        $xmlUtils = new Utils\XML();

        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $nested = new DOMElement('nested');
        $dom->appendChild($root);
        $root->appendChild($nested);
        $nested->appendChild(new DOMText('text'));

        $xmlUtils->formatDOMElement($root);
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
     * @test
     */
    public function testFormatDomElementIndentBase(): void
    {
        $xmlUtils = new Utils\XML();

        $indent_base = 'base';
        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $nested = new DOMElement('nested');
        $dom->appendChild($root);
        $root->appendChild($nested);
        $nested->appendChild(new DOMText('text'));

        $xmlUtils->formatDOMElement($root, $indent_base);
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
     * @test
     */
    public function testFormatDomElementTextAndChild(): void
    {
        $xmlUtils = new Utils\XML();

        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $dom->appendChild($root);
        $root->appendChild(new DOMText('text'));
        $root->appendChild(new DOMElement('child'));

        $xmlUtils->formatDOMElement($root);
        $res = $dom->saveXML();
        $expected = <<<HEREDOC
<?xml version="1.0"?>
<root>text<child/></root>

HEREDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @test
     */
    public function testFormatXmlStringBasic(): void
    {
        $xmlUtils = new Utils\XML();

        $xml = '<root><nested>text</nested></root>';

        $res = $xmlUtils->formatXMLString($xml);
        $expected = <<<'NOWDOC'
<root>
  <nested>text</nested>
</root>
NOWDOC;

        $this->assertEquals($expected, $res);
    }


    /**
     * @test
     */
    public function testFormatXmlStringMalformedXml(): void
    {
        $xmlUtils = new Utils\XML();

        $this->expectException(DOMException::class);
        $xml = '<root><nested>text';

        $xmlUtils->formatXMLString($xml);
    }


    /**
     * @test
     */
    public function testIsValidMalformedXml(): void
    {
        $xmlUtils = new Utils\XML();

        $xml = '<root><nested>text';

        $res = $xmlUtils->isValid($xml, 'unused');
        $this->assertIsString($res);

        $expected = 'Failed to parse XML string for schema validation';
        $this->assertStringContainsString($expected, $res);
    }


    /**
     */
    public function testIsValidMetadata(): void
    {
        $xmlUtils = new Utils\XML();

        $schema = 'saml-schema-metadata-2.0.xsd';
        $xml = file_get_contents(self::FRAMEWORK . '/metadata/valid-metadata-selfsigned.xml');

        $dom = new DOMDocument('1.0');
        $dom->loadXML($xml, LIBXML_NONET);

        $res = $xmlUtils->isValid($dom, $schema);
        $this->assertTrue($res === true);
    }

    /**
     */
    public function testCheckSAMLMessageInvalidType(): void
    {
        $xmlUtils = new Utils\XML();

        $this->expectException(InvalidArgumentException::class);
        $xmlUtils->checkSAMLMessage('<test></test>', 'blub');
    }
}
