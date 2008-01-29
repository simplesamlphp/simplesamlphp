<?php
 
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
 
/**
 * The Shibboleth 1.3 Authentication Request. Not part of SAML 1.1, 
 * but an extension using query paramters no XML.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_SAML20_AuthnRequest {

	private $configuration = null;
	private $metadata = 'default.php';
	
	private $message = null;
	private $dom;
	private $relayState = null;
	
	
	const PROTOCOL = 'saml2';


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


	public function generate($spentityid, $destination) {
		$md = $this->metadata->getMetaData($spentityid);
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();

		//$assertionConsumerServiceURL = $md['AssertionConsumerService'];
		$assertionConsumerServiceURL = $this->metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted');
		
		
		$spNameQualifier = $md['spNameQualifier'];
		$nameidformat = isset($md['NameIDFormat']) ? $md['NameIDFormat'] : 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
		
		// TODO: Make an option in the metadata to allow adding a RequestedAuthnContext
		$requestauthncontext = '<samlp:RequestedAuthnContext Comparison="exact">
        <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
    </samlp:RequestedAuthnContext>';
		
		$authnRequest = '<samlp:AuthnRequest 
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="' . $id . '" Version="2.0"
    IssueInstant="' . $issueInstant . '" 
    Destination="' . htmlspecialchars($destination) . '"
    ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
    AssertionConsumerServiceURL="' . htmlspecialchars($assertionConsumerServiceURL) . '">
    <saml:Issuer >' . htmlspecialchars($spentityid) . '</saml:Issuer>
    <samlp:NameIDPolicy
        Format="' . htmlspecialchars($nameidformat) . '"
        AllowCreate="true"/>
    '  . '
</samlp:AuthnRequest>
';
		
		
		
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