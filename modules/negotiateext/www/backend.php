<?php

/**
 * Provide a URL for the module to statically link to.
 *
 * @author Mathias Meisfjordskar, University of Oslo.
 *         <mathias.meisfjordskar@usit.uio.no>
 * @package SimpleSAMLphp
 */

if (!isset($_REQUEST['AuthState'])) {
	throw new SimpleSAML_Error_BadRequest('Missing "AuthState" parameter.');
}
$state = SimpleSAML_Auth_State::loadState($_REQUEST['AuthState'], sspmod_negotiateext_Auth_Source_Negotiate::STAGEID);
SimpleSAML\Logger::debug('backend - fallback: '.$state['LogoutState']['negotiate:backend']);

sspmod_negotiateext_Auth_Source_Negotiate::fallBack($state);

exit;
