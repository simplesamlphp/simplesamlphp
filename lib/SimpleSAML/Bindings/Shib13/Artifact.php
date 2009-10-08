<?php

/**
 * Implementation of the Shibboleth 1.3 Artifact binding.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Bindings_Shib13_Artifact {

	/**
	 * Parse the query string, and extract the SAMLart parameters.
	 *
	 * This function is required because each query contains multiple
	 * artifact with the same parameter name.
	 *
	 * @return array  The artifacts.
	 */
	private static function getArtifacts() {
		assert('array_key_exists("QUERY_STRING", $_SERVER)');

		/* We need to process the query string manually, to capture all SAMLart parameters. */

		$artifacts = array();

		$elements = explode('&', $_SERVER['QUERY_STRING']);
		foreach ($elements as $element) {
			list($name, $value) = explode('=', $element, 2);
			$name = urldecode($name);
			$value = urldecode($value);

			if ($name === 'SAMLart') {
				$artifacts[] = $value;
			}
		}

		return $artifacts;
	}


	/**
	 * Build the request we will send to the IdP.
	 *
	 * @param array $artifacts  The artifacts we will request.
	 * @return string  The request, as an XML string.
	 */
	private static function buildRequest(array $artifacts) {

		$msg = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">' .
			'<SOAP-ENV:Body>' .
			'<samlp:Request xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol"' .
			' RequestID="' . SimpleSAML_Utilities::generateID() . '"' .
			' MajorVersion="1" MinorVersion="1"' .
			' IssueInstant="' . SimpleSAML_Utilities::generateTimestamp() . '"' .
			'>';

		foreach ($artifacts as $a) {
			$msg .= '<samlp:AssertionArtifact>' . htmlspecialchars($a) . '</samlp:AssertionArtifact>';
		}

		$msg .= '</samlp:Request>' .
			'</SOAP-ENV:Body>' .
			'</SOAP-ENV:Envelope>';

		return $msg;
	}


	/**
	 * Extract the response element from the SOAP response.
	 *
	 * @param string $soapResponse  The SOAP response.
	 * @return string  The <saml1p:Response> element, as a string.
	 */
	private static function extractResponse($soapResponse) {
		assert('is_string($soapResponse)');

		$doc = new DOMDocument();
		if (!$doc->loadXML($soapResponse)) {
			throw new SimpleSAML_Error_Exception('Error parsing SAML 1 artifact response.');
		}

		$soapEnvelope = $doc->firstChild;
		if (!SimpleSAML_Utilities::isDOMElementOfType($soapEnvelope, 'Envelope', 'http://schemas.xmlsoap.org/soap/envelope/')) {
			throw new SimpleSAML_Error_Exception('Expected artifact response to contain a <soap:Envelope> element.');
		}

		$soapBody = SimpleSAML_Utilities::getDOMChildren($soapEnvelope, 'Body', 'http://schemas.xmlsoap.org/soap/envelope/');
		if (count($soapBody) === 0) {
			throw new SimpleSAML_Error_Exception('Couldn\'t find <soap:Body> in <soap:Envelope>.');
		}
		$soapBody = $soapBody[0];


		$responseElement = SimpleSAML_Utilities::getDOMChildren($soapBody, 'Response', 'urn:oasis:names:tc:SAML:1.0:protocol');
		if (count($responseElement) === 0) {
			throw new SimpleSAML_Error_Exception('Couldn\'t find <saml1p:Response> in <soap:Body>.');
		}
		$responseElement = $responseElement[0];

		/*
		 * Save the <saml1p:Response> element. Note that we need to import it
		 * into a new document, in order to preserve namespace declarations.
		 */
		$newDoc = new DOMDocument();
		$newDoc->appendChild($newDoc->importNode($responseElement, TRUE));
		$responseXML = $newDoc->saveXML();

		return $responseXML;
	}


	/**
	 * This function receives a SAML 1.1 artifact.
	 *
	 * @param SimpleSAML_Configuration $spMetadata  The metadata of the SP.
	 * @param SimpleSAML_Configuration $idpMetadata  The metadata of the IdP.
	 * @return string  The <saml1p:Response> element, as an XML string.
	 */
	public static function receive(SimpleSAML_Configuration $spMetadata, SimpleSAML_Configuration $idpMetadata) {

		$artifacts = self::getArtifacts();
		$request = self::buildRequest($artifacts);

		$url = 'https://skjak.uninett.no:1245/test...';
		$url = $idpMetadata->getString('ArtifactResolutionService');

		$certData = SimpleSAML_Utilities::loadPublicKey($idpMetadata->toArray(), TRUE);
		if (!array_key_exists('PEM', $certData)) {
			throw new SimpleSAML_Error_Exception('Missing one of certData or certificate in metadata for '
				. var_export($idpMetadata->getString('entityid'), TRUE));
		}
		$certData = $certData['PEM'];

		$file = SimpleSAML_Utilities::getTempDir() . '/' . sha1($certData) . '.crt';
		if (!file_exists($file)) {
			SimpleSAML_Utilities::writeFile($file, $certData);
		}

		$globalConfig = SimpleSAML_Configuration::getInstance();
		$spKeyCertFile = $globalConfig->getPathValue('certdir', 'cert/') . $spMetadata->getString('privatekey');

		$opts = array(
			'ssl' => array(
				'verify_peer' => TRUE,
				'cafile' => $file,
				'local_cert' => $spKeyCertFile,
				'capture_peer_cert' => TRUE,
				'capture_peer_chain' => TRUE,
			),
			'http' => array(
				'method' => 'POST',
				'content' => $request,
				'header' => 'SOAPAction: http://www.oasis-open.org/committees/security' . "\r\n" .
					'Content-Type: text/xml',
			),
		);
		$context = stream_context_create($opts);

		/* Fetch the artifact. */
		$response = file_get_contents($url, FALSE, $context);
		if ($response === FALSE) {
			throw new SimpleSAML_Error_Exception('Failed to retrieve assertion from IdP.');
		}

		/* Find the response in the SOAP message. */
		$response = self::extractResponse($response);

		return $response;
	}

}