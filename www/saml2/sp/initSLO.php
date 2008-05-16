<?php

require_once('../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Configuration.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPRedirect.php');

$config = SimpleSAML_Configuration::getInstance();

$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: Accessing SAML 2.0 SP initSLO script');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');


if (isset($_REQUEST['RelayState'])) {
	$returnTo = $_REQUEST['RelayState'];
} else {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

	
if (isset($session) ) {
	
	try {
	
		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	
		$idpentityid = $session->getIdP();
		$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
	
		/**
		 * Create a logout request
		 */
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
		$req = $lr->generate($spentityid, $idpentityid, $session->getNameID(), $session->getSessionIndex(), 'SP');

		/* Save the $returnTo url until the user returns from the IdP. */
		$session->setData('spLogoutReturnTo', $lr->getGeneratedID(), $returnTo);
		
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		
		
		SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: SP (' . $spentityid . ') is sending logout request to IdP (' . $idpentityid . ')');
		
		$httpredirect->sendMessage($req, $spentityid, $idpentityid, NULL, 'SingleLogoutService', 'SAMLRequest', 'SP');
		

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
	}

} else {

	SimpleSAML_Logger::info('SAML2.0 - SP.initSLO: User is already logged out. Go back to relaystate');
	SimpleSAML_Utilities::redirect($returnTo);
	
}


?>