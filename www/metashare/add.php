<?php

require_once('../_include.php');

/**
 * This page handles adding of metadata.
 */

$config = SimpleSAML_Configuration::getInstance();
$metaConfig = SimpleSAML_Configuration::getConfig('metashare.php');

if(!$metaConfig->getBoolean('metashare.enable', FALSE)) {
	header('HTTP/1.0 401 Forbidden');
	$session = SimpleSAML_Session::getInstance();
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');
}

$store = SimpleSAML_MetaShare_Store::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'metashare-add.php', 'metashare');


if(!array_key_exists('url', $_GET) || empty($_GET['url'])) {
	$t->data['url'] = NULL;
	$t->data['status'] = 'nourl';
	$t->show();
	exit;
}

$url = $_GET['url'];
$t->data['url'] = $url;

/* We accept http or https URLs */
if(substr($url, 0, 7) !== 'http://' && substr($url, 0, 8) !== 'https://') {
	$t->data['status'] = 'invalidurl';
	$t->show();
	exit();
}

/* Attempt to download the metadata. */
$metadata = file_get_contents($url);
if($metadata === FALSE) {
	$t->data['status'] = 'nodownload';
	$t->show();
	exit();
}

/* Load the metadata into an XML document. */
SimpleSAML_XML_Errors::begin();
$doc = new DOMDocument();
$doc->validateOnParse = FALSE;
$doc->strictErrorChecking = TRUE;
try {
	$ok = $doc->loadXML($metadata);
	if($ok !== TRUE) {
		$doc = NULL;
	}
} catch(DOMException $e) {
	$doc = NULL;
}
$errors = SimpleSAML_XML_Errors::end();
if($doc === NULL || count($errors) > 0) {
	$t->data['status'] = 'invalidxml';
	$t->data['errortext'] = SimpleSAML_XML_Errors::formatErrors($errors);
	$t->show();
	exit();
}
$metadata = $doc->firstChild;

/* Check that the metadata is an EntityDescriptor */
if(!SimpleSAML_Utilities::isDOMElementOfType($metadata, 'EntityDescriptor', '@md')) {
	$t->data['status'] = 'notentitydescriptor';
	$t->show();
	exit();
}

/* Check that the entity id of the metadata matches the URL. */
$entityId = $metadata->getAttribute('entityID');
if($entityId !== $url) {
	$t->data['status'] = 'entityid';
	$t->data['errortext'] = 'Entity id: ' . $entityId . "\n" . 'URL:       ' . $url . "\n";
	$t->show();
	exit();
}

/* Validate the metadata against the metadata schema (if enabled). */
if($metaConfig->getBoolean('metashare.validateschema')) {
	$errors = SimpleSAML_Utilities::validateXML($doc, 'saml-schema-metadata-2.0.xsd');
	if($errors !== '') {
		$t->data['status'] = 'validation';
		$t->data['errortext'] = $errors;
		$t->show();
		exit();
	}
}


/* Add the metadata to the metadata store. */
$store->addMetadata($metadata);

$t->data['status'] = 'ok';
$t->show();
exit();

?>