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
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XML_Shib13_AuthnRequest {

	private $configuration = null;
	private $metadata = null;
	
	private $issuer = null;
	private $shire = null;
	private $relayState = null;
	
	private $requestid = null;
	
	
	const PROTOCOL = 'shibboleth';


	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
		
		$this->requestid = $this->generateID();
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
		$this->requestid = $this->generateID();
	}
	
	public function getRequestID() {
		return $this->requestid;
	}
	
	public function createSession() {
		
		$session = SimpleSAML_Session::getInstance();
		
		if (!isset($session)) {
			SimpleSAML_Session::init(self::PROTOCOL, null, false);
			$session = SimpleSAML_Session::getInstance();
		}

		$session->setShibAuthnRequest($this);
		
		/*
		if (isset($this->relayState)) {
			$session->setRelayState($this->relayState);
		}
		*/
		return $session;
	}
	
	public function createRedirect($destination) {
		$idpmetadata = $this->metadata->getMetaData($destination, 'shib13-idp-remote');
		$spmetadata = $this->metadata->getMetaData($this->getIssuer(), 'shib13-sp-hosted');
	
		$desturl = $idpmetadata['SingleSignOnUrl'];
		$shire = $this->metadata->getGenerated('AssertionConsumerService', 'shib13-sp-hosted');
		$target = $this->getRelayState();
		
		$url = $desturl . '?' .
	    	'providerId=' . urlencode($this->getIssuer()) .
		    '&shire=' . urlencode($shire) .
		    (isset($target) ? '&target=' . urlencode($target) : '');
		return $url;
	}
	
	public static function generateID() {
		$length = 42;
		$key = "_";
		for ( $i=0; $i < $length; $i++ ) {
			 $key .= dechex( rand(0,15) );
		}
		return $key;
	}
	
	
}

?>