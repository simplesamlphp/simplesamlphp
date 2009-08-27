<?php

$config = SimpleSAML_Configuration::getInstance();
$statconfig = SimpleSAML_Configuration::getConfig('module_statistics.php');
$session = SimpleSAML_Session::getInstance();


/**
 * AUTHENTICATION and Authorization for access to the statistics.
 */
$protected = $statconfig->getBoolean('protected', FALSE);
$authsource = $statconfig->getString('auth', NULL);
$allowedusers = $statconfig->getValue('allowedUsers', NULL);
$useridattr = $statconfig->getString('useridattr', 'eduPersonPrincipalName');

$acl = $statconfig->getValue('acl', NULL);
if ($acl !== NULL && !is_string($acl) && !is_array($acl)) {
	throw new SimpleSAML_Error_Exception('Invalid value for \'acl\'-option. Should be an array or a string.');
}

if ($protected) {

	if (SimpleSAML_Utilities::isAdmin()) {
		// User logged in as admin. OK.
		SimpleSAML_Logger::debug('Statistics auth - logged in as admin, access granted');
		
	} elseif(isset($authsource) && $session->isValid($authsource) ) {
	
		// User logged in with auth source.
		SimpleSAML_Logger::debug('Statistics auth - valid login with auth source [' . $authsource . ']');
		
		// Retrieving attributes
		$attributes = $session->getAttributes();

		$allow = FALSE;
		if (!empty($allowedusers)) {
			// Check if userid exists
			if (!isset($attributes[$useridattr][0]))
				throw new Exception('User ID is missing');

			// Check if userid is allowed access..
			if (!in_array($attributes[$useridattr][0], $allowedusers)) {
				SimpleSAML_Logger::debug('Statistics auth - User denied access by user ID [' . $attributes[$useridattr][0] . ']');
			} else {
				SimpleSAML_Logger::debug('Statistics auth - User granted access by user ID [' . $attributes[$useridattr][0] . ']');
				$allow = TRUE;
			}
		} else {
			SimpleSAML_Logger::debug('Statistics auth - no allowedUsers list.');
		}

		if (!$allow && !is_null($acl)) {
			$acl = new sspmod_core_ACL($acl);
			if (!$acl->allows($attributes)) {
				SimpleSAML_Logger::debug('Statistics auth - denied access by ACL.');
			} else {
				SimpleSAML_Logger::debug('Statistics auth - allowed access by ACL.');
				$allow = TRUE;
			}
		} else {
			SimpleSAML_Logger::debug('Statistics auth - no ACL configured.');
		}

		if (!$allow) {
			throw new SimpleSAML_Error_Exception('Access denied to the current user.');
		}

	} elseif(isset($authsource)) {
		// If user is not logged in init login with authrouce if authsousrce is defined.
		SimpleSAML_Auth_Default::initLogin($authsource, SimpleSAML_Utilities::selfURL());
		
	} else {
		// If authsource is not defined, init admin login.
		SimpleSAML_Utilities::requireAdmin();
	}
}
/**
 * AUTHENTICATION and Authorization for access to the statistics.  ------
 */



/*
 * Check input parameters
 */
$preferRule = NULL;
$preferRule2 = NULL;
$preferTime = NULL;
$preferTimeRes = NULL;
$delimiter = NULL;
if(array_key_exists('rule', $_REQUEST)) $preferRule = $_REQUEST['rule'];
if(array_key_exists('rule2', $_REQUEST)) $preferRule2 = $_REQUEST['rule2'];
if(array_key_exists('time', $_REQUEST)) $preferTime = $_REQUEST['time'];
if(array_key_exists('res', $_REQUEST)) $preferTimeRes = $_REQUEST['res'];
if(array_key_exists('d', $_REQUEST)) $delimiter = $_REQUEST['d'];

if ($preferRule2 === '_') $preferRule2 = NULL;

/*
 * Create statistics data.
 */
$ruleset = new sspmod_statistics_Ruleset($statconfig);
$statrule = $ruleset->getRule($preferRule);
$rule = $statrule->getRuleID();

$dataset = $statrule->getDataset($preferTimeRes, $preferTime);
$dataset->setDelimiter($delimiter);

$delimiter = $dataset->getDelimiter();

$timeres = $dataset->getTimeRes();
$fileslot = $dataset->getFileslot();
$availableFileSlots = $statrule->availableFileSlots($timeres);

$timeNavigation = $statrule->getTimeNavigation($timeres, $preferTime);

$dataset->aggregateSummary();
$dataset->calculateMax();




$piedata = $dataset->getPieData();

$datasets = array();
$datasets[] = $dataset->getPercentValues();

$axis = $dataset->getAxis();

$maxes = array();

$maxes[] = $dataset->getMax();


if (isset($preferRule2)) {
	$statrule = $ruleset->getRule($preferRule2);
#	$rule2 = $statrule->getRuleID();
	$dataset2 = $statrule->getDataset($preferTimeRes, $preferTime);
	$dataset2->aggregateSummary();
	$dataset2->calculateMax();
	
	$datasets[] = $dataset2->getPercentValues();
	$maxes[] = $dataset2->getMax();
}








$dimx = $statconfig->getValue('dimension.x', 800);
$dimy = $statconfig->getValue('dimension.y', 350);
$grapher = new sspmod_statistics_Graph_GoogleCharts($dimx, $dimy);

if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] === 'csv') {

	header('Content-type: text/csv');
	header('Content-Disposition: attachment; filename="simplesamlphp-data.csv"');
	$data = $dataset->getDebugData();
	foreach($data AS $de) {
		if (isset($de[1])) {
			echo('"' . $de[0] . '",' . $de[1] . "\n");
		}
	}
	exit;
}



$t = new SimpleSAML_XHTML_Template($config, 'statistics:statistics-tpl.php');
$t->data['pageid'] = 'statistics';
$t->data['header'] = 'stat';
$t->data['imgurl'] = $grapher->show($axis['axis'], $axis['axispos'], $datasets, $maxes);
if (isset($piedata)) {
	$t->data['pieimgurl'] = $grapher->showPie( $dataset->getDelimiterPresentationPie(), $piedata);
}
$t->data['available.rules'] = $ruleset->availableRulesNames();
$t->data['available.times'] = $statrule->availableFileSlots($timeres);
$t->data['available.timeres'] = $statrule->availableTimeRes();
$t->data['available.times.prev'] = $timeNavigation['prev'];
$t->data['available.times.next'] = $timeNavigation['next'];

$t->data['selected.rule']= $rule;
$t->data['selected.rule2']= $preferRule2;
$t->data['selected.time'] = $fileslot;
$t->data['selected.timeres'] = $timeres;
$t->data['selected.delimiter'] = $delimiter;

$t->data['debugdata'] = $dataset->getDebugData();
$t->data['results'] = $dataset->getResults();
$t->data['summaryDataset'] = $dataset->getSummary();
$t->data['topdelimiters'] = $dataset->getTopDelimiters();
$t->data['availdelimiters'] = $dataset->availDelimiters();

$t->data['delimiterPresentation'] =  $dataset->getDelimiterPresentation();
$t->show();

