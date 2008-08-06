<?php

require_once('_include.php');

if(array_key_exists('sptype', $_GET) && array_key_exists('spentityid', $_GET)) {
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	$spmetadata = $metadata->getMetaData($_GET['spentityid'], $_GET['sptype']);
} else {
	$spmetadata = array();
}
/* Load simpleSAMLphp, configuration */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

$t = new SimpleSAML_XHTML_Template($config, 'noconsent.php');
$t->data['spmetadata'] = $spmetadata;
if(array_key_exists('resumeFrom', $_REQUEST)) {
	$t->data['resumeFrom'] = $_REQUEST['resumeFrom'];
}
$t->show();

?>