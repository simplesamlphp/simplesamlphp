<?php

/**
 * A Shibboleth 1.3 authentication response.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\XML\Shib13;

use SAML2\DOMDocumentFactory;
use SAML2\Utils;
use SimpleSAML\Utils\Config;
use SimpleSAML\Utils\Random;
use SimpleSAML\Utils\Time;
use SimpleSAML\XML\Validator;

class AuthnResponse
{

    /**
     * @var \SimpleSAML\XML\Validator This variable contains an XML validator for this message.
     */
    private $validator = null;


    /**
     * @var bool Whether this response was validated by some external means (e.g. SSL).
     */
    private $messageValidated = false;


    const SHIB_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:1.0:protocol';
    const SHIB_ASSERT_NS = 'urn:oasis:names:tc:SAML:1.0:assertion';


    /**
     * @var \DOMDocument The DOMDocument which represents this message.
     */
    private $dom;

    /**
     * @var string|null The relaystate which is associated with this response.
     */
    private $relayState = null;


    /**
     * Set whether this message was validated externally.
     *
     * @param bool $messageValidated  TRUE if the message is already validated, FALSE if not.
     */
    public function setMessageValidated($messageValidated)
    {
        assert('is_bool($messageValidated)');

        $this->messageValidated = $messageValidated;
    }


    public function setXML($xml)
    {
        assert('is_string($xml)');

        try {
            $this->dom = DOMDocumentFactory::fromString(str_replace("\r", "", $xml));
        } catch (\Exception $e) {
            throw new \Exception('Unable to parse AuthnResponse XML.');
        }
    }

    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
    }

    public function getRelayState()
    {
        return $this->relayState;
    }

    public function validate()
    {
        assert('$this->dom instanceof DOMDocument');

        if ($this->messageValidated) {
            // This message was validated externally
            return true;
        }

        // Validate the signature
        $this->validator = new Validator($this->dom, array('ResponseID', 'AssertionID'));

        // Get the issuer of the response
        $issuer = $this->getIssuer();

        // Get the metadata of the issuer
        $metadata = \SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $md = $metadata->getMetaDataConfig($issuer, 'shib13-idp-remote');

        $publicKeys = $md->getPublicKeys('signing');
        if ($publicKeys !== null) {
            $certFingerprints = array();
            foreach ($publicKeys as $key) {
                if ($key['type'] !== 'X509Certificate') {
                    continue;
                }
                $certFingerprints[] = sha1(base64_decode($key['X509Certificate']));
            }
            $this->validator->validateFingerprint($certFingerprints);
        } elseif ($md->hasValue('certFingerprint')) {
            $certFingerprints = $md->getArrayizeString('certFingerprint');

            // Validate the fingerprint
            $this->validator->validateFingerprint($certFingerprints);
        } elseif ($md->hasValue('caFile')) {
            // Validate against CA
            $this->validator->validateCA(Config::getCertPath($md->getString('caFile')));
        } else {
            throw new \SimpleSAML_Error_Exception('Missing certificate in Shibboleth 1.3 IdP Remote metadata for identity provider [' . $issuer . '].');
        }

        return true;
    }


    /**
     * Checks if the given node is validated by the signature on this response.
     *
     * @param \DOMElement $node Node to be validated.
     * @return bool TRUE if the node is validated or FALSE if not.
     */
    private function isNodeValidated($node)
    {
        if ($this->messageValidated) {
            // This message was validated externally
            return true;
        }

        if ($this->validator === null) {
            return false;
        }

        // Convert the node to a DOM node if it is an element from SimpleXML
        if ($node instanceof \SimpleXMLElement) {
            $node = dom_import_simplexml($node);
        }

        assert('$node instanceof DOMNode');

        return $this->validator->isNodeValidated($node);
    }


    /**
     * This function runs an xPath query on this authentication response.
     *
     * @param string $query   The query which should be run.
     * @param \DOMNode $node  The node which this query is relative to. If this node is NULL (the default)
     *                        then the query will be relative to the root of the response.
     * @return \DOMNodeList
     */
    private function doXPathQuery($query, $node = null)
    {
        assert('is_string($query)');
        assert('$this->dom instanceof DOMDocument');

        if ($node === null) {
            $node = $this->dom->documentElement;
        }

        assert('$node instanceof DOMNode');

        $xPath = new \DOMXpath($this->dom);
        $xPath->registerNamespace('shibp', self::SHIB_PROTOCOL_NS);
        $xPath->registerNamespace('shib', self::SHIB_ASSERT_NS);

        return $xPath->query($query, $node);
    }

    /**
     * Retrieve the session index of this response.
     *
     * @return string|null  The session index of this response.
     */
    public function getSessionIndex()
    {
        assert('$this->dom instanceof DOMDocument');

        $query = '/shibp:Response/shib:Assertion/shib:AuthnStatement';
        $nodelist = $this->doXPathQuery($query);
        if ($node = $nodelist->item(0)) {
            return $node->getAttribute('SessionIndex');
        }

        return null;
    }

    
    public function getAttributes()
    {
        $metadata = \SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
        $md = $metadata->getMetadata($this->getIssuer(), 'shib13-idp-remote');
        $base64 = isset($md['base64attributes']) ? $md['base64attributes'] : false;

        if (! ($this->dom instanceof \DOMDocument)) {
            return array();
        }

        $attributes = array();

        $assertions = $this->doXPathQuery('/shibp:Response/shib:Assertion');

        foreach ($assertions as $assertion) {
            if (!$this->isNodeValidated($assertion)) {
                throw new \Exception('Shib13 AuthnResponse contained an unsigned assertion.');
            }

            $conditions = $this->doXPathQuery('shib:Conditions', $assertion);
            if ($conditions && $conditions->length > 0) {
                $condition = $conditions->item(0);

                $start = $condition->getAttribute('NotBefore');
                $end = $condition->getAttribute('NotOnOrAfter');

                if ($start && $end) {
                    if (!self::checkDateConditions($start, $end)) {
                        error_log('Date check failed ... (from ' . $start . ' to ' . $end . ')');
                        continue;
                    }
                }
            }

            $attribute_nodes = $this->doXPathQuery('shib:AttributeStatement/shib:Attribute/shib:AttributeValue', $assertion);
            /** @var \DOMElement $attribute */
            foreach ($attribute_nodes as $attribute) {
                $value = $attribute->textContent;
                $name = $attribute->parentNode->getAttribute('AttributeName');

                if ($attribute->hasAttribute('Scope')) {
                    $scopePart = '@' . $attribute->getAttribute('Scope');
                } else {
                    $scopePart = '';
                }

                if (!is_string($name)) {
                    throw new \Exception('Shib13 Attribute node without an AttributeName.');
                }

                if (!array_key_exists($name, $attributes)) {
                    $attributes[$name] = array();
                }

                if ($base64) {
                    $encodedvalues = explode('_', $value);
                    foreach ($encodedvalues as $v) {
                        $attributes[$name][] = base64_decode($v) . $scopePart;
                    }
                } else {
                    $attributes[$name][] = $value . $scopePart;
                }
            }
        }

        return $attributes;
    }

    
    public function getIssuer()
    {
        $query = '/shibp:Response/shib:Assertion/@Issuer';
        $nodelist = $this->doXPathQuery($query);

        if ($attr = $nodelist->item(0)) {
            return $attr->value;
        } else {
            throw new \Exception('Could not find Issuer field in Authentication response');
        }
    }

    public function getNameID()
    {
        $nameID = array();

        $query = '/shibp:Response/shib:Assertion/shib:AuthenticationStatement/shib:Subject/shib:NameIdentifier';
        $nodelist = $this->doXPathQuery($query);

        if ($node = $nodelist->item(0)) {
            $nameID["Value"] = $node->nodeValue;
            $nameID["Format"] = $node->getAttribute('Format');
        }

        return $nameID;
    }


    /**
     * Build a authentication response.
     *
     * @param \SimpleSAML_Configuration $idp Metadata for the IdP the response is sent from.
     * @param \SimpleSAML_Configuration $sp Metadata for the SP the response is sent to.
     * @param string $shire The endpoint on the SP the response is sent to.
     * @param array|null $attributes The attributes which should be included in the response.
     * @return string The response.
     */
    public function generate(\SimpleSAML_Configuration $idp, \SimpleSAML_Configuration $sp, $shire, $attributes)
    {
        assert('is_string($shire)');
        assert('$attributes === NULL || is_array($attributes)');

        if ($sp->hasValue('scopedattributes')) {
            $scopedAttributes = $sp->getArray('scopedattributes');
        } elseif ($idp->hasValue('scopedattributes')) {
            $scopedAttributes = $idp->getArray('scopedattributes');
        } else {
            $scopedAttributes = array();
        }

        $id = Random::generateID();
        
        $issueInstant = Time::generateTimestamp();
        
        // 30 seconds timeskew back in time to allow differing clocks
        $notBefore = Time::generateTimestamp(time() - 30);
        
        
        $assertionExpire = Time::generateTimestamp(time() + 60 * 5);# 5 minutes
        $assertionid = Random::generateID();

        $spEntityId = $sp->getString('entityid');

        $audience = $sp->getString('audience', $spEntityId);
        $base64 = $sp->getBoolean('base64attributes', false);

        $namequalifier = $sp->getString('NameQualifier', $spEntityId);
        $nameid = Random::generateID();
        $subjectNode =
            '<Subject>' .
            '<NameIdentifier' .
            ' Format="urn:mace:shibboleth:1.0:nameIdentifier"' .
            ' NameQualifier="' . htmlspecialchars($namequalifier) . '"' .
            '>' .
            htmlspecialchars($nameid) .
            '</NameIdentifier>' .
            '<SubjectConfirmation>' .
            '<ConfirmationMethod>' .
            'urn:oasis:names:tc:SAML:1.0:cm:bearer' .
            '</ConfirmationMethod>' .
            '</SubjectConfirmation>' .
            '</Subject>';

        $encodedattributes = '';

        if (is_array($attributes)) {
            $encodedattributes .= '<AttributeStatement>';
            $encodedattributes .= $subjectNode;

            foreach ($attributes as $name => $value) {
                $encodedattributes .= $this->enc_attribute($name, $value, $base64, $scopedAttributes);
            }

            $encodedattributes .= '</AttributeStatement>';
        }

        /*
         * The SAML 1.1 response message
         */
        $response = '<Response xmlns="urn:oasis:names:tc:SAML:1.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:1.0:assertion"
    xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol" xmlns:xsd="http://www.w3.org/2001/XMLSchema"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" IssueInstant="' . $issueInstant. '"
    MajorVersion="1" MinorVersion="1"
    Recipient="' . htmlspecialchars($shire) . '" ResponseID="' . $id . '">
    <Status>
        <StatusCode Value="samlp:Success" />
    </Status>
    <Assertion xmlns="urn:oasis:names:tc:SAML:1.0:assertion"
        AssertionID="' . $assertionid . '" IssueInstant="' . $issueInstant. '"
        Issuer="' . htmlspecialchars($idp->getString('entityid')) . '" MajorVersion="1" MinorVersion="1">
        <Conditions NotBefore="' . $notBefore. '" NotOnOrAfter="'. $assertionExpire . '">
            <AudienceRestrictionCondition>
                <Audience>' . htmlspecialchars($audience) . '</Audience>
            </AudienceRestrictionCondition>
        </Conditions>
        <AuthenticationStatement AuthenticationInstant="' . $issueInstant. '"
            AuthenticationMethod="urn:oasis:names:tc:SAML:1.0:am:unspecified">' .
            $subjectNode . '
        </AuthenticationStatement>
        ' . $encodedattributes . '
    </Assertion>
</Response>';

        return $response;
    }


    /**
     * Format a shib13 attribute.
     *
     * @param string $name  Name of the attribute.
     * @param array $values  Values of the attribute (as an array of strings).
     * @param bool $base64  Whether the attriubte values should be base64-encoded.
     * @param array $scopedAttributes  Array of attributes names which are scoped.
     * @return string  The attribute encoded as an XML-string.
     */
    private function enc_attribute($name, $values, $base64, $scopedAttributes)
    {
        assert('is_string($name)');
        assert('is_array($values)');
        assert('is_bool($base64)');
        assert('is_array($scopedAttributes)');

        if (in_array($name, $scopedAttributes, true)) {
            $scoped = true;
        } else {
            $scoped = false;
        }

        $attr = '<Attribute AttributeName="' . htmlspecialchars($name) . '" AttributeNamespace="urn:mace:shibboleth:1.0:attributeNamespace:uri">';
        foreach ($values as $value) {
            $scopePart = '';
            if ($scoped) {
                $tmp = explode('@', $value, 2);
                if (count($tmp) === 2) {
                    $value = $tmp[0];
                    $scopePart = ' Scope="' . htmlspecialchars($tmp[1]) . '"';
                }
            }

            if ($base64) {
                $value = base64_encode($value);
            }

            $attr .= '<AttributeValue' . $scopePart . '>' . htmlspecialchars($value) . '</AttributeValue>';
        }
        $attr .= '</Attribute>';

        return $attr;
    }

    /**
     * Check if we are currently between the given date & time conditions.
     *
     * Note that this function allows a 10-minute leap from the initial time as marked by $start.
     *
     * @param string|null $start A SAML2 timestamp marking the start of the period to check. Defaults to null, in which
     *     case there's no limitations in the past.
     * @param string|null $end A SAML2 timestamp marking the end of the period to check. Defaults to null, in which
     *     case there's no limitations in the future.
     *
     * @return bool True if the current time belongs to the period specified by $start and $end. False otherwise.
     *
     * @see \SAML2\Utils::xsDateTimeToTimestamp.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    protected static function checkDateConditions($start = null, $end = null)
    {
        $currentTime = time();

        if (!empty($start)) {
            $startTime = Utils::xsDateTimeToTimestamp($start);
            // allow for a 10 minute difference in time
            if (($startTime < 0) || (($startTime - 600) > $currentTime)) {
                return false;
            }
        }
        if (!empty($end)) {
            $endTime = Utils::xsDateTimeToTimestamp($end);
            if (($endTime < 0) || ($endTime <= $currentTime)) {
                return false;
            }
        }
        return true;
    }
}
