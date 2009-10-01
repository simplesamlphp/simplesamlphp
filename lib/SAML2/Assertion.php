<?php

/**
 * Class representing a SAML 2 assertion.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_Assertion implements SAML2_SignedElement {

	/**
	 * The identifier of this assertion.
	 *
	 * @var string
	 */
	private $id;


	/**
	 * The issue timestamp of this assertion, as an UNIX timestamp.
	 *
	 * @var int
	 */
	private $issueInstant;


	/**
	 * The entity id of the issuer of this assertion.
	 *
	 * @var string
	 */
	private $issuer;


	/**
	 * The NameId of the subject in the assertion.
	 *
	 * If the NameId is NULL, no subject was included in the assertion.
	 *
	 * @var array|NULL
	 */
	private $nameId;


	/**
	 * The encrypted NameId of the subject.
	 *
	 * If this is not NULL, the NameId needs decryption before it can be accessed.
	 *
	 * @var DOMElement|NULL
	 */
	private $encryptedNameId;


	/**
	 * The earliest time this assertion is valid, as an UNIX timestamp.
	 *
	 * @var int
	 */
	private $notBefore;


	/**
	 * The time this assertion expires, as an UNIX timestamp.
	 *
	 * @var int
	 */
	private $notOnOrAfter;


	/**
	 * The destination URL for this assertion.
	 *
	 * @var string|NULL
	 */
	private $destination;


	/**
	 * The id of the request this assertion is sent as a response to.
	 *
	 * This should be NULL if this isn't a response to a request.
	 *
	 * @var string|NULL
	 */
	private $inResponseTo;


	/**
	 * The set of audiences that are allowed to receive this assertion.
	 *
	 * This is an array of valid service providers.
	 *
	 * If no restrictions on the audience are present, this variable contains NULL.
	 *
	 * @var array|NULL
	 */
	private $validAudiences;


	/**
	 * The session expiration timestamp.
	 *
	 * @var int|NULL
	 */
	private $sessionNotOnOrAfter;


	/**
	 * The session index for this user on the IdP.
	 *
	 * Contains NULL if no session index is present.
	 *
	 * @var string|NULL
	 */
	private $sessionIndex;


	/**
	 * The authentication context for this assertion.
	 *
	 * @var string|NULL
	 */
	private $authnContext;


	/**
	 * The attributes, as an associative array.
	 *
	 * @var array
	 */
	private $attributes;


	/**
	 * The NameFormat used on all attributes.
	 *
	 * If more than one NameFormat is used, this will contain
	 * the unspecified nameformat.
	 *
	 * @var string
	 */
	private $nameFormat;


	/**
	 * The private key we should use to sign the assertion.
	 *
	 * The private key can be NULL, in which case the assertion is sent unsigned.
	 *
	 * @var XMLSecurityKey|NULL
	 */
	private $signatureKey;


	/**
	 * List of certificates that should be included in the assertion.
	 *
	 * @var array
	 */
	private $certificates;


	/**
	 * The data needed to verify the signature.
	 *
	 * @var array|NULL
	 */
	private $signatureData;



	/**
	 * Constructor for SAML 2 assertions.
	 *
	 * @param DOMElement|NULL $xml  The input assertion.
	 */
	public function __construct(DOMElement $xml = NULL) {

		$this->id = SimpleSAML_Utilities::generateID();
		$this->issueInstant = time();
		$this->issuer = '';
		$this->attributes = array();
		$this->nameFormat = SAML2_Const::NAMEFORMAT_UNSPECIFIED;
		$this->certificates = array();

		if ($xml === NULL) {
			return;
		}

		if (!$xml->hasAttribute('ID')) {
			throw new Exception('Missing ID attribute on SAML assertion.');
		}
		$this->id = $xml->getAttribute('ID');

		if ($xml->getAttribute('Version') !== '2.0') {
			/* Currently a very strict check. */
			throw new Exception('Unsupported version: ' . $xml->getAttribute('Version'));
		}

		$this->issueInstant = SimpleSAML_Utilities::parseSAML2Time($xml->getAttribute('IssueInstant'));

		$issuer = SAML2_Utils::xpQuery($xml, './saml_assertion:Issuer');
		if (empty($issuer)) {
			throw new Exception('Missing <saml:Issuer> in assertion.');
		}
		$this->issuer = trim($issuer[0]->textContent);

		$this->parseSubject($xml);
		$this->parseConditions($xml);
		$this->parseAuthnStatement($xml);
		$this->parseAttributes($xml);
		$this->parseSignature($xml);
	}


	/**
	 * Parse subject in assertion.
	 *
	 * @param DOMElement $xml  The assertion XML element.
	 */
	private function parseSubject(DOMElement $xml) {

		$subject = SAML2_Utils::xpQuery($xml, './saml_assertion:Subject');
		if (empty($subject)) {
			/* No Subject node. */
			return;
		} elseif (count($subject) > 1) {
			throw new Exception('More than one <saml:Subject> in <saml:Assertion>.');
		}
		$subject = $subject[0];

		$nameId = SAML2_Utils::xpQuery($subject, './saml_assertion:NameID | ./saml_assertion:EncryptedID/xenc:EncryptedData');
		if (empty($nameId)) {
			throw new Exception('Missing <saml:NameID> or <saml:EncryptedID> in <saml:Subject>.');
		} elseif (count($nameId) > 1) {
			throw new Exception('More than one <saml:NameID> or <saml:EncryptedD> in <saml:Subject>.');
		}
		$nameId = $nameId[0];
		if ($nameId->localName === 'EncryptedData') {
			/* The NameID element is encrypted. */
			$this->encryptedNameId = $nameId;
		} else {
			$this->nameId = SAML2_Utils::parseNameId($nameId);
		}

		$subjectConfirmation = SAML2_Utils::xpQuery($subject, './saml_assertion:SubjectConfirmation');
		if (empty($subjectConfirmation)) {
			throw new Exception('Missing <saml:SubjectConfirmation> in <saml:Subject>.');
		} elseif (count($subjectConfirmation) > 1) {
			throw new Exception('More than one <saml:SubjectConfirmation> in <saml:Subject>.');
		}
		$subjectConfirmation = $subjectConfirmation[0];

		if (!$subjectConfirmation->hasAttribute('Method')) {
			throw new Exception('Missing required attribute "Method" on <saml:SubjectConfirmation>-node.');
		}
		$method = $subjectConfirmation->getAttribute('Method');

		if ($method !== SAML2_Const::CM_BEARER) {
			throw new Exception('Unsupported subject confirmation method: ' . var_export($method, TRUE));
		}

		$confirmationData = SAML2_Utils::xpQuery($subjectConfirmation, './saml_assertion:SubjectConfirmationData');
		if (empty($confirmationData)) {
			return;
		} elseif (count($confirmationData) > 1) {
			throw new Exception('More than one <saml:SubjectConfirmationData> in <saml:SubjectConfirmation> is currently unsupported.');
		}
		$confirmationData = $confirmationData[0];

		if ($confirmationData->hasAttribute('NotBefore')) {
			$notBefore = SimpleSAML_Utilities::parseSAML2Time($confirmationData->getAttribute('NotBefore'));
			if ($this->notBefore === NULL || $this->notBefore < $notBefore) {
				$this->notBefore = $notBefore;
			}
		}
		if ($confirmationData->hasAttribute('NotOnOrAfter')) {
			$notOnOrAfter = SimpleSAML_Utilities::parseSAML2Time($confirmationData->getAttribute('NotOnOrAfter'));
			if ($this->notOnOrAfter === NULL || $this->notOnOrAfter > $notOnOrAfter) {
				$this->notOnOrAfter = $notOnOrAfter;
			}
		}
		if ($confirmationData->hasAttribute('InResponseTo')) {
			$this->inResponseTo = $confirmationData->getAttribute('InResponseTo');;
		}
		if ($confirmationData->hasAttribute('Recipient')) {
			$this->destination = $confirmationData->getAttribute('Recipient');;
		}
	}


	/**
	 * Parse conditions in assertion.
	 *
	 * @param DOMElement $xml  The assertion XML element.
	 */
	private function parseConditions(DOMElement $xml) {

		$conditions = SAML2_Utils::xpQuery($xml, './saml_assertion:Conditions');
		if (empty($conditions)) {
			/* No <saml:Conditions> node. */
			return;
		} elseif (count($conditions) > 1) {
			throw new Exception('More than one <saml:Conditions> in <saml:Assertion>.');
		}
		$conditions = $conditions[0];

		if ($conditions->hasAttribute('NotBefore')) {
			$notBefore = SimpleSAML_Utilities::parseSAML2Time($conditions->getAttribute('NotBefore'));
			if ($this->notBefore === NULL || $this->notBefore < $notBefore) {
				$this->notBefore = $notBefore;
			}
		}
		if ($conditions->hasAttribute('NotOnOrAfter')) {
			$notOnOrAfter = SimpleSAML_Utilities::parseSAML2Time($conditions->getAttribute('NotOnOrAfter'));
			if ($this->notOnOrAfter === NULL || $this->notOnOrAfter > $notOnOrAfter) {
				$this->notOnOrAfter = $notOnOrAfter;
			}
		}


		for ($node = $conditions->firstChild; $node !== NULL; $node = $node->nextSibling) {
			if ($node instanceof DOMText) {
				continue;
			}
			if ($node->namespaceURI !== SAML2_Const::NS_SAML) {
				throw new Exception('Unknown namespace of condition: ' . var_export($node->namespaceURI, TRUE));
			}
			switch ($node->localName) {
			case 'AudienceRestriction':
				$audiences = SAML2_Utils::xpQuery($node, './saml_assertion:Audience');
				foreach ($audiences as &$audience) {
					$audience = trim($audience->textContent);
				}
				if ($this->validAudiences === NULL) {
					/* The first (and probably last) AudienceRestriction element. */
					$this->validAudiences = $audiences;

				} else {
					/*
					 * The set of AudienceRestriction are ANDed together, so we need
					 * the subset that are present in all of them.
					 */
					$this->validAudiences = array_intersect($this->validAudiences, $audiences);
				}
				break;
			case 'OneTimeUse':
				/* Currently ignored. */
				break;
			case 'ProxyRestriction':
				/* Currently ignored. */
				break;
			default:
				throw new Exception('Unknown condition: ' . var_export($node->localName, TRUE));
			}
		}

	}


	/**
	 * Parse AuthnStatement in assertion.
	 *
	 * @param DOMElement $xml  The assertion XML element.
	 */
	private function parseAuthnStatement(DOMElement $xml) {

		$as = SAML2_Utils::xpQuery($xml, './saml_assertion:AuthnStatement');
		if (empty($as)) {
			return;
		} elseif (count($as) > 1) {
			throw new Exception('More that one <saml:AuthnStatement> in <saml:Assertion> not supported.');
		}
		$as = $as[0];
		$this->authnStatement = array();

		if (!$as->hasAttribute('AuthnInstant')) {
			throw new Exception('Missing required AuthnInstant attribute on <saml:AuthnStatement>.');
		}

		if ($as->hasAttribute('SessionNotOnOrAfter')) {
			$this->sessionNotOnOrAfter = SimpleSAML_Utilities::parseSAML2Time($as->getAttribute('SessionNotOnOrAfter'));
		}

		if ($as->hasAttribute('SessionIndex')) {
			$this->sessionIndex = $as->getAttribute('SessionIndex');
		}

		$ac = SAML2_Utils::xpQuery($as, './saml_assertion:AuthnContext');
		if (empty($ac)) {
			throw new Exception('Missing required <saml:AuthnContext> in <saml:AuthnStatement>.');
		} elseif (count($ac) > 1) {
			throw new Exception('More than one <saml:AuthnContext> in <saml:AuthnStatement>.');
		}
		$ac = $ac[0];

		$accr = SAML2_Utils::xpQuery($ac, './saml_assertion:AuthnContextClassRef');
		if (empty($accr)) {
			$acdr = SAML2_Utils::xpQuery($ac, './saml_assertion:AuthnContextDeclRef');
			if (empty($acdr)) {
				throw new Exception('Neither <saml:AuthnContextClassRef> nor <saml:AuthnContextDeclRef> found in <saml:AuthnContext>.');
			} elseif (count($accr) > 1) {
				throw new Exception('More than one <saml:AuthnContextDeclRef> in <saml:AuthnContext>.');
			}
			$this->authnContext = trim($acdr[0]->textContent);
		} elseif (count($accr) > 1) {
			throw new Exception('More than one <saml:AuthnContextClassRef> in <saml:AuthnContext>.');
		} else {
			$this->authnContext = trim($accr[0]->textContent);
		}
	}


	/**
	 * Parse attribute statements in assertion.
	 *
	 * @param DOMElement $xml  The XML element with the assertion.
	 */
	private function parseAttributes(DOMElement $xml) {

		$firstAttribute = TRUE;
		$attributes = SAML2_Utils::xpQuery($xml, './saml_assertion:AttributeStatement/saml_assertion:Attribute');
		foreach ($attributes as $attribute) {
			if (!$attribute->hasAttribute('Name')) {
				throw new Exception('Missing name on <saml:Attribute> element.');
			}
			$name = $attribute->getAttribute('Name');

			if ($attribute->hasAttribute('NameFormat')) {
				$nameFormat = $attribute->getAttribute('NameFormat');
			} else {
				$nameFormat = SAML2_Const::NAMEFORMAT_UNSPECIFIED;
			}

			if ($firstAttribute) {
				$this->nameFormat = $nameFormat;
				$firstAttribute = FALSE;
			} else {
				if ($this->nameFormat !== $nameFormat) {
					$this->nameFormat = SAML2_Const::NAMEFORMAT_UNSPECIFIED;
				}
			}

			if (!array_key_exists($name, $this->attributes)) {
				$this->attributes[$name] = array();
			}

			$values = SAML2_Utils::xpQuery($attribute, './saml_assertion:AttributeValue');
			foreach ($values as $value) {
				$this->attributes[$name][] = trim($value->textContent);
			}
		}
	}


	/**
	 * Parse signature on assertion.
	 *
	 * @param DOMElement $xml  The assertion XML element.
	 */
	private function parseSignature(DOMElement $xml) {

		/* Validate the signature element of the message. */
		$sig = SAML2_Utils::validateElement($xml);
		if ($sig !== FALSE) {
			$this->certificates = $sig['Certificates'];
			$this->signatureData = $sig;
		}
	}


	/**
	 * Validate this assertion against a public key.
	 *
	 * If no signature was present on the assertion, we will return FALSE.
	 * Otherwise, TRUE will be returned. An exception is thrown if the
	 * signature validation fails.
	 *
	 * @param XMLSecurityKey $key  The key we should check against.
	 * @return boolean  TRUE if successful, FALSE if it is unsigned.
	 */
	public function validate(XMLSecurityKey $key) {
		assert('$key->type === XMLSecurityKey::RSA_SHA1');

		if ($this->signatureData === NULL) {
			return FALSE;
		}

		SAML2_Utils::validateSignature($this->signatureData, $key);

		return TRUE;
	}


	/**
	 * Retrieve the identifier of this assertion.
	 *
	 * @return string  The identifier of this assertion.
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * Set the identifier of this assertion.
	 *
	 * @param string $id  The new identifier of this assertion.
	 */
	public function setId($id) {
		assert('is_string($id)');

		$this->id = $id;
	}


	/**
	 * Retrieve the issue timestamp of this assertion.
	 *
	 * @return int  The issue timestamp of this assertion, as an UNIX timestamp.
	 */
	public function getIssueInstant() {
		return $this->issueInstant;
	}


	/**
	 * Set the issue timestamp of this assertion.
	 *
	 * @param int $issueInstant  The new issue timestamp of this assertion, as an UNIX timestamp.
	 */
	public function setIssueInstant($issueInstant) {
		assert('is_int($issueInstant)');

		$this->issueInstant = $issueInstant;
	}


	/**
	 * Retrieve the issuer if this assertion.
	 *
	 * @return string  The issuer of this assertion.
	 */
	public function getIssuer() {
		return $this->issuer;
	}


	/**
	 * Set the issuer of this message.
	 *
	 * @param string $issuer  The new issuer of this assertion.
	 */
	public function setIssuer($issuer) {
		assert('is_string($issuer)');

		$this->issuer = $issuer;
	}


	/**
	 * Retrieve the NameId of the subject in the assertion.
	 *
	 * The returned NameId is in the format used by SAML2_Utils::addNameId().
	 *
	 * @see SAML2_Utils::addNameId()
	 * @return array|NULL  The name identifier of the assertion.
	 */
	public function getNameId() {

		if ($this->encryptedNameId !== NULL) {
			throw new Exception('Attempted to retrieve encrypted NameID without decrypting it first.');
		}

		return $this->nameId;
	}


	/**
	 * Set the NameId of the subject in the assertion.
	 *
	 * The NameId must be in the format accepted by SAML2_Utils::addNameId().
	 *
	 * @see SAML2_Utils::addNameId()
	 * @param array|NULL $nameId  The name identifier of the assertion.
	 */
	public function setNameId($nameId) {
		assert('is_array($nameId) || is_null($nameId)');

		$this->nameId = $nameId;
	}


	/**
	 * Check whether the NameId is encrypted.
	 *
	 * @return TRUE if the NameId is encrypted, FALSE if not.
	 */
	public function isNameIdEncrypted() {

		if ($this->encryptedNameId !== NULL) {
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Decrypt the NameId of the subject in the assertion.
	 *
	 * @param XMLSecurityKey $key  The decryption key.
	 */
	public function decryptNameId(XMLSecurityKey $key) {

		if ($this->encryptedNameId === NULL) {
			/* No NameID to decrypt. */
			return;
		}

		$nameId = SAML2_Utils::decryptElement($this->encryptedNameId, $key);
		$this->nameId = SAML2_Utils::parseNameId($nameId);

		$this->encryptedNameId = NULL;
	}


	/**
	 * Retrieve the earliest timestamp this assertion is valid.
	 *
	 * This function returns NULL if there are no restrictions on how early the
	 * assertion can be used.
	 *
	 * @return int|NULL  The earliest timestamp this assertion is valid.
	 */
	public function getNotBefore() {

		return $this->notBefore;
	}


	/**
	 * Set the earliest timestamp this assertion can be used.
	 *
	 * Set this to NULL if no limit is required.
	 *
	 * @param int|NULL $notBefore  The earliest timestamp this assertion is valid.
	 */
	public function setNotBefore($notBefore) {
		assert('is_int($notBefore) || is_null($notBefore)');

		$this->notBefore = $notBefore;
	}


	/**
	 * Retrieve the expiration timestamp of this assertion.
	 *
	 * This function returns NULL if there are no restrictions on how
	 * late the assertion can be used.
	 *
	 * @return int|NULL  The latest timestamp this assertion is valid.
	 */
	public function getNotOnOrAfter() {

		return $this->notOnOrAfter;
	}


	/**
	 * Set the expiration timestamp of this assertion.
	 *
	 * Set this to NULL if no limit is required.
	 *
	 * @param int|NULL $notOnOrAfter  The latest timestamp this assertion is valid.
	 */
	public function setNotOnOrAfter($notOnOrAfter) {
		assert('is_int($notOnOrAfter) || is_null($notOnOrAfter)');

		$this->notOnOrAfter = $notOnOrAfter;
	}


	/**
	 * Retrieve the destination URL of this assertion.
	 *
	 * This function returns NULL if there are no restrictions on which URL can
	 * receive the assertion.
	 *
	 * @return string|NULL  The destination URL of this assertion.
	 */
	public function getDestination() {

		return $this->destination;
	}


	/**
	 * Set the destination URL of this assertion.
	 *
	 * @return string|NULL  The destination URL of this assertion.
	 */
	public function setDestination($destination) {
		assert('is_string($destination) || is_null($destination)');

		$this->destination = $destination;
	}


	/**
	 * Retrieve the request this assertion is sent in response to.
	 *
	 * Can be NULL, in which case this assertion isn't sent in response to a specific request.
	 *
	 * @return string|NULL  The id of the request this assertion is sent in response to.
	 */
	public function getInResponseTo() {

		return $this->inResponseTo;
	}


	/**
	 * Set the request this assertion is sent in response to.
	 *
	 * Can be set to NULL, in which case this assertion isn't sent in response to a specific request.
	 *
	 * @param string|NULL $inResponseTo  The id of the request this assertion is sent in response to.
	 */
	public function setInResponseTo($inResponseTo) {
		assert('is_string($inResponseTo) || is_null($inResponseTo)');

		$this->inResponseTo = $inResponseTo;
	}


	/**
	 * Retrieve the audiences that are allowed to receive this assertion.
	 *
	 * This may be NULL, in which case all audiences are allowed.
	 *
	 * @return array|NULL  The allowed audiences.
	 */
	public function getValidAudiences() {

		return $this->validAudiences;
	}


	/**
	 * Set the audiences that are allowed to receive this assertion.
	 *
	 * This may be NULL, in which case all audiences are allowed.
	 *
	 * @param array|NULL $validAudiences  The allowed audiences.
	 */
	public function setValidAudiences(array $validAudiences = NULL) {

		$this->validAudiences = $validAudiences;
	}


	/**
	 * Retrieve the session expiration timestamp.
	 *
	 * This function returns NULL if there are no restrictions on the
	 * session lifetime.
	 *
	 * @return int|NULL  The latest timestamp this session is valid.
	 */
	public function getSessionNotOnOrAfter() {

		return $this->sessionNotOnOrAfter;
	}


	/**
	 * Set the session expiration timestamp.
	 *
	 * Set this to NULL if no limit is required.
	 *
	 * @param int|NULL $sessionLifetime  The latest timestamp this session is valid.
	 */
	public function setSessionNotOnOrAfter($sessionNotOnOrAfter) {
		assert('is_int($sessionNotOnOrAfter) || is_null($sessionNotOnOrAfter)');

		$this->sessionNotOnOrAfter = $sessionNotOnOrAfter;
	}


	/**
	 * Retrieve the session index of the user at the IdP.
	 *
	 * @return string|NULL  The session index of the user at the IdP.
	 */
	public function getSessionIndex() {

		return $this->sessionIndex;
	}


	/**
	 * Set the session index of the user at the IdP.
	 *
	 * Note that the authentication context must be set before the
	 * session index can be inluded in the assertion.
	 *
	 * @param string|NULL $sessionIndex  The session index of the user at the IdP.
	 */
	public function setSessionIndex($sessionIndex) {
		assert('is_string($sessionIndex) || is_null($sessionIndex)');

		$this->sessionIndex = $sessionIndex;
	}


	/**
	 * Retrieve the authentication method used to authenticate the user.
	 *
	 * This will return NULL if no authentication statement was
	 * included in the assertion.
	 *
	 * @return string|NULL  The authentication method.
	 */
	public function getAuthnContext() {

		return $this->authnContext;
	}


	/**
	 * Set the authentication method used to authenticate the user.
	 *
	 * If this is set to NULL, no authentication statement will be
	 * included in the assertion. The default is NULL.
	 *
	 * @param string|NULL $authnContext  The authentication method.
	 */
	public function setAuthnContext($authnContext) {
		assert('is_string($authnContext) || is_null($authnContext)');

		$this->authnContext = $authnContext;
	}


	/**
	 * Retrieve all attributes.
	 *
	 * @return array  All attributes, as an associative array.
	 */
	public function getAttributes() {

		return $this->attributes;
	}


	/**
	 * Replace all attributes.
	 *
	 * @param array $attributes  All new attributes, as an associative array.
	 */
	public function setAttributes(array $attributes) {

		$this->attributes = $attributes;
	}


	/**
	 * Retrieve the NameFormat used on all attributes.
	 *
	 * If more than one NameFormat is used in the received attributes, this
	 * returns the unspecified NameFormat.
	 *
	 * @return string  The NameFormat used on all attributes.
	 */
	public function getAttributeNameFormat() {
		return $this->nameFormat;
	}


	/**
	 * Set the NameFormat used on all attributes.
	 *
	 * @param string $nameFormat  The NameFormat used on all attributes.
	 */
	public function setAttributeNameFormat($nameFormat) {
		assert('is_string($nameFormat)');

		$this->nameFormat = $nameFormat;
	}


	/**
	 * Retrieve the private key we should use to sign the assertion.
	 *
	 * @return XMLSecurityKey|NULL The key, or NULL if no key is specified.
	 */
	public function getSignatureKey() {
		return $this->signatureKey;
	}


	/**
	 * Set the private key we should use to sign the assertion.
	 *
	 * If the key is NULL, the assertion will be sent unsigned.
	 *
	 * @param XMLSecurityKey|NULL $key
	 */
	public function setSignatureKey(XMLsecurityKey $signatureKey = NULL) {
		$this->signatureKey = $signatureKey;
	}


	/**
	 * Set the certificates that should be included in the assertion.
	 *
	 * The certificates should be strings with the PEM encoded data.
	 *
	 * @param array $certificates  An array of certificates.
	 */
	public function setCertificates(array $certificates) {
		$this->certificates = $certificates;
	}


	/**
	 * Retrieve the certificates that are included in the assertion.
	 *
	 * @return array  An array of certificates.
	 */
	public function getCertificates() {
		return $this->certificates;
	}


	/**
	 * Convert this assertion to an XML element.
	 *
	 * @param DOMNode|NULL $parentElement  The DOM node the assertion should be created in.
	 * @return DOMElement  This assertion.
	 */
	public function toXML(DOMNode $parentElement = NULL) {

		if ($parentElement === NULL) {
			$document = new DOMDocument();
			$parentElement = $document;
		} else {
			$document = $parentElement->ownerDocument;
		}

		$root = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:' . 'Assertion');
		$parentElement->appendChild($root);

		/* Ugly hack to add another namespace declaration to the root element. */
		$root->setAttributeNS(SAML2_Const::NS_SAMLP, 'samlp:tmp', 'tmp');
		$root->removeAttributeNS(SAML2_Const::NS_SAMLP, 'tmp');
		$root->setAttributeNS(SAML2_Const::NS_XSI, 'xsi:tmp', 'tmp');
		$root->removeAttributeNS(SAML2_Const::NS_XSI, 'tmp');
		$root->setAttributeNS(SAML2_Const::NS_XS, 'xs:tmp', 'tmp');
		$root->removeAttributeNS(SAML2_Const::NS_XS, 'tmp');

		$root->setAttribute('ID', $this->id);
		$root->setAttribute('Version', '2.0');
		$root->setAttribute('IssueInstant', gmdate('Y-m-d\TH:i:s\Z', $this->issueInstant));

		$issuer = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:Issuer');
		$issuer->appendChild($document->createTextNode($this->issuer));
		$root->appendChild($issuer);

		$this->addSubject($root);
		$this->addConditions($root);
		$this->addAuthnStatement($root);
		$this->addAttributeStatement($root);

		if ($this->signatureKey !== NULL) {
			SAML2_Utils::insertSignature($this->signatureKey, $this->certificates, $root, $issuer->nextSibling);
		}

		return $root;
	}


	/**
	 * Add a Subject-node to the assertion.
	 *
	 * @param DOMElement $root  The assertion element we should add the subject to.
	 */
	private function addSubject(DOMElement $root) {

		if ($this->nameId === NULL) {
			/* We don't have anything to create a Subject node for. */
			return;
		}

		$subject = $root->ownerDocument->createElementNS(SAML2_Const::NS_SAML, 'saml:Subject');
		$root->appendChild($subject);

		SAML2_Utils::addNameId($subject, $this->nameId);

		$sc = $root->ownerDocument->createElementNS(SAML2_Const::NS_SAML, 'saml:SubjectConfirmation');
		$subject->appendChild($sc);

		$sc->setAttribute('Method', SAML2_Const::CM_BEARER);

		$scd = $root->ownerDocument->createElementNS(SAML2_Const::NS_SAML, 'saml:SubjectConfirmationData');
		$sc->appendChild($scd);

		if ($this->notOnOrAfter !== NULL) {
			$scd->setAttribute('NotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->notOnOrAfter));
		}
		if ($this->destination !== NULL) {
			$scd->setAttribute('Recipient', $this->destination);
		}
		if ($this->inResponseTo !== NULL) {
			$scd->setAttribute('InResponseTo', $this->inResponseTo);
		}
	}


	/**
	 * Add a Conditions-node to the assertion.
	 *
	 * @param DOMElement $root  The assertion element we should add the conditions to.
	 */
	private function addConditions(DOMElement $root) {

		$document = $root->ownerDocument;

		$conditions = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:Conditions');
		$root->appendChild($conditions);

		if ($this->notBefore !== NULL) {
			$conditions->setAttribute('NotBefore', gmdate('Y-m-d\TH:i:s\Z', $this->notBefore));
		}
		if ($this->notOnOrAfter !== NULL) {
			$conditions->setAttribute('NotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->notOnOrAfter));
		}

		if ($this->validAudiences !== NULL) {
			$ar = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:AudienceRestriction');
			$conditions->appendChild($ar);

			foreach ($this->validAudiences as $audience) {
				$a = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:Audience');
				$ar->appendChild($a);

				$a->appendChild($document->createTextNode($audience));
			}
		}
	}


	/**
	 * Add a AuthnStatement-node to the assertion.
	 *
	 * @param DOMElement $root  The assertion element we should add the authentication statement to.
	 */
	private function addAuthnStatement(DOMElement $root) {

		if ($this->authnContext === NULL) {
			/* No authentication context => no authentication statement. */
			return;
		}

		$document = $root->ownerDocument;

		$as = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:AuthnStatement');
		$root->appendChild($as);

		$as->setAttribute('AuthnInstant', gmdate('Y-m-d\TH:i:s\Z', $this->issueInstant));

		if ($this->sessionNotOnOrAfter !== NULL) {
			$as->setAttribute('SessionNotOnOrAfter', gmdate('Y-m-d\TH:i:s\Z', $this->sessionNotOnOrAfter));
		}
		if ($this->sessionIndex !== NULL) {
			$as->setAttribute('SessionIndex', $this->sessionIndex);
		}

		$ac = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:AuthnContext');
		$as->appendChild($ac);

		$accr = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:AuthnContextClassRef');
		$ac->appendChild($accr);

		$accr->appendChild($document->createTextNode($this->authnContext));
	}


	/**
	 * Add an AttributeStatement-node to the assertion.
	 *
	 * @param DOMElement $root  The assertion element we should add the subject to.
	 */
	private function addAttributeStatement(DOMElement $root) {

		if (empty($this->attributes)) {
			return;
		}

		$document = $root->ownerDocument;

		$attributeStatement = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:AttributeStatement');
		$root->appendChild($attributeStatement);

		foreach ($this->attributes as $name => $values) {
			$attribute = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:Attribute');
			$attributeStatement->appendChild($attribute);
			$attribute->setAttribute('Name', $name);

			if ($this->nameFormat !== SAML2_Const::NAMEFORMAT_UNSPECIFIED) {
				$attribute->setAttribute('NameFormat', $this->nameFormat);
			}

			foreach ($values as $value) {
				if (is_string($value)) {
					$type = 'xs:string';
				} elseif (is_int($value)) {
					$type = 'xs:integer';
				} else {
					$type = NULL;
				}

				$attributeValue = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:AttributeValue');
				$attribute->appendChild($attributeValue);
				if ($type !== NULL) {
					$attributeValue->setAttributeNS(SAML2_Const::NS_XSI, 'xsi:type', $type);
				}

				if ($value instanceof DOMNodeList) {
					for ($i = 0; $i < $value->length; $i++) {
						$node = $document->importNode($value->item($i), TRUE);
						$attributeValue->appendChild($node);
					}
				} else {
					$attributeValue->appendChild($document->createTextNode($value));
				}
			}
		}
	}

}

?>