<?php

/**
 * This page shows a list of authentication sources. When the user selects
 * one of them if pass this information to the
 * \SimpleSAML\Module\multiauth\Auth\Source\MultiAuth class and call the
 * delegateAuthentication method on it.
 *
 * @author Lorenzo Gil, Yaco Sistemas S.L.
 * @package SimpleSAMLphp
 */

namespace SimpleSAML\Module\multiauth;

use SimpleSAML\Configuration;
use SimpleSAML\Session;
use Symfony\Component\HttpFoundation\Request;

$config = Configuration::getInstance();
$session = Session::getSessionFromRequest();
$request = Request::createFromGlobals();

$controller = new DiscoController($config, $session);
$response = $controller->discovery($request);
$response->send();
