<?php


require_once('../../www/_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

$ldapconfigfile = $config->getBaseDir() . 'config/ldapmulti.php';
require_once($ldapconfigfile);


SimpleSAML_Logger::info('AUTH - ldap-multi: Accessing auth endpoint login-ldapmulti');

if (empty($session))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');

$error = null;
$attributes = array();

/* Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

if (isset($_POST['username'])) {

	try {
	
		$ldapconfig = $ldapmulti[$_POST['org']];
		
		
	
		$dn = str_replace('%username%', $_POST['username'], $ldapconfig['dnpattern'] );
		$pwd = $_POST['password'];
	
		$ds = ldap_connect($ldapconfig['hostname']);
		
		if ($ds) {
		
			if (!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			
				SimpleSAML_Logger::critical('AUTH - ldap-multi: Error setting LDAP protocol version to 3');
				
				$error = "Failed to set LDAP Protocol version to 3";
			}
			/*
			if (!ldap_start_tls($ds)) {
			echo "Failed to start TLS";
			exit;
			}
			*/
			if (!@ldap_bind($ds, $dn, $pwd)) {
				$error = 'Bind failed, wrong username or password.' .
					' Tried with DN=[' . $dn . '] DNPattern=[' .
					$ldapconfig['dnpattern'] . '] Error=[' .
					ldap_error($ds) . "] ErrNo=[" .
					ldap_errno($ds) . "]";
	
				SimpleSAML_Logger::info('AUTH - ldap-multi: '. $_POST['username'] . ' failed to authenticate');
				
			} else {
				$sr = ldap_read($ds, $dn, $ldapconfig['attributes'] );
				$ldapentries = ldap_get_entries($ds, $sr);
				
	
				for ($i = 0; $i < $ldapentries[0]['count']; $i++) {
					$values = array();
					if ($ldapentries[0][$i] == 'jpegphoto') continue;
					for ($j = 0; $j < $ldapentries[0][$ldapentries[0][$i]]['count']; $j++) {
						$values[] = $ldapentries[0][$ldapentries[0][$i]][$j];
					}
					
					$attributes[$ldapentries[0][$i]] = $values;
				}
	
				// generelt ldap_next_entry for flere, men bare ett her
				//print_r($ldapentries);
				//print_r($attributes);
				
				SimpleSAML_Logger::info('AUTH - ldap-multi: '. $_POST['username'] . ' successfully authenticated');
				
				
				$session->doLogin('login-ldapmulti');
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
					SimpleSAML_Logger::stats('AUTH-login-ldapmulti OK ' . $attributes[$authlogattr][0]);
				else 
					SimpleSAML_Logger::stats('AUTH-login-ldapmulti OK');
				
				
				$returnto = $_REQUEST['RelayState'];
				SimpleSAML_Utilities::redirect($returnto);
	
			}
		// ldap_close() om du vil, men frigjoeres naar skriptet slutter
		}

	} catch (Exception $e) {
		
		$error = $e->getMessage();
		
	}	
}


$t = new SimpleSAML_XHTML_Template($config, 'login-ldapmulti.php', 'login.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['ldapconfig'] = $ldapmulti;
$t->data['org'] = $_REQUEST['org'];
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();


?>
