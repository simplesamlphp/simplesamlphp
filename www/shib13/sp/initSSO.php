<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();


$session = SimpleSAML_Session::getInstance();
		

/*
 * Incomming URL parameters
 *
 * idpentityid 	optional	The entityid of the wanted IdP to authenticate with. If not provided will use default.
 * spentityid	optional	The entityid of the SP config to use. If not provided will use default to host.
 * RelayState	required	Where to send the user back to after authentication.
 *  
 */

SimpleSAML_Logger::info('Shib1.3 - SP.initSSO: Accessing Shib 1.3 SP initSSO script');

if (!$config->getValue('enable.shib13-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');


try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-shib13-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted');

} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}



if (!isset($session) || !$session->isValid('shib13') ) {
	
	if ($idpentityid == null) {
	
		SimpleSAML_Logger::notice('Shib1.3 - SP.initSSO: No chosen or default IdP, go to Shib13disco');
	
		$returnURL = urlencode(SimpleSAML_Utilities::selfURL());
		$discservice = '/' . $config->getValue('baseurlpath') . 'shib13/sp/idpdisco.php?entityID=' . $spentityid . 
			'&return=' . $returnURL . '&returnIDParam=idpentityid';
		SimpleSAML_Utilities::redirect($discservice);
		
	}
	
	
	try {
		$ar = new SimpleSAML_XML_Shib13_AuthnRequest($config, $metadata);
		$ar->setIssuer($spentityid);	
		if(isset($_GET['RelayState'])) 
			$ar->setRelayState($_GET['RelayState']);

		SimpleSAML_Logger::notice('Shib1.3 - SP.initSSO: SP (' . $spentityid . ') is sending AuthNRequest to IdP (' . $idpentityid . ')');

		$url = $ar->createRedirect($idpentityid);
		SimpleSAML_Utilities::redirect($url);
	
	} catch(Exception $exception) {		
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
	}

} else {

	
	$relaystate = $session->getRelayState();
	
	if (isset($relaystate) && !empty($relaystate)) {
		SimpleSAML_Logger::notice('Shib1.3 - SP.initSSO: Already Authenticated, Go back to RelayState');
		SimpleSAML_Utilities::redirect($relaystate);
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

}




?>