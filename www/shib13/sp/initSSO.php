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
 * idpentityid 		The entityid of the wanted IdP to authenticate with. If not provided will use default.
 * spentityid		The entityid of the SP config to use. If not provided will use default to host.
 * 
 */

try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-shib13-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID('shib13-sp-hosted');

} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}



if (!isset($session) || !$session->isValid('shib13') ) {
	
	if ($idpentityid == null) {
	
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

		$url = $ar->createRedirect($idpentityid);
		SimpleSAML_Utilities::redirect($url);
	
	} catch(Exception $exception) {		
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
	}

} else {

	
	$relaystate = $session->getRelayState();
	
	if (isset($relaystate) && !empty($relaystate)) {
		SimpleSAML_Utilities::redirect($relaystate);
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

}




?>