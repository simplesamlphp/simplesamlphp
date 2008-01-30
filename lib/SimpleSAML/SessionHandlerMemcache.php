<?php

/* We use the MemcacheStore class to store session information. */
require_once('SimpleSAML/MemcacheStore.php');

/* We base this session handler on the SessionHandlerCookie helper
 * class. This saves us from having to handle session ids in this class.
 */
require_once('SimpleSAML/SessionHandlerCookie.php');

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


	/* This function is used to store data in this session object.
	 *
	 * See the information in SimpleSAML_SessionHandler::set(...) for
	 * more information.
	 */
	public function set($key, $value) {
		$this->store->set($key, $value);
	}


	/* This function retrieves a value from this session object.
	 *
	 * See the information in SimpleSAML_SessionHandler::get(...) for
	 * more information.
	 */
	public function get($key) {
		return $this->store->get($key);
	}
}

?>