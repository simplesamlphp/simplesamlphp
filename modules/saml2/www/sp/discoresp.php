<?php

/**
 * Handler for response from IdP discovery service.
 */

if (!array_key_exists('AuthID', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthID to discovery service response handler');
}

if (!array_key_exists('idpentityid', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing idpentityid to discovery service response handler');
}

$state = SimpleSAML_Auth_State::loadState($_REQUEST['AuthID'], sspmod_saml2_Auth_Source_SP::STAGE_DISCO);

/* Find authentication source. */
assert('array_key_exists(sspmod_saml2_Auth_Source_SP::AUTHID, $state)');
$sourceId = $state[sspmod_saml2_Auth_Source_SP::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}

$source->initSSO($_REQUEST['idpentityid'], $state);

?>