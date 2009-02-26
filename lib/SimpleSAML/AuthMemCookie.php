<?php

/**
 * This is a helper class for the Auth MemCookie module.
 * It handles the configuration, and implements the logout handler.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_AuthMemCookie {

	/**
	 * This is the singleton instance of this class.
	 */
	private static $instance = NULL;


	/**
	 * The configuration for Auth MemCookie.
	 */
	private $amcConfig;

	/**
	 * This function is used to retrieve the singleton instance of this class.
	 *
	 * @return The singleton instance of this class.
	 */
	public static function getInstance() {
		if(self::$instance === NULL) {
			self::$instance = new SimpleSAML_AuthMemCookie();
		}

		return self::$instance;
	}


	/**
	 * This function implements the constructor for this class. It loads the Auth MemCookie configuration.
	 */
	private function __construct() {
		/* Load Auth MemCookie configuration. */
		$this->amcConfig = SimpleSAML_Configuration::getConfig('authmemcookie.php');
	}


	/**
	 * Retrieve the login method which should be used to authenticate the user.
	 *
	 * @return  The login type which should be used for Auth MemCookie.
	 */
	public function getLoginMethod() {
		$loginMethod = $this->amcConfig->getValue('loginmethod', 'saml2');
		$supportedLogins = array(
			'saml2',
			'shib13',
			);
		if(!in_array($loginMethod, $supportedLogins, TRUE)) {
			throw new Exception('Configuration option \'loginmethod\' contains an invalid value.');
		}

		return $loginMethod;
	}


	/**
	 * This function retrieves the name of the cookie from the configuration.
	 *
	 * @return The name of the cookie.
	 */
	public function getCookieName() {
		$cookieName = $this->amcConfig->getValue('cookiename', 'AuthMemCookie');
		if(!is_string($cookieName) || strlen($cookieName) === 0) {
			throw new Exception('Configuration option \'cookiename\' contains an invalid value. This option should be a string.');
		}

		return $cookieName;
	}


	/**
	 * This function retrieves the name of the attribute which contains the username from the configuration.
	 *
	 * @return The name of the attribute which contains the username.
	 */
	public function getUsernameAttr() {
		$usernameAttr = $this->amcConfig->getValue('username');
		if($usernameAttr === NULL) {
			throw new Exception('Missing required configuration option \'username\' in authmemcookie.php.');
		}

		return $usernameAttr;
	}


	/**
	 * This function retrieves the name of the attribute which contains the groups from the configuration.
	 *
	 * @return The name of the attribute which contains the groups.
	 */
	public function getGroupsAttr() {
		$groupsAttr = $this->amcConfig->getValue('groups');

		return $groupsAttr;
	}


	/**
	 * This function creates and initializes a Memcache object from our configuration.
	 *
	 * @return A Memcache object initialized from our configuration.
	 */
	public function getMemcache() {

		$memcacheHost = $this->amcConfig->getValue('memcache.host', '127.0.0.1');
		if(!is_string($memcacheHost)) {
			throw new Exception('Invalid value of the \'memcache.host\' configuration option. This option' .
					    ' should be a string with a hostname or a string with an IP address.');
		}

		$memcachePort = $this->amcConfig->getValue('memcache.port', 11211);
		if(!is_int($memcachePort)) {
			throw new Exception('Invalid value of the \'memcache.port\' configuration option. This option' .
					    ' should be an integer.');
		}

		$memcache = new Memcache;
		$memcache->connect($memcacheHost, $memcachePort);

		return $memcache;
	}


	/**
	 * This function logs the user out by deleting the session information from memcache.
	 */
	private function doLogout() {

		$cookieName = $this->getCookieName();

		/* Check if we have a valid cookie. */
		if(!array_key_exists($cookieName, $_COOKIE)) {
			return;
		}

		$sessionID = $_COOKIE[$cookieName];

		/* Delete the session from memcache. */

		$memcache = $this->getMemcache();
		$memcache->delete($sessionID);
	}


	/**
	 * This function implements the logout handler. It deletes the information from Memcache.
	 */
	public static function logoutHandler() {
		self::getInstance()->doLogout();
	}
}

?>