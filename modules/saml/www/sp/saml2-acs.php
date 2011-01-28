<?php

/**
 * Assertion consumer service handler for SAML 2.0 SP authentication client.
 */

$sourceId = substr($_SERVER['PATH_INFO'], 1);
$source = SimpleSAML_Auth_Source::getById($sourceId, 'sspmod_saml_Auth_Source_SP');
$spMetadata = $source->getMetadata();

$b = SAML2_Binding::getCurrentBinding();
if ($b instanceof SAML2_HTTPArtifact) {
	$b->setSPMetadata($spMetadata);
}

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

SimpleSAML_Logger::debug('Received SAML2 Response from ' . var_export($idp, TRUE) . '.');

$idpMetadata = $source->getIdPmetadata($idp);

try {
	$assertions = sspmod_saml_Message::processResponse($spMetadata, $idpMetadata, $response);
} catch (sspmod_saml_Error $e) {
	/* The status of the response wasn't "success". */
	$e = $e->toException();
	SimpleSAML_Auth_State::throwException($state, $e);
}


$authenticatingAuthority = NULL;
$nameId = NULL;
$sessionIndex = NULL;
$expire = NULL;
$attributes = array();
$foundAuthnStatement = FALSE;
foreach ($assertions as $assertion) {

	/* Check for duplicate assertion (replay attack). */
	$store = SimpleSAML_Store::getInstance();
	if ($store !== FALSE) {
		$aID = $assertion->getId();
		if ($store->get('saml.AssertionReceived', $aID) !== NULL) {
			$e = new SimpleSAML_Error_Exception('Received duplicate assertion.');
			SimpleSAML_Auth_State::throwException($state, $e);
		}

		$notOnOrAfter = $assertion->getNotOnOrAfter();
		if ($notOnOrAfter === NULL) {
			$notOnOrAfter = time() + 24*60*60;
		} else {
			$notOnOrAfter += 60; /* We allow 60 seconds clock skew, so add it here also. */
		}

		$store->set('saml.AssertionReceived', $aID, TRUE, $notOnOrAfter);
	}


	if ($authenticatingAuthority === NULL) {
		$authenticatingAuthority = $assertion->getAuthenticatingAuthority();
	}
	if ($nameId === NULL) {
		$nameId = $assertion->getNameId();
	}
	if ($sessionIndex === NULL) {
		$sessionIndex = $assertion->getSessionIndex();
	}
	if ($expire === NULL) {
		$expire = $assertion->getSessionNotOnOrAfter();
	}

	$attributes = array_merge($attributes, $assertion->getAttributes());

	if ($assertion->getAuthnInstant() !== NULL) {
		/* Assertion contains AuthnStatement, since AuthnInstant is a required attribute. */
		$foundAuthnStatement = TRUE;
	}
}

if (!$foundAuthnStatement) {
	$e = new SimpleSAML_Error_Exception('No AuthnStatement found in assertion(s).');
	SimpleSAML_Auth_State::throwException($state, $e);
}

if ($expire === NULL) {
	/* Just expire the logout associtaion 24 hours into the future. */
	$expire = time() + 24*60*60;
}

/* Register this session in the logout store. */
sspmod_saml_SP_LogoutStore::addSession($sourceId, $nameId, $sessionIndex, $expire);

/* We need to save the NameID and SessionIndex for logout. */
$logoutState = array(
	'saml:logout:Type' => 'saml2',
	'saml:logout:IdP' => $idp,
	'saml:logout:NameID' => $nameId,
	'saml:logout:SessionIndex' => $sessionIndex,
	);
$state['LogoutState'] = $logoutState;
$state['saml:AuthenticatingAuthority'] = $authenticatingAuthority;
$state['saml:AuthenticatingAuthority'][] = $idp;
$state['PersistentAuthData'][] = 'saml:AuthenticatingAuthority';

$state['saml:sp:IdP'] = $idp;
$state['PersistentAuthData'][] = 'saml:sp:IdP';
$state['saml:sp:NameID'] = $nameId;
$state['PersistentAuthData'][] = 'saml:sp:NameID';
$state['saml:sp:SessionIndex'] = $sessionIndex;
$state['PersistentAuthData'][] = 'saml:sp:SessionIndex';


$source->handleResponse($state, $idp, $attributes);
assert('FALSE');
