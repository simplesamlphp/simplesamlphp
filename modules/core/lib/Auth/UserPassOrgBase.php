<?php

/**
 * Helper class for username/password/organization authentication.
 *
 * This helper class allows for implementations of username/password/organization
 * authentication by implementing two functions:
 * - login($username, $password, $organization)
 * - getOrganizations()
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
abstract class sspmod_core_Auth_UserPassOrgBase extends SimpleSAML_Auth_Source {


	/**
	 * The string used to identify our states.
	 */
	const STAGEID = 'sspmod_core_Auth_UserPassOrgBase.state';


	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'sspmod_core_Auth_UserPassOrgBase.AuthId';


	/**
	 * Constructor for this authentication source.
	 *
	 * All subclasses who implement their own constructor must call this constructor before
	 * using $config for anything.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array &$config  Configuration for this authentication source.
	 */
	public function __construct($info, &$config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);
	}


	/**
	 * Initialize login.
	 *
	 * This function saves the information about the login, and redirects to a
	 * login page.
	 *
	 * @param array &$state  Information about the current authentication.
	 */
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);

		$url = SimpleSAML_Module::getModuleURL('core/loginuserpassorg.php');
		$params = array('AuthState' => $id);
		SimpleSAML_Utilities::redirect($url, $params);
	}


	/**
	 * Attempt to log in using the given username, password and organization.
	 *
	 * On a successful login, this function should return the users attributes. On failure,
	 * it should throw an exception/error. If the error was caused by the user entering the wrong
	 * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
	 *
	 * Note that both the username and the password are UTF-8 encoded.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @param string $organization  The id of the organization the user chose.
	 * @return array  Associative array with the user's attributes.
	 */
	abstract protected function login($username, $password, $organization);


	/**
	 * Retrieve list of organizations.
	 *
	 * The list of organizations is an associative array. The key of the array is the
	 * id of the organization, and the value is the description. The value can be another
	 * array, in which case that array is expected to contain language-code to
	 * description mappings.
	 *
	 * @return array  Associative array with the organizations.
	 */
	abstract protected function getOrganizations();


	/**
	 * Handle login request.
	 *
	 * This function is used by the login form (core/www/loginuserpassorg.php) when the user
	 * enters a username and password. On success, it will not return. On wrong
	 * username/password failure, it will return the error code. Other failures will throw an
	 * exception.
	 *
	 * @param string $authStateId  The identifier of the authentication state.
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @param string $organization  The id of the organization the user chose.
	 * @return string Error code in the case of an error.
	 */
	public static function handleLogin($authStateId, $username, $password, $organization) {
		assert('is_string($authStateId)');
		assert('is_string($username)');
		assert('is_string($password)');
		assert('is_string($organization)');

		/* Retrieve the authentication state. */
		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

		/* Find authentication source. */
		assert('array_key_exists(self::AUTHID, $state)');
		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}


		try {
			/* Attempt to log in. */
			$attributes = $source->login($username, $password, $organization);
		} catch (SimpleSAML_Error_Error $e) {
			/* An error occured during login. Check if it is because of the wrong
			 * username/password - if it is, we pass that error up to the login form,
			 * if not, we let the generic error handler deal with it.
			 */
			if ($e->getErrorCode() === 'WRONGUSERPASS') {
				return 'WRONGUSERPASS';
			}

			/* Some other error occured. Rethrow exception and let the generic error
			 * handler deal with it.
			 */
			throw $e;
		}

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);
	}


	/**
	 * Get available organizations.
	 *
	 * This function is used by the login form to get the available organizations.
	 *
	 * @param string $authStateId  The identifier of the authentication state.
	 * @return array  Array of organizations.
	 */
	public static function listOrganizations($authStateId) {
		assert('is_string($authStateId)');

		/* Retrieve the authentication state. */
		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);

		/* Find authentication source. */
		assert('array_key_exists(self::AUTHID, $state)');
		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}

		return $source->getOrganizations();
	}
}

?>