<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML;

use DOMElement;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use SAML2\DOMDocumentFactory;
use SimpleSAML\Test\SigningTestCase;
use SimpleSAML\XML\Signer;
use SimpleSAML\XML\Validator;

/**
 * Tests for SimpleSAML\XML\Validator.
 */
#[CoversClass(Validator::class)]
class ValidatorTest extends SigningTestCase
{
    /**
     */
    public function testValidatorMissingSignature(): void
    {
        $doc = DOMDocumentFactory::fromString('<node>value</node>');

        $this->expectException(Exception::class);
        new Validator($doc);
    }


    /**
     */
    public function testGetX509Certificate(): void
    {
        $doc = DOMDocumentFactory::fromString('<node>value</node>');

        /** @psalm-var DOMElement $node */
        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new DOMElement('signature_parent'));

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->loadCertificate($this->good_certificate_file, true);
        $signer->sign($node, $signature_parent);

        $validator = new Validator($doc, 'node');

        $result = $validator->getX509Certificate();

        // getX509Certificate returns a certificate with a newline
        $expected = $this->good_certificate . "\n";

        $this->assertEquals($result, $expected);
    }


    /**
     */
    public function testIsNodeValidatedSuccess(): void
    {
        $doc = DOMDocumentFactory::fromString('<node>value</node>');

        /** @psalm-var DOMElement $node */
        $node = $doc->getElementsByTagName('node')->item(0);

        $signature_parent = $doc->appendChild(new DOMElement('signature_parent'));

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->sign($node, $signature_parent);

        $validator = new Validator(
            $doc,
            'node',
            ['PEM' => $this->good_certificate],
        );

        $result = $validator->isNodeValidated($node);

        $this->assertTrue($result);
    }


    /**
     */
    public function testIsNodeValidatedFailure(): void
    {
        $doc = DOMDocumentFactory::fromString('<parent><node1>value1</node1><node2>value2</node2></parent>');

        /** @psalm-var DOMElement $node1 */
        $node1 = $doc->getElementsByTagName('node1')->item(0);

        /** @psalm-var DOMElement $node2 */
        $node2 = $doc->getElementsByTagName('node2')->item(0);

        $signature_parent = $doc->appendChild(new DOMElement('signature_parent'));

        $signer = new Signer([]);
        $signer->loadPrivateKey($this->good_private_key_file, null, true);
        $signer->sign($node1, $signature_parent);

        $validator = new Validator(
            $doc,
            'node1',
            ['PEM' => $this->good_certificate],
        );

        $result = $validator->isNodeValidated($node2);

        $this->assertFalse($result);
    }
}
