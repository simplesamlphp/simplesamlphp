<?php

if (isset($_REQUEST['retryURL'])) {
    $retryURL = (string) $_REQUEST['retryURL'];
    $retryURL = \SimpleSAML\Utils\HTTP::checkURLAllowed($retryURL);
} else {
    $retryURL = null;
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:no_cookie.tpl.php');

$t->data['header'] = htmlspecialchars($t->t('{core:no_cookie:header}'));
$t->data['description'] = htmlspecialchars($t->t('{core:no_cookie:description}'));
$t->data['retry'] = htmlspecialchars($t->t('{core:no_cookie:retry}'));
$t->data['retryURL'] = $retryURL;
$t->show();
