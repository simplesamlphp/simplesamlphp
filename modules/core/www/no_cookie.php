<?php

if (isset($_REQUEST['retryURL'])) {
	$retryURL = (string)$_REQUEST['retryURL'];
	$retryURL = \SimpleSAML\Utils\HTTP::normalizeURL($retryURL);
} else {
	$retryURL = NULL;
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:no_cookie.tpl.php');
$t->data['retryURL'] = $retryURL;
$t->show();
