<?php

/**
 * Helper class for simple authentication applications.
 *
 * This class will use the authentication source specified in the
 * 'default-authsource' option in 'config.php'.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Auth_Simple {

	/**
	 * Check if the user is authenticated.
	 *
	 * This function checks if the user is authenticated with the default
	 * authentication source selected by the 'default-authsource' option in
	 * 'config.php'.
	 *
	 * @return bool  TRUE if the user is authenticated, FALSE if not.
	 */
	public static function isAuthenticated() {
		$config = SimpleSAML_Configuration::getInstance();
		$session = SimpleSAML_Session::getInstance();

		$as = $config->getString('default-authsource');

		return $session->isValid($as);
	}


	/**
	 * Require the user to be authenticated.
	 *
	 * If the user is authenticated, this function returns immediately.
	 *
	 * If the user isn't authenticated, this function will authenticate the
	 * user with the authentication source, and then return the user to the
	 * current page.
	 *
	 * If $allowPost is set to TRUE, any POST data to the current page is
	 * preserved. If $allowPost is FALSE, the user will be returned to the
	 * current page with a GET request.
	 *
	 * @param bool $allowPost  Whether POST requests will be preserved. The default is to preserve POST requests.
	 */
	public static function requireAuth($allowPost = TRUE) {
		assert('is_bool($allowPost)');

		$config = SimpleSAML_Configuration::getInstance();
		$session = SimpleSAML_Session::getInstance();

		$as = $config->getString('default-authsource');

		if ($session->isValid($as)) {
			/* Already authenticated. */
			return;
		}

		$url = SimpleSAML_Utilities::selfURL();
		if ($allowPost && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$url = SimpleSAML_Utilities::createPostRedirectLink($url, $_POST);
		}

		SimpleSAML_Auth_Default::initLogin($as, $url);
	}


	/**
	 * Log the user out.
	 *
	 * This function logs the user out. It will never return. By default,
	 * it will cause a redirect to the current page after logging the user
	 * out, but a different URL can be given with the $url parameter.
	 *
	 * @param string|NULL $url  The url the user should be redirected to after logging out.
	 *                          Defaults to the current page.
	 */
	public static function logout($url = NULL) {
		assert('is_string($url) || is_null($url)');

		if ($url === NULL) {
			$url = SimpleSAML_Utilities::selfURL();
		}

		SimpleSAML_Auth_Default::initLogout($url);
	}


	/**
	 * Retrieve attributes of the current user.
	 *
	 * This function will retrieve the attributes of the current user if
	 * the user is authenticated. If the user isn't authenticated, it will
	 * return an empty array.
	 *
	 * @return array  The users attributes.
	 */
	public static function getAttributes() {

		if (!self::isAuthenticated()) {
			/* Not authenticated. */
			return array();
		}

		/* Authenticated. */
		$session = SimpleSAML_Session::getInstance();
		return $session->getAttributes();
	}

}

?>