<?php

/**
 * Assertion consumer service handler for SAML 2.0 SP authentication client.
 */

$b = SAML2_Binding::getCurrentBinding();
$response = $b->receive();
if (!($response instanceof SAML2_Response)) {
	throw new SimpleSAML_Error_BadRequest('Invalid message received to AssertionConsumerService endpoint.');
}

$relayState = $response->getRelayState();
if (empty($relayState)) {
	throw new SimpleSAML_Error_BadRequest('Missing relaystate in message received on AssertionConsumerService endpoint.');
}

$state = SimpleSAML_Auth_State::loadState($relayState, 'saml:sp:ssosent-saml2');

/* Find authentication source. */
assert('array_key_exists("saml:sp:AuthId", $state)');
$sourceId = $state['saml:sp:AuthId'];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}
if (!($source instanceof sspmod_saml_Auth_Source_SP)) {
	throw new SimpleSAML_Error_Exception('Source type changed?');
}


$idp = $response->getIssuer();
if ($idp === NULL) {
	throw new Exception('Missing <saml:Issuer> in message delivered to AssertionConsumerService.');
}

$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$idpMetadata = $source->getIdPmetadata($idp);
$spMetadata = $source->getMetadata();

try {
	$assertion = sspmod_saml2_Message::processResponse($spMetadata, $idpMetadata, $response);
} catch (sspmod_saml2_Error $e) {
	/* The status of the response wasn't "success". */
	$e = $e->toException();
	SimpleSAML_Auth_State::throwException($state, $e);
}

$nameId = $assertion->getNameID();
$sessionIndex = $assertion->getSessionIndex();

/* We need to save the NameID and SessionIndex for logout. */
$logoutState = array(
	'saml:logout:Type' => 'saml2',
	'saml:logout:IdP' => $idp,
	'saml:logout:NameID' => $nameId,
	'saml:logout:SessionIndex' => $sessionIndex,
	);
$state['LogoutState'] = $logoutState;

$source->handleResponse($state, $idp, $assertion->getAttributes());
assert('FALSE');
