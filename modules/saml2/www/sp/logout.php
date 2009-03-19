<?php

/**
 * Logout endpoint handler for SAML 2.0 SP authentication client.
 *
 * This endpoint handles both logout requests and logout responses.
 */

if (!array_key_exists('PATH_INFO', $_SERVER)) {
	throw new SimpleSAML_Error_BadRequest('Missing authentication source id in logout URL');
}

$sourceId = substr($_SERVER['PATH_INFO'], 1);

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

if (array_key_exists('SAMLResponse', $_GET)) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	$response = $binding->decodeLogoutResponse($_GET);

	if ($binding->validateQuery($response->getIssuer(), 'SP', 'SAMLResponse')) {
		SimpleSAML_Logger::debug('module/saml2/sp/logout: Valid signature found on response');
	}

	if (!array_key_exists('RelayState', $_REQUEST)) {
		throw new SimpleSAML_Error_BadRequest('Missing RelayState in logout response.');
	}

	$stateId = $_REQUEST['RelayState'];
	$state = SimpleSAML_Auth_State::loadState($_REQUEST['RelayState'], sspmod_saml2_Auth_Source_SP::STAGE_LOGOUTSENT);


	SimpleSAML_Auth_Source::completeLogout($state);

} elseif (array_key_exists('SAMLRequest', $_GET)) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

	$request = $binding->decodeLogoutRequest($_GET);

	$requestId = $request->getRequestID();
	$idpEntityId = $request->getIssuer();
	$relayState = $request->getRelayState();

	if ($binding->validateQuery($idpEntityId, 'SP')) {
		SimpleSAML_Logger::debug('module/saml2/sp/logout: Valid signature found on request');
	}


	$spEntityId = $source->getEntityId();

	SimpleSAML_Logger::debug('module/saml2/sp/logout: Request from ' . $idpEntityId . ' with request id ' . $requestId);
	SimpleSAML_Logger::stats('saml20-idp-SLO idpinit ' . $spEntityId . ' ' . $idpEntityId);

	/* Notify source of logout, so that it may call logout callbacks. */
	$source->onLogout($idpEntityId);

	/* Create an send response. */
	$lr = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
	$responseXML = $lr->generate($spEntityId, $idpEntityId, $requestId, 'SP');
	$binding->sendMessage($responseXML, $spEntityId, $idpEntityId, $relayState, 'SingleLogoutServiceResponse', 'SAMLResponse');

} else {
	throw new SimpleSAML_Error_BadRequest('Missing request or response to logout endpoint');
}
