<?php

/**
 * IdP class.
 *
 * This class implements the various functions used by IdP.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_IdP {

	/**
	 * A cache for resolving IdP id's.
	 *
	 * @var array
	 */
	private static $idpCache = array();


	/**
	 * The identifier for this IdP.
	 *
	 * @var string
	 */
	private $id;


	/**
	 * The configuration for this IdP.
	 *
	 * @var SimpleSAML_Configuration
	 */
	private $config;


	/**
	 * Initialize an IdP.
	 *
	 * @param string $id  The identifier of this IdP.
	 */
	private function __construct($id) {
		assert('is_string($id)');

		$this->id = $id;

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		if (substr($id, 0, 6) === 'saml2:') {
			$this->config = $metadata->getMetaDataConfig(substr($id, 6), 'saml20-idp-hosted');
		} elseif (substr($id, 0, 6) === 'saml1:') {
			$this->config = $metadata->getMetaDataConfig(substr($id, 6), 'shib13-idp-hosted');
		} else {
			assert(FALSE);
		}

	}


	/**
	 * Retrieve an IdP by ID.
	 *
	 * @param string $id  The identifier of the IdP.
	 * @return SimpleSAML_IdP  The IdP.
	 */
	public static function getById($id) {
		assert('is_string($id)');

		if (isset(self::$idpCache[$id])) {
			return self::$idpCache[$id];
		}

		$idp = new self($id);
		self::$idpCache[$id] = $idp;
		return $idp;
	}


	/**
	 * Retrieve the IdP "owning" the state.
	 *
	 * @param array &$state  The state array.
	 * @return SimpleSAML_IdP  The IdP.
	 */
	public static function getByState(array &$state) {
		assert('isset($state["core:IdP"])');

		return self::getById($state['core:IdP']);
	}


	/**
	 * Retrieve the configuration for this IdP.
	 *
	 * @return SimpleSAML_Configuration  The configuration object.
	 */
	public function getConfig() {

		return $this->config;
	}


	/**
	 * Is the current user authenticated?
	 *
	 * @return bool  TRUE if the user is authenticated, FALSE if not.
	 */
	public function isAuthenticated() {

		$session = SimpleSAML_Session::getInstance();

		$authority = $this->config->getString('auth');
		if ($session->isValid($authority)) {
			return TRUE;
		}

		/* Maybe the 'auth' option didn't point to an authentication source? */
		if (SimpleSAML_Auth_Source::getById($authority) !== NULL) {
			/* It was an authentication source - the user is therefore not authenticated. */
			return FALSE;
		}

		/* It wasn't an authentication source. */
		$authority = SimpleSAML_Utilities::getAuthority($this->config->toArray());
		return $session->isValid($authority);
	}


	/**
	 * Called after authproc has run.
	 *
	 * @param array &$state  The authentication request state array.
	 */
	public static function postAuthProc(array &$state) {
		assert('is_callable($state["Responder"])');

		if (isset($state['core:SP'])) {
			$session = SimpleSAML_Session::getInstance();
			$session->setData('core:idp-ssotime', $state['core:IdP'] . ';' . $state['core:SP'],
				time(), SimpleSAML_Session::DATA_TIMEOUT_LOGOUT);
		}

		call_user_func($state['Responder'], $state);
		assert('FALSE');
	}


	/**
	 * The user is authenticated.
	 *
	 * @param array &$state  The authentication request state arrray.
	 */
	public static function postAuth(array &$state) {

		$idp = SimpleSAML_IdP::getByState($state);

		if (!$idp->isAuthenticated()) {
			throw new SimpleSAML_Error_Exception('Not authenticated.');
		}

		$session = SimpleSAML_Session::getInstance();
		$state['Attributes'] = $session->getAttributes();

		if (isset($state['SPMetadata'])) {
			$spMetadata = $state['SPMetadata'];
		} else {
			$spMetadata = array();
		}

		if (isset($state['core:SP'])) {
			$previousSSOTime = $session->getData('core:idp-ssotime', $state['core:IdP'] . ';' . $state['core:SP']);
			if ($previousSSOTime !== NULL) {
				$state['PreviousSSOTimestamp'] = $previousSSOTime;
			}
		}

		$idpMetadata = $idp->getConfig()->toArray();

		$pc = new SimpleSAML_Auth_ProcessingChain($idpMetadata, $spMetadata, 'idp');

		$state['ReturnCall'] = array('SimpleSAML_IdP', 'postAuthProc');
		$state['Destination'] = $spMetadata;
		$state['Source'] = $idpMetadata;

		$pc->processState($state);

		self::postAuthProc($state);
	}


	/**
	 * Authenticate the user.
	 *
	 * This function authenticates the user.
	 *
	 * @param array &$state  The authentication request state.
	 */
	private function authenticate(array &$state) {

		if (isset($state['isPassive']) && (bool)$state['isPassive']) {
			throw new SimpleSAML_Error_NoPassive('Passive authentication not supported.');
		}

		$auth = $this->config->getString('auth');
		$authSource = SimpleSAML_Auth_Source::getById($auth);
		if ($authSource === NULL) {
			$config = SimpleSAML_Configuration::getInstance();
			$authurl = '/' . $config->getBaseURL() . $auth;

			$authnRequest = array(
				'IsPassive' => isset($state['isPassive']) ? $state['isPassive'] : FALSE,
				'ForceAuthn' => isset($state['ForceAuthn']) ? $state['ForceAuthn'] : FALSE,
				'State' => $state,
			);

			$authId = SimpleSAML_Utilities::generateID();
			$session = SimpleSAML_Session::getInstance();
			$session->setAuthnRequest('saml2', $authId, $authnRequest);

			$relayState = SimpleSAML_Module::getModuleURL('core/idp/resumeauth.php', array('RequestID' => $authId));

			SimpleSAML_Utilities::redirect($authurl, array(
				'RelayState' => $relayState,
				'AuthId' => $authId,
				'protocol' => 'saml2',
			));
		}

		$state['IdPMetadata'] = $this->getConfig()->toArray();
		SimpleSAML_Auth_Default::initLogin($auth, array('SimpleSAML_IdP', 'postAuth'), NULL, $state);
	}


	/**
	 * Process authentication requests.
	 *
	 * @param array &$state  The authentication request state.
	 */
	public function handleAuthenticationRequest(array &$state) {
		assert('isset($state["Responder"])');

		$state['core:IdP'] = $this->id;

		if (isset($state['SPMetadata']['entityid'])) {
			$spEntityId = $state['SPMetadata']['entityid'];
		} elseif (isset($state['SPMetadata']['entityID'])) {
			$spEntityId = $state['SPMetadata']['entityID'];
		} else {
			$spEntityId = NULL;
		}
		$state['core:SP'] = $spEntityId;

		/* First, check whether we need to authenticate the user. */
		if (isset($state['ForceAuthn']) && (bool)$state['ForceAuthn']) {
			/* Force authentication is in effect. */
			$needAuth = TRUE;
		} else {
			$needAuth = !$this->isAuthenticated();
		}

		try {
			if ($needAuth) {
				$this->authenticate($state);
				assert('FALSE');
			}
			$this->postAuth($state);
		} catch (SimpleSAML_Error_Exception $e) {
			SimpleSAML_Auth_State::throwException($state, $e);
		} catch (Exception $e) {
			$e = new SimpleSAML_Error_UnserializableException($e);
			SimpleSAML_Auth_State::throwException($state, $e);
		}
	}

}
