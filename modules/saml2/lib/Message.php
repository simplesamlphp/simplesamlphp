<?php


/**
 * Common code for building SAML 2 messages based on the
 * available metadata.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_saml2_Message {

	/**
	 * Retrieve the destination we should send the message to.
	 *
	 * This will return a debug endpoint if we have debug enabled. If debug
	 * is disabled, NULL is returned, in which case the default destination
	 * will be used.
	 *
	 * @return string|NULL  The destination the message should be delivered to.
	 */
	public static function getDebugDestination() {

		$globalConfig = SimpleSAML_Configuration::getInstance();
		if (!$globalConfig->getBoolean('debug', FALSE)) {
			return NULL;
		}

		return SimpleSAML_Module::getModuleURL('saml2/debug.php');
	}


	/**
	 * Add signature key and and senders certificate to an element (Message or Assertion).
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient.
	 * @param SAML2_Message $element  The element we should add the data to.
	 */
	public static function addSign(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata, SAML2_SignedElement $element) {

		$srcMetadata = $srcMetadata->toArray();

		$keyArray = SimpleSAML_Utilities::loadPrivateKey($srcMetadata, TRUE);
		$certArray = SimpleSAML_Utilities::loadPublicKey($srcMetadata, FALSE);

		$privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
		if (array_key_exists('password', $keyArray)) {
			$privateKey->passphrase = $keyArray['password'];
		}
		$privateKey->loadKey($keyArray['PEM'], FALSE);

		$element->setSignatureKey($privateKey);

		if ($certArray === NULL) {
			/* We don't have a certificate to add. */
			return;
		}

		if (!array_key_exists('PEM', $certArray)) {
			/* We have a public key with only a fingerprint. */
			return;
		}

		$element->setCertificates(array($certArray['PEM']));
	}


	/**
	 * Add signature key and and senders certificate to message.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient.
	 * @param SAML2_Message $message  The message we should add the data to.
	 */
	private static function addRedirectSign(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata, SAML2_message $message) {

		$signingEnabled = $dstMetadata->getBoolean('redirect.sign', NULL);
		if ($signingEnabled === NULL) {
			$signingEnabled = $srcMetadata->getBoolean('redirect.sign', FALSE);
		}
		if (!$signingEnabled) {
			return;
		}

		self::addSign($srcMetadata, $dstMetadata, $message);
	}


	/**
	 * Find the certificate used to sign a message or assertion.
	 *
	 * An exception is thrown if we are unable to locate the certificate.
	 *
	 * @param array $certFingerprints  The fingerprints we are looking for.
	 * @param array $certificates  Array of certificates.
	 * @return string  Certificate, in PEM-format.
	 */
	private static function findCertificate(array $certFingerprints, array $certificates) {

		$candidates = array();

		foreach ($certificates as $cert) {
			$fp = strtolower(sha1(base64_decode($cert)));
			if (!in_array($fp, $certFingerprints, TRUE)) {
				$candidates[] = $fp;
				continue;
			}

			/* We have found a matching fingerprint. */
			$pem = "-----BEGIN CERTIFICATE-----\n" .
				chunk_split($cert, 64) .
				"-----END CERTIFICATE-----\n";
			return $pem;
		}

		$candidates = "'" . implode("', '", $candidates) . "'";
		$fps = "'" .  implode("', '", $certFingerprints) . "'";
		throw new SimpleSAML_Error_Exception('Unable to find a certificate matching the configured ' .
			'fingerprint. Candidates: ' . $candidates . '; certFingerprint: ' . $fps . '.');
	}


	/**
	 * Check the signature on a SAML2 message or assertion.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SAML2_SignedElement $element  Either a SAML2_Response or a SAML2_Assertion.
	 */
	public static function checkSign(SimpleSAML_Configuration $srcMetadata, SAML2_SignedElement $element) {

		$certificates = $element->getCertificates();
		SimpleSAML_Logger::debug('Found ' . count($certificates) . ' certificates in ' . get_class($element));

		/* Find the certificate that should verify signatures by this entity. */
		$certArray = SimpleSAML_Utilities::loadPublicKey($srcMetadata->toArray(), FALSE);
		if ($certArray !== NULL) {
			if (array_key_exists('PEM', $certArray)) {
				$pemCert = $certArray['PEM'];
			} else {
				/*
				 * We don't have the full certificate stored. Try to find it
				 * in the message or the assertion instead.
				 */
				if (count($certificates) === 0) {
					/* We need the full certificate in order to match it against the fingerprint. */
					SimpleSAML_Logger::debug('No certificate in message when validating against fingerprint.');
					return FALSE;
				}

				$certFingerprints = $certArray['certFingerprint'];
				if (count($certFingerprints) === 0) {
					/* For some reason, we have a certFingerprint entry without any fingerprints. */
					throw new SimpleSAML_Error_Exception('certFingerprint array was empty.');
				}

				$pemCert = self::findCertificate($certFingerprints, $certificates);
			}
		} else {
			/* Attempt CA validation. */
			$caFile = $srcMetadata->getString('caFile', NULL);
			if ($caFile === NULL) {
				throw new SimpleSAML_Error_Exception(
					'Missing certificate in metadata for ' .
					var_export($srcMetadata->getString('entityid'), TRUE));
			}
			$globalConfig = SimpleSAML_Configuration::getInstance();
			$caFile = $globalConfig->getPathValue('certdir', 'cert/') . $caFile;

			if (count($certificates) === 0) {
				/* We need the full certificate in order to check it against the CA file. */
				SimpleSAML_Logger::debug('No certificate in message when validating with CA.');
				return FALSE;
			}

			/* We assume that it is the first certificate that was used to sign the message. */
			$pemCert = "-----BEGIN CERTIFICATE-----\n" .
				chunk_split($certificates[0], 64) .
				"-----END CERTIFICATE-----\n";

			SimpleSAML_Utilities::validateCA($pemCert, $caFile);
		}


		/* Extract the public key from the certificate for validation. */
		$key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
		$key->loadKey($pemCert);

		/*
		 * Make sure that we have a valid signature on either the response
		 * or the assertion.
		 */
		return $element->validate($key);
	}


	/**
	 * Check signature on a SAML2 message if enabled.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient.
	 * @param SAML2_Message $message  The message we should check the signature on.
	 */
	public static function validateMessage(
		SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata,
		SAML2_Message $message
		) {

		$enabled = $srcMetadata->getBoolean('redirect.validate', NULL);
		if ($enabled === NULL) {
			$enabled = $dstMetadata->getBoolean('redirect.validate', FALSE);
		}

		if (!$enabled) {
			return;
		}

		if (!self::checkSign($srcMetadata, $message)) {
			throw new SimpleSAML_Error_Exception('Validation of received messages enabled, but no signature found on message.');
		}
	}


	/**
	 * Retrieve the decryption key from metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 * @return XMLSecurityKey  The decryption key.
	 */
	private static function getDecryptionKey(SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata) {

		$sharedKey = $srcMetadata->getString('sharedkey', NULL);
		if ($sharedKey !== NULL) {
			$key = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
			$key->loadKey($sharedKey);
		} else {
			/* Find the private key we should use to decrypt messages to this SP. */
			$keyArray = SimpleSAML_Utilities::loadPrivateKey($dstMetadata->toArray(), TRUE);
			if (!array_key_exists('PEM', $keyArray)) {
				throw new Exception('Unable to locate key we should use to decrypt the message.');
			}

			/* Extract the public key from the certificate for encryption. */
			$key = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'private'));
			if (array_key_exists('password', $keyArray)) {
				$key->passphrase = $keyArray['password'];
			}
			$key->loadKey($keyArray['PEM']);
		}

		return $key;
	}


	/**
	 * Encrypt an assertion.
	 *
	 * This function takes in a SAML2_Assertion and encrypts it if encryption of
	 * assertions are enabled in the metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 * @param SAML2_Assertion $assertion  The assertion we are encrypting.
	 * @return SAML2_Assertion|SAML2_EncryptedAssertion  The assertion.
	 */
	public static function encryptAssertion(SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata, SAML2_Assertion $assertion) {

		$encryptAssertion = $dstMetadata->getBoolean('assertion.encryption', NULL);
		if ($encryptAssertion === NULL) {
			$encryptAssertion = $srcMetadata->getBoolean('assertion.encryption', FALSE);
		}
		if (!$encryptAssertion) {
			/* We are _not_ encrypting this assertion, and are therefore done. */
			return $assertion;
		}


		$sharedKey = $dstMetadata->getString('sharedkey', NULL);
		if ($sharedKey !== NULL) {
			$key = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
			$key->loadKey($sharedKey);
		} else {
			/* Find the certificate that we should use to encrypt messages to this SP. */
			$certArray = SimpleSAML_Utilities::loadPublicKey($dstMetadata->toArray(), TRUE);
			if (!array_key_exists('PEM', $certArray)) {
				throw new Exception('Unable to locate key we should use to encrypt the assertionst ' .
					'to the SP: ' . var_export($dstMetadata->getString('entityid'), TRUE) . '.');
			}

			$pemCert = $certArray['PEM'];

			/* Extract the public key from the certificate for encryption. */
			$key = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'public'));
			$key->loadKey($pemCert);
		}

		$ea = new SAML2_EncryptedAssertion();
		$ea->setAssertion($assertion, $key);
		return $ea;
	}


	/**
	 * Decrypt an assertion.
	 *
	 * This function takes in a SAML2_Assertion and decrypts it if it is encrypted.
	 * If it is unencrypted, and encryption is enabled in the metadata, an exception
	 * will be throws.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 * @param SAML2_Assertion|SAML2_EncryptedAssertion $assertion  The assertion we are decrypting.
	 * @return SAML2_Assertion  The assertion.
	 */
	private static function decryptAssertion(SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata, $assertion) {
		assert('$assertion instanceof SAML2_Assertion || $assertion instanceof SAML2_EncryptedAssertion');

		if ($assertion instanceof SAML2_Assertion) {
			$encryptAssertion = $srcMetadata->getBoolean('assertion.encryption', NULL);
			if ($encryptAssertion === NULL) {
				$encryptAssertion = $dstMetadata->getBoolean('assertion.encryption', FALSE);
			}
			if ($encryptAssertion) {
				/* The assertion was unencrypted, but we have encryption enabled. */
				throw new Exception('Received unencrypted assertion, but encryption was enabled.');
			}

			return $assertion;
		}

		try {
			$key = self::getDecryptionKey($srcMetadata, $dstMetadata);
		} catch (Exception $e) {
			throw new SimpleSAML_Error_Exception('Error decrypting assertion: ' . $e->getMessage());
		}

		return $assertion->getAssertion($key);
	}


	/**
	 * Retrieve the status code of a response as a sspmod_saml2_error.
	 *
	 * @param SAML2_StatusResponse $response  The response.
	 * @return sspmod_saml2_Error  The error.
	 */
	public static function getResponseError(SAML2_StatusResponse $response) {

		$status = $response->getStatus();
		return new sspmod_saml2_Error($status['Code'], $status['SubCode'], $status['Message']);
	}


	/**
	 * Build an authentication request based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $spMetadata  The metadata of the service provider.
	 * @param SimpleSAML_Configuration $idpMetadata  The metadata of the identity provider.
	 */
	public static function buildAuthnRequest(SimpleSAML_Configuration $spMetadata, SimpleSAML_Configuration $idpMetadata) {

		$ar = new SAML2_AuthnRequest();

		if ($spMetadata->hasValue('NameIDPolicy')) {
			$nameIdPolicy = $spMetadata->getString('NameIDPolicy', NULL);
		} else {
			$nameIdPolicy = $spMetadata->getString('NameIDFormat', SAML2_Const::NAMEID_TRANSIENT);
		}

		if ($nameIdPolicy !== NULL) {
			$ar->setNameIdPolicy(array(
				'Format' => $nameIdPolicy,
				'AllowCreate' => TRUE,
			));
		}

		$ar->setIssuer($spMetadata->getString('entityid'));
		$ar->setDestination($idpMetadata->getString('SingleSignOnService'));

		$ar->setForceAuthn($spMetadata->getBoolean('ForceAuthn', FALSE));
		$ar->setIsPassive($spMetadata->getBoolean('IsPassive', FALSE));

		if ($spMetadata->hasValue('AuthnContextClassRef')) {
			$accr = $spMetadata->getArrayizeString('AuthnContextClassRef');
			$ar->setRequestedAuthnContext(array('AuthnContextClassRef' => $accr));
		}

		self::addRedirectSign($spMetadata, $idpMetadata, $ar);

		return $ar;
	}


	/**
	 * Build a logout request based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstpMetadata  The metadata of the recipient.
	 */
	public static function buildLogoutRequest(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata) {

		$lr = new SAML2_LogoutRequest();

		$lr->setIssuer($srcMetadata->getString('entityid'));
		$lr->setDestination($dstMetadata->getString('SingleLogoutService'));

		self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

		return $lr;
	}


	/**
	 * Build a logout response based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstpMetadata  The metadata of the recipient.
	 */
	public static function buildLogoutResponse(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata) {

		$lr = new SAML2_LogoutResponse();

		$lr->setIssuer($srcMetadata->getString('entityid'));

		$dst = $dstMetadata->getString('SingleLogoutServiceResponse', NULL);
		if ($dst === NULL) {
			$dst = $dstMetadata->getString('SingleLogoutService');
		}
		$lr->setDestination($dst);

		self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

		return $lr;
	}


	/**
	 * Calculate the NameID value that should be used.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 * @param array $attributes  The attributes of the user
	 * @return string  The NameID value.
	 */
	private static function generateNameIdValue(SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata, array $attributes) {

		$attribute = $dstMetadata->getString('simplesaml.nameidattribute', NULL);
		if ($attribute === NULL) {
			$attribute = $srcMetadata->getString('simplesaml.nameidattribute', NULL);
			if ($attribute === NULL) {
				SimpleSAML_Logger::error('simplesaml.nameidattribute not set in either SP metadata or IdP metadata');
				return SimpleSAML_Utilities::generateID();
			}
		}

		if (!array_key_exists($attribute, $attributes)) {
			SimpleSAML_Logger::error('Unable to add NameID: Missing ' . var_export($attribute, TRUE) .
				' in the attributes of the user.');
			return SimpleSAML_Utilities::generateID();
		}

		return $attributes[$attribute][0];
	}


	/**
	 * Helper function for encoding attributes.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 * @param array $attributes  The attributes of the user
	 * @return array  The encoded attributes.
	 */
	private static function encodeAttributes(SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata, array $attributes) {

		$base64Attributes = $dstMetadata->getBoolean('base64attributes', NULL);
		if ($base64Attributes === NULL) {
			$base64Attributes = $srcMetadata->getBoolean('base64attributes', FALSE);
		}

		if ($base64Attributes) {
			$defaultEncoding = 'base64';
		} else {
			$defaultEncoding = 'string';
		}

		$srcEncodings = $srcMetadata->getArray('attributeencodings', array());
		$dstEncodings = $dstMetadata->getArray('attributeencodings', array());

		/*
		 * Merge the two encoding arrays. Encodings specified in the target metadata
		 * takes precedence over the source metadata.
		 */
		$encodings = array_merge($srcEncodings, $dstEncodings);

		$ret = array();
		foreach ($attributes as $name => $values) {
			$ret[$name] = array();
			if (array_key_exists($name, $encodings)) {
				$encoding = $encodings[$name];
			} else {
				$encoding = $defaultEncoding;
			}

			foreach ($values as $value) {
				switch ($encoding) {
				case 'string':
					$value = (string)$value;
					break;
				case 'base64':
					$value = base64_encode((string)$value);
					break;
				case 'raw':
					if (is_string($value)) {
						$doc = new DOMDocument();
						$doc->loadXML('<root>' . $value . '</root>');
						$value = $doc->firstChild->childNodes;
					}
					assert('$value instanceof DOMNodeList');
					break;
				default:
					throw new SimpleSAML_Error_Exception('Invalid encoding for attribute ' .
						var_export($name, TRUE) . ': ' . var_export($encoding, TRUE));
				}
				$ret[$name][] = $value;
			}
		}

		return $ret;
	}


	/**
	 * Build an assertion based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 * @param array $attributes  The attributes of the user
	 * @return SAML2_Assertion  The assertion.
	 */
	public static function buildAssertion(SimpleSAML_Configuration $srcMetadata,
		SimpleSAML_Configuration $dstMetadata, array $attributes, $consumerURL) {

		$signAssertion = $dstMetadata->getBoolean('saml20.sign.assertion', NULL);
		if ($signAssertion === NULL) {
			$signAssertion = $srcMetadata->getBoolean('saml20.sign.assertion', TRUE);
		}

		$config = SimpleSAML_Configuration::getInstance();

		$a = new SAML2_Assertion();
		if ($signAssertion) {
			self::addSign($srcMetadata, $dstMetadata, $a);
		}

		$a->setIssuer($srcMetadata->getString('entityid'));
		$a->setDestination($consumerURL);
		$a->setValidAudiences(array($dstMetadata->getString('entityid')));

		$a->setNotBefore(time() - 30);

		$assertionLifetime = $dstMetadata->getInteger('assertion.lifetime', NULL);
		if ($assertionLifetime === NULL) {
			$assertionLifetime = $srcMetadata->getInteger('assertion.lifetime', 300);
		}
		$a->setNotOnOrAfter(time() + $assertionLifetime);

		$a->setAuthnContext(SAML2_Const::AC_PASSWORD);

		$sessionLifetime = $config->getInteger('session.duration', 8*60*60);
		$a->setSessionNotOnOrAfter(time() + $sessionLifetime);

		$session = SimpleSAML_Session::getInstance();
		$sessionIndex = $session->getSessionIndex();
		$a->setSessionIndex($sessionIndex);

		/* Add attributes. */

		if ($dstMetadata->getBoolean('simplesaml.attributes', TRUE)) {
			$attributeNameFormat = $dstMetadata->getString('AttributeNameFormat', NULL);
			if ($attributeNameFormat === NULL) {
				$attributeNameFormat = $srcMetadata->getString('AttributeNameFormat',
					'urn:oasis:names:tc:SAML:2.0:attrname-format:basic');
			}
			$a->setAttributeNameFormat($attributeNameFormat);
			$attributes = self::encodeAttributes($srcMetadata, $dstMetadata, $attributes);
			$a->setAttributes($attributes);
		}


		/* Generate the NameID for the assertion. */

		$nameIdFormat = $dstMetadata->getString('NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

		$spNameQualifier = $dstMetadata->getString('SPNameQualifier', NULL);
		if ($spNameQualifier === NULL) {
			$spNameQualifier = $dstMetadata->getString('entityid');
		}

		if ($nameIdFormat === SAML2_Const::NAMEID_TRANSIENT) {
			$nameIdValue = SimpleSAML_Utilities::generateID();
		} else {
			$nameIdValue = self::generateNameIdValue($srcMetadata, $dstMetadata, $attributes);
		}

		$a->setNameId(array(
			'Format' => $nameIdFormat,
			'Value' => $nameIdValue,
			'SPNameQualifier' => $spNameQualifier,
			));

		return $a;
	}


	/**
	 * Build a authentication response based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender (IdP).
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient (SP).
	 */
	public static function buildResponse(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata, $consumerURL) {

		$signResponse = $dstMetadata->getBoolean('saml20.sign.response', NULL);
		if ($signResponse === NULL) {
			$signResponse = $srcMetadata->getBoolean('saml20.sign.response', TRUE);
		}

		$r = new SAML2_Response();

		$r->setIssuer($srcMetadata->getString('entityid'));
		$r->setDestination($consumerURL);

		if ($signResponse) {
			self::addSign($srcMetadata, $dstMetadata, $r);
		}

		return $r;
	}


	/**
	 * Process a response message.
	 *
	 * If the response is an error response, we will throw a sspmod_saml2_Error
	 * exception with the error.
	 *
	 * @param SimpleSAML_Configuration $spMetadata  The metadata of the service provider.
	 * @param SimpleSAML_Configuration $idpMetadata  The metadata of the identity provider.
	 * @param SAML2_Response $response  The response.
	 * @return SAML2_Assertion  The assertion in the response, if it is valid.
	 */
	public static function processResponse(
		SimpleSAML_Configuration $spMetadata, SimpleSAML_Configuration $idpMetadata,
		SAML2_Response $response
		) {

		if (!$response->isSuccess()) {
			throw self::getResponseError($response);
		}

		/*
		 * When we get this far, the response itself is valid.
		 * We only need to check signatures and conditions of the response.
		 */

		$assertion = $response->getAssertions();
		if (empty($assertion)) {
			throw new SimpleSAML_Error_Exception('No assertions found in response from IdP.');
		} elseif (count($assertion) > 1) {
			throw new SimpleSAML_Error_Exception('More than one assertion found in response from IdP.');
		}
		$assertion = $assertion[0];

		$assertion = self::decryptAssertion($idpMetadata, $spMetadata, $assertion);

		if (!self::checkSign($idpMetadata, $assertion)) {
			if (!self::checkSign($idpMetadata, $response)) {
				throw new SimpleSAML_Error_Exception('Neither the assertion nor the response was signed.');
			}
		}
		/* At least one valid signature found. */


		/* Make sure that some fields in the assertion matches the same fields in the message. */

		$asrtInResponseTo = $assertion->getInResponseTo();
		$msgInResponseTo = $response->getInResponseTo();
		if ($asrtInResponseTo !== NULL && $msgInResponseTo !== NULL) {
			if ($asrtInResponseTo !== $msgInResponseTo) {
				throw new SimpleSAML_Error_Exception('InResponseTo in assertion did not match InResponseTo in message.');
			}
		}

		$asrtDestination = $assertion->getDestination();
		$msgDestination = $response->getDestination();
		if ($asrtDestination !== NULL && $msgDestination !== NULL) {
			if ($asrtDestination !== $msgDestination) {
				throw new SimpleSAML_Error_Exception('Destination in assertion did not match Destination in message.');
			}
		}


		/* Check various properties of the assertion. */

		$notBefore = $assertion->getNotBefore();
		if ($notBefore > time() + 60) {
			throw new SimpleSAML_Error_Exception('Received an assertion that is valid in the future. Check clock synchronization on IdP and SP.');
		}

		$notOnOrAfter = $assertion->getNotOnOrAfter();
		if ($notOnOrAfter <= time() - 60) {
			throw new SimpleSAML_Error_Exception('Received an assertion that has expired. Check clock synchronization on IdP and SP.');
		}

		$sessionNotOnOrAfter = $assertion->getSessionNotOnOrAfter();
		if ($sessionNotOnOrAfter !== NULL && $sessionNotOnOrAfter <= time() - 60) {
			throw new SimpleSAML_Error_Exception('Received an assertion with a session that has expired. Check clock synchronization on IdP and SP.');
		}

		$destination = $assertion->getDestination();
		$currentURL = SimpleSAML_Utilities::selfURLNoQuery();
		if ($destination !== $currentURL) {
			throw new Exception('Recipient in assertion doesn\'t match the current URL. Recipient is "' .
				$destination . '", current URL is "' . $currentURL . '".');
		}

		$validAudiences = $assertion->getValidAudiences();
		if ($validAudiences !== NULL) {
			$spEntityId = $spMetadata->getString('entityid');
			if (!in_array($spEntityId, $validAudiences, TRUE)) {
				$candidates = '[' . implode('], [', $validAudiences) . ']';
				throw new SimpleSAML_Error_Exception('This SP [' . $spEntityId . ']  is not a valid audience for the assertion. Candidates were: ' . $candidates);
			}
		}

		/* As far as we can tell, the assertion is valid. */


		/* Maybe we need to base64 decode the attributes in the assertion? */
		if ($idpMetadata->getBoolean('base64attributes', FALSE)) {
			$attributes = $assertion->getAttributes();
			$newAttributes = array();
			foreach ($attributes as $name => $values) {
				$newAttributes[$name] = array();
				foreach ($values as $value) {
					foreach(explode('_', $value) AS $v) {
						$newAttributes[$name][] = base64_decode($v);
					}
				}
			}
			$assertion->setAttributes($newAttributes);
		}


		/* Decrypt the NameID element if it is encrypted. */
		if ($assertion->isNameIdEncrypted()) {
			try {
				$key = self::getDecryptionKey($idpMetadata, $spMetadata);
			} catch (Exception $e) {
				throw new SimpleSAML_Error_Exception('Error decrypting NameID: ' . $e->getMessage());
			}

			$assertion->decryptNameId($key);
		}

		return $assertion;
	}


}

?>