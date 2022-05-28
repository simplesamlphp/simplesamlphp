<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use SimpleSAML\Assert\Assert;
use InvalidArgumentException;
use SimpleSAML\Configuration;
use SimpleSAML\Error;

/**
 * A class for cryptography-related functions.
 *
 * @package SimpleSAMLphp
 */

class Crypto
{
    /**
     * Decrypt data using AES-256-CBC and the key provided as a parameter.
     *
     * @param string $ciphertext The HMAC of the encrypted data, the IV used and the encrypted data, concatenated.
     * @param string $secret The secret to use to decrypt the data.
     *
     * @return string The decrypted data.
     * @throws \InvalidArgumentException If $ciphertext is not a string.
     * @throws Error\Exception If the openssl module is not loaded.
     *
     * @see \SimpleSAML\Utils\Crypto::aesDecrypt()
     */
    private function aesDecryptInternal(string $ciphertext, string $secret): string
    {
        if (!extension_loaded('openssl')) {
            throw new Error\Exception("The openssl PHP module is not loaded.");
        }

        $len = mb_strlen($ciphertext, '8bit');
        if ($len < 48) {
            throw new InvalidArgumentException(
                'Input parameter "$ciphertext" must be a string with more than 48 characters.'
            );
        }

        // derive encryption and authentication keys from the secret
        $key  = openssl_digest($secret, 'sha512');

        $hmac = mb_substr($ciphertext, 0, 32, '8bit');
        $iv   = mb_substr($ciphertext, 32, 16, '8bit');
        $msg  = mb_substr($ciphertext, 48, $len - 48, '8bit');

        // authenticate the ciphertext
        if ($this->secureCompare(hash_hmac('sha256', $iv . $msg, substr($key, 64, 64), true), $hmac)) {
            $plaintext = openssl_decrypt(
                $msg,
                'AES-256-CBC',
                substr($key, 0, 64),
                defined('OPENSSL_RAW_DATA') ? OPENSSL_RAW_DATA : 1,
                $iv
            );

            if ($plaintext !== false) {
                return $plaintext;
            }
        }

        throw new Error\Exception("Failed to decrypt ciphertext.");
    }


    /**
     * Decrypt data using AES-256-CBC and the system-wide secret salt as key.
     *
     * @param string $ciphertext The HMAC of the encrypted data, the IV used and the encrypted data, concatenated.
     * @param string $secret The secret to use to decrypt the data.
     *                       If not provided, the secret salt from the configuration will be used
     *
     * @return string The decrypted data.
     * @throws \InvalidArgumentException If $ciphertext is not a string.
     * @throws Error\Exception If the openssl module is not loaded.
     *
     */
    public function aesDecrypt(string $ciphertext, string $secret = null): string
    {
        if ($secret === null) {
            $configUtils = new Config();
            $secret = $configUtils->getSecretSalt();
        }

        return $this->aesDecryptInternal($ciphertext, $secret);
    }


    /**
     * Encrypt data using AES-256-CBC and the key provided as a parameter.
     *
     * @param string $data The data to encrypt.
     * @param string $secret The secret to use to encrypt the data.
     *
     * @return string An HMAC of the encrypted data, the IV and the encrypted data, concatenated.
     * @throws \InvalidArgumentException If $data is not a string.
     * @throws Error\Exception If the openssl module is not loaded.
     *
     * @see \SimpleSAML\Utils\Crypto::aesEncrypt()
     */
    private function aesEncryptInternal(string $data, string $secret): string
    {
        if (!extension_loaded('openssl')) {
            throw new Error\Exception('The openssl PHP module is not loaded.');
        }

        // derive encryption and authentication keys from the secret
        $key = openssl_digest($secret, 'sha512');

        // generate a random IV
        $iv = openssl_random_pseudo_bytes(16);

        // encrypt the message
        $ciphertext = openssl_encrypt(
            $data,
            'AES-256-CBC',
            substr($key, 0, 64),
            defined('OPENSSL_RAW_DATA') ? OPENSSL_RAW_DATA : 1,
            $iv
        );

        if ($ciphertext === false) {
            throw new Error\Exception("Failed to encrypt plaintext.");
        }

        // return the ciphertext with proper authentication
        return hash_hmac('sha256', $iv . $ciphertext, substr($key, 64, 64), true) . $iv . $ciphertext;
    }


