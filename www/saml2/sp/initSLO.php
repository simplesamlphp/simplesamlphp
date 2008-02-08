<?php

require_once('../../_include.php');

require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

$session = SimpleSAML_Session::getInstance();

$logger = new SimpleSAML_Logger();

if (isset($session) ) {
	
	try {
	
		$idpentityid = $session->getIdP();
		$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();
		
		$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'SP.initSLO', 'EVENT', 'Access', 
			'Accessing SAML 2.0 SP initSLO script');
	
		/**
		 * Create a logout request
		 */
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
		$req = $lr->generate($spentityid, $idpentityid, $session->getNameID(), $session->getSessionIndex(), 'SP');
		
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		
		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_REQUEST['RelayState'])) {
			$relayState = $_REQUEST['RelayState'];
		}
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSLO', 'LogoutRequest', 'N/A', 
			'SP (' . $spentityid . ') is sending logout request to IdP (' . $idpentityid . ')');
		
		$httpredirect->sendMessage($req, $spentityid, $idpentityid, $relayState, 'SingleLogoutService', 'SAMLRequest', 'SP');
		

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
	}

} else {

	if (!isset($_REQUEST['RelayState']))
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');		
	
	$relaystate = $_REQUEST['RelayState'];
	
	$logger->log(LOG_NOTICE, 'NA', 'SAML2.0', 'SP.initSLO', 'AlreadyLoggedOut', 'N/A', 
		'User is already logged out. Go back to relaystate');
	
	SimpleSAML_Utilities::redirect($relaystate);
	
}


?>