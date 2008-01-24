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
class SimpleSAML_XML_SAML20_AuthnRequest {

	private $configuration = null;
	private $metadata = 'default.php';
	
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
		
		$requestelement = $dom->getElementsByTagName('AuthnRequest')->item(0);
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
	
	public function createSession() {
	
		
		$session = SimpleSAML_Session::getInstance();
		
		if (!isset($session)) {
			SimpleSAML_Session::init(self::PROTOCOL, null, false);
			$session = SimpleSAML_Session::getInstance();
		}

		$session->setAuthnRequest($this->getRequestID(), $this);
		
		/*
		if (isset($this->relayState)) {
			$session->setRelayState($this->relayState);
		}
		*/
		return $session;
	}
	

	public function generate($spentityid, $destination) {
		$md = $this->metadata->getMetaData($spentityid);
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();

		//$assertionConsumerServiceURL = $md['AssertionConsumerService'];
		$assertionConsumerServiceURL = $this->metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted');
		
		
		$spNameQualifier = $md['spNameQualifier'];
		$nameidformat = isset($md['NameIDFormat']) ? 
			$md['NameIDFormat'] : 
			'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
		
		$authnRequest = "<samlp:AuthnRequest  " .
		  "xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\"\n" .
		  "ID=\"" . $id . "\" " .
		  "Version=\"2.0\" " .
		  "IssueInstant=\"" . $issueInstant . "\" " .
		  "ForceAuthn=\"false\" " .
		  "IsPassive=\"false\" " .
		  "Destination=\"" . htmlspecialchars($destination) . "\" " .
		  "ProtocolBinding=\"urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST\" " .
		  "AssertionConsumerServiceURL=\"" . htmlspecialchars($assertionConsumerServiceURL) . "\">\n" .
			"<saml:Issuer " .
			"xmlns:saml=\"urn:oasis:names:tc:SAML:2.0:assertion\">" .
			  htmlspecialchars($spentityid) .
			"</saml:Issuer>\n" .
			"<samlp:NameIDPolicy  " .
			"xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\" " .
			"Format=\"" . htmlspecialchars($nameidformat). "\" " .
			"SPNameQualifier=\"" . htmlspecialchars($spNameQualifier) . "\" " .
			"AllowCreate=\"true\" />\n" . 
			"<samlp:RequestedAuthnContext " .
			"xmlns:samlp=\"urn:oasis:names:tc:SAML:2.0:protocol\" " .
			"Comparison=\"exact\">" .
			  "<saml:AuthnContextClassRef " .
			  "xmlns:saml=\"urn:oasis:names:tc:SAML:2.0:assertion\">" .
				"urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport" .
			  "</saml:AuthnContextClassRef>" .
			"</samlp:RequestedAuthnContext>\n" .
		  "</samlp:AuthnRequest>";
		  
		return $authnRequest;
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