<?php

declare(strict_types=1);

namespace SimpleSAML\Error;

use E_DEPRECATED;
use E_USER_DEPRECATED;
use E_NOTICE;
use E_USER_NOTICE;
use E_STRICT;
use E_WARNING;
use E_USER_WARNING;
use E_COMPILE_WARNING;
use E_CORE_WARNING;
use E_USER_ERROR;
use E_RECOVERABLE_ERROR;
use E_COMPILE_ERROR;
use E_PARSE;
use E_ERROR;
use E_CORE_ERROR;
use SimpleSAML\Logger;

use function is_null;

/**
 * SimpleSAMLphp's custom error handler that logs full backtraces on errors and warnings
 *
 * @package SimpleSAMLphp
 */
class ErrorHandler
{
   /**
    * @param int $errno
    * @param string $errstr
    * @param string|null $errfile
    * @param int $errline
    * @param string|null $errcontext
    * @return false
    */
    public function customErrorHandler(
        int $errno,
        string $errstr,
        ?string $errfile = null,
        int $errline = 0,
    ): bool {
        if (Logger::isErrorMasked($errno)) {
            // masked error
            return false;
        }

        static $limit = 5;
        $limit -= 1;
        if ($limit < 0) {
            // we have reached the limit in the number of backtraces we will log
            return false;
        }

        $levels = [
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated',
            E_NOTICE => 'Notice',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Runtime Notice',
            E_WARNING => 'Warning',
            E_USER_WARNING => 'User Warning',
            E_COMPILE_WARNING => 'Compile Warning',
            E_CORE_WARNING => 'Core Warning',
            E_USER_ERROR => 'User Error',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
            E_COMPILE_ERROR => 'Compile Error',
            E_PARSE => 'Parse Error',
            E_ERROR => 'Error',
            E_CORE_ERROR => 'Core Error',
        ];

        // show an error with a full backtrace
        $context = (is_null($errfile) ? '' : " at $errfile:$errline");
        $e = new Exception($levels[$errno] . ' - ' . $errstr . $context);
        $e->logError();

        // resume normal error processing
        return false;
    }
}
