<?php

/**
 * SAML 2.0 SP authentication client.
 *
 * Example:
 * 'example-openidp' => array(
 *   'saml2:SP',
 *   'idp' => 'https://openidp.feide.no',
 * ),
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_saml2_Auth_Source_SP extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGE_SENT = 'saml2:SP-SSOSent';

	/**
	 * The string used to identify our logout state.
	 */
	const STAGE_LOGOUTSENT = 'saml2:SP-LogoutSent';


	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'saml2:AuthId';


	/**
	 * The key for the IdP entity id in the logout state.
	 */
	const LOGOUT_IDP = 'saml2:SP-Logout-IdP';

	/**
	 * The key for the NameID in the logout state.
	 */
	const LOGOUT_NAMEID = 'saml2:SP-Logout-NameID';


	/**
	 * The key for the SessionIndex in the logout state.
	 */
	const LOGOUT_SESSIONINDEX = 'saml2:SP-Logout-SessionIndex';


	/**
	 * The entity id of this SP.
	 */
	private $entityId;


	/**
	 * The entity id of the IdP we connect to.
	 */
	private $idp;


	/**
	 * Constructor for SAML 2.0 SP authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		if (array_key_exists('entityId', $config)) {
			$this->entityId = $config['entityId'];
		} else {
			$this->entityId = SimpleSAML_Module::getModuleURL('saml2/sp/metadata.php?source=' . urlencode($this->authId));
		}

		if (array_key_exists('idp', $config)) {
			$this->idp = $config['idp'];
		} else {
			throw new Exception('TODO: Add support for IdP discovery.');
		}
	}


	/**
	 * Start login.
	 *
	 * This function saves the information about the login, and redirects to  the IdP.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGE_SENT);

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $metadata->getMetaData($this->idp, 'saml20-idp-remote');

		$config = SimpleSAML_Configuration::getInstance();
		$sr = new SimpleSAML_XML_SAML20_AuthnRequest($config, $metadata);
		$req = $sr->generate($this->entityId, $idpMetadata['SingleSignOnService']);

		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		$httpredirect->sendMessage($req, $this->entityId, $this->idp, $id);
		exit(0);
	}


	/**
	 * Retrieve the entity id of this SP.
	 *
	 * @return string  Entity id of this SP.
	 */
	public function getEntityId() {

		return $this->entityId;
	}


	/**
	 * Retrieve the NameIDFormat used by this SP.
	 *
	 * @return string  NameIDFormat used by this SP.
	 */
	public function getNameIDFormat() {

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$spmeta = $metadata->getMetadata($this->getEntityId(), 'saml20-sp-hosted');

		if (array_key_exists('NameIDFormat', $spmeta)) {
			return $spmeta['NameIDFormat'];
		} else {
			return 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient';
		}
	}


	/**
	 * Check if the IdP entity id is allowed to authenticate users for this authentication source.
	 *
	 * @param string $idpEntityId  The entity id of the IdP.
	 * @return boolean  TRUE if it is valid, FALSE if not.
	 */
	public function isIdPValid($idpEntityId) {
		assert('is_string($idpEntityId)');

		if ($this->idp === NULL) {
			/* No IdP configured - all are allowed. */
			return TRUE;
		}

		if ($this->idp === $idpEntityId) {
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * Handle logout operation.
	 *
	 * @param array $state  The logout state.
	 */
	public function logout(&$state) {
		assert('is_array($state)');
		assert('array_key_exists(self::LOGOUT_IDP, $state)');
		assert('array_key_exists(self::LOGOUT_NAMEID, $state)');
		assert('array_key_exists(self::LOGOUT_SESSIONINDEX, $state)');

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGE_LOGOUTSENT);

		$idp = $state[self::LOGOUT_IDP];
		$nameId = $state[self::LOGOUT_NAMEID];
		$sessionIndex = $state[self::LOGOUT_SESSIONINDEX];

		$config = SimpleSAML_Configuration::getInstance();
		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
		$req = $lr->generate($this->entityId, $idp, $nameId, $sessionIndex, 'SP');

		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		$httpredirect->sendMessage($req, $this->entityId, $idp, $id, 'SingleLogoutService', 'SAMLRequest', 'SP');

		exit(0);
	}


	/**
	 * Called when we are logged in.
	 *
	 * @param string $idpEntityId  Entity id of the IdP.
	 * @param array $state  The state of the authentication operation.
	 */
	public function onLogin($idpEntityId, $state) {
		assert('is_string($idpEntityId)');
		assert('is_array($state)');

		$this->addLogoutCallback($idpEntityId, $state);
	}


	/**
	 * Called when we receive a logout request.
	 *
	 * @param string $idpEntityId  Entity id of the IdP.
	 */
	public function onLogout($idpEntityId) {
		assert('is_string($idpEntityId)');

		$this->callLogoutCallback($idpEntityId);
	}

}

?>