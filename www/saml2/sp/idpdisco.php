<?php

/**
 * WARNING:
 *
 * THIS FILE IS DEPRECATED AND WILL BE REMOVED IN FUTURE VERSIONS
 *
 * @deprecated
 */

require_once('../../_include.php');

SimpleSAML_Logger::warning('The file saml2/sp/idpdisco.php is deprecated and will be removed in future versions.');

try {
	$discoHandler = new SimpleSAML_XHTML_IdPDisco(array('saml20-idp-remote'), 'saml20');
} catch (Exception $exception) {
	/* An error here should be caused by invalid query parameters. */
	throw new SimpleSAML_Error_Error('DISCOPARAMS', $exception);
}

try {
	$discoHandler->handleRequest();
} catch(Exception $exception) {
	/* An error here should be caused by metadata. */
	throw new SimpleSAML_Error_Error('METADATA', $exception);
}

?>