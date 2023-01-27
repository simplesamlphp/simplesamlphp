<?php

declare(strict_types=1);

// initialize the autoloader
require_once(dirname(__FILE__, 2) . '/src/_autoload.php');

use SAML2\Compat\ContainerSingleton;
use SimpleSAML\Compat\SspContainer;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Utils;

/**
 * show error page on unhandled exceptions
 * @param \Throwable $exception
 * @return void
 */
function SimpleSAML_exception_handler(Throwable $exception): void
{
    Module::callHooks('exception_handler', $exception);

    if ($exception instanceof Error\Error) {
        $exception->show();
    } elseif ($exception instanceof Exception) {
        $e = new Error\Error('UNHANDLEDEXCEPTION', $exception);
        $e->show();
    } elseif (class_exists('Error') && $exception instanceof \Error) {
        $e = new Error\Error('UNHANDLEDEXCEPTION', $exception);
        $e->show();
    }
}

set_exception_handler('SimpleSAML_exception_handler');

// log full backtrace on errors and warnings
/**
 * @param int $errno
 * @param string $errstr
 * @param string|null $errfile
 * @param int $errline
 * @param string|null $errcontext
 * @return false
 */
function SimpleSAML_error_handler(
    $errno,
    string $errstr,
    ?string $errfile = null,
    int $errline = 0,
    /** @scrutinizer-unused */ $errcontext = null
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
        \E_DEPRECATED => 'Deprecated',
        \E_USER_DEPRECATED => 'User Deprecated',
        \E_NOTICE => 'Notice',
        \E_USER_NOTICE => 'User Notice',
        \E_STRICT => 'Runtime Notice',
        \E_WARNING => 'Warning',
        \E_USER_WARNING => 'User Warning',
        \E_COMPILE_WARNING => 'Compile Warning',
        \E_CORE_WARNING => 'Core Warning',
        \E_USER_ERROR => 'User Error',
        \E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
        \E_COMPILE_ERROR => 'Compile Error',
        \E_PARSE => 'Parse Error',
        \E_ERROR => 'Error',
        \E_CORE_ERROR => 'Core Error',
    ];

    // show an error with a full backtrace
    $context = (is_null($errfile) ? '' : " at $errfile:$errline");
    $e = new Error\Exception($levels[$errno] . ' - ' . $errstr . $context);
    $e->logError();

    // resume normal error processing
    return false;
}

set_error_handler('SimpleSAML_error_handler');

try {
    Configuration::getInstance();
} catch (Exception $e) {
    throw new Error\CriticalConfigurationError(
        $e->getMessage()
    );
}

// set the timezone
$timeUtils = new Utils\Time();
$timeUtils->initTimezone();

// set the SAML2 container
$container = new SspContainer();
ContainerSingleton::setContainer($container);
