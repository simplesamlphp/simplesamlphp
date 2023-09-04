<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('../_include.php');

$config = Configuration::getInstance();
$httpUtils = new Utils\HTTP();

$headers = $config->getOptionalArray('headers.security', Configuration::DEFAULT_SECURITY_HEADERS);
$redirect = Module::getModuleURL('admin/');
$response = $httpUtils->redirectTrustedURL($redirect);
foreach ($headers as $header => $value) {
    // Some pages may have specific requirements that we must follow. Don't touch them.
    if (!$response->headers->has($header)) {
        $response->headers->set($header, $value);
    }
}

$response->send();
