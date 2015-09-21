<?php
/**
 * This is the handler for logout started from the consent page.
 *
 * @package simpleSAMLphp
 */

if (!array_key_exists('StateId', $_GET)) {
    throw new SimpleSAML_Error_BadRequest('Missing required StateId query parameter.');
}
$id = (string)$_GET['StateId'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($id);
if (!is_null($sid['url'])) {
	SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($id, 'consent:request');

$state['Responder'] = array('sspmod_consent_Logout', 'postLogout');

$idp = SimpleSAML_IdP::getByState($state);
$idp->handleLogoutRequest($state, NULL);
assert('FALSE');
