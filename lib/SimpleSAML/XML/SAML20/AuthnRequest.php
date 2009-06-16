<?php
 
/**
 * The Shibboleth 1.3 Authentication Request. Not part of SAML 1.1, 
 * but an extension using query paramters no XML.
 *
 * @author Andreas Aakre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XML_SAML20_AuthnRequest {

	private $configuration = null;
	private $metadata = 'default.php';
	
	private $message = null;
	private $dom;
	private $relayState = null;
	private $isPassive = null;
	
	
	const PROTOCOL = 'saml2';

	/**
	 * This variable holds the generated request id for this request.
	 */
	private $id = null;


	function __construct(SimpleSAML_Configuration $configuration, SimpleSAML_Metadata_MetaDataStorageHandler $metadatastore) {
		$this->configuration = $configuration;
		$this->metadata = $metadatastore;

		/* Generate request id. */
		$this->id = SimpleSAML_Utilities::generateID();
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
				$issuer = trim($issuerNodes->item(0)->textContent);
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


	/**
	 * This function sets the IsPassive flag
	 *
	 */
	public function setIsPassive($isPassive) {
		$this->isPassive = $isPassive ? 'true' : 'false';
	}

	/**
	 * This function retrieves the IsPassive flag from this authentication request.
	 *
	 * @return The IsPassive flag from this authentication request.
	 */
	public function getIsPassive() {
		$dom = $this->getDOM();
		if (empty($dom)) {
			throw new Exception("Could not get message DOM in AuthnRequest object");
		}

		$root = $dom->documentElement;

		if(!$root->hasAttribute('IsPassive')) {
			/* ForceAuthn defaults to false. */
			return FALSE;
		}

		$ispas = $root->getAttribute('IsPassive');
		try{
			return $this->isSamlBoolTrue($ispas);
		}catch(Exception $e){
			// ... I don't understand ...
			// return FALSE;
			throw new Exception('Invalid value of IsPassive attribute in SAML2 AuthnRequest.');
		}
	}


	/**
	 * This function retrieves the ForceAuthn flag from this authentication request.
	 *
	 * @return The ForceAuthn flag from this authentication request.
	 */
	public function getForceAuthn() {
		$dom = $this->getDOM();
		if (empty($dom)) {
			throw new Exception("Could not get message DOM in AuthnRequest object");
		}

		$root = $dom->documentElement;

		if(!$root->hasAttribute('ForceAuthn')) {
			/* ForceAuthn defaults to false. */
			return FALSE;
		}

		$fa = $root->getAttribute('ForceAuthn');
		try{
			return $this->isSamlBoolTrue($fa);
		} catch(Exception $e){
			// ... I don't understand ...
			// return FALSE;
			throw new Exception('Invalid value of ForceAuthn attribute in SAML2 AuthnRequest.');
		}
	}



	/**
	 * Generate a new SAML 2.0 Authentication Request
	 *
	 * @param $spentityid SP Entity ID
	 * @param $destination SingleSignOnService endpoint
	 */
	public function generate($spentityid, $destination) {
		$md = $this->metadata->getMetaData($spentityid);
		
		$issueInstant = SimpleSAML_Utilities::generateTimestamp();

		$assertionConsumerServiceURL = $this->metadata->getGenerated('AssertionConsumerService', 'saml20-sp-hosted');
		
		/*
		 * Process the SAML 2.0 SP hosted metadata parameter: NameIDFormat
		 */
		$nameidformat = 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
		$includeNameIDPolicy = true;
		if (array_key_exists('NameIDFormat', $md)) {
			if (is_null($md['NameIDFormat'])) {
				$includeNameIDPolicy = false;
			} elseif (!is_string($md['NameIDFormat'])) {
				throw new Exception('SAML 2.0 SP hosted metadata parameter [NameIDFormat] must be a string.');
			} else {
				$nameidformat = $md['NameIDFormat'];
			}
		}
		if ($includeNameIDPolicy) {	
			$nameIDPolicy = $this->generateNameIDPolicy($nameidformat);
		}
		
		
		/*
		 * Process the SAML 2.0 SP hosted metadata parameter: ForceAuthn
		 */
		$forceauthn = 'false';
		if (isset($md['ForceAuthn'])) {
			if (is_bool($md['ForceAuthn'])) {
				$forceauthn = ($md['ForceAuthn'] ? 'true' : 'false');
			} else {
				throw new Exception('Illegal format of the ForceAuthn parameter in the SAML 2.0 SP hosted metadata for entity [' . $spentityid . ']. This value should be set to a PHP boolean value.');
			}
		}

		/*
		 * Process the SAML 2.0 SP hosted metadata parameter: AuthnContextClassRef
		 */
		$requestauthncontext = '';
		if (!empty($md['AuthnContextClassRef'])) {
			if (!is_string($md['AuthnContextClassRef'])) {
				throw new Exception('SAML 2.0 SP hosted metadata parameter [AuthnContextClassRef] must be a string.');
			}
			
			$requestauthncontext = '<samlp:RequestedAuthnContext Comparison="exact">
		<saml:AuthnContextClassRef>' . $md['AuthnContextClassRef'] . '</saml:AuthnContextClassRef>
	</samlp:RequestedAuthnContext>';
		}


		/* Check the metadata for isPassive if $this->isPassive === NULL. */
		if($this->isPassive === NULL) {
			/*
			 * Process the SAML 2.0 SP hosted metadata parameter: IsPassive
			 */
			if (isset($md['IsPassive'])) {
				if (is_bool($md['IsPassive'])) {
					$this->isPassive = ($md['IsPassive'] ? 'true' : 'false');
				} else {
					throw new Exception('Illegal format of the IsPassive parameter in' .
						' the SAML 2.0 SP hosted metadata for entity [' . $spentityid .
						']. This value should be set to a PHP boolean value.');
				}
			} else {
				/* The default is off. */
				$this->isPassive = 'false';
			}
		}
		

		/*
		 * Create the complete SAML 2.0 Authentication Request
		 */
		$authnRequest = '<samlp:AuthnRequest 
	xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
	ID="' . $this->id . '" Version="2.0"
	IssueInstant="' . $issueInstant . '" ForceAuthn="' . $forceauthn . '" IsPassive="' . $this->isPassive . '"
	Destination="' . htmlspecialchars($destination) . '"
	ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
	AssertionConsumerServiceURL="' . htmlspecialchars($assertionConsumerServiceURL) . '">
	<saml:Issuer >' . htmlspecialchars($spentityid) . '</saml:Issuer>
	' . $nameIDPolicy . '
	' . $requestauthncontext . '
</samlp:AuthnRequest>
';

		return $authnRequest;
	}
	
	/**
	 * Generate a NameIDPoliy element
	 *
	 * @param $nameidformat NameIDFormat. 
	 */
	public function generateNameIDPolicy($nameidformat) {
		return '<samlp:NameIDPolicy
		Format="' . htmlspecialchars($nameidformat) . '"
		AllowCreate="true" />';
	}


	/**
	 * Retrieves the request id we used for the generated authentication request.
	 *
	 * @return  The request id of the generated authentication request.
	 */
	public function getGeneratedID() {
		return $this->id;
	}
	
	
	/**
	 * Check if a saml attribute value is a legal bool and if it is true or false.
	 * SAML legal bool values is true/false or 1/0.
	 * 
	 * @throws Exception when no legal bool value is found
	 * @param string $boolSaml
	 * @return bool TRUE or FALSE
	 */
	private function isSamlBoolTrue($boolSaml){
		$boolSaml = strtolower($boolSaml);
		if($boolSaml === 'true' || $boolSaml === '1') {
			return TRUE;
		} elseif($boolSaml === 'false' || $boolSaml === '0') {
			return FALSE;
		} else {
			throw new Exception('Invalid bool value of attribute in SAML2 AuthnRequest.');
		}
	}

}

?>