    /**
     * Encrypt data using AES-256-CBC and the system-wide secret salt as key.
     *
     * @param string $data The data to encrypt.
     * @param string $secret The secret to use to decrypt the data.
     *                       If not provided, the secret salt from the configuration will be used
     *
     * @return string An HMAC of the encrypted data, the IV and the encrypted data, concatenated.
     * @throws \InvalidArgumentException If $data is not a string.
     * @throws Error\Exception If the openssl module is not loaded.
     *
     */
    public function aesEncrypt(string $data, string $secret = null): string
    {
        if ($secret === null) {
            $configUtils = new Config();
            $secret = $configUtils->getSecretSalt();
        }

        return $this->aesEncryptInternal($data, $secret);
    }


    /**
     * Convert data from DER to PEM encoding.
     *
     * @param string $der Data encoded in DER format.
     * @param string $type The type of data we are encoding, as expressed by the PEM header. Defaults to "CERTIFICATE".
     * @return string The same data encoded in PEM format.
     * @see RFC7648 for known types and PEM format specifics.
     */
    public function der2pem(string $der, string $type = 'CERTIFICATE'): string
    {
        return "-----BEGIN " . $type . "-----\n" .
            chunk_split(base64_encode($der), 64, "\n") .
            "-----END " . $type . "-----\n";
    }


    /**
     * Load a private key from metadata.
     *
     * This function loads a private key from a metadata array. It looks for the following elements:
     * - 'privatekey': Name of a private key file in the cert-directory.
     * - 'privatekey_pass': Password for the private key.
     *
     * It returns an array with the following elements:
     * - 'PEM': Data for the private key, in PEM-format.
     * - 'password': Password for the private key.
     *
     * @param \SimpleSAML\Configuration $metadata The metadata array the private key should be loaded from.
     * @param bool                      $required Whether the private key is required. If this is true, a
     * missing key will cause an exception. Defaults to false.
     * @param string                    $prefix The prefix which should be used when reading from the metadata
     * array. Defaults to ''.
     * @param bool                      $full_path Whether the filename found in the configuration contains the
     * full path to the private key or not. Default to false.
     *
     * @return array|NULL Extracted private key, or NULL if no private key is present.
     * @throws \InvalidArgumentException If $required is not boolean or $prefix is not a string.
     * @throws Error\Exception If no private key is found in the metadata, or it was not possible to load
     *     it.
     *
     */
    public function loadPrivateKey(
        Configuration $metadata,
        bool $required = false,
        string $prefix = '',
        bool $full_path = false
    ): ?array {
        $file = $metadata->getOptionalString($prefix . 'privatekey', null);
        if ($file === null) {
            // no private key found
            if ($required) {
                throw new Error\Exception('No private key found in metadata.');
            } else {
                return null;
            }
        }

        if (!$full_path) {
            $configUtils = new Config();
            $file = $configUtils->getCertPath($file);
        }

        $data = @file_get_contents($file);
        if ($data === false) {
            throw new Error\Exception('Unable to load private key from file "' . $file . '"');
        }

        $ret = [
            'PEM' => $data,
            'password' => $metadata->getOptionalString($prefix . 'privatekey_pass', null),
        ];

        return $ret;
    }


