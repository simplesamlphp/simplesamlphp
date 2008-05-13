<?php

/* We need access to the configuration from config/config.php. */
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');

/* We need the randomBytes and stringToHex functions from Utilities. */
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a base class for session handlers that need to store
 * the session id in a cookie. It takes care of storing and retrieving the
 * session id.
 *
 * @author Olav Morken, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @abstract
 * @version $Id$
 */
abstract class SimpleSAML_SessionHandlerCookie
extends SimpleSAML_SessionHandler {

	/* This variable contains the current session id. */
	protected $session_id = NULL;



	/* This constructor initializes the session id based on what
	 * we receive in a cookie. We create a new session id and set
	 * a cookie with this id if we don't have a session id.
	 */
	protected function __construct() {
		/* Call the constructor in the base class in case it should
		 * become necessary in the future.
		 */
		parent::__construct();

		/* Attempt to retrieve the session id from the cookie. */
		if(array_key_exists('SimpleSAMLSessionID', $_COOKIE)) {
			$this->session_id = $_COOKIE['SimpleSAMLSessionID'];
		}

		/* Check if we have a valid session id. */
		if(self::isValidSessionID($this->session_id)) {
			/* We are done now if it was valid. */
			return;
		}

		/* We don't have a valid session. Create a new session id. */
		$this->session_id = self::createSessionID();
		setcookie('SimpleSAMLSessionID', $this->session_id, 0, '/',
			NULL, self::secureCookie(), TRUE);
	}


	/**
	 * This function checks if we should set a secure cookie.
	 *
	 * @return TRUE if the cookie should be secure, FALSE otherwise.
	 */
	private static function secureCookie() {

		if(!array_key_exists('HTTPS', $_SERVER)) {
			/* Not a https-request. */
			return FALSE;
		}

		if($_SERVER['HTTPS'] === 'off') {
			/* IIS with HTTPS off. */
			return FALSE;
		}

		/* Otherwise, HTTPS will be a non-empty string. */
		return $_SERVER['HTTPS'] !== '';
	}


	/* This function retrieves the session id of the current session.
	 *
	 * Returns:
	 *  The session id of the current session.
	 */
	public function getSessionId() {
		return $this->session_id;
	}


	/* This static function creates a session id. A session id consists
	 * of 32 random hexadecimal characters.
	 *
	 * Returns:
	 *  A random session id.
	 */
	private static function createSessionID() {
		return SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(16));
	}


	/* This static function validates a session id. A session id is valid
	 * if it only consists of characters which are allowed in a session id
	 * and it is the correct length.
	 *
	 * Parameters:
	 *  $session_id  The session id we should validate.
	 *
	 * Returns:
	 *  TRUE if this session id is valid, FALSE if not.
	 */
	private static function isValidSessionID($session_id) {
		if(!is_string($session_id)) {
			return FALSE;
		}

		if(strlen($session_id) != 32) {
			return FALSE;
		}

		if(preg_match('/[^0-9a-f]/', $session_id)) {
			return FALSE;
		}

		return TRUE;
	}
}

?>