<?php

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();

$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: Accessing SAML 2.0 SP initSLO script');

if (!$config->getBoolean('enable.saml20-sp', TRUE))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');


if (isset($_REQUEST['RelayState'])) {
	$returnTo = $_REQUEST['RelayState'];
} else {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}


try {
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

	$idpEntityId = $session->getIdP();
	if ($idpEntityId === NULL) {
		SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: User not authenticated with an IdP.');
		SimpleSAML_Utilities::redirect($returnTo);
	}
	$idpMetadata = $metadata->getMetaDataConfig($idpEntityId, 'saml20-idp-remote');
	if (!$idpMetadata->hasValue('SingleLogoutService')) {
		SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: No SingleLogoutService endpoint in IdP.');
		SimpleSAML_Utilities::redirect($returnTo);
	}

	$spEntityId = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-hosted');

	$nameId = $session->getNameId();

	$lr = sspmod_saml2_Message::buildLogoutRequest($spMetadata, $idpMetadata);
	$lr->setNameId($nameId);
	$lr->setSessionIndex($session->getSessionIndex());

	$session->doLogout();

	/* Save the $returnTo url until the user returns from the IdP. */
	$session->setData('spLogoutReturnTo', $lr->getId(), $returnTo);

	SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: SP (' . $spEntityId . ') is sending logout request to IdP (' . $idpEntityId . ')');

	$b = new SAML2_HTTPRedirect();
	$b->setDestination(sspmod_SAML2_Message::getDebugDestination());
	$b->send($lr);


} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
}


?>