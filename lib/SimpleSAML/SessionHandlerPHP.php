<?php

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a session handler which uses the default php
 * session handler for storage.
 *
 * @author Olav Morken, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_SessionHandlerPHP extends SimpleSAML_SessionHandler {

	/* Initialize the PHP session handling. This constructor is protected
	 * because it should only be called from
	 * SimpleSAML_SessionHandler::createSessionHandler(...).
	 */
	protected function __construct() {

		/* Call the parent constructor in case it should become
		 * necessary in the future.
		 */
		parent::__construct();

		/* Initialize the php session handling.
		 *
		 * If session_id() returns a blank string, then we need
		 * to call session start. Otherwise the session is already
		 * started, and we should avoid calling session_start().
		 */
		if(session_id() === '') {
			$config = SimpleSAML_Configuration::getInstance();
			
			$cookiepath = ($config->getValue('session.phpsession.limitedpath', FALSE) ? '/' . $config->getValue('baseurlpath') : '/');
			session_set_cookie_params(0, $cookiepath, NULL, SimpleSAML_Utilities::isHTTPS());
			
			$cookiename = $config->getValue('session.phpsession.cookiename', NULL);
			if (!empty($cookiename)) session_name($cookiename);

			if(!array_key_exists(session_name(), $_COOKIE)) {
				/* Session cookie unset - session id not set. Generate new (secure) session id. */
				session_id(SimpleSAML_Utilities::stringToHex(SimpleSAML_Utilities::generateRandomBytes(16)));
			}
			
			session_start();
		}
	}


	/* This function retrieves the session id of the current session.
	 *
	 * Returns:
	 *  The session id of the current session.
	 */
	public function getSessionId() {
		return session_id();
	}


	/* This function is used to store data in this session object.
	 *
	 * See the information in SimpleSAML_SessionHandler::set(...) for
	 * more information.
	 */
	public function set($key, $value) {
		$_SESSION[$key] = $value;
	}


	/* This function retrieves a value from this session object.
	 *
	 * See the information in SimpleSAML_SessionHandler::get(...) for
	 * more information.
	 */
	public function get($key) {
		/* Check if key exists first to avoid notice-messages in the
		 * log.
		 */
		if(!array_key_exists($key, $_SESSION)) {
			/* We should return NULL if we don't have that
			 * key in the session.
			 */
			return NULL;
		}

		return $_SESSION[$key];
	}
}

?>