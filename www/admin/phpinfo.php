<?php

require_once('../_include.php');

try {

	$config = SimpleSAML_Configuration::getInstance();
	$session = SimpleSAML_Session::getInstance();

	/* Make sure that the user has admin access rights. */
	SimpleSAML_Utilities::requireAdmin();
	phpinfo();

} catch(Exception $e) {
	SimpleSAML_Utilities::fatalError('na', NULL, $e);
}

?>