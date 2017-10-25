<?php

class sspmod_saml_Auth_Source_SP extends SimpleSAML_Auth_Source {

	/**
	 * The entity ID of this SP.
	 *
	 * @var string
	 */
	private $entityId;


	/**
	 * The metadata of this SP.
	 *
	 * @var SimpleSAML_Configuration.
	 */
	private $metadata;


	/**
	 * The IdP the user is allowed to log into.
	 *
	 * @var string|NULL  The IdP the user can log into, or NULL if the user can log into all IdPs.
	 */
	private $idp;


	/**
	 * URL to discovery service.
	 *
	 * @var string|NULL
	 */
	private $discoURL;


	/**
	 * Constructor for SAML SP authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		// Call the parent constructor first, as required by the interface
		parent::__construct($info, $config);

		if (!isset($config['entityID'])) {
			$config['entityID'] = $this->getMetadataURL();
		}

		/* For compatibility with code that assumes that $metadata->getString('entityid') gives the entity id. */
		$config['entityid'] = $config['entityID'];

		$this->metadata = SimpleSAML_Configuration::loadFromArray($config, 'authsources[' . var_export($this->authId, TRUE) . ']');
		$this->entityId = $this->metadata->getString('entityID');
		$this->idp = $this->metadata->getString('idp', NULL);
		$this->discoURL = $this->metadata->getString('discoURL', NULL);
		
