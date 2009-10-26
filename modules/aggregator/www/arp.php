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


$md = $aggregator->getSources();


$attributemap = NULL;
if (isset($_REQUEST['attributemap'])) $attributemap = $_REQUEST['attributemap'];
$prefix = '';
if (isset($_REQUEST['prefix'])) $prefix = $_REQUEST['prefix'];
$suffix = '';
if (isset($_REQUEST['suffix'])) $suffix = $_REQUEST['suffix'];


$arp = new sspmod_aggregator_ARP($md, $attributemap, $prefix, $suffix);

$arpxml = $arp->getXML();

$xml = new DOMDocument();
$xml->loadXML($arpxml);

$firstelement = $xml->firstChild;

if ($aggregator->shouldSign()) {
	$signinfo = $aggregator->getSigningInfo();
	$signer = new SimpleSAML_XML_Signer($signinfo);
	$signer->sign($firstelement, $firstelement, $firstelement->firstChild);
}




// echo('<pre>' . $arpxml); exit;


/* Show the metadata. */
if(array_key_exists('mimetype', $_GET)) {
	$mimeType = $_GET['mimetype'];
} else {
	$mimeType = 'application/samlmetadata+xml';
}

header('Content-Type: ' . $mimeType);

echo($xml->saveXML());


?>