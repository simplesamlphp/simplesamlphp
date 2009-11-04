<?php

/**
 * The Shibboleth 1.3 Authentication Request. Not part of SAML 1.1, 
 * but an extension using query paramters no XML.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_Shib13_AuthnRequest {

	private $metadata = null;
	
	private $issuer = null;
	private $shire = null;
	private $relayState = null;
	
	private $requestid = null;
	
	
	const PROTOCOL = 'shib13';


	function __construct() {
		
		$this->requestid = SimpleSAML_Utilities::generateID();
	}
	
	public function setRelayState($relayState) {
		$this->relayState = $relayState;
	}
	
	public function getRelayState() {
		return $this->relayState;
	}
	
	public function setShire($shire) {
		$this->shire = $shire;
	}
	
	public function getShire() {
		return $this->shire;
	}
	
	public function setIssuer($issuer) {
		$this->issuer = $issuer;
	}
	public function getIssuer() {
		return $this->issuer;
	}
	


	public function parseGet($get) {
		if (!isset($get['shire'])) throw new Exception('Could not read shire parameter from HTTP GET request');
		if (!isset($get['providerId'])) throw new Exception('Could not read providerId parameter from HTTP GET request');
		if (!isset($get['target'])) throw new Exception('Could not read target parameter from HTTP GET request');

		$this->setIssuer($get['providerId']);
		$this->setRelayState($get['target']);
		
		$this->setShire($get['shire']);

	}
	
	public function setNewRequestID() {	
		$this->requestid = SimpleSAML_Utilities::generateID();
	}
	
	public function getRequestID() {
		return $this->requestid;
	}

	
	public function createRedirect($destination, $shire = NULL) {
		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpmetadata = $metadata->getMetaDataConfig($destination, 'shib13-idp-remote');

		if ($shire === NULL) {
			$shire = $metadata->getGenerated('AssertionConsumerService', 'shib13-sp-hosted');
		}

		$desturl = $idpmetadata->getDefaultEndpoint('SingleSignOnService', array('urn:mace:shibboleth:1.0:profiles:AuthnRequest'));
		$desturl = $desturl['Location'];

		$target = $this->getRelayState();
		
		$url = $desturl . '?' .
	    	'providerId=' . urlencode($this->getIssuer()) .
		    '&shire=' . urlencode($shire) .
		    (isset($target) ? '&target=' . urlencode($target) : '');
		return $url;
	}

}

?>