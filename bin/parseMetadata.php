#!/usr/bin/env php
<?php

/*
 * This script can be used to generate SAML 1.x metadata for simpleSAMLphp
 * based on a metadata file.
 */


/* Set up the include path. */
$path_extra = dirname(dirname(__FILE__)) . '/lib';
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

/* Load required libraries. */
require_once('SimpleSAML/Metadata/SAMLParser.php');


/* This variable contains the files we will parse. */
$files = array();

/* Parse arguments. */

$progName = array_shift($argv);

foreach($argv as $a) {
	if(strlen($a) === 0) {
		continue;
	}

	if($a[0] !== '-') {
		/* Not an option. Assume that it is a file we should parse. */
		$files[] = $a;
		continue;
	}

	/* Map short options to long options. */
	$shortOptMap = array(
		'-h' => '--help',
		);
	if(array_key_exists($a, $shortOptMap)) {
		$a = $shortOptMap[$a];
	}

	switch($a) {
	case '--help':
		printHelp();
		exit(0);
	default:
		echo('Unknown option: ' . $a . "\n");
		echo('Please run `' . $progName . ' --help` for usage information.' . "\n");
		exit(1);
	}
}

if(count($files) === 0) {
	echo($progName . ': Missing input files. Please run `' . $progName . ' --help` for usage information.' . "\n");
	exit(1);
}


/* The current date, as a string. */
date_default_timezone_set('UTC');
$when = date('Y-m-d\\TH:i:s\\Z');

/* The metadata global variable will be filled with the metadata we extract. */
$metadata = array();

foreach($files as $f) {
	processFile($f);
}

dumpMetadata();

exit(0);

/**
 * This function prints the help output.
 */
function printHelp() {
	global $progName;

	/*   '======================================================================' */
	echo('Usage: ' . $progName . ' [options] [files]' . "\n");
	echo("\n");
	echo('This program parses a SAML metadata files and output pieces that can' . "\n");
	echo('be added to the metadata files in metadata/.' . "\n");
	echo("\n");
	echo('Options:' . "\n");
	echo(' -h, --help     Print this help.' . "\n");
	echo("\n");
}


/**
 * This function outputs data which should be added to the metadata/shib13-sp-remote.php file.
 */
function dumpMetadata() {

	foreach($GLOBALS['metadata'] as $category => $elements) {

		echo('/* The following data should be added to metadata/' . $category . '.php. */' . "\n");


		foreach($elements as $m) {
			$filename = $m['filename'];
			$entityID = $m['metadata']['entityID'];

			echo("\n");
			echo('/* The following metadata was generated from ' . $filename . ' on ' . $GLOBALS['when'] . '. */' . "\n");
			echo('$metadata[\'' . addslashes($entityID) . '\'] = ' . var_export($m['metadata'], TRUE)) . ';' . "\n";
		}


		echo("\n");
		echo('/* End of data which should be added to metadata/' . $category . '.php. */' . "\n");
		echo("\n");
	}
}


/**
 * This function processes a SAML metadata file.
 *
 * @param $filename  Filename of the metadata file.
 */
function processFile($filename) {
	$entities = SimpleSAML_Metadata_SAMLParser::parseDescriptorsFile($filename);

	foreach($entities as $entity) {
		addMetadata($filename, $entity->getMetadata1xSP(), 'shib13-sp-remote');
		addMetadata($filename, $entity->getMetadata1xIdP(), 'shib13-idp-remote');
		addMetadata($filename, $entity->getMetadata20SP(), 'saml20-sp-remote');
		addMetadata($filename, $entity->getMetadata20IdP(), 'saml20-idp-remote');
	}
}


/**
 * This function adds metadata from the specified file to the list of metadata.
 * This function will return without making any changes if $metadata is NULL.
 *
 * @param $filename The filename the metadata comes from.
 * @param $metadata The metadata.
 * @param $type The metadata type.
 */
function addMetadata($filename, $metadata, $type) {

	if($metadata === NULL) {
		return;
	}

	if(!array_key_exists($type, $GLOBALS['metadata'])) {
		$GLOBALS['metadata'][$type] = array();
	}

	$GLOBALS['metadata'][$type][] = array('filename' => $filename, 'metadata' => $metadata);
}