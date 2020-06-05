<?php

require_once('_include.php');

$config = \SimpleSAML\Configuration::getInstance();

\SimpleSAML\Utils\HTTP::redirectTrustedURL(SimpleSAML\Module::getModuleURL('core/login'));
