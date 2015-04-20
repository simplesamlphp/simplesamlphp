<?php
/**
 * Utility class for XML and DOM manipulation.
 *
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Utils;


class XML
{

    /**
     * Format a DOM element.
     *
     * This function takes in a DOM element, and inserts whitespace to make it more readable. Note that whitespace
     * added previously will be removed.
     *
     * @param \DOMElement $root The root element which should be formatted.
     * @param string      $indentBase The indentation this element should be assumed to have. Defaults to an empty
     *     string.
     *
     * @throws \SimpleSAML_Error_Exception If $root is not a DOMElement or $indentBase is not a string.
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function formatDOMElement(\DOMElement $root, $indentBase = '')
    {
        if (!is_string($indentBase)) {
            throw new \SimpleSAML_Error_Exception('Invalid input parameters');
        }

        // check what this element contains
        $fullText = ''; // all text in this element
        $textNodes = array(); // text nodes which should be deleted
        $childNodes = array(); // other child nodes
        for ($i = 0; $i < $root->childNodes->length; $i++) {
            $child = $root->childNodes->item($i);

            if ($child instanceof \DOMText) {
                $textNodes[] = $child;
                $fullText .= $child->wholeText;
            } elseif ($child instanceof \DOMComment || $child instanceof \DOMElement) {
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
            $root->appendChild(new \DOMText($fullText));
            return;
        }

        if (!$hasChildNode) {
            // empty node. Nothing to do
            return;
        }

        /* Element contains only child nodes - add indentation before each one, and
         * format child elements.
         */
        $childIndentation = $indentBase.'  ';
        foreach ($childNodes as $node) {
            // add indentation before node
            $root->insertBefore(new \DOMText("\n".$childIndentation), $node);

            // format child elements
            if ($node instanceof \DOMElement) {
                self::formatDOMElement($node, $childIndentation);
            }
        }

        // add indentation before closing tag
        $root->appendChild(new \DOMText("\n".$indentBase));
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
     * @throws \SimpleSAML_Error_Exception If the input does not parse correctly as an XML string or parameters are not
     *     strings.
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function formatXMLString($xml, $indentBase = '')
    {
        if (!is_string($xml) || !is_string($indentBase)) {
            throw new \SimpleSAML_Error_Exception('Invalid input parameters');
        }

        $doc = new \DOMDocument();
        if (!$doc->loadXML($xml)) {
            throw new \SimpleSAML_Error_Exception('Error parsing XML string.');
        }

        $root = $doc->firstChild;
        self::formatDOMElement($root, $indentBase);

        return $doc->saveXML($root);
    }


    /**
     * This function finds direct descendants of a DOM element with the specified
     * localName and namespace. They are returned in an array.
     *
     * This function accepts the same shortcuts for namespaces as the isDOMElementOfType function.
     *
     * @param \DOMElement $element The element we should look in.
     * @param string      $localName The name the element should have.
     * @param string      $namespaceURI The namespace the element should have.
     *
     * @return array  Array with the matching elements in the order they are found. An empty array is
     *         returned if no elements match.
     */
    public static function getDOMChildren(\DOMElement $element, $localName, $namespaceURI)
    {
        assert('is_string($localName)');
        assert('is_string($namespaceURI)');

        $ret = array();

        for ($i = 0; $i < $element->childNodes->length; $i++) {
            $child = $element->childNodes->item($i);

            // skip text nodes and comment elements
            if ($child instanceof \DOMText || $child instanceof \DOMComment) {
                continue;
            }

            if (self::isDOMElementOfType($child, $localName, $namespaceURI) === true) {
                $ret[] = $child;
            }
        }

        return $ret;
    }


    /**
     * This function extracts the text from DOMElements which should contain only text content.
     *
     * @param \DOMElement $element The element we should extract text from.
     *
     * @return string The text content of the element.
     * @throws \SimpleSAML_Error_Exception If the element contains a non-text child node.
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function getDOMText(\DOMElement $element)
    {
        if (!($element instanceof \DOMElement)) {
            throw new \SimpleSAML_Error_Exception('Invalid input parameters');
        }

        $txt = '';

        for ($i = 0; $i < $element->childNodes->length; $i++) {
            $child = $element->childNodes->item($i);
            if (!($child instanceof \DOMText)) {
                throw new \SimpleSAML_Error_Exception($element->localName.' contained a non-text child node.');
            }

            $txt .= $child->wholeText;
        }

        $txt = trim($txt);
        return $txt;
    }


    /**
     * This function checks if the DOMElement has the correct localName and namespaceURI.
     *
     * We also define the following shortcuts for namespaces:
     * - '@ds':      'http://www.w3.org/2000/09/xmldsig#'
     * - '@md':      'urn:oasis:names:tc:SAML:2.0:metadata'
     * - '@saml1':   'urn:oasis:names:tc:SAML:1.0:assertion'
     * - '@saml1md': 'urn:oasis:names:tc:SAML:profiles:v1metadata'
     * - '@saml1p':  'urn:oasis:names:tc:SAML:1.0:protocol'
     * - '@saml2':   'urn:oasis:names:tc:SAML:2.0:assertion'
     * - '@saml2p':  'urn:oasis:names:tc:SAML:2.0:protocol'
     *
     * @param \DOMNode $element The element we should check.
     * @param string   $name The local name the element should have.
     * @param string   $nsURI The namespaceURI the element should have.
     *
     * @return boolean True if both namespace and local name matches, false otherwise.
     * @throws \SimpleSAML_Error_Exception If the namespace shortcut is unknown.
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function isDOMElementOfType(\DOMNode $element, $name, $nsURI)
    {
        if (!($element instanceof \DOMElement) || !is_string($name) || !is_string($nsURI) || strlen($nsURI) === 0) {
            // most likely a comment-node
            return false;
        }

        // check if the namespace is a shortcut, and expand it if it is
        if ($nsURI[0] === '@') {
            // the defined shortcuts
            $shortcuts = array(
                '@ds'      => 'http://www.w3.org/2000/09/xmldsig#',
                '@md'      => 'urn:oasis:names:tc:SAML:2.0:metadata',
                '@saml1'   => 'urn:oasis:names:tc:SAML:1.0:assertion',
                '@saml1md' => 'urn:oasis:names:tc:SAML:profiles:v1metadata',
                '@saml1p'  => 'urn:oasis:names:tc:SAML:1.0:protocol',
                '@saml2'   => 'urn:oasis:names:tc:SAML:2.0:assertion',
                '@saml2p'  => 'urn:oasis:names:tc:SAML:2.0:protocol',
                '@shibmd'  => 'urn:mace:shibboleth:metadata:1.0',
            );

            // check if it is a valid shortcut
            if (!array_key_exists($nsURI, $shortcuts)) {
                throw new \SimpleSAML_Error_Exception('Unknown namespace shortcut: '.$nsURI);
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
}
