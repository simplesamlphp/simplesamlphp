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

if ($protected) {

	if (SimpleSAML_Utilities::isAdmin()) {
		// User logged in as admin. OK.
		SimpleSAML_Logger::debug('Statistics auth - logged in as admin, access granted');
		
	} elseif(isset($authsource) && $session->isValid($authsource) ) {
	
		// User logged in with auth source.
		SimpleSAML_Logger::debug('Statistics auth - valid login with auth source [' . $authsource . ']');
		
		// Retrieving attributes
		$attributes = $session->getAttributes();
		
		// Check if userid exists
		if (!isset($attributes[$useridattr])) 
			throw new Exception('User ID is missing');
		
		// Check if userid is allowed access..
		if (!in_array($attributes[$useridattr][0], $allowedusers)) {
			SimpleSAML_Logger::debug('Statistics auth - User denied access by user ID [' . $attributes[$useridattr][0] . ']');
			throw new Exception('Access denied for this user.');
		}
		SimpleSAML_Logger::debug('Statistics auth - User granted access by user ID [' . $attributes[$useridattr][0] . ']');		
		
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
$preferTime = NULL;
$preferTimeRes = NULL;
$delimiter = NULL;
if(array_key_exists('rule', $_GET)) $preferRule = $_GET['rule'];
if(array_key_exists('time', $_GET)) $preferTime = $_GET['time'];
if(array_key_exists('res', $_GET)) $preferTimeRes = $_GET['res'];
if(array_key_exists('d', $_GET)) $delimiter = $_GET['d'];


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
$max = $dataset->getMax();


$dimx = $statconfig->getValue('dimension.x', 800);
$dimy = $statconfig->getValue('dimension.y', 350);
$grapher = new sspmod_statistics_Graph_GoogleCharts($dimx, $dimy);

$htmlContentPre = array(); $htmlContentPost = array(); $htmlContentHead = array(); $jquery = array();
$hookinfo = array('pre' => &$htmlContentPre, 'post' => &$htmlContentPost, 'head' => &$htmlContentHead, 'jquery' => &$jquery, 'page' => 'statistics');
SimpleSAML_Module::callHooks('htmlinject', $hookinfo);


$t = new SimpleSAML_XHTML_Template($config, 'statistics:statistics-tpl.php');
$t->data['header'] = 'stat';
$t->data['imgurl'] = $grapher->show($axis['axis'], $axis['axispos'], $datasets, $max);
$t->data['pieimgurl'] = $grapher->showPie( $dataset->getDelimiterPresentationPie(), $piedata);
$t->data['available.rules'] = $ruleset->availableRulesNames();
$t->data['available.times'] = $statrule->availableFileSlots($timeres);
$t->data['available.timeres'] = $statrule->availableTimeRes();
$t->data['available.times.prev'] = $timeNavigation['prev'];
$t->data['available.times.next'] = $timeNavigation['next'];
$t->data['htmlContentPre'] = $htmlContentPre;
$t->data['htmlContentPost'] = $htmlContentPost;
$t->data['htmlContentHead'] = $htmlContentHead;
$t->data['jquery'] = $jquery;
$t->data['selected.rule']= $rule;
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

