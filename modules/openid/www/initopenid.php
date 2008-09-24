<?php

/**
 * The _include script registers a autoloader for the simpleSAMLphp libraries. It also
 * initializes the simpleSAMLphp config class with the correct path.
 */
require_once('_include.php');


/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

if (empty($_REQUEST['RelayState'])) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

if (! $session->isValid('openid') ) {
	/* Authenticate with an AuthSource. */
	$hints = array();
	if (array_key_exists('openid', $_REQUEST)) $hints['openid'] = $_REQUEST['openid'];
	SimpleSAML_Auth_Default::initLogin('openid', $_REQUEST['RelayState'], NULL, $hints);
}


?>