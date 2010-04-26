<?php

/**
 * Implements the default behaviour for authentication.
 *
 * This class contains an implementation for default behaviour when authenticating. It will
 * save the session information it got from the authentication client in the users session.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Auth_Default {


	/**
	 * Start authentication.
	 *
	 * This function never returns.
	 *
	 * @param string $authId  The identifier of the authentication source.
	 * @param string|array $return  The URL or function we should direct the user to after authentication.
	 * @param string|NULL $errorURL  The URL we should direct the user to after failed authentication.
	 *                               Can be NULL, in which case a standard error page will be shown.
	 * @param array $params  Extra information about the login. Different authentication requestors may
	 *                       provide different information. Optional, will default to an empty array.
	 */
	public static function initLogin($authId, $return, $errorURL = NULL, array $params = array()) {
		assert('is_string($authId)');
		assert('is_string($return) || is_array($return)');
		assert('is_string($errorURL) || is_null($errorURL)');

		$state = array_merge($params, array(
			'SimpleSAML_Auth_Default.id' => $authId,
			'SimpleSAML_Auth_Default.Return' => $return,
			'SimpleSAML_Auth_Default.ErrorURL' => $errorURL,
			'LoginCompletedHandler' => array(get_class(), 'loginCompleted'),
			'LogoutCallback' => array(get_class(), 'logoutCallback'),
			'LogoutCallbackState' => array(
				'SimpleSAML_Auth_Default.logoutSource' => $authId,
			),
		));

		if (is_string($return)) {
			$state['SimpleSAML_Auth_Default.ReturnURL'] = $return;
		}

		if ($errorURL !== NULL) {
			$state[SimpleSAML_Auth_State::EXCEPTION_HANDLER_URL] = $errorURL;
		}

		$as = SimpleSAML_Auth_Source::getById($authId);
		if ($as === NULL) {
			throw new Exception('Invalid authentication source: ' . $authId);
		}

		try {
			$as->authenticate($state);
		} catch (SimpleSAML_Error_Exception $e) {
			SimpleSAML_Auth_State::throwException($state, $e);
		} catch (Exception $e) {
			$e = new SimpleSAML_Error_UnserializableException($e);
			SimpleSAML_Auth_State::throwException($state, $e);
		}
		self::loginCompleted($state);
	}


	/**
	 * Extract the persistent authentication state from the state array.
	 *
	 * @param array $state  The state after the login.
	 * @return array  The persistent authentication state.
	 */
	private static function extractPersistentAuthState(array &$state) {

		/* Save persistent authentication data. */
		$persistentAuthState = array();
		if (isset($state['PersistentAuthData'])) {
			foreach ($state['PersistentAuthData'] as $key) {
				if (isset($state[$key])) {
					$persistentAuthState[$key] = $state[$key];
				}
			}
		}

		return $persistentAuthState;
	}


	/**
	 * Called when a login operation has finished.
	 *
	 * @param array $state  The state after the login.
	 */
	public static function loginCompleted($state) {
		assert('is_array($state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.Return", $state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.id", $state)');
		assert('array_key_exists("Attributes", $state)');
		assert('!array_key_exists("LogoutState", $state) || is_array($state["LogoutState"])');

		$return = $state['SimpleSAML_Auth_Default.Return'];

		/* Save session state. */
		$session = SimpleSAML_Session::getInstance();
		$session->doLogin($state['SimpleSAML_Auth_Default.id'], self::extractPersistentAuthState($state));
		$session->setAttributes($state['Attributes']);
		if(array_key_exists('Expires', $state)) {
			$session->setSessionDuration($state['Expires'] - time());
		}

		if (array_key_exists('LogoutState', $state)) {
			$session->setLogoutState($state['LogoutState']);
		}

		if (array_key_exists('IdP', $state)) {
			$session->setIdP($state['IdP']);
		} else {
			$session->setIdP(NULL);
		}

		if (is_string($return)) {
			/* Redirect... */
			SimpleSAML_Utilities::redirect($return);
		} else {
			call_user_func($return, $state);
			assert('FALSE');
		}
	}


	/**
	 * Start logout.
	 *
	 * This function starts a logout operation from the current authentication source. This function
	 * will return if the logout operation does not require a redirect.
	 *
	 * @param string $returnURL  The URL we should redirect the user to after logging out.
	 */
	public static function initLogoutReturn($returnURL) {
		assert('is_string($returnURL)');

		$session = SimpleSAML_Session::getInstance();

		$state = $session->getLogoutState();
		$authId = $session->getAuthority();
		$session->doLogout();

		$state['SimpleSAML_Auth_Default.ReturnURL'] = $returnURL;
		$state['LogoutCompletedHandler'] = array(get_class(), 'logoutCompleted');

		$as = SimpleSAML_Auth_Source::getById($authId);
		if ($as === NULL) {
			/* The authority wasn't an authentication source... */
			self::logoutCompleted($state);
		}

		$as->logout($state);
	}


	/**
	 * Start logout.
	 *
	 * This function starts a logout operation from the current authentication source. This function
	 * never returns.
	 *
	 * @param string $returnURL  The URL we should redirect the user to after logging out.
	 */
	public static function initLogout($returnURL) {
		assert('is_string($returnURL)');

		self::initLogoutReturn($returnURL);

		/* Redirect... */
		SimpleSAML_Utilities::redirect($returnURL);
	}


	/**
	 * Called when logout operation completes.
	 *
	 * This function never returns.
	 *
	 * @param array $state  The state after the logout.
	 */
	public static function logoutCompleted($state) {
		assert('is_array($state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.ReturnURL", $state)');

		$returnURL = $state['SimpleSAML_Auth_Default.ReturnURL'];

		/* Redirect... */
		SimpleSAML_Utilities::redirect($returnURL);
	}


	/**
	 * Called when the authentication source receives an external logout request.
	 *
	 * @param array $state  State array for the logout operation.
	 */
	public static function logoutCallback($state) {
		assert('is_array($state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.logoutSource", $state)');

		$source = $state['SimpleSAML_Auth_Default.logoutSource'];

		$session = SimpleSAML_Session::getInstance();
		$authId = $session->getAuthority();

		if ($authId !== $source) {
			SimpleSAML_Logger::warning('Received logout from different authentication source ' .
				'than the current. Current is ' . var_export($authId, TRUE) .
				'. Logout source is ' . var_export($source, TRUE) . '.');
			return;
		}

		$session->doLogout();
	}


	/**
	 * Handle a unsoliced login operations.
	 *
	 * This function creates a session from the received information. It
	 * will then redirect to the given URL.
	 *
	 * This is used to handle IdP initiated SSO.
	 *
	 * @param string $authId  The id of the authentication source that received the request.
	 * @param array $state  A state array.
	 * @param string $redirectTo  The URL we should redirect the user to after
	 *                            updating the session.
	 */
	public static function handleUnsolicedAuth($authId, array $state, $redirectTo) {
		assert('is_string($authId)');
		assert('is_string($redirectTo)');

		$session = SimpleSAML_Session::getInstance();
		$session->doLogin($authId, self::extractPersistentAuthState($state));

		if (array_key_exists('Attributes', $state)) {
			$session->setAttributes($state['Attributes']);
		} else {
			$session->setAttributes(array());
		}

		if(array_key_exists('Expires', $state)) {
			$session->setSessionDuration($state['Expires'] - time());
		}

		if (array_key_exists('LogoutState', $state)) {
			$session->setLogoutState($state['LogoutState']);
		}

		if (array_key_exists('IdP', $state)) {
			$session->setIdP($state['IdP']);
		} else {
			$session->setIdP(NULL);
		}

		SimpleSAML_Utilities::redirect($redirectTo);
	}

}

?>