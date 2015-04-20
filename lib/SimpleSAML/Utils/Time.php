<?php
/**
 * Time-related utility methods.
 *
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Utils;


class Time
{

    /**
     * This function generates a timestamp on the form used by the SAML protocols.
     *
     * @param int $instant The time the timestamp should represent. Defaults to current time.
     *
     * @return string The timestamp.
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function generateTimestamp($instant = null)
    {
        if ($instant === null) {
            $instant = time();
        }
        return gmdate('Y-m-d\TH:i:s\Z', $instant);
    }
}