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
	 * The SessionIndexes of the sessions that should be terminated.
	 *
	 * @var array
	 */
	private $sessionIndexes;


	/**
	 * Constructor for SAML 2 logout request messages.
	 *
	 * @param DOMElement|NULL $xml  The input message.
	 */
	public function __construct(DOMElement $xml = NULL) {
		parent::__construct('LogoutRequest', $xml);

		$this->sessionIndexes = array();

		if ($xml === NULL) {
			return;
		}

		$nameId = SAML2_Utils::xpQuery($xml, './saml_assertion:NameID');
		if (empty($nameId)) {
			throw new Exception('Missing NameID in logout request.');
		}
		$this->nameId = SAML2_Utils::parseNameId($nameId[0]);

		$sessionIndexes = SAML2_Utils::xpQuery($xml, './saml_protocol:SessionIndex');
		foreach ($sessionIndexes as $sessionIndex) {
			$this->sessionIndexes[] = trim($sessionIndex->textContent);
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
	 * Retrieve the SessionIndexes of the sessions that should be terminated.
	 *
	 * @return array  The SessionIndexes, or an empty array if all sessions should be terminated.
	 */
	public function getSessionIndexes() {
		return $this->sessionIndexes;
	}


	/**
	 * Set the SessionIndexes of the sessions that should be terminated.
	 *
	 * @param array $sessionIndexes  The SessionIndexes, or an empty array if all sessions should be terminated.
	 */
	public function setSessionIndexes(array $sessionIndexes) {
		$this->sessionIndexes = $sessionIndexes;
	}


	/**
	 * Retrieve the sesion index of the session that should be terminated.
	 *
	 * @return string|NULL  The sesion index of the session that should be terminated.
	 */
	public function getSessionIndex() {

		if (empty($this->sessionIndexes)) {
			return NULL;
		}

		return $this->sessionIndexes[0];
	}


	/**
	 * Set the sesion index of the session that should be terminated.
	 *
	 * @param string|NULL $sessionIndex The sesion index of the session that should be terminated.
	 */
	public function setSessionIndex($sessionIndex) {
		assert('is_string($sessionIndex) || is_null($sessionIndex)');

		if (is_null($sessionIndex)) {
			$this->sessionIndexes = array();
		} else {
			$this->sessionIndexes = array($sessionIndex);
		}
	}


	/**
	 * Convert this logout request message to an XML element.
	 *
	 * @return DOMElement  This logout request.
	 */
	public function toUnsignedXML() {

		$root = parent::toUnsignedXML();

		SAML2_Utils::addNameId($root, $this->nameId);

		foreach ($this->sessionIndexes as $sessionIndex) {
			SAML2_Utils::addString($root, SAML2_Const::NS_SAMLP, 'SessionIndex', $sessionIndex);
		}

		return $root;
	}

}


?>