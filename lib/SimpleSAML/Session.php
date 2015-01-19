<?php

/**
 * The Session class holds information about a user session, and everything attached to it.
 *
 * The session will have a duration and validity, and also cache information about the different
 * federation protocols, as Shibboleth and SAML 2.0. On the IdP side the Session class holds 
 * information about all the currently logged in SPs. This is used when the user initiates a
 * Single-Log-Out.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 */
class SimpleSAML_Session {

	/**
	 * This is a timeout value for setData, which indicates that the data should be deleted
	 * on logout.
	 * @deprecated
	 */
	const DATA_TIMEOUT_LOGOUT = 'logoutTimeout';


	/**
	 * This is a timeout value for setData, which indicates that the data
	 * should never be deleted, i.e. lasts the whole session lifetime.
	 */
	const DATA_TIMEOUT_SESSION_END = 'sessionEndTimeout';


	/**
	 * The list of loaded session objects.
	 *
	 * This is an associative array indexed with the session id.
	 *
	 * @var array
	 */
	private static $sessions = array();


	/**
	 * This variable holds the instance of the session - Singleton approach.
	 */
	private static $instance = null;
	

	/**
	 * The session ID of this session.
	 *
	 * @var string|NULL
	 */
	private $sessionId;


	/**
	 * Transient session flag.
	 *
	 * @var boolean|FALSE
	 */
	private $transient = FALSE;


	/**
	 * The track id is a new random unique identifier that is generated for each session.
	 * This is used in the debug logs and error messages to easily track more information
	 * about what went wrong.
	 *
	 * @var int
	 */
	private $trackid = 0;


	private $rememberMeExpire = null;


	/**
	 * Marks a session as modified, and therefore needs to be saved before destroying
	 * this object.
	 *
	 * @var bool
	 */
    private $dirty = false;


	/**
	 * This is an array of objects which will expire automatically after a set time. It is used
	 * where one needs to store some information - for example a logout request, but doesn't
	 * want it to be stored forever.
	 *
	 * The data store contains three levels of nested associative arrays. The first is the data type, the
	 * second is the identifier, and the third contains the expire time of the data and the data itself.
     *
     * @var array
	 */
	private $dataStore = null;


	/**
	 * The list of IdP-SP associations.
	 *
	 * This is an associative array with the IdP id as the key, and the list of
	 * associations as the value.
	 *
	 * @var array
	 */
	private $associations = array();


	/**
	 * The authentication token.
	 *
	 * This token is used to prevent session fixation attacks.
	 *
	 * @var string|NULL
	 */
	private $authToken;


	/**
	 * Authentication data.
	 *
	 * This is an array with authentication data for the various authsources.
	 *
	 * @var array|NULL  Associative array of associative arrays.
	 */
	private $authData;


	/**
	 * Private constructor that restricts instantiation to getInstance().
	 *
	 * @param boolean $transient Whether to create a transient session or not.
	 */
	private function __construct($transient = FALSE) {

		$this->authData = array();

		if ($transient) {
			$this->trackid = 'XXXXXXXXXX';
			$this->transient = TRUE;
			return;
		}

		$sh = SimpleSAML_SessionHandler::getSessionHandler();
		$this->sessionId = $sh->newSessionId();

		$this->trackid = SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(5));

		$this->dirty = TRUE;

