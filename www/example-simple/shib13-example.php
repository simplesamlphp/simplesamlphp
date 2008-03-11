<?php

/**
 * The _include script sets simpleSAMLphp libraries in the PHP PATH, as well as 
 * initialize the simpleSAMLphp config class with the correct path.
 */
require_once('../_include.php');

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
 * Check if valid local session exists, and the authority is the Shib 1.3 SP
 * part of simpleSAMLphp. If the currenct session is not valid, the user is
 * redirected to the initSSO.php script. This script will send the user to
 * a Shib 1.3 IdP with an authentication request, and thereafter the user
 * will be asked at the Shib 1.3 IdP to authenticate. You add one important
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
if (!isset($session) || !$session->isValid('shib13') ) {	
	SimpleSAML_Utilities::redirect(
		'/' . $config->getBaseURL() . 'shib13/sp/initSSO.php',
		array('RelayState' => SimpleSAML_Utilities::selfURL())
	);
}

$t = new SimpleSAML_XHTML_Template($config, 'status.php');

$t->data['header'] = 'Shibboleth demo';
$t->data['remaining'] = $session->remainingTime();
$t->data['attributes'] = $session->getAttributes();
$t->data['logout'] = 'Shibboleth logout not implemented yet.';
$et->data['icon'] = 'bino.png';
$t->show();


?>
