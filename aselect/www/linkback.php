<?php

/**
 * Handle linkback() response from A-Select.
 */


if (!isset($_GET['stateID'])) {
	throw new SimpleSAML_Error_BadRequest('Missing stateID parameter.');
}
$stateId = (string)$_GET['stateID'];

if (!isset($_GET['aselect_credentials'])) {
	throw new SimpleSAML_Error_BadRequest('Missing aselect_credentials parameter.');
}
if (!isset($_GET['rid'])) {
	throw new SimpleSAML_Error_BadRequest('Missing ridparameter.');
}


$state = SimpleSAML_Auth_State::loadState($stateId, sspmod_aselect_Auth_Source_aselect::STAGE_INIT);
$state['aselect:credentials'] = $_GET['aselect_credentials'];
$state['aselect:rid'] = $_GET['rid'];


/* Find authentication source. */
assert('array_key_exists(sspmod_aselect_Auth_Source_aselect::AUTHID, $state)');
$sourceId = $state[sspmod_aselect_Auth_Source_aselect::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}

$source->finalStep($state);

