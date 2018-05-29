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

if (array_key_exists('completeLogout', $_REQUEST)) {
	$completeLogout = $_REQUEST['completeLogout'];
} else {
	$completeLogout = '';
}
$errorCode = NULL;
if ($completeLogout == "TRUE") {
	/*  attempt to log in. */
	$errorCode = sspmod_authjanrain_Auth_Source_JanrainRegistration::handleLogout($authStateId);
} 

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'authjanrain:janrainLogout.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['errorcode'] = $errorCode;
$t->show();
exit();


?>