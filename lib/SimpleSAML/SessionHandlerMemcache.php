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

	/* Initialize the memcache session handling. This constructor is
	 * protected because it should only be called from
	 * SimpleSAML_SessionHandler::createSessionHandler(...).
	 */
	protected function __construct() {

		/* Call parent constructor to allow it to configure the session
		 * id.
		 */
		parent::__construct();
	}


	/**
	 * Save the current session to memcache.
	 *
	 * @param SimpleSAML_Session $session  The session object we should save.
	 */
	public function saveSession(SimpleSAML_Session $session) {

		SimpleSAML_Memcache::set('simpleSAMLphp.session.' . $this->session_id, $session);
	}


	/**
	 * Load the session from memcache.
	 *
	 * @return SimpleSAML_Session|NULL  The session object, or NULL if it doesn't exist.
	 */
	public function loadSession() {

		$session = SimpleSAML_Memcache::get('simpleSAMLphp.session.' . $this->session_id);
		if ($session !== NULL) {
			assert('$session instanceof SimpleSAML_Session');
			return $session;
		}

		/* For backwards compatibility, check the MemcacheStore object. */
		$store = SimpleSAML_MemcacheStore::find($this->session_id);
		if ($store === NULL) {
			return NULL;
		}

		$session = $store->get('SimpleSAMLphp_SESSION');
		if ($session === NULL) {
			return NULL;
		}

		assert('is_string($session)');

		$session = unserialize($session);
		assert('$session instanceof SimpleSAML_Session');

		return $session;
	}

}
