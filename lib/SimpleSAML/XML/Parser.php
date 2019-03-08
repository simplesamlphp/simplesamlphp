<?php

/**
 * This file will help doing XPath queries in SAML 2 XML documents.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\XML;

class Parser
{
    /** @var \SimpleXMLElement|null */
    public $simplexml = null;

    /**
     * @param string $xml
     */
    public function __construct($xml)
    {
        $this->simplexml = new \SimpleXMLElement($xml);
        $this->simplexml->registerXPathNamespace('saml2', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $this->simplexml->registerXPathNamespace('saml2meta', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $this->simplexml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    }
    

    /**
     * @param \SimpleXMLElement $element
     * @return \SimpleSAML\XML\Parser
     */
    public static function fromSimpleXMLElement(\SimpleXMLElement $element)
    {
        // Traverse all existing namespaces in element
        $namespaces = $element->getNamespaces();
        foreach ($namespaces as $prefix => $ns) {
            $element[(($prefix === '') ? 'xmlns' : 'xmlns:'.$prefix)] = $ns;
        }
        
        /* Create a new parser with the xml document where the namespace definitions
         * are added.
         */
        $parser = new Parser($element->asXML());
        return $parser;
    }
    

    /**
     * @param string $xpath
     * @param string $defvalue
     * @throws \Exception
     * @return string
     */
    public function getValueDefault($xpath, $defvalue)
    {
        try {
            return $this->getValue($xpath, true);
        } catch (\Exception $e) {
            return $defvalue;
        }
    }
    

    /**
     * @param string $xpath
     * @param bool $required
     * @throws \Exception
     * @return string
     */
    public function getValue($xpath, $required = false)
    {
        $result = $this->simplexml->xpath($xpath);
        if (!is_array($result) || empty($result)) {
            if ($required) {
                throw new \Exception(
                    'Could not get value from XML document using the following XPath expression: '.$xpath
                );
            } else {
                return null;
            }
        }
        return (string) $result[0];
    }
    

    /**
     * @param array $xpath
     * @param bool $required
     * @throws \Exception
     * @return string|null
     */
    public function getValueAlternatives(array $xpath, $required = false)
    {
        foreach ($xpath as $x) {
            $seek = $this->getValue($x);
            if ($seek) {
                return $seek;
            }
        }
        if ($required) {
            throw new \Exception(
                'Could not get value from XML document using multiple alternative XPath expressions.'
            );
        } else {
            return null;
        }
    }
}
