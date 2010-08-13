#!/usr/bin/env php
<?php

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/lib/_autoload.php');

if ($argc < 2) {
	fwrite(STDERR, "Missing aggregator id.\n");
	exit(1);
}
$id = $argv[1];

$gConfig = SimpleSAML_Configuration::getConfig('module_aggregator.php');
$aggregators = $gConfig->getConfigItem('aggregators');

$aConfig = $aggregators->getConfigItem($id, NULL);
if ($aConfig === NULL) {
	fwrite(STDERR, 'No aggregator with id ' . var_export($id, TRUE) . " found.\n");
	exit(1);
}

$aggregator = new sspmod_aggregator_Aggregator($gConfig, $aConfig, $id);

$xml = $aggregator->getMetadataDocument();
echo($xml->saveXML());
