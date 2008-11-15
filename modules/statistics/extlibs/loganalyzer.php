<?php

$config = SimpleSAML_Configuration::getInstance();
$statconfig = $config->copyFromBase('statconfig', 'statistics.php');

$statdir = $statconfig->getValue('statdir');
$inputfile = $statconfig->getValue('inputfile');
$statrules = $statconfig->getValue('statrules');

$file = fopen($inputfile, 'r');
$logfile = file($inputfile, FILE_IGNORE_NEW_LINES );


$logparser = new sspmod_statistics_LogParser(
	$statconfig->getValue('datestart', 0), $statconfig->getValue('datelength', 15), $statconfig->getValue('offsetspan', 44)
);
$datehandler = new sspmod_statistics_DateHandler($statconfig->getValue('offset', 0));

$results = array();

// Parse through log file, line by line
foreach ($logfile AS $logline) {

	// Continue if STAT is not found on line.
	if (!preg_match('/STAT/', $logline)) continue;

	// Parse log, and extract epoch time and rest of content.
	$epoch = $logparser->parseEpoch($logline);
	$content = $logparser->parseContent($logline);
	$action = $content[4];
	
	// Iterate all the statrules from config.
	foreach ($statrules AS $rulename => $rule) {
		$timeslot = $datehandler->toSlot($epoch, $rule['slot']);
		$fileslot = $datehandler->toSlot($epoch, $rule['fileslot']); //print_r($content);
		if (isset($rule['action']) && ($action !== $rule['action'])) continue;

		$difcol = $content[$rule['col']]; // echo '[...' . $difcol . '...]';

		$results[$rulename][$fileslot][$timeslot]['_']++;
		$results[$rulename][$fileslot][$timeslot][$difcol]++;
	}
}

// Iterate the first level of results, which is per rule, as defined in the config.
foreach ($results AS $rulename => $ruleresults) {

	// Iterate the second level of results, which is the fileslot.
	foreach ($ruleresults AS $fileno => $fileres) {
	
		$slotlist = array_keys($fileres);

		// Get start and end slot number within the file, based on the fileslot.
		$start = $datehandler->toSlot($datehandler->fromSlot($fileno, $statrules[$rulename]['fileslot']), $statrules[$rulename]['slot']);
		$end = $datehandler->toSlot($datehandler->fromSlot($fileno+1, $statrules[$rulename]['fileslot']), $statrules[$rulename]['slot']);

		// Fill in missing entries and sort file results
		$filledresult = array();
		for ($slot = $start; $slot < $end; $slot++) {
			$filledresult[$slot] = (isset($fileres[$slot])) ? $fileres[$slot] : array('_' => 0);
		}
		
		// store file
		file_put_contents($statdir . $rulename . '-' . $fileno . '.stat', serialize($filledresult) );
	}
}

?>