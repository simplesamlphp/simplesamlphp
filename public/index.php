<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('_include.php');

$kernel = new Kernel();
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

$config = Configuration::getInstance();
$headers = $config->getOptionalArray('headers.security', Configuration::DEFAULT_SECURITY_HEADERS);
foreach ($headers as $header => $value) {
    // Some pages may have specific requirements that we must follow. Don't touch them.
    if (!$response->headers->has($header)) {
        $response->headers->set($header, $value);
    }
}
$response->send();
