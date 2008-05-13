<?php

require_once('../../_include.php');


require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPRedirect.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

// Get the local session
$session = SimpleSAML_Session::getInstance(true);


SimpleSAML_Logger::info('SAML2.0 - SP.SingleLogoutService: Accessing SAML 2.0 SP endpoint SingleLogoutService');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');



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
			SimpleSAML_Logger::info('SAML2.0 - SP.SingleLogoutService: Valid signature found for '.$requestid);
		}

		// Extract some parameters from the logout request
		$requestid = $logoutrequest->getRequestID();
		$requester = $logoutrequest->getIssuer();
		$relayState = $logoutrequest->getRelayState();

		//$responder = $config->getValue('saml2-hosted-sp');
		$responder = $metadata->getMetaDataCurrentEntityID();
	
		SimpleSAML_Logger::info('SAML2.0 - SP.SingleLogoutService: IdP (' . $requester . ') is sending logout request to me SP (' . $responder . ') requestid '.$requestid);
		SimpleSAML_Logger::stats('saml20-idp-SLO idpinit ' . $responder . ' ' . $requester);
	
		// Create a logout response
		$lr = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
		$logoutResponseXML = $lr->generate($responder, $requester, $requestid, 'SP');
	
	
		// Create a HTTP Redirect binding.
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	
		SimpleSAML_Logger::info('SAML2.0 - SP.SingleLogoutService: SP me (' . $responder . ') is sending logout response to IdP (' . $requester . ')');
	
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
			SimpleSAML_Logger::info('SAML2.0  - SP.SingleLogoutService: Valid signature found');
		}

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTRESPONSE', $exception);
	}


	// Extract some parameters from the logout request
	#$requestid = $logoutrequest->getRequestID();
	$responder = $logoutresponse->getIssuer();
	#$relayState = $logoutrequest->getRelayState();

	//$responder = $config->getValue('saml2-hosted-sp');
	$requester = $metadata->getMetaDataCurrentEntityID('saml20-sp-hosted');

	SimpleSAML_Logger::stats('saml20-sp-SLO spinit ' . $requester . ' ' . $responder);

	$id = $logoutresponse->getInResponseTo();
	error_log('ID: ' . strlen($id) . ':' . $id);
	$returnTo = $session->getData('spLogoutReturnTo', $id);
	error_log("returnTo: " . var_export($returnTo, TRUE));

	if(empty($returnTo)) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

	SimpleSAML_Utilities::redirect($returnTo);

} else {
	
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS');
}



?>