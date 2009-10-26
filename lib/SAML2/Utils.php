<?php

/**
 * Helper functions for the SAML2 library.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_Utils {

	/**
	 * Check the Signature in a XML element.
	 *
	 * This function expects the XML element to contain a Signature-element
	 * which contains a reference to the XML-element. This is common for both
	 * messages and assertions.
	 *
	 * Note that this function only validates the element itself. It does not
	 * check this against any local keys.
	 *
	 * If no Signature-element is located, this function will return FALSE. All
	 * other validation errors result in an exception. On successful validation
	 * an array will be returned. This array contains the information required to
	 * check the signature against a public key.
	 *
	 * @param DOMElement $root  The element which should be validated.
	 * @return array|FALSE  An array with information about the Signature-element.
	 */
	public static function validateElement(DOMElement $root) {

		/* Create an XML security object. */
		$objXMLSecDSig = new XMLSecurityDSig();

		/* Both SAML messages and SAML assertions use the 'ID' attribute. */
		$objXMLSecDSig->idKeys[] = 'ID';

		/* Locate the XMLDSig Signature element to be used. */
		$signatureElement = self::xpQuery($root, './ds:Signature');
		if (count($signatureElement) === 0) {
			/* We don't have a signature element ot validate. */
			return FALSE;
		} elseif (count($signatureElement) > 1) {
			throw new Exception('XMLSec: more than one signature element in root.');
		}
		$signatureElement = $signatureElement[0];
		$objXMLSecDSig->sigNode = $signatureElement;

		/* Canonicalize the XMLDSig SignedInfo element in the message. */
		$objXMLSecDSig->canonicalizeSignedInfo();

		/* Validate referenced xml nodes. */
		if (!$objXMLSecDSig->validateReference()) {
			throw new Exception('XMLsec: digest validation failed');
		}

		/* Check that $root is one of the signed nodes. */
		$rootSigned = FALSE;
		foreach ($objXMLSecDSig->getValidatedNodes() as $signedNode) {
			if ($signedNode->isSameNode($root)) {
				$rootSigned = TRUE;
				break;
			}
		}
		if (!$rootSigned) {
			throw new Exception('XMLSec: The root element is not signed.');
		}

		/* Now we extract all available X509 certificates in the signature element. */
		$certificates = array();
		foreach (self::xpQuery($signatureElement, './ds:KeyInfo/ds:X509Data/ds:X509Certificate') as $certNode) {
			$certData = trim($certNode->textContent);
			$certData = str_replace(array("\r", "\n", "\t", ' '), '', $certData);
			$certificates[] = $certData;
		}

		$ret = array(
			'Signature' => $objXMLSecDSig,
			'Certificates' => $certificates,
			);

		return $ret;
	}


	/**
	 * Check a signature against a key.
	 *
	 * An exception is thrown if we are unable to validate the signature.
	 *
	 * @param array $info  The information returned by the validateElement()-function.
	 * @param XMLSecurityKey $key  The publickey that should validate the Signature object.
	 */
	public static function validateSignature(array $info, XMLSecurityKey $key) {
		assert('array_key_exists("Signature", $info)');

		$objXMLSecDSig = $info['Signature'];

		/* Check the signature. */
		if (! $objXMLSecDSig->verify($key)) {
			throw new Exception("Unable to validate Signature");
		}
	}


	/**
	 * Do an XPath query on an XML node.
	 *
	 * @param DOMNode $node  The XML node.
	 * @param string $query  The query.
	 * @return array  Array with matching DOM nodes.
	 */
	public static function xpQuery(DOMNode $node, $query) {
		assert('is_string($query)');
		static $xpCache = NULL;

		if ($xpCache === NULL || !$xpCache->document->isSameNode($node->ownerDocument)) {
			$xpCache = new DOMXPath($node->ownerDocument);
			$xpCache->registerNamespace('saml_protocol', SAML2_Const::NS_SAMLP);
			$xpCache->registerNamespace('saml_assertion', SAML2_Const::NS_SAML);
			$xpCache->registerNamespace('ds', XMLSecurityDSig::XMLDSIGNS);
			$xpCache->registerNamespace('xenc', XMLSecEnc::XMLENCNS);
		}

		$results = $xpCache->query($query, $node);
		$ret = array();
		for ($i = 0; $i < $results->length; $i++) {
			$ret[$i] = $results->item($i);
		}

		return $ret;
	}


	/**
	 * Parse a boolean attribute.
	 *
	 * @param DOMElement $node  The element we should fetch the attribute from.
	 * @param string $attributeName  The name of the attribute.
	 * @param mixed $default  The value that should be returned if the attribute doesn't exist.
	 * @return bool|mixed  The value of the attribute, or $default if the attribute doesn't exist.
	 */
	public static function parseBoolean(DOMElement $node, $attributeName, $default = NULL) {
		assert('is_string($attributeName)');

		if (!$node->hasAttribute($attributeName)) {
			return $default;
		}
		$value = $node->getAttribute($attributeName);
		switch (strtolower($value)) {
		case '0':
		case 'false':
			return FALSE;
		case '1':
		case 'true':
			return TRUE;
		default:
			throw new Exception('Invalid value of boolean attribute ' . var_export($attributeName, TRUE) . ': ' . var_export($value, TRUE));
		}
	}


	/**
	 * Create a NameID element.
	 *
	 * The NameId array can have the following elements: 'Value', 'Format',
	 *   'NameQualifier, 'SPNameQualifier'
	 *
	 * Only the 'Value'-element is required.
	 *
	 * @param DOMElement $node  The DOM node we should append the NameId to.
	 * @param array $nameId  The name identifier.
	 */
	public static function addNameId(DOMElement $node, array $nameId) {
		assert('array_key_exists("Value", $nameId)');

		$xml = $node->ownerDocument->createElementNS(SAML2_Const::NS_SAML, 'saml:NameID');
		$node->appendChild($xml);

		if (array_key_exists('NameQualifier', $nameId) && $nameId['NameQualifier'] !== NULL) {
			$xml->setAttribute('NameQualifier', $nameId['NameQualifier']);
		}
		if (array_key_exists('SPNameQualifier', $nameId) && $nameId['SPNameQualifier'] !== NULL) {
			$xml->setAttribute('SPNameQualifier', $nameId['SPNameQualifier']);
		}
		if (array_key_exists('Format', $nameId) && $nameId['Format'] !== NULL) {
			$xml->setAttribute('Format', $nameId['Format']);
		}

		$xml->appendChild($node->ownerDocument->createTextNode($nameId['Value']));
	}


	/**
	 * Parse a NameID element.
	 *
	 * @param DOMElement $xml  The DOM element we should parse.
	 * @return array  The parsed name identifier.
	 */
	public static function parseNameId(DOMElement $xml) {

		$ret = array('Value' => trim($xml->textContent));

		foreach (array('NameQualifier', 'SPNameQualifier', 'Format') as $attr) {
			if ($xml->hasAttribute($attr)) {
				$ret[$attr] = $xml->getAttribute($attr);
			}
		}

		return $ret;
	}


	/**
	 * Insert a Signature-node.
	 *
	 * @param XMLSecurityKey $key  The key we should use to sign the message.
	 * @param array $certificates  The certificates we should add to the signature node.
	 * @param DOMElement $root  The XML node we should sign.
	 * @param DomElement $insertBefore  The XML element we should insert the signature element before.
	 */
	public static function insertSignature(XMLSecurityKey $key, array $certificates, DOMElement $root, DOMNode $insertBefore = NULL) {

		$objXMLSecDSig = new XMLSecurityDSig();
		$objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

		$objXMLSecDSig->addReferenceList(
			array($root),
			XMLSecurityDSig::SHA1,
			array('http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N),
			array('id_name' => 'ID')
			);

		$objXMLSecDSig->sign($key);

		foreach ($certificates as $certificate) {
			$objXMLSecDSig->add509Cert($certificate, TRUE);
		}

		$objXMLSecDSig->insertSignature($root, $insertBefore);

	}


	/**
	 * Decrypt an encrypted element.
	 *
	 * @param DOMElement $encryptedData  The encrypted data.
	 * @param XMLSecurityKey $inputKey  The decryption key.
	 * @return DOMElement  The decrypted element.
	 */
	public static function decryptElement(DOMElement $encryptedData, XMLSecurityKey $inputKey) {

		$enc = new XMLSecEnc();

		$enc->setNode($encryptedData);
		$enc->type = $encryptedData->getAttribute("Type");

		$symmetricKey = $enc->locateKey($encryptedData);
		if (!$symmetricKey) {
			throw new Exception('Could not locate key algorithm in encrypted data.');
		}

		$symmetricKeyInfo = $enc->locateKeyInfo($symmetricKey);
		if (!$symmetricKeyInfo) {
			throw new Exception('Could not locate <dsig:KeyInfo> for the encrypted key.');
		}

		$inputKeyAlgo = $inputKey->getAlgorith();
		if ($symmetricKeyInfo->isEncrypted) {
			$symKeyInfoAlgo = $symmetricKeyInfo->getAlgorith();

			if ($symKeyInfoAlgo === XMLSecurityKey::RSA_OAEP_MGF1P && $inputKeyAlgo === XMLSecurityKey::RSA_1_5) {
				/*
				 * The RSA key formats are equal, so loading an RSA_1_5 key
				 * into an RSA_OAEP_MGF1P key can be done without problems.
				 * We therefore pretend that the input key is an
				 * RSA_OAEP_MGF1P key.
				 */
				$inputKeyAlgo = XMLSecurityKey::RSA_OAEP_MGF1P;
			}

			/* Make sure that the input key format is the same as the one used to encrypt the key. */
			if ($inputKeyAlgo !== $symKeyInfoAlgo) {
				throw new Exception('Algorithm mismatch between input key and key used to encrypt ' .
					' the symmetric key for the message. Key was: ' .
					var_export($inputKeyAlgo, TRUE) . '; message was: ' .
					var_export($symKeyInfoAlgo, TRUE));
			}

			$encKey = $symmetricKeyInfo->encryptedCtx;
			$symmetricKeyInfo->key = $inputKey->key;
			$key = $encKey->decryptKey($symmetricKeyInfo);
			$symmetricKey->loadkey($key);
		} else {
			$symKeyAlgo = $symmetricKey->getAlgorith();
			/* Make sure that the input key has the correct format. */
			if ($inputKeyAlgo !== $symKeyAlgo) {
				throw new Exception('Algorithm mismatch between input key and key in message. ' .
					'Key was: ' . var_export($inputKeyAlgo, TRUE) . '; message was: ' .
					var_export($symKeyAlgo, TRUE));
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
		$decryptedElement = $newDoc->firstChild->firstChild;
		if ($decryptedElement === NULL) {
			throw new Exception('Missing encrypted element.');
		}

		return $decryptedElement;
	}

}

?>