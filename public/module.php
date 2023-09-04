<?php

/**
 * This web page receives requests for web-pages hosted by modules, and directs them to
 * the process() handler in the Module class.
 */

declare(strict_types=1);

namespace SimpleSAML;

require_once('_include.php');

$config = Configuration::getInstance();
$headers = $config->getOptionalArray('headers.security', Configuration::DEFAULT_SECURITY_HEADERS);

$response = Module::process();
foreach ($headers as $header => $value) {
    // Some pages may have specific requirements that we must follow. Don't touch them.
    if (!$response->headers->has($header)) {
        $response->headers->set($header, $value);
    }
}
$response->send();
