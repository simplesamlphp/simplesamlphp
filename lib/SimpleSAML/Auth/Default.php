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
	 * @param string $returnURL  The URL we should direct the user to after authentication.
	 * @param string|NULL $errorURL  The URL we should direct the user to after failed authentication.
	 *                               Can be NULL, in which case a standard error page will be shown.
	 * @param array $hints  Extra information about the login. Different authentication requestors may
	 *                      provide different information. Optional, will default to an empty array.
	 */
	public static function initLogin($authId, $returnURL, $errorURL = NULL, $hints = array()) {
		assert('is_string($authId)');
		assert('is_string($returnURL)');
		assert('is_string($errorURL) || is_null($errorURL)');
		assert('is_array($hints)');

		$state = array(
			'SimpleSAML_Auth_Default.id' => $authId,
			'SimpleSAML_Auth_Default.ReturnURL' => $returnURL,
			'SimpleSAML_Auth_Default.ErrorURL' => $errorURL,
			'LoginCompletedHandler' => array(get_class(), 'loginCompleted'),
			'LoginFailedHandler' => array(get_class(), 'loginFailed'),
			);

		if (array_key_exists('SPMetadata', $hints)) {
			$state['SPMetadata'] = $hints['SPMetadata'];
		}
		if (array_key_exists('IdPMetadata', $hints)) {
			$state['IdPMetadata'] = $hints['IdPMetadata'];
		}

		$as = SimpleSAML_Auth_Source::getById($authId);
		if ($as === NULL) {
			throw new Exception('Invalid authentication source: ' . $authId);
		}

		$as->authenticate($state);
		self::loginCompleted($state);
	}


	/**
	 * Called when a login operation has finished.
	 *
	 * @param array $state  The state after the login.
	 */
	public static function loginCompleted($state) {
		assert('is_array($state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.ReturnURL", $state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.id", $state)');
		assert('array_key_exists("Attributes", $state)');
		assert('!array_key_exists("LogoutState", $state) || is_array($state["LogoutState"])');

		$returnURL = $state['SimpleSAML_Auth_Default.ReturnURL'];

		/* Save session state. */
		$session = SimpleSAML_Session::getInstance();
		$session->doLogin($state['SimpleSAML_Auth_Default.id']);
		$session->setAttributes($state['Attributes']);
		if(array_key_exists('Expires', $state)) {
			$session->setSessionDuration($state['Expires'] - time());
		}

		if (array_key_exists('LogoutState', $state)) {
			$session->setLogoutState($state['LogoutState']);
		}

		/* Redirect... */
		SimpleSAML_Utilities::redirect($returnURL);
	}


	/**
	 * Called when a login operation fails.
	 *
	 * @param array $state  The state array.
	 * @param string $statusCode  A status code, in the form of an URI, which indicates why the login failed.
	 * @param string $statusMessage  A text which describes why the login failed.
	 */
	public static function loginFailed($state, $statusCode, $statusMessage) {
		assert('is_array($state)');
		assert('array_key_exists("SimpleSAML_Auth_Default.ErrorURL", $state)');
		assert('is_string($statusCode)');
		assert('is_string($statusMessage)');

		$url = $state['SimpleSAML_Auth_Default.ErrorURL'];
		if ($url === NULL) {
			/* We don't have an error handler. Show an error page. */
			SimpleSAML_Utilities::fatalError($session->getTrackID(), 'RESPONSESTATUSNOSUCCESS',
				new Exception('StatusCode = \'' . $statusCode . '\'; StatusMessage = \'' .
					$statusMessage . '\';'));
		}

		$info = array('StatusCode' => $statusCode, 'StatusMessage' => $statusMessage);

		/* Redirect... */
		SimpleSAML_Utilities::redirect($url, $info);
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
		self::logoutCompleted($state);
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

}

?>