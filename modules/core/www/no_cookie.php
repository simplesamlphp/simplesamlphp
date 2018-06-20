<?php

if (isset($_REQUEST['retryURL'])) {
    $retryURL = (string) $_REQUEST['retryURL'];
    $retryURL = \SimpleSAML\Utils\HTTP::checkURLAllowed($retryURL);
} else {
    $retryURL = null;
}

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'core:no_cookie.tpl.php');
$t->data['retryURL'] = $retryURL;
$t->show();