    /**
     * Get public key or certificate from metadata.
     *
     * This function implements a function to retrieve the public key or certificate from a metadata array.
     *
     * It will search for the following elements in the metadata:
     * - 'certData': The certificate as a base64-encoded string.
     * - 'certificate': A file with a certificate or public key in PEM-format.
     *
     * This function will return an array with these elements:
     * - 'PEM': The public key/certificate in PEM-encoding.
     * - 'certData': The certificate data, base64 encoded, on a single line. (Only present if this is a certificate.)
     *
     * @param \SimpleSAML\Configuration $metadata The metadata.
     * @param bool                      $required Whether the public key is required. If this is TRUE, a missing key
     *     will cause an exception. Default is FALSE.
     * @param string                    $prefix The prefix which should be used when reading from the metadata array.
     *     Defaults to ''.
     *
     * @return array|NULL Public key or certificate data, or NULL if no public key or certificate was found.
     * @throws \InvalidArgumentException If $metadata is not an instance of \SimpleSAML\Configuration, $required is not
     *     boolean or $prefix is not a string.
     * @throws Error\Exception If no public key is found in the metadata, or it was not possible to load
     *     it.
     *
     */
    public function loadPublicKey(Configuration $metadata, bool $required = false, string $prefix = ''): ?array
    {
        $keys = $metadata->getPublicKeys(null, false, $prefix);
        if (!empty($keys)) {
            foreach ($keys as $key) {
                if ($key['type'] !== 'X509Certificate') {
                    continue;
                }
                if ($key['signing'] !== true) {
                    continue;
                }
                $certData = $key['X509Certificate'];
                $pem = "-----BEGIN CERTIFICATE-----\n" .
                    chunk_split($certData, 64) .
                    "-----END CERTIFICATE-----\n";
                return [
                    'name'            => $key['name'] ?? null,
                    'certData'        => $certData,
                    'PEM'             => $pem,
                ];
            }
            // no valid key found
        }

        // no public key/certificate available
        if ($required) {
            throw new Error\Exception('No public key / certificate found in metadata.');
        } else {
            return null;
        }
    }


    /**
     * Convert from PEM to DER encoding.
     *
     * @param string $pem Data encoded in PEM format.
     * @return string The same data encoded in DER format.
     * @throws \InvalidArgumentException If $pem is not encoded in PEM format.
     * @see RFC7648 for PEM format specifics.
     */
    public function pem2der(string $pem): string
    {
        $pem   = trim($pem);
        $begin = "-----BEGIN ";
        $end   = "-----END ";
        $lines = explode("\n", $pem);
        $last  = count($lines) - 1;

        if (strpos($lines[0], $begin) !== 0) {
            throw new InvalidArgumentException("pem2der: input is not encoded in PEM format.");
        }
        unset($lines[0]);
        if (strpos($lines[$last], $end) !== 0) {
            throw new InvalidArgumentException("pem2der: input is not encoded in PEM format.");
        }
        unset($lines[$last]);

        return base64_decode(implode($lines));
    }


    /**
     * This function hashes a password with a given algorithm.
     *
     * @param string $password The password to hash.
     * @param mixed $algorithm The algorithm to use. Defaults to the system default
     *
     * @return string The hashed password.
     * @throws \Exception If the algorithm is not known ti PHP.
     * @throws Error\Exception If the algorithm specified is not supported.
     *
     * @see hash_algos()
     *
     */
    public function pwHash(string $password, $algorithm = PASSWORD_DEFAULT): string
    {
        return password_hash($password, $algorithm);
    }


    /**
     * Compare two strings securely.
     *
     * This method checks if two strings are equal in constant time, avoiding timing attacks. Use it every time we need
     * to compare a string with a secret that shouldn't be leaked, i.e. when verifying passwords, one-time codes, etc.
     *
     * @param string $known A known string.
     * @param string $user A user-provided string to compare with the known string.
     *
     * @return bool True if both strings are equal, false otherwise.
     */
    public function secureCompare(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }


    /**
     * This function checks if a password is valid
     *
     * @param string $hash The password as it appears in password file, optionally prepended with algorithm.
     * @param string $password The password to check in clear.
     *
     * @return boolean True if the hash corresponds with the given password, false otherwise.
     * @throws \InvalidArgumentException If the input parameters are not strings.
     * @throws Error\Exception If the algorithm specified is not supported.
     *
     */
    public function pwValid(string $hash, string $password): bool
    {
        if (!is_null(password_get_info($password)['algo'])) {
            throw new Error\Exception("Cannot use a hash value for authentication.");
        }
        if (password_verify($password, $hash)) {
            return true;
        }
        return $hash === $password;
    }
}
