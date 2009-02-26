<?php

require_once('../_include.php');

/**
 * This page handles downloading of single metadata entries from the MetaShare.
 */

$metaConfig = SimpleSAML_Configuration::getConfig('metashare.php');

if(!$metaConfig->getBoolean('metashare.enable', FALSE)) {
	header('HTTP/1.0 401 Forbidden');
	header('Content-Type: text/plain');

	echo("The MetaShare service is disabled.\n");
	exit();
}

/**
 * This function shows a minimal 404 Not Found page.
 *
 * This function newer returns.
 *
 * @param $entityId  The entity identifier which was not found. Can be NULL,
 */
function showNotFound($entityId) {

	header('HTTP/1.0 404 Not Found');
	header('Content-Type: text/plain');

	echo("Could not find the given entity id.\n");


	if($entityId === NULL) {
		echo("No entity id given.\n");
	} else {
		echo('Entity id: ' . $entityId . "\n");
	}

	exit();
}


if(!array_key_exists('entityid', $_GET)) {
	showNotFound(NULL);
}
$entityId = $_GET['entityid'];


/* Load the metadata. */
$store = SimpleSAML_MetaShare_Store::getInstance();
$metadata = $store->getMetadata($entityId);
if($metadata === FALSE) {
	showNotFound($entityId);
}


/* Show the metadata. */
if(array_key_exists('mimetype', $_GET)) {
	$mimeType = $_GET['mimetype'];
} else {
	$mimeType = 'application/samlmetadata+xml';
}
header('Content-Type: ' . $mimeType);

echo($metadata->ownerDocument->saveXML($metadata));

?>