<?php
/**
 * This file is part of SimpleSAMLphp. See the file COPYING in the
 * root of the distribution for licence information.
 *
 * This file implements authentication of users using LDAP. Which LDAP
 * server to do bind against is decided based on the users home
 * organization.
 *
 * First a search is done on the users eduPersonPrincipalName (ePPN). Only
 * one user with the ePPN should exist. After the DN of the user is found
 * a LDAP bind is used to authenticate the user and fetch the attributes.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @author Anders Lund, UNINETT AS. <anders.lund@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */


require_once('../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

require_once('SimpleSAML/Auth/LDAP.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

$ldapconfigfile = $config->getBaseDir() . 'config/ldapfeide.php';
require_once($ldapconfigfile);

SimpleSAML_Logger::info('AUTH - ldap-feide: Accessing auth endpoint login-feide');

$error = null;
$attributes = array();

/*
 * Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
        SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

if (isset($_REQUEST['username'])) {	
	try {
		$requestedOrg = null;
		$requestedUser = strtolower($_REQUEST['username']);
		
		/*
		 * Checking username parameter for illegal characters.
		 */
		if (!preg_match('/^[a-z0-9._]+(@[a-z0-9._]+)?$/', $requestedUser) ) 
			throw new Exception('Illegal characters in (or empty) username.');
		
		/*
		 * Split username and organization if user input includes @.
		 */
		if (strstr($requestedUser, '@')) {
			$decomposed = explode('@', $requestedUser);
			$requestedUser = $decomposed[0];
			$requestedOrg = $decomposed[1];
		}
		
		/*
		 * Checking organization parameter.
		 */		
		if (empty($requestedOrg) ) {		
			if (empty($_REQUEST['org'])) 
				throw new Exception('Organization parameter is not set.');
			
			$requestedOrg = strtolower($_REQUEST['org']);
		}

		if (!preg_match('/^[a-z0-9.]*$/', $requestedOrg) ) 
			throw new Exception('Illegal characters in organization.');

		if (!array_key_exists($requestedOrg, $ldapfeide))
			throw new Exception('Organization ' . $requestedOrg . ' does not exist in configuration.');
		
		$ldapconfig = $ldapfeide[$requestedOrg];
		
		/*
		 * Checking password parameter.
		 */
		if (empty($_REQUEST['password']))
			throw new Exception('The password field was left empty. Please fill in a valid password.');
		
		$password = $_REQUEST['password'];
		
		if (!preg_match('/^[a-zA-Z0-9.]+$/', $password) ) 
			throw new Exception('Illegal characters in password.');
		
		/*
		 * Connecting to LDAP.
		 */
		$ldap = new SimpleSAML_Auth_LDAP($ldapconfig['hostname']);

		/*
		 * Search for eduPersonPrincipalName.
		 */
		$eppn = $requestedUser."@".$requestedOrg;
		$dn = $ldap->searchfordn($ldapconfig['searchbase'],'eduPersonPrincipalName', $eppn);

		/*
		 * Do LDAP bind using DN found from the search on ePPN.
		 */
		if (!$ldap->bind($dn, $password)) {
			SimpleSAML_Logger::notice('AUTH - ldap-feide: '. $requestedUser . ' failed to authenticate. DN=' . $dn);
			throw new Exception('Wrong username or password');
		}

		/*
		 * Retrieve attributes from LDAP
		 */
		$attributes = $ldap->getAttributes($dn, $ldapconfig['attributes']);

		SimpleSAML_Logger::notice('AUTH - ldap-feide: '. $requestedUser . ' successfully authenticated');
		
		$session->setAuthenticated(true, 'login-feide');
		$session->setAttributes($attributes);
		
		$session->setNameID(array(
			'value' => SimpleSAML_Utilities::generateID(),
			'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'));
		
		
		/**
		 * Create a statistics log entry for every successfull login attempt.
		 * Also log a specific attribute as set in the config: statistics.authlogattr
		 */
		$authlogattr = $config->getValue('statistics.authlogattr', null);
		if ($authlogattr && array_key_exists($authlogattr, $attributes)) 
			SimpleSAML_Logger::stats('AUTH-login-feide OK ' . $attributes[$authlogattr][0]);
		else 
			SimpleSAML_Logger::stats('AUTH-login-feide OK');
		
		
		$returnto = $_REQUEST['RelayState'];
		SimpleSAML_Utilities::redirect($returnto);

		
	} catch (Exception $e) {
		SimpleSAML_Logger::error('AUTH - ldap-feide: User: '.(isset($requestedUser) ? $requestedUser : 'na'). ':'. $e->getMessage());
		SimpleSAML_Logger::stats('AUTH-login-feide Failed');
		$error = $e->getMessage();
	}
}


$t = new SimpleSAML_XHTML_Template($config, 'login-ldapmulti.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['ldapconfig'] = $ldapfeide;
$t->data['org'] = isset($_REQUEST['org']) ? $_REQUEST['org'] : null;
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();

?>
