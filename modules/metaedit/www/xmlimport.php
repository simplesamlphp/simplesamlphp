<?php


/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

$template = new SimpleSAML_XHTML_Template($config, 'metaedit:xmlimport.tpl.php');
$template->show();
