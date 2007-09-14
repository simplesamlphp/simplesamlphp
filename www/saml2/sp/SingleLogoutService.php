<?php

require_once('../../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once('SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');

session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);





// Get the local session
$session = SimpleSAML_Session::getInstance();


// Destroy local session if exists.
if (isset($session) && $session->isAuthenticated() ) {	
	$session->setAuthenticated(false);
}




if (isset($_GET['SAMLRequest'])) {
		
	// Create a HTTPRedirect binding
	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	// Decode the LogoutRequest using the HTTP Redirect binding.
	$logoutrequest = $binding->decodeLogoutRequest($_GET);
	
	// Extract some parameters from the logout request
	$requestid = $logoutrequest->getRequestID();
	$requester = $logoutrequest->getIssuer();
	$relayState = $logoutrequest->getRelayState();
	
	
	//$responder = $config->getValue('saml2-hosted-sp');
	$responder = $metadata->getMetaDataCurrentEntityID();
	

	
	// Create a logout response
	$lr = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
	$logoutResponseXML = $lr->generate($responder, $requester, $requestid, 'SP');
	
	
	// Create a HTTP Redirect binding.
	$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	// Send the Logout response using HTTP POST binding.
	$httpredirect->sendMessage($logoutResponseXML, $requester, $logoutrequest->getRelayState(), 'SingleLogOutUrl', 'SAMLResponse');

} elseif(isset($_GET['SAMLResponse'])) {
	

	if (isset($_GET['RelayState'])) {
		header('Location: ' . $_GET['RelayState']);
	} else {
		
		echo 'You are now successfully logged out.';
		
	}

}



?>