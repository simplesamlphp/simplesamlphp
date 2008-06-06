<?php

require_once('../_include.php');

try {

	$config = SimpleSAML_Configuration::getInstance();
	$session = SimpleSAML_Session::getInstance();

	/* Make sure that the user has admin access rights. */
	if (!isset($session) || !$session->isValid('login-admin') ) {
		SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'auth/login-admin.php',
		                               array('RelayState' => SimpleSAML_Utilities::selfURL())
		                               );
	}


	$stats = SimpleSAML_MemcacheStore::getStats();

	$template = new SimpleSAML_XHTML_Template($config, 'status-table.php');
	$template->data['title'] = 'Memcache stats';
	$template->data['table'] = $stats;
	$template->show();

} catch(Exception $e) {
	SimpleSAML_Utilities::fatalError('na', NULL, $e);
}

?>