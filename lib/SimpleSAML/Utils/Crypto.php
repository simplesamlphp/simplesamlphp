<?php


/**
 * A class for cryptography-related functions
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_Crypto
{

    /**
     * This function hashes a password with a given algorithm.
     *
     * @param string $password The password to hash.
     * @param string $algorithm The hashing algorithm, uppercase, optionally prepended with 'S' (salted). See
     *     hash_algos() for a complete list of hashing algorithms.
     * @param string $salt An optional salt to use.
     *
     * @return string The hashed password.
     * @throws SimpleSAML_Error_Exception If the algorithm specified is not supported, or the input parameters are not
     *     strings.
     * @see hash_algos()
     * @author Dyonisius Visser, TERENA <visser@terena.org>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function pwHash($password, $algorithm, $salt = null)
    {
        if (!is_string($algorithm) || !is_string($password)) {
            throw new SimpleSAML_Error_Exception('Invalid input parameters.');
        }

        // hash w/o salt
        if (in_array(strtolower($algorithm), hash_algos())) {
            $alg_str = '{'.str_replace('SHA1', 'SHA', $algorithm).'}'; // LDAP compatibility
            $hash = hash(strtolower($algorithm), $password, true);
            return $alg_str.base64_encode($hash);
        }

        // hash w/ salt
        if (!$salt) { // no salt provided, generate one
            // default 8 byte salt, but 4 byte for LDAP SHA1 hashes
            $bytes = ($algorithm == 'SSHA1') ? 4 : 8;
            $salt = SimpleSAML_Utilities::generateRandomBytes($bytes);
        }

        if ($algorithm[0] == 'S' && in_array(substr(strtolower($algorithm), 1), hash_algos())) {
            $alg = substr(strtolower($algorithm), 1); // 'sha256' etc
            $alg_str = '{'.str_replace('SSHA1', 'SSHA', $algorithm).'}'; // LDAP compatibility
            $hash = hash($alg, $password.$salt, true);
            return $alg_str.base64_encode($hash.$salt);
        }

        throw new SimpleSAML_Error_Exception('Hashing algorithm \''.strtolower($algorithm).'\' is not supported');
    }


    /**
     * This function checks if a password is valid
     *
     * @param string $hash The password as it appears in password file, optionally prepended with algorithm.
     * @param string $password The password to check in clear.
     *
     * @return boolean True if the hash corresponds with the given password, false otherwise.
     * @throws SimpleSAML_Error_Exception If the algorithm specified is not supported, or the input parameters are not
     *     strings.
     * @author Dyonisius Visser, TERENA <visser@terena.org>
     */
    public static function pwValid($hash, $password)
    {
        if (!is_string($hash) || !is_string($password)) {
            throw new SimpleSAML_Error_Exception('Invalid input parameters.');
        }

        // match algorithm string (e.g. '{SSHA256}', '{MD5}')
        if (preg_match('/^{(.*?)}(.*)$/', $hash, $matches)) {

            // LDAP compatibility
            $alg = preg_replace('/^(S?SHA)$/', '${1}1', $matches[1]);

            // hash w/o salt
            if (in_array(strtolower($alg), hash_algos())) {
                return $hash === self::pwHash($password, $alg);
            }

            // hash w/ salt
            if ($alg[0] === 'S' && in_array(substr(strtolower($alg), 1), hash_algos())) {
                $php_alg = substr(strtolower($alg), 1);

                // get hash length of this algorithm to learn how long the salt is
                $hash_length = strlen(hash($php_alg, '', true));
                $salt = substr(base64_decode($matches[2]), $hash_length);
                return ($hash === self::pwHash($password, $alg, $salt));
            }
        } else {
            return $hash === $password;
        }

        throw new SimpleSAML_Error_Exception('Hashing algorithm \''.strtolower($alg).'\' is not supported');
    }
}
