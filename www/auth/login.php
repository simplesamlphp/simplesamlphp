<?php


require_once('../../www/_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/XHTML/Template.php');

session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);

	
$session = SimpleSAML_Session::getInstance();

$error = null;
$attributes = array();
	
if (isset($_POST['username'])) {


	$dn = str_replace('%username%', $_POST['username'], $config->getValue('auth.ldap.dnpattern'));
	$pwd = $_POST['password'];

	$ds = ldap_connect($config->getValue('auth.ldap.hostname'));
	
	if ($ds) {
	
		if (!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
			echo "Failed to set LDAP Protocol version to 3";
			exit;
		}
		/*
		if (!ldap_start_tls($ds)) {
		echo "Failed to start TLS";
		exit;
		}
		*/
		if (!ldap_bind($ds, $dn, $pwd)) {
			$error = "Bind failed, wrong username or password. Tried with DN=[" . $dn . "] DNPattern=[" . $config->getValue('auth.ldap.dnpattern') . "]";
			
			
		} else {
			$sr = ldap_read($ds, $dn, $config->getValue('auth.ldap.attributes'));
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
			
			$session->setAuthenticated(true);
			$session->setAttributes($attributes);
			
			$session->setNameID(SimpleSAML_Utilities::generateID());
			$session->setNameIDFormat('urn:oasis:names:tc:SAML:2.0:nameid-format:transient');
			
			$returnto = $_REQUEST['RelayState'];
			header("Location: " . $returnto);
			exit(0);

		}
	// ldap_close() om du vil, men frigjoeres naar skriptet slutter
	}

	
}


$t = new SimpleSAML_XHTML_Template($config, 'login.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();


?>
