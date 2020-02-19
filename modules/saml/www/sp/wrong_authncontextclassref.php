<?php

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'saml:sp/wrong_authncontextclassref.twig');
$t->send();
