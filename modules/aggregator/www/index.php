<?php

$config = SimpleSAML_Configuration::getInstance();
$gConfig = SimpleSAML_Configuration::getConfig('module_aggregator.php');


// Get list of aggregators
$aggregators = $gConfig->getConfigItem('aggregators');

// If aggregator ID is not provided, show the list of available aggregates
if (!array_key_exists('id', $_GET)) {
	$t = new SimpleSAML_XHTML_Template($config, 'aggregator:list.php');
	$t->data['sources'] = $aggregators->getOptions();
	$t->show();
	exit;
}
$id = $_GET['id'];
if (!in_array($id, $aggregators->getOptions())) 
	throw new SimpleSAML_Error_NotFound('No aggregator with id ' . var_export($id, TRUE) . ' found.');

$aConfig = $aggregators->getConfigItem($id);


$aggregator = new sspmod_aggregator_Aggregator($gConfig, $aConfig, $id);

if (isset($_REQUEST['set'])) 
	$aggregator->limitSets($_REQUEST['set']);

if (isset($_REQUEST['exclude'])) 
	$aggregator->exclude($_REQUEST['exclude']);


$xml = $aggregator->getMetadataDocument();

$format = 'application/samlmetadata+xml';

/* Show the metadata. */
if(array_key_exists('format', $_GET)) {
	if ($_GET['format'] === "txt") {
		SimpleSAML_Utilities::formatDOMElement($xml);
		$format = 'text/plain';
	}
}

$metadata = '<?xml version="1.0"?>'."\n".$xml->ownerDocument->saveXML($xml);

header('Content-Type: ' . $format);
header('Content-Length: ' . strlen($metadata));

echo $metadata;
