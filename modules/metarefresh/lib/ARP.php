<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

class sspmod_metarefresh_ARP
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var
     */
    private $suffix;

	/**
	 * Constructor
	 *
	 * @param array $metadata
     * @param string $attributemap
     * @param string $prefix
     * @param string $suffix
	 */
    public function __construct($metadata, $attributemap, $prefix, $suffix)
    {
        $this->metadata = $metadata;
        $this->prefix = $prefix;
        $this->suffix = $suffix;

        if (isset($attributemap)) {
            $this->loadAttributeMap($attributemap);
        }
    }
	
    /**
     * @param string $attributemap
     *
     * @return void
     */
    private function loadAttributeMap($attributemap)
    {
        $config = SimpleSAML_Configuration::getInstance();
        include($config->getPathValue('attributemap', 'attributemap/') . $attributemap . '.php');
        // Note that $attributemap was a string before the call to include() and is now an array!
        $this->attributes = $attributemap;
	}

    /**
     * @param string $name
     *
     * @return string
     */
    private function surround($name)
    {
        $ret = '';
        if (!empty($this->prefix)) {
            $ret .= $this->prefix;
        }
        $ret .= $name;
        if (!empty($this->suffix)) {
            $ret .= $this->suffix;
        }
        return $ret;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getAttributeID($name)
    {
        if (empty($this->attributes)) {
            return $this->surround($name);
        }
        if (array_key_exists($name, $this->attributes)) {
            return $this->surround($this->attributes[$name]);
        }
        return $this->surround($name);
    }

    /**
     * @return string
     */
    public function getXML()
    {
        $xml = <<<MSG
        <?xml version="1.0" encoding="UTF-8"?>
        <AttributeFilterPolicyGroup id="urn:mace:funet.fi:haka:kalmar" xmlns="urn:mace:shibboleth:2.0:afp"
    xmlns:basic="urn:mace:shibboleth:2.0:afp:mf:basic" xmlns:saml="urn:mace:shibboleth:2.0:afp:mf:saml"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="urn:mace:shibboleth:2.0:afp classpath:/schema/shibboleth-2.0-afp.xsd
                        urn:mace:shibboleth:2.0:afp:mf:basic classpath:/schema/shibboleth-2.0-afp-mf-basic.xsd
                        urn:mace:shibboleth:2.0:afp:mf:saml classpath:/schema/shibboleth-2.0-afp-mf-saml.xsd">
MSG;

        foreach ($this->metadata as $metadata) {
            $xml .= $this->getEntryXML($metadata['metadata']);
        }

        $xml .= '</AttributeFilterPolicyGroup>';
        return $xml;
    }

    /**
     * @param array $entry
     *
     * @return string
     */
    private function getEntryXML($entry)
    {
        $entityid = $entry['entityid'];
        return '    <AttributeFilterPolicy id="' . $entityid .
            '"><PolicyRequirementRule xsi:type="basic:AttributeRequesterString" value="' . $entityid .
            '" />' . $this->getEntryXMLcontent($entry) . '</AttributeFilterPolicy>';
    }

    /**
     * @param array $entry
     *
     * @return string
     */
    private function getEntryXMLcontent($entry)
    {
        if (!array_key_exists('attributes', $entry)) {
            return '';
        }

        $ret = '';
        foreach ($entry['attributes'] as $a) {
            $ret .= '            <AttributeRule attributeID="' . $this->getAttributeID($a) .
                '"><PermitValueRule xsi:type="basic:ANY" /></AttributeRule>';
        }
        return $ret;
    }
}
