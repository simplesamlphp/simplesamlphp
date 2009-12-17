<?php

if (isset($_REQUEST['RequestID'])) {
	/* Backwards-compatibility with old authentication pages. */
	$session = SimpleSAML_Session::getInstance();
	$requestcache = $session->getAuthnRequest('saml2', (string)$_REQUEST['RequestID']);
	if (!$requestcache) {
		throw new Exception('Could not retrieve cached RequestID = ' . $authId);
	}
	$state = $requestcache['State'];
	SimpleSAML_IdP::postAuth($state);

} else {
	throw new SimpleSAML_Error_BadRequest('Missing required URL parameter.');
}
