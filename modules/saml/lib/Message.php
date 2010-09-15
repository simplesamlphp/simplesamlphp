<?php


/**
 * Common code for building SAML 2 messages based on the
 * available metadata.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_saml_Message {

	/**
	 * Add signature key and and senders certificate to an element (Message or Assertion).
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstMetadata  The metadata of the recipient.
	 * @param SAML2_Message $element  The element we should add the data to.
	 */
	public static function addSign(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata, SAML2_SignedElement $element) {

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

		/* Find the public key that should verify signatures by this entity. */
		$keys = $srcMetadata->getPublicKeys('signing');
		if ($keys !== NULL) {
			$pemKeys = array();
			foreach ($keys as $key) {
				switch ($key['type']) {
				case 'X509Certificate':
					$pemKeys[] = "-----BEGIN CERTIFICATE-----\n" .
						chunk_split($key['X509Certificate'], 64) .
						"-----END CERTIFICATE-----\n";
					break;
				default:
					SimpleSAML_Logger::debug('Skipping unknown key type: ' . $key['type']);
				}
			}

		} elseif ($srcMetadata->hasValue('certFingerprint')) {
			$certFingerprint = $srcMetadata->getArrayizeString('certFingerprint');
			foreach ($certFingerprint as &$fp) {
				$fp = strtolower(str_replace(':', '', $fp));
			}

			$certificates = $element->getCertificates();

			/*
			 * We don't have the full certificate stored. Try to find it
			 * in the message or the assertion instead.
			 */
			if (count($certificates) === 0) {
				/* We need the full certificate in order to match it against the fingerprint. */
				SimpleSAML_Logger::debug('No certificate in message when validating against fingerprint.');
				return FALSE;
			} else {
				SimpleSAML_Logger::debug('Found ' . count($certificates) . ' certificates in ' . get_class($element));
			}

			$pemCert = self::findCertificate($certFingerprint, $certificates);
			$pemKeys = array($pemCert);
		} else {
			/* Attempt CA validation. */
			$caFile = $srcMetadata->getString('caFile', NULL);
			if ($caFile === NULL) {
				throw new SimpleSAML_Error_Exception(
					'Missing certificate in metadata for ' .
					var_export($srcMetadata->getString('entityid'), TRUE));
			}
			$caFile = SimpleSAML_Utilities::resolveCert($caFile);

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
			$pemKeys = array($pemCert);
		}

		SimpleSAML_Logger::debug('Has ' . count($pemKeys) . ' candidate keys for validation.');

		$lastException = NULL;
		foreach ($pemKeys as $i => $pem) {
			$key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
			$key->loadKey($pem);

			try {
				/*
				 * Make sure that we have a valid signature on either the response
				 * or the assertion.
				 */
				$res = $element->validate($key);
				if ($res) {
					SimpleSAML_Logger::debug('Validation with key #' . $i . ' succeeded.');
					return TRUE;
				}
				SimpleSAML_Logger::debug('Validation with key #' . $i . ' failed without exception.');
			} catch (Exception $e) {
				SimpleSAML_Logger::debug('Validation with key #' . $i . ' failed with exception: ' . $e->getMessage());
				$lastException = $e;
			}
		}

		/* We were unable to validate the signature with any of our keys. */
		if ($lastException !== NULL) {
			throw $lastException;
		} else {
			return FALSE;
		}
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

		if ($message instanceof SAML2_LogoutRequest || $message instanceof SAML2_LogoutResponse) {
			$enabled = $srcMetadata->getBoolean('validate.logout', NULL);
			if ($enabled === NULL) {
				$enabled = $dstMetadata->getBoolean('validate.logout', NULL);
			}
		} elseif ($message instanceof SAML2_AuthnRequest) {
			$enabled = $srcMetadata->getBoolean('validate.authnrequest', NULL);
			if ($enabled === NULL) {
				$enabled = $dstMetadata->getBoolean('validate.authnrequest', NULL);
			}
		}

		if ($enabled === NULL) {
			$enabled = $srcMetadata->getBoolean('redirect.validate', NULL);
			if ($enabled === NULL) {
				$enabled = $dstMetadata->getBoolean('redirect.validate', FALSE);
			}
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
			$keyArray = SimpleSAML_Utilities::loadPrivateKey($dstMetadata, TRUE);
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
	 * Retrieve the status code of a response as a sspmod_saml_Error.
	 *
	 * @param SAML2_StatusResponse $response  The response.
	 * @return sspmod_saml_Error  The error.
	 */
	public static function getResponseError(SAML2_StatusResponse $response) {

		$status = $response->getStatus();
		return new sspmod_saml_Error($status['Code'], $status['SubCode'], $status['Message']);
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

		$dst = $idpMetadata->getDefaultEndpoint('SingleSignOnService', array(SAML2_Const::BINDING_HTTP_REDIRECT));
		$dst = $dst['Location'];

		$ar->setIssuer($spMetadata->getString('entityid'));
		$ar->setDestination($dst);

		$ar->setForceAuthn($spMetadata->getBoolean('ForceAuthn', FALSE));
		$ar->setIsPassive($spMetadata->getBoolean('IsPassive', FALSE));

		$protbind = $spMetadata->getValueValidate('ProtocolBinding', array(
				SAML2_Const::BINDING_HTTP_POST,
				SAML2_Const::BINDING_HTTP_ARTIFACT,
				SAML2_Const::BINDING_HTTP_REDIRECT,
			), SAML2_Const::BINDING_HTTP_POST);

		/* Shoaib - setting the appropriate binding based on parameter in sp-metadata defaults to HTTP_POST */
		$ar->setProtocolBinding($protbind);

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

		$dst = $dstMetadata->getDefaultEndpoint('SingleLogoutService', array(SAML2_Const::BINDING_HTTP_REDIRECT));
		$dst = $dst['Location'];

		$lr = new SAML2_LogoutRequest();

		$lr->setIssuer($srcMetadata->getString('entityid'));
		$lr->setDestination($dst);

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

		$dst = $dstMetadata->getDefaultEndpoint('SingleLogoutService', array(SAML2_Const::BINDING_HTTP_REDIRECT));
		if (isset($dst['ResponseLocation'])) {
			$dst = $dst['ResponseLocation'];
		} else {
			$dst = $dst['Location'];
		}

		$lr = new SAML2_LogoutResponse();

		$lr->setIssuer($srcMetadata->getString('entityid'));
		$lr->setDestination($dst);

		self::addRedirectSign($srcMetadata, $dstMetadata, $lr);

		return $lr;
	}


	/**
	 * Process a response message.
	 *
	 * If the response is an error response, we will throw a sspmod_saml_Error
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