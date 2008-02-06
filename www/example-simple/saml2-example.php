<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');


/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);

/* Check if valid local session exists.. */
if (!isset($session) || !$session->isValid('saml2') ) {
	SimpleSAML_Utilities::redirect(
		'/' . $config->getValue('baseurlpath') .
		'saml2/sp/initSSO.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
		);
}

$attributes = $session->getAttributes();

/*
 * The attributes variable now contains all the attributes. So this variable is basicly all you need to perform integration in 
 * your PHP application.
 * 
 * To debug the content of the attributes variable, do something like:
 *
 * print_r($attributes);
 *
 */

$et = new SimpleSAML_XHTML_Template($config, 'status.php');

$et->data['header'] = 'SAML 2.0 SP Demo Example';
$et->data['remaining'] = $session->remainingTime();
$et->data['sessionsize'] = $session->getSize();
$et->data['attributes'] = $attributes;
$et->data['valid'] = $session->isValid() ? 'Session is valid' : 'Session is invalid';
	$et->data['icon'] = 'bino.png';
$et->data['logout'] = '<p>[ <a href="/' . $config->getValue('baseurlpath') . 'saml2/sp/initSLO.php?RelayState=/' . 
	$config->getValue('baseurlpath') . 'logout.html">Logout</a> ]';

$et->show();


?>