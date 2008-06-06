<?php

require_once('_include.php');

/* Load simpleSAMLphp, configuration */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'noconsent.php');
$t->show();

?>