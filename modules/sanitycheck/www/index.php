<?php


$config = SimpleSAML_Configuration::getInstance();
$sconfig = $config->copyFromBase('sconfig', 'config-sanitycheck.php');




$info = array();
$errors = array();
$hookinfo = array(
	'info' => &$info, 
	'errors' => &$errors,
);
SimpleSAML_Module::callHooks('sanitycheck', $hookinfo);


$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'sanitycheck:check-tpl.php');
$t->data['errors'] = $errors;
$t->data['info'] = $info;
$t->show();

?>