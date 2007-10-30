<?php

require_once('../../_include.php');

require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/XML/MetaDataStore.php');
require_once('SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
//require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');



session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = new SimpleSAML_XML_MetaDataStore($config);

$session = SimpleSAML_Session::getInstance();

$logger = new SimpleSAML_Logger();

$idpentityid = $session->getIdP();
//	isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-saml20-idp') ;
$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();

$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'SP.initSLO', 'EVENT', 'Access', 
	'Accessing SAML 2.0 SP initSLO script');
	

if (isset($session) ) {
	
	try {
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
	
		// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
		$req = $lr->generate($spentityid, $idpentityid, $session->getNameID(), $session->getNameIDFormat(), $session->getSessionIndex(), 'SP');
		
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		
		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_GET['RelayState'])) {
			$relayState = $_GET['RelayState'];
		}
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSLO', 'LogoutRequest', $req->getRequestID(), 
			'SP (' . $spentityid . ') is sending logout request to IdP (' . $idpentityid . ')');
		
		//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
		$httpredirect->sendMessage($req, $idpentityid, $relayState, 'SingleLogoutService', 'SAMLRequest', 'SP');

	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');

		$et->$data['message'] = 'Some error occured when trying to issue the logout request to the IdP.';	
		$et->$data['e'] = $exception;
		
		$et->show();

	}

} else {

	
	$relaystate = $session->getRelayState();
	
	$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'SP.initSLO', 'AlreadyLoggedOut', $req->getRequestID(), 
		'User is already logged out. Go back to relaystate');
	
	header('Location: ' . $relaystate );
	
	#print_r($metadata->getMetaData('sam.feide.no'));
	#print_r($req);

}


?>