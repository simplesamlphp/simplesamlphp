<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
//require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
//require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);

$logger = new SimpleSAML_Logger();


/*
 * Incomming URL parameters
 *
 * idpentityid 		The entityid of the wanted IdP to authenticate with. If not provided will use default.
 * spentityid		The entityid of the SP config to use. If not provided will use default to host.
 * 
 */		

$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'SP.initSSO', 'EVENT', 'Access', 
	'Accessing SAML 2.0 SP initSSO script');


try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-saml20-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();

} catch (Exception $exception) {

	$et = new SimpleSAML_XHTML_Template($config, 'error.php');
	$et->data['message'] = 'Error loading SAML 2.0 metadata';	
	$et->data['e'] = $exception;	
	$et->show();
	exit(0);
}

if (!isset($session) || !$session->isValid('saml2') ) {
	
	
	if ($idpentityid == null) {
	
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSSO', 'NextDisco', $spentityid, 
			'No SP default or specified, go to SAML2disco');
		
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
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSSO', 'AuthnRequest', $idpentityid, 
			'SP (' . $spentityid . ') is sending authenticatino request to IdP (' . $idpentityid . ')');
		
		$httpredirect->sendMessage($req, $spentityid, $idpentityid, $relayState);

	
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['message'] = 'Some error occured when trying to issue the authentication request to the IdP.';	
		$et->data['e'] = $exception;
		
		$et->show();
		exit(0);
	}

} else {
	
	
	$relaystate = $_GET['RelayState'];
		
	if (isset($relaystate) && !empty($relaystate)) {
	
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSSO', 'AlreadyAuthenticated', '-', 
			'Go back to RelayState');
	
		SimpleSAML_Utilities::redirect($relaystate);
	} else {
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['message'] = 'Could not get relay state, do not know where to send the user.';	
		$et->data['e'] = new Exception();
		
		$et->show();

	
	}

}


?>