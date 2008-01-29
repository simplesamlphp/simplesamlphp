<?php


require_once('../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Logger.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();
$logger = new SimpleSAML_Logger();

$logger->log(LOG_INFO, $session->getTrackID(), 'AUTH', 'admin', 'EVENT', 'Access', 'Accessing auth endpoint login-admin');

$error = null;
$attributes = array();
$username = null;


/* Load the RelayState argument. The RelayState argument contains the address
 * we should redirect the user to after a successful authentication.
 */
if (!array_key_exists('RelayState', $_REQUEST)) {
	SimpleSAML_Utilities::fatalError(
		'Invalid access of LDAP login page',
		'Missing RelayState argument to authentication module.'
		);
}

$relaystate = $_REQUEST['RelayState'];

$correctpassword = $config->getValue('auth.adminpassword', '123');

if (empty($correctpassword) or $correctpassword === '123') {
	SimpleSAML_Utilities::fatalError(
		'Password not set',
		'The password in the coniguration (auth.adminpassword) is not changed from the default value, please edit the config.'
	);
}


if (isset($_POST['password'])) {

	/* Validate and sanitize form data. */

	if ($_POST['password'] === $correctpassword) {
		$username = 'admin';
		$password = $_POST['password'];
	
	
		$attributes = array('user' => array('admin'));
	
		$session->setAuthenticated(true, 'login-admin');
		$session->setAttributes($attributes);

		$session->setNameID(SimpleSAML_Utilities::generateID());
		$session->setNameIDFormat('urn:oasis:names:tc:SAML:2.0:nameid-format:transient');
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'admin', 'OK', $username, $username . ' successfully authenticated');
		
		SimpleSAML_Utilities::redirect($relaystate);
		exit(0);
	} else {
		$error = 'Password incorrect';
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
