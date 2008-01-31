<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once('SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');

require_once('SimpleSAML/XHTML/Template.php');

$config = SimpleSAML_Configuration::getInstance(true);
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

// Get the local session
$session = SimpleSAML_Session::getInstance();


$logger = new SimpleSAML_Logger();
$logger->log(LOG_INFO, $session->getTrackId(), 'SAML2.0', 'SP.SingleLogoutService', 'EVENT', 'Access',
	'Accessing SAML 2.0 SP endpoint SingleLogoutService');

// Destroy local session if exists.
if (isset($session) ) {
	$session->setAuthenticated(false);
	$session->clean();
}



if (isset($_GET['SAMLRequest'])) {

	// Create a HTTPRedirect binding
	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	try {
		// Decode the LogoutRequest using the HTTP Redirect binding.
		$logoutrequest = $binding->decodeLogoutRequest($_GET);

		if ($binding->validateQuery($logoutrequest->getIssuer(),'SP')) {
			$logger->log(LOG_NOTICE, $trackId, 'SAML2.0', 'SP.SingleLogoutService', 'LogoutRequest', $requestid,'Valid signature found');
		}

		// Extract some parameters from the logout request
		$requestid = $logoutrequest->getRequestID();
		$requester = $logoutrequest->getIssuer();
		$relayState = $logoutrequest->getRelayState();

		//$responder = $config->getValue('saml2-hosted-sp');
		$responder = $metadata->getMetaDataCurrentEntityID();
	
		$logger->log(LOG_NOTICE, $trackId, 'SAML2.0', 'SP.SingleLogoutService', 'LogoutRequest', $requestid,
			'IdP (' . $requester . ') is sending logout request to me SP (' . $responder . ')');
	
	
		// Create a logout response
		$lr = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
		$logoutResponseXML = $lr->generate($responder, $requester, $requestid, 'SP');
	
	
		// Create a HTTP Redirect binding.
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	
		$logger->log(LOG_NOTICE, $trackId, 'SAML2.0', 'SP.SingleLogoutService', 'LogoutResponse', '-',
			'SP me (' . $responder . ') is sending logout response to IdP (' . $requester . ')');
	
		// Send the Logout response using HTTP POST binding.
		$httpredirect->sendMessage($logoutResponseXML, $responser, $requester, $logoutrequest->getRelayState(), 'SingleLogoutServiceResponse', 'SAMLResponse');
	
	} catch(Exception $exception) {

		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTREQUEST', $exception);

	}

} elseif(isset($_GET['SAMLResponse'])) {

	// Create a HTTPRedirect binding
	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	try {
		// Decode the LogoutResponse using the HTTP Redirect binding.
		$logoutresponse = $binding->decodeLogoutResponse($_GET);

		if ($binding->validateQuery($logoutresponse->getIssuer(),'SP','SAMLResponse')) {
			$logger->log(LOG_NOTICE, $trackId, 'SAML2.0', 'SP.SingleLogoutService', 'LogoutResponse', 'SingleLogoutServiceResponse','Valid signature found');
		}

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTRESPONSE', $exception);
	}

	if (isset($_GET['RelayState'])) {
		SimpleSAML_Utilities::redirect($_GET['RelayState']);
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

}



?>