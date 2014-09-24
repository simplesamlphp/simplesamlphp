<?php

/**
 * Handle linkback() response from Janrain.
 */
 
if (!array_key_exists('AuthState', $_REQUEST) || empty($_REQUEST['AuthState'])) {
	throw new SimpleSAML_Error_BadRequest('Missing state parameter on Janrain linkback endpoint.');
}

$stateID = $_REQUEST['AuthState'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($stateID);
if (!is_null($sid['url'])) {
	SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($stateID, sspmod_authjanrain_Auth_Source_JanrainRegistration::STAGE_INIT);

/* Find authentication source. */
if (!array_key_exists(sspmod_authjanrain_Auth_Source_JanrainRegistration::AUTHID, $state)) {
	throw new SimpleSAML_Error_BadRequest('No data in state for ' . sspmod_authjanrain_Auth_Source_JanrainRegistration::AUTHID);
}
$sourceId = $state[sspmod_authjanrain_Auth_Source_JanrainRegistration::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new SimpleSAML_Error_BadRequest('Could not find authentication source with id ' . var_export($sourceId, TRUE));
}

try {
	if (isset($_REQUEST['error_reason']) && $_REQUEST['error_reason'] == 'user_denied') {
		throw new SimpleSAML_Error_UserAborted();
	}

	$source->finalStep($state);
} catch (SimpleSAML_Error_Exception $e) {
	SimpleSAML_Auth_State::throwException($state, $e);
} catch (Exception $e) {
	SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_AuthSource($sourceId, 'Error on Janrain linkback endpoint.', $e));
}

SimpleSAML_Auth_Source::completeAuth($state);

?>