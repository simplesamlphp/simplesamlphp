<?php

/**
 * Provide a URL for the module to statically link to.
 *
 * @author Mathias Meisfjordskar, University of Oslo.
 *         <mathias.meisfjordskar@usit.uio.no>
 * @package simpleSAMLphp
 */

$authStateId = $_REQUEST['AuthState'];

// sanitize the input
$sid = SimpleSAML_Utilities::parseStateID($authStateId);
if (!is_null($sid['url'])) {
	SimpleSAML_Utilities::checkURLAllowed($sid['url']);
}

$state = SimpleSAML_Auth_State::loadState($authStateId, sspmod_negotiate_Auth_Source_Negotiate::STAGEID);
SimpleSAML_Logger::debug('backend - fallback: '.$state['LogoutState']['negotiate:backend']);

sspmod_negotiate_Auth_Source_Negotiate::fallBack($state);

exit;
