<?php

/* Types of metadata. */
$metadataSets = array(
	'saml20-idp-remote',
	'saml20-sp-remote',
	'shib13-idp-remote',
	'shib13-sp-remote',
	);

$globalConfig = SimpleSAML_Configuration::getInstance();
$aggregatorConfig = SimpleSAML_Configuration::getConfig('aggregator.php');


$reconstruct = $aggregatorConfig->getBoolean('reconstruct', FALSE);
$aggregators = $aggregatorConfig->getArray('aggragators');

if (!array_key_exists('id', $_GET)) {
	$t = new SimpleSAML_XHTML_Template($globalConfig, 'aggregator:list.php');
	$t->data['sources'] = array_keys($aggregators);
	$t->show();
	exit;
}

$id = $_GET['id'];
if (!array_key_exists($id, $aggregators)) {
	throw new SimpleSAML_Error_NotFound('No aggregator with id ' . var_export($id, TRUE) . ' found.');
}


/* Parse metadata sources. */
$sources = $aggregators[$id];
if (!is_array($sources)) {
	throw new Exception('Invalid aggregator source configuration for aggregator ' .
		var_export($id, TRUE) . ': Aggregator wasn\'t an array.');
};

try {
	$sources = SimpleSAML_Metadata_MetaDataStorageSource::parseSources($sources);
} catch (Exception $e) {
	throw new Exception('Invalid aggregator source configuration for aggregator ' .
		var_export($id, TRUE) . ': ' . $e->getMessage());
}

$exclude = NULL;
if (array_key_exists('exclude', $_REQUEST)) $exclude = $_REQUEST['exclude'];

#echo $exclude; exit;
/* Find list of all available entities. */
$entities = array();
foreach ($sources as $source) {
	foreach ($metadataSets as $set) {
		foreach ($source->getMetadataSet($set) as $entityId => $metadata) {
			if (isset($exclude) && 
					array_key_exists('tags', $metadata) && 
					in_array($exclude, $metadata['tags'])) {
				SimpleSAML_Logger::debug('Excluding entity ID [' . $entityId . '] becuase it is tagged with [' . $exclude . ']');
				continue;
			} else {
				#echo('<pre>'); print_r($metadata); exit;
			}
			if (!array_key_exists($entityId, $entities)) 
				$entities[$entityId] = array();
				
			if (array_key_exists($set, $entities[$entityId])) {
				/* Entity already has metadata for the given set. */
				continue;
			}
			
			$entities[$entityId][$set] = $metadata;
		}
	}
}

$xml = new DOMDocument();
$entitiesDescriptor = $xml->createElementNS('urn:oasis:names:tc:SAML:2.0:metadata', 'EntitiesDescriptor');
$entitiesDescriptor->setAttribute('Name', $id);
$xml->appendChild($entitiesDescriptor);



/* Build EntityDescriptor elements for them. */
foreach ($entities as $entity => $sets) {

	$entityDescriptor = NULL;
	foreach ($sets as $set => $metadata) {
		if (!array_key_exists('entityDescriptor', $metadata)) {
			/* One of the sets doesn't contain an EntityDescriptor element. */
			$entityDescriptor = FALSE;
			break;
		}

		if ($entityDescriptor == NULL) {
			/* First EntityDescriptor elements. */
			$entityDescriptor = $metadata['entityDescriptor'];
			continue;
		}

		assert('is_string($entityDescriptor)');
		if ($entityDescriptor !== $metadata['entityDescriptor']) {
			/* Entity contains multiple different EntityDescriptor elements. */
			$entityDescriptor = FALSE;
			break;
		}
	}

	if (is_string($entityDescriptor) && !$reconstruct) {
		/* All metadata sets for the entity contain the same entity descriptor. Use that one. */
		$tmp = new DOMDocument();
		$tmp->loadXML(base64_decode($entityDescriptor));
		$entityDescriptor = $tmp->documentElement;
	} else {
		$tmp = new SimpleSAML_Metadata_SAMLBuilder($entity, 
			$aggregatorConfig->getValue('maxCache', NULL), $aggregatorConfig->getValue('maxDuration', NULL));
		
		$orgmeta = NULL;
		foreach ($sets as $set => $metadata) {
			$tmp->addMetadata($set, $metadata);
			$orgmeta = $metadata;
		}
		$tmp->addOrganizationInfo($orgmeta);
		$entityDescriptor = $tmp->getEntityDescriptor();
	}

	$entitiesDescriptor->appendChild($xml->importNode($entityDescriptor, TRUE));
}


/* Sign the metadata if enabled. */
if ($aggregatorConfig->getBoolean('sign.enable', FALSE)) {
	$privateKey = $aggregatorConfig->getString('sign.privatekey');
	$privateKeyPass = $aggregatorConfig->getString('sign.privatekey_pass', NULL);
	$certificate = $aggregatorConfig->getString('sign.certificate');

	$signer = new SimpleSAML_XML_Signer(array(
		'privatekey' => $privateKey,
		'privatekey_pass' => $privateKeyPass,
		'certificate' => $certificate,
		'id' => 'ID',
		));
	$signer->sign($entitiesDescriptor, $entitiesDescriptor, $entitiesDescriptor->firstChild);
}


/* Show the metadata. */
if(array_key_exists('mimetype', $_GET)) {
	$mimeType = $_GET['mimetype'];
} else {
	$mimeType = 'application/samlmetadata+xml';
}

header('Content-Type: ' . $mimeType);

echo($xml->saveXML());


?>