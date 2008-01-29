<?php

/**
 * This class implements helper functions for XML validation.
 */
class SimpleSAML_XML_Validator {

	/**
	 * This variable contains the XML document we have validated.
	 */
	private $xmlDocument;

	/**
	 * This variable contains the fingerprint of the certificate the XML document
	 * was signed with.
	 */
	private $x509Fingerprint;

	/**
	 * This variable contains the nodes which are signed.
	 */
	private $validNodes = null;


	/**
	 * This function initializes the validator.
	 *
	 * @param $xmlDocument  The XML document we should validate.
	 * @param $idAttribute  The ID attribute which is used in node references. If this attribute is
	 *                      NULL (the default), then we will use whatever is the default ID.
	 */
	public function __construct($xmlDocument, $idAttribute = NULL) {
		assert('$xmlDocument instanceof DOMDocument');

		$this->xmlDocument = $xmlDocument;

		/* Create an XML security object. */
		$objXMLSecDSig = new XMLSecurityDSig();

		/* Add the id attribute if the user passed in an id attribute. */
		if($idAttribute !== NULL) {
			assert('is_string($idAttribute)');
			$objXMLSecDSig->idKeys[] = $idAttribute;
		}

		/* Locate the XMLDSig Signature element to be used. */
		$signatureElement = $objXMLSecDSig->locateSignature($this->xmlDocument);
		if (!$signatureElement) {
			throw new Exception('Could not locate XML Signature element.');
		}

		/* Canonicalize the XMLDSig SignedInfo element in the message. */
		$objXMLSecDSig->canonicalizeSignedInfo();

		/* Validate referenced xml nodes. */
		if (!$objXMLSecDSig->validateReference()) {
			throw new Exception('XMLsec: digest validation failed');
		}


		/* Find the key used to sign the document. */
		$objKey = $objXMLSecDSig->locateKey();
		if (empty($objKey)) {
			throw new Exception('Error loading key to handle XML signature');
		}

		/* Load the key data. */
		if (!XMLSecEnc::staticLocateKeyInfo($objKey, $signatureElement)) {
			throw new Exception('Error finding key data for XML signature validation.');
		}

		/* Check the signature. */
		if (! $objXMLSecDSig->verify($objKey)) {
			throw new Exception("Unable to validate Signature");
		}

		/* Extract the certificate fingerprint. */
		$this->x509Fingerprint = $objKey->getX509Fingerprint();

		/* Find the list of validated nodes. */
		$this->validNodes = $objXMLSecDSig->getValidatedNodes();
	}


	/**
	 * This function validates that the fingerprint of the certificate which was used to
	 * sign this document matches the given fingerprint. An exception will be thrown if
	 * the fingerprints doesn't match.
	 *
	 * @param $fingerprint  The fingerprint which should match.
	 */
	public function validateFingerprint($fingerprint) {
		assert('is_string($fingerprint)');

		if($this->x509Fingerprint === NULL) {
			throw new Exception('Key used to sign the message wasn\'t an X509 certificate.');
		}

		/* Make sure that the fingerprint is in the correct format. */
		$fingerprint = strtolower(str_replace(":", "", $fingerprint));

		/* Compare the fingerprints. Throw an exception if they didn't match. */
		if ($fingerprint !== $this->x509Fingerprint) {
			throw new Exception('Expecting certificate fingerprint [' . $fingerprint . ']but got [' . $this->x509Fingerprint . ']');
		}

		/* The fingerprints matched. */
	}


	/**
	 * This function checks if the given XML node was signed.
	 *
	 * @param $node   The XML node which we should verify that was signed.
	 *
	 * @return TRUE if this node (or a parent node) was signed. FALSE if not.
	 */
	public function isNodeValidated($node) {
		assert('$node instanceof DOMNode');

		while($node !== NULL) {
			if(in_array($node, $this->validNodes)) {
				return TRUE;
			}

			$node = $node->parentNode;
		}

		/* Neither this node nor any of the parent nodes could be found in the list of
		 * signed nodes.
		 */
		return FALSE;
	}
}

?>