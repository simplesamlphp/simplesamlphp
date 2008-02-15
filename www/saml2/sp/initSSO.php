<?php

require_once('../../_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);



/**
 * Incomming URL parameters
 *
 * idpentityid 	optional	The entityid of the wanted IdP to authenticate with. If not provided will use default.
 * spentityid	optional	The entityid of the SP config to use. If not provided will use default to host.
 * RelayState	required	Where to send the user back to after authentication.
 * 
 */		

SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: Accessing SAML 2.0 SP initSSO script');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-saml20-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();

} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

if (!isset($session) || !$session->isValid('saml2') ) {
	
	
	if ($idpentityid == null) {
	
		SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: No chosen or default IdP, go to SAML2disco');
		
		$returnURL = urlencode(SimpleSAML_Utilities::selfURL());
		$discservice = '/' . $config->getValue('baseurlpath') . 'saml2/sp/idpdisco.php?entityID=' . $spentityid . 
			'&return=' . $returnURL . '&returnIDParam=idpentityid';
		SimpleSAML_Utilities::redirect($discservice);
	}
	
	
	try {
		$sr = new SimpleSAML_XML_SAML20_AuthnRequest($config, $metadata);
	
		$md = $metadata->getMetaData($idpentityid, 'saml20-idp-remote');
		$req = $sr->generate($spentityid, $md['SingleSignOnService']);

		
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		
		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_GET['RelayState'])) {
			$relayState = $_GET['RelayState'];
		}
		
		SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: SP (' . $spentityid . ') is sending AuthNRequest to IdP (' . $idpentityid . ')');
		
		$httpredirect->sendMessage($req, $spentityid, $idpentityid, $relayState);

	
	} catch(Exception $exception) {		
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
	}

} else {
	
	
	$relaystate = $_GET['RelayState'];
		
	if (isset($relaystate) && !empty($relaystate)) {
		SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: Already Authenticated, Go back to RelayState');
		SimpleSAML_Utilities::redirect($relaystate);
	} else {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
	}

}


?>