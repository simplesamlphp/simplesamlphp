<?php

/**
 * Implementation of the SAML 2.0 LogoutResponse message.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_SAML20_LogoutResponse {

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


	/**
	 * This function retrieves the InResponseTo attribute value from the logout response.
	 *
	 * @return The InResponseTo attribute value from the logout response.
	 */
	public function getInResponseTo() {
		$dom = $this->getDOM();

		$responseElement = $dom->getElementsByTagName('LogoutResponse')->item(0);
		$inResponseTo = $responseElement->getAttribute('InResponseTo');

		if(empty($inResponseTo)) {
			throw new Exception('Empty InResponseTo attribute on SAML2 logout response.');
		}

		return $inResponseTo;
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
		
		$id = SimpleSAML_Utilities::generateID();
		$issueInstant = SimpleSAML_Utilities::generateTimestamp();

		$destination = $receivermd['SingleLogoutService'];
		if (isset($receivermd['SingleLogoutServiceResponse'])) {
			$destination = $receivermd['SingleLogoutServiceResponse'];
		}
		
		$samlResponse = '<samlp:LogoutResponse 
    xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
    xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
    ID="' . $id . '" Version="2.0"
    IssueInstant="' . $issueInstant . '"
    Destination="'. htmlspecialchars($destination). '"
    InResponseTo="' . htmlspecialchars($inresponseto) . '">
    <saml:Issuer>' . htmlspecialchars($issuer) . '</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"> </samlp:StatusCode>
        <samlp:StatusMessage>Successfully logged out from service ' . htmlspecialchars($issuer) . '</samlp:StatusMessage>
    </samlp:Status>
</samlp:LogoutResponse>
';

		return $samlResponse;
	}

}

?>