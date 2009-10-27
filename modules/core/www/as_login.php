<?php

/**
 * Endpoint for logging in with an authentication source.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!is_string($_REQUEST['ReturnTo'])) {
	throw new SimpleSAML_Error_BadRequest('Missing ReturnTo parameter.');
}

if (!is_string($_REQUEST['AuthId'])) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthId parameter.');
}

$as = new SimpleSAML_Auth_Simple($_REQUEST['AuthId']);
$as->requireAuth(array(
	'ReturnTo' => $_REQUEST['ReturnTo'],
));

SimpleSAML_Utilities::redirect($_REQUEST['ReturnTo']);
