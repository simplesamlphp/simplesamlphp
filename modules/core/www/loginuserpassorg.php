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

if (array_key_exists('username', $_REQUEST)) {
	$username = $_REQUEST['username'];
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
} else {
	$organization = NULL;
}

if (!empty($organization) && (!empty($username) || !empty($password))) {
	/* Organization and either username or password set - attempt to log in. */
	$errorCode = sspmod_core_Auth_UserPassOrgBase::handleLogin($authStateId, $username, $password, $organization);
} else {
	$errorCode = NULL;
}

$organizations = sspmod_core_Auth_UserPassOrgBase::listOrganizations($authStateId);

$globalConfig = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:loginuserpass.php');
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['selectedOrg'] = $organization;
$t->data['organizations'] = $organizations;
$t->data['username'] = $username;
$t->data['errorcode'] = $errorCode;
$t->show();
exit();


?>