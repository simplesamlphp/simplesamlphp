<?php

require_once('../../../www/_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.initSLO: Accessing SAML 2.0 IdP endpoint init Single Logout');

if (!$config->getBoolean('enable.saml20-idp', false)) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');
}


if (!isset($_GET['RelayState'])) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

$returnTo = $_GET['RelayState'];

$slo = $metadata->getGenerated('SingleLogoutService', 'saml20-idp-hosted');

/* We turn processing over to the SingleLogoutService script. */
SimpleSAML_Utilities::redirect($slo, array('ReturnTo' => $returnTo));

?>