<?php


/**
 * Utility class for random data generation and manipulation.
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_Random
{

    /**
     * Generate a random identifier, 22 bytes long.
     *
     * @return string A 22-bytes long string with a random, hex string.
     *
     * @author Andreas Solberg, UNINETT AS <andreas.solberg@uninett.no>
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function generateID()
    {
        return '_'.bin2hex(openssl_random_pseudo_bytes(21));
    }
}