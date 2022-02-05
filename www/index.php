<?php

require_once('_include.php');

$config = \SimpleSAML\Configuration::getInstance();
$httpUtils = new \SimpleSAML\Utils\HTTP();

$redirect = $config->getOptionalString('frontpage.redirect', SimpleSAML\Module::getModuleURL('core/welcome'));
$httpUtils->redirectTrustedURL($redirect);
