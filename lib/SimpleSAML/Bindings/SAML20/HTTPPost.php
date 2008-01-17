<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');

require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/XHTML/Template.php');


/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Bindings_SAML20_HTTPPost {

	private $configuration = null;
	private $metadata = null;

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	
	public function sendResponseUnsigned($response, $idpentityid, $spentityid, $relayState = null, $endpoint = 'AssertionConsumerService') {

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
	
	public function sendResponse($response, $idpentityid, $spentityid, $relayState = null) {

		$idpmd = $this->metadata->getMetaData($idpentityid, 'saml20-idp-hosted');
		$spmd = $this->metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$destination = $spmd['AssertionConsumerService'];
	
		/*
		$privatekey = "/home/as/erlang/feide2/cert/edugain/server1Key.pem";
		$publiccert = "/home/as/erlang/feide2/cert/edugain/server2chain.pem";


		$privatekey = "/home/as/erlang/feide2/cert/server.pem";
		$publiccert = "/home/as/erlang/feide2/cert/server.crt";
				*/
		
		$privatekey = $this->configuration->getBaseDir() . '/cert/' . $idpmd['privatekey'];
		$publiccert = $this->configuration->getBaseDir() . '/cert/' . $idpmd['certificate'];

		if (!file_exists($privatekey))
			throw new Exception('Could not find private key file [' . $privatekey . '] which is needed to sign the authentication response');

		if (!file_exists($publiccert)) 
			throw new Exception('Could not find certificate [' . $publiccert . '] to attach to the authentication resposne');

		
		/*
		 * XMLDSig. Sign the complete request with the key stored in cert/server.pem
		 */
		$objXMLSecDSig = new XMLSecurityDSig();
		//$objXMLSecDSig->idKeys[] = 'ResponseID';
		#$objXMLSecDSig->idKeys = array('ResponseID');
		
		$objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
	
	
		try {
			$responsedom = new DOMDocument();
			$responsedom->loadXML(str_replace("\n", "", str_replace ("\r", "", $response)));
		} catch (Exception $e) {
			throw new Exception("foo");
		}
		$responseroot = $responsedom->getElementsByTagName('Response')->item(0);
		
		//$assertionroot = $responsedom->getElementsByTagName('Assertion')->item(1);
		$firstassertionroot = $responsedom->getElementsByTagName('Assertion')->item(0);
		
		//$objXMLSecDSig->addReferenceList(array($responseroot), XMLSecurityDSig::SHA1, //array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'));
		
// 		$objXMLSecDSig->addReferenceList(array($firstassertionroot), XMLSecurityDSig::SHA1, 
// 			array('http://www.w3.org/2000/09/xmldsig#enveloped-signature',
// 			'http://www.w3.org/2001/10/xml-exc-c14n#'));
			
		$objXMLSecDSig->addReferenceList(array($firstassertionroot), XMLSecurityDSig::SHA1,
			array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'),
			array('id_name' => 'ID'));
		
		#$objXMLSecDSig->addRefInternal($responseroot, $responseroot, XMLSecurityDSig::SHA1);
		
		/* create new XMLSecKey using RSA-SHA-1 and type is private key */
		$objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
		
		/* load the private key from file - last arg is bool if key in file (TRUE) or is string (FALSE) */
		$objKey->loadKey($privatekey,TRUE);
		
		
		
		
		
		$objXMLSecDSig->sign($objKey);
		
		$public_cert = file_get_contents($publiccert);
		$objXMLSecDSig->add509Cert($public_cert, true);
		/*
		$public_cert = file_get_contents("cert/edugain/public2.pem");
		$objXMLSecDSig->add509Cert($public_cert, true);
		
		$public_cert = file_get_contents("cert/edugain/public3.pem");
		$objXMLSecDSig->add509Cert($public_cert, true);
		*/
		
		
		$objXMLSecDSig->appendSignature($firstassertionroot, true, true);
		//$objXMLSecDSig->appendSignature($responseroot, true, false);
		
		$response = $responsedom->saveXML();
		
		
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
			$p->data['responseHTML'] = htmlentities($responsedom->saveHTML());
			
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