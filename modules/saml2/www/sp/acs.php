<?php

/**
 * Assertion consumer service handler for SAML 2.0 SP authentication client.
 */

if (!array_key_exists('SAMLResponse', $_POST)) {
	throw new SimpleSAML_Error_BadRequest('Missing SAMLResponse to AssertionConsumerService');
}

if (!array_key_exists('RelayState', $_POST)) {
	throw new SimpleSAML_Error_BadRequest('Missing RelayState to AssertionConsumerService');
}

$state = SimpleSAML_Auth_State::loadState($_POST['RelayState'], sspmod_saml2_Auth_Source_SP::STAGE_SENT);

/* Find authentication source. */
assert('array_key_exists(sspmod_saml2_Auth_Source_SP::AUTHID, $state)');
$sourceId = $state[sspmod_saml2_Auth_Source_SP::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

$binding = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
$authnResponse = $binding->decodeResponse($_POST);

$result = $authnResponse->process();

/* Check status code. */
if($result === FALSE) {
	/* Not successful. */
	SimpleSAML_Auth_State::throwException($state, $authnResponse->getStatus()->toException());
}

/* The response should include the entity id of the IdP. */
$idp = $authnResponse->getIssuer();

/* Check if the IdP is allowed to authenticate users for this authentication source. */
if (!$source->isIdPValid($idp)) {
	throw new Exception('Invalid IdP responded for authentication source with id ' . $sourceId .
		'. The IdP was ' . var_export($idp, TRUE));
}

/*
 * Retrieve the name identifier. We also convert it to the format used by the
 * logout request handler.
 */
$nameId = $authnResponse->getNameID();
$nameId['Value'] = $nameId['value'];
unset($nameId['value']);

/* We need to save the NameID and SessionIndex for logout. */
$logoutState = array(
	sspmod_saml2_Auth_Source_SP::LOGOUT_IDP => $idp,
	sspmod_saml2_Auth_Source_SP::LOGOUT_NAMEID => $nameId,
	sspmod_saml2_Auth_Source_SP::LOGOUT_SESSIONINDEX => $authnResponse->getSessionIndex(),
	);
$state['LogoutState'] = $logoutState;

$source->onLogin($idp, $state);

$state['Attributes'] = $authnResponse->getAttributes();
SimpleSAML_Auth_Source::completeAuth($state);

?>