<?php

/**
 * Implementation of the SAML 2.0 HTTP-REDIRECT binding.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Bindings_SAML20_HTTPRedirect {

	private $configuration = null;
	private $metadata = null;

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}


	/**
	 * Sign a HTTP-Redirect query string.
	 *
	 * @param string $query  The query string.
	 * @param array $md  The metadata of the sender.
	 * @param array $targetmd  The metadata of the recipient.
	 * @return string  The signed query.
	 */
	public function signQuery($query, $md, $targetmd) {
		assert('is_string($query)');
		assert('is_array($md)');
		assert('is_array($targetmd)');

		/* Check if signing of HTTP-Redirect messages is enabled. */
		if (array_key_exists('redirect.sign', $targetmd)) {
			$sign = (bool)$targetmd['redirect.sign'];
		} elseif (array_key_exists('redirect.sign', $md)) {
			$sign = (bool)$md['redirect.sign'];
		} elseif (array_key_exists('request.signing', $md)) {
			SimpleSAML_Logger::warning('Found deprecated \'request.signing\' metadata' .
				' option for entity ' . var_export($md['entityid'], TRUE) . '.' .
				' Please replace with \'redirect.sign\' instead.');
			$sign = (bool)$md['request.signing'];
		} else {
			$sign = FALSE;
		}

		if (!$sign) {
			/* Signing of queries disabled. */
			return $query;
		}


		/* Load the private key. */
		$privatekey = SimpleSAML_Utilities::loadPrivateKey($md, TRUE);

		/* Sign the query string. According to the specification, the string which should be
		 * signed is the concatenation of the following query parameters (in order):
		 * - SAMLRequest/SAMLResponse
		 * - RelayState (if present)
		 * - SigAlg
		 *
		 * We assume that the query string now contains only the two first parameters.
		 */

		/* Append the signature algorithm. We always use RSA-SHA1. */
		$algURI = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
		$query = $query . "&" . "SigAlg=" . urlencode($algURI);
		
		$xmlseckey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));

		/* Set the passphrase which should be used to open the key, if this attribute is
		 * set in the metadata.
		 */
		if(array_key_exists('password', $privatekey)) {
			$xmlseckey->passphrase = $privatekey['password'];
		}

		$xmlseckey->loadKey($privatekey['PEM']);
		$signature = $xmlseckey->signData($query);
                
		$query = $query . "&" . "Signature=" . urlencode(base64_encode($signature));

		return $query;
	}


	/**
	 * Validate query string.
	 *
	 * This function validates the signature on the query string of the current request.
	 *
	 * @param string $issuer  The issuer of this query string.
	 * @param string $mode  Whether we are running as an SP or an IdP.
	 * @param string $request  The query parameter which contains the request/response we should validate.
	 * @return bool  FALSE if the query string wasn't validated, TRUE if it validate. An exception will be
	 *               thrown if the validation fails.
	 */
	public function validateQuery($issuer, $mode = 'SP', $request = 'SAMLRequest') {
		assert('is_string($issuer)');
		assert('$mode === "SP" || $mode === "IdP"');
		assert('$request === "SAMLRequest" || $request === "SAMLResponse"');

		if ($mode == 'IdP') {
			$issuerSet = 'saml20-sp-remote';
			$recipientSet = 'saml20-idp-hosted';
		} else {
			$issuerSet = 'saml20-idp-remote';
			$recipientSet = 'saml20-sp-hosted';
		}
		SimpleSAML_Logger::debug('Library - HTTPRedirect validateQuery(): Looking up metadata issuer:' . $issuer . ' in set '. $issuerSet);
		$md = $this->metadata->getMetaData($issuer, $issuerSet);

		$recipientMetadata = $this->metadata->getMetaDataCurrent($recipientSet);
		
		// check whether to validate or not
		if (array_key_exists('redirect.validate', $md)) {
			$validate = (bool)$md['redirect.validate'];
		} elseif (array_key_exists('redirect.validate', $recipientMetadata)) {
			$validate = (bool)$recipientMetadata['redirect.validate'];
		} elseif (array_key_exists('request.signing', $md)) {
			SimpleSAML_Logger::warning('Found deprecated \'request.signing\' metadata' .
				' option for entity ' . var_export($issuer, TRUE) . '.' .
				' Please replace with \'redirect.validate\' instead.');
			$validate = (bool)$md['request.signing'];
		} else {
			$validate = FALSE;
		}

		if (!$validate) {
			return false;
		}

		if (!isset($_GET['Signature'])) {
			throw new Exception('No Signature on the request, required by configuration');
		}
		
		SimpleSAML_Logger::debug('Library - HTTPRedirect validateQuery(): All required paramaters received.');

		// building query string
		$query = $request.'='.urlencode($_GET[$request]);

		if(array_key_exists('RelayState', $_GET)) {
			$relaystate = $_GET['RelayState'];
			$query .= "&RelayState=" . urlencode($relaystate);
		} 
		
		$algURI = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

		if (isset($_GET['SigAlg']) && $_GET['SigAlg'] != $algURI) {
			throw new Exception('Signature must be rsa-sha1 based');
		}

		$query = $query . "&" . "SigAlg=" . urlencode($algURI);
		
		SimpleSAML_Logger::debug('Library - HTTPRedirect validateQuery(): Built query: ' . $query);
		SimpleSAML_Logger::debug('Library - HTTPRedirect validateQuery(): Sig Alg: ' . $algURI);
				
				
		$publickey = SimpleSAML_Utilities::loadPublicKey($md, TRUE);
		if (!array_key_exists('PEM', $publickey)) {
			throw new Exception('We need a full public key to validate HTTP-Redirect signatures. A fingerprint is not enough.');
		}

		// getting signature from get arguments
		$signature = @base64_decode($_GET['Signature']);
		if (!$signature) {
			throw new Exception('Error base64 decoding signature parameter.');
		}

		// verify signature using xmlseclibs
		$xmlseckey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
		$xmlseckey->loadKey($publickey['PEM']);

		if (!$xmlseckey->verifySignature($query,$signature)) {
			throw new Exception("Unable to validate Signature");
		}

		//signature ok
		return true;
	}


	public function getRedirectURL($request, $localentityid, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP') {
	
		if (!in_array($mode, array('SP', 'IdP'))) {
			throw new Exception('mode parameter of sendMessage() must be either SP or IdP');
		}
		$metadataset = 'saml20-idp-remote';
		if ($mode == 'IdP') {
			$metadataset = 'saml20-sp-remote';
		}

		$md = $this->metadata->getMetaData($remoteentityid, $metadataset);
		
		$realendpoint = $endpoint;
		if ($endpoint == 'SingleLogoutServiceResponse' && !isset($md[$endpoint])) 
			$realendpoint = 'SingleLogoutService';
		
		$idpTargetUrl = $md[$realendpoint];
		
		if (!isset($idpTargetUrl) or $idpTargetUrl == '') {
			throw new Exception('Could not find endpoint [' .$endpoint  . '] in metadata for [' . $remoteentityid . '] (looking in ' . $metadataset . ')');
		}

		$request = urlencode( base64_encode( gzdeflate( $request ) ));
		$request = $direction . "=" . $request;
		if (isset($relayState)) {
			$request .= "&RelayState=" . urlencode($relayState);
		}

		$metadataset = 'saml20-sp-hosted';
		if ($mode == 'IdP') {
			$metadataset = 'saml20-idp-hosted';
		}
		$localmd = $this->metadata->getMetaData($localentityid, $metadataset);
		$request = $this->signQuery($request, $localmd, $md);
		$redirectURL = $idpTargetUrl . "?" . $request;

		return $redirectURL;
	
	}
	# $request, $localentityid, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP'
	public function sendMessage($request, $localentityid, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP') {
		
		SimpleSAML_Utilities::validateXMLDocument($request, 'saml20');

		$redirectURL = $this->getRedirectURL($request, $localentityid, $remoteentityid, $relayState, $endpoint, $direction, $mode);
		
		if ($this->configuration->getValue('debug')) {
	
			$p = new SimpleSAML_XHTML_Template($this->configuration, 'httpredirect-debug.php');
			
			$p->data['header'] = 'HTTP-REDIRECT Debug';
			$p->data['url'] = $redirectURL;
			$p->data['message'] = htmlspecialchars(SimpleSAML_Utilities::formatXMLString($request));
			
			$p->show();

		
		} else {

			SimpleSAML_Utilities::redirect($redirectURL);
		
		}

		
		
	}



	public function decodeRequest($get) {
		if (!isset($get['SAMLRequest'])) {
			throw new Exception('SAMLRequest parameter not set in paramter (on SAML 2.0 HTTP Redirect binding endpoint)');
		}
		$rawRequest = 	$get["SAMLRequest"];

		/* Check if the service provider has included a RelayState
		 * parameter with the request. This parameter should be
		 * included in the response to the SP after authentication.
		 */
		if(array_key_exists('RelayState', $get)) {
			$relaystate = $get['RelayState'];
		} else {
			$relaystate = NULL;
		}
		
		$decodedRequest = @base64_decode($rawRequest);
		if (!$decodedRequest) {
			throw new Exception('Could not base64 decode SAMLRequest GET parameter');
		}

		$samlRequestXML = @gzinflate($decodedRequest);
		if (!$samlRequestXML) {
			$error = error_get_last();
			throw new Exception('Could not gzinflate base64 decoded SAMLRequest: ' . $error['message'] );
		}		

		SimpleSAML_Utilities::validateXMLDocument($samlRequestXML, 'saml20');
		
		$samlRequest = new SimpleSAML_XML_SAML20_AuthnRequest($this->configuration, $this->metadata);
	
		$samlRequest->setXML($samlRequestXML);
		
		if (!is_null($relaystate)) {
			$samlRequest->setRelayState($relaystate);
		}

        return $samlRequest;
        
	}
	
	public function decodeLogoutRequest($get) {
		if (!isset($get['SAMLRequest'])) {
			throw new Exception('SAMLRequest parameter not set in paramter (on SAML 2.0 HTTP Redirect binding endpoint)');
		}
		$rawRequest = 	$get["SAMLRequest"];

		/* Check if a RelayState was provided with the request. */
		if(array_key_exists('RelayState', $get)) {
			$relaystate = $get['RelayState'];
		} else {
			$relaystate = NULL;
		}
		
		$decodedRequest = @base64_decode($rawRequest);
		if (!$decodedRequest) {
			throw new Exception('Could not base64 decode SAMLRequest GET parameter');
		}

		$samlRequestXML = @gzinflate($decodedRequest);
		if (!$samlRequestXML) {
			$error = error_get_last();
			throw new Exception('Could not gzinflate base64 decoded SAMLRequest: ' . $error['message'] );
		}		

		SimpleSAML_Utilities::validateXMLDocument($samlRequestXML, 'saml20');

		$samlRequest = new SimpleSAML_XML_SAML20_LogoutRequest($this->configuration, $this->metadata);
	
		$samlRequest->setXML($samlRequestXML);
		
		if (isset($relaystate)) {
			$samlRequest->setRelayState($relaystate);
		}
	
        #echo("Authn response = " . $samlResponse );

        return $samlRequest;
	}
	
	public function decodeLogoutResponse($get) {
		if (!isset($get['SAMLResponse'])) {
			throw new Exception('SAMLResponse parameter not set in paramter (on SAML 2.0 HTTP Redirect binding endpoint)');
		}
		$rawRequest = 	$get["SAMLResponse"];

		/* Check if a RelayState was provided with the request. */
		if(array_key_exists('RelayState', $get)) {
			$relaystate = $get['RelayState'];
		} else {
			$relaystate = NULL;
		}
		
		$decodedRequest = @base64_decode($rawRequest);
		if (!$decodedRequest) {
			throw new Exception('Could not base64 decode SAMLRequest GET parameter');
		}

		$samlRequestXML = @gzinflate($decodedRequest);
		if (!$samlRequestXML) {
			$error = error_get_last();
			throw new Exception('Could not gzinflate base64 decoded SAMLRequest: ' . $error['message'] );
		}		

		SimpleSAML_Utilities::validateXMLDocument($samlRequestXML, 'saml20');
		
         
		$samlRequest = new SimpleSAML_XML_SAML20_LogoutResponse($this->configuration, $this->metadata);
	
		$samlRequest->setXML($samlRequestXML);
		
		if (isset($relaystate)) {
			$samlRequest->setRelayState($relaystate);
		}
	
        #echo("Authn response = " . $samlResponse );

        return $samlRequest;
	}
	
}

?>