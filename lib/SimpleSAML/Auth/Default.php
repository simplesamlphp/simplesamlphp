<?php

/**
 * Implements the default behaviour for authentication.
 *
 * This class contains an implementation for default behaviour when authenticating. It will
 * save the session information it got from the authentication client in the users session.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 *
 * @deprecated This class will be removed in SSP 2.0.
 */
class SimpleSAML_Auth_Default {


	/**
	 * @deprecated This method will be removed in SSP 2.0. Use SimpleSAML_Auth_Source::initLogin() instead.
	 */
	public static function initLogin($authId, $return, $errorURL = NULL,
		array $params = array()) {

		$as = SimpleSAML_Auth_Source::getById($authId);
		if ($as === null) {
			throw new Exception('Invalid authentication source: ' . $authId);
		}
		$as->initLogin($return, $errorURL, $params);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use
	 * SimpleSAML_Auth_State::getPersistentAuthData() instead.
	 */
	public static function extractPersistentAuthState(array &$state) {

		return SimpleSAML_Auth_State::getPersistentAuthData($state);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML_Auth_Source::loginCompleted() instead.
	 */
	public static function loginCompleted($state) {
		SimpleSAML_Auth_Source::loginCompleted($state);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0.
	 */
	public static function initLogoutReturn($returnURL, $authority) {
		$as = SimpleSAML_Auth_Source::getById($authority);
		if ($as === null) {
			throw new Exception('Invalid authentication source: ' . $authority);
		}
		$as->initLogoutReturn($returnURL);
	}


	/**
	 * Start logout.
	 *
	 * This function starts a logout operation from the current authentication
	 * source. This function never returns.
	 *
	 * @param string $returnURL The URL we should redirect the user to after
	 * logging out. No checking is performed on the URL, so make sure to verify
	 * it on beforehand if the URL is obtained from user input. Refer to
	 * \SimpleSAML\Utils\HTTP::checkURLAllowed() for more information.
	 * @param string|NULL $authority The authentication source we are logging
	 * out from.
	 * @return void This function never returns.
	 */
	public static function initLogout($returnURL, $authority) {
		assert('is_string($returnURL)');
		assert('is_string($authority)');

		self::initLogoutReturn($returnURL, $authority);

		/* Redirect... */
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($returnURL);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML_Auth_Source::logoutCompleted() instead.
	 */
	public static function logoutCompleted($state) {
		SimpleSAML_Auth_Source::logoutCompleted($state);
	}


	/**
	 * Called when the authentication source receives an external logout request.
	 *
	 * @param array $state  State array for the logout operation.
	 *
	 * @deprecated This method will be removed in SSP 2.0. Please use SimpleSAML_Auth_Source::logoutCallback() instead.
	 */
	public static function logoutCallback($state) {
		SimpleSAML_Auth_Source::logoutCallback($state);
	}


	/**
	 * @deprecated This method will be removed in SSP 2.0. Please use
	 * sspmod_saml_Auth_Source_SP::handleUnsolicitedAuth() instead.
	 */
	public static function handleUnsolicitedAuth($authId, array $state, $redirectTo) {
		sspmod_saml_Auth_Source_SP::handleUnsolicitedAuth($authId, $state, $redirectTo);
	}

}
