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
	$statusCode = $authnResponse->findstatus();
	throw new Exception('Error authenticating: ' . $statusCode);
}

/* The response should include the entity id of the IdP. */
$idp = $authnResponse->getIssuer();

/* TODO: Check that IdP is the correct IdP. */

/* TODO: Save NameID & SessionIndex for logout. */

$state['Attributes'] = $authnResponse->getAttributes();
SimpleSAML_Auth_Source::completeAuth($state);

?>