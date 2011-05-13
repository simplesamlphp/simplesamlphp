<?php

/* Find the authentication state. */
if (!array_key_exists('AuthState', $_REQUEST) || empty($_REQUEST['AuthState'])) {
	throw new SimpleSAML_Error_BadRequest('Missing mandatory parameter: AuthState');
}

$authState = $_REQUEST['AuthState'];
$state = SimpleSAML_Auth_State::loadState($authState, 'openid:auth');
$sourceId = $state['openid:AuthId'];
$authSource = SimpleSAML_Auth_Source::getById($sourceId);
if ($authSource === NULL) {
	throw new SimpleSAML_Error_BadRequest('Invalid AuthId \'' . $sourceId . '\' - not found.');
}

try {
	$authSource->postAuth($state);
	/* postAuth() should never return. */
	assert('FALSE');
} catch (SimpleSAML_Error_Exception $e) {
	SimpleSAML_Auth_State::throwException($state, $e);
} catch (Exception $e) {
	SimpleSAML_Auth_State::throwException($state, new SimpleSAML_Error_AuthSource($sourceId, 'Error on OpenID linkback endpoint.', $e));
}
