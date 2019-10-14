<?php

namespace SimpleSAML\Module\cron;

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use Symfony\Component\HttpFoundation\Request;

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();
$request = Request::createFromGlobals();

$tag = $request->get('tag');
$key = $request->get('key');
$output = $request->get('output');

$controller = new Controller\Cron($config, $session);
$response = $controller->run($tag, $key, $output);
$response->send();
