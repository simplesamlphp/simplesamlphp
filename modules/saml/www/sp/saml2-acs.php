<?php

/**
 * Assertion consumer service handler for SAML 2.0 SP authentication client.
 */

$sourceId = substr($_SERVER['PATH_INFO'], 1);
$source = SimpleSAML_Auth_Source::getById($sourceId, 'sspmod_saml_Auth_Source_SP');

$b = SAML2_Binding::getCurrentBinding();
$response = $b->receive();
if (!($response instanceof SAML2_Response)) {
	throw new SimpleSAML_Error_BadRequest('Invalid message received to AssertionConsumerService endpoint.');
}

$stateId = $response->getInResponseTo();
if (!empty($stateId)) {
	/* This is a response to a request we sent earlier. */
	$state = SimpleSAML_Auth_State::loadState($stateId, 'saml:sp:sso');

	/* Check that the authentication source is correct. */
	assert('array_key_exists("saml:sp:AuthId", $state)');
	if ($state['saml:sp:AuthId'] !== $sourceId) {
		throw new SimpleSAML_Error_Exception('The authentication source id in the URL does not match the authentication source which sent the request.');
	}
} else {
	/* This is an unsoliced response. */
	$state = array(
		'saml:sp:isUnsoliced' => TRUE,
		'saml:sp:AuthId' => $sourceId,
		'saml:sp:RelayState' => $response->getRelayState(),
	);
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

$nameId = $assertion->getNameId();
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
