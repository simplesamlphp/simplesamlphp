<?php

if (isset($_REQUEST['retryURL'])) {
    $retryURL = (string) $_REQUEST['retryURL'];
    $retryURL = \SimpleSAML\Utils\HTTP::checkURLAllowed($retryURL);
} else {
    $retryURL = null;
}

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'core:no_cookie.tpl.php');
$translator = $t->getTranslator();

$t->data['header'] = htmlspecialchars($translator->t('{core:no_cookie:header}'));
$t->data['description'] = htmlspecialchars($translator->t('{core:no_cookie:description}'));
$t->data['retry'] = htmlspecialchars($translator->t('{core:no_cookie:retry}'));
$t->data['retryURL'] = $retryURL;
$t->show();
