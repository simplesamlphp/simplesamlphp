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


	try {
	
		$radius = radius_auth_open();
		// ( resource $radius_handle, string $hostname, int $port, string $secret, int $timeout, int $max_tries )
		if (! radius_add_server($radius, $config->getValue('auth.radius.hostname'), $config->getValue('auth.radius.port'), 
				$config->getValue('auth.radius.secret'), 5, 3)) {
			throw new Exception('Problem occured when connecting to Radius server: ' . radius_strerror($radius));
		}
	
		if (! radius_create_request($radius,RADIUS_ACCESS_REQUEST)) {
			throw new Exception('Problem occured when creating the Radius request: ' . radius_strerror($radius));
		}
	
		radius_put_attr($radius,RADIUS_USER_NAME,$_POST['username']);
		radius_put_attr($radius,RADIUS_USER_PASSWORD, $_POST['password']);
	
		switch (radius_send_request($radius))
		{
			case RADIUS_ACCESS_ACCEPT:
	
				// GOOD Login :)
				$attributes = array('urn:mace:eduroam.no:username' => array($_POST['username']));
				
				$session->setAuthenticated(true);
				$session->setAttributes($attributes);
				$returnto = $_REQUEST['RelayState'];
				header("Location: " . $returnto);
				
				exit(0);
				
	
			case RADIUS_ACCESS_REJECT:
			
				throw new Exception('Radius authentication error: Bad credentials ');
				break;
			case RADIUS_ACCESS_CHALLENGE:
				throw new Exception('Radius authentication error: Challenge requested');
				break;
			default:
				throw new Exception('Error during radius authentication: ' . radius_strerror($radius));
				
		}

	} catch (Exception $e) {
		
		$error = $e->getMessage();
		
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
