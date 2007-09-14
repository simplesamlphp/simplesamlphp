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
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/XML/MetaDataStore.php');

require_once('xmlseclibs.php');
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XML_SAML20_LogoutResponse {

	private $configuration = null;
	private $metadata = null;
	
	private $message = null;
	private $dom;
	private $relayState = null;
	
	const PROTOCOL = 'urn:oasis:names:tc:SAML:2.0';

	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_XML_MetaDataStore $metadatastore) {
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
			
			/*
			if (isset($this->dom)) {
				return $this->dom;
			}
			*/
		
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
		if ($issuerNodes = $dom->getElementsByTagName('Issuer')) {
			if ($issuerNodes->length > 0) {
				$issuer = $issuerNodes->item(0)->textContent;
			}
		}
		return $issuer;
	}


	// Not updated for response. from request.
	public function generate($issuer, $receiver, $inresponseto, $mode ) {
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
		
	
		//echo 'idp:' . $idpentityid . ' sp:' . $spentityid .' inresponseto:' .  $inresponseto . ' namid:' . $nameid;
	
		$issuermd 	= $this->metadata->getMetaData($issuer, $issuerset);
		$receivermd = $this->metadata->getMetaData($receiver, $receiverset);
		
		$id = self::generateID();
		$issueInstant = self::generateIssueInstant();

		$destination = $receivermd['SingleLogOutUrl'];
		
		$samlResponse = '<samlp:LogoutResponse  xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
ID="_' . $id . '" Version="2.0" IssueInstant="' . $issueInstant . '" Destination="'. $destination. '" InResponseTo="' . $inresponseto . '">
<saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">' . $issuer . '</saml:Issuer>
<samlp:Status xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
<samlp:StatusCode  xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
Value="urn:oasis:names:tc:SAML:2.0:status:Success">
</samlp:StatusCode>
<samlp:StatusMessage xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol">
Successfully logged out from service ' . $issuer . '
</samlp:StatusMessage>
</samlp:Status>
</samlp:LogoutResponse>';

		return $samlResponse;
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
	
	public static function generateIssueInstant($offset = 0) {
		return gmdate("Y-m-d\TH:i:s\Z", time() + $offset);
	}
	
}

?>