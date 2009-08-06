<?php

/**
 * Class handling encrypted assertions.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_EncryptedAssertion {

	/**
	 * The current encrypted assertion.
	 *
	 * @var DOMElement
	 */
	private $encryptedData;


	/**
	 * Constructor for SAML 2 encrypted assertions.
	 *
	 * @param DOMElement|NULL $xml  The encrypted assertion XML element.
	 */
	public function __construct(DOMElement $xml = NULL) {
		if ($xml === NULL) {
			return;
		}

		$data = SAML2_Utils::xpQuery($xml, './xenc:EncryptedData');
		if (count($data) === 0) {
			throw new Exception('Missing encrypted data in <saml:EncryptedAssertion>.');
		} elseif (count($data) > 1) {
			throw new Exception('More than one encrypted data element in <saml:EncryptedAssertion>.');
		}
		$this->encryptedData = $data[0];
	}


	/**
	 * Set the assertion.
	 *
	 * @param SAML2_Assertion $assertion  The assertion.
	 * @param XMLSecurityKey $key  The key we should use to encrypt the assertion.
	 */
	public function setAssertion(SAML2_Assertion $assertion, XMLSecurityKey $key) {

		$xml = $assertion->toXML();

		$enc = new XMLSecEnc();
		$enc->setNode($xml);
		$enc->type = XMLSecEnc::Element;

		switch ($key->type) {
		case XMLSecurityKey::TRIPLEDES_CBC:
		case XMLSecurityKey::AES128_CBC:
		case XMLSecurityKey::AES192_CBC:
		case XMLSecurityKey::AES256_CBC:
			$symmetricKey = $key;
			break;

		case  XMLSecurityKey::RSA_1_5:
			$symmetricKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
			$symmetricKey->generateSessionKey();

			$enc->encryptKey($key, $symmetricKey);

			break;

		default:
			throw new Exception('Unknown key type for encryption: ' . $key->type);
		}

		$this->encryptedData = $enc->encryptNode($symmetricKey);
	}


	/**
	 * Retrieve the assertion.
	 *
	 * @param XMLSecurityKey $key  The key we should use to decrypt the assertion.
	 * @return SAML2_Assertion  The decrypted assertion.
	 */
	public function getAssertion(XMLSecurityKey $inputKey) {

		$enc = new XMLSecEnc();

		$enc->setNode($this->encryptedData);
		$enc->type = $this->encryptedData->getAttribute("Type");

		$symmetricKey = $enc->locateKey($this->encryptedData);
		if (!$symmetricKey) {
			throw new Exception('Could not locate key algorithm in encrypted data.');
		}

		$symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
		if (!$symmetricKeyInfo) {
			throw new Exception('Could not locate <dsig:KeyInfo> for the encrypted key.');
		}

		if ($symmetricKeyInfo->isEncrypted) {
			/* Make sure that the input key  format is the same as the one used to encrypt the key. */
			if ($inputKey->getAlgorith() !== $symmetricKeyInfo->getAlgorith()) {
				throw new Exception('Algorithm mismatch between input key and key used to encrypt ' .
					' the symmetric key for the message. Key was: ' .
					var_export($inputKey->getAlgorith(), TRUE) . '; message was: ' .
					var_export($symmetricKeyInfo->getAlgorith(), TRUE));
			}

			$encKey = $symmetricKeyInfo->encryptedCtx;
			$symmetricKeyInfo->key = $inputKey->key;
			$key = $encKey->decryptKey($symmetricKeyInfo);
			$symmetricKey->loadkey($key);
		} else {
			/* Make sure that the input key has the correct format. */
			if ($inputKey->getAlgorith() !== $symmetricKey->getAlgorith()) {
				throw new Exception('Algorithm mismatch between input key and key in message. ' .
					'Key was: ' . var_export($inputKey->getAlgorith(), TRUE) . '; message was: ' .
					var_export($symmetricKey->getAlgorith(), TRUE));
			}
			$symmetricKey = $inputKey;
		}

		$decrypted = $enc->decryptNode($symmetricKey, FALSE);

		/*
		 * This is a workaround for the case where only a subset of the XML
		 * tree was serialized for encryption. In that case, we may miss the
		 * namespaces needed to parse the XML.
		 */
		$xml = '<root xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$decrypted.'</root>';
		$newDoc = new DOMDocument();
		if (!$newDoc->loadXML($xml)) {
			throw new Exception('Failed to parse decrypted XML. Maybe the wrong sharedkey was used?');
		}
		$assertionXML = $newDoc->firstChild->firstChild;
		if ($assertionXML === NULL) {
			throw new Exception('Missing encrypted assertion within <saml:EncryptedAssertion>.');
		}
		return new SAML2_Assertion($assertionXML);
	}


	/**
	 * Convert this encrypted assertion to an XML element.
	 *
	 * @param DOMNode|NULL $parentElement  The DOM node the assertion should be created in.
	 * @return DOMElement  This encrypted assertion.
	 */
	public function toXML(DOMNode $parentElement = NULL) {

		if ($parentElement === NULL) {
			$document = new DOMDocument();
			$parentElement = $document;
		} else {
			$document = $parentElement->ownerDocument;
		}

		$root = $document->createElementNS(SAML2_Const::NS_SAML, 'saml:' . 'EncryptedAssertion');
		$parentElement->appendChild($root);

		$root->appendChild($document->importNode($this->encryptedData, TRUE));

		return $root;
	}

}