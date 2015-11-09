<?php

/*
 * Copyright (C) 2009  Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * Copyright (C) 2009  Simon Josefsson <simon@yubico.com>.
 *
 * This file is part of SimpleSAMLphp
 *
 * SimpleSAMLphp is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * SimpleSAMLphp is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License License along with GNU SASL Library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA 02110-1301, USA.
 *
 */

/**
 * YubiKey authentication module, see http://www.yubico.com/developers/intro/
 * *
 * Configure it by adding an entry to config/authsources.php such as this:
 *
 *	'yubikey' => array(
 *		  'authYubiKey:YubiKey',
 *		  'id' => 997,
 *		  'key' => 'b64hmackey',
 *		  ),
 *
 * To generate your own client id/key you will need one YubiKey, and then
 * go to http://yubico.com/developers/api/
 *
 * @package SimpleSAMLphp
 */
class sspmod_authYubiKey_Auth_Source_YubiKey extends SimpleSAML_Auth_Source {

	/**
	 * The string used to identify our states.
	 */
	const STAGEID = 'sspmod_authYubiKey_Auth_Source_YubiKey.state';

	/**
	 * The number of characters of the OTP that is the secure token.
	 * The rest is the user id.
	 */
	const TOKENSIZE = 32;

	/**
	 * The key of the AuthId field in the state.
	 */
	const AUTHID = 'sspmod_authYubiKey_Auth_Source_YubiKey.AuthId';

	/**
	 * The client id/key for use with the Auth_Yubico PHP module.
	 */
	private $yubi_id;
	private $yubi_key;

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		// Call the parent constructor first, as required by the interface
		parent::__construct($info, $config);

		if (array_key_exists('id', $config)) {
			$this->yubi_id = $config['id'];
		}

		if (array_key_exists('key', $config)) {
			$this->yubi_key = $config['key'];
		}
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

		$url = SimpleSAML_Module::getModuleURL('authYubiKey/yubikeylogin.php');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('AuthState' => $id));
	}
	
	
	/**
	 * Handle login request.
	 *
	 * This function is used by the login form (core/www/loginuserpass.php) when the user
	 * enters a username and password. On success, it will not return. On wrong
	 * username/password failure, it will return the error code. Other failures will throw an
	 * exception.
	 *
	 * @param string $authStateId  The identifier of the authentication state.
	 * @param string $otp  The one time password entered-
	 * @return string  Error code in the case of an error.
	 */
	public static function handleLogin($authStateId, $otp) {
		assert('is_string($authStateId)');
		assert('is_string($otp)');

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
			$attributes = $source->login($otp);
		} catch (SimpleSAML_Error_Error $e) {
			/* An error occurred during login. Check if it is because of the wrong
			 * username/password - if it is, we pass that error up to the login form,
			 * if not, we let the generic error handler deal with it.
			 */
			if ($e->getErrorCode() === 'WRONGUSERPASS') {
				return 'WRONGUSERPASS';
			}

			/* Some other error occurred. Rethrow exception and let the generic error
			 * handler deal with it.
			 */
			throw $e;
		}

		$state['Attributes'] = $attributes;
		SimpleSAML_Auth_Source::completeAuth($state);
	}
	
	/**
	 * Return the user id part of a one time passord
	 */
	public static function getYubiKeyPrefix($otp) {
		$uid = substr ($otp, 0, strlen ($otp) - self::TOKENSIZE);
		return $uid;
	}

	/**
	 * Attempt to log in using the given username and password.
	 *
	 * On a successful login, this function should return the users attributes. On failure,
	 * it should throw an exception. If the error was caused by the user entering the wrong
	 * username or password, a SimpleSAML_Error_Error('WRONGUSERPASS') should be thrown.
	 *
	 * Note that both the username and the password are UTF-8 encoded.
	 *
	 * @param string $username  The username the user wrote.
	 * @param string $password  The password the user wrote.
	 * @return array  Associative array with the users attributes.
	 */
	protected function login($otp) {
		assert('is_string($otp)');

		require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/libextinc/Yubico.php';

		$attributes = array();

		try {
			$yubi = new Auth_Yubico($this->yubi_id, $this->yubi_key);
			$auth = $yubi->verify($otp);
			$uid = self::getYubiKeyPrefix($otp);
			$attributes = array('uid' => array($uid));
		} catch (Exception $e) {
		  	SimpleSAML_Logger::info('YubiKey:' . $this->authId . ': Validation error (otp ' . $otp . '), debug output: ' . $yubi->getLastResponse());

			throw new SimpleSAML_Error_Error('WRONGUSERPASS', $e);
		}

		SimpleSAML_Logger::info('YubiKey:' . $this->authId . ': YubiKey otp ' . $otp . ' validated successfully: ' . $yubi->getLastResponse());

		return $attributes;
	}

}
