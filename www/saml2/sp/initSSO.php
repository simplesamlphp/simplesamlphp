<?php

require_once('../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPRedirect.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: Accessing SAML 2.0 SP initSSO script');

if (!$config->getValue('enable.saml20-sp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

/*
 * Incomming URL parameters
 *
 * idpentityid 	optional	The entityid of the wanted IdP to authenticate with. If not provided will use default.
 * spentityid	optional	The entityid of the SP config to use. If not provided will use default to host.
 * RelayState	required	Where to send the user back to after authentication.
 */		

if (empty($_GET['RelayState'])) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');
}

try {

	$idpentityid = isset($_GET['idpentityid']) ? $_GET['idpentityid'] : $config->getValue('default-saml20-idp') ;
	$spentityid = isset($_GET['spentityid']) ? $_GET['spentityid'] : $metadata->getMetaDataCurrentEntityID();

} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}


/*
 * If no IdP can be resolved, send the user to the SAML 2.0 Discovery Service
 */
if ($idpentityid == null) {

	SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: No chosen or default IdP, go to SAML2disco');

	SimpleSAML_Utilities::redirect('/' . $config->getBaseURL() . 'saml2/sp/idpdisco.php', array(
		'entityID' => $spentityid,
		'return' => SimpleSAML_Utilities::selfURL(),
		'returnIDParam' => 'idpentityid')
	);
}


/*
 * Create and send authentication request to the IdP.
 */
try {

	$sr = new SimpleSAML_XML_SAML20_AuthnRequest($config, $metadata);

	if (isset($_GET['IsPassive'])) { 
		$sr->setIsPassive($_GET['IsPassive']);
	};
	$md = $metadata->getMetaData($idpentityid, 'saml20-idp-remote');
	$req = $sr->generate($spentityid, $md['SingleSignOnService']);

	$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: SP (' . $spentityid . ') is sending AuthNRequest to IdP (' . $idpentityid . ')');
	
	$httpredirect->sendMessage($req, $spentityid, $idpentityid, $_GET['RelayState']);

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
}

?>