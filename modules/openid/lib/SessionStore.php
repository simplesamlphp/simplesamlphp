<?php

/**
 * Class which implements the openid session store logic.
 *
 * This class has the interface specified in the constructor of the
 * Auth_OpenID_Consumer class.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_openid_SessionStore {

	/**
	 * Retrieve a key from the session store.
	 *
	 * @param string $key  The key we should retrieve.
	 * @return mixed  The value stored with the given key, or NULL if the key isn't found.
	 */
	public function get($key) {
		assert('is_string($key)');

		$session = SimpleSAML_Session::getInstance();
		return $session->getData('openid.session', $key);
	}


	/**
	 * Save a value to the session store under the given key.
	 *
	 * @param string $key  The key we should save.
	 * @param mixed NULL $value  The value we should save.
	 */
	public function set($key, $value) {
		assert('is_string($key)');

		$session = SimpleSAML_Session::getInstance();
		$session->setData('openid.session', $key, $value);
	}


	/**
	 * Delete a key from the session store.
	 *
	 * @param string $key  The key we should delete.
	 */
	public function del($key) {
		assert('is_string($key)');

		$session = SimpleSAML_Session::getInstance();
		$session->deleteData('openid.session', $key);
	}

}
