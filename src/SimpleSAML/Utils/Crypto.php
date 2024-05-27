<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use Exception;
use InvalidArgumentException;
use PDO;
use SimpleSAML\{Configuration, Database, Error, Logger};

use function base64_decode;
use function base64_encode;
use function chunk_split;
use function count;
use function explode;
use function implode;
use function strncmp;
use function strpos;
use function substr;
use function trim;

/**
 * A class for cryptography-related functions.
 *
 * @package SimpleSAMLphp
 */

class Crypto
{
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
     * - 'privatekey': Location of a private key
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
     * @param bool                      $full_path Whether the location found in the configuration contains the
     * full path to the private key or not (only relevant for file locations). Default to false.
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
        bool $full_path = false,
    ): ?array {
        $location = $metadata->getOptionalString($prefix . 'privatekey', null);
        if ($location === null) {
            // no private key found
            if ($required) {
                throw new Error\Exception('No private key found in metadata.');
            } else {
                return null;
            }
        }

        $data = $this->retrieveKey($location, $full_path);

        if ($data === null) {
            throw new Error\Exception('Unable to load private key from location "' . $location . '"');
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
     * - 'certificate': Location of a certificate or public key in PEM-format.
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

        $transform = base64_decode(implode($lines), true);
        if (empty($transform)) {
            throw new InvalidArgumentException("pem2der: input is empty or not a valid base64 encoded string.");
        }

        return $transform;
    }


    /**
     * Retrieve a certificate or private key from specified storage location
     *
     * @param string $data_type Type of data to retrieve, either "certificate" or "private_key"
     * @param string $location Location of data to retrieve
     * @param bool $full_path Whether the location found in the configuration contains the
     *                        full path to the certificate or private key (only relevant to file locations)
     *
     * @return string The certificate or private key, or null if not found
     *
     */
    private function retrieveCertOrKey(string $data_type, string $location, bool $full_path): ?string
    {
        if (strncmp($location, 'pdo://', 6) === 0) {
            // Attempt to load data via pdo from database

            $location = substr($location, 6);

            $globalConfig = Configuration::getInstance();
            $cert_table = $globalConfig->getOptionalString('cert.pdo.table', 'certificates');
            $key_table = $globalConfig->getOptionalString('cert.pdo.keytable', 'private_keys');
            $apply_prefix = $globalConfig->getOptionalBoolean('cert.pdo.apply_prefix', true);
            $id_column = $globalConfig->getOptionalString('cert.pdo.id_column', 'id');
            $data_column = $globalConfig->getOptionalString('cert.pdo.data_column', 'data');

            try {
                $db = Database::getInstance();
            } catch (Exception $e) {
                Logger::error('failed to instantiate database: ' . $e->getMessage());
                return null;
            }

            if ($apply_prefix) {
                $cert_table = $db->applyPrefix($cert_table);
                $key_table = $db->applyPrefix($key_table);
            }

            try {
                $query = $db->read("select $data_column from " .
                                    ($data_type == 'certificate' ? $cert_table : $key_table) .
                                    " where $id_column = :id", ['id' => $location]);
            } catch (Exception $e) {
                Logger::error('failed to query database: ' . $e->getMessage());
                return null;
            }

            $result = $query->fetch(PDO::FETCH_NUM);

            if ($result) {
                return $result[0];
            }

            return null;
        } elseif (strncmp($location, 'file://', 7) === 0) {
            // Locations without a prefix are assumed to be file locations.
            // So just remove prefix and fall through

            $location = substr($location, 7);
        }

        // Attempt to load data from file
        if (!$full_path) {
            $configUtils = new Config();
            $location = $configUtils->getCertPath($location);
        }

        $data = @file_get_contents($location);

        if ($data === false) {
            Logger::error("failed to read $data_type data from file $location");
            return null;
        }

        return $data;
    }

    /**
     * Public wrapper around retrieveCertOrKey to retrieve a certificate
     *
     * @param string $location Location of certificate data to retrieve
     * @param bool $full_path Whether the location found in the configuration contains the
     *                        full path to the certificate (only relevant to file locations).
     *                        Default to false.
     *
     * @return string The certificate or null if not found
     *
     */
    public function retrieveCertificate(string $location, bool $full_path = false): ?string
    {
        return $this->retrieveCertOrKey('certificate', $location, $full_path);
    }

    /**
     * Public wrapper around retrieveCertOrKey to retrieve a private key
     *
     * @param string $location Location of private key data to retrieve
     * @param bool $full_path Whether the location found in the configuration contains the
     *                        full path to the private key (only relevant to file locations).
     *                        Default to false.
     *
     * @return string The private key or null if not found
     *
     */
    public function retrieveKey(string $location, bool $full_path = false): ?string
    {
        return $this->retrieveCertOrKey('private_key', $location, $full_path);
    }
}
