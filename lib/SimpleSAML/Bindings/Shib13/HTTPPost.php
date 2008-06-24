<?php

/**
 * Implementation of the Shibboleth 1.3 HTTP-POST binding.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Bindings_Shib13_HTTPPost {

	private $configuration = null;
	private $metadata = null;

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	
	public function sendResponseUnsigned($response, $idpentityid, $spentityid, $relayState = null, $endpoint = 'AssertionConsumerService') {

		SimpleSAML_Utilities::validateXMLDocument($response, 'saml11');

		$idpmd = $this->metadata->getMetaData($idpentityid, 'saml20-idp-hosted');
		$spmd = $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$destination = $spmd[$endpoint];
		
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
				"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8">
			<title>Send SAML 2.0 Authentication Response</title>
		</head>
		<body>
		<h1>Send SAML 2.0 Authentication Response</h1>
		 
		 <form style="border: 1px solid #777; margin: 2em; padding: 2em" method="post" action="' . $destination . '">
			<input type="hidden" name="SAMLResponse" value="' . base64_encode($response) . '" />
			<input type="hidden" name="TARGET" value="' . $relayState. '">
			<input type="submit" value="Submit the SAML 1.1 Response" />
		 </form>
		 
		<ul>
			<li>From IdP: <tt>' . $idpentityid . '</tt></li>
			<li>To SP: <tt>' . $spentityid . '</tt></li>
			<li>SP Assertion Consumer Service URL: <tt>' . $destination . '</tt></li>
			<li>RelayState: <tt>' . $relayState . '</tt></li>
		</ul>
		
		<p>SAML Message: <pre>' .  htmlentities($response) . '</pre>
		
		
		</body>
		</html>';
	}
	
	/**
	 * Send an authenticationResponse using HTTP-POST.
	 *
	 * @param $idpmetaindex The metaindex of the IdP to send from.
	 */
	public function sendResponse($response, $idpmetaindex, $spentityid, $relayState = null, $claimedacs = null) {

		SimpleSAML_Utilities::validateXMLDocument($response, 'saml11');

		$idpmd = $this->metadata->getMetaData($idpmetaindex, 'shib13-idp-hosted');
		$spmd = $this->metadata->getMetaData($spentityid, 'shib13-sp-remote');
		
		$destination = $spmd['AssertionConsumerService'];
		
		if(!isset($destination) or $destination == '') {
			throw new Exception('Could not find AssertionConsumerService for SP entity ID [' . $spentityid. ']. ' . 
				'Claimed ACS is: ' . (isset($claimedacs) ? $claimedacs : 'N/A'));
		}

		if(strpos($claimedacs, $destination) === 0) {
			$destination = $claimedacs;
		} else {
			throw new Exception('Claimed ACS (shire) and ACS in SP Metadata do not match. [' . $claimedacs. '] [' . $destination . ']');
		}


		if(!array_key_exists('privatekey', $idpmd)) {
			throw new Exception('Missing \'privatekey\' option from metadata for idp: ' . $idpmetaindex);
		}

		if(!array_key_exists('certificate', $idpmd)) {
			throw new Exception('Missing \'certificate\' option from metadata for idp: ' . $idpmetaindex);
		}

		if(array_key_exists('privatekey_pass', $idpmd)) {
			$passphrase = $idpmd['privatekey_pass'];
		} else {
			$passphrase = NULL;
		}

		$signer = new SimpleSAML_XML_Signer(array(
			'privatekey' => $idpmd['privatekey'],
			'privatekey_pass' => $passphrase,
			'certificate' => $idpmd['certificate'],
			'id' => 'ResponseID',
			));

		if(array_key_exists('certificatechain', $idpmd)) {
			$signer->addCertificate($idpmd['certificatechain']);
		}
		
		$responsedom = new DOMDocument();
		$responsedom->loadXML(str_replace ("\r", "", $response));
		
		$responseroot = $responsedom->getElementsByTagName('Response')->item(0);

		/* We insert the signature before the saml1p:Status element. */
		$statusElements = SimpleSAML_Utilities::getDOMChildren($responseroot, 'Status', '@saml1p');
		assert('count($statusElements) === 1');

		$signer->sign($responseroot, $responseroot, $statusElements[0]);

		$response = $responsedom->saveXML();
		
		
		# openssl genrsa -des3 -out server.key 1024 
		# openssl rsa -in server.key -out server.pem
		# openssl req -new -key server.key -out server.csr
		# openssl x509 -req -days 60 -in server.csr -signkey server.key -out server.crt
		
		if ($this->configuration->getValue('debug')) {
	
			$p = new SimpleSAML_XHTML_Template($this->configuration, 'post-debug.php');
			
			$p->data['header'] = 'SAML (Shibboleth 1.3) Response Debug-mode';
			$p->data['RelayStateName'] = 'TARGET';
			$p->data['RelayState'] = $relayState;
			$p->data['destination'] = $destination;
			$p->data['response'] = str_replace("\n", "", base64_encode($response));
			$p->data['responseHTML'] = htmlentities($responsedom->saveHTML());
			
			$p->show();

		
		} else {
			
			$p = new SimpleSAML_XHTML_Template($this->configuration, 'post.php');
		
			$p->data['RelayStateName'] = 'TARGET';
			$p->data['RelayState'] = $relayState;
			$p->data['destination'] = $destination;
			$p->data['response'] = base64_encode($response);
			
			$p->show();

		
		}
		
		
	}
	
	public function decodeResponse($post) {
		$rawResponse = 	$post["SAMLResponse"];
		$relaystate = 	$post["TARGET"];
		
		$samlResponseXML = base64_decode( $rawResponse );

		SimpleSAML_Utilities::validateXMLDocument($samlResponseXML, 'saml11');
        
		$samlResponse = new SimpleSAML_XML_Shib13_AuthnResponse($this->configuration, $this->metadata);
	
		$samlResponse->setXML($samlResponseXML);
		
		if (isset($relaystate)) {
			$samlResponse->setRelayState($relaystate);
		}
        
        return $samlResponse;
        
	}


	
}

?>