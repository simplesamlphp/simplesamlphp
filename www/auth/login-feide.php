<?php

require_once('../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();
$logger = new SimpleSAML_Logger();

$ldapconfigfile = $config->getBaseDir() . 'config/ldapfeide.php';
require_once($ldapconfigfile);


$logger->log(LOG_INFO, $session->getTrackID(), 'AUTH', 'ldap-feide', 'EVENT', 'Access', 'Accessing auth endpoint login-feide');


$error = null;
$attributes = array();

/* Load the RelayState argument. The RelayState argument contains the address
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
		 * Checking username parameter
		 */
		if (!preg_match('/^[a-z0-9._]+(@[a-z0-9._]+)?$/', $requestedUser) ) 
			throw new Exception('Illegal characters in username.');
		
		
		if (strstr($requestedUser, '@')) {
			$decomposed = explode('@', $requestedUser);
			$requestedUser = $decomposed[0];
			$requestedOrg = $decomposed[1];
		}
		
		/*
		 * Checking organization parameter
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
		
		
		if (empty($_REQUEST['password']))
			throw new Exception('The password field was left empty. Please fill in a valid password.');
		
		$password = $_REQUEST['password'];
		
		if (!preg_match('/^[a-zA-Z0-9.]+$/', $password) ) 
			throw new Exception('Illegal characters in password.');
		
		
		//throw new Exception('everything is ok   username:' . $requestedUser . ' org:' . $requestedOrg);
		

		
		
		
		/*
		 * Connecting to LDAP
		 */
		
		$search_eppn = "(eduPersonPrincipalName=".$requestedUser."@".$requestedOrg.")";
		
		$ds = @ldap_connect($ldapconfig['hostname']);
				
		if (empty($ds)) {
			throw new Exception('Could not connect to LDAP server. Please try again, and if the problem persists, please report the error.');
		}
		

		// Give error if LDAPv3 is not supported
		if (!@ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'ldap-feide', 'LDAP_OPT_PROTOCOL_VERSION', '3', 
				'Error setting LDAP prot version to 3');
			throw new Exception('Failed to set LDAP Protocol version to 3: ' . ldap_error($ds) );
		}

		// Search for ePPN
		$eppn_result = @ldap_search($ds, $ldapconfig['searchbase'], $search_eppn);

		if ($eppn_result === false)
			throw new Exception('Failed performing a LDAP search: ' . ldap_error($ds) . ' search:' . $search_eppn);

		// Check number of entries. ePPN should be unique!
		if (ldap_count_entries($ds, $eppn_result) > 1 ) {
			throw new Exception("Din organisasjon (".$requestedOrg.") har feilregistrert flere like FEIDE-navn.");
		}

		if (ldap_count_entries($ds, $eppn_result) == 0) {
			throw new Exception('User could not be found.');
		}
		
		// Authenticate user and fetch attributes
		$entry = ldap_first_entry($ds, $eppn_result);
		
		if (empty($entry))
			throw new Exception('Could not retrieve result of LDAP search for Feide name.');
		

		$dn = @ldap_get_dn($ds, $entry);
		
		if (empty($dn))
			throw new Exception('Error retrieving DN from search result.');
		
		

		if (!@ldap_bind($ds, $dn, $password)) {
		
			$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'ldap-feide', 'Fail', $username, $username . ' failed to authenticate');
			throw new Exception('Bind failed, wrong username or password. ' .
				' Tried with DN=[' . $dn . '] DNPattern=[' .
				$ldapconfig['dnpattern'] . '] Error=[' .
				ldap_error($ds) . '] ErrNo=[' .
				ldap_errno($ds) . ']');
			
		}

		$sr = @ldap_read($ds, $dn, $ldapconfig['attributes'] );
		
		if ($sr === false) 
			throw new Exception('Could not retrieve attribtues for user:' . ldap_error($ds));
		
		$ldapentries = @ldap_get_entries($ds, $sr);
		
		if ($ldapentries === false)
			throw new Exception('Could not retrieve results from attribute retrieval for user:' . ldap_error($ds));
		
		
		for ($i = 0; $i < $ldapentries[0]['count']; $i++) {
			$values = array();
			if ($ldapentries[0][$i] == 'jpegphoto') continue;
			for ($j = 0; $j < $ldapentries[0][$ldapentries[0][$i]]['count']; $j++) {
				$values[] = $ldapentries[0][$ldapentries[0][$i]][$j];
			}
			
			$attributes[$ldapentries[0][$i]] = $values;
		}
			
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'ldap-feide', 'OK', $username, $username . ' successfully authenticated');
		
		
		$session->setAuthenticated(true, 'login-feide');
		$session->setAttributes($attributes);
		
		$session->setNameID(array(
			'value' => SimpleSAML_Utilities::generateID(),
			'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'));
		
		$returnto = $_REQUEST['RelayState'];
		SimpleSAML_Utilities::redirect($returnto);

		
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
	
}


$t = new SimpleSAML_XHTML_Template($config, 'login-ldapmulti.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['ldapconfig'] = $ldapfeide;
$t->data['org'] = $_REQUEST['org'];
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();


?>
