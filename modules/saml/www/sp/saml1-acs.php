<?php

if (!array_key_exists('SAMLResponse', $_REQUEST) && !array_key_exists('SAMLart', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing SAMLResponse or SAMLart parameter.');
}

if (!array_key_exists('TARGET', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing TARGET parameter.');
}

$sourceId = $_SERVER['PATH_INFO'];
$end = strpos($sourceId, '/', 1);
if ($end === FALSE) {
	$end = strlen($sourceId);
}
$sourceId = substr($sourceId, 1, $end - 1);

$source = SimpleSAML_Auth_Source::getById($sourceId, 'sspmod_saml_Auth_Source_SP');


$state = SimpleSAML_Auth_State::loadState($_REQUEST['TARGET'], 'saml:sp:sso');

/* Check that the authentication source is correct. */
assert('array_key_exists("saml:sp:AuthId", $state)');
if ($state['saml:sp:AuthId'] !== $sourceId) {
	throw new SimpleSAML_Error_Exception('The authentication source id in the URL does not match the authentication source which sent the request.');
}

if (!isset($state['saml:idp'])) {
	/* We seem to have received a response without sending a request. */
	throw new SimpleSAML_Error_Exception('SAML 1 response received before SAML 1 request.');
}


$spMetadata = $source->getMetadata();

$idpEntityId = $state['saml:idp'];
$idpMetadata = $source->getIdPMetadata($idpEntityId);

if (array_key_exists('SAMLart', $_REQUEST)) {
	$responseXML = SimpleSAML_Bindings_Shib13_Artifact::receive($spMetadata, $idpMetadata);
	$isValidated = TRUE; /* Artifact binding validated with ssl certificate. */
} elseif (array_key_exists('SAMLResponse', $_REQUEST)) {
	$responseXML = $_REQUEST['SAMLResponse'];
	$responseXML = base64_decode($responseXML);
	$isValidated = FALSE; /* Must check signature on response. */
} else {
	assert('FALSE');
}

$response = new SimpleSAML_XML_Shib13_AuthnResponse();
$response->setXML($responseXML);

$response->setMessageValidated($isValidated);
$response->validate();

$responseIssuer = $response->getIssuer();
$attributes = $response->getAttributes();

if ($responseIssuer !== $idpEntityId) {
	throw new SimpleSAML_Error_Exception('The issuer of the response wasn\'t the destination of the request.');
}

$logoutState = array(
	'saml:logout:Type' => 'saml1'
	);
$state['LogoutState'] = $logoutState;

$source->handleResponse($state, $idpEntityId, $attributes);
assert('FALSE');

?>