<?php

/**
 * This SAML 2.0 endpoint can receive incomming LogoutRequests. It will also send LogoutResponses, and LogoutRequests
 * and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

// TODO: Show error message, when shibboleth sp is logged in.
// TODO: Propagate HTTP-REDIRECT SLO on SAML 2.0 bridge.

require_once('../../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once('SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');

$logger = new SimpleSAML_Logger();
$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SingleLogoutService', 'EVENT', 'Access',
	'Accessing SAML 2.0 IdP endpoint SingleLogoutService');
	
	
/**
 * If we get an incomming LogoutRequest then we initiate the logout process.
 * in this case an SAML 2.0 SP is sending an request, which also is referred to as
 * SP initiated Single Logout.
 *
 */
if (isset($_GET['SAMLRequest'])) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	try {
		$logoutrequest = $binding->decodeLogoutRequest($_GET);

		if ($binding->validateQuery($logoutrequest->getIssuer(),'IdP')) {
			$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SingleLogoutService', 'LogoutRequest', $logoutrequest->getRequestID(), 'Valid signature found');
		}

	} catch(Exception $exception) {

		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['header'] = 'Error in received logout request';
		$et->data['message'] = 'An error occured when trying to read logout request.';
		$et->data['e'] = $exception;

		$et->show();
		exit(0);

	}
	/* Check if we have a valid session. */
	if($session === NULL) {
	
		/* Invalid session. To prevent the user from being unable to
		 * log out from the service provider, we should just return a
		 * LogoutResponse pretending that the logout was successful to
		 * the SP that sent the LogoutRequest.
		 */

		$spentityid = $logoutrequest->getIssuer();

		/* Generate the response. */
		$response = new SimpleSAML_XML_SAML20_LogoutResponse($config,
		$metadata);
		$responseText = $response->generate($idpentityid, $spentityid,
		$logoutrequest->getRequestID(), 'IdP');

		/* Retrieve the relay state from the request. */
		$relayState = $logoutrequest->getRelayState();

		/* Send the response using the HTTP-Redirect binding. */
		$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config,
		$metadata);
		$binding->sendMessage($responseText, $idpentityid, $spentityid, $relayState,
			'SingleLogoutService', 'SAMLResponse', 'IdP');
		exit;
	}


	$session->setAuthenticated(false);

	//$requestid = $authnrequest->getRequestID();
	//$session->setAuthnRequest($requestid, $authnrequest);

	//echo '<pre>' . htmlentities($logoutrequest->getXML()) . '</pre>';

	error_log('IdP LogoutService: got Logoutrequest from ' . $logoutrequest->getIssuer() . '  ');

	
#	$session->setLogoutRequest($logoutrequest);

	/*
	 * Create an assoc array of the request to store in the session cache.
	 */
	$requestcache = array(
		'Issuer'    	=> $logoutrequest->getIssuer(),
		'RequestID'		=> $logoutrequest->getRequestID()
	);
	if ($relaystate = $logoutrequest->getRelayState() )
		$requestcache['RelayState'] = $relaystate;
		
	$session->setLogoutRequest($requestcache);
	
	$session->set_sp_logout_completed($logoutrequest->getIssuer() );
	

	/*
	 * We receive a Logout Response to a Logout Request that we have issued earlier.
	 */
} elseif (isset($_GET['SAMLResponse'])) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	try {
		$loginresponse = $binding->decodeLogoutResponse($_GET);

		if ($binding->validateQuery($loginresponse->getIssuer(),'SP','SAMLResponse')) {
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



	$session->set_sp_logout_completed($loginresponse->getIssuer());

	error_log('IdP LogoutService: got LogoutResponse from ' . $loginresponse->getIssuer() . '  ');
}


/* Dump the current sessions (for debugging). */
$session->dump_sp_sessions();


/*
 * We proceed to send logout requests to all remaining SPs.
 */
$spentityid = $session->get_next_sp_logout();
if ($spentityid) {

	error_log('IdP LogoutService: next SP ' . $spentityid);

	try {
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);

		// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
		$req = $lr->generate($idpentityid, $spentityid, $session->getNameID(), $session->getSessionIndex(), 'IdP');

		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_GET['RelayState'])) {
			$relayState = $_GET['RelayState'];
		}

		//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
		$httpredirect->sendMessage($req, $idpentityid, $spentityid, $relayState, 'SingleLogoutService', 'SAMLRequest', 'IdP');

		exit();

	} catch(Exception $exception) {

		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['header'] = 'Error sending logout request to service';
		$et->data['message'] = 'Some error occured when trying to issue the logout response, and send it to the SP.';
		$et->data['e'] = $exception;

		$et->show();
		exit(0);
	}


}

/*
 * Logout procedure is done and we send a Logout Response back to the SP
 */
error_log('IdP LogoutService:  SPs done ');
try {

	$requestcache = $session->getLogoutRequest();
	if (!$requestcache) {
		throw new Exception('Could not get reference to the logout request.');
	}
	
	
	/**
	 * Clean up session object to save storage.
	 */
	if ($config->getValue('debug', false)) 
		$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SingleLogoutService', 'EVENT', 'SessionSize', 'Size before cleaning: ' . $session->getSize());
		
	$session->clean();
	
	if ($config->getValue('debug', false)) 
		$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SingleLogoutService', 'EVENT', 'SessionSize', 'Size after cleaning: ' . $session->getSize());
	
	
	/**
	 * Create a Logot Response.
	 */
	$rg = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);

	// 							generate($issuer, $receiver, $inresponseto, $mode )
	$logoutResponseXML = $rg->generate($idpentityid, $requestcache['Issuer'], $requestcache['RequestID'], 'IdP');

	// Create a HTTP-REDIRECT Binding.
	$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	// Find the relaystate if cached.
	$relayState = isset($requestcache['RelayState']) ? $requestcache['RelayState'] : null;

	// Parameters: $request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
	$httpredirect->sendMessage($logoutResponseXML, $idpentityid, $requestcache['Issuer'], $relayState, 'SingleLogoutService', 'SAMLResponse', 'IdP');

} catch(Exception $exception) {

	$et = new SimpleSAML_XHTML_Template($config, 'error.php');

	$et->data['header'] = 'Error sending response to service';
	$et->data['message'] = 'Some error occured when trying to issue the logout response, and send it to the SP.';
	$et->data['e'] = $exception;

	$et->show();
}

?>