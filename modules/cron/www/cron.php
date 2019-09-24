<?php

namespace SimpleSAML\Module\cron;

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use Symfony\Component\HttpFoundation\Request;

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();
$request = Request::createFromGlobals();

$controller = new Controller\CronController($config, $session);
$response = $controller->run($request);
$response->send();
