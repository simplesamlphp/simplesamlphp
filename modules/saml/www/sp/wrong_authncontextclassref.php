<?php

$globalConfig = \SimpleSAML\Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'saml:sp/wrong_authncontextclassref.tpl.php');
$t->show();
