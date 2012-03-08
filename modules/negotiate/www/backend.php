<?php

/**
 * Provide a URL for the module to statically link to.
 *
 * @author Mathias Meisfjordskar, University of Oslo.
 *         <mathias.meisfjordskar@usit.uio.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

$authStateId = $_REQUEST['AuthState'];
$state = SimpleSAML_Auth_State::loadState($authStateId, sspmod_negotiate_Auth_Source_Negotiate::STAGEID);
SimpleSAML_Logger::debug('backend - fallback: '.$state['LogoutState']['negotiate:backend']);

sspmod_negotiate_Auth_Source_Negotiate::fallBack($state);

exit;
