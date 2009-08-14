<?php

/**
 * This SAML 2.0 endpoint can receive incoming LogoutRequests. It will also send LogoutResponses, 
 * and LogoutRequests and also receive LogoutResponses. It is implemeting SLO at the SAML 2.0 IdP.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

// TODO: Show error message, when shibboleth sp is logged in.

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Accessing SAML 2.0 IdP endpoint SingleLogoutService');

if (!$config->getBoolean('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpEntityId = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got IdP entity id: ' . $idpEntityId);


$logouttype = $idpMetadata->getString('logouttype', 'traditional');
if ($logouttype !== 'traditional') 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS', new Exception('This IdP is configured to use logout type [' . $logouttype . '], but this endpoint is only available for IdP using logout type [traditional]'));




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
 * If we get an incoming LogoutRequest then we initiate the logout process.
 * in this case an SAML 2.0 SP is sending an request, which also is referred to as
 * SP initiated Single Logout.
 *
 */
if (isset($_REQUEST['SAMLRequest'])) {

	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got SAML reuqest');

	$binding = SAML2_Binding::getCurrentBinding();

	try {
		$logoutRequest = $binding->receive();
		if (!($logoutRequest instanceof SAML2_LogoutRequest)) {
			throw new Exception('Received a request which wasn\'t a LogoutRequest ' .
				'on logout endpoint. Was: ' . get_class($logoutRequest));
		}

		$spEntityId = $logoutRequest->getIssuer();
		if ($spEntityId === NULL) {
			throw new Exception('Missing issuer in logout reqeust.');
		}

		$spMetadata = $metadata->getMetadataConfig($spEntityId, 'saml20-sp-remote');

		sspmod_saml2_Message::validateMessage($spMetadata, $idpMetadata, $logoutRequest);

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTREQUEST', $exception);
	}

	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: got Logoutrequest from ' . $spEntityId);
	SimpleSAML_Logger::stats('saml20-idp-SLO spinit ' . $spEntityId . ' ' . $idpEntityId);

	$session->doLogout();

	/* Fill in the $logoutInfo associative array with information about this logout request. */
	$logoutInfo['Issuer'] = $spEntityId;
	$logoutInfo['RequestID'] = $logoutRequest->getId();

	$logoutInfo['RelayState'] = $logoutRequest->getRelayState();

	SimpleSAML_Logger::debug('SAML2.0 - IDP.SingleLogoutService: Setting cached request with issuer ' . $spEntityId);

	$session->set_sp_logout_completed($spEntityId);


	/*
	 * We receive a Logout Response to a Logout Request that we have issued earlier.
	 */
} elseif (isset($_REQUEST['SAMLResponse'])) {

	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Got SAML response');

	$binding = SAML2_Binding::getCurrentBinding();

	try {
		$logoutResponse = $binding->receive();
		if (!($logoutResponse instanceof SAML2_LogoutResponse)) {
			throw new Exception('Received a response which wasn\'t a LogoutResponse ' .
				'on logout endpoint. Was: ' . get_class($logoutResponse));
		}

		$spEntityId = $logoutResponse->getIssuer();
		if ($spEntityId === NULL) {
			throw new Exception('Missing issuer in logout response.');
		}

		SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: SAML response parsed. Issuer is: ' . $spEntityId);
		$spMetadata = $metadata->getMetadataConfig($spEntityId, 'saml20-sp-remote');

		sspmod_saml2_Message::validateMessage($spMetadata, $idpMetadata, $logoutResponse);

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTRESPONSE', $exception);
	}

	/* Fetch the $logoutInfo variable based on the InResponseTo attribute of the response. */
	fetchLogoutInfo($logoutResponse->getInResponseTo());

	$session->set_sp_logout_completed($spEntityId);

	SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: got LogoutResponse from ' . $spEntityId);

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

/*
 * Find the next SP we should log out from. We will search through the list of
 * SPs until we find a valid SP with a SingleLogoutService endpoint.
 */
while (TRUE) {
	/* Dump the current sessions (for debugging). */
	$session->dump_sp_sessions();

	/*
	 * We proceed to send logout requests to all remaining SPs.
	 */
	$spEntityId = $session->get_next_sp_logout();

	// If there are no more SPs left, then we will not look for more SPs.
	if (empty($spEntityId)) {
		break;
	}

	try {
		$spMetadata = $metadata->getMetadataConfig($spEntityId, 'saml20-sp-remote');
	} catch (Exception $e) {
		/* It seems that an SP has disappeared from the metadata between login and logout. */
		SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Missing metadata for ' .
			$spEntityId . '; looking for more SPs.');
		continue;
	}

	$singleLogoutService = $spMetadata->getString('SingleLogoutService', NULL);
	if ($singleLogoutService === NULL) {
		SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: No SingleLogoutService for ' .
			$spEntityId . '; looking for more SPs.');
		continue;
	}

	/* $spEntityId now contains the next SP. */
	break;
}


if ($spEntityId) {

	SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Logout next SP ' . $spEntityId);

	try {

		$nameId = $session->getSessionNameId('saml20-sp-remote', $spEntityId);
		if($nameId === NULL) {
			$nameId = $session->getNameID();
		}

		$lr = sspmod_saml2_Message::buildLogoutRequest($idpMetadata, $spMetadata);
		$lr->setSessionIndex($session->getSessionIndex());
		$lr->setNameId($nameId);

		/* Save the $logoutInfo until we return from the SP. */
		saveLogoutInfo($lr->getId());

		$binding = new SAML2_HTTPRedirect();
		$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$binding->send($lr);

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATELOGOUTREQUEST', $exception);
	}
}

if ($config->getBoolean('debug', false))
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
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'LOGOUTINFOLOST');
	}

	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutService: Found logout info with these keys: ' . join(',', array_keys($logoutInfo)));

	/**
	 * Clean up session object to save storage.
	 */
	if ($config->getBoolean('debug', false))
		SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size before cleaning: ' . $session->getSize());

	$session->clean();

	if ($config->getBoolean('debug', false))
		SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size after cleaning: ' . $session->getSize());


	/*
	 * Check if the Single Logout procedure is initated by an SP (alternatively IdP initiated SLO
	 */
	if (array_key_exists('Issuer', $logoutInfo)) {

		$spEntityId = $logoutInfo['Issuer'];
		$spMetadata = $metadata->getMetadataConfig($spEntityId, 'saml20-sp-remote');

		$lr = sspmod_saml2_Message::buildLogoutResponse($idpMetadata, $spMetadata);
		$lr->setInResponseTo($logoutInfo['RequestID']);
		$lr->setRelayState($logoutInfo['RelayState']);
		$binding = new SAML2_HTTPRedirect();
		$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$binding->send($lr);

	} elseif (array_key_exists('RelayState', $logoutInfo)) {

		SimpleSAML_Utilities::redirect($logoutInfo['RelayState']);
		exit;

	} else {
		echo 'You are logged out'; exit;
	}

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATELOGOUTRESPONSE', $exception);
}
