<?php

/**
 * This class defines an interface for accessing errors from the XML library.
 *
 * In PHP versions which doesn't support accessing error information, this class
 * will hide that, and pretend that no errors were logged.
 *
 * @package SimpleSAMLphp
 */

declare(strict_types=1);

namespace SimpleSAML\XML;

use LibXMLError;

use function array_merge;
use function array_pop;
use function count;
use function function_exists;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function trim;

class Errors
{
    /**
     * @var array This is an stack of error logs. The topmost element is the one we are currently working on.
     */
    private static array $errorStack = [];

    /**
     * @var bool This is the xml error state we had before we began logging.
     */
    private static bool $xmlErrorState;


    /**
     * Append current XML errors to the current stack level.
     *
     */
    private static function addErrors(): void
    {
        $currentErrors = libxml_get_errors();
        libxml_clear_errors();

        $level = count(self::$errorStack) - 1;
        self::$errorStack[$level] = array_merge(self::$errorStack[$level], $currentErrors);
    }


    /**
     * Start error logging.
     *
     * A call to this function will begin a new error logging context. Every call must have
     * a corresponding call to end().
     *
     */
    public static function begin(): void
    {

        // Check whether the error access functions are present
        if (!function_exists('libxml_use_internal_errors')) {
            return;
        }

        if (count(self::$errorStack) === 0) {
            // No error logging is currently in progress. Initialize it.
            self::$xmlErrorState = libxml_use_internal_errors(true);
            libxml_clear_errors();
        } else {
            /* We have already started error logging. Append the current errors to the
             * list of errors in this level.
             */
            self::addErrors();
        }

        // Add a new level to the error stack
        self::$errorStack[] = [];
    }


    /**
     * End error logging.
     *
     * @return array  An array with the LibXMLErrors which has occurred since begin() was called.
     */
    public static function end(): array
    {
        // Check whether the error access functions are present
        if (!function_exists('libxml_use_internal_errors')) {
            // Pretend that no errors occurred
            return [];
        }

        // Add any errors which may have occurred
        self::addErrors();


        $ret = array_pop(self::$errorStack);

        if (count(self::$errorStack) === 0) {
            // Disable our error logging and restore the previous state
            libxml_use_internal_errors(self::$xmlErrorState);
        }

        return $ret;
    }


    /**
     * Format an error as a string.
     *
     * This function formats the given LibXMLError object as a string.
     *
     * @param \LibXMLError $error  The LibXMLError which should be formatted.
     * @return string  A string representing the given LibXMLError.
     */
    public static function formatError(LibXMLError $error): string
    {
        return 'level=' . $error->level
            . ',code=' . $error->code
            . ',line=' . $error->line
            . ',col=' . $error->column
            . ',msg=' . trim($error->message);
    }


    /**
     * Format a list of errors as a string.
     *
     * This function takes an array of LibXMLError objects and creates a string with all the errors.
     * Each error will be separated by a newline, and the string will end with a newline-character.
     *
     * @param array $errors  An array of errors.
     * @return string  A string representing the errors. An empty string will be returned if there were no
     *          errors in the array.
     */
    public static function formatErrors(array $errors): string
    {
        $ret = '';
        foreach ($errors as $error) {
            $ret .= self::formatError($error) . "\n";
        }

        return $ret;
    }
}
