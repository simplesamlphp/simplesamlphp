<?php

/**
 * Class for SAML 2 logout request messages.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_LogoutRequest extends SAML2_Request {


	/**
	 * The name identifier of the session that should be terminated.
	 *
	 * @var array
	 */
	private $nameId;


	/**
	 * The session index of the session that should be terminated.
	 *
	 * @var string|NULL
	 */
	private $sessionIndex;


	/**
	 * Constructor for SAML 2 logout request messages.
	 *
	 * @param DOMElement|NULL $xml  The input message.
	 */
	public function __construct(DOMElement $xml = NULL) {
		parent::__construct('LogoutRequest', $xml);

		if ($xml === NULL) {
			return;
		}

		$nameId = SAML2_Utils::xpQuery($xml, './saml_assertion:NameID');
		if (empty($nameId)) {
			throw new Exception('Missing NameID in logout request.');
		}
		$this->nameId = SAML2_Utils::parseNameId($nameId[0]);

		$sessionIndex = SAML2_Utils::xpQuery($xml, './saml_protocol:SessionIndex');
		if (!empty($sessionIndex)) {
			$this->sessionIndex = trim($sessionIndex[0]->textContent);
		}
	}


	/**
	 * Retrieve the name identifier of the session that should be terminated.
	 *
	 * @return array  The name identifier of the session that should be terminated.
	 */
	public function getNameId() {
		return $this->nameId;
	}


	/**
	 * Set the name identifier of the session that should be terminated.
	 *
	 * The name identifier must be in the format accepted by SAML2_message::buildNameId().
	 *
	 * @see SAML2_message::buildNameId()
	 * @param array $nameId  The name identifier of the session that should be terminated.
	 */
	public function setNameId($nameId) {
		assert('is_array($nameId)');

		$this->nameId = $nameId;
	}


	/**
	 * Retrieve the sesion index of the session that should be terminated.
	 *
	 * @return string|NULL  The sesion index of the session that should be terminated.
	 */
	public function getSessionIndex() {
		return $this->sessionIndex;
	}


	/**
	 * Set the sesion index of the session that should be terminated.
	 *
	 * @param string|NULL $sessionIndex The sesion index of the session that should be terminated.
	 */
	public function setSessionIndex($sessionIndex) {
		assert('is_string($sessionIndex)');

		$this->sessionIndex = $sessionIndex;
	}


	/**
	 * Convert this logout request message to an XML element.
	 *
	 * @return DOMElement  This logout request.
	 */
	public function toUnsignedXML() {

		$root = parent::toUnsignedXML();

		SAML2_Utils::addNameId($root, $this->nameId);

		if ($this->sessionIndex !== NULL) {
			$sessionIndex = $this->document->createElementNS(SAML2_Const::NS_SAMLP, 'SessionIndex');
			$sessionIndex->appendChild($this->document->createTextNode($this->sessionIndex));
			$root->appendChild($sessionIndex);
		}

		return $root;
	}

}


?>