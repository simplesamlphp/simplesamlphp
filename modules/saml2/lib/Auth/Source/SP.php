<?php

/**
 * SAML 2.0 SP authentication client.
 *
 * Note: This authentication source is depreceated. You should
 * use saml:sp instead.
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
	 * The identifier for the stage where we have sent a discovery service request.
	 */
	const STAGE_DISCO = 'saml2:SP-DiscoSent';


	/**
	 * The identifier for the stage where we have sent a SSO request.
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
	 * The metadata for this SP.
	 *
	 * @var SimpleSAML_Configuration
	 */
	private $metadata;


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

		/* For compatibility with code that assumes that $metadata->getString('entityid') gives the entity id. */
		if (array_key_exists('entityId', $config)) {
			$config['entityid'] = $config['entityId'];
		} else {
			$config['entityid'] = SimpleSAML_Module::getModuleURL('saml2/sp/metadata.php?source=' . urlencode($this->authId));
		}

		/* For backwards-compatibility with configuration in saml20-sp-hosted. */
		try {
			$metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
			$oldMetadata = $metadataHandler->getMetaData($config['entityid'], 'saml20-sp-hosted');

			SimpleSAML_Logger::warning('Depreceated metadata for ' . var_export($config['entityid'], TRUE) .
				' in saml20-sp-hosted. The metadata in should be moved into authsources.php.');

			$config = array_merge($oldMetadata, $config);
		} catch (Exception $e) {};

		$this->metadata = SimpleSAML_Configuration::loadFromArray($config, 'authsources[' . var_export($this->authId, TRUE) . ']');

		$this->entityId = $this->metadata->getString('entityid');
		$this->idp = $this->metadata->getString('idp', NULL);
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

		if ($this->idp === NULL) {
			$this->initDisco($state);
		}

		$this->initSSO($this->idp, $state);
	}


	/**
	 * Send authentication request to specified IdP.
	 *
	 * @param string $idp  The IdP we should send the request to.
	 * @param array $state  Our state array.
	 */
	public function initDisco($state) {
		assert('is_array($state)');

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGE_DISCO);

		$config = SimpleSAML_Configuration::getInstance();

		$discoURL = $config->getString('idpdisco.url.saml20', NULL);
		if ($discoURL === NULL) {
			/* Fallback to internal discovery service. */
			$discoURL = SimpleSAML_Module::getModuleURL('saml2/disco.php');
		}

		$returnTo = SimpleSAML_Module::getModuleURL('saml2/sp/discoresp.php');
		$returnTo = SimpleSAML_Utilities::addURLparameter($returnTo, array('AuthID' => $id));

		SimpleSAML_Utilities::redirect($discoURL, array(
			'entityID' => $this->entityId,
			'return' => $returnTo,
			'returnIDParam' => 'idpentityid')
			);
	}


	/**
	 * Send authentication request to specified IdP.
	 *
	 * @param string $idp  The IdP we should send the request to.
	 * @param array $state  Our state array.
	 */
	public function initSSO($idp, $state) {
		assert('is_string($idp)');
		assert('is_array($state)');

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

		$idpMetadata = $metadata->getMetaDataConfig($idp, 'saml20-idp-remote');

		$ar = sspmod_saml2_Message::buildAuthnRequest($this->metadata, $idpMetadata);

		$ar->setAssertionConsumerServiceURL(SimpleSAML_Module::getModuleURL('saml2/sp/acs.php'));
		$ar->setProtocolBinding(SAML2_Const::BINDING_HTTP_POST);

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGE_SENT);
		$ar->setRelayState($id);

		$b = new SAML2_HTTPRedirect();
		$b->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$b->send($ar);

		assert('FALSE');
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
	 * Retrieve the metadata for this SP.
	 *
	 * @return SimpleSAML_Configuration  The metadata, as a configuration object.
	 */
	public function getMetadata() {

		return $this->metadata;
	}


	/**
	 * Retrieve the NameIDFormat used by this SP.
	 *
	 * @return string  NameIDFormat used by this SP.
	 */
	public function getNameIDFormat() {

		return $this->metadata->getString('NameIDFormat', 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient');
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

		if (array_key_exists('value', $nameId)) {
			/*
			 * This session was saved by an old version of simpleSAMLphp.
			 * Convert to the new NameId format.
			 *
			 * TODO: Remove this conversion once every session should use the new format.
			 */
			$nameId['Value'] = $nameId['value'];
			unset($nameId['value']);
		}

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $metadata->getMetaDataConfig($idp, 'saml20-idp-remote');

		$lr = sspmod_saml2_Message::buildLogoutRequest($this->metadata, $idpMetadata);
		$lr->setNameId($nameId);
		$lr->setSessionIndex($sessionIndex);
		$lr->setRelayState($id);

		$b = new SAML2_HTTPRedirect();
		$b->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$b->send($lr);

		assert('FALSE');
	}


	/**
	 * Called when we receive a logout request.
	 *
	 * @param string $idpEntityId  Entity id of the IdP.
	 */
	public function onLogout($idpEntityId) {
		assert('is_string($idpEntityId)');

		/* Call the logout callback we registered in onProcessingCompleted(). */
		$this->callLogoutCallback($idpEntityId);
	}


	/**
	 * Called when we have completed the procssing chain.
	 *
	 * @param array $authProcState  The processing chain state.
	 */
	public static function onProcessingCompleted(array $authProcState) {
		assert('array_key_exists("saml2:sp:IdP", $authProcState)');
		assert('array_key_exists("saml2:sp:State", $authProcState)');
		assert('array_key_exists("Attributes", $authProcState)');

		$idp = $authProcState['saml2:sp:IdP'];
		$state = $authProcState['saml2:sp:State'];

		$sourceId = $state[sspmod_saml2_Auth_Source_SP::AUTHID];
		$source = SimpleSAML_Auth_Source::getById($sourceId);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $sourceId);
		}

		/* Register a callback that we can call if we receive a logout request from the IdP. */
		$source->addLogoutCallback($idp, $state);

		$state['Attributes'] = $authProcState['Attributes'];
		SimpleSAML_Auth_Source::completeAuth($state);
	}

}

?>