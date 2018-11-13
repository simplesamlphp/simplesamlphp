<?php

namespace SimpleSAML\Module\adfs\SAML2\XML\fed;

/**
 * Class representing SecurityTokenServiceType RoleDescriptor.
 *
 * @package SimpleSAMLphp
 */

class SecurityTokenServiceType extends \SAML2\XML\md\RoleDescriptor
{
    /**
     * List of supported protocols.
     *
     * @var array
     */
    public $protocolSupportEnumeration = [FedConst::NS_FED];

    /**
     * The Location of Services.
     *
     * @var string
     */
    public $Location;

    /**
     * Initialize a SecurityTokenServiceType element.
     *
     * @param \DOMElement|null $xml  The XML element we should load.
     */
    public function __construct(\DOMElement $xml = null)
    {
        parent::__construct('RoleDescriptor', $xml);
        if ($xml === null) {
            return;
        }
    }

    /**
     * Convert this SecurityTokenServiceType RoleDescriptor to XML.
     *
     * @param \DOMElement $parent  The element we should add this contact to.
     * @return \DOMElement  The new ContactPerson-element.
     */
    public function toXML(\DOMElement $parent)
    {
        assert(is_string($this->Location));

        $e = parent::toXML($parent);
        $e->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fed', FedConst::NS_FED);
        $e->setAttributeNS(\SAML2\Constants::NS_XSI, 'xsi:type', 'fed:SecurityTokenServiceType');
        TokenTypesOffered::appendXML($e);
        Endpoint::appendXML($e, 'SecurityTokenServiceEndpoint', $this->Location);
        Endpoint::appendXML($e, 'fed:PassiveRequestorEndpoint', $this->Location);

        return $e;
    }
}
