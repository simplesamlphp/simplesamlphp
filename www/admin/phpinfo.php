<?php

require_once('../_include.php');
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Utilities.php');

try {

	$config = SimpleSAML_Configuration::getInstance();
	$session = SimpleSAML_Session::getInstance(true);

	/* Make sure that the user has admin access rights. */
	if (!isset($session) || !$session->isValid('login-admin') ) {
		SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		                               array('RelayState' => SimpleSAML_Utilities::selfURL())
		                               );
	}
	phpinfo();

} catch(Exception $e) {
	SimpleSAML_Utilities::fatalError('na', NULL, $e);
}

?>