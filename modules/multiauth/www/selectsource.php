<?php

/**
 * This page shows a list of authentication sources. When the user selects
 * one of them if pass this information to the
 * sspmod_multiauth_Auth_Source_MultiAuth class and call the
 * delegateAuthentication method on it.
 *
 * @author Lorenzo Gil, Yaco Sistemas S.L.
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];

/* Retrieve the authentication state. */
$state = SimpleSAML_Auth_State::loadState($authStateId, sspmod_multiauth_Auth_Source_MultiAuth::STAGEID);

if (array_key_exists('source', $_REQUEST)) {
	$source = $_REQUEST['source'];
	sspmod_multiauth_Auth_Source_MultiAuth::delegateAuthentication($source, $state);
} elseif (array_key_exists('multiauth:preselect', $state)) {
	$source = $state['multiauth:preselect'];
	sspmod_multiauth_Auth_Source_MultiAuth::delegateAuthentication($source, $state);
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'multiauth:selectsource.php');
$t->data['authstate'] = $authStateId;
$t->data['sources'] = $state[sspmod_multiauth_Auth_Source_MultiAuth::SOURCESID];
$t->show();
exit();

?>