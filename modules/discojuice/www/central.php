<?php


$djconfig = SimpleSAML_Configuration::getOptionalConfig('disojuicecentral.php');
$config = SimpleSAML_Configuration::getInstance();


$feed = new sspmod_discojuice_Feed();
$metadata = json_decode($feed->read(), TRUE);	



$t = new SimpleSAML_XHTML_Template($config, 'discojuice:central.tpl.php');
$t->data['metadata'] = $metadata;
$t->data['discojuice.options'] = $djconfig->getValue('discojuice.options');
$t->data['acl'] = $djconfig->getValue('acl');
$t->show();

