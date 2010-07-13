<?php

/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file defines a session handler which uses the MemcacheStore
 * class to store data in memcache servers.
 *
 * @author Olav Morken, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_SessionHandlerMemcache
extends SimpleSAML_SessionHandlerCookie {


	/* This variable contains a reference to the MemcacheStore object
	 * which contains the session data.
	 */
	private $store = NULL;



	/* Initialize the memcache session handling. This constructor is
	 * protected because it should only be called from
	 * SimpleSAML_SessionHandler::createSessionHandler(...).
	 */
	protected function __construct() {

		/* Call parent constructor to allow it to configure the session
		 * id.
		 */
		parent::__construct();

		/* Load the session object if it already exists. */
		$this->store = SimpleSAML_MemcacheStore::find($this->session_id);

		if($this->store === NULL) {
			/* We didn't find the session. This may be because the
			 * session has expired, or it could be because this is
			 * a new session. In any case we create a new session.
			 */
			$this->store = new SimpleSAML_MemcacheStore(
				$this->session_id);
		}
	}


	/**
	 * Save the current session to the PHP session array.
	 *
	 * @param SimpleSAML_Session $session  The session object we should save.
	 */
	public function saveSession(SimpleSAML_Session $session) {

		$this->store->set('SimpleSAMLphp_SESSION', serialize($session));
	}


	/**
	 * Load the session from the PHP session array.
	 *
	 * @return SimpleSAML_Session|NULL  The session object, or NULL if it doesn't exist.
	 */
	public function loadSession() {

		$session = $this->store->get('SimpleSAMLphp_SESSION');
		if ($session === NULL) {
			return NULL;
		}

		assert('is_string($session)');

		$session = unserialize($session);
		assert('$session instanceof SimpleSAML_Session');

		return $session;
	}

}
