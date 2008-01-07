<?php


/**
 * SimpleSAMLphp
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
class SimpleSAML_XML_SAML20_LogoutRequest {

	private $configuration = null;
	private $metadata = null;
	
	private $message = null;
	private $dom;
	private $relayState = null;
	
	
	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;
	}
	
	public function setXML($xml) {
		$this->message = $xml;
	}
	
	public function getXML() {
		return $this->message;
	}
	
	public function setRelayState($relayState) {
		$this->relayState = $relayState;
	}
	
	public function getRelayState() {
		return $this->relayState;
	}
	
	public function getDOM() {
		if (isset($this->message) ) {
		
			/* if (isset($this->dom) && $this->dom != null ) {
				return $this->dom;
			} */
		
			$token = new DOMDocument();
			$token->loadXML(str_replace ("\r", "", $this->message));
			if (empty($token)) {
				throw new Exception("Unable to load token");
			}
			$this->dom = $token;
			return $this->dom;
		
		} 
		
		return null;
	}
	
	
	public function getIssuer() {
		$dom = $this->getDOM();
		$issuer = null;
		
		if (!$dom instanceof DOMDocument) {
			throw new Exception("Could not get message DOM in AuthnRequest object");
		}
		
		//print_r($dom->saveXML());
		
		if ($issuerNodes = $dom->getElementsByTagName('Issuer')) {
			if ($issuerNodes->length > 0) {
				$issuer = $issuerNodes->item(0)->textContent;
			}
		}
		return $issuer;
	}
	
	public function getRequestID() {
		$dom = $this->getDOM();
		$requestid = null;
		
		if (empty($dom)) {
			throw new Exception("Could not get message DOM in AuthnRequest object");
		}
		
		$requestelement = $dom->getElementsByTagName('LogoutRequest')->item(0);
		$requestid = $requestelement->getAttribute('ID');
		return $requestid;
		/*
		if ($issuerNodes = $dom->getElementsByTagName('Issuer')) {
			if ($issuerNodes->length > 0) {
				$requestid = $issuerNodes->item(0)->textContent;
			}
		}
		return $requestid;	
		*/
	}
	


	public function generate($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
	
		if (!in_array($mode, array('SP', 'IdP'))) {
			throw new Exception('mode parameter of generate() must be either SP or IdP');
		}
		if ($mode == 'IdP') {
			$issuerset = 'saml20-idp-hosted';
			$receiverset = 'saml20-sp-remote';
		} else {
			$issuerset = 'saml20-sp-hosted';
			$receiverset = 'saml20-idp-remote';
		}
	
		$issuermd 	= $this->metadata->getMetaData($issuer, $issuerset);
		$receivermd = $this->metadata->getMetaData($receiver, $receiverset);
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();

		$destination = $receivermd['SingleLogoutService'];

/*
		$spNameQualifier = $md['spNameQualifier'];
		$nameidformat = isset($md['NameIDformat']) ? 
			$md['NameIDformat'] : 
			'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent';
	*/	
		$logoutRequest = "<samlp:LogoutRequest " .
      "xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\" " . 
      "ID=\"" . $id . "\" " .
      "Version=\"2.0\" " .
      "IssueInstant=\"" . $issueInstant . "\"> " .
        "<saml:Issuer " . 
        "xmlns:saml=\"urn:oasis:names:tc:SAML:2.0:assertion\">" .
          $issuer .
        "</saml:Issuer>" .
        "<saml:NameID " . 
        "xmlns:saml=\"urn:oasis:names:tc:SAML:2.0:assertion\" " . 
//        "NameQualifier=\"" . $nameId["NameQualifier"] . "\" " . 
//        "SPNameQualifier=\"" . $nameId["SPNameQualifier"] . "\" " . 
        "Format=\"" .  $nameidformat. "\">" . 
          $nameid . 
        "</saml:NameID>" . 
        "<samlp:SessionIndex " .
        "xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\">" . 
          $sessionindex .
        "</samlp:SessionIndex>" .
      "</samlp:LogoutRequest>";
		  
		return $logoutRequest;
	}
	
	public static function generateID() {
	
		$length = 42;
		$key = "_";
		for ( $i=0; $i < $length; $i++ )
		{
			 $key .= dechex( rand(0,15) );
		}
		return $key;
	}
	
	public static function generateIssueInstant() {
		return gmdate("Y-m-d\TH:i:s\Z");
	}

}

?>