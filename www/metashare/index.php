<?php

require_once('../_include.php');

/**
 * This page lists all metadata currently stored in the MetaShare store.
 */

$config = SimpleSAML_Configuration::getInstance();
$metaConfig = SimpleSAML_Configuration::getConfig('metashare.php');

if(!$metaConfig->getBoolean('metashare.enable', FALSE)) {
	header('HTTP/1.0 401 Forbidden');
	$session = SimpleSAML_Session::getInstance();
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');
}

$store = SimpleSAML_MetaShare_Store::getInstance();
$entities = $store->getEntityList();

$t = new SimpleSAML_XHTML_Template($config, 'metashare-list.php', 'metashare');
$t->data['entities'] = $entities;
$t->show();
exit;


?>