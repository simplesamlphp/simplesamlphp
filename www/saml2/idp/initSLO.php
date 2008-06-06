<?php

require_once('../../../www/_include.php');

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.initSLO: Accessing SAML 2.0 IdP endpoint init Single Logout');

if (!$config->getValue('enable.saml20-idp', false)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');
}


if (!isset($_GET['RelayState'])) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

$returnTo = $_GET['RelayState'];

/* We turn processing over to the SingleLogoutService script. */
SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'saml2/idp/SingleLogoutService.php',
	array('ReturnTo' => $returnTo));

?>