		if (empty($this->discoURL) && SimpleSAML\Module::isModuleEnabled('discojuice')) {
			$this->discoURL = SimpleSAML\Module::getModuleURL('discojuice/central.php');
		}
	}


	/**
	 * Retrieve the URL to the metadata of this SP.
	 *
	 * @return string  The metadata URL.
	 */
	public function getMetadataURL() {

		return SimpleSAML\Module::getModuleURL('saml/sp/metadata.php/' . urlencode($this->authId));
	}


	/**
	 * Retrieve the entity id of this SP.
	 *
	 * @return string  The entity id of this SP.
	 */
	public function getEntityId() {

		return $this->entityId;
	}


	/**
	 * Retrieve the metadata of this SP.
	 *
	 * @return SimpleSAML_Configuration  The metadata of this SP.
	 */
	public function getMetadata() {

		return $this->metadata;

	}


	/**
	 * Retrieve the metadata of an IdP.
	 *
	 * @param string $entityId  The entity id of the IdP.
	 * @return SimpleSAML_Configuration  The metadata of the IdP.
	 */
	public function getIdPMetadata($entityId) {
		assert('is_string($entityId)');

		if ($this->idp !== NULL && $this->idp !== $entityId) {
			throw new SimpleSAML_Error_Exception('Cannot retrieve metadata for IdP ' . var_export($entityId, TRUE) .
				' because it isn\'t a valid IdP for this SP.');
		}

		$metadataHandler = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

		// First, look in saml20-idp-remote.
		try {
			return $metadataHandler->getMetaDataConfig($entityId, 'saml20-idp-remote');
		} catch (Exception $e) {
			/* Metadata wasn't found. */
            SimpleSAML\Logger::debug('getIdpMetadata: ' . $e->getMessage());
		}

		/* Not found in saml20-idp-remote, look in shib13-idp-remote. */
		try {
			return $metadataHandler->getMetaDataConfig($entityId, 'shib13-idp-remote');
		} catch (Exception $e) {
			/* Metadata wasn't found. */
            SimpleSAML\Logger::debug('getIdpMetadata: ' . $e->getMessage());
		}

		/* Not found. */
		throw new SimpleSAML_Error_Exception('Could not find the metadata of an IdP with entity ID ' . var_export($entityId, TRUE));
	}


	/**
	 * Send a SAML1 SSO request to an IdP.
	 *
	 * @param SimpleSAML_Configuration $idpMetadata  The metadata of the IdP.
	 * @param array $state  The state array for the current authentication.
	 */
	private function startSSO1(SimpleSAML_Configuration $idpMetadata, array $state) {

		$idpEntityId = $idpMetadata->getString('entityid');

		$state['saml:idp'] = $idpEntityId;

		$ar = new \SimpleSAML\XML\Shib13\AuthnRequest();
		$ar->setIssuer($this->entityId);

		$id = SimpleSAML_Auth_State::saveState($state, 'saml:sp:sso');
		$ar->setRelayState($id);

		$useArtifact = $idpMetadata->getBoolean('saml1.useartifact', NULL);
		if ($useArtifact === NULL) {
			$useArtifact = $this->metadata->getBoolean('saml1.useartifact', FALSE);
		}

		if ($useArtifact) {
			$shire = SimpleSAML\Module::getModuleURL('saml/sp/saml1-acs.php/' . $this->authId . '/artifact');
		} else {
			$shire = SimpleSAML\Module::getModuleURL('saml/sp/saml1-acs.php/' . $this->authId);
		}

		$url = $ar->createRedirect($idpEntityId, $shire);

		SimpleSAML\Logger::debug('Starting SAML 1 SSO to ' . var_export($idpEntityId, TRUE) .
			' from ' . var_export($this->entityId, TRUE) . '.');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
	}


	/**
	 * Send a SAML2 SSO request to an IdP.
	 *
	 * @param SimpleSAML_Configuration $idpMetadata  The metadata of the IdP.
	 * @param array $state  The state array for the current authentication.
	 */
	private function startSSO2(SimpleSAML_Configuration $idpMetadata, array $state) {
	
		if (isset($state['saml:ProxyCount']) && $state['saml:ProxyCount'] < 0) {
			SimpleSAML_Auth_State::throwException(
				$state,
				new \SimpleSAML\Module\saml\Error\ProxyCountExceeded(\SAML2\Constants::STATUS_RESPONDER)
			);
		}

		$ar = sspmod_saml_Message::buildAuthnRequest($this->metadata, $idpMetadata);

		$ar->setAssertionConsumerServiceURL(SimpleSAML\Module::getModuleURL('saml/sp/saml2-acs.php/' . $this->authId));

		if (isset($state['SimpleSAML_Auth_Source.ReturnURL'])) {
			$ar->setRelayState($state['SimpleSAML_Auth_Source.ReturnURL']);
		}

		if (isset($state['saml:AuthnContextClassRef'])) {
			$accr = SimpleSAML\Utils\Arrays::arrayize($state['saml:AuthnContextClassRef']);
			$comp = SAML2\Constants::COMPARISON_EXACT;
			if (isset($state['saml:AuthnContextComparison']) && in_array($state['AuthnContextComparison'], array(
						SAML2\Constants::COMPARISON_EXACT,
						SAML2\Constants::COMPARISON_MINIMUM,
						SAML2\Constants::COMPARISON_MAXIMUM,
						SAML2\Constants::COMPARISON_BETTER,
			), true)) {
				$comp = $state['saml:AuthnContextComparison'];
			}
			$ar->setRequestedAuthnContext(array('AuthnContextClassRef' => $accr, 'Comparison' => $comp));
		}

		if (isset($state['ForceAuthn'])) {
			$ar->setForceAuthn((bool)$state['ForceAuthn']);
		}

		if (isset($state['isPassive'])) {
			$ar->setIsPassive((bool)$state['isPassive']);
		}

		if (isset($state['saml:NameID'])) {
			if (!is_array($state['saml:NameID']) && !is_a($state['saml:NameID'], '\SAML2\XML\saml\NameID')) {
				throw new SimpleSAML_Error_Exception('Invalid value of $state[\'saml:NameID\'].');
			}
			$ar->setNameId($state['saml:NameID']);
		}

		if (isset($state['saml:NameIDPolicy'])) {
			if (is_string($state['saml:NameIDPolicy'])) {
				$policy = array(
					'Format' => (string)$state['saml:NameIDPolicy'],
					'AllowCreate' => TRUE,
				);
			} elseif (is_array($state['saml:NameIDPolicy'])) {
				$policy = $state['saml:NameIDPolicy'];
			} else {
				throw new SimpleSAML_Error_Exception('Invalid value of $state[\'saml:NameIDPolicy\'].');
			}
			$ar->setNameIdPolicy($policy);
		}

		if (isset($state['saml:IDPList'])) {
			$IDPList = $state['saml:IDPList'];
		} else {
			$IDPList = array();
		}
		
		$ar->setIDPList(array_unique(array_merge($this->metadata->getArray('IDPList', array()), 
												$idpMetadata->getArray('IDPList', array()),
												(array) $IDPList)));
		
		if (isset($state['saml:ProxyCount']) && $state['saml:ProxyCount'] !== null) {
			$ar->setProxyCount($state['saml:ProxyCount']);
		} elseif ($idpMetadata->getInteger('ProxyCount', null) !== null) {
			$ar->setProxyCount($idpMetadata->getInteger('ProxyCount', null));
		} elseif ($this->metadata->getInteger('ProxyCount', null) !== null) {
			$ar->setProxyCount($this->metadata->getInteger('ProxyCount', null));
		}
		
		$requesterID = array();
		if (isset($state['saml:RequesterID'])) {
			$requesterID = $state['saml:RequesterID'];
		}
		
		if (isset($state['core:SP'])) {
			$requesterID[] = $state['core:SP'];
		}
		
		$ar->setRequesterID($requesterID);
		
		if (isset($state['saml:Extensions'])) {
			$ar->setExtensions($state['saml:Extensions']);
		}

		// save IdP entity ID as part of the state
		$state['ExpectedIssuer'] = $idpMetadata->getString('entityid');

		$id = SimpleSAML_Auth_State::saveState($state, 'saml:sp:sso', TRUE);
		$ar->setId($id);

		SimpleSAML\Logger::debug('Sending SAML 2 AuthnRequest to ' . var_export($idpMetadata->getString('entityid'), TRUE));

		/* Select appropriate SSO endpoint */
		if ($ar->getProtocolBinding() === \SAML2\Constants::BINDING_HOK_SSO) {
			$dst = $idpMetadata->getDefaultEndpoint('SingleSignOnService', array(
				\SAML2\Constants::BINDING_HOK_SSO)
			);
		} else {
			$dst = $idpMetadata->getDefaultEndpoint('SingleSignOnService', array(
				\SAML2\Constants::BINDING_HTTP_REDIRECT,
				\SAML2\Constants::BINDING_HTTP_POST)
			);
		}
		$ar->setDestination($dst['Location']);

		$b = \SAML2\Binding::getBinding($dst['Binding']);

		$this->sendSAML2AuthnRequest($state, $b, $ar);

		assert('FALSE');
	}


	/**
	 * Function to actually send the authentication request.
	 *
	 * This function does not return.
	 *
	 * @param array &$state  The state array.
	 * @param \SAML2\Binding $binding  The binding.
	 * @param \SAML2\AuthnRequest  $ar  The authentication request.
	 */
	public function sendSAML2AuthnRequest(array &$state, \SAML2\Binding $binding, \SAML2\AuthnRequest $ar) {
		$binding->send($ar);
		assert('FALSE');
	}


	/**
	 * Send a SSO request to an IdP.
	 *
	 * @param string $idp  The entity ID of the IdP.
	 * @param array $state  The state array for the current authentication.
	 */
	public function startSSO($idp, array $state) {
		assert('is_string($idp)');

		$idpMetadata = $this->getIdPMetadata($idp);

		$type = $idpMetadata->getString('metadata-set');
		switch ($type) {
		case 'shib13-idp-remote':
			$this->startSSO1($idpMetadata, $state);
			assert('FALSE'); /* Should not return. */
		case 'saml20-idp-remote':
			$this->startSSO2($idpMetadata, $state);
			assert('FALSE'); /* Should not return. */
		default:
			/* Should only be one of the known types. */
			assert('FALSE');
		}
	}


	/**
	 * Start an IdP discovery service operation.
	 *
	 * @param array $state  The state array.
	 */
	private function startDisco(array $state) {

		$id = SimpleSAML_Auth_State::saveState($state, 'saml:sp:sso');

		$config = SimpleSAML_Configuration::getInstance();

		$discoURL = $this->discoURL;
		if ($discoURL === NULL) {
			/* Fallback to internal discovery service. */
			$discoURL = SimpleSAML\Module::getModuleURL('saml/disco.php');
		}

		$returnTo = SimpleSAML\Module::getModuleURL('saml/sp/discoresp.php', array('AuthID' => $id));
		
		$params = array(
			'entityID' => $this->entityId,
			'return' => $returnTo,
			'returnIDParam' => 'idpentityid'
		);
		
		if(isset($state['saml:IDPList'])) {
			$params['IDPList'] = $state['saml:IDPList'];
		}

		if (isset($state['isPassive']) && $state['isPassive']) {
			$params['isPassive'] = 'true';
		}

		\SimpleSAML\Utils\HTTP::redirectTrustedURL($discoURL, $params);
	}


	/**
	 * Start login.
	 *
	 * This function saves the information about the login, and redirects to the IdP.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state['saml:sp:AuthId'] = $this->authId;

		$idp = $this->idp;

		if (isset($state['saml:idp'])) {
			$idp = (string)$state['saml:idp'];
		}

		if (isset($state['saml:IDPList']) && sizeof($state['saml:IDPList']) > 0) {
			// we have a SAML IDPList (we are a proxy): filter the list of IdPs available
			$mdh = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
			$known_idps = $mdh->getList();
			$intersection = array_intersect($state['saml:IDPList'], array_keys($known_idps));

			if (empty($intersection)) { // all requested IdPs are unknown
				throw new SimpleSAML\Module\saml\Error\NoSupportedIDP(
					\SAML2\Constants::STATUS_REQUESTER,
					'None of the IdPs requested are supported by this proxy.'
				);
			}

			if (!is_null($idp) && !in_array($idp, $intersection, true)) { // the IdP is enforced but not in the IDPList
				throw new SimpleSAML\Module\saml\Error\NoAvailableIDP(
					\SAML2\Constants::STATUS_REQUESTER,
					'None of the IdPs requested are available to this proxy.'
				);
			}

			if (is_null($idp) && sizeof($intersection) === 1) { // only one IdP requested or valid
				$idp = current($state['saml:IDPList']);
			}
		}

		if ($idp === NULL) {
			$this->startDisco($state);
			assert('FALSE');
		}

		$this->startSSO($idp, $state);
		assert('FALSE');
	}


	/**
	 * Re-authenticate an user.
	 *
	 * This function is called by the IdP to give the authentication source a chance to
	 * interact with the user even in the case when the user is already authenticated.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function reauthenticate(array &$state) {
		assert('is_array($state)');

		$session = SimpleSAML_Session::getSessionFromRequest();
		$data = $session->getAuthState($this->authId);
		foreach ($data as $k => $v) {
			$state[$k] = $v;
		}

		// check if we have an IDPList specified in the request
		if (isset($state['saml:IDPList']) && sizeof($state['saml:IDPList']) > 0 &&
			!in_array($state['saml:sp:IdP'], $state['saml:IDPList'], true))
		{
			/*
			 * The user has an existing, valid session. However, the SP provided a list of IdPs it accepts for
			 * authentication, and the IdP the existing session is related to is not in that list.
			 *
			 * First, check if we recognize any of the IdPs requested.
			 */

			$mdh = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
			$known_idps = $mdh->getList();
			$intersection = array_intersect($state['saml:IDPList'], array_keys($known_idps));

			if (empty($intersection)) { // all requested IdPs are unknown
				throw new SimpleSAML\Module\saml\Error\NoSupportedIDP(
					\SAML2\Constants::STATUS_REQUESTER,
					'None of the IdPs requested are supported by this proxy.'
				);
			}

			/*
			 * We have at least one IdP in the IDPList that we recognize, and it's not the one currently in use. Let's
			 * see if this proxy enforces the use of one single IdP.
			 */
			if (!is_null($this->idp) && !in_array($this->idp, $intersection, true)) { // an IdP is enforced but not requested
				throw new SimpleSAML\Module\saml\Error\NoAvailableIDP(
					\SAML2\Constants::STATUS_REQUESTER,
					'None of the IdPs requested are available to this proxy.'
				);
			}

			/*
			 * We need to inform the user, and ask whether we should logout before starting the authentication process
			 * again with a different IdP, or cancel the current SSO attempt.
			 */
			SimpleSAML\Logger::warning(
				"Reauthentication after logout is needed. The IdP '${state['saml:sp:IdP']}' is not in the IDPList ".
				"provided by the Service Provider '${state['core:SP']}'."
			);

			$state['saml:sp:IdPMetadata'] = $this->getIdPMetadata($state['saml:sp:IdP']);
			$state['saml:sp:AuthId'] = $this->authId;
			self::askForIdPChange($state);
		}
	}


	/**
	 * Ask the user to log out before being able to log in again with a different identity provider. Note that this
	 * method is intended for instances of SimpleSAMLphp running as a SAML proxy, and therefore acting both as an SP
	 * and an IdP at the same time.
	 *
	 * This method will never return.
	 *
	 * @param array $state The state array. The following keys must be defined in the array:
	 * - 'saml:sp:IdPMetadata': a SimpleSAML_Configuration object containing the metadata of the IdP that authenticated
	 *   the user in the current session.
	 * - 'saml:sp:AuthId': the identifier of the current authentication source.
	 * - 'core:IdP': the identifier of the local IdP.
	 * - 'SPMetadata': an array with the metadata of this local SP.
	 *
	 * @throws SimpleSAML_Error_NoPassive In case the authentication request was passive.
	 */
	public static function askForIdPChange(array &$state)
	{
		assert('array_key_exists("saml:sp:IdPMetadata", $state)');
		assert('array_key_exists("saml:sp:AuthId", $state)');
		assert('array_key_exists("core:IdP", $state)');
		assert('array_key_exists("SPMetadata", $state)');

		if (isset($state['isPassive']) && (bool)$state['isPassive']) {
			// passive request, we cannot authenticate the user
			throw new SimpleSAML_Error_NoPassive('Reauthentication required');
		}

		// save the state WITHOUT a restart URL, so that we don't try an IdP-initiated login if something goes wrong
		$id = SimpleSAML_Auth_State::saveState($state, 'saml:proxy:invalid_idp', true);
		$url = SimpleSAML\Module::getModuleURL('saml/proxy/invalid_session.php');
		SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('AuthState' => $id));
		assert('false');
	}


	/**
	 * Log the user out before logging in again.
	 *
	 * This method will never return.
	 *
	 * @param array $state The state array.
	 */
	public static function reauthLogout(array $state)
	{
		SimpleSAML\Logger::debug('Proxy: logging the user out before re-authentication.');

		if (isset($state['Responder'])) {
			$state['saml:proxy:reauthLogout:PrevResponder'] = $state['Responder'];
		}
		$state['Responder'] = array('sspmod_saml_Auth_Source_SP', 'reauthPostLogout');

		$idp = SimpleSAML_IdP::getByState($state);
		$idp->handleLogoutRequest($state, null);
		assert('false');
	}


	/**
	 * Complete login operation after re-authenticating the user on another IdP.
	 *
	 * @param array $state  The authentication state.
	 */
	public static function reauthPostLogin(array $state) {
		assert('isset($state["ReturnCallback"])');

		// Update session state
		$session = SimpleSAML_Session::getSessionFromRequest();
		$authId = $state['saml:sp:AuthId'];
		$session->doLogin($authId, SimpleSAML_Auth_State::getPersistentAuthData($state));

		// resume the login process
		call_user_func($state['ReturnCallback'], $state);
		assert('FALSE');
	}


	/**
	 * Post-logout handler for re-authentication.
	 *
	 * This method will never return.
	 *
	 * @param SimpleSAML_IdP $idp The IdP we are logging out from.
	 * @param array &$state The state array with the state during logout.
	 */
	public static function reauthPostLogout(SimpleSAML_IdP $idp, array $state) {
		assert('isset($state["saml:sp:AuthId"])');

		SimpleSAML\Logger::debug('Proxy: logout completed.');

		if (isset($state['saml:proxy:reauthLogout:PrevResponder'])) {
			$state['Responder'] = $state['saml:proxy:reauthLogout:PrevResponder'];
		}

		$sp = SimpleSAML_Auth_Source::getById($state['saml:sp:AuthId'], 'sspmod_saml_Auth_Source_SP');
		/** @var sspmod_saml_Auth_Source_SP $authSource */
		SimpleSAML\Logger::debug('Proxy: logging in again.');
		$sp->authenticate($state);
		assert('false');
	}


	/**
	 * Start a SAML 2 logout operation.
	 *
	 * @param array $state  The logout state.
	 */
	public function startSLO2(&$state) {
		assert('is_array($state)');
		assert('array_key_exists("saml:logout:IdP", $state)');
		assert('array_key_exists("saml:logout:NameID", $state)');
		assert('array_key_exists("saml:logout:SessionIndex", $state)');

		$id = SimpleSAML_Auth_State::saveState($state, 'saml:slosent');

		$idp = $state['saml:logout:IdP'];
		$nameId = $state['saml:logout:NameID'];
		$sessionIndex = $state['saml:logout:SessionIndex'];

		$idpMetadata = $this->getIdPMetadata($idp);

		$endpoint = $idpMetadata->getEndpointPrioritizedByBinding('SingleLogoutService', array(
			\SAML2\Constants::BINDING_HTTP_REDIRECT,
			\SAML2\Constants::BINDING_HTTP_POST), FALSE);
		if ($endpoint === FALSE) {
			SimpleSAML\Logger::info('No logout endpoint for IdP ' . var_export($idp, TRUE) . '.');
			return;
		}

		$lr = sspmod_saml_Message::buildLogoutRequest($this->metadata, $idpMetadata);
		$lr->setNameId($nameId);
		$lr->setSessionIndex($sessionIndex);
		$lr->setRelayState($id);
		$lr->setDestination($endpoint['Location']);

		$encryptNameId = $idpMetadata->getBoolean('nameid.encryption', NULL);
		if ($encryptNameId === NULL) {
			$encryptNameId = $this->metadata->getBoolean('nameid.encryption', FALSE);
		}
		if ($encryptNameId) {
			$lr->encryptNameId(sspmod_saml_Message::getEncryptionKey($idpMetadata));
		}

		$b = \SAML2\Binding::getBinding($endpoint['Binding']);
		$b->send($lr);

		assert('FALSE');
	}


	/**
	 * Start logout operation.
	 *
	 * @param array $state  The logout state.
	 */
	public function logout(&$state) {
		assert('is_array($state)');
		assert('array_key_exists("saml:logout:Type", $state)');

		$logoutType = $state['saml:logout:Type'];
		switch ($logoutType) {
		case 'saml1':
			/* Nothing to do. */
			return;
		case 'saml2':
			$this->startSLO2($state);
			return;
		default:
			/* Should never happen. */
			assert('FALSE');
		}
	}


	/**
	 * Handle a response from a SSO operation.
	 *
	 * @param array $state  The authentication state.
	 * @param string $idp  The entity id of the IdP.
	 * @param array $attributes  The attributes.
	 */
	public function handleResponse(array $state, $idp, array $attributes) {
		assert('is_string($idp)');
		assert('array_key_exists("LogoutState", $state)');
		assert('array_key_exists("saml:logout:Type", $state["LogoutState"])');
		
		$idpMetadata = $this->getIdpMetadata($idp);

		$spMetadataArray = $this->metadata->toArray();
		$idpMetadataArray = $idpMetadata->toArray();

		/* Save the IdP in the state array. */
		$state['saml:sp:IdP'] = $idp;
		$state['PersistentAuthData'][] = 'saml:sp:IdP';

		$authProcState = array(
			'saml:sp:IdP' => $idp,
			'saml:sp:State' => $state,
			'ReturnCall' => array('sspmod_saml_Auth_Source_SP', 'onProcessingCompleted'),

			'Attributes' => $attributes,
			'Destination' => $spMetadataArray,
			'Source' => $idpMetadataArray,
		);

		if (isset($state['saml:sp:NameID'])) {
			$authProcState['saml:sp:NameID'] = $state['saml:sp:NameID'];
		}
		if (isset($state['saml:sp:SessionIndex'])) {
			$authProcState['saml:sp:SessionIndex'] = $state['saml:sp:SessionIndex'];
		}

		$pc = new SimpleSAML_Auth_ProcessingChain($idpMetadataArray, $spMetadataArray, 'sp');
		$pc->processState($authProcState);

		self::onProcessingCompleted($authProcState);
	}


	/**
	 * Handle a logout request from an IdP.
	 *
	 * @param string $idpEntityId  The entity ID of the IdP.
	 */
	public function handleLogout($idpEntityId) {
		assert('is_string($idpEntityId)');

		/* Call the logout callback we registered in onProcessingCompleted(). */
		$this->callLogoutCallback($idpEntityId);
	}


	/**
	 * Handle an unsolicited login operations.
	 *
	 * This method creates a session from the information received. It will then redirect to the given URL. This is used
	 * to handle IdP initiated SSO. This method will never return.
	 *
	 * @param string $authId The id of the authentication source that received the request.
	 * @param array $state A state array.
	 * @param string $redirectTo The URL we should redirect the user to after updating the session. The function will
	 * check if the URL is allowed, so there is no need to manually check the URL on beforehand. Please refer to the
	 * 'trusted.url.domains' configuration directive for more information about allowing (or disallowing) URLs.
	 */
	public static function handleUnsolicitedAuth($authId, array $state, $redirectTo) {
		assert('is_string($authId)');
		assert('is_string($redirectTo)');

		$session = SimpleSAML_Session::getSessionFromRequest();
		$session->doLogin($authId, SimpleSAML_Auth_State::getPersistentAuthData($state));

		\SimpleSAML\Utils\HTTP::redirectUntrustedURL($redirectTo);
	}


	/**
	 * Called when we have completed the procssing chain.
	 *
	 * @param array $authProcState  The processing chain state.
	 */
	public static function onProcessingCompleted(array $authProcState) {
		assert('array_key_exists("saml:sp:IdP", $authProcState)');
		assert('array_key_exists("saml:sp:State", $authProcState)');
		assert('array_key_exists("Attributes", $authProcState)');

		$idp = $authProcState['saml:sp:IdP'];
		$state = $authProcState['saml:sp:State'];

		$sourceId = $state['saml:sp:AuthId'];
		$source = SimpleSAML_Auth_Source::getById($sourceId);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $sourceId);
		}

		/* Register a callback that we can call if we receive a logout request from the IdP. */
		$source->addLogoutCallback($idp, $state);

		$state['Attributes'] = $authProcState['Attributes'];

		if (isset($state['saml:sp:isUnsolicited']) && (bool)$state['saml:sp:isUnsolicited']) {
			if (!empty($state['saml:sp:RelayState'])) {
				$redirectTo = $state['saml:sp:RelayState'];
			} else {
				$redirectTo = $source->getMetadata()->getString('RelayState', '/');
			}
			self::handleUnsolicitedAuth($sourceId, $state, $redirectTo);
		}

		SimpleSAML_Auth_Source::completeAuth($state);
	}

}
