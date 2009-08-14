<?php

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();

$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('WS-Fed - SP.initSLO: Accessing WS-Fed SP initSLO script');

if (!$config->getBoolean('enable.wsfed-sp', false))
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
		
		$session->doLogout();
		
		SimpleSAML_Logger::info('WS-Fed - SP.initSLO: SP (' . $spentityid . ') is sending logout request to IdP (' . $idpentityid . ')');
			
		$idpmeta = $metadata->getMetaData($idpentityid, 'wsfed-idp-remote');
		
		SimpleSAML_Utilities::redirect($idpmeta['prp'], array(
			'wa' => 'wsignout1.0',
			'wct' =>  gmdate('Y-m-d\TH:i:s\Z', time()),
			'wtrealm' => $spentityid,
			'wctx' => $returnTo
		));
		

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
	}

} else {

	SimpleSAML_Logger::info('WS-Fed - SP.initSLO: User is already logged out. Go back to relaystate');
	SimpleSAML_Utilities::redirect($returnTo);
	
}


?>