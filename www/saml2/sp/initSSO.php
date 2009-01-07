<?php

require_once('../../_include.php');

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

	if($idpentityid === NULL) {
		/* We are going to need the SP metadata to determine which IdP discovery service we should use. */
		$spmetadata = $metadata->getMetaDataCurrent('saml20-sp-hosted');
	}

} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}


/*
 * If no IdP can be resolved, send the user to the SAML 2.0 Discovery Service
 */
if ($idpentityid === NULL) {

	SimpleSAML_Logger::info('SAML2.0 - SP.initSSO: No chosen or default IdP, go to SAML2disco');

	/* Which IdP discovery service should we use? Can be set in SP metadata or in global configuration.
	 * Falling back to builtin discovery service.
	 */
	if(array_key_exists('idpdisco.url', $spmetadata)) {
		$discourl = $spmetadata['idpdisco.url'];
	} elseif($config->getValue('idpdisco.url.saml20', NULL) !== NULL) {
		$discourl = $config->getValue('idpdisco.url.saml20', NULL);
	} else {
		$discourl = SimpleSAML_Utilities::selfURLhost() . '/' . $config->getBaseURL() . 'saml2/sp/idpdisco.php';
	}

	if ($config->getValue('idpdisco.extDiscoveryStorage', NULL) != NULL) {
		
		$extDiscoveryStorage = $config->getValue('idpdisco.extDiscoveryStorage');
		
		SimpleSAML_Utilities::redirect($extDiscoveryStorage, array(
			'entityID' => $spentityid,
			'return' => SimpleSAML_Utilities::addURLparameter($discourl, array(
				'return' => SimpleSAML_Utilities::selfURL(),
				'remember' => 'true',
				'entityID' => $spentityid,
				'returnIDParam' => 'idpentityid',
			)),
			'returnIDParam' => 'idpentityid',
			'isPassive' => 'true')
		);
	}


	SimpleSAML_Utilities::redirect($discourl, array(
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

	/* Save request information. */
	$info = array();
	$info['RelayState'] = $_GET['RelayState'];
	if(array_key_exists('OnError', $_REQUEST)) {
		$info['OnError'] = $_REQUEST['OnError'];
	}
	$session->setData('SAML2:SP:SSO:Info', $sr->getGeneratedID(), $info);

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CREATEREQUEST', $exception);
}

?>