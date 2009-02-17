<?php

/**
 * Store consent in cookies.
 *
 * This class implements a consent store which stores the consent information in
 * cookies on the users computer.
 *
 * Example - Consent module with cookie store:
 * <code>
 * 'authproc' => array(
 *   array(
 *     'consent:Consent',
 *     'store' => 'consent:Cookie',
 *     ),
 *   ),
 * </code>
 *
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_consent_Consent_Store_Cookie extends sspmod_consent_Store {


	/**
	 * Check for consent.
	 *
	 * This function checks whether a given user has authorized the release of the attributes
	 * identified by $attributeSet from $source to $destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 * @param string $attributeSet  A hash which identifies the attributes.
	 * @return bool  TRUE if the user has given consent earlier, FALSE if not (or on error).
	 */
	public function hasConsent($userId, $destinationId, $attributeSet) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');
		assert('is_string($attributeSet)');

		$cookieName = self::getCookieName($userId, $destinationId);
		
		$data = $userId . ':' . $attributeSet . ':' . $destinationId;
		
		SimpleSAML_Logger::debug('Consent cookie - Get [' . $data . ']');

		if (!array_key_exists($cookieName, $_COOKIE)) {
			SimpleSAML_Logger::debug('Consent cookie - no cookie with name \'' . $cookieName . '\'.');
			return FALSE;
		}
		if (!is_string($_COOKIE[$cookieName])) {
			SimpleSAML_Logger::warning('Value of consent cookie wasn\'t a string. Was: ' . var_export($_COOKIE[$cookieName], TRUE));
			return FALSE;
		}


		
		$data = self::sign($data);

		if ($_COOKIE[$cookieName] !== $data) {
			SimpleSAML_Logger::info('Attribute set changed from the last time consent was given.');
			return FALSE;
		}

		SimpleSAML_Logger::debug('Consent cookie - found cookie with correct name and value.');

		return TRUE;
	}


	/**
	 * Save consent.
	 *
	 * Called when the user asks for the consent to be saved. If consent information
	 * for the given user and destination already exists, it should be overwritten.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 * @param string $attributeSet  A hash which identifies the attributes.
	 */
	public function saveConsent($userId, $destinationId, $attributeSet) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');
		assert('is_string($attributeSet)');

		$name = self::getCookieName($userId, $destinationId);
		$value = $userId . ':' . $attributeSet . ':' . $destinationId;
		
		SimpleSAML_Logger::debug('Consent cookie - Set [' . $value . ']');
		
		$value = self::sign($value);
		$this->setConsentCookie($name, $value);
	}


	/**
	 * Delete consent.
	 *
	 * Called when a user revokes consent for a given destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 */
	public function deleteConsent($userId, $destinationId) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');

		$name = self::getCookieName($userId, $destinationId);
		$this->setConsentCookie($name, NULL);

	}
	
	/**
	 * Delete consent.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 */
	public function deleteAllConsents($userId) {
		assert('is_string($userId)');

		throw new Exception('The cookie consent handler does not support to delete all consents...');
	}


	/**
	 * Retrieve consents.
	 *
	 * This function should return a list of consents the user has saved.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @return array  Array of all destination ids the user has given consent for.
	 */
	public function getConsents($userId) {
		assert('is_string($userId)');

		$ret = array();

		$cookieNameStart = 'sspmod_consent:';
		$cookieNameStartLen = strlen($cookieNameStart);
		foreach ($_COOKIE as $name => $value) {

			if (substr($name, 0, $cookieNameStartLen) !== $cookieNameStart) {
				continue;
			}

			$value = self::verify($value);
			if ($value === FALSE) {
				continue;
			}

			$tmp = explode(':', $value, 3);
			if (count($tmp) !== 3) {
				SimpleSAML_Logger::warning('Consent cookie with invalid value: ' . $value);
				continue;
			}

			if ($userId !== $tmp[0]) {
				/* Wrong user. */
				continue;
			}

			$destination = $tmp[2];


			$ret[] = $destination;
		}

		return $ret;
	}


	/**
	 * Calculate a signature of some data.
	 *
	 * This function calculates a signature of the data.
	 *
	 * @param string $data  The data which should be signed.
	 * @return string  The signed data.
	 */
	private static function sign($data) {
		assert('is_string($data)');

		$secretSalt = SimpleSAML_Utilities::getSecretSalt();

		return sha1($secretSalt . $data . $secretSalt) . ':' . $data;
	}


	/**
	 * Verify signed data.
	 *
	 * This function verifies signed data.
	 *
	 * @param string $signedData  The data which is signed.
	 * @return string|FALSE  The data, or FALSE if the signature is invalid.
	 */
	private static function verify($signedData) {
		assert('is_string($signedData)');

		$data = explode(':', $signedData, 2);
		if (count($data) !== 2) {
			SimpleSAML_Logger::warning('Consent cookie: Missing signature.');
			return FALSE;
		}
		$data = $data[1];

		$newSignedData = self::sign($data);
		if ($newSignedData !== $signedData) {
			SimpleSAML_Logger::warning('Consent cookie: Invalid signature.');
			return FALSE;
		}

		return $data;
	}


	/**
	 * Get cookie name.
	 *
	 * This function gets the cookie name for the given user & destination.
	 *
	 * @param string $userId  The hash identifying the user at an IdP.
	 * @param string $destinationId  A string which identifies the destination.
	 */
	private static function getCookieName($userId, $destinationId) {
		assert('is_string($userId)');
		assert('is_string($destinationId)');

		return 'sspmod_consent:' . sha1($userId . ':' . $destinationId);
	}


	/**
	 * Helper function for setting a cookie.
	 *
	 * @param string $name  Name of the cookie.
	 * @param string|NULL $value  Value of the cookie. Set this to NULL to delete the cookie.
	 */
	private function setConsentCookie($name, $value) {
		assert('is_string($name)');
		assert('is_string($value)');

		if ($value === NULL) {
			$expire = 1; /* Delete by setting expiry in the past. */
			$value = '';
		} else {
			$expire = time() + 90 * 24*60*60;
		}

		if (SimpleSAML_Utilities::isHTTPS()) {
			/* Enable secure cookie for https-requests. */
			$secure = TRUE;
		} else {
			$secure = FALSE;
		}

		$globalConfig = SimpleSAML_Configuration::getInstance();
		$path = '/' . $globalConfig->getBaseURL();

		setcookie($name, $value, $expire, $path, NULL, $secure);
	}

}

?>