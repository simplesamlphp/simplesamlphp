<?php
/**
 * Implementation of the SAML 2.0 SOAP binding.
 *
 * @author Shoaib Ali
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_SOAPClient {

	const START_SOAP_ENVELOPE = '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/"><soap-env:Header/><soap-env:Body>';
	const END_SOAP_ENVELOPE = '</soap-env:Body></soap-env:Envelope>';

	/**
	 * This function sends the SOAP message to the service location and returns SOAP response
	 *
	 * @param $ar SAML2_ArtifactResolve object.
	 * @return $soapresponse string
	 */
	public function send(SAML2_ArtifactResolve $ar, SimpleSAML_Configuration $spMetadata) {

		$issuer = $ar->getIssuer();

		$options = array(
			'uri' => $issuer,
			'location' => $ar->getDestination(),
		);

		// Determine if we are going to do a MutualSSL connection between the IdP and SP  - Shoaib
		if ($spMetadata->hasValue('saml.SOAPClient.certificate')) {
			$options['local_cert'] = SimpleSAML_Utilities::resolveCert($spMetadata->getString('saml.SOAPClient.certificate'));
			if ($spMetadata->hasValue('saml.SOAPClient.privatekey_pass')) {
				$options['passphrase'] = $spMetadata->getString('saml.SOAPClient.privatekey_pass');
			}
		} else {
			/* Use the SP certificate and privatekey if it is configured. */
			$privateKey = SimpleSAML_Utilities::loadPrivateKey($spMetadata);
			$publicKey = SimpleSAML_Utilities::loadPublicKey($spMetadata);
			if ($privateKey !== NULL && $publicKey !== NULL && isset($publicKey['PEM'])) {
				$keyCertData = $privateKey['PEM'] . $publicKey['PEM'];
				$file = SimpleSAML_Utilities::getTempDir() . '/' . sha1($keyCertData) . '.pem';
				if (!file_exists($file)) {
					SimpleSAML_Utilities::writeFile($file, $keyCertData);
				}
				$options['local_cert'] = $file;
				if (isset($privateKey['password'])) {
					$options['passphrase'] = $privateKey['password'];
				}
			}
		}

		$x = new SoapClient(NULL, $options);

		// Add soap-envelopes
		$request = $ar->toSignedXML();
		$request = self::START_SOAP_ENVELOPE . $request->ownerDocument->saveXML($request) . self::END_SOAP_ENVELOPE;

		$action = 'http://www.oasis-open.org/committees/security';
		$version = '1.1';
		$destination = $ar->getDestination();


		/* Perform SOAP Request over HTTP */
		$soapresponsexml = $x->__doRequest($request, $destination, $action, $version);


		// Convert to SAML2_Message (DOMElement)
		$dom = new DOMDocument();
		if (!$dom->loadXML($soapresponsexml)) {
			throw new Exception('Not a SOAP response.');
		}

		$soapfault = $this->getSOAPFault($dom);
		if (isset($soapfault)) {
			throw new Exception($soapfault);
		}
		//Extract the message from the response
		$xml = $dom->firstChild;    /* Soap Envelope */
		$samlresponse = SAML2_Utils::xpQuery($dom->firstChild, '/soap-env:Envelope/soap-env:Body/*[1]');
		$samlresponse = SAML2_Message::fromXML($samlresponse[0]);


		simpleSAML_Logger::debug("Valid ArtifactResponse received from IdP");

		return $samlresponse;

	}


	/*
	 * Extracts the SOAP Fault from SOAP message
	 * @param $soapmessage Soap response needs to be type DOMDocument
	 * @return $soapfaultstring string|NULL
	 */
	private function getSOAPFault($soapmessage) {

		$soapfault = SAML2_Utils::xpQuery($soapmessage->firstChild, '/soap-env:Envelope/soap-env:Body/soap-env:Fault');

		if (empty($soapfault)) {
			/* No fault. */
			return NULL;
		}
		$soapfaultelement = $soapfault[0];
		$soapfaultstring = "Unknown fault string found"; // There is a fault element but we havn't found out what the fault string is
		// find out the fault string
		$faultstringelement =   SAML2_Utils::xpQuery($soapfaultelement, './soap-env:faultstring') ;
		if (!empty($faultstringelement)) {
			return $faultstringelement[0]->textContent;
		}
		return $soapfaultstring;
	}

}
