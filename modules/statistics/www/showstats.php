<?php


function encodeaxis($axis) {
	return join('|', $axis);
}
# t:10.0,58.0,95.0
function encodedata($data) {
	return 't:' . join(',', $data);
}

function show($axis, $axispos, $values, $max) {

	$nv = count($values);

	$url = 'http://chart.apis.google.com/chart?' .
		'chs=800x350' .
		'&chd=' . encodedata($values) .
		'&cht=lc' .
		'&chxt=x,y' .
		'&chxl=0:|' . encodeaxis($axis) . # . $'|1:||top' .
		'&chxp=0,' . join(',', $axispos) . 
#		'&chxp=0,0.3,0.4' .
		'&chxr=0,0,1|1,0,' . $max . 
#		'&chm=R,CCCCCC,0,0.25,0.5' .
		'&chg=' . (2400/(count($values)-1)) . ',20,3,3';   // lines
		
	return $url;
}

function prettydate($timeslot, $granularity, $offset, $dateformat) {
#	echo 'date: [' . $dateformat . date($dateformat, $timeslot*$granularity-$offset) . ']';
	return date($dateformat, $timeslot*$granularity-$offset);
}

function roof($in) {
	if ($in < 1) return 1;
	$base = log10($in);
	$r =  ceil(5*$in / pow(10, ceil($base)));
	return ($r/5)*pow(10, ceil($base));
}

// $foo = array(0, 2, 2.3, 2.6, 6, 10, 15, 98, 198, 256, 487, 563, 763, 801, 899, 999, 987, 198234.485, 283746);
// foreach ($foo AS $f) {
// 	echo '<p>' . $f . ' => ' . roof($f);
// }
// exit;


$config = SimpleSAML_Configuration::getInstance();
$statconfig = $config->copyFromBase('statconfig', 'statistics.php');


$statdir = $statconfig->getValue('statdir');
$offset = $statconfig->getValue('offset');
$inputfile = $statconfig->getValue('inputfile');

$statrules = $statconfig->getValue('statrules');



if (!is_dir($statdir))
	throw new Exception('Statisics output directory [' . $statdir . '] does not exists.');
$filelist = scandir($statdir);

$available = array();
foreach ($filelist AS $file) {
	if (preg_match('/([a-z_]+)-([0-9]+)\.stat/', $file, $matches)) {

		if (array_key_exists($matches[1], $statrules)) {
			$available[$matches[1]][] = $matches[2];
		}
	}
}

$available_rules = array();
foreach ($available AS $key => $av) {
	$available_rules[$key] = array('name' => $statrules[$key]['name'], 'descr' => $statrules[$key]['descr']);
}

$availrulenames = array_keys($available_rules);
$rule = $availrulenames[0];
if(array_key_exists('rule', $_GET)) {
	if (array_key_exists($_GET['rule'], $available_rules)) {
		$rule = $_GET['rule'];
	}
}

$available_times = array(); 
foreach ($available[$rule] AS $slot) {
	$available_times[$slot] = prettydate($slot, $statrules[$rule]['fileslot'], $offset, $statrules[$rule]['dateformat-period']) . ' to ' .
		prettydate($slot+1, $statrules[$rule]['fileslot'], $offset, $statrules[$rule]['dateformat-period']);
}

#print_r($available_times); exit;

$fileslot = $available[$rule][count($available[$rule])-1];

if (array_key_exists('time', $_GET)) {
	if (in_array($_GET['time'], $available[$rule])) {
		$fileslot = $_GET['time'];
	}
}

#echo 'fileslot: ' . $fileslot; exit;
#echo '<pre>'; print_r($available_rules); exit;
#echo '<pre>'; print_r($available); exit;


$resultfile = file_get_contents($statdir . $rule . '-' . $fileslot . '.stat');
$results = unserialize($resultfile);


// echo '<html><body><pre>';
// print_r($results);
// echo '</pre>';

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

$maxvalue = 0;
$maxvaluetime = 0;
$debugdata = array();
foreach($results AS $slot => $res) {
	if ($res[$delimiter] > $maxvalue) { $maxvaluetime = prettydate($slot, $statrules[$rule]['slot'], $offset, $statrules[$rule]['dateformat-intra']); }
	$maxvalue = max($res[$delimiter],$maxvalue);
	
	$debugdata[] = array(
		prettydate($slot, $statrules[$rule]['slot'], $offset, $statrules[$rule]['dateformat-intra']),
		$res[$delimiter]
	);
}
$max = roof($maxvalue);

#echo 'Maxvalue [' .  $maxvalue . '] at time ' . $maxvaluetime; exit;


#echo '<pre>'; print_r($debugdata); exit;

$availdelimiters = array();

$lastslot = 0;
$xentries = count($results);
$i = 0;
foreach($results AS $slot => $res) {

	#echo '<p>' . date($dateformat, $slot*$granularity) . ': ' . (isset($results[$slot]) ? $results[$slot] : 0);

	$dataset[] = number_format(100*$res[$delimiter] / $max, 2);
	foreach(array_keys($res) AS $nd) $availdelimiters[$nd] = 1;

	#$dataset[] = (isset($results[$slot]) ? round(($results[$slot]*$perseconds/($granularity*$max))*100) : 0);
	if ($slot % $axislabelint == 0)  {
		$axis[] = date($dateformat_intra, $slot*$slotsize - $offset);
		$axispos[] = (($i)/($xentries-1));
		#echo "<p> ". $slot . " = " . date($dateformat_intra, ($slot*$slotsize - $offset) ) . " ";
	}
	#echo "<p> ". $slot . " = " . date($dateformat_intra, ($slot*$slotsize - $offset) ) . " ";
	$lastslot = $slot;
	$i++;
}
$axis[] = date($dateformat_intra, ($lastslot*$slotsize) + $slotsize - $offset);
#echo "<p> ". ($lastslot+1) . " = " . date($dateformat_intra, (($lastslot+1)*$slotsize - $offset) ) . " ";

#print_r($axis);

// echo '<input value="' . htmlspecialchars(show($axis, $dataset, $max)) . '" />';
// echo '<img src="' . htmlspecialchars() . '" />';

$t = new SimpleSAML_XHTML_Template($config, 'statistics:statistics-tpl.php');
$t->data['header'] = 'stat';
$t->data['imgurl'] = show($axis, $axispos, $dataset, $max);
$t->data['available.rules'] = $available_rules;
$t->data['available.times'] = $available_times;
$t->data['selected.rule']= $rule;
$t->data['selected.time'] = $fileslot;
$t->data['debugdata'] = $debugdata;
$t->data['availdelimiters'] = array_keys($availdelimiters);
$t->show();


// $slotlist = array_keys($results);
// $start = $slotlist[0];
// $end   = $slotlist[count($results)-1];
// 
// #echo 'from slot ' . $start . ' to ' . $end;
// 
// $dataset = array();
// $axis = array();
// $max = 10;
// 
// $perseconds = 60;
// 
// for ($slot = $start; $slot <= $end; $slot++) {
// 	#echo '<p>' . date($dateformat, $slot*$granularity) . ': ' . (isset($results[$slot]) ? $results[$slot] : 0);
// 
// 	$dataset[] = (isset($results[$slot]) ? round(($results[$slot]*$perseconds/($granularity*$max))*100) : 0);
// 	if ($slot % 3 == 0) 
// 		$axis[] = date($dateformat, $slot*$granularity);
// }
// 
// echo '<input value="' . htmlspecialchars(show($axis, $dataset)) . '" />';
// echo '<img src="' . htmlspecialchars(show($axis, $dataset, $max)) . '" />';


?>