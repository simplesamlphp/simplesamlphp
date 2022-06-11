<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('_include.php');

$config = Configuration::getInstance();
$httpUtils = new Utils\HTTP();

$redirect = $config->getOptionalString('frontpage.redirect', Module::getModuleURL('core/welcome'));
$httpUtils->redirectTrustedURL($redirect);
