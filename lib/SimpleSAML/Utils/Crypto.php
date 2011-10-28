<?
/**
 * A class for crypto related functions
 *
 * @author Dyonisius Visser, TERENA. <visser@terena.org>
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Utils_Crypto {

	/**
	 * This function generates a password hash
	 * @param $password  The unencrypted password
	 * @param $algo      The hashing algorithm, capitals, optionally prepended with 'S' (salted)
	 * @param $salt      Optional salt
	 */
	public static function pwHash($password, $algo, $salt = NULL) {
		assert('is_string($algo)');
		assert('is_string($password)');

		if(in_array(strtolower($algo), hash_algos())) {
			$php_algo = strtolower($algo); // 'sha256' etc
			// LDAP compatibility
			return '{' . preg_replace('/^SHA1$/', 'SHA', $algo) . '}'
				.base64_encode(hash($php_algo, $password, TRUE));
		}

		// Salt
		if(!$salt) {
			// Default 8 byte salt, but 4 byte for LDAP SHA1 hashes
			$bytes = ($algo == 'SSHA1') ? 4 : 8;
			$salt = SimpleSAML_Utilities::generateRandomBytes($bytes, TRUE);
		}

		if($algo[0] == 'S' && in_array(substr(strtolower($algo),1), hash_algos())) {
			$php_algo = substr(strtolower($algo),1); // 'sha256' etc
			// Salted hash, with LDAP compatibility
			return '{' . preg_replace('/^SSHA1$/', 'SSHA', $algo) . '}' .
				base64_encode(hash($php_algo, $password.$salt, TRUE) . $salt);
		}

		throw new Exception('Hashing algoritm \'' . strtolower($algo) . '\' not supported');

	}


	/**
	 * This function checks if a password is valid
	 * @param $crypted  Password as appears in password file, optionally prepended with algorithm
	 * @param $clear    Password to check
	 */
	public static function pwValid($crypted, $clear) {
		assert('is_string($crypted)');
		assert('is_string($clear)');

		// Match algorithm string ('{SSHA256}', '{MD5}')
		if(preg_match('/^{(.*?)}(.*)$/', $crypted, $matches)) {

			// LDAP compatibility
			$algo = preg_replace('/^(S?SHA)$/', '${1}1', $matches[1]);

			$cryptedpw =  $matches[2];

			if(in_array(strtolower($algo), hash_algos())) {
				// Unsalted hash
				return ( $crypted == self::pwHash($clear, $algo) );
			}

			if($algo[0] == 'S' && in_array(substr(strtolower($algo),1), hash_algos())) {
				$php_algo = substr(strtolower($algo),1);
				// Salted hash
				$hash_length = strlen(hash($php_algo, 'whatever', TRUE));
				$salt = substr(base64_decode($cryptedpw), $hash_length);
				return ( $crypted == self::pwHash($clear, $algo, $salt) );
			}

			throw new Exception('Hashing algoritm \'' . strtolower($algo) . '\' not supported');

		} else {
			return $crypted === $clear;
		}
	}
}
