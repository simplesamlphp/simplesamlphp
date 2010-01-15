<?php


/**
 * Class which implements the HTTP-Redirect binding.
 *
 * @author  Danny Bollaert, UGent AS. <danny.bollaert@ugent.be>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_HTTPArtifact extends SAML2_Binding {

	/**
	 * Create the redirect URL for a message.
	 *
	 * @param SAML2_Message $message  The message.
	 * @return string  The URL the user should be redirected to in order to send a message.
	 */
	public function getRedirectURL(SAML2_Message $message) {

		$generatedId = pack('H*', ((string)  SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(20))));
		$artifact = base64_encode("\x00\x04\x00\x00" . sha1($message->getIssuer(), TRUE) . $generatedId) ;
		$artifactData = $message->toUnsignedXML();
		$artifactDataString = $artifactData->ownerDocument->saveXML($artifactData);
		SimpleSAML_Memcache::set('artifact:' . $artifact, $artifactDataString);
		$params = array(
			'SAMLart' => $artifact,
		);
		$relayState = $message->getRelayState();
		if ($relayState !== NULL) {
			$params['RelayState'] = $relayState;
		}

		return SimpleSAML_Utilities::addURLparameter($message->getDestination(), $params);
	}


	/**
	 * Send a SAML 2 message using the HTTP-Redirect binding.
	 *
	 * Note: This function never returns.
	 *
	 * @param SAML2_Message $message  The message we should send.
	 */
	public function send(SAML2_Message $message) {

		$destination = $this->getRedirectURL($message);
		SimpleSAML_Utilities::redirect($destination);
	}


	/**
	 * Receive a SAMLart.
	 *
	 * Throws an exception if it is unable receive the message.
	 *
	 * @return SAML2_Message  The received message.
	 */
	public function receive() {

		throw new Exception('Receiving SAML2 Artifact messages not supported.');
	}

}
