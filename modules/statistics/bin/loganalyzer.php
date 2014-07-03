#!/usr/bin/env php
<?php


/* This is the base directory of the simpleSAMLphp installation. */
$baseDir = dirname(dirname(dirname(dirname(__FILE__))));

/* Add library autoloader. */
require_once($baseDir . '/lib/_autoload.php');

$configdir = '';

$progName = array_shift($argv);
$debug = FALSE;
$dryrun = FALSE;

foreach($argv as $a) {
	if(strlen($a) === 0) continue;

	if(strpos($a, '=') !== FALSE) {
		$p = strpos($a, '=');
		$v = substr($a, $p + 1);
		$a = substr($a, 0, $p);
	} else {
		$v = NULL;
	}

	/* Map short options to long options. */
	$shortOptMap = array(
		'-d' => '--debug',
	);
	if(array_key_exists($a, $shortOptMap))  $a = $shortOptMap[$a];

	switch($a) {
		case '--help':
			printHelp();
			exit(0);
		case '--debug':
			$debug = TRUE;
			break;
		case '--dry-run':
			$dryrun = TRUE;
			break;
		case '--configdir':
			/* If the path is not empty and the file exists, then set the configuration directory. */
			if (!empty($v) && file_exists($v)) {
				$configdir = $v;
			} else {
				/* Directory does not exist. */
				echo "Alternate config directory argument: $configdir was not found. Using default config directory location.\n";
				$configdir = '';
			}
			break;
		default:
			echo('Unknown option: ' . $a . "\n");
			echo('Please run `' . $progName . ' --help` for usage information.' . "\n");
			exit(1);
		}
}

if (empty($configdir)) {
	if (empty(getenv("SIMPLESAMLPHP_CONFIG_DIR"))) {
		/* Initialize the default configuration. */
		$configdir = $baseDir . '/config';
	} else {
		$configdir = getenv("SIMPLESAMLPHP_CONFIG_DIR");
	}
}

/* Initialize the configuration. */
SimpleSAML_Configuration::setConfigDir($configdir);

SimpleSAML_Utilities::initTimezone();

$aggregator = new sspmod_statistics_Aggregator(TRUE);
$aggregator->dumpConfig();
$aggregator->debugInfo();
$results = $aggregator->aggregate($debug);
$aggregator->debugInfo();

if (!$dryrun) {
	$aggregator->store($results);
}


foreach ($results AS $slot => $val) {
	 foreach ($val AS $sp => $no) {
	 	echo $sp . " " . count($no) . " - ";
	 }
	 echo "\n";
}




/**
 * This function prints the help output.
 */
function printHelp() {
	global $progName;

	/*   '======================================================================' */
	echo('Usage: ' . $progName . ' [options]

This program parses and aggregates SimpleSAMLphp log files.

Options:
	-d, --debug			Used when configuring the log file syntax. See doc.
	--dry-run			Aggregate but do not store the results.
	--configdir			Specify the absolute path of the config directory.

');
}