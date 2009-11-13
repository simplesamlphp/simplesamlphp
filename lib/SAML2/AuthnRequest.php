<?php

/**
 * Class for SAML 2 authentication request messages.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_AuthnRequest extends SAML2_Request {

	/**
	 * The options for what type of name identifier should be returned.
	 *
	 * @var array
	 */
	private $nameIdPolicy;

	/**
	 * Whether the Identity Provider must authenticate the user again.
	 *
	 * @var bool
	 */
	private $forceAuthn;


	/**
	 * Set to TRUE if this request is passive.
	 *
	 * @var bool.
	 */
	private $isPassive;

	/**
	 * The list of providerIDs in this request's scoping element
	 *
	 * @var array
	*/
	private $IDPList = array();

	/**
	 * The URL of the asertion consumer service where the response should be delivered.
	 *
	 * @var string|NULL
	 */
	private $assertionConsumerServiceURL;


	/**
	 * What binding should be used when sending the response.
	 *
	 * @var string|NULL
	 */
	private $protocolBinding;


	/**
	 * What authentication context was requested.
	 *
	 * Array with the following elements.
	 * - AuthnContextClassRef (required)
	 * - Comparison (optinal)
	 *
	 * @var array
	 */
	private $requestedAuthnContext;


	/**
	 * Constructor for SAML 2 authentication request messages.
	 *
	 * @param DOMElement|NULL $xml  The input message.
	 */
	public function __construct(DOMElement $xml = NULL) {
		parent::__construct('AuthnRequest', $xml);

		$this->nameIdPolicy = array();
		$this->forceAuthn = FALSE;
		$this->isPassive = FALSE;

		if ($xml === NULL) {
			return;
		}

		$this->forceAuthn = SAML2_Utils::parseBoolean($xml, 'ForceAuthn', FALSE);
		$this->isPassive = SAML2_Utils::parseBoolean($xml, 'IsPassive', FALSE);

		if ($xml->hasAttribute('AssertionConsumerServiceURL')) {
			$this->assertionConsumerServiceURL = $xml->getAttribute('AssertionConsumerServiceURL');
		}

		if ($xml->hasAttribute('ProtocolBinding')) {
			$this->protocolBinding = $xml->getAttribute('ProtocolBinding');
		}

		$nameIdPolicy = SAML2_Utils::xpQuery($xml, './saml_protocol:NameIDPolicy');

		if (!empty($nameIdPolicy)) {
			$nameIdPolicy = $nameIdPolicy[0];
			if ($nameIdPolicy->hasAttribute('Format')) {
				$this->nameIdPolicy['Format'] = $nameIdPolicy->getAttribute('Format');
			}
			if ($nameIdPolicy->hasAttribute('SPNameQualifier')) {
				$this->nameIdPolicy['SPNameQualifier'] = $nameIdPolicy->getAttribute('SPNameQualifier');
			}
			if ($nameIdPolicy->hasAttribute('AllowCreate')) {
				$this->nameIdPolicy['AllowCreate'] = SAML2_Utils::parseBoolean($nameIdPolicy, 'AllowCreate', FALSE);
			}
		}

		$requestedAuthnContext = SAML2_Utils::xpQuery($xml, './saml_protocol:RequestedAuthnContext');
		if (!empty($requestedAuthnContext)) {
			$requestedAuthnContext = $requestedAuthnContext[0];

			$rac = array(
				'AuthnContextClassRef' => array(),
				'Comparison' => 'exact',
			);

			$accr = SAML2_Utils::xpQuery($requestedAuthnContext, './saml_assertion:AuthnContextClassRef');
			foreach ($accr as $i) {
				$rac['AuthnContextClassRef'][] = trim($i->textContent);
			}

			if ($requestedAuthnContext->hasAttribute('Comparison')) {
				$rac['Comparison'] = $requestedAuthnContext->getAttribute('Comparison');
			}

			$this->requestedAuthnContext = $rac;
		}

		$idpEntries = SAML2_Utils::xpQuery($xml, './saml_protocol:Scoping/saml_protocol:IDPList/saml_protocol:IDPEntry');

		foreach($idpEntries as $idpEntry) {
			if (!$idpEntry->hasAttribute('ProviderID')) {
				throw new Exception("Could not get ProviderID from Scoping/IDPEntry element in AuthnRequest object");
			}
			$this->IDPList[] = $idpEntry->getAttribute('ProviderID');
		}
	}


	/**
	 * Retrieve the NameIdPolicy.
	 *
	 * @see SAML2_AuthnRequest::setNameIdPolicy()
	 * @return array  The NameIdPolicy.
	 */
	public function getNameIdPolicy() {
		return $this->nameIdPolicy;
	}


	/**
	 * Set the NameIDPolicy.
	 *
	 * This function accepts an array with the following options:
	 *  - 'Format'
	 *  - 'SPNameQualifier'
	 *  - 'AllowCreate'
	 *
	 * @param array $nameIdPolicy  The NameIDPolicy.
	 */
	public function setNameIdPolicy(array $nameIdPolicy) {

		$this->nameIdPolicy = $nameIdPolicy;
	}


	/**
	 * Retrieve the value of the ForceAuthn attribute.
	 *
	 * @return bool  The ForceAuthn attribute.
	 */
	public function getForceAuthn() {
		return $this->forceAuthn;
	}


	/**
	 * Set the value of the ForceAuthn attribute.
	 *
	 * @param bool $forceAuthn  The ForceAuthn attribute.
	 */
	public function setForceAuthn($forceAuthn) {
		assert('is_bool($forceAuthn)');

		$this->forceAuthn = $forceAuthn;
	}


	/**
	 * Retrieve the value of the IsPassive attribute.
	 *
	 * @return bool  The IsPassive attribute.
	 */
	public function getIsPassive() {
		return $this->isPassive;
	}


	/**
	 * Set the value of the IsPassive attribute.
	 *
	 * @param bool $isPassive  The IsPassive attribute.
	 */
	public function setIsPassive($isPassive) {
		assert('is_bool($isPassive)');

		$this->isPassive = $isPassive;
	}


	/**
	 * This function sets the scoping for the request
	 * See Core 3.4.1.2 for the definition of scoping
	 * Currently we only support an IDPList of idpEntries
	 * and only the required ProviderID in an IDPEntry
	 * $providerIDs is an array of Entity Identifiers
	 *
	 */
	public function setIDPList($IDPList) {
		assert('is_array($IDPList)');
		$this->IDPList = $IDPList;
	}


	/**
	 * This function retrieves the list of providerIDs from this authentication request.
	 * Currently we only support a list of ipd ientity id's.
	 * @return The list of idpidentityids from the request
	 */
	 
	public function getIDPList() {
		return $this->IDPList;
	}


	/**
	 * Retrieve the value of the AssertionConsumerServiceURL attribute.
	 *
	 * @return string|NULL  The AssertionConsumerServiceURL attribute.
	 */
	public function getAssertionConsumerServiceURL() {
		return $this->assertionConsumerServiceURL;
	}


	/**
	 * Set the value of the AssertionConsumerServiceURL attribute.
	 *
	 * @param string|NULL $assertionConsumerServiceURL  The AssertionConsumerServiceURL attribute.
	 */
	public function setAssertionConsumerServiceURL($assertionConsumerServiceURL) {
		assert('is_string($assertionConsumerServiceURL) || is_null($assertionConsumerServiceURL)');

		$this->assertionConsumerServiceURL = $assertionConsumerServiceURL;
	}


	/**
	 * Retrieve the value of the ProtocolBinding attribute.
	 *
	 * @return string|NULL  The ProtocolBinding attribute.
	 */
	public function getProtocolBinding() {
		return $this->protocolBinding;
	}


	/**
	 * Set the value of the ProtocolBinding attribute.
	 *
	 * @param string $protocolBinding  The ProtocolBinding attribute.
	 */
	public function setProtocolBinding($protocolBinding) {
		assert('is_string($protocolBinding) || is_null($protocolBinding)');

		$this->protocolBinding = $protocolBinding;
	}


	/**
	 * Retrieve the RequestedAuthnContext.
	 *
	 * @return array|NULL  The RequestedAuthnContext.
	 */
	public function getRequestedAuthnContext() {
		return $this->requestedAuthnContext;
	}


	/**
	 * Set the RequestedAuthnContext.
	 *
	 * @param array|NULL $requestedAuthnContext  The RequestedAuthnContext.
	 */
	public function setRequestedAuthnContext($requestedAuthnContext) {
		assert('is_array($requestedAuthnContext) || is_null($requestedAuthnContext)');

		$this->requestedAuthnContext = $requestedAuthnContext;
	}


	/**
	 * Convert this authentication request to an XML element.
	 *
	 * @return DOMElement  This authentication request.
	 */
	public function toUnsignedXML() {

		$root = parent::toUnsignedXML();

		if ($this->forceAuthn) {
			$root->setAttribute('ForceAuthn', 'true');
		}

		if ($this->isPassive) {
			$root->setAttribute('IsPassive', 'true');
		}

		if ($this->assertionConsumerServiceURL !== NULL) {
			$root->setAttribute('AssertionConsumerServiceURL', $this->assertionConsumerServiceURL);
		}

		if ($this->protocolBinding !== NULL) {
			$root->setAttribute('ProtocolBinding', $this->protocolBinding);
		}

		if (!empty($this->nameIdPolicy)) {
			$nameIdPolicy = $this->document->createElementNS(SAML2_Const::NS_SAMLP, 'NameIDPolicy');
			if (array_key_exists('Format', $this->nameIdPolicy)) {
				$nameIdPolicy->setAttribute('Format', $this->nameIdPolicy['Format']);
			}
			if (array_key_exists('SPNameQualifier', $this->nameIdPolicy)) {
				$nameIdPolicy->setAttribute('SPNameQualifier', $this->nameIdPolicy['SPNameQualifier']);
			}
			if (array_key_exists('AllowCreate', $this->nameIdPolicy) && $this->nameIdPolicy['AllowCreate']) {
				$nameIdPolicy->setAttribute('AllowCreate', 'true');
			}
			$root->appendChild($nameIdPolicy);
		}

		$rac = $this->requestedAuthnContext;
		if (!empty($rac) && !empty($rac['AuthnContextClassRef'])) {
			$e = $this->document->createElementNS(SAML2_Const::NS_SAMLP, 'RequestedAuthnContext');
			$root->appendChild($e);
			if (isset($rac['Comparison']) && $rac['Comparison'] !== 'exact') {
				$e->setAttribute('Comparison', $rac['Comparison']);
			}
			foreach ($rac['AuthnContextClassRef'] as $accr) {
				$i = $this->document->createElementNS(SAML2_Const::NS_SAML, 'AuthnContextClassRef');
				$i->appendChild($this->document->createTextNode($accr));
				$e->appendChild($i);
			}
		}

		if (count($this->IDPList) > 0) {
			$scoping = $this->document->createElementNS(SAML2_Const::NS_SAMLP, 'Scoping');
			$idplist = $this->document->createElementNS(SAML2_Const::NS_SAMLP, 'IDPList');
			foreach ($this->IDPList as $provider) {
				$idpEntry = $this->document->createElementNS(SAML2_Const::NS_SAMLP, 'IDPEntry');
				$idpEntry->setAttribute('ProviderID', $provider);
				$idplist->appendChild($idpEntry);
			}
			$scoping->appendChild($idplist);
			$root->appendChild($scoping);
		}

		return $root;
	}

}


?>