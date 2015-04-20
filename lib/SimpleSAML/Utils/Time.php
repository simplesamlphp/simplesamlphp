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


    /**
     * Initialize the timezone.
     *
     * This function should be called before any calls to date().
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function initTimezone()
    {
        static $initialized = false;

        if ($initialized) {
            return;
        }

        $initialized = true;

        $globalConfig = \SimpleSAML_Configuration::getInstance();

        $timezone = $globalConfig->getString('timezone', null);
        if ($timezone !== null) {
            if (!date_default_timezone_set($timezone)) {
                throw new \SimpleSAML_Error_Exception('Invalid timezone set in the "timezone" option in config.php.');
            }
            return;
        }
        // we don't have a timezone configured

        /*
         * The date_default_timezone_get() function is likely to cause a warning.
         * Since we have a custom error handler which logs the errors with a backtrace,
         * this error will be logged even if we prefix the function call with '@'.
         * Instead we temporarily replace the error handler.
         */
        set_error_handler(function (){
                return true;
            });
        $serverTimezone = date_default_timezone_get();
        restore_error_handler();

        // set the timezone to the default
        date_default_timezone_set($serverTimezone);
    }
}