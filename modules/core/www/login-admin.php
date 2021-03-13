<?php

/*
 * Helper page for starting an admin login. Can be used as a target for links.
 */

if (!array_key_exists('ReturnTo', $_REQUEST)) {
    throw new \SimpleSAML\Error\BadRequest('Missing ReturnTo parameter.');
}

$authUtils = new \SimpleSAML\Utils\Auth();
$authUtils->requireAdmin();

$httpUtils = new \SimpleSAML\Utils\HTTP();
$httpUtils->redirectUntrustedURL($_REQUEST['ReturnTo']);
