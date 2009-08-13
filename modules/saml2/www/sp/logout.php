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

$binding = SAML2_Binding::getCurrentBinding();
$message = $binding->receive();

$idpEntityId = $message->getIssuer();
if ($idpEntityId === NULL) {
	/* Without an issuer we have no way to respond to the message. */
	throw new SimpleSAML_Error_BadRequest('Received message on logout endpoint without issuer.');
}

$spEntityId = $source->getEntityId();

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-remote');
$spMetadata = $source->getMetadata();

sspmod_saml2_Message::validateMessage($idpMetadata, $spMetadata, $message);

if ($message instanceof SAML2_LogoutResponse) {

	$relayState = $message->getRelayState();
	if ($relayState === NULL) {
		/* Somehow, our RelayState has been lost. */
		throw new SimpleSAML_Error_BadRequest('Missing RelayState in logout response.');
	}

	if (!$message->isSuccess()) {
		SimpleSAML_Logger::warning('Unsuccessful logout. Status was: ' . sspmod_saml2_Message::getResponseError($message));
	}

	$state = SimpleSAML_Auth_State::loadState($relayState, sspmod_saml2_Auth_Source_SP::STAGE_LOGOUTSENT);
	SimpleSAML_Auth_Source::completeLogout($state);

} elseif ($message instanceof SAML2_LogoutRequest) {

	SimpleSAML_Logger::debug('module/saml2/sp/logout: Request from ' . $idpEntityId);
	SimpleSAML_Logger::stats('saml20-idp-SLO idpinit ' . $spEntityId . ' ' . $idpEntityId);

	/* Notify source of logout, so that it may call logout callbacks. */
	$source->onLogout($idpEntityId);

	/* Create an send response. */
	$lr = sspmod_saml2_Message::buildLogoutResponse($spMetadata, $idpMetadata);
	$lr->setRelayState($message->getRelayState());
	$lr->setInResponseTo($message->getId());

	$binding = new SAML2_HTTPRedirect();
	$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
	$binding->send($lr);
} else {
	throw new SimpleSAML_Error_BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
}
