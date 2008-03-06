#!/usr/bin/env php
<?php

/*
 * This script can be used to generate metadata for simpleSAMLphp
 * based on an XML metadata file.
 */


/* This is the base directory of the simpleSAMLphp installation. */
$baseDir = dirname(dirname(__FILE__));

/* Set up the include path. */
$path_extra =  $baseDir . '/lib';
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

/* Load required libraries. */
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/SAMLParser.php');


/* $outputDir contains the directory we will store the generated metadata in. */
$outputDir = $baseDir . '/metadata-generated';


/* $toStdOut is a boolean telling us wheter we will print the output to stdout instead
 * of writing it to files in $outputDir.
 */
$toStdOut = FALSE;



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

	if(strpos($a, '=') !== FALSE) {
		$p = strpos($a, '=');
		$v = substr($a, $p + 1);
		$a = substr($a, 0, $p);
	} else {
		$v = NULL;
	}

	/* Map short options to long options. */
	$shortOptMap = array(
		'-h' => '--help',
		'-o' => '--out-dir',
		'-s' => '--stdout',
		);
	if(array_key_exists($a, $shortOptMap)) {
		$a = $shortOptMap[$a];
	}

	switch($a) {
	case '--help':
		printHelp();
		exit(0);
	case '--out-dir':
		if($v === NULL || strlen($v) === 0) {
			echo('The --out-dir option requires an parameter.' . "\n");
			echo('Please run `' . $progName . ' --help` for usage information.' . "\n");
			exit(1);
		}
		$outputDir = $v;
		break;
	case '--stdout':
		$toStdOut = TRUE;
		break;
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

if($toStdOut) {
	dumpMetadataStdOut();
} else {
	writeMetadataFiles();
}

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
	echo(' -h, --help                   Print this help.' . "\n");
	echo(' -o=<DIR>, --out-dir=<DIR>    Write the output to this directory. The' . "\n");
	echo('                              default directory is metadata-generated/' . "\n");
	echo(' -s, --stdout                 Write the output to stdout instead of' . "\n");
	echo('                              seperate files in the output directory.' . "\n");
	echo("\n");
}


/**
 * This function writes the metadata to to separate files in the output directory.
 */
function writeMetadataFiles() {

	global $outputDir;

	while(strlen($outputDir) > 0 && $outputDir[strlen($outputDir) - 1] === '/') {
		$outputDir = substr($outputDir, 0, strlen($outputDir) - 1);
	}

	if(!file_exists($outputDir)) {
		echo('Creating directory: ' . $outputDir . "\n");
		mkdir($outputDir, 0777, TRUE);
	}

	foreach($GLOBALS['metadata'] as $category => $elements) {

		$filename = $outputDir . '/' . $category . '.php';

		echo('Writing: ' . $filename . "\n");

		$fh = fopen($filename, 'w');
		if($fh === FALSE) {
			echo('Failed to open file for writing: ' . $filename . "\n");
			exit(1);
		}

		fwrite($fh, '<?php' . "\n");

		foreach($elements as $m) {
			$filename = $m['filename'];
			$entityID = $m['metadata']['entityID'];

			fwrite($fh, "\n");
			fwrite($fh, '/* The following metadata was generated from ' . $filename . ' on ' . $GLOBALS['when'] . '. */' . "\n");
			fwrite($fh, '$metadata[\'' . addslashes($entityID) . '\'] = ' . var_export($m['metadata'], TRUE) . ';' . "\n");
		}


		fwrite($fh, "\n");
		fwrite($fh, '?>');

		fclose($fh);
	}
}


/**
 * This function writes the metadata to stdout.
 */
function dumpMetadataStdOut() {

	foreach($GLOBALS['metadata'] as $category => $elements) {

		echo('/* The following data should be added to metadata/' . $category . '.php. */' . "\n");


		foreach($elements as $m) {
			$filename = $m['filename'];
			$entityID = $m['metadata']['entityID'];

			echo("\n");
			echo('/* The following metadata was generated from ' . $filename . ' on ' . $GLOBALS['when'] . '. */' . "\n");
			echo('$metadata[\'' . addslashes($entityID) . '\'] = ' . var_export($m['metadata'], TRUE) . ';' . "\n");
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