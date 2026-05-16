<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('_include.php');

$kernel = new Kernel();
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);
