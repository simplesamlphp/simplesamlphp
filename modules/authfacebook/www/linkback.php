<?php

/**
 * Handle linkback() response from Facebook.
 */
 

#if (!array_key_exists('StateID', $_GET))
#	throw new SimpleSAML_Error_BadRequest('Missing StateID to facebook linkback endpoint');

if (!array_key_exists('next', $_GET))
	throw new SimpleSAML_Error_BadRequest('Missing parameter [next] to facebook linkback endpoint');

$stateID = $_GET['next'];

$state = SimpleSAML_Auth_State::loadState($stateID, sspmod_authfacebook_Auth_Source_Facebook::STAGE_INIT);

/* Find authentication source. */
assert('array_key_exists(sspmod_authfacebook_Auth_Source_Facebook::AUTHID, $state)');
$sourceId = $state[sspmod_authfacebook_Auth_Source_Facebook::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}

$config = SimpleSAML_Configuration::getInstance();

$source->authenticate($state);

SimpleSAML_Auth_Source::completeAuth($state);

?>