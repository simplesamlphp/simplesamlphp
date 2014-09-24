<?php

/**
 * This page shows a login form, and passes information from it
 * to the sspmod_authJanrain_Auth_Source_JanrainRegistration class
 *
 * @author .
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];

if (array_key_exists('captureServerUrl', $_REQUEST)) {
	$captureServerUrl = $_REQUEST['captureServerUrl'];
} else {
	$captureServerUrl = '';
}
if (array_key_exists('captureToken', $_REQUEST)) {
	$captureToken = $_REQUEST['captureToken'];
} else {
	$captureToken = '';
}

if (!empty($captureToken) && !empty($captureServerUrl)) {
	/*  attempt to log in. */
	$errorCode = sspmod_authjanrain_Auth_Source_JanrainRegistration::handleLogin($authStateId, $captureServerUrl, $captureToken);
} else {
	$errorCode = NULL;
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'authjanrain:janrainWidget.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['errorcode'] = $errorCode;
$t->show();
exit();


?>