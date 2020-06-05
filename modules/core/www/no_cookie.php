<?php

if (isset($_REQUEST['retryURL'])) {
    $retryURL = strval($_REQUEST['retryURL']);
    $retryURL = \SimpleSAML\Utils\HTTP::checkURLAllowed($retryURL);
} else {
    $retryURL = null;
}

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'core:no_cookie.twig');
$t->data['retryURL'] = $retryURL;
$t->send();
