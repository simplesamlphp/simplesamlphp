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

$config = SimpleSAML_Configuration::getInstance();
$ldapconfig = $config->copyFromBase('loginfeide', 'config-login-feide.php');
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


SimpleSAML_Logger::info('AUTH - ldap-feide: Accessing auth endpoint login-feide');

$ldaporgconfig = $ldapconfig->getValue('orgldapconfig');


/*
 * Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
        SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

/*
 * Fetch information about the service the user is coming from.
 */
if (!array_key_exists('AuthId', $_REQUEST)) {
        SimpleSAML_Utilities::fatalError($session->getTrackID(), null, new Exception('This login module does not support local login without reference to a Login request'));
}
if (!array_key_exists('protocol', $_REQUEST)) {
        SimpleSAML_Utilities::fatalError($session->getTrackID(), null, new Exception('Protocol URL parameter was not set'));
}
if ($_REQUEST['protocol'] != 'saml2') {
        SimpleSAML_Utilities::fatalError($session->getTrackID(), null, new Exception('This login module only works with SAML 2.0'));
}

try {
        $protocol = $_REQUEST['protocol'];
        $authid = $_REQUEST['AuthId'];
        $authrequestcache = $session->getAuthnRequest($protocol, $authid);
} catch (Exception $e) {
        SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION', $e);
}
 
$spentityid = $authrequestcache['Issuer'];
$spmetadata = $metadata->getMetadata($spentityid, 'saml20-sp-remote');

/*
 * Find the list of allowed organizations.
 */
$allowedOrgs = array_keys($ldaporgconfig);
if(array_key_exists('feide.allowedorgs', $spmetadata)) {
	assert('is_array($spmetadata["feide.allowedorgs"])');
	$allowedOrgs = array_intersect($spmetadata['feide.allowedorgs'], $allowedOrgs);
}


$error = null;
$attributes = array();

$selectorg = true;
$org = null;

/**
 * Check if user has selected organization in this request.
 */
if (isset($_REQUEST['org'])) {
	$org = $_REQUEST['org'];
	// OrgCookie is set to expire in 30 days. If set to 0, or omitted, the cookie will expire at the end of the session (when the browser closes). 
	setcookie("OrgCookie", $_REQUEST['org'], time()+60*60*24*30);
	$selectorg = false;

/**
 * If user has not selected organization in this request, then check if the user
 * has stored the selected organization as a cookie.
 */
} elseif (isset($_COOKIE["OrgCookie"])) {
	$org = $_COOKIE["OrgCookie"];
	$selectorg = false;
}

/**
 * If the user has excplicitly selected to change the preselected organization.
 */
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'change_org') {
//	setcookie("OrgCookie", "", time() - 3600);
	$selectorg = true;
}

/*
 * The user may have previously selected an organization which the SP doesn't allow. Correct this.
 */
if ($selectorg === FALSE && !in_array($org, $allowedOrgs, TRUE)) {
	$selectorg = TRUE;
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

		if (!array_key_exists($requestedOrg, $ldaporgconfig))
			throw new Exception('Organization ' . $requestedOrg . ' does not exist in configuration.');
		
		$orgconfig = $ldaporgconfig[$requestedOrg];
		
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
		$ldap = new SimpleSAML_Auth_LDAP($orgconfig['hostname'], $orgconfig['enable_tls']);

		/*
		 * Search for eduPersonPrincipalName.
		 */
		if (isset($orgconfig['adminUser'])) {
			$ldap->bind($orgconfig['adminUser'], $orgconfig['adminPassword']);
		}
		 
		$eppn = $requestedUser."@".$requestedOrg;
		$dn = $ldap->searchfordn($orgconfig['searchbase'], 'eduPersonPrincipalName', $eppn);

		/*
		 * Do LDAP bind using DN found from the search on ePPN.
		 */
		if (!$ldap->bind($dn, $password)) {
			SimpleSAML_Logger::info('AUTH - ldap-feide: '. $requestedUser . ' failed to authenticate. DN=' . $dn);
			throw new Exception('Wrong username or password');
		}

		/*
		 * Retrieve attributes from LDAP
		 */
		$attributes = $ldap->getAttributes($dn, $orgconfig['attributes']);
		
		
		/**
		 * Retrieve organizational attributes, if the eduPersonOrgDN attribute is set.
		 */
		if (isset($attributes['eduPersonOrgDN'])) {
			$orgdn = $attributes['eduPersonOrgDN'][0];
			$orgattributes = $ldap->getAttributes($orgdn);
			
			$orgattr = array_keys($orgattributes);
			foreach($orgattr as $value){
				$orgattributename = ('eduPersonOrgDN:' . $value);
				//SimpleSAML_Logger::debug('AUTH - ldap-feide: Orgattributename: '. $orgattributename);
				$attributes[$orgattributename] = $orgattributes[$value];
				//SimpleSAML_Logger::debug('AUTH - ldap-feide: Attribute added: '. $attributes[$orgattributename]);
			}
			
		}
		/*
		
		TODO: We need to figure out how to map the orgunit attributes into SAML attributes.
		
		if(isset($attributes['edupersonprimaryorgunitdn'][0])){
			$orgunitdn = $attributes['edupersonprimaryorgunitdn'][0];
		}
			elseif(isset($attributes['edupersonorgunitdn'][0])){
				$orgunitdn = $attributes['edupersonorgunitdn'][0];
			}
			
		$orgunitattributes = $ldap->getAttributes($orgunitdn);


		
		$orgunitattr = array_keys($orgunitattributes);
		foreach($orgunitattr as $value){
			$orgunitattributename = ('edupersonorgunit: ' . $value);
			// SimpleSAML_Logger::debug('AUTH - ldap-feide: Orgunitattributename: '. $orgunitattributename);
			$attributes[$orgunitattributename] = $orgunitattributes[$value];
		}
		*/
		
		
		
		//SimpleSAML_Logger::debug('AUTH - ldap-feide: '. $orgattributes . ' successfully authenticated');
		SimpleSAML_Logger::info('AUTH - ldap-feide: '. $requestedUser . ' successfully authenticated');
		
		$session->doLogin('login-feide');
		
		
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


$t = new SimpleSAML_XHTML_Template($config, 'login-feide.php', 'login.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['ldapconfig'] = $ldaporgconfig;
$t->data['protocol'] = $protocol;
$t->data['authid'] = $authid;

if(array_key_exists('logo', $spmetadata)) {
	$t->data['splogo'] = $spmetadata['logo'];
} else {
	$t->data['splogo'] = NULL;
}
if(array_key_exists('description', $spmetadata)) {
	$t->data['spdesc'] = $spmetadata['description'];
} else {
	$t->data['spdesc'] = NULL;
}
if(array_key_exists('name', $spmetadata)) {
	$t->data['spname'] = $spmetadata['name'];
} else {
	$t->data['spname'] = NULL;
}
if(array_key_exists('contact', $spmetadata)) {
	$t->data['contact'] = $spmetadata['contact'];
} else {
	$t->data['contact'] = NULL;
}
if(array_key_exists('attributes', $spmetadata)) {
	$t->data['attrib'] = $spmetadata['attributes'];
} else {
	$t->data['attrib'] = NULL;
}

$t->data['selectorg'] = $selectorg;
$t->data['org'] = $org;
$t->data['allowedorgs'] = $allowedOrgs;
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();

?>
