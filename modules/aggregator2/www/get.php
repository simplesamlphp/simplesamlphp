<?php

if (!isset($_REQUEST['id'])) {
    throw new SimpleSAML_Error_BadRequest('Missing required parameter "id".');
}
$id = (string) $_REQUEST['id'];

$aggregator = sspmod_aggregator2_Aggregator::getAggregator($id);
$xml = $aggregator->getMetadata();

$mimetype = 'application/samlmetadata+xml';
$allowedmimetypes = array(
    'text/plain',
    'application/samlmetadata-xml',
    'application/xml',
);

if (isset($_GET['mimetype']) && in_array($_GET['mimetype'], $allowedmimetypes)) {
    $mimetype = $_GET['mimetype'];
}

if ($mimetype === 'text/plain') {
    $xml = SimpleSAML_Utilities::formatXMLString($xml);
}

header('Content-Type: '.$mimetype);
header('Content-Length: ' . strlen($xml));

/*
 * At this point, if the ID was forged, getMetadata() would
 * have failed to find a valid metadata set, so we can trust it.
 */
header('Content-Disposition: filename='.$id.'.xml');

echo $xml;
