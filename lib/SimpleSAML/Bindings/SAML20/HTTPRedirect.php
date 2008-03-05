<?php

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'xmlseclibs.php');

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


	public function signQuery($query, $md) {

		/* Check if signing of HTTP-Redirect messages is enabled. */
		
		if (!array_key_exists('request.signing', $md) || !$md['request.signing']){ 
			return $query;
		}

		/* Load the private key. */

		$privatekey = $this->configuration->getPathValue('certdir') . $md['privatekey'];
		if (!file_exists($privatekey)) {
			throw new Exception('Could not find private key file [' . $privatekey . '] which is needed to sign the request.');
		}

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
		$xmlseckey->loadKey($privatekey,TRUE);
        $signature = $xmlseckey->signData($query);
                
		$query = $query . "&" . "Signature=" . urlencode(base64_encode($signature));

		return $query;
	}
	
	public function validateQuery($issuer,$mode = 'SP',$request = 'SAMLRequest') {

		$metadataset = 'saml20-idp-remote';
		if ($mode == 'IdP') {
			$metadataset = 'saml20-sp-remote';
		}
		SimpleSAML_Logger::debug('Library - HTTPRedirect validateQuery(): Looking up metadata issuer:' . $issuer . ' in set '. $metadataset);
		$md = $this->metadata->getMetaData($issuer, $metadataset);
		
		// check wether to validate or not
		if (!array_key_exists('request.signing', $md) || !$md['request.signing']){ 
			return false;
		}

		if (!isset($_GET['Signature'])) {
			throw new Exception('No Signature on the request, required by configuration');
		}

		// building query string
		$query = $request.'='.urlencode($_GET[$request]);

		if($_GET['RelayState']) {
			$relaystate = $_GET['RelayState'];
			$query .= "&RelayState=" . urlencode($relaystate);
		} 
		
		$algURI = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';

		if (isset($_GET['SigAlg']) && $_GET['SigAlg'] != $algURI) {
			throw new Exception('Signature must be rsa-sha1 based');
		}

		$query = $query . "&" . "SigAlg=" . urlencode($algURI);
				
		// check if public key of sp exists
		$publickey = $this->configuration->getPathValue('certdir') . $md['certificate'];
		if (!file_exists($publickey)) {
			throw new Exception('Could not find private key file [' . $publickey . '] which is needed to verify the request.');
		}

		// getting signature from get arguments
		$signature = base64_decode(($_GET['Signature']));

		// verify signature using xmlseclibs
		$xmlseckey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'public'));
		$xmlseckey->loadKey($publickey,TRUE);

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
		$request = $this->signQuery($request, $localmd);
		$redirectURL = $idpTargetUrl . "?" . $request;

		return $redirectURL;
	
	}
	
	public function sendMessage($request, $localentityid, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP') {
		
		$redirectURL = $this->getRedirectURL($request, $localentityid, $remoteentityid, $relayState, $endpoint, $direction, $mode);
		
		if ($this->configuration->getValue('debug')) {
	
			$p = new SimpleSAML_XHTML_Template($this->configuration, 'httpredirect-debug.php');
			
			$p->data['header'] = 'HTTP-REDIRECT Debug';
			$p->data['url'] = $redirectURL;
			$p->data['message'] = htmlentities($request);
			
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
		
		$samlRequestXML = gzinflate(base64_decode( $rawRequest ));
         
		$samlRequest = new SimpleSAML_XML_SAML20_AuthnRequest($this->configuration, $this->metadata);
	
		$samlRequest->setXML($samlRequestXML);
		
		if (isset($relaystate)) {
			$samlRequest->setRelayState($relaystate);
		}
	
        #echo("Authn response = " . $samlResponse );

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
		
		$samlRequestXML = gzinflate(base64_decode( $rawRequest ));
         
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
		
		$samlRequestXML = gzinflate(base64_decode( $rawRequest ));
         
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