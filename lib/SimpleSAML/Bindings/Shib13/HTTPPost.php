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
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/Shib13/AuthnResponse.php');
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Bindings_Shib13_HTTPPost {

	private $configuration = null;
	private $metadata = null;

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_XML_MetaDataStore $metadatastore) {
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
	
	public function sendResponse($response, $idpentityid, $spentityid, $relayState = null, $claimedacs = null) {

		$idpmd = $this->metadata->getMetaData($idpentityid, 'shib13-idp-hosted');
		$spmd = $this->metadata->getMetaData($spentityid, 'shib13-sp-remote');
		
		$destination = $spmd['AssertionConsumerService'];
		
		if (!isset($destination) or $destination == '') 
			throw new Exception('Could not find AssertionConsumerService for SP entity ID [' . $spentityid. ']. ' . 
				'Claimed ACS is: ' . (isset($claimedacs) ? $claimedacs : 'N/A'));
	
		$privatekey = $this->configuration->getValue('basedir') . '/cert/' . $idpmd['privatekey'];
		$publiccert = $this->configuration->getValue('basedir') . '/cert/' . $idpmd['certificate'];

		
		if (strstr($claimedacs, $destination) == 0) {
			$destination = $claimedacs;
		} else {
			throw new Exception('Claimed ACS (shire) and ACS in SP Metadata do not match. [' . $claimedacs. '] [' . $destination . ']');
		}
		
		
		/*
		 * XMLDSig. Sign the complete request with the key stored in cert/server.pem
		 */
		$objXMLSecDSig = new XMLSecurityDSig();
		//$objXMLSecDSig->idKeys[] = 'ResponseID';
		
		$objXMLSecDSig->idKeys = array('ResponseID');
		
		$objXMLSecDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
		
		$responsedom = new DOMDocument();
		$responsedom->loadXML(str_replace ("\r", "", $response));
		
		$responseroot = $responsedom->getElementsByTagName('Response')->item(0);
		
		//$assertionroot = $responsedom->getElementsByTagName('Assertion')->item(1);
		$firstassertionroot = $responsedom->getElementsByTagName('Assertion')->item(0);
		
		$objXMLSecDSig->addReferenceList(array($responseroot), XMLSecurityDSig::SHA1, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature'), null, 'ResponseID');
		#$objXMLSecDSig->addReferenceList(array($firstassertionroot), XMLSecurityDSig::SHA1, array('http://www.w3.org/2000/09/xmldsig#enveloped-signature',
		#	'http://www.w3.org/2001/10/xml-exc-c14n#'));
		
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
		
		
		$objXMLSecDSig->appendSignature($responseroot, true);
		
		$response = $responsedom->saveXML();
		
		
		# openssl genrsa -des3 -out server.key 1024 
		# openssl rsa -in server.key -out server.pem
		# openssl req -new -key server.key -out server.csr
		# openssl x509 -req -days 60 -in server.csr -signkey server.key -out server.crt
		
		$p = new SimpleSAML_XHTML_Template($this->configuration, 'post.php');
	
		$p->data['RelayStateName'] = 'TARGET';
		$p->data['RelayState'] = $relayState;
		$p->data['destination'] = $destination;
		$p->data['response'] = base64_encode($response);
		
		$p->show();
		/*
		
		
		
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
		
		<p>SAML Message: <pre>' .  htmlentities($responsedom->saveHTML()) . '</pre>
		
		
		</body>
		</html>';
		*/
		
	}
	
	public function decodeResponse($post) {
		$rawResponse = 	$post["SAMLResponse"];
		$relaystate = 	$post["TARGET"];
		
		$samlResponseXML = base64_decode( $rawResponse );
        
		$samlResponse = new SimpleSAML_XML_Shib13_AuthnResponse($this->configuration, $this->metadata);
	
		$samlResponse->setXML($samlResponseXML);
		
		if (isset($relaystate)) {
			$samlResponse->setRelayState($relaystate);
		}
	
        #echo("Authn response = " . $samlResponse );

        return $samlResponse;
        
	}


	
}

?>