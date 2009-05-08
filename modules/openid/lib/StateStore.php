<?php

/* The OpenID library relies on manual loading of classes. */
require_once('Auth/OpenID/Interface.php');
require_once('Auth/OpenID/Association.php');

/**
 * Implementation of Auth_OpenID_OpenIDStore which saves the state in
 * an state-array.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_openid_StateStore extends Auth_OpenID_OpenIDStore{


	/**
	 * Reference to the state array.
	 */
	private $state;


	/**
	 * Reference to the array with associations in the state array.
	 */
	private $associations;


	/**
	 * Initializes the store object.
	 *
	 * @param array &$state  Reference to the state array.
	 */
	public function __construct(&$state) {
		assert('is_array($state)');

		$this->state =& $state;

		if (!array_key_exists('openid:Assocs', $state)) {
			$state['openid:Assocs'] = array();
		}

		$this->associations =& $state['openid:Assocs'];
	}


	/**
	 * Determine whether a given nonce can be used.
	 *
	 * This implementation accepts all nonces, and relies on the state array
	 * being invalidated when login completes to prevent replay attacks.
	 *
	 * @return bool  This function always returns TRUE.
	 */
	public function useNonce($server_url, $timestamp, $salt) {
		return TRUE;
	}


	/**
	 * Retrieve all associations for a given server.
	 *
	 * The associations are returned as an associative array with the
	 * association handle as the index and the association object as
	 * the value.
	 *
	 * @param string $server_url  The server.
	 * @return array  Associative array with associations.
	 */
	private function getServerAssociations($server_url) {
		assert('is_string($server_url)');

		if (!array_key_exists($server_url, $this->associations)) {
			return array();
		}

		$ret = array();
		foreach ($this->associations[$server_url] as $handle => $association) {

			$association = Auth_OpenID_Association::deserialize(
				'Auth_OpenID_Association', $association);
			if ($association === NULL) {
				continue;
			}

			if ($association->getExpiresIn() == 0) {
				continue;
			}

			$ret[$handle] = $association;
		}

		return $ret;
	}


	/**
	 * Retrieve an association with the given handle.
	 *
	 * @param string $server_url  The server.
	 * @param string $handle  The handle of the association.
	 * @return Auth_OpenID_Association|NULL  The association object, if it is found.
	 */
	private function readAssociation($server_url, $handle) {
		assert('is_string($server_url)');
		assert('is_string($handle)');

		$sassoc = $this->getServerAssociations($server_url);
		if (!array_key_exists($handle, $sassoc)) {
			return NULL;
		}

		return $sassoc[$handle];
	}


	/**
	 * Retrieve an association.
	 *
	 * This function retrieves an association with the given handle, or the most
	 * recent association if no handle is given.
	 *
	 * @param string $server_url  The server.
	 * @param string|NULL $handle  The association handle.
	 * @return Auth_OpenID_Association|NULL  The association object, if it is found.
	 */
	public function getAssociation($server_url, $handle = NULL) {
		assert('is_string($server_url)');
		assert('is_null($handle) || is_string($handle)');

		if ($handle !== NULL) {
			return $this->readAssociation($server_url, $handle);
		}


		/* $handle is NULL - we should retrieve the most recent association. */

		$sassoc = $this->getServerAssociations($server_url);

		$recentAssoc = NULL;
		foreach ($sassoc as $handle => $association) {
			if ($recentAssoc === NULL) {
				/* No $recentAssoc - this is the most recent association. */
				$recentAssoc = $association;
				continue;
			}

			if ($association->issued > $recentAssoc->issued) {
				/* More recently issued than $recentAssoc. */
				$recentAssoc = $association;
			}
		}

		return $recentAssoc;
	}


	/**
	 * Store an association.
	 *
	 * This function stores an association.

	 * @param string $server_url  The server.
	 * @param Auth_OpenID_Association $association  The association which should be stored.
	 * @return bool  TRUE if the association is stored, FALSE if not.
	 */
	public function storeAssociation($server_url, Auth_OpenID_Association $association) {
		assert('is_string($server_url)');

		if (!array_key_exists($server_url, $this->associations)) {
			$this->associations[$server_url] = array();
		}

		$handle = $association->handle;
		assert('is_string($handle)');

		$this->associations[$server_url][$handle] = $association->serialize();

		/* We rely on saveState saving with the same id as before. */
		SimpleSAML_Auth_State::saveState($this->state, 'openid:state');

		return TRUE;
	}

}

?>