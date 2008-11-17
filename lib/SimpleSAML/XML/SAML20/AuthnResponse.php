<?php

/**
 * An SAML 2.0 Authentication Response
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Olav Morken, UNINETT AS
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_SAML20_AuthnResponse extends SimpleSAML_XML_AuthnResponse {

	
	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';
	
	const TRANSIENT 	= 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
	const PERSISTENT 	= 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent';
	const EMAIL 		= 'urn:oasis:names:tc:SAML:2.0:nameid-format:email';

	/* Namespaces used in the XML representation of this object.
	 * TODO: Move these constants into a generic SAML2-class?
	 */
	const SAML2_ASSERT_NS = 'urn:oasis:names:tc:SAML:2.0:assertion';
	const SAML2_PROTOCOL_NS = 'urn:oasis:names:tc:SAML:2.0:protocol';


	/**
	 * This variable contains an XML validator for this message.
	 */
	private $validator = NULL;


	/**
	 * This varaible contains the entitiyid of the IdP which issued this message.
	 */
	private $issuer = NULL;


	/**
	 * This variable contains the NameID of this subject. It is an associative array with
	 * two keys:
	 * - 'Format'  The type of the NameID.
	 * - 'value'   Tha value of the NameID.
	 *
	 * This variable will be set by the processSubject function. A exception will be thrown if the response
	 * contains two different NameIDs.
	 */
	private $nameid = NULL;


	/**
	 * This variable contains the SessionIndex, as set by a AuthnStatement element in an assertion.
	 */
	private $sessionIndex = NULL;


	/**
	 * This associative array contains the attribute we extract from the response.
	 */
	private $attributes = array();


	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}


	/* The following methods aren't used anymore. They are included because it is required by inheritance.
	 * TODO: Remove them.
	 */
	public function validate() { throw new Exception('TODO!'); }
	public function createSession() { throw new Exception('TODO!'); }


	/**
	 * This function runs an xPath query on this authentication response.
	 *
	 * @param $query  The query which should be run.
	 * @param $node   The node which this query is relative to. If this node is NULL (the default)
	 *                then the query will be relative to the root of the response.
	 * @return Whatever DOMXPath::query returns.
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
		$xPath->registerNamespace("saml", self::SAML2_ASSERT_NS);
		$xPath->registerNamespace("samlp", self::SAML2_PROTOCOL_NS);
		$xPath->registerNamespace("ds", 'http://www.w3.org/2000/09/xmldsig#');

		return $xPath->query($query, $node);
	}


	/**
	 * This function checks if the user has added the given id to 'saml2.relaxvalidation'
	 * in the saml2-idp-remote configuration.
	 *
	 * @param $id   The id which identifies a part of the verification which may be relaxed.
	 * @return TRUE if this id is added to the list, FALSE if not.
	 */
	private function isValidationRelaxed($id) {

		assert('is_string($id)');
		assert('$this->issuer != NULL');

		/* Get the metadata of the issuer. */
		$md = $this->metadata->getMetaData($this->issuer, 'saml20-idp-remote');

		if(!array_key_exists('saml2.relaxvalidation', $md)) {
			/* The user hasn't added a saml2.relaxvalidation option. */
			return FALSE;
		}

		$rv = $md['saml2.relaxvalidation'];
		if(!is_array($rv)) {
			throw new Exception('saml2.relaxvalidation must be an array.');
		}

		return in_array($id, $rv, TRUE);
	}


	/**
	 * This function finds the status of this response.
	 */
	public function findstatus() {

		$status = $this->doXPathQuery('/samlp:Response/samlp:Status/samlp:StatusCode')->item(0);
		if($status != NULL) {
			return $status->getAttribute('Value');
		}
		throw new Exception('Unable to determine the status of this SAML2 AuthnResponse message.: ' . $this->getXML());
	}

	/**
	 * This function finds the issuer of this response. It will first search the Response element,
	 * and if it isn't found there, it will search all Assertion elements.
	 */
	public function findIssuer() {

		/* First check the Response element. */
		$issuer = $this->doXPathQuery('/samlp:Response/saml:Issuer')->item(0);
		if($issuer !== NULL) {
			return $issuer->textContent;
		}

		/* Then we search the Assertion elements. */
		$issuers = $this->doXPathQuery('/samlp:Response/saml:Assertion/saml:Issuer');

		if($issuers->length === 0) {
			throw new Exception('Unable to determine the issuer of this SAML2 AuthnResponse message.');
		}

		/* Since all Issuer elements should be equal in this version of simpleSAMLphp, we pick
		 * the first Issuer element we find.
		 */
		return $issuers->item(0)->textContent;
	}


	/**
	* This function decrypts the Assertion in the AuthnResponse
	* It throws an exception if the encryptAssertion for the remote idp is true and
	* the assertion is not encrypted
	* To Do: handle multible assertions
	*/
	private function decryptAssertion() {

		$dom = $this->getDOM();
		$encryptedassertion = $this->doXPathQuery('/samlp:Response/saml:EncryptedAssertion')->item(0);
		$objenc = new XMLSecEnc();
		$encData = $objenc->locateEncryptedData($dom);
		if ($encData) {	
			$spmd = $this->metadata->getMetaDataCurrent('saml20-sp-hosted');
			$spid = $this->metadata->getMetaDataCurrentEntityID('saml20-sp-hosted');
			$objenc->setNode($encData);
			$objenc->type = $encData->getAttribute("Type");

			$key = NULL;
			$objKey = $objenc->locateKey($encData);
			if ($objKey) {
				if ($objKeyInfo = $objenc->locateKeyInfo($objKey)) {
					if ($objKeyInfo->isEncrypted) {
						$objencKey = $objKeyInfo->encryptedCtx;
						$privatekey = SimpleSAML_Utilities::loadPrivateKey($spmd, TRUE);
						if(array_key_exists('password', $privatekey)) {
							$objKeyInfo->passphrase = $privatekey['password'];
						}
						$objKeyInfo->loadKey($privatekey['PEM']);
						$key = $objencKey->decryptKey($objKeyInfo);
					} else {
						$idpmd = $this->metadata->getMetaData($this->issuer, 'saml20-idp-remote');
						if (!isset( $idpmd['sharedkey'])) {
							throw new Exception("Shared key for decrypting assertion needed, but not specified for saml20-idp-remote id: " . $this->issuer);
						}
						$key = $idpmd['sharedkey'];
					}
				}
			}

			if (empty($objKey) || empty($key)) {
				throw new Exception("Error loading key to handle Decryption: >" . var_export($objKey, true));
			}
			$objKey->loadkey($key);
			
			$decrypted = $objenc->decryptNode($objKey, false);
	
			$newdoc = new DOMDocument();
			$newdoc->loadXML('<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$decrypted.'</root>');
			$importEnc = $encData->ownerDocument->importNode($newdoc->documentElement->firstChild, TRUE);
			$encryptedassertion->parentNode->replaceChild($importEnc, $encryptedassertion);
		} else {
			$md = $this->metadata->getMetaData($this->issuer, 'saml20-idp-remote');
			if (isset($md['assertion.encryption']) && $md['assertion.encryption']) {
				throw new Exception('Received unencrypted assertion from [' . $this->issuer . '] contrary to its metadata attribute [assertion.encryption]: ' . $md['assertion.encryption']);
			}
		}
	}

	/**
	 * Validate the signature in the given node.
	 *
	 * The node should either be a samlp:Response node, or a saml:Assertion node.
	 * An exception will be thrown if an error occurs during validation.
	 *
	 * @param $node  The node which contains the ds:Signature element.
	 */
	private function validateSignature($node) {

		/* Get the metadata of the issuer. */
		$md = $this->metadata->getMetaData($this->issuer, 'saml20-idp-remote');

		/* Load public key / certificate / certificate fingerprints. */
		$publickey = SimpleSAML_Utilities::loadPublicKey($md);

		/* Validate the signature. */
		$this->validator = new SimpleSAML_XML_Validator($node, 'ID', $publickey);

		if (!$publickey) {
			/* No validation of the certificate performed by the validator if $publickey isn't set. */
			if(array_key_exists('caFile', $md)) {

				/* Validation against a CA file. */
				$this->validator->validateCA($this->configuration->getPathValue('certdir') . $md['caFile']);
			} else {

				/* Misconfigured - neither publickey, certFingerprint or caFile given. */
				throw new Exception('Misconfigured saml20-idp-remote ' . $this->issuer . ':' .
					' Neither publickey, certFingerprint or caFile given.');
			}
		}
	}


	/**
	 * This function processes a Subject node. It will throw
	 * an Exception if the subject cannot be confirmed. On successful verification,
	 * the data stored about this subject will be saved.
	 */
	private function processSubject($subject) {

		/* We currently require urn:oasis:names:tc:SAML:2.0:cm:bearer subject confirmation. */
		$bearerValidated = false;

		/* Iterate over the SubjectConfirmation nodes, looking for it. */
		foreach($this->doXPathQuery('saml:SubjectConfirmation', $subject) as $subjectConfirmation) {
			$method = $subjectConfirmation->getAttributeNode('Method');
			if($method === NULL) {
				throw new Exception('SubjectConfirmation is missing the required Method attribute.');
			}
			if($method->value !== 'urn:oasis:names:tc:SAML:2.0:cm:bearer') {
				throw new Exception('Unhandled SubjectConfirmationData: ' . $method->value);
			}

			foreach ($this->doXPathQuery('saml:SubjectConfirmationData', $subjectConfirmation)
				as $subjectConfirmationData) {

				$recipient = $subjectConfirmationData->getAttributeNode('Recipient');
				if ($recipient !== NULL) {
					/* The Recipient attribute contains the address this assertion should
					 * be delivered to. Verify that it matches the current address.
					 */
					$recipient = $recipient->value;
					$currentURL = SimpleSAML_Utilities::selfURL();

					if ($recipient !== $currentURL) {
						throw new Exception('Recipient in assertion doesn\'t match the ' .
							' current URL. Recipient is "' . $recipient .
							'", current URL is "' . $currentURL . '".');
					}
				}

				/* TODO: Verify the rest of the subject. Missing are:
				 * - NotBefore & NotOnOrAfter
				 * - InResponseTo
				 * - Address
				 */
			}
		}


		/* We expect the subject node to contain a NameID element which identifies this subject. */
		$nameid = $this->doXPathQuery('saml:NameID', $subject)->item(0);
		if($nameid === NULL) {
			throw new Exception('Could not find the NameID node in a Subject node.');
		}

		$format = $nameid->getAttribute('Format');
		$value = $nameid->textContent;

		if($this->nameid === NULL) {
			/* We haven't saved a nameID earlier. Save it now. */
			$this->nameid = array('Format' => $format, 'value' => $value);
			return;
		}

		/* We have saved a nameID earlier. Verify that this nameID is equal. */
		if($this->nameid['Format'] !== $format || $this->nameid['value'] !== $value) {
			throw new Exception('Multiple assertions with different nameIDs is unsupported by simpleSAMLphp');
		}
	}


	/**
	 * This function processes a Conditions node. It will throw an exception if any of the conditions
	 * are invalid.
	 */
	private function processConditions($conditions) {

		/* First verify the NotBefore and NotOnOrAfter attributes if they are present. */
		$notBefore = $conditions->getAttribute("NotBefore");
		$notOnOrAfter = $conditions->getAttribute("NotOnOrAfter");
		if (! SimpleSAML_Utilities::checkDateConditions($notBefore, $notOnOrAfter)) {
			throw new Exception('Date check failed (between ' . $notBefore . ' and ' . $notOnOrAfter . ').' .
				' Check if the clocks on the SP and IdP are synchronized. Alternatively' .
				' you can get this message, when you move back in history or refresh an old page.');
		}


		if($this->doXPathQuery('Condition', $conditions)->length > 0) {
			if(!$this->isValidationRelaxed('unknowncondition')) {
				throw new Exception('A Conditions node in a SAML2 AuthnResponse contained a' .
					' Condition node. This is unsupported by simpleSAMLphp. To disable this' .
					' check, add \'unknowncondition\' to the \'saml2.relaxvalidation\' list in' .
					' \'saml2-idp-remote\'.');
			}
		}


		$spEntityId = $this->metadata->getMetaDataCurrentEntityID('saml20-sp-hosted');

		/* The specification says that every AudienceRestriction element must be valid, but only one
		 * Audience element in each AudienceRestriction element must be valid.
		 */
		foreach($this->doXPathQuery('AudienceRestriction', $conditions) as $ar) {

			$validAudience = false;
			foreach($this->doXPathQuery('Audience', $ar) as $a) {
				if($a->textContent === $spEntityId) {
					$validAudience = true;
				}
			}
			if(!$validAudience) {
				throw new Exception('Could not verify audience of SAML2 AuthnResponse.');
			}
		}

		/* We ignore OneTimeUse and ProxyRestriction conditions. */
	}


	/**
	 * This function processes a AuthnStatement node. It will throw an exception if the statement is
	 * invalid.
	 */
	private function processAuthnStatement($authnStatement) {
		/* Extract the SessionIndex. */
		$sessionIndex = $authnStatement->getAttributeNode('SessionIndex');
		if($sessionIndex !== NULL) {
			$sessionIndex = $sessionIndex->value;
			if($this->sessionIndex === NULL) {
				$this->sessionIndex = $sessionIndex;
			} elseif($this->sessionIndex !== $sessionIndex) {
				throw new Exception('Got two different session indexes in a SAML2 AuthnResponse.');
			}
		}
	}


	/**
	 * This function processes a AttributeStatement node.
	 */
	private function processAttributeStatement($attributeStatement) {

		$md = $this->metadata->getMetadata($this->issuer, 'saml20-idp-remote');
		$base64 = isset($md['base64attributes']) ? $md['base64attributes'] : false;

		foreach($this->doXPathQuery('saml:Attribute/saml:AttributeValue', $attributeStatement) as $attribute) {

			$name = $attribute->parentNode->getAttribute('Name');
			$value = $attribute->textContent;

			if(!array_key_exists($name, $this->attributes)) {
				$this->attributes[$name] = array();
			}

			if ($base64) {
				
				if ($name != 'jpegPhoto') {				
					foreach(explode('_', $value) AS $v) {
	
							$this->attributes[$name][] = base64_decode($v);
					}

				} else {
					$this->attributes[$name][] = $value;
					file_put_contents('/tmp/image2.jpg', $value);
				}
				
			} else {
				$this->attributes[$name][] = $value;
			}
		}
	}


	/**
	 * This function processes a Assertion node. It will throw an exception if the assertion is invalid.
	 */
	private function processAssertion($assertion) {

		/* Make sure that the assertion is signed. */
		if(!$this->validator->isNodeValidated($assertion)) {
			throw new Exception('A SAML2 AuthnResponse contained an Assertion which isn\'t verified by' .
				' the signature.');
		}

		$subject = $this->doXPathQuery('saml:Subject', $assertion)->item(0);
		if($subject === NULL) {
			if(!$this->isValidationRelaxed('nosubject')) {
				throw new Exception('Could not find required Subject information in a SAML2' .
					' AuthnResponse. To disable this check, add \'nosubject\' to the' .
					' \'saml2.relaxvalidation\' list in \'saml2-idp-remote\'.');
			}
		} else {
			$this->processSubject($subject);
		}

		$conditions = $this->doXPathQuery('saml:Conditions', $assertion)->item(0);
		if($conditions === NULL) {
			if(!$this->isValidationRelaxed('noconditions')) {
				throw new Exception('Could not find required Conditions node in a SAML2' .
					' AuthnResponse. To disable this check, add \'noconditions\' to the' .
					' \'saml2.relaxvalidation\' list in \'saml2-idp-remote\'.');
			}
		} else {
			$this->processConditions($conditions);
		}

		$authnStatement = $this->doXPathQuery('saml:AuthnStatement', $assertion)->item(0);
		if($authnStatement === NULL) {
			if(!$this->isValidationRelaxed('noauthnstatement')) {
				throw new Exception('Could not find required AuthnStatement node in a SAML2' .
					' AuthnResponse. To disable this check, add \'noauthnstatement\' to the' .
					' \'saml2.relaxvalidation\' list in \'saml2-idp-remote\'.');
			}
		} else {
			$this->processAuthnStatement($authnStatement);
		}

		$attributeStatement = $this->doXPathQuery('saml:AttributeStatement', $assertion)->item(0);
		if($attributeStatement === NULL) {
			if(!$this->isValidationRelaxed('noattributestatement')) {
				throw new Exception('Could not find required AttributeStatement in a SAML2' .
					' AuthnResponse. To disable this check, add \'noattributestatement\' to the' .
					' \'saml2.relaxvalidation\' list in \'saml2-idp-remote\'.');
			}
		} else {
			$this->processAttributeStatement($attributeStatement);
		}
	}


	/**
	 * This function processes a response message and adds information from it to the
	 * current session if it is valid.
	 *
	 * An exception will be thrown on a processing error. If the status code is something
	 * else than [...]:Success, FALSE will be returned, and no futher processing will occur.
	 *
	 * @return  TRUE on success. FALSE on an error response. The SAML 2.0 status code can
	 *          be retrieved with the findstatus() function.
	 */
	public function process() {
		$status = $this->findstatus();
		if ($status == 'urn:oasis:names:tc:SAML:2.0:status:Success' ) {
			/* Find the issuer of this response. */
			$this->issuer = $this->findIssuer();

			/* Check for signature in the saml:Response-element, and validate it if present. */
			$signature = $this->doXPathQuery('/samlp:Response/ds:Signature');
			if($signature->length > 0) {
				$this->validateSignature($signature->item(0)->parentNode);
			}
	
			$this->decryptAssertion();

			/* Check for signature in the saml:Assertion-element(s), and validate it if present. */
			$signature = $this->doXPathQuery('/samlp:Response/saml:Assertion/ds:Signature');
			if($signature->length > 0) {
				$this->validateSignature($signature->item(0)->parentNode);
			}

			/* Process all assertions. */
			$assertions = $this->doXPathQuery('/samlp:Response/saml:Assertion');
			foreach($assertions as $assertion) {
				$this->processAssertion($assertion);
			}
	
			if($this->nameid === NULL) {
				throw new Exception('No nameID found in AuthnResponse.');
			}
	
			return TRUE;
		} else {
			/* A different status code. */
			return FALSE;
		}
	}


	/**
	 * This function retrieves the ID of the request this response is a
	 * response to. This ID is stored in the InResponseTo attribute of the
	 * top level DOM element.
	 *
	 * @return The ID of the request this response is a response to, or NULL if
	 *  we don't know.
	 */
	public function getInResponseTo() {
		$dom = $this->getDOM();
		if($dom === NULL) {
			return NULL;
		}

		assert('$dom instanceof DOMDocument');

		$xPath = new DOMXpath($dom);
		$xPath->registerNamespace('samlp', self::SAML2_PROTOCOL_NS);

		$query = 'string(/samlp:Response/@InResponseTo)';
		$result = $xPath->evaluate($query);
		if($result === '') {
			return NULL;
		}

		return $result;
	}		


	/**
	 * Retrieve the attributes.
	 *
	 * This function should only be called after a successful call to the process-function.
	 *
	 * @return array  The attributes.
	 */
	public function getAttributes() {
		return $this->attributes;
	}


	/**
	 * Retrieve the NameID.
	 *
	 * The NameID will be returned as an associative array with two elements:
	 * - 'Format'  The format of the NameID.
	 * - 'value'   The valud of the NameID.
	 *
	 * This function should only be called after a successful call to the process-function.
	 *
	 * @return array  The NameID.
	 */
	public function getNameID() {
		assert('is_array($this->nameid)');
		assert('array_key_exists("Format", $this->nameid)');
		assert('array_key_exists("value", $this->nameid)');

		return $this->nameid;
	}


	/**
	 * Retrieve the session index.
	 *
	 * This function retrieves the SessionIndex of this authentication response.
	 *
	 * This function should only be called after a successful call to the process-function.
	 *
	 * @return string  The SessionIndex of this response.
	 */
	public function getSessionIndex() {
		assert('is_string($this->sessionIndex)');

		return $this->sessionIndex;
	}


	/**
	 * Retrieve the issuer.
	 *
	 * This function retrieves the Issuer of this authentication response.
	 *
	 * This function should only be called after a successful call to the process-function.
	 *
	 * @return string  The entity id of the issuer of this response.
	 */
	public function getIssuer() {
		assert('is_string($this->issuer)');

		return $this->issuer;
	}


	/**
	 * This function generates an AuthenticationResponse
	 *
	 *  @param $idpentityid   entityid of IdP
	 *  @param $spentityid    entityid of SP
	 *  @param $inresponseto  the ID of the request, that these message is an response to.
	 *  @param $nameid        the NameID of the user (an array)
	 *  @param $attributes    A two level array of multivalued attributes, where the first level
	 *   index is the attribute name.
	 *
	 *  @return AuthenticationResponse as string
	 */
	public function generate($idpentityid, $spentityid, $inresponseto, $nameid, $attributes, $status = 'Success') {
		
		/**
		 * Retrieving metadata for the two specific entity IDs.
		 */
		$idpmd 	= $this->metadata->getMetaData($idpentityid, 'saml20-idp-hosted');
		$spmd 	= $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$issuer = $idpentityid;
		$destination = $spmd['AssertionConsumerService'];
		
		/**
		 * Generating IDs and timestamps.
		 */
		$id = SimpleSAML_Utilities::generateID();
		$issueInstant = SimpleSAML_Utilities::generateTimestamp();
		$assertionExpire = SimpleSAML_Utilities::generateTimestamp(time() + 60 * 5);# 5 minutes
		$notBefore = SimpleSAML_Utilities::generateTimestamp(time() - 30);

		$assertionid = SimpleSAML_Utilities::generateID();

		$session = SimpleSAML_Session::getInstance();
		$sessionindex = $session->getSessionIndex();

		
		/**
		 * Handling attributes.
		 */
		$base64 = isset($spmd['base64attributes']) ? $spmd['base64attributes'] : false;
		$nameidformat = isset($spmd['NameIDFormat']) ? $spmd['NameIDFormat'] : 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
		$spnamequalifier = isset($spmd['SPNameQualifier']) ? $spmd['SPNameQualifier'] : $spmd['entityid'];
		
		// Attribute Name Format handling. Priority is 1) SP metadata 2) IdP metadata 3) default setting
		$attributeNameFormat = 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic';
		if (isset($spmd['AttributeNameFormat']))
			$attributeNameFormat = $spmd['AttributeNameFormat'];
		elseif (isset($idpmd['AttributeNameFormat']))
			$attributeNameFormat = $idpmd['AttributeNameFormat'];

		
		$encodedattributes = '';
		foreach ($attributes AS $name => $values) {
			$encodedattributes .= self::enc_attribute($name, $values, $base64, $attributeNameFormat);
		}
		$attributestatement = '<saml:AttributeStatement>' . $encodedattributes . '</saml:AttributeStatement>';
		
		$sendattributes = isset($spmd['simplesaml.attributes']) ? $spmd['simplesaml.attributes'] : true;
		
		if (!$sendattributes) 
			$attributestatement = '';
		
		
		
		/**
		 * Handling NameID
		 */
		if ( ($nameidformat == self::EMAIL) or ($nameidformat == self::PERSISTENT) ) {
			$nameIdValue = $attributes[$spmd['simplesaml.nameidattribute']][0];
		} else {
			$nameIdValue = SimpleSAML_Utilities::generateID();
		}
		$nameIdData = array('Format' => $nameidformat, 'value' => $nameIdValue);
		$session->setSessionNameId('saml20-sp-remote', $spentityid, $nameIdData);
		$nameid = $this->generateNameID($nameidformat, $nameIdValue, $spnamequalifier);

		$assertion = "";
		if ($status === 'Success') {
			$assertion = '<saml:Assertion Version="2.0"
		ID="' . $assertionid . '" IssueInstant="' . $issueInstant . '">
		<saml:Issuer>' . htmlspecialchars($issuer) . '</saml:Issuer>
		<saml:Subject>
			' . $nameid . ' 
			<saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
				<saml:SubjectConfirmationData NotOnOrAfter="' . $assertionExpire . '"
					InResponseTo="' . htmlspecialchars($inresponseto). '"
					Recipient="' . htmlspecialchars($destination) . '"/>
			</saml:SubjectConfirmation>
		</saml:Subject>
		<saml:Conditions NotBefore="' . $notBefore. '" NotOnOrAfter="' . $assertionExpire. '">
            <saml:AudienceRestriction>
                <saml:Audience>' . htmlspecialchars($spentityid) . '</saml:Audience>
            </saml:AudienceRestriction>
		</saml:Conditions> 
		<saml:AuthnStatement AuthnInstant="' . $issueInstant . '"
			SessionIndex="' . htmlspecialchars($sessionindex) . '">
			<saml:AuthnContext>
				<saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
			</saml:AuthnContext>
        </saml:AuthnStatement>
        ' . $attributestatement. '
    </saml:Assertion>';
			$statusCode = '<samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>';
		} else {
			$statusCode = '<samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Responder">
				<samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:' . $status . '"/>
				</samlp:StatusCode>';
		}
		
		
		/**
		 * Generating the response.
		 */
		$authnResponse = '<samlp:Response 
			xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" 
			xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" 
			xmlns:xs="http://www.w3.org/2001/XMLSchema"
			xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
			ID="' . $id . '"
			InResponseTo="' . htmlspecialchars($inresponseto) . '" Version="2.0"
			IssueInstant="' . $issueInstant . '"
			Destination="' . htmlspecialchars($destination) . '">
			<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . htmlspecialchars($issuer) . '</saml:Issuer>
			<samlp:Status>' . $statusCode .	'</samlp:Status>'
			. $assertion . 
			'</samlp:Response>';

		return $authnResponse;
	}


	private function generateNameID($type = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient', 
			$value = 'anonymous', $spnamequalifier = null) {
		
		$spnamequalifiertext = '';
		if (!empty($spnamequalifier)) {
			$spnamequalifiertext = ' SPNameQualifier="' . htmlspecialchars($spnamequalifier) . '"';
		}

		if ($value == null) {
			throw new Exception("NameID value is empty probably because of a configuration error (ie. the attribute that was configured as the simplesaml.nameidattribute setting was not found).");
		}
		
		if ($type == self::EMAIL) {
			return '<saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress"' . 
				$spnamequalifiertext . '>' . htmlspecialchars($value) . '</saml:NameID>';

		} else {
			return '<saml:NameID Format="' . $type . '"' . 
				$spnamequalifiertext. '>' . htmlspecialchars($value). '</saml:NameID>';
		}
		
	}

	
	/**
	 * This function converts an array of attribute values into an
	 * encoded saml:Attribute element which should go into the
	 * AuthnResponse. The data can optionally be base64 encoded.
	 *
	 *  @param $name      Name of this attribute.
	 *  @param $values    Array with the values of this attribute.
	 *  @param $base64    Enable base64 encoding of attribute values.
	 *  @param $attributeNameFormat		Which attribute name format to use. (See SAML 2.0 Spec for details)
	 *
	 *  @return String containing the encoded saml:attribute value for this
	 *  attribute.
	 */
	private static function enc_attribute($name, $values, $base64 = false, $attributeNameFormat) {
		assert(is_array($values));

		// Default: urn:oasis:names:tc:SAML:2.0:attrname-format:basic
		$ret = '<saml:Attribute NameFormat="' . htmlspecialchars($attributeNameFormat) . '"  Name="' . htmlspecialchars($name) . '">';

		foreach($values as $value) {
			if($base64) {
				$text = base64_encode($value);
			} else {
				$text = htmlspecialchars($value);
			}
			
			$xsiType = '';
			if ($attributeNameFormat == 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic')
				$xsiType = ' xsi:type="xs:string"';
			

			$ret .= '<saml:AttributeValue' . $xsiType . '>' . $text . '</saml:AttributeValue>';
		}

		$ret .= '</saml:Attribute>';

		return $ret;
	}
	
	
}

?>