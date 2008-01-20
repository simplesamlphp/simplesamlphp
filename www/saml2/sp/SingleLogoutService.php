<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once('SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

// Get the local session
$session = SimpleSAML_Session::getInstance();

/* Get the tracking id for this session if we have a valid session. Use an
 * empty string if we don't have a valid session.
 */
if($session !== NULL) {
	$trackId = $session->getTrackId();
} else {
	$trackId = '';
}

$logger = new SimpleSAML_Logger();

$logger->log(LOG_INFO, $trackId, 'SAML2.0', 'SP.SingleLogoutService', 'EVENT', 'Access',
	'Accessing SAML 2.0 SP endpoint SingleLogoutService');

// Destroy local session if exists.
if (isset($session) && $session->isAuthenticated() ) {
	$session->setAuthenticated(false);
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

	} catch(Exception $exception) {

		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['header'] = 'Error in received logout request';
		$et->data['message'] = 'An error occured when trying to read logout request.';
		$et->data['e'] = $exception;

		$et->show();
		exit(0);

	}



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

		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['header'] = 'Error in received logout response';
		$et->data['message'] = 'An error occured when trying to read logout response.';
		$et->data['e'] = $exception;

		$et->show();
		exit(0);

	}

	if (isset($_GET['RelayState'])) {
		SimpleSAML_Utilities::redirect($_GET['RelayState']);
	} else {

		echo 'You are now successfully logged out.';

	}

}



?>