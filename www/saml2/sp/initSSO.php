<?php

require_once('../../_include.php');


require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/XHTML/Template.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
//require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
//require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');

session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);
$session = SimpleSAML_Session::getInstance(true);

$logger = new SimpleSAML_Logger();


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

if (!isset($session) || !$session->isValid() ) {
	
	
	if ($idpentityid == null) {
	
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSSO', 'NextDisco', $spentityid, 
			'No SP default or specified, go to SAML2disco');
		
		$returnURL = urlencode(SimpleSAML_Utilities::selfURL());
		$discservice = '/' . $config->getValue('baseurlpath') . 'saml2/sp/idpdisco.php?entityID=' . $spentityid . 
			'&return=' . $returnURL . '&returnIDParam=idpentityid';
		header('Location: ' . $discservice);
		exit(0);
		
	}
	
	
	try {
		$sr = new SimpleSAML_XML_SAML20_AuthnRequest($config, $metadata);
	
		$req = $sr->generate($spentityid);
		
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		
		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_GET['RelayState'])) {
			$relayState = $_GET['RelayState'];
		}
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSSO', 'AuthnRequest', $idpentityid, 
			'SP (' . $spentityid . ') is sending authenticatino request to IdP (' . $idpentityid . ')');
		
		$httpredirect->sendMessage($req, $idpentityid, $relayState);

	
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
	
		header('Location: ' . $relaystate );
	} else {
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->data['message'] = 'Could not get relay state, do not know where to send the user.';	
		$et->data['e'] = new Exception();
		
		$et->show();

	
	}

}


?>