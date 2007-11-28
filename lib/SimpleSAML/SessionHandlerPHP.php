<?php

/*
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a session handler which uses the default php
 * session handler for storage.
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

		/* Initialize the php session handling. */
		session_start();
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
		return $_SESSION[$key];
	}
}

?>