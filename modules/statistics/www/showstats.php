<?php

$config = SimpleSAML_Configuration::getInstance();
$statconfig = SimpleSAML_Configuration::getConfig('module_statistics.php');

$statdir = $statconfig->getValue('statdir');
$inputfile = $statconfig->getValue('inputfile');
$statrules = $statconfig->getValue('statrules');

$datehandler = new sspmod_statistics_DateHandler($statconfig->getValue('offset', 0));



/*
 * Walk through file lists, and get available [rule][fileslot]...
 */
if (!is_dir($statdir))
	throw new Exception('Statisics output directory [' . $statdir . '] does not exists.');
$filelist = scandir($statdir);
$available = array();
foreach ($filelist AS $file) {
	if (preg_match('/([a-z0-9_]+)-([0-9]+)\.stat/', $file, $matches)) {
		if (array_key_exists($matches[1], $statrules)) {
			$available[$matches[1]][] = $matches[2];
		}
	}
}
if (empty($available)) 
	throw new Exception('No aggregated statistics files found in [' . $statdir . ']');

/*
 * Create array with information about available rules..
 */
$available_rules = array();
foreach ($available AS $key => $av) {
	$available_rules[$key] = array('name' => $statrules[$key]['name'], 'descr' => $statrules[$key]['descr']);
}
$availrulenames = array_keys($available_rules);

// Get selected rulename....
$rule = $availrulenames[0];
if(array_key_exists('rule', $_GET)) {
	if (array_key_exists($_GET['rule'], $available_rules)) {
		$rule = $_GET['rule'];
	}
}

/*
 * Get list of avaiable times in current file (rule)
 */
$available_times = array(); 
foreach ($available[$rule] AS $slot) {
	$available_times[$slot] = $datehandler->prettyDateSlot($slot, $statrules[$rule]['fileslot'], $statrules[$rule]['dateformat-period']) . 
		' to ' . $datehandler->prettyDateSlot($slot+1, $statrules[$rule]['fileslot'], $statrules[$rule]['dateformat-period']);
}

// Get which time (fileslot) to use.. First get a default, which is the most recent one.
$fileslot = $available[$rule][count($available[$rule])-1];
// Then check if the user have provided one.
if (array_key_exists('time', $_GET)) {
	if (in_array($_GET['time'], $available[$rule])) {
		$fileslot = $_GET['time'];
	}
}

// Get file and extract results.
$resultFileName = $statdir . $rule . '-' . $fileslot . '.stat';
if (!file_exists($resultFileName))
	throw new Exception('Aggregated statitics file [' . $resultFileName . '] not found.');
if (!is_readable($resultFileName))
	throw new Exception('Could not read statitics file [' . $resultFileName . ']. Bad file permissions?');
$resultfile = file_get_contents($resultFileName);
$results = unserialize($resultfile);
if (empty($results))
	throw new Exception('Aggregated statistics in file [' . $resultFileName . '] was empty.');

$dataset = array();
$axis = array();
$axispos = array();
$max = 15;


/*
 * Get rule specific configuration from the configuration file.
 */
$slotsize = $statrules[$rule]['slot'];
$dateformat_period = $statrules[$rule]['dateformat-period'];
$dateformat_intra = $statrules[$rule]['dateformat-intra'];
$axislabelint = $statrules[$rule]['axislabelint'];

$delimiter = '_';
if (isset($_REQUEST['d'])) {
	$delimiter = $_REQUEST['d'];
}

/*
 * Walk through dataset to get the max values.
 */
$maxvalue = 0;
$maxvaluetime = 0;
$debugdata = array();
foreach($results AS $slot => &$res) {
	if (!array_key_exists($delimiter, $res)) $res[$delimiter] = 0;
	if ($res[$delimiter] > $maxvalue) { 
		$maxvaluetime = $datehandler->prettyDateSlot($slot, $slotsize, $dateformat_intra); 
	}
	$maxvalue = max($res[$delimiter],$maxvalue);
	$debugdata[] = array($datehandler->prettyDateSlot($slot, $slotsize, $dateformat_intra), $res[$delimiter] );
}
$max = sspmod_statistics_Graph_GoogleCharts::roof($maxvalue);

#echo 'Maxvalue [' .  $maxvalue . '] at time ' . $maxvaluetime; exit;
#echo '<pre>'; print_r($debugdata); exit;




/**
 * Aggregate summary table from dataset. To be used in the table view.
 */
$summaryDataset = array();
foreach($results AS $slot => $res) {
	foreach ($res AS $key => $value) {
		if (array_key_exists($key, $summaryDataset)) {
			$summaryDataset[$key] += $value;
		} else {
			$summaryDataset[$key] = $value;
		}
	}
}
asort($summaryDataset);
$summaryDataset = array_reverse($summaryDataset, TRUE);
#echo '<pre>'; print_r($summaryDataset); exit;


/*
 * Walk through dataset to get percent values from max into dataset[].
 */
$availdelimiters = array();
$xentries = count($results);
$lastslot = 0; $i = 0;

foreach($results AS $slot => $res) {

	$dataset[] = number_format(100*$res[$delimiter] / $max, 2);
	foreach(array_keys($res) AS $nd) $availdelimiters[$nd] = 1;

	// check if there should be an axis here...
	if ( $slot % $axislabelint == 0)  {
		$axis[] =  $datehandler->prettyDateSlot($slot, $slotsize, $dateformat_intra);
		$axispos[] = (($i)/($xentries-1));
		
		#echo 'set axis on [' . $slot . ']';
	}
	$lastslot = $slot;
	$i++;
}
#echo 'set axis on lastslot [' . $lastslot . ']';
$axis[] =  $datehandler->prettyDateSlot($lastslot+1, $slotsize, $dateformat_intra); 
#print_r($axis);

$dimx = $statconfig->getValue('dimension.x', 800);
$dimy = $statconfig->getValue('dimension.y', 350);
$grapher = new sspmod_statistics_Graph_GoogleCharts($dimx, $dimy);

$t = new SimpleSAML_XHTML_Template($config, 'statistics:statistics-tpl.php');
$t->data['header'] = 'stat';
$t->data['imgurl'] = $grapher->show($axis, $axispos, $dataset, $max);
$t->data['available.rules'] = $available_rules;
$t->data['available.times'] = $available_times;
$t->data['selected.rule']= $rule;
$t->data['selected.time'] = $fileslot;
$t->data['debugdata'] = $debugdata;
$t->data['summaryDataset'] = $summaryDataset;
$t->data['availdelimiters'] = array_keys($availdelimiters);
$t->show();

