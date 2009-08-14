<?php
 
/**
 * A Shibboleth 1.3 authentication response.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_Shib13_AuthnResponse extends SimpleSAML_XML_AuthnResponse {

	/**
	 * This variable contains an XML validator for this message.
	 */
	private $validator = null;


	const SHIB_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:1.0:protocol';
	const SHIB_ASSERT_NS = 'urn:oasis:names:tc:SAML:1.0:assertion';

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	// Inhereted public function setXML($xml) {
	// Inhereted public function getXML() {
	// Inhereted public function setRelayState($relayState) {
	// Inhereted public function getRelayState() {

	
	public function validate() {
	
		$dom = $this->getDOM();

		/* Validate the signature. */
		$this->validator = new SimpleSAML_XML_Validator($dom, array('ResponseID', 'AssertionID'));

		// Get the issuer of the response.
		$issuer = $this->getIssuer();

		/* Get the metadata of the issuer. */
		$md = $this->metadata->getMetaData($issuer, 'shib13-idp-remote');

		if(array_key_exists('certFingerprint', $md)) {
			/* Get fingerprint for the certificate of the issuer. */
			$issuerFingerprint = $md['certFingerprint'];

			/* Validate the fingerprint. */
			$this->validator->validateFingerprint($issuerFingerprint);
		} elseif(array_key_exists('caFile', $md)) {
			/* Validate against CA. */
			$this->validator->validateCA($this->configuration->getPathValue('certdir', 'cert/') . $md['caFile']);
		} else {
			throw new Exception('Required field [certFingerprint] or [caFile] in Shibboleth 1.3 IdP Remote metadata was not found for identity provider [' . $issuer . ']. Please add a fingerprint and try again. You can add a dummy fingerprint first, and then an error message will be printed with the real fingerprint.');
		}

		return true;
	}


	/* Checks if the given node is validated by the signatore on this response.
	 *
	 * Returns:
	 *  TRUE if the node is validated or FALSE if not.
	 */
	private function isNodeValidated($node) {

		if($this->validator === NULL) {
			return FALSE;
		}

		/* Convert the node to a DOM node if it is an element from SimpleXML. */
		if($node instanceof SimpleXMLElement) {
			$node = dom_import_simplexml($node);
		}

		assert('$node instanceof DOMNode');

		return $this->validator->isNodeValidated($node);
	}


	/**
	 * This function runs an xPath query on this authentication response.
	 *
	 * @param $query  The query which should be run.
	 * @param $node   The node which this query is relative to. If this node is NULL (the default)
	 *                then the query will be relative to the root of the response.
	 */
	private function doXPathQuery($query, $node = NULL) {
		assert('is_string($query)');

		$dom = $this->getDOM();
		assert('$dom instanceof DOMDocument');

		if($node === NULL) {
			$node = $dom->documentElement;
		}

		assert('$node instanceof DOMNode');

		$xPath = new DOMXpath($dom);
		$xPath->registerNamespace('shibp', self::SHIB_PROTOCOL_NS);
		$xPath->registerNamespace('shib', self::SHIB_ASSERT_NS);

		return $xPath->query($query, $node);
	}

	/* This function is only included because it is in the base class. Will be removed in the future. */
	public function createSession() { throw new Exception('Removed');}
	
	//TODO
	function getSessionIndex() {
		$token = $this->getDOM();
		if ($token instanceof DOMDocument) {
			$xPath = new DOMXpath($token);
			$xPath->registerNamespace('mysamlp', self::SHIB_PROTOCOL_NS);
			$xPath->registerNamespace('mysaml', self::SHIB_ASSERT_NS);
			
			$query = '/mysamlp:Response/mysaml:Assertion/mysaml:AuthnStatement';
			$nodelist = $xPath->query($query);
			if ($node = $nodelist->item(0)) {
				return $node->getAttribute('SessionIndex');
			}
		}
		return NULL;
	}

	
	public function getAttributes() {

		$md = $this->metadata->getMetadata($this->getIssuer(), 'shib13-idp-remote');
		$base64 = isset($md['base64attributes']) ? $md['base64attributes'] : false;

		if (! ($this->getDOM() instanceof DOMDocument) ) {
			return array();
		}

		$attributes = array();

		$assertions = $this->doXPathQuery('/shibp:Response/shib:Assertion');

		foreach ($assertions AS $assertion) {

			if(!$this->isNodeValidated($assertion)) {
				throw new Exception('Shib13 AuthnResponse contained an unsigned assertion.');
			}

			$conditions = $this->doXPathQuery('shib:Conditions', $assertion);
			if ($conditions && $conditions->length > 0) {
				$condition = $conditions->item(0);

				$start = $condition->getAttribute('NotBefore');
				$end = $condition->getAttribute('NotOnOrAfter');

				if ($start && $end) {
					if (! SimpleSAML_Utilities::checkDateConditions($start, $end)) {
						error_log('Date check failed ... (from ' . $start . ' to ' . $end . ')');
						continue;
					}
				}
			}

			$attribute_nodes = $this->doXPathQuery('shib:AttributeStatement/shib:Attribute/shib:AttributeValue', $assertion);
			foreach($attribute_nodes as $attribute) {

				$value = $attribute->textContent;
				$name = $attribute->parentNode->getAttribute('AttributeName');

				if ($attribute->hasAttribute('Scope')) {
					$scopePart = '@' . $attribute->getAttribute('Scope');
				} else {
					$scopePart = '';
				}

				if(!is_string($name)) {
					throw new Exception('Shib13 Attribute node without an AttributeName.');
				}

				if(!array_key_exists($name, $attributes)) {
					$attributes[$name] = array();
				}

				if ($base64) {
					$encodedvalues = explode('_', $value);
					foreach($encodedvalues AS $v) {
						$attributes[$name][] = base64_decode($v) . $scopePart;
					}
				} else {
					$attributes[$name][] = $value . $scopePart;
				}
			}
		}

		return $attributes;
	}

	
	public function getIssuer() {
	
		$token = $this->getDOM();
		$xPath = new DOMXpath($token);
		$xPath->registerNamespace('mysamlp', self::SHIB_PROTOCOL_NS);
		$xPath->registerNamespace('mysaml', self::SHIB_ASSERT_NS);

		$query = '/mysamlp:Response/mysaml:Assertion/@Issuer';
		$nodelist = $xPath->query($query);

		if ($attr = $nodelist->item(0)) {
			return $attr->value;
		} else {
			throw new Exception('Could not find Issuer field in Authentication response');
		}

	}
	
	public function getNameID() {
				
		$token = $this->getDOM();
		$nameID = array();
		if ($token instanceof DOMDocument) {
			$xPath = new DOMXpath($token);
			$xPath->registerNamespace('mysamlp', self::SHIB_PROTOCOL_NS);
			$xPath->registerNamespace('mysaml', self::SHIB_ASSERT_NS);
	
			$query = '/mysamlp:Response/mysaml:Assertion/mysaml:AuthenticationStatement/mysaml:Subject/mysaml:NameIdentifier';
			$nodelist = $xPath->query($query);
			if ($node = $nodelist->item(0)) {
				$nameID["Value"] = $node->nodeValue;
				$nameID["Format"] = $node->getAttribute('Format');
				//$nameID["NameQualifier"] = $node->getAttribute('NameQualifier');
			}
		}
		return $nameID;

	}


	/**
	 * Build a authentication response.
	 *
	 * @param array $idp  Metadata for the IdP the response is sent from.
	 * @param array $sp  Metadata for the SP the response is sent to.
	 * @param string $shire  The endpoint on the SP the response is sent to.
	 * @param array|NULL $attributes  The attributes which should be included in the response.
	 * @return string  The response.
	 */
	public function generate($idp, $sp, $shire, $attributes) {
		assert('is_array($idp)');
		assert('is_array($sp)');
		assert('is_string($shire)');
		assert('$attributes === NULL || is_array($attributes)');

		if (array_key_exists('scopedattributes', $sp)) {
			$scopedAttributes = $sp['scopedattributes'];
			$scopedAttributesSource = 'the shib13-sp-remote sp \'' . $sp['entityid'] . '\'';
		} elseif (array_key_exists('scopedattributes', $idp)) {
			$scopedAttributes = $idp['scopedattributes'];
			$scopedAttributesSource = 'the shib13-idp-hosted idp \'' . $idp['entityid'] . '\'';
		} else {
			$scopedAttributes = array();
		}
		if (!is_array($scopedAttributes)) {
			throw new Exception('The \'scopedattributes\' option in ' . $scopedAttributesSource .
				' should be an array of attribute names.');
		}
		foreach ($scopedAttributes as $an) {
			if (!is_string($an)) {
				throw new Exception('Invalid attribute name in the \'scopedattributes\' option in ' .
					$scopedAttributesSource . ': ' . var_export($an, TRUE));
			}
		}

		$id = SimpleSAML_Utilities::generateID();
		
		$issueInstant = SimpleSAML_Utilities::generateTimestamp();
		
		// 30 seconds timeskew back in time to allow differing clocks.
		$notBefore = SimpleSAML_Utilities::generateTimestamp(time() - 30);
		
		
		$assertionExpire = SimpleSAML_Utilities::generateTimestamp(time() + 60 * 5);# 5 minutes
		$assertionid = SimpleSAML_Utilities::generateID();

		$audience = isset($sp['audience']) ? $sp['audience'] : $sp['entityid'];
		$base64 = isset($sp['base64attributes']) ? $sp['base64attributes'] : false;

		$namequalifier = isset($sp['NameQualifier']) ? $sp['NameQualifier'] : $sp['entityid'];
		$nameid = SimpleSAML_Utilities::generateID();
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

			foreach ($attributes AS $name => $value) {
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
        Issuer="' . htmlspecialchars($idp['entityid']) . '" MajorVersion="1" MinorVersion="1">
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
	private function enc_attribute($name, $values, $base64, $scopedAttributes) {
		assert('is_string($name)');
		assert('is_array($values)');
		assert('is_bool($base64)');
		assert('is_array($scopedAttributes)');

		if (in_array($name, $scopedAttributes, TRUE)) {
			$scoped = TRUE;
		} else {
			$scoped = FALSE;
		}

		$attr = '<Attribute AttributeName="' . htmlspecialchars($name) . '" AttributeNamespace="urn:mace:shibboleth:1.0:attributeNamespace:uri">';
		foreach ($values AS $value) {

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

}

?>