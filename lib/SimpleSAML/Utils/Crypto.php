<?php


/**
 * A class for cryptography-related functions
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_Crypto
{

    /**
     * Decrypt data using AES and the system-wide secret salt as key.
     *
     * @param string $data The encrypted data to decrypt.
     *
     * @return string The decrypted data.
     * @throws SimpleSAML_Error_Exception If the mcrypt module is not loaded or $ciphertext is not a string.
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function aesDecrypt($ciphertext)
    {
        if (!is_string($ciphertext)) {
            throw new SimpleSAML_Error_Exception('Input parameter "$ciphertext" must be a string.');
        }
        if (!function_exists("mcrypt_encrypt")) {
            throw new SimpleSAML_Error_Exception("The mcrypt PHP module is not loaded.");
        }

        $enc = MCRYPT_RIJNDAEL_256;
        $mode = MCRYPT_MODE_CBC;

        $ivSize = mcrypt_get_iv_size($enc, $mode);
        $keySize = mcrypt_get_key_size($enc, $mode);

        $key = hash('sha256', SimpleSAML_Utilities::getSecretSalt(), true);
        $key = substr($key, 0, $keySize);

        $iv = substr($ciphertext, 0, $ivSize);
        $data = substr($ciphertext, $ivSize);

        $clear = mcrypt_decrypt($enc, $key, $data, $mode, $iv);

        $len = strlen($clear);
        $numpad = ord($clear[$len - 1]);
        $clear = substr($clear, 0, $len - $numpad);

        return $clear;
    }

    /**
     * Encrypt data using AES and the system-wide secret salt as key.
     *
     * @param string $data The data to encrypt.
     *
     * @return string The encrypted data and IV.
     * @throws SimpleSAML_Error_Exception If the mcrypt module is not loaded or $data is not a string.
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function aesEncrypt($data)
    {
        if (!is_string($data)) {
            throw new SimpleSAML_Error_Exception('Input parameter "$data" must be a string.');
        }
        if (!function_exists("mcrypt_encrypt")) {
            throw new SimpleSAML_Error_Exception('The mcrypt PHP module is not loaded.');
        }

        $enc = MCRYPT_RIJNDAEL_256;
        $mode = MCRYPT_MODE_CBC;

        $blockSize = mcrypt_get_block_size($enc, $mode);
        $ivSize = mcrypt_get_iv_size($enc, $mode);
        $keySize = mcrypt_get_key_size($enc, $mode);

        $key = hash('sha256', SimpleSAML_Utilities::getSecretSalt(), true);
        $key = substr($key, 0, $keySize);

        $len = strlen($data);
        $numpad = $blockSize - ($len % $blockSize);
        $data = str_pad($data, $len + $numpad, chr($numpad));

        $iv = SimpleSAML_Utilities::generateRandomBytes($ivSize);

        $data = mcrypt_encrypt($enc, $key, $data, $mode, $iv);

        return $iv.$data;
    }

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
