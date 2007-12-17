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
require_once('SimpleSAML/XHTML/Template.php');


/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_Bindings_SAML20_HTTPRedirect {

	private $configuration = null;
	private $metadata = null;

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_XML_MetaDataStore $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	public function getRedirectURL($request, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP') {
	
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
	
		$encodedRequest = urlencode( base64_encode( gzdeflate( $request ) ));
		
		$redirectURL = $idpTargetUrl . "?" . $direction . "=" . $encodedRequest;
		if (isset($relayState)) {
			$redirectURL .= "&RelayState=" . urlencode($relayState);
		}
		return $redirectURL;
	
	}
	
	
	public function sendMessage($request, $remoteentityid, $relayState = null, $endpoint = 'SingleSignOnService', $direction = 'SAMLRequest', $mode = 'SP') {
		
		$redirectURL = $this->getRedirectURL($request, $remoteentityid, $relayState, $endpoint, $direction, $mode);
		
		if ($this->configuration->getValue('debug')) {
	
			$p = new SimpleSAML_XHTML_Template($this->configuration, 'httpredirect-debug.php');
			
			$p->data['header'] = 'HTTP-REDIRECT Debug';
			$p->data['url'] = $redirectURL;
			$p->data['message'] = htmlentities($request);
			
			$p->show();

		
		} else {

			header("Location: " . $redirectURL);

		
		}

		
		
	}



	public function decodeRequest($get) {
		if (!isset($get['SAMLRequest'])) {
			throw new Exception('SAMLRequest parameter not set in paramter (on SAML 2.0 HTTP Redirect binding endpoint)');
		}
		$rawRequest = 	$get["SAMLRequest"];
		/* We don't need to remove any magic quotes from the
		 * SAMLRequest parameter since this parameter is guaranteed
		 * to be base64-encoded.
		 */

		/* Check if the service provider has included a RelayState
		 * parameter with the request. This parameter should be
		 * included in the response to the SP after authentication.
		 */
		if(array_key_exists('RelayState', $get)) {
			$relaystate = $get['RelayState'];
			/* Remove any magic quotes that php may have added. */
			if(get_magic_quotes_gpc()) {
				$relaystate = stripslashes($relaystate);
			}
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
		$relaystate = isset($get["RelayState"]) ? $get["RelayState"] : null;
		
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
		$relaystate = isset($get["RelayState"]) ? $get["RelayState"] : null;
		
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