<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

/**
 * Utility class for random data generation and manipulation.
 *
 * @package SimpleSAMLphp
 */
class Random
{
    /**
     * The fixed length of random identifiers.
     */
    public const ID_LENGTH = 43;

    /**
     * Generate a random identifier, ID_LENGTH bytes long.
     *
     * @return string A ID_LENGTH-bytes long string with a random, hex-encoded string.
     *
     */
    public static function generateID(): string
    {
        return '_' . bin2hex(openssl_random_pseudo_bytes((int) ((self::ID_LENGTH - 1) / 2)));
    }
}
