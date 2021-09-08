<?php

require_once('_include.php');

$config = \SimpleSAML\Configuration::getInstance();
$httpUtils = new \SimpleSAML\Utils\HTTP();

$httpUtils->redirectTrustedURL(SimpleSAML\Module::getModuleURL('core/login'));
