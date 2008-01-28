<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

/* Get the session object for the user. Create a new session if no session
 * exists for this user.
 */
$session = SimpleSAML_Session::getInstance(TRUE);

$logger = new SimpleSAML_Logger();


$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'SP.AssertionConsumerService', 'EVENT', 'Access', 
	'Accessing SAML 2.0 SP endpoint AssertionConsumerService');

try {
	
	$config = SimpleSAML_Configuration::getInstance();	
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$binding = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
	$authnResponse = $binding->decodeResponse($_POST);
	
	$authnResponse->validate();
	
	$session = $authnResponse->createSession();
	if (isset($session)) {
		
		$attributes = $session->getAttributes();

		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.AssertionConsumerService', 'AuthnResponse', '-', 
			'Successfully created local session from Authentication Response');
	
		$relayState = $authnResponse->getRelayState();
		if (isset($relayState)) {
			SimpleSAML_Utilities::redirect($relayState);
		} else {
			throw new Exception('Could not find RelayState parameter, you are stucked here.');
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