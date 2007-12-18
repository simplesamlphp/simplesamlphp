<?php

/*
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a base class for session handlers that need to store
 * the session id in a cookie. It takes care of storing and retrieving the
 * session id.
 */

/* We need access to the configuration from config/config.php. */
require_once('SimpleSAML/Configuration.php');


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
		setcookie('SimpleSAMLSessionID', $this->session_id, 0, '/');
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
		$id = '';
		for($i = 0; $i < 32; $i++) {
			/* TODO: Is rand(...) secure enough? */
			$id .= dechex(rand(0, 15));
		}

		return $id;
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