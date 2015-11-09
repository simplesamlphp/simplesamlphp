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
 * @package SimpleSAMLphp
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
	 * The key of the OrgId field in the state, identifies which org was selected.
	 */
	const ORGID = 'sspmod_core_Auth_UserPassOrgBase.SelectedOrg';


	/**
	 * What way do we handle the organization as part of the username.
	 * Three values:
	 *  'none': Force the user to select the correct organization from the dropdown box.
	 *  'allow': Allow the user to enter the organization as part of the username.
	 *  'force': Remove the dropdown box.
	 */
	private $usernameOrgMethod;

	/**
	 * Storage for authsource config option remember.username.enabled
	 * loginuserpass.php and loginuserpassorg.php pages/templates use this option to
	 * present users with a checkbox to save their username for the next login request.
	 * @var bool
	 */
	protected $rememberUsernameEnabled = FALSE;

	/**
	 * Storage for authsource config option remember.username.checked
	 * loginuserpass.php and loginuserpassorg.php pages/templates use this option
	 * to default the remember username checkbox to checked or not.
	 * @var bool
	 */
	protected $rememberUsernameChecked = FALSE;


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

		// Call the parent constructor first, as required by the interface
		parent::__construct($info, $config);

		// Get the remember username config options
		if (isset($config['remember.username.enabled'])) {
			$this->rememberUsernameEnabled = (bool) $config['remember.username.enabled'];
			unset($config['remember.username.enabled']);
		}
		if (isset($config['remember.username.checked'])) {
			$this->rememberUsernameChecked = (bool) $config['remember.username.checked'];
			unset($config['remember.username.checked']);
		}

		$this->usernameOrgMethod = 'none';
	}


	/**
	 * Configure the way organizations as part of the username is handled.
	 *
	 * There are three possible values:
	 * - 'none': Force the user to select the correct organization from the dropdown box.
	 * - 'allow': Allow the user to enter the organization as part of the username.
	 * - 'force': Remove the dropdown box.
	 *
	 * If unconfigured, the default is 'none'.
	 *
	 * @param string $usernameOrgMethod  The method which should be used.
	 */
	protected function setUsernameOrgMethod($usernameOrgMethod) {
		assert('in_array($usernameOrgMethod, array("none", "allow", "force"), TRUE)');

		$this->usernameOrgMethod = $usernameOrgMethod;
	}


	/**
	 * Retrieve the way organizations as part of the username should be handled.
	 *
	 * There are three possible values:
	 * - 'none': Force the user to select the correct organization from the dropdown box.
	 * - 'allow': Allow the user to enter the organization as part of the username.
	 * - 'force': Remove the dropdown box.
	 *
	 * @return string  The method which should be used.
	 */
	public function getUsernameOrgMethod() {
		return $this->usernameOrgMethod;
	}

	/**
	 * Getter for the authsource config option remember.username.enabled
	 * @return bool
	 */
	public function getRememberUsernameEnabled() {
		return $this->rememberUsernameEnabled;
	}

	/**
	 * Getter for the authsource config option remember.username.checked
	 * @return bool
	 */
	public function getRememberUsernameChecked() {
		return $this->rememberUsernameChecked;
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

		// We are going to need the authId in order to retrieve this authentication source later
		$state[self::AUTHID] = $this->authId;

		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);

		$url = SimpleSAML_Module::getModuleURL('core/loginuserpassorg.php');
		$params = array('AuthState' => $id);
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, $params);
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
	 * username/password failure, and other errors, it will throw an exception.
	 *
	 * @param string $authStateId  The identifier of the authentication state.
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @param string $organization  The id of the organization the user chose.
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

		$orgMethod = $source->getUsernameOrgMethod();
		if ($orgMethod !== 'none') {
			$tmp = explode('@', $username, 2);
			if (count($tmp) === 2) {
				$username = $tmp[0];
				$organization = $tmp[1];
			} else {
				if ($orgMethod === 'force') {
					/* The organization should be a part of the username, but isn't. */
					throw new SimpleSAML_Error_Error('WRONGUSERPASS');
				}
			}
		}

		/* Attempt to log in. */
		$attributes = $source->login($username, $password, $organization);

		// Add the selected Org to the state
		$state[self::ORGID] = $organization;
		$state['PersistentAuthData'][] = self::ORGID;

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);
	}


	/**
	 * Get available organizations.
	 *
	 * This function is used by the login form to get the available organizations.
	 *
	 * @param string $authStateId  The identifier of the authentication state.
	 * @return array|NULL  Array of organizations. NULL if the user must enter the
	 *         organization as part of the username.
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

		$orgMethod = $source->getUsernameOrgMethod();
		if ($orgMethod === 'force') {
			return NULL;
		}

		return $source->getOrganizations();
	}
}
