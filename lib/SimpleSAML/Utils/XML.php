<?php

/**
 * Utility class for XML and DOM manipulation.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\Utils;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use SAML2\DOMDocumentFactory;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\XML\Errors;

class XML
{
    /**
     * This function performs some sanity checks on XML documents, and optionally validates them against their schema
     * if the 'validatexml' debugging option is enabled. A warning will be printed to the log if validation fails.
     *
     * @param string $message The SAML document we want to check.
     * @param string $type The type of document. Can be one of:
     * - 'saml20'
     * - 'saml-meta'
     *
     * @throws \InvalidArgumentException If $message is not a string or $type is not a string containing one of the
     *     values allowed.
     * @throws \SimpleSAML\Error\Exception If $message contains a doctype declaration.
     *
     *
     */
    public function checkSAMLMessage(string $message, string $type): void
    {
        $allowed_types = ['saml20', 'saml-meta'];
        if (!in_array($type, $allowed_types, true)) {
            throw new \InvalidArgumentException('Invalid input parameters.');
        }

        // a SAML message should not contain a doctype-declaration
        if (strpos($message, '<!DOCTYPE') !== false) {
            throw new Error\Exception('XML contained a doctype declaration.');
        }

        // see if debugging is enabled for XML validation
        $debug = Configuration::getInstance()->getOptionalArray('debug', ['validatexml' => false]);

        if (
            !(
                in_array('validatexml', $debug, true)
                || (array_key_exists('validatexml', $debug) && ($debug['validatexml'] === true))
            )
        ) {
            // XML validation is disabled
            return;
        }

        $result = true;
        switch ($type) {
            case 'saml20':
                $result = $this->isValid($message, 'saml-schema-protocol-2.0.xsd');
                break;
            case 'saml-meta':
                $result = $this->isValid($message, 'saml-schema-metadata-2.0.xsd');
        }
        if (is_string($result)) {
            Logger::warning($result);
        }
    }


    /**
     * Helper function to log SAML messages that we send or receive.
     *
     * @param string|\DOMElement $message The message, as an string containing the XML or an XML element.
     * @param string             $type Whether this message is sent or received, encrypted or decrypted. The following
     *     values are supported:
     *      - 'in': for messages received.
     *      - 'out': for outgoing messages.
     *      - 'decrypt': for decrypted messages.
     *      - 'encrypt': for encrypted messages.
     *
     * @throws \InvalidArgumentException If $type is not a string or $message is neither a string nor a \DOMElement.
     *
     *
     */
    public function debugSAMLMessage($message, string $type): void
    {
        if (!(is_string($message) || $message instanceof DOMElement)) {
            throw new \InvalidArgumentException('Invalid input parameters.');
        }

        // see if debugging is enabled for SAML messages
        $debug = Configuration::getInstance()->getOptionalArray('debug', ['saml' => false]);

        if (
            !(
                in_array('saml', $debug, true) || // implicitly enabled
                (array_key_exists('saml', $debug) && $debug['saml'] === true) // explicitly enabled
            )
        ) {
            // debugging messages is disabled
            return;
        }

        if ($message instanceof DOMElement) {
            $message = $message->ownerDocument->saveXML($message);
        }

        switch ($type) {
            case 'in':
                Logger::debug('Received message:');
                break;
            case 'out':
                Logger::debug('Sending message:');
                break;
            case 'decrypt':
                Logger::debug('Decrypted message:');
                break;
            case 'encrypt':
                Logger::debug('Encrypted message:');
                break;
            default:
                Assert::true(false);
        }

        $str = $this->formatXMLString($message);
        foreach (explode("\n", $str) as $line) {
            Logger::debug($line);
        }
    }


    /**
     * Format a DOM element.
     *
     * This function takes in a DOM element, and inserts whitespace to make it more readable. Note that whitespace
     * added previously will be removed.
     *
     * @param \DOMNode $root The root element which should be formatted.
     * @param string      $indentBase The indentation this element should be assumed to have. Defaults to an empty
     *     string.
     *
     * @throws \InvalidArgumentException If $root is not a DOMElement or $indentBase is not a string.
     *
     *
     */
    public function formatDOMElement(DOMNode $root, string $indentBase = ''): void
    {
        // check what this element contains
        $fullText = ''; // all text in this element
        $textNodes = []; // text nodes which should be deleted
        $childNodes = []; // other child nodes
        for ($i = 0; $i < $root->childNodes->length; $i++) {
            /** @var \DOMNode $child */
            $child = $root->childNodes->item($i);

            if ($child instanceof DOMText) {
                $textNodes[] = $child;
                $fullText .= $child->wholeText;
            } elseif ($child instanceof DOMComment || $child instanceof DOMElement) {
                $childNodes[] = $child;
            } else {
                // unknown node type. We don't know how to format this
                return;
            }
        }

        $fullText = trim($fullText);
        if (strlen($fullText) > 0) {
            // we contain textelf
            $hasText = true;
        } else {
            $hasText = false;
        }

        $hasChildNode = (count($childNodes) > 0);

        if ($hasText && $hasChildNode) {
            // element contains both text and child nodes - we don't know how to format this one
            return;
        }

        // remove text nodes
        foreach ($textNodes as $node) {
            $root->removeChild($node);
        }

        if ($hasText) {
            // only text - add a single text node to the element with the full text
            $root->appendChild(new DOMText($fullText));
            return;
        }

        if (!$hasChildNode) {
            // empty node. Nothing to do
            return;
        }

        /* Element contains only child nodes - add indentation before each one, and
         * format child elements.
         */
        $childIndentation = $indentBase . '  ';
        foreach ($childNodes as $node) {
            // add indentation before node
            $root->insertBefore(new DOMText("\n" . $childIndentation), $node);

            // format child elements
            if ($node instanceof \DOMElement) {
                $this->formatDOMElement($node, $childIndentation);
            }
        }

        // add indentation before closing tag
        $root->appendChild(new DOMText("\n" . $indentBase));
    }


    /**
     * Format an XML string.
     *
     * This function formats an XML string using the formatDOMElement() function.
     *
     * @param string $xml An XML string which should be formatted.
     * @param string $indentBase Optional indentation which should be applied to all the output. Optional, defaults
     * to ''.
     *
     * @return string The formatted string.
     * @throws \InvalidArgumentException If the parameters are not strings.
     * @throws \DOMException If the input does not parse correctly as an XML string.
     *
     */
    public function formatXMLString(string $xml, string $indentBase = ''): string
    {
        try {
            $doc = DOMDocumentFactory::fromString($xml);
        } catch (\Exception $e) {
            throw new \DOMException('Error parsing XML string.');
        }

        $root = $doc->firstChild;
        Assert::notNull($root);
        $this->formatDOMElement($root, $indentBase);

        return $doc->saveXML($root);
    }


    /**
     * This function checks if the DOMElement has the correct localName and namespaceURI.
     *
     * We also define the following shortcuts for namespaces:
     * - '@ds':      'http://www.w3.org/2000/09/xmldsig#'
     * - '@md':      'urn:oasis:names:tc:SAML:2.0:metadata'
     * - '@saml1md': 'urn:oasis:names:tc:SAML:profiles:v1metadata'
     * - '@saml2':   'urn:oasis:names:tc:SAML:2.0:assertion'
     * - '@saml2p':  'urn:oasis:names:tc:SAML:2.0:protocol'
     *
     * @param \DOMNode $element The element we should check.
     * @param string   $name The local name the element should have.
     * @param string   $nsURI The namespaceURI the element should have.
     *
     * @return boolean True if both namespace and local name matches, false otherwise.
     * @throws \InvalidArgumentException If the namespace shortcut is unknown.
     *
     */
    public function isDOMNodeOfType(DOMNode $element, string $name, string $nsURI): bool
    {
        if (strlen($nsURI) === 0) {
            // most likely a comment-node
            return false;
        }

        // check if the namespace is a shortcut, and expand it if it is
        if ($nsURI[0] === '@') {
            // the defined shortcuts
            $shortcuts = [
                '@ds'      => 'http://www.w3.org/2000/09/xmldsig#',
                '@md'      => 'urn:oasis:names:tc:SAML:2.0:metadata',
                '@saml2'   => 'urn:oasis:names:tc:SAML:2.0:assertion',
                '@saml2p'  => 'urn:oasis:names:tc:SAML:2.0:protocol'
            ];

            // check if it is a valid shortcut
            if (!array_key_exists($nsURI, $shortcuts)) {
                throw new \InvalidArgumentException('Unknown namespace shortcut: ' . $nsURI);
            }

            // expand the shortcut
            $nsURI = $shortcuts[$nsURI];
        }
        if ($element->localName !== $name) {
            return false;
        }
        if ($element->namespaceURI !== $nsURI) {
            return false;
        }
        return true;
    }


    /**
     * This function attempts to validate an XML string against the specified schema. It will parse the string into a
     * DOM document and validate this document against the schema.
     *
     * Note that this function returns values that are evaluated as a logical true, both when validation works and when
     * it doesn't. Please use strict comparisons to check the values returned.
     *
     * @param string|\DOMDocument $xml The XML string or document which should be validated.
     * @param string              $schema The filename of the schema that should be used to validate the document.
     *
     * @return bool|string Returns a string with errors found if validation fails. True if validation passes ok.
     * @throws \InvalidArgumentException If $schema is not a string, or $xml is neither a string nor a \DOMDocument.
     *
     */
    public function isValid($xml, string $schema)
    {
        if (!is_string($xml) && !($xml instanceof DOMDocument)) {
            throw new \InvalidArgumentException('Invalid input parameters.');
        }

        Errors::begin();

        if ($xml instanceof DOMDocument) {
            $dom = $xml;
            $res = true;
        } else {
            try {
                $dom = DOMDocumentFactory::fromString($xml);
                $res = true;
            } catch (\Exception $e) {
                $res = false;
            }
        }

        if ($res === true) {
            $config = Configuration::getInstance();
            /** @var string $schemaPath */
            $schemaPath = $config->resolvePath('schemas');
            $schemaFile = $schemaPath . '/' . $schema;

            libxml_set_external_entity_loader(
                /**
                 * @param string|null $public
                 * @param string $system
                 * @param array $context
                 * @return string|null
                 */
                function (string $public = null, string $system, /** @scrutinizer ignore-unused */ array $context) {
                    if (filter_var($system, FILTER_VALIDATE_URL) === $system) {
                        return null;
                    }
                    return $system;
                }
            );

            /** @psalm-suppress PossiblyUndefinedVariable */
            $res = $dom->schemaValidate($schemaFile);
            if ($res) {
                Errors::end();
                return true;
            }

            $errorText = "Schema validation failed on XML string:\n";
        } else {
            $errorText = "Failed to parse XML string for schema validation:\n";
        }

        $errors = Errors::end();
        $errorText .= Errors::formatErrors($errors);

        return $errorText;
    }
}
