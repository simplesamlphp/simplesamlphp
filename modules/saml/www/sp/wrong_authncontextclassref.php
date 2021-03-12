<?php

use SimpleSAML\Configuration;
use SimpleSAML\XHTML\Template;

$globalConfig = Configuration::getInstance();
$t = new Template($globalConfig, 'saml:sp/wrong_authncontextclassref.twig');
$t->send();
