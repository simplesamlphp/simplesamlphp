<?php

/**
 * This class defines a base class for authentication source.
 *
 * An authentication source is any system which somehow authenticate the user.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
abstract class SimpleSAML_Auth_Source {


	/**
	 * The authentication source identifier.
	 *
	 * This identifier can be used to look up this object, for example when returning from a login form.
	 */
	protected $authId;


	/**
	 * Constructor for an authentication source.
	 *
	 * Any authentication source which implements its own constructor must call this
	 * constructor first.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array &$config  Configuration for this authentication source.
	 */
	public function __construct($info, &$config) {
		assert('is_array($info)');
		assert('is_array($config)');

		assert('array_key_exists("AuthId", $info)');
		$this->authId = $info['AuthId'];
	}


	/**
	 * Process a request.
	 *
	 * If an authentication source returns from this function, it is assumed to have
	 * authenticated the user, and should have set elements in $state with the attributes
	 * of the user.
	 *
	 * If the authentication process requires additional steps which make it impossible to
	 * complete before returning from this function, the authentication source should
	 * save the state, and at a later stage, load the state, update it with the authentication
	 * information about the user, and call completeAuth with the state array.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	abstract public function authenticate(&$state);


	/**
	 * Complete authentication.
	 *
	 * This function should be called if authentication has completed. It will never return,
	 * except in the case of exceptions. Exceptions thrown from this page should not be caught,
	 * but should instead be passed to the top-level exception handler.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public static function completeAuth(&$state) {
		assert('is_array($state)');
		assert('array_key_exists("LoginCompletedHandler", $state)');

		SimpleSAML_Auth_State::deleteState($state);

		$func = $state['LoginCompletedHandler'];
		assert('is_callable($func)');

		call_user_func($func, $state);
		assert(FALSE);
	}


	/**
	 * Create authentication source object from configuration array.
	 *
	 * This function takes an array with the configuration for an authentication source object,
	 * and returns the object.
	 *
	 * @param string $authId  The authentication source identifier.
	 * @param array $config  The configuration.
	 * @return SimpleSAML_Auth_Source  The parsed authentication source.
	 */
	private static function parseAuthSource($authId, $config) {
		assert('is_string($authId)');
		assert('is_array($config)');

		if (!array_key_exists(0, $config) || !is_string($config[0])) {
			throw new Exception('Invalid authentication source \'' . $authId .
				'\': First element must be a string which identifies the authentication source.');
		}

		$className = SimpleSAML_Module::resolveClass($config[0], 'Auth_Source',
			'SimpleSAML_Auth_Source');

		$info = array('AuthId' => $authId);
		unset($config[0]);
		return new $className($info, $config);
	}


	/**
	 * Retrieve authentication source.
	 *
	 * This function takes an id of an authentication source, and returns the
	 * AuthSource object.
	 *
	 * @param string $authId  The authentication source identifier.
	 * @return SimpleSAML_Auth_Source|NULL  The AuthSource object, or NULL if no authentication
	 *     source with the given identifier is found.
	 */
	public static function getById($authId) {
		assert('is_string($authId)');

		/* For now - load and parse config file. */
		$globalConfig = SimpleSAML_Configuration::getInstance();
		$config = $globalConfig->copyFromBase('authsources', 'authsources.php');

		$authConfig = $config->getValue($authId, NULL);
		if ($authConfig === NULL) {
			return NULL;
		}

		if (!is_array($authConfig)) {
			throw new Exception('Invalid configuration for authentication source \'' . $authId . '\'.');
		}

		return self::parseAuthSource($authId, $authConfig);
	}

}

?>