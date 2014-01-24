<?php
/*
 * Helper page for starting a admin login. Can be used as a target for links.
 */

if (!array_key_exists('ReturnTo', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing ReturnTo parameter.');
}

SimpleSAML_Utilities::requireAdmin();

SimpleSAML_Utilities::redirectUntrustedURL($_REQUEST['ReturnTo']);

