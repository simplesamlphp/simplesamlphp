<?php


require_once('../../www/_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Logger.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);	
$session = SimpleSAML_Session::getInstance();
$logger = new SimpleSAML_Logger();

$logger->log(LOG_INFO, $session->getTrackID(), 'AUTH', 'ldap', 'EVENT', 'Access', 'Accessing auth endpoint login');

$error = null;
$attributes = array();
$username = null;


/* Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
	SimpleSAML_Utilities::fatalError(
		'Invalid access of LDAP login page',
		'Missing RelayState argument to LDAP authenticator.'
		);
}

$relaystate = $_REQUEST['RelayState'];
/* Remove backslashes if magic quotes are enabled. */
if(get_magic_quotes_gpc()) {
	$relaystate = stripslashes($relaystate);
}


if (isset($_POST['username'])) {

	/* Validate and sanitize form data. */

	/* First, make sure that the password field is included. */
	if (!array_key_exists('password', $_POST)) {
		SimpleSAML_Utilities::fatalError(
			'Invalid access of LDAP login page',
			'Invalid form data posted to login form. Missing' .
			' required password form field.'
			);
	}

	$username = $_POST['username'];
	$password = $_POST['password'];

	/* Remove backslashes if magic quotes are enabled. */
	if(get_magic_quotes_gpc()) {
		$username = stripslashes($username);
		$password = stripslashes($password);
	}

	/* Escape any characters with a special meaning in LDAP. The following
	 * characters have a special meaning (according to RFC 2253):
	 * ',', '+', '"', '\', '<', '>', ';', '*'
	 * These characters are escaped by prefixing them with '\'.
	 */
	$ldapusername = addcslashes($username, ',+"\\<>;*');

	/* Insert the LDAP username into the pattern configured in the
	 * 'auth.ldap.dnpattern' option.
	 */
	$dn = str_replace('%username%', $ldapusername,
	                  $config->getValue('auth.ldap.dnpattern'));

	/* Connect to the LDAP server. */
	$ds = ldap_connect($config->getValue('auth.ldap.hostname'));
	
	if ($ds) {
	
		if (!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) {
		
			$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'ldap-multi', 'LDAP_OPT_PROTOCOL_VERSION', '3', 'Error setting LDAP prot version to 3');
			
			echo "Failed to set LDAP Protocol version to 3";
			exit;
		}
		/*
		if (!ldap_start_tls($ds)) {
		echo "Failed to start TLS";
		exit;
		}
		*/
		if (!ldap_bind($ds, $dn, $password)) {
			$error = "Bind failed, wrong username or password. Tried with DN=[" . $dn . "] DNPattern=[" . $config->getValue('auth.ldap.dnpattern')
				. "] Error=[" . ldap_error($ds) . "] ErrNo=[" . ldap_errno($ds) . "]";
			
			$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'ldap', 'Fail', $username, $username . ' failed to authenticate');
			
		} else {
			$sr = ldap_read($ds, $dn, $config->getValue('auth.ldap.attributes'));
			$ldapentries = ldap_get_entries($ds, $sr);

			/* Check if we have any entries in the search result.
			 */
			if($ldapentries['count'] == 0) {
				throw new Exception('LDAP: No entries in the' .
				                    ' search result.');
			}

			/* Currently we only care about the first entry. We
			 * write a message to the error log if we have more.
			 */
			if($ldapentries['count'] > 1) {
				error_log('LDAP: we have more than one entry' .
				          ' in the search result.');
			}

			/* Iterate over all the attributes in the first
			 * result. $ldapentries[0]['count'] contains the
			 * attribute count, while $ldapentries[0][$i]
			 * contains the name of the $i'th attribute.
			 */
			for ($i = 0; $i < $ldapentries[0]['count']; $i++) {
				$name = $ldapentries[0][$i];

				/* We currently ignore the 'jpegphoto'
				 * attribute since it is relatively big.
				 */
				if ($name === 'jpegphoto') {
					continue;
				}

				$attribute = $ldapentries[0][$name];

				$values = array();

				for ($j = 0; $j < $attribute['count']; $j++) {
					$values[] = $attribute[$j];
				}

				assert(!array_key_exists($name, $attributes));
				$attributes[$name] = $values;
			}

			$session->setAuthenticated(true);
			$session->setAttributes($attributes);
			
			$session->setNameID(SimpleSAML_Utilities::generateID());
			$session->setNameIDFormat('urn:oasis:names:tc:SAML:2.0:nameid-format:transient');
			
			$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'ldap', 'OK', $username, $username . ' successfully authenticated');
			
			
			header("Location: " . $relaystate);
			exit(0);

		}
	// ldap_close() om du vil, men frigjoeres naar skriptet slutter
	}

	
}


$t = new SimpleSAML_XHTML_Template($config, 'login.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $relaystate;
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $username;
}

$t->show();


?>
