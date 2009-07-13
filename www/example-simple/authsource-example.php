<?php

/**
 * The _include script registers a autoloader for the simpleSAMLphp libraries. It also
 * initializes the simpleSAMLphp config class with the correct path.
 */
require_once('../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();


if(array_key_exists('logout', $_REQUEST)) {
	SimpleSAML_Auth_Default::initLogout('/' . $config->getBaseURL() . 'logout.php');
}

if (array_key_exists(SimpleSAML_Auth_State::EXCEPTION_PARAM, $_REQUEST)) {
	/* This is just a simple example of an error. */

	$state = SimpleSAML_Auth_State::loadExceptionState();
	assert('array_key_exists(SimpleSAML_Auth_State::EXCEPTION_DATA, $state)');
	$e = $state[SimpleSAML_Auth_State::EXCEPTION_DATA];

	header('Content-Type: text/plain');
	echo "Exception during login:\n";
	foreach ($e->format() as $line) {
		echo $line . "\n";
	}
	exit(0);
}

if(!array_key_exists('as', $_REQUEST)) {
	throw new Exception('No authentication source chosen.');
}

$as = $_REQUEST['as'];

if (!$session->isValid($as)) {
	SimpleSAML_Auth_Default::initLogin($as, SimpleSAML_Utilities::selfURL(), SimpleSAML_Utilities::selfURL());
}

$attributes = $session->getAttributes();

$t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes');

$t->data['header'] = '{status:header_saml20_sp}';
$t->data['remaining'] = $session->remainingTime();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = $attributes;
$t->data['logouturl'] = SimpleSAML_Utilities::selfURLNoQuery() . '?logout';
$t->data['icon'] = 'bino.png';
$t->show();



?>