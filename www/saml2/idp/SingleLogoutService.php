<?php

/**
 * This SAML 2.0 endpoint can receive incomming LogoutRequests. It will also send LogoutResponses, 
 * and LogoutRequests and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

// TODO: Show error message, when shibboleth sp is logged in.
// TODO: Propagate HTTP-REDIRECT SLO on SAML 2.0 bridge.

require_once('../../../www/_include.php');

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
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Accessing SAML 2.0 IdP endpoint SingleLogoutService');

if (!$config->getValue('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got IdP entity id: ' . $idpentityid);


/**
 * The $logoutInfo contains information about the current logout operation.
 * It can have the following attributes:
 * - 'RelayState' - The RelayState which should be returned to the SP which initiated the logout operation.
 * - 'Issuer' - The entity id of the SP which initiated the logout operation.
 * - 'RequestID' - The id of the LogoutRequest which initiated the logout operation.
 */
$logoutInfo = array();


/**
 * This function retrieves the logout info with the given ID.
 *
 * @param $id  The identifier of the logout info.
 */
function fetchLogoutInfo($id) {
	global $session;
	global $logoutInfo;

	$logoutInfo = $session->getData('idplogoutresponsedata', $id);

	if($logoutInfo === NULL) {
		SimpleSAML_Logger::warning('SAML2.0 - IdP.SingleLogoutService: Lost logout information.');
	}
}


/**
 * This function saves the logout info with the given ID.
 *
 * @param $id  The identifier the logout info should be saved with.
 */
function saveLogoutInfo($id) {
	global $session;
	global $logoutInfo;

	$session->setData('idplogoutresponsedata', $id, $logoutInfo);
}


/**
 * If we get an incomming LogoutRequest then we initiate the logout process.
 * in this case an SAML 2.0 SP is sending an request, which also is referred to as
 * SP initiated Single Logout.
 *
 */
if (isset($_GET['SAMLRequest'])) {

	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got SAML reuqest');

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	try {
		$logoutrequest = $binding->decodeLogoutRequest($_GET);

		if ($binding->validateQuery($logoutrequest->getIssuer(),'IdP')) {
			SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Valid signature found for '.$logoutrequest->getRequestID());
		}

	} catch(Exception $exception) {
	
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTREQUEST', $exception);
		
	}
	
	// Extract some parameters from the logout request
	#$requestid = $logoutrequest->getRequestID();
	$requester = $logoutrequest->getIssuer();
	#$relayState = $logoutrequest->getRelayState();

	//$responder = $config->getValue('saml2-hosted-sp');
	$responder = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	
	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: got Logoutrequest from ' . $logoutrequest->getIssuer());
	SimpleSAML_Logger::stats('saml20-idp-SLO spinit ' . $requester . ' ' . $responder);
	
	/* Check if we have a valid session. */
	if($session === NULL) {
	
		/* Invalid session. To prevent the user from being unable to
		 * log out from the service provider, we should just return a
		 * LogoutResponse pretending that the logout was successful to
		 * the SP that sent the LogoutRequest.
		 */

		SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Did not find a session here, but we are returning a LogoutResponse anyway.');

		$spentityid = $logoutrequest->getIssuer();

		/* Generate the response. */
		$response = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
		$responseText = $response->generate($idpentityid, $spentityid, $logoutrequest->getRequestID(), 'IdP');

		/* Retrieve the relay state from the request. */
		$relayState = $logoutrequest->getRelayState();

		/* Send the response using the HTTP-Redirect binding. */
		$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config,
		$metadata);
		$binding->sendMessage($responseText, $idpentityid, $spentityid, $relayState,
			'SingleLogoutService', 'SAMLResponse', 'IdP');
		exit;
	}


	$session->doLogout();


	/* Fill in the $logoutInfo associative array with information about this logout request. */
	$logoutInfo['Issuer'] = $logoutrequest->getIssuer();
	$logoutInfo['RequestID'] = $logoutrequest->getRequestID();

	$relayState = $logoutrequest->getRelayState();
	if($relayState !== NULL) {
		$logoutInfo['RelayState'] = $relayState;
	}

		
	SimpleSAML_Logger::debug('SAML2.0 - IDP.SingleLogoutService: Setting cached request with issuer ' . $logoutrequest->getIssuer());
	
	$session->set_sp_logout_completed($logoutrequest->getIssuer() );
	

	/*
	 * We receive a Logout Response to a Logout Request that we have issued earlier.
	 */
} elseif (isset($_GET['SAMLResponse'])) {

	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got SAML response');

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	try {
		$loginresponse = $binding->decodeLogoutResponse($_GET);
		
		SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: SAML response parsed. Issuer is: ' . $loginresponse->getIssuer());

		if ($binding->validateQuery($loginresponse->getIssuer(),'IdP','SAMLResponse')) {
			SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Valid signature found');
		}


	} catch(Exception $exception) {

		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTRESPONSE', $exception);

	}

	/* Fetch the $logoutInfo variable based on the InResponseTo attribute of the response. */
	fetchLogoutInfo($loginresponse->getInResponseTo());

	$session->set_sp_logout_completed($loginresponse->getIssuer());

	SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: got LogoutResponse from ' . $loginresponse->getIssuer());

} elseif(array_key_exists('LogoutID', $_GET)) {
	/* This is a response from bridged SLO. */
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got response from bridged SLO.');

	/* Fetch the $logoutInfo variable. */
	fetchLogoutInfo($_GET['LogoutID']);

} elseif(array_key_exists('ReturnTo', $_GET)) {
	/* We have a ReturnTo - this is IdP initialized SLO. */
	$logoutInfo['RelayState'] = $_GET['ReturnTo'];

} else {
	/*
	 * We have no idea what to do here. It is neither a logout request, a logout
	 * response nor a response from bridged SLO.
	 */
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: No request, response or bridge');
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SLOSERVICEPARAMS');
}

$lookformore = true;
$spentityid = null;
do {
	/* Dump the current sessions (for debugging). */
	$session->dump_sp_sessions();

	/*
	 * We proceed to send logout requests to all remaining SPs.
	 */
	$spentityid = $session->get_next_sp_logout();
	
	
	// If there are no more SPs left, then we will not look for more SPs.
	if (empty($spentityid)) $lookformore = false;
	
	try {
		$spmetadata = $metadata->getMetadata($spentityid, 'saml20-sp-remote');
	} catch (Exception $e) {
		continue;
	}
	
	// If the SP we found have an SingleLogout endpoint then we will use it, and
	// hence we do not need to look for more yet.
	if (array_key_exists('SingleLogoutService', $spmetadata) && 
		!empty($spmetadata['SingleLogoutService']) ) $lookformore = false;
		
	if ($lookformore)
		SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Will not logout from ' . $spentityid . ' looking for more SPs');

} while ($lookformore);


if ($spentityid) {

	SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Logout next SP ' . $spentityid);

	try {
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);

		// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
		$req = $lr->generate($idpentityid, $spentityid, $session->getNameID(), $session->getSessionIndex(), 'IdP');

		/* Save the $logoutInfo until we return from the SP. */
		saveLogoutInfo($lr->getGeneratedID());


		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

		//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
		$httpredirect->sendMessage($req, $idpentityid, $spentityid, NULL, 'SingleLogoutService', 'SAMLRequest', 'IdP');

		exit();

	} catch(Exception $exception) {

		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATELOGOUTREQUEST', $exception);

	}


}

if ($config->getValue('debug', false))
	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: LogoutService: All SPs done ');



/**
 * If there exists a local valid session with the SAML 2.0 module as an authority, 
 * initiate SAML 2.0 SP Single LogOut, with the RelayState equal this URL.
 */
if ($session->getAuthority() == 'saml2') {

	$bridgedId = SimpleSAML_Utilities::generateID();
	$returnTo = SimpleSAML_Utilities::selfURLNoQuery() . '?LogoutID=' . $bridgedId;

	/* Save the $logoutInfo until we return from the SP. */
	saveLogoutInfo($bridgedId);

	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'saml2/sp/initSLO.php',
		array('RelayState' => $returnTo)
	);
}

if ($session->getAuthority() == 'shib13') {
	/**
	 * TODO: Show warning to inform the user that he is logged on through an Shibboleth 1.3 IdP that
	 * do not support logout.
	 */
}




/*
 * Logout procedure is done and we send a Logout Response back to the SP
 */

try {

	if(!$logoutInfo) {
		throw new Exception('The logout information has been lost during logout processing.');
	}
	
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Found logout info with these keys: ' . join(',', array_keys($logoutInfo)));
	
	/**
	 * Clean up session object to save storage.
	 */
	if ($config->getValue('debug', false)) 
		SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size before cleaning: ' . $session->getSize());
		
	$session->clean();
	
	if ($config->getValue('debug', false)) 
		SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size after cleaning: ' . $session->getSize());
	
	
	/*
	 * Check if the Single Logout procedure is initated by an SP (alternatively IdP initiated SLO
	 */
	if (array_key_exists('Issuer', $logoutInfo)) {
		
		/**
		 * Create a Logot Response.
		 */
		$rg = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
	
		// generate($issuer, $receiver, $inresponseto, $mode )
		$logoutResponseXML = $rg->generate($idpentityid, $logoutInfo['Issuer'], $logoutInfo['RequestID'], 'IdP');
	
		// Create a HTTP-REDIRECT Binding.
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
		// Find the relaystate if cached.
		$relayState = isset($logoutInfo['RelayState']) ? $logoutInfo['RelayState'] : null;
	
		// Parameters: $request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
		$httpredirect->sendMessage($logoutResponseXML, $idpentityid, $logoutInfo['Issuer'], $relayState, 'SingleLogoutService', 'SAMLResponse', 'IdP');
		exit;
		
	} elseif (array_key_exists('RelayState', $logoutInfo)) {

		SimpleSAML_Utilities::redirect($logoutInfo['RelayState']);
		exit;
		
	} else {
	
		echo 'You are logged out'; exit;
	
	}

} catch(Exception $exception) {
	
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATELOGOUTRESPONSE', $exception);
	
}


