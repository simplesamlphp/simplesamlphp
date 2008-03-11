<?php

require_once('../../../www/_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Logger.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


SimpleSAML_Logger::info('SAML2.0 - IdP.initSLO: Accessing SAML 2.0 IdP endpoint init Single Logout');

if (!$config->getValue('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}


/**
 * If we get an incomming LogoutRequest then we initiate the logout process.
 * in this case an SAML 2.0 SP is sending an request, which also is referred to as
 * SP initiated Single Logout.
 *
 */
if (isset($_GET['RelayState'])) {

	$relaystate = $_GET['RelayState'];
	
	/** 
	 * No session exists. Just go to the RelayState.
	 */
	if($session === NULL) {
		SimpleSAML_Logger::info('SAML2.0 - IdP.initSLO: Did not find a session here, so we redirect to the RelayState');
		SimpleSAML_Utilities::redirect($relaystate);
		exit;
	}

	// Set local IdP session to invalid.
	$session->setAuthenticated(false, $session->getAuthority() );


	/*
	 * Create an assoc array of the request to store in the session cache.
	 */
	$requestcache = array(
		'RelayState' => $relaystate
	);
		
	$session->setLogoutRequest($requestcache);
	SimpleSAML_Logger::debug('SAML2.0 - IDP.initSSO: Setting cached request with relay state ' . $relaystate);
	
	//$session->set_sp_logout_completed($logoutrequest->getIssuer() );
	

}

$lookformore = true;
$spentityid = null;
do {
	/* Dump the current sessions (for debugging). */
	$session->dump_sp_sessions();

	/*
	 * We proceed to send logout requests to all remaining SPs.
	 */
	$spentityid = $session->get_next_sp_logout();
	
	
	// If there are no more SPs left, then we will not look for more SPs.
	if (empty($spentityid)) $lookformore = false;
	
	try {
		$spmetadata = $metadata->getMetadata($spentityid, 'saml20-sp-remote');
	} catch (Exception $e) {
		continue;
	}
	
	// If the SP we found have an SingleLogout endpoint then we will use it, and
	// hence we do not need to look for more yet.
	if (array_key_exists('SingleLogoutService', $spmetadata) && 
		!empty($spmetadata['SingleLogoutService']) ) $lookformore = false;
		
	if ($lookformore)
		SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Will not logout from ' . $spentityid . ' looking for more SPs');

} while ($lookformore);



/*
 * We proceed to send logout requests to the first remaining SP.
 */
$spentityid = $session->get_next_sp_logout();
if ($spentityid) {

	SimpleSAML_Logger::info('SAML2.0 - IDP.SingleLogoutService: Logout next SP ' . $spentityid);

	try {
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);

		// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
		$req = $lr->generate($idpentityid, $spentityid, $session->getNameID(), $session->getSessionIndex(), 'IdP');

		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);

		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_GET['RelayState'])) {
			$relayState = $_GET['RelayState'];
		}

		//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
		$httpredirect->sendMessage($req, $idpentityid, $spentityid, $relayState, 'SingleLogoutService', 'SAMLRequest', 'IdP');

		exit();

	} catch(Exception $exception) {

		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATELOGOUTREQUEST', $exception);

	}

}

if ($config->getValue('debug', false))
	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: LogoutService: All SPs done ');


if (isset($_GET['RelayState'])) {

	$relayState = $_GET['RelayState'];
	SimpleSAML_Utilities::redirect($relayState);

} else {
	
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NORELAYSTATE');

}



