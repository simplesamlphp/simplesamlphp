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

$aggr = new sspmod_statistics_Aggregator();
$aggr->loadMetadata();
$metadata = $aggr->getMetadata();

// echo('<pre>'); print_r($metadata);

/**
 * AUTHENTICATION and Authorization for access to the statistics.  ------
 */

$t = new SimpleSAML_XHTML_Template($config, 'statistics:statmeta-tpl.php');
$t->data['metadata'] =  $metadata;
$t->show();

