#!/usr/bin/env php
<?php

#$logfile = file_get_contents('/Users/andreas/Desktop/simplesamlphp.stat');
#int mktime ([ int $hour [, int $minute [, int $second [, int $month [, int $day [, int $year [, int $is_dst ]]]]]]] )
#http://chart.apis.google.com/chart?cht=lc&chs=200x125&chd=s:helloWorld&chxt=x,y&chxl=0:|Mar|Apr|May|June|July|1:||50+Kb



require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/www/_include.php');

$config = SimpleSAML_Configuration::getInstance();
$statconfig = $config->copyFromBase('statconfig', 'statistics.php');


$statdir = $statconfig->getValue('statdir');
$offset = $statconfig->getValue('offset');
$inputfile = $statconfig->getValue('inputfile');

echo 'Statistics directory   : ' . $statdir . "\n";
echo 'Input file             : ' . $inputfile . "\n";
echo 'Offset                 : ' . $offset . "\n";

$statrules = $statconfig->getValue('statrules');

$file = fopen($inputfile, 'r');
$logfile = file($inputfile, FILE_IGNORE_NEW_LINES );



# Aug 27 12:54:25 ssp 5 STAT [5416262207] saml20-sp-SSO urn:mace:feide.no:services:no.uninett.wiki-feide sam.feide.no NA
# 
#Oct 30 11:07:14 www1 simplesamlphp-foodle[12677]: 5 STAT [200b4679af] saml20-sp-SLO spinit urn:mace:feide.no:services:no.feide.foodle sam.feide.no

function parse15($str) {
	$di = date_parse($str);
	$datestamp = mktime($di['hour'], $di['minute'], $di['second'], $di['month'], $di['day']);	
	return $datestamp;
}

function parse23($str) {
	$timestamp = strtotime($str);
	return $timestamp;
}

$results = array();
# Sat, 16 Feb 08 00:55:11  (23 chars)
foreach ($logfile AS $logline) {
	$datenumbers = 15;

	$datestr = substr($logline,0,$datenumbers);
	#$datestr = substr($logline,0,23);
	$timestamp = parse15($datestr) + $offset;
	$restofline = substr($logline,$datenumbers+1);
	$restcols = split(' ', $restofline);
	$action = $restcols[5];
	
#	print_r($restcols); exit;
	
	foreach ($statrules AS $rulename => $rule) {
		$timeslot = floor($timestamp/$rule['slot']);
		$fileslot = floor($timestamp/$rule['fileslot']);
		if ($action !== $rule['action']) continue; 
		
		$difcol = $restcols[$rule['col']];
		$results[$rulename][$fileslot][$timeslot]['_']++;
		$results[$rulename][$fileslot][$timeslot][$difcol]++;
	}
}




echo "Results:\n";
print_r($results);



foreach ($results AS $rulename => $ruleresults) {
	foreach ($ruleresults AS $fileno => $fileres) {
	
		$slotlist = array_keys($fileres);
		$start = $slotlist[0];
		$start = $fileno*($statrules[$rulename]['fileslot'] / $statrules[$rulename]['slot']);
		#echo 'Start was set to ' . $start . ' instead consider ' . $fileno*($statrules[$rulename]['fileslot'] / $statrules[$rulename]['slot']) . "\n";

		$end   = $slotlist[count($fileres)-1];
		$end = ($fileno+1)*($statrules[$rulename]['fileslot'] / $statrules[$rulename]['slot']);
		#echo 'End   was set to ' . $end   . ' instead consider ' . ($fileno+1)*($statrules[$rulename]['fileslot'] / $statrules[$rulename]['slot']) . "\n";
// 		exit;		
// 		echo "From $start to $end \n";
		
		$filledresult = array();
		for ($slot = $start; $slot < $end; $slot++) {
			$filledresult[$slot] = (isset($fileres[$slot])) ? $fileres[$slot] : array('_' => 0);
		}
	
		file_put_contents($statdir . $rulename . '-' . $fileno . '.stat', serialize($filledresult) );
	}
}

// foreach ($results AS $rulename => $ruleresults) {
// 	foreach ($ruleresults AS $fileno => $fileres) {
// 		file_put_contents($statdir . $rulename . '-' . $fileno . '.stat', serialize($fileres) );
// 	}
// }


foreach ($results AS $slot => $val) {
	 echo date($dateformat, ($slot*$granularity)-$offset) . "\t" . $slot . "\t";
	 foreach ($val AS $sp => $no) {
	 	echo $sp . " " . $no . " - ";
	 }
	 echo "\n";
}



