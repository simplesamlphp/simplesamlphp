<?php

/**
 * This page shows a username/password/organization login form, and passes information from
 * itto the sspmod_core_Auth_UserPassBase class, which is a generic class for
 * username/password/organization authentication.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];
$organizations = sspmod_core_Auth_UserPassOrgBase::listOrganizations($authStateId);

if (array_key_exists('username', $_REQUEST)) {
	$username = $_REQUEST['username'];
} elseif (isset($state['core:username'])) {
	$username = (string)$state['core:username'];
} else {
	$username = '';
}

if (array_key_exists('password', $_REQUEST)) {
	$password = $_REQUEST['password'];
} else {
	$password = '';
}

if (array_key_exists('organization', $_REQUEST)) {
	$organization = $_REQUEST['organization'];
} elseif (isset($state['core:organization'])) {
	$organization = (string)$state['core:organization'];
} else {
	$organization = '';
}

$errorCode = NULL;
if ($organizations === NULL || !empty($organization)) {
	if (!empty($username) && !empty($password)) {
		$errorCode = sspmod_core_Auth_UserPassOrgBase::handleLogin($authStateId, $username, $password, $organization);
	}
}

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:loginuserpass.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['username'] = $username;
$t->data['forceUsername'] = FALSE;
$t->data['errorcode'] = $errorCode;

if ($organizations !== NULL) {
	$t->data['selectedOrg'] = $organization;
	$t->data['organizations'] = $organizations;
}

if (isset($state['SPMetadata'])) {
	$t->data['SPMetadata'] = $state['SPMetadata'];
} else {
	$t->data['SPMetadata'] = NULL;
}

$t->show();
exit();


?>