<?php

/**
 * Implementation of the SAML 2.0 HTTP-POST binding.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Bindings_SAML20_HTTPPost {

	private $configuration = null;
	private $metadata = null;

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	
	public function sendResponseUnsigned($response, $idpentityid, $spentityid, $relayState = null, $endpoint = 'AssertionConsumerService') {

		SimpleSAML_Utilities::validateXMLDocument($response, 'saml20');

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
			<input type="hidden" name="RelayState" value="' . $relayState. '">
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
	
	public function sendResponse($response, $idmetaindex, $spentityid, $relayState = null) {

		$idpmd = $this->metadata->getMetaData($idmetaindex, 'saml20-idp-hosted');
		$spmd = $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$destination = $spmd['AssertionConsumerService'];

		$privatekey = SimpleSAML_Utilities::loadPrivateKey($idpmd, TRUE);
		$publickey = SimpleSAML_Utilities::loadPublicKey($idpmd, TRUE);

		$signer = new SimpleSAML_XML_Signer(array(
			'privatekey_array' => $privatekey,
			'publickey_array' => $publickey,
			'id' => 'ID',
			));

		try {
			$responsedom = new DOMDocument();
			$responsedom->loadXML(str_replace("\n", "", str_replace ("\r", "", $response)));
		} catch (Exception $e) {
			throw new Exception("foo");
		}

		$responseroot = $responsedom->getElementsByTagName('Response')->item(0);
		$firstassertionroot = $responsedom->getElementsByTagName('Assertion')->item(0);


		/* Determine what we should sign - either the Response element or the Assertion. The default
		 * is to sign the Assertion, but that can be overridden by the 'signresponse' option in the
		 * SP metadata or 'saml20.signresponse' in the global configuration.
		 */
		$signResponse = FALSE;
		if(array_key_exists('signresponse', $spmd) && $spmd['signresponse'] !== NULL) {
			$signResponse = $spmd['signresponse'];
			if(!is_bool($signResponse)) {
				throw new Exception('Expected the \'signresponse\' option in the metadata of the' .
					' SP \'' . $spmd['entityid'] . '\' to be a boolean value.');
			}
		} else {
			$signResponse = $this->configuration->getBoolean('saml20.signresponse', FALSE);
		}

		/* Check if we have an assertion to sign. Force to sign the response if not. */
		if($firstassertionroot === NULL) {
			$signResponse = TRUE;
		}

		if(!$signResponse) {
			/* Sign the assertion - this must be done before encrypting the assertion. */

			/* We insert the signature before the saml2:Subject element. */
			$subjectElements = SimpleSAML_Utilities::getDOMChildren(
				$firstassertionroot, 'Subject', '@saml2');
			assert('count($subjectElements) === 1');

			$signer->sign($firstassertionroot, $firstassertionroot, $subjectElements[0]);
		}

		/* if the response status is not Success (eg. NoPassive) there is no assertions (firstassertionroot == null) to encrypt */
		if (isset($spmd['assertion.encryption']) && $spmd['assertion.encryption'] && $firstassertionroot != null) {
			$encryptedassertion = $responsedom->createElement("saml:EncryptedAssertion");
			$encryptedassertion->setAttribute("xmlns:saml", "urn:oasis:names:tc:SAML:2.0:assertion");
		
			$firstassertionroot->parentNode->replaceChild($encryptedassertion, $firstassertionroot);
			$encryptedassertion->appendChild($firstassertionroot);
			$firstassertionroot->setAttribute("xmlns:saml", "urn:oasis:names:tc:SAML:2.0:assertion");
			$firstassertionroot->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
	
			$enc = new XMLSecEnc();
			$enc->setNode($firstassertionroot);
			$enc->type = XMLSecEnc::Element;
			
			$objKey = new XMLSecurityKey(XMLSecurityKey::AES128_CBC);
			if (isset($spmd['sharedkey'])) {
				$objKey->loadkey($spmd['sharedkey']);
			} else {
				$key = $objKey->generateSessionKey();
				$objKey->loadKey($key);

				if (!isset($spmd['certificate'])) {
					throw new Exception("Public key for encrypting assertion needed, but not specified for saml20-sp-remote id: " . $spentityid);
				}

				$sp_publiccert = @file_get_contents($this->configuration->getPathValue('certdir') . $spmd['certificate']);

				if ($sp_publiccert === FALSE) {
					throw new Exception("Public key for encrypting assertion specified but not found for saml20-sp-remote id: " . $spentityid . " Filename: " . $spmd['certificate']);
				}
				
				$keyKey = new XMLSecurityKey(XMLSecurityKey::RSA_1_5, array('type'=>'public'));
				
				$keyKey->loadKey($sp_publiccert);
				
				$enc->encryptKey($keyKey, $objKey);
			}
			$encNode = $enc->encryptNode($objKey); # replacing the unencrypted node
	
		}

		if($signResponse) {
			/* Sign the response - this must be done after encrypting the assertion. */

			/* We insert the signature before the saml2p:Status element. */
			$statusElements = SimpleSAML_Utilities::getDOMChildren($responseroot, 'Status', '@saml2p');
			assert('count($statusElements) === 1');

			$signer->sign($responseroot, $responseroot, $statusElements[0]);
		}


		$response = $responsedom->saveXML();
		
		SimpleSAML_Utilities::validateXMLDocument($response, 'saml20');
		
		# openssl genrsa -des3 -out server.key 1024 
		# openssl rsa -in server.key -out server.pem
		# openssl req -new -key server.key -out server.csr
		# openssl x509 -req -days 60 -in server.csr -signkey server.key -out server.crt
		
		if ($this->configuration->getValue('debug')) {
	
			$p = new SimpleSAML_XHTML_Template($this->configuration, 'post-debug.php');
			
			$p->data['header'] = 'SAML Response Debug-mode';
			$p->data['RelayStateName'] = 'RelayState';
			$p->data['RelayState'] = $relayState;
			$p->data['destination'] = $destination;
			$p->data['response'] = str_replace("\n", "", base64_encode($response));
			$p->data['responseHTML'] = htmlspecialchars(SimpleSAML_Utilities::formatXMLString($response));
			
			$p->show();

		
		} else {

			$p = new SimpleSAML_XHTML_Template($this->configuration, 'post.php');
	
			$p->data['RelayStateName'] = 'RelayState';
			$p->data['RelayState'] = $relayState;
			$p->data['destination'] = $destination;
			$p->data['response'] = base64_encode($response);
			
			$p->show();

		
		}
		
		
	}
	
	public function decodeResponse($post) {
		if (!isset($post["SAMLResponse"])) throw new Exception('Could not get SAMLResponse from Browser/POST. May be there is some redirection related problem on your server? In example apache redirecting the POST to http to a GET on https.');
		
		$rawResponse = 	$post["SAMLResponse"];
		$relaystate = 	$post["RelayState"];
		

		
		$samlResponseXML = base64_decode( $rawResponse );

		SimpleSAML_Utilities::validateXMLDocument($samlResponseXML, 'saml20');
		
		//error_log("Response is: " . $samlResponseXML);
        
		$samlResponse = new SimpleSAML_XML_SAML20_AuthnResponse($this->configuration, $this->metadata);
	
		$samlResponse->setXML($samlResponseXML);
		
		if (isset($relaystate)) {
			$samlResponse->setRelayState($relaystate);
		}
	
        #echo("Authn response = " . $samlResponse );

        return $samlResponse;
        
	}


	
}

?>