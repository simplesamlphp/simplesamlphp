#!/usr/bin/env php
<?php

require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/www/_include.php');
require_once('../extlibs/loganalyzer.php');


echo 'Statistics directory   : ' . $statdir . "\n";
echo 'Input file             : ' . $inputfile . "\n";
echo 'Offset                 : ' . $offset . "\n";


// foreach ($results AS $rulename => $ruleresults) {
// 	foreach ($ruleresults AS $fileno => $fileres) {
// 		file_put_contents($statdir . $rulename . '-' . $fileno . '.stat', serialize($fileres) );
// 	}
// }

foreach ($results AS $slot => $val) {
	 foreach ($val AS $sp => $no) {
	 	echo $sp . " " . count($no) . " - ";
	 }
	 echo "\n";
}

echo "Results:\n";
#print_r($results);


?>