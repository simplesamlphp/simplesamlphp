<?php

/*
 * Copyright (C) 2009  Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * Copyright (C) 2009  Simon Josefsson <simon@yubico.com>.
 *
 * This file is part of simpleSAMLphp
 *
 * simpleSAMLphp is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public License
 * as published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * simpleSAMLphp is distributed in the hope that it will be useful,
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
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_authYubiKey_Auth_Source_YubiKey extends sspmod_core_Auth_UserPassBase {

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

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		if (array_key_exists('id', $config)) {
			$this->yubi_id = $config['id'];
		}

		if (array_key_exists('key', $config)) {
			$this->yubi_key = $config['key'];
		}
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
	protected function login($username, $password) {
		assert('is_string($username)');
		assert('is_string($password)');

		require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/libextinc/Yubico.php';

		$attributes = array();

		try {
			$yubi = &new Auth_Yubico($this->yubi_id, $this->yubi_key);
			$auth = $yubi->verify($password);
			
			$attributes = array('uid' => array($username), 'otp' => array($password));
		} catch (Exception $e) {
		  	SimpleSAML_Logger::info('YubiKey:' . $this->authId . ': Validation error (user ' . $username . ' otp ' . $password . '), debug output: ' . $yubi->getLastResponse());

			throw new SimpleSAML_Error_Error('WRONGUSERPASS', $e);
		}

		SimpleSAML_Logger::info('YubiKey:' . $this->authId . ': YubiKey otp ' . $password . ' for user ' . $username . ' validated successfully: ' . $yubi->getLastResponse());

		return $attributes;
	}

}

?>
