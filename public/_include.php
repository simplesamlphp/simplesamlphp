<?php

declare(strict_types=1);

// Set start-time for debugging purposes
define('SIMPLESAMLPHP_START', hrtime(true));

// initialize the autoloader
require_once(dirname(__FILE__, 2) . '/src/_autoload.php');

use SimpleSAML\Compat\SspContainer;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\SAML2\Compat\ContainerSingleton;
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\Utils;

$exceptionHandler = new Error\ExceptionHandler();
set_exception_handler([$exceptionHandler, 'customExceptionHandler']);

$errorHandler = new Error\ErrorHandler();
set_error_handler([$errorHandler, 'customErrorHandler']);

try {
    $config = Configuration::getInstance();
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
$container->setBlacklistedAlgorithms([C::SIG_RSA_SHA1, C::KEY_TRANSPORT_RSA_1_5]);
ContainerSingleton::setContainer($container);
