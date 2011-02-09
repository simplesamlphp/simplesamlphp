<?php

/**
 * Handle linkback() response from Windows Live ID.
 */

if (array_key_exists('wrap_client_state', $_REQUEST)) {
	$stateId = $_REQUEST['wrap_client_state'];
	$state = SimpleSAML_Auth_State::loadState($stateId, sspmod_authwindowslive_Auth_Source_LiveID::STAGE_INIT);
} else {
	throw new Exception('Lost OAuth-WRAP Client State');
}

// http://msdn.microsoft.com/en-us/library/ff749771.aspx
if (array_key_exists('wrap_verification_code', $_REQUEST)) {

	// Good
	$state['authwindowslive:wrap_verification_code'] = $_REQUEST['wrap_verification_code'];

	if (array_key_exists('exp', $_REQUEST))
		$state['authwindowslive:wrap_exp'] = $_REQUEST['exp'];

} else {
	// wrap_error_reason = 'user_denied' means user chose not to login with LiveID
	// redirect them to their original page so they can choose another auth mechanism
	if ($_REQUEST['wrap_error_reason'] === 'user_denied') {
		$e = new SimpleSAML_Error_UserAborted();
		SimpleSAML_Auth_State::throwException($state, $e);
	}

	// Error
	throw new Exception('Authentication failed: [' . $_REQUEST['error_code'] . '] ' . $_REQUEST['wrap_error_reason']);
}

/* Find authentication source. */
assert('array_key_exists(sspmod_authwindowslive_Auth_Source_LiveID::AUTHID, $state)');
$sourceId = $state[sspmod_authwindowslive_Auth_Source_LiveID::AUTHID];

$source = SimpleSAML_Auth_Source::getById($sourceId);
if ($source === NULL) {
	throw new Exception('Could not find authentication source with id ' . $sourceId);
}

$source->finalStep($state);

SimpleSAML_Auth_Source::completeAuth($state);

