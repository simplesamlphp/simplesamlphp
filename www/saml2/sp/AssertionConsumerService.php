<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

session_start();

try {
	
	$config = SimpleSAML_Configuration::getInstance();	
	$metadata = new SimpleSAML_XML_MetaDataStore($config);

	$binding = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);
	
	$authnResponse->validate();
	
	$session = $authnResponse->createSession();
	if (isset($session)) {
		
		$attributes = $session->getAttributes();
		syslog(LOG_INFO, 'User is authenticated,' . $attributes['mail'] . ',' . $authnResponse->getIssuer());
	
		$relayState = $authnResponse->getRelayState();
		if (isset($relayState)) {
			header("Location: " . $relayState);
			exit(0);
		} else {
			echo 'Could not find RelayState parameter, you are stucked here.';
		}
	} else {
		throw new Exception('Unkown error. Could not get session.');
	}

} catch(Exception $exception) {

	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['header'] = 'Error receiving response from IdP';
	$et->data['message'] = 'Some error occured when trying to issue the authentication request to the IdP.';	
	$et->data['e'] = $exception;
	
	$et->show();

}


?>