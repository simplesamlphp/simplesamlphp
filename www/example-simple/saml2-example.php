<?php

/**
 * The _include script sets simpleSAMLphp libraries in the PHP PATH, as well as 
 * initialize the simpleSAMLphp config class with the correct path.
 */
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . '../_include.php');

/**
 * We need to load a few classes from simpleSAMLphp. These are available because
 * the _include script above did set the PHP class PATH properly.
 */
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');


/* Load simpleSAMLphp, configuration and metadata */
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance(TRUE);

/**
 * Check if valid local session exists, and the authority is the SAML 2.0 SP
 * part of simpleSAMLphp. If the currenct session is not valid, the user is
 * redirected to the initSSO.php script. This script will send the user to
 * a SAML 2.0 IdP with an authentication request, and thereafter the user
 * will be asked at the SAML 2.0 IdP to authenticate. You add one important
 * parameter when you send the user to the initSSO script, the RelayState.
 * The RelayState URL is the URL that you want to send the user to after
 * authentication is complete - and usually you want to send the user back
 * to this very page. To get the URL of the current page we use the selfURL()
 * helper function.
 *
 * When the user is complete authenticating at the IdP, the user will be sent
 * back to the AssertionConsumerService.php script in simpleSAMLphp. The assertion
 * is validated, and if trusted, the user's session is set to be valid, and the user
 * is redirected back to the RelayState URL. And then the user is here again, but 
 * authenticated, and therefore passes the if sentence below, and moves on to 
 * retrieving attributes from the session.
 */
if (!$session->isValid('saml2') ) {
	SimpleSAML_Utilities::redirect(
		'/' . $config->getBaseURL() . 'saml2/sp/initSSO.php',
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

$t = new SimpleSAML_XHTML_Template($config, 'status.php', 'attributes.php');

$t->data['header'] = 'SAML 2.0 SP Demo Example';
$t->data['remaining'] = $session->remainingTime();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = $attributes;
$t->data['icon'] = 'bino.png';
$t->data['logout'] = '<p>[ <a href="/' . $config->getBaseURL() . 'saml2/sp/initSLO.php?RelayState=/' . 
	$config->getBaseURL() . 'logout.html">Logout</a> ]';
$t->show();


?>