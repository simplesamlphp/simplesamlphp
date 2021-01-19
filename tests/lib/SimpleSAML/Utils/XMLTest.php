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
use SimpleSAML\Utils\XML;

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
        $name = 'name';
        $namespace_uri = 'ns';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $name, $namespace_uri);

        $this->assertTrue($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeMissingNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $name = 'name';
        $namespace_uri = '@missing';
        $element = new DOMElement($name, 'value', $namespace_uri);

        XML::isDOMNodeOfType($element, $name, $namespace_uri);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeEmpty(): void
    {
        $name = 'name';
        $namespace_uri = '';
        $element = new DOMElement($name);

        $res = XML::isDOMNodeOfType($element, $name, $namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeShortcut(): void
    {
        $name = 'name';
        $namespace_uri = 'urn:oasis:names:tc:SAML:2.0:metadata';
        $short_namespace_uri = '@md';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $name, $short_namespace_uri);

        $this->assertTrue($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeIncorrectName(): void
    {
        $name = 'name';
        $bad_name = 'bad name';
        $namespace_uri = 'ns';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $bad_name, $namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @test
     */
    public function testIsDomNodeOfTypeIncorrectNamespace(): void
    {
        $name = 'name';
        $namespace_uri = 'ns';
        $bad_namespace_uri = 'bad name';
        $element = new DOMElement($name, 'value', $namespace_uri);

        $res = XML::isDOMNodeOfType($element, $name, $bad_namespace_uri);

        $this->assertFalse($res);
    }


    /**
     * @test
     */
    public function testFormatDomElementBasic(): void
    {
        $dom = new DOMDocument();
        $root = new DOMElement('root');
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
     * @test
     */
    public function testFormatDomElementNested(): void
    {
        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $nested = new DOMElement('nested');
        $dom->appendChild($root);
        $root->appendChild($nested);
        $nested->appendChild(new DOMText('text'));

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
     * @test
     */
    public function testFormatDomElementIndentBase(): void
    {
        $indent_base = 'base';
        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $nested = new DOMElement('nested');
        $dom->appendChild($root);
        $root->appendChild($nested);
        $nested->appendChild(new DOMText('text'));

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
     * @test
     */
    public function testFormatDomElementTextAndChild(): void
    {
        $dom = new DOMDocument();
        $root = new DOMElement('root');
        $dom->appendChild($root);
        $root->appendChild(new DOMText('text'));
        $root->appendChild(new DOMElement('child'));

        XML::formatDOMElement($root);
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
     * @test
     */
    public function testFormatXmlStringMalformedXml(): void
    {
        $this->expectException(DOMException::class);
        $xml = '<root><nested>text';

        XML::formatXMLString($xml);
    }


    /**
     * @test
     */
    public function testIsValidMalformedXml(): void
    {
        $xml = '<root><nested>text';

        $res = XML::isValid($xml, 'unused');
        $this->assertIsString($res);

        $expected = 'Failed to parse XML string for schema validation';
        $this->assertStringContainsString($expected, $res);
    }


    /**
     */
    public function testIsValidMetadata(): void
    {
        $schema = 'saml-schema-metadata-2.0.xsd';
        $xml = file_get_contents(self::FRAMEWORK . '/metadata/valid-metadata-selfsigned.xml');

        $dom = new DOMDocument('1.0');
        $dom->loadXML($xml, LIBXML_NONET);

        $res = XML::isValid($dom, $schema);
        $this->assertTrue($res === true);
    }

    /**
     */
    public function testCheckSAMLMessageInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        XML::checkSAMLMessage('<test></test>', 'blub');
    }
}