		/* Initialize data for session check function if defined */
		$globalConfig = SimpleSAML_Configuration::getInstance();
		$checkFunction = $globalConfig->getArray('session.check_function', NULL);
		if (isset($checkFunction)) {
			assert('is_callable($checkFunction)');
			call_user_func($checkFunction, $this, TRUE);
		}
	}


	/**
	 * Destructor for this class. It will save the session to the session handler
	 * in case the session has been marked as dirty. Do nothing otherwise.
	 */
    public function __destruct() {
        if(!$this->dirty) {
            /* Session hasn't changed - don't bother saving it. */
            return;
        }

        $this->dirty = FALSE;

        $sh = SimpleSAML_SessionHandler::getSessionHandler();

        try {
            $sh->saveSession($this);
        } catch (Exception $e) {
            if (!($e instanceof SimpleSAML_Error_Exception)) {
                $e = new SimpleSAML_Error_UnserializableException($e);
            }
            SimpleSAML_Logger::error('Unable to save session.');
            $e->logError();
        }
    }


    /**
     * @deprecated
     * @see SimpleSAML_Session::getSessionFromRequest()
     */
    public static function getInstance() {
        return self::getSessionFromRequest();
    }


    /**
	 * Retrieves the current session. Will create a new session if there isn't a session.
	 *
	 * @return SimpleSAML_Session The current session.
	 * @throws Exception When session couldn't be initialized and
	 * the session fallback is disabled by configuration.
	 */
	public static function getSessionFromRequest() {

		/* Check if we already have initialized the session. */
		if (isset(self::$instance)) {
			return self::$instance;
		}


		/* Check if we have stored a session stored with the session
		 * handler.
		 */
		try {
			self::$instance = self::getSession();
		} catch (Exception $e) {
			/* For some reason, we were unable to initialize this session. Use a transient session instead. */
			self::useTransientSession();

			$globalConfig = SimpleSAML_Configuration::getInstance();
			if ($globalConfig->getBoolean('session.disable_fallback', FALSE) === TRUE) {
				throw $e;
			}

			if ($e instanceof SimpleSAML_Error_Exception) {
				SimpleSAML_Logger::error('Error loading session:');
				$e->logError();
			} else {
				SimpleSAML_Logger::error('Error loading session: ' . $e->getMessage());
			}

			return self::$instance;
		}

		if(self::$instance !== NULL) {
			return self::$instance;
		}


		/* Create a new session. */
		self::$instance = new SimpleSAML_Session();
		return self::$instance;
	}


	/**
	 * Use a transient session.
	 *
	 * Create a session that should not be saved at the end of the request.
	 * Subsequent calls to getInstance() will return this transient session.
	 */
	public static function useTransientSession() {

		if (isset(self::$instance)) {
			/* We already have a session. Don't bother with a transient session. */
			return;
		}

		self::$instance = new SimpleSAML_Session(TRUE);
	}


	/**
	 * Retrieve the session ID of this session.
	 *
	 * @return string|NULL  The session ID, or NULL if this is a transient session.
	 */
	public function getSessionId() {

		return $this->sessionId;
	}


	/**
	 * Retrieve if session is transient.
	 *
	 * @return boolean The session transient flag.
	 */
	public function isTransient() {
		return $this->transient;
	}


	/**
	 * Get a unique ID that will be permanent for this session.
	 * Used for debugging and tracing log files related to a session.
	 *
	 * @return string The unique ID.
	 */
	public function getTrackID() {
		return $this->trackid;
	}


	/**
	 * Set remember me expire time.
	 *
	 * @param int $expire Unix timestamp when remember me session cookies expire.
	 */
	public function setRememberMeExpire($expire = NULL) {
		assert('is_int($expire) || is_null($expire)');

		if ($expire === NULL) {
			$globalConfig = SimpleSAML_Configuration::getInstance();
			$expire = time() + $globalConfig->getInteger('session.rememberme.lifetime', 14*86400);
		}
		$this->rememberMeExpire = $expire;

		$cookieParams = array('expire' => $this->rememberMeExpire);
		$this->updateSessionCookies($cookieParams);
	}


	/**
	 * Get remember me expire time.
	 *
	 * @return integer|NULL The remember me expire time.
	 */
	public function getRememberMeExpire() {
		return $this->rememberMeExpire;
	}


	/**
	 * Update session cookies.
	 */
	public function updateSessionCookies($params = NULL) {
		$sessionHandler = SimpleSAML_SessionHandler::getSessionHandler();

		if ($this->sessionId !== NULL) {
			$sessionHandler->setCookie($sessionHandler->getSessionCookieName(), $this->sessionId, $params);
		}

		if ($this->authToken !== NULL) {
			$globalConfig = SimpleSAML_Configuration::getInstance();
			$sessionHandler->setCookie($globalConfig->getString('session.authtoken.cookiename',
                                                                'SimpleSAMLAuthToken'), $this->authToken, $params);
		}
	}


	/**
	 * Marks the user as logged in with the specified authority.
	 *
	 * If the user already has logged in, the user will be logged out first.
	 *
	 * @param string $authority The authority the user logged in with.
	 * @param array|NULL $data The authentication data for this authority.
	 */
	public function doLogin($authority, array $data = NULL) {
		assert('is_string($authority)');
		assert('is_array($data) || is_null($data)');

		SimpleSAML_Logger::debug('Session: doLogin("' . $authority . '")');

		$this->dirty = TRUE;

		if (isset($this->authData[$authority])) {
			/* We are already logged in. Log the user out first. */
			$this->doLogout($authority);
		}


		if ($data === NULL) {
			$data = array();
		}

		$data['Authority'] = $authority;

		$globalConfig = SimpleSAML_Configuration::getInstance();
		if (!isset($data['AuthnInstant'])) {
			$data['AuthnInstant'] = time();
		}

		$maxSessionExpire = time() + $globalConfig->getInteger('session.duration', 8*60*60);
		if (!isset($data['Expire']) || $data['Expire'] > $maxSessionExpire) {
			/* Unset, or beyond our session lifetime. Clamp it to our maximum session lifetime. */
			$data['Expire'] = $maxSessionExpire;
		}

		$this->authData[$authority] = $data;

		$this->authToken = SimpleSAML_Utilities::generateID();
		$sessionHandler = SimpleSAML_SessionHandler::getSessionHandler();

		if (!$this->transient && (!empty($data['RememberMe']) || $this->rememberMeExpire) &&
            $globalConfig->getBoolean('session.rememberme.enable', FALSE)) {

            $this->setRememberMeExpire();
		} else {
			$sessionHandler->setCookie($globalConfig->getString('session.authtoken.cookiename',
                                                                'SimpleSAMLAuthToken'), $this->authToken);
		}
	}


	/**
	 * Marks the user as logged out.
	 *
	 * This function will call any registered logout handlers before marking the user as logged out.
	 *
	 * @param string $authority The authentication source we are logging out of.
	 */
	public function doLogout($authority) {

		SimpleSAML_Logger::debug('Session: doLogout(' . var_export($authority, TRUE) . ')');

		if (!isset($this->authData[$authority])) {
			SimpleSAML_Logger::debug('Session: Already logged out of ' . $authority . '.');
			return;
		}

		$this->dirty = TRUE;

		$this->callLogoutHandlers($authority);
		unset($this->authData[$authority]);

		if (!$this->isValid($authority) && $this->rememberMeExpire) {
			$this->rememberMeExpire = NULL;
			$this->updateSessionCookies();
		}

		/* Delete data which expires on logout. */
		$this->expireDataLogout();
	}


	/**
	 * Set the lifetime for authentication source.
	 *
	 * @param string $authority The authentication source we are setting expire time for.
	 * @param int $expire The number of seconds authentication source is valid.
	 */
	public function setAuthorityExpire($authority, $expire = NULL) {
		assert('isset($this->authData[$authority])');
		assert('is_int($expire) || is_null($expire)');

		$this->dirty = true;

		if ($expire === NULL) {
			$globalConfig = SimpleSAML_Configuration::getInstance();
			$expire = time() + $globalConfig->getInteger('session.duration', 8*60*60);
		}

		$this->authData[$authority]['Expire'] = $expire;
	}


	/**
	 * Is the session representing an authenticated user, and is the session still alive.
	 * This function will return false after the user has timed out.
	 *
	 * @param string $authority  The authentication source that the user should be authenticated with.
	 * @return TRUE if the user has a valid session, FALSE if not.
	 */
	public function isValid($authority) {
		assert('is_string($authority)');

		if (!isset($this->authData[$authority])) {
			SimpleSAML_Logger::debug('Session: '. var_export($authority, TRUE) .
                                     ' not valid because we are not authenticated.');
			return FALSE;
		}

		if ($this->authData[$authority]['Expire'] <= time()) {
			SimpleSAML_Logger::debug('Session: ' . var_export($authority, TRUE) .' not valid because it is expired.');
			return FALSE;
		}

		SimpleSAML_Logger::debug('Session: Valid session found with ' . var_export($authority, TRUE) . '.');

		return TRUE;
	}


	/**
	 * Calculates the size of the session object after serialization
	 *
	 * @return int The size of the session measured in bytes.
	 * @deprecated
	 */
	public function getSize() {
		$s = serialize($this);
		return strlen($s);
	}


	/**
	 * This function registers a logout handler.
	 *
	 * @param string $authority The authority for which register the handler.
	 * @param string $classname The class which contains the logout handler.
	 * @param string $functionname The logout handler function.
	 * @throws Exception If the handler is not a valid function or method.
	 */
	public function registerLogoutHandler($authority, $classname, $functionname) {
		assert('isset($this->authData[$authority])');

		$logout_handler = array($classname, $functionname);

		if(!is_callable($logout_handler)) {
			throw new Exception('Logout handler is not a vaild function: ' . $classname . '::' .
				$functionname);
		}


		$this->authData[$authority]['LogoutHandlers'][] = $logout_handler;
		$this->dirty = TRUE;
	}


	/**
	 * This function calls all registered logout handlers.
	 *
	 * @param string $authority The authentication source we are logging out from.
	 * @throws Exception If the handler is not a valid function or method.
	 */
	private function callLogoutHandlers($authority) {
		assert('is_string($authority)');
		assert('isset($this->authData[$authority])');

		if (empty($this->authData[$authority]['LogoutHandlers'])) {
			return;
		}
		foreach($this->authData[$authority]['LogoutHandlers'] as $handler) {

			/* Verify that the logout handler is a valid function. */
			if(!is_callable($handler)) {
				$classname = $handler[0];
				$functionname = $handler[1];

				throw new Exception('Logout handler is not a vaild function: ' . $classname . '::' .
					$functionname);
			}

			/* Call the logout handler. */
			call_user_func($handler);

		}

		/* We require the logout handlers to register themselves again if they want to be called later. */
		unset($this->authData[$authority]['LogoutHandlers']);
	}


	/**
	 * This function removes expired data from the data store.
	 *
	 * Note that this function doesn't mark the session object as dirty. This means that
	 * if the only change to the session object is that some data has expired, it will not be
	 * written back to the session store.
	 */
	private function expireData() {

		if(!is_array($this->dataStore)) {
			return;
		}

		$ct = time();

		foreach($this->dataStore as &$typedData) {
			foreach($typedData as $id => $info) {
				if ($info['expires'] === self::DATA_TIMEOUT_LOGOUT) {
					/* This data only expires on logout. */
					continue;
				}

				if ($info['expires'] === self::DATA_TIMEOUT_SESSION_END) {
					/* This data never expires. */
					continue;
				}

				if($ct > $info['expires']) {
					unset($typedData[$id]);
				}
			}
		}
	}


	/**
	 * This function deletes data which should be deleted on logout from the data store.
	 * @deprecated
	 */
	private function expireDataLogout() {

		if(!is_array($this->dataStore)) {
			return;
		}

		$this->dirty = TRUE;

		foreach ($this->dataStore as &$typedData) {
			foreach ($typedData as $id => $info) {
				if ($info['expires'] === self::DATA_TIMEOUT_LOGOUT) {
					unset($typedData[$id]);
				}
			}
		}
	}


	/**
	 * Delete data from the data store.
	 *
	 * This function immediately deletes the data with the given type and id from the data store.
	 *
	 * @param string $type  The type of the data.
	 * @param string $id  The identifier of the data.
	 */
	public function deleteData($type, $id) {
		assert('is_string($type)');
		assert('is_string($id)');

		if (!is_array($this->dataStore)) {
			return;
		}

		if(!array_key_exists($type, $this->dataStore)) {
			return;
		}

		unset($this->dataStore[$type][$id]);
		$this->dirty = TRUE;
	}


	/**
	 * This function stores data in the data store.
	 *
	 * The timeout value can be SimpleSAML_Session::DATA_TIMEOUT_LOGOUT, which indicates
	 * that the data should be deleted on logout (and not before).
	 *
	 * @param string $type The type of the data. This is checked when retrieving data from the store.
	 * @param string $id The identifier of the data.
	 * @param mixed $data The data.
	 * @param int|NULL $timeout The number of seconds this data should be stored after its last access.
	 * This parameter is optional. The default value is set in 'session.datastore.timeout',
	 * and the default is 4 hours.
     * @throws Exception If the data couldn't be stored.
     *
	 */
	public function setData($type, $id, $data, $timeout = NULL) {
		assert('is_string($type)');
		assert('is_string($id)');
		assert('is_int($timeout) || is_null($timeout) || $timeout === self::DATA_TIMEOUT_LOGOUT ||'.
               ' $timeout === self::DATA_TIMEOUT_SESSION_END');

		/* Clean out old data. */
		$this->expireData();

		if($timeout === NULL) {
			/* Use the default timeout. */

			$configuration = SimpleSAML_Configuration::getInstance();

			$timeout = $configuration->getInteger('session.datastore.timeout', NULL);
			if($timeout !== NULL) {
				if ($timeout <= 0) {
					throw new Exception('The value of the session.datastore.timeout' .
						' configuration option should be a positive integer.');
				}
			} else {
				/* For backwards compatibility. */
				$timeout = $configuration->getInteger('session.requestcache', 4*(60*60));
				if ($timeout <= 0) {
					throw new Exception('The value of the session.requestcache' .
						' configuration option should be a positive integer.');
				}
			}
		}

		if ($timeout === self::DATA_TIMEOUT_LOGOUT) {
			$expires = self::DATA_TIMEOUT_LOGOUT;
		} elseif ($timeout === self::DATA_TIMEOUT_SESSION_END) {
			$expires = self::DATA_TIMEOUT_SESSION_END;
		} else {
			$expires = time() + $timeout;
		}

		$dataInfo = array(
			'expires' => $expires,
			'timeout' => $timeout,
			'data' => $data
			);

		if(!is_array($this->dataStore)) {
			$this->dataStore = array();
		}

		if(!array_key_exists($type, $this->dataStore)) {
			$this->dataStore[$type] = array();
		}

		$this->dataStore[$type][$id] = $dataInfo;

		$this->dirty = TRUE;
	}


	/**
	 * This function retrieves data from the data store.
	 *
	 * Note that this will not change when the data stored in the data store will expire. If that is required,
	 * the data should be written back with setData.
	 *
	 * @param string $type The type of the data. This must match the type used when adding the data.
	 * @param string|NULL $id The identifier of the data. Can be NULL, in which case NULL will be returned.
	 * @return mixed The data of the given type with the given id or NULL if the data doesn't exist in the data store.
	 */
	public function getData($type, $id) {
		assert('is_string($type)');
		assert('$id === NULL || is_string($id)');

		if($id === NULL) {
			return NULL;
		}

		$this->expireData();

		if(!is_array($this->dataStore)) {
			return NULL;
		}

		if(!array_key_exists($type, $this->dataStore)) {
			return NULL;
		}

		if(!array_key_exists($id, $this->dataStore[$type])) {
			return NULL;
		}

		return $this->dataStore[$type][$id]['data'];
	}


	/**
	 * This function retrieves all data of the specified type from the data store.
	 *
	 * The data will be returned as an associative array with the id of the data as the key, and the data
	 * as the value of each key. The value will be stored as a copy of the original data. setData must be
	 * used to update the data.
	 *
	 * An empty array will be returned if no data of the given type is found.
	 *
	 * @param string $type The type of the data.
	 * @return array An associative array with all data of the given type.
	 */
	public function getDataOfType($type) {
		assert('is_string($type)');

		if(!is_array($this->dataStore)) {
			return array();
		}

		if(!array_key_exists($type, $this->dataStore)) {
			return array();
		}

		$ret = array();
		foreach($this->dataStore[$type] as $id => $info) {
			$ret[$id] = $info['data'];
		}

		return $ret;
	}

	/**
	 * Create a new session and cache it.
	 *
	 * @param string $sessionId  The new session we should create.
	 */
	public static function createSession($sessionId) {
		assert('is_string($sessionId)');
		self::$sessions[$sessionId] = NULL;
	}

	/**
	 * Load a session from the session handler.
	 *
	 * @param string|NULL $sessionId  The session we should load, or NULL to load the current session.
	 * @return The session which is stored in the session handler, or NULL if the session wasn't found.
	 */
	public static function getSession($sessionId = NULL) {
		assert('is_string($sessionId) || is_null($sessionId)');

		$sh = SimpleSAML_SessionHandler::getSessionHandler();

		if ($sessionId === NULL) {
			$checkToken = TRUE;
			$sessionId = $sh->getCookieSessionId();
		} else {
			$checkToken = FALSE;
		}

		if (array_key_exists($sessionId, self::$sessions)) {
			return self::$sessions[$sessionId];
		}


		$session = $sh->loadSession($sessionId);
		if($session === NULL) {
			return NULL;
		}

		assert('$session instanceof self');

		if ($checkToken) {
			$globalConfig = SimpleSAML_Configuration::getInstance();

			if ($session->authToken !== NULL) {
				$authTokenCookieName = $globalConfig->getString('session.authtoken.cookiename',
                                                                'SimpleSAMLAuthToken');
				if (!isset($_COOKIE[$authTokenCookieName])) {
					SimpleSAML_Logger::warning('Missing AuthToken cookie.');
					return NULL;
				}
				if ($_COOKIE[$authTokenCookieName] !== $session->authToken) {
					SimpleSAML_Logger::warning('Invalid AuthToken cookie.');
					return NULL;
				}
			}

			/* Run session check function if defined */
			$checkFunction = $globalConfig->getArray('session.check_function', NULL);
			if (isset($checkFunction)) {
				assert('is_callable($checkFunction)');
				$check = call_user_func($checkFunction, $session);
				if ($check !== TRUE) {
					SimpleSAML_Logger::warning('Session did not pass check function.');
					return NULL;
				}
			}
		}

		self::$sessions[$sessionId] = $session;

		return $session;
	}


	/**
	 * Get the current persistent authentication state.
	 *
	 * @param string $authority  The authority to retrieve the data from.
	 * @return array  The current persistent authentication state, or NULL if not authenticated.
	 */
	public function getAuthState($authority ) {
		assert('is_string($authority)');

		if (!isset($this->authData[$authority])) {
			return NULL;
		}

		return $this->authData[$authority];
	}


	/**
	 * Check whether the session cookie is set.
	 *
	 * This function will only return FALSE if is is certain that the cookie isn't set.
	 *
	 * @return bool  TRUE if it was set, FALSE if not.
	 */
	public function hasSessionCookie() {

		$sh = SimpleSAML_SessionHandler::getSessionHandler();
		return $sh->hasSessionCookie();
	}


	/**
	 * Add an SP association for an IdP.
	 *
	 * This function is only for use by the SimpleSAML_IdP class.
	 *
	 * @param string $idp  The IdP id.
	 * @param array $association  The association we should add.
	 */
	public function addAssociation($idp, array $association) {
		assert('is_string($idp)');
		assert('isset($association["id"])');
		assert('isset($association["Handler"])');

		if (!isset($this->associations)) {
			$this->associations = array();
		}

		if (!isset($this->associations[$idp])) {
			$this->associations[$idp] = array();
		}

		$this->associations[$idp][$association['id']] = $association;

		$this->dirty = TRUE;
	}


	/**
	 * Retrieve the associations for an IdP.
	 *
	 * This function is only for use by the SimpleSAML_IdP class.
	 *
	 * @param string $idp  The IdP id.
	 * @return array  The IdP associations.
	 */
	public function getAssociations($idp) {
		assert('is_string($idp)');

		if (!isset($this->associations)) {
			$this->associations = array();
		}

		if (!isset($this->associations[$idp])) {
			return array();
		}

		foreach ($this->associations[$idp] as $id => $assoc) {
			if (!isset($assoc['Expires'])) {
				continue;
			}
			if ($assoc['Expires'] >= time()) {
				continue;
			}

			unset($this->associations[$idp][$id]);
		}

		return $this->associations[$idp];
	}


	/**
	 * Remove an SP association for an IdP.
	 *
	 * This function is only for use by the SimpleSAML_IdP class.
	 *
	 * @param string $idp  The IdP id.
	 * @param string $associationId  The id of the association.
	 */
	public function terminateAssociation($idp, $associationId) {
		assert('is_string($idp)');
		assert('is_string($associationId)');

		if (!isset($this->associations)) {
			return;
		}

		if (!isset($this->associations[$idp])) {
			return;
		}

		unset($this->associations[$idp][$associationId]);

		$this->dirty = TRUE;
	}


	/**
	 * Retrieve authentication data.
	 *
	 * @param string $authority  The authentication source we should retrieve data from.
	 * @param string $name  The name of the data we should retrieve.
	 * @return mixed  The value, or NULL if the value wasn't found.
	 */
	public function getAuthData($authority, $name) {
		assert('is_string($authority)');
		assert('is_string($name)');

		if (!isset($this->authData[$authority][$name])) {
			return NULL;
		}
		return $this->authData[$authority][$name];
	}

}
