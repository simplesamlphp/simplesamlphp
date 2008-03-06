<?php


require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . '../../www/_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('AUTH -admin: Accessing auth endpoint login-admin');

$error = null;
$attributes = array();
$username = null;

if (empty($session))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');

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

		$session->setNameID(array(
			'value' => SimpleSAML_Utilities::generateID(),
			'Format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient'));
		
		SimpleSAML_Logger::info('AUTH - admin: '. $username . ' successfully authenticated');

		/**
		 * Create a statistics log entry for every successfull login attempt.
		 * Also log a specific attribute as set in the config: statistics.authlogattr
		 */
		$authlogattr = $config->getValue('statistics.authlogattr', null);
		if ($authlogattr && array_key_exists($authlogattr, $attributes)) 
			SimpleSAML_Logger::stats('AUTH-login-admin OK ' . $attributes[$authlogattr][0]);
		else 
			SimpleSAML_Logger::stats('AUTH-login-admin OK');
		
		SimpleSAML_Utilities::redirect($relaystate);
		exit(0);
	} else {
		SimpleSAML_Logger::stats('AUTH-login-admin Failed');
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
