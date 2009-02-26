<?php

require_once('../_include.php');

/**
 * This page handles downloading of all metadata entries from the MetaShare.
 */

$metaConfig = SimpleSAML_Configuration::getConfig('metashare.php');

if(!$metaConfig->getBoolean('metashare.enable', FALSE)) {
	header('HTTP/1.0 401 Forbidden');
	header('Content-Type: text/plain');

	echo("The MetaShare service is disabled.\n");
	exit();
}

/* Build EntitiesDescriptor. */

$doc = new DOMDocument('1.0', 'utf-8');
$root = $doc->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EntitiesDescriptor');
$doc->appendChild($root);

$store = SimpleSAML_MetaShare_Store::getInstance();
foreach($store->getEntityList() as $entityId) {
	$entityNode = $store->getMetadata($entityId);
	if($entityNode === FALSE) {
		/* For some reason we were unable to load the metadata - skip entity. */
		continue;
	}

	$entityNode = $doc->importNode($entityNode, TRUE);
	assert($entityNode !== FALSE);

	$root->appendChild($entityNode);
}


/* Sign the metadata if enabled. */
if($metaConfig->getBoolean('metashare.signmetadatalist', FALSE)) {
	$privateKey = $metaConfig->getString('metashare.privatekey');
	$privateKeyPass = $metaConfig->getString('metashare.privatekey_pass', NULL);
	$certificate = $metaConfig->getString('metashare.certificate');

	$signer = new SimpleSAML_XML_Signer(array(
		'privatekey' => $privateKey,
		'privatekey_pass' => $privateKeyPass,
		'certificate' => $certificate,
		'id' => 'ID',
		));
	$signer->sign($root, $root, $root->firstChild);
}


/* Show the metadata. */
if(array_key_exists('mimetype', $_GET)) {
	$mimeType = $_GET['mimetype'];
} else {
	$mimeType = 'application/samlmetadata+xml';
}
header('Content-Type: ' . $mimeType);

echo($doc->saveXML());

?>