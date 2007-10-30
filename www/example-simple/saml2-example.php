<?php

require_once('../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');


session_start();


/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);
$session = SimpleSAML_Session::getInstance();

/* Check if valid local session exists.. */
if (!isset($session) || !$session->isValid() ) {
	header('Location: /' . $config->getValue('baseurlpath') . 'saml2/sp/initSSO.php?RelayState=' . urlencode(SimpleSAML_Utilities::selfURL()));
	exit(0);
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
$et->data['attributes'] = $attributes;
$et->data['valid'] = $session->isValid() ? 'Session is valid' : 'Session is invalid';

$et->data['logout'] = '<p>[ <a href="/' . $config->getValue('baseurlpath') . 'saml2/sp/initSLO.php?RelayState=/' . 
	$config->getValue('baseurlpath') . 'logout.html">Logout</a> ]';

$et->show();


?>