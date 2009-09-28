<?php

/**
 * IdP Initiated Single Log-Out. Requires one parameter: RelayState.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../_include.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();

SimpleSAML_Logger::info('SAML2.0 - IdP.idpInitSingleLogoutServiceiFrame: Accessing SAML 2.0 IdP endpoint SingleLogoutService (iFrame version)');

SimpleSAML_Logger::debug('Initially; ' . join(',', $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE)));

if (!$config->getBoolean('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError(isset($session) ? $session->getTrackID() : null, 'NOACCESS');

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idpMetadata = $metadata->getMetaDataConfig($idpentityid, 'saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: Got IdP entity id: ' . $idpentityid);



$logouttype = 'traditional';
$idpmeta = $metadata->getMetaDataCurrent('saml20-idp-hosted');
if (array_key_exists('logouttype', $idpmeta)) $logouttype = $idpmeta['logouttype'];

if ($logouttype !== 'iframe') 
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS', new Exception('This IdP is configured to use logout type [' . $logouttype . '], but this endpoint is only available for IdP using logout type [iframe]'));







/**
 * This function retrieves the logout info with the given ID.
 *
 * @param $id  The identifier of the logout info.
 */
function fetchLogoutInfo($id) {
	global $session;
	global $logoutInfo;

	$logoutInfo = $session->getData('idplogoutresponsedata', $id);

	if($logoutInfo === NULL) {
		SimpleSAML_Logger::warning('SAML2.0 - IdP.SingleLogoutService: Lost logout information.');
	}
}


/**
 * This function saves the logout info with the given ID.
 *
 * @param $id  The identifier the logout info should be saved with.
 */
function saveLogoutInfo($id) {
	global $session;
	global $logoutInfo;

	$session->setData('idplogoutresponsedata', $id, $logoutInfo);
}


// Include XAJAX definition.
require_once(SimpleSAML_Utilities::resolvePath('libextinc') . '/xajax/xajax.inc.php');



/*
 * This function is called via AJAX and will send LogoutRequest to one single SP by
 * sending a LogoutRequest using HTTP-REDIRECT
 */
function updateslostatus() {

	SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutServiceiFrame: Accessing SAML 2.0 IdP endpoint SingleLogoutService (iFrame version) within updateslostatus() ');

	$config = SimpleSAML_Configuration::getInstance();
	$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
	$session = SimpleSAML_Session::getInstance();

	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	$templistofsps = $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE);
	$listofsps = array();
	foreach ($templistofsps AS $spentityid) {
		if (!empty($_COOKIE['spstate-' . sha1($spentityid)])) {
			$listofsps[] = $spentityid;
			continue;
		}

		try {
			$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		} catch (Exception $e) {
			/*
			 * For some reason, the metadata for this SP is no longer available. Most
			 * likely it was deleted from the IdP while the user had a session to it.
			 * In any case - skip this SP.
			 */
			$listofsps[] = $spentityid;
			continue;
		}

		if (!isset($spmetadata['SingleLogoutService'])) {
			/* No logout endpoint. */
			$listofsps[] = $spentityid;
			continue;
		}

		/* This SP isn't ready yet. */
	}
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: templistofsps ' . join(',', $templistofsps));
	SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame:     listofsps ' . join(',', $listofsps));


	// Using template object to be able to translate name of service provider.
	$t = new SimpleSAML_XHTML_Template($config, 'logout-iframe.php');

    // Instantiate the xajaxResponse object
    $objResponse = new xajaxResponse();

	foreach ($listofsps AS $spentityid) {

		SimpleSAML_Logger::debug('SAML2.0 - IdP.SingleLogoutServiceiFrame: Completed ' . $spentityid);
		
		// add a command to the response to assign the innerHTML attribute of
		// the element with id="SomeElementId" to whatever the new content is
		
		$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		$name = array_key_exists('name', $spmetadata) ? $spmetadata['name'] : $spentityid;
		
		$spname = is_array($name) ? $t->getTranslation($name) : $name;
		
		$objResponse->addScriptCall('slocompletesp', 'e' . sha1($spentityid));

	}
	
	if (count($templistofsps) === count($listofsps)) {

		$templistofsps = $session->get_sp_list(SimpleSAML_Session::STATE_ONLINE);
		foreach ($templistofsps AS $spentityid) {
			$session->set_sp_logout_completed($spentityid);
			setcookie('spstate-' . sha1($spentityid) , '', time() - 3600); // Delete cookie
		}

		$objResponse->addScriptCall('slocompleted');

		/**
		 * Clean up session object to save storage.
		 */
		if ($config->getBoolean('debug', false))
			SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size before cleaning: ' . $session->getSize());
			
		$session->clean();
		
		if ($config->getBoolean('debug', false))
			SimpleSAML_Logger::info('SAML2.0 - IdP.SingleLogoutService: Session Size after cleaning: ' . $session->getSize());

	} else {
		SimpleSAML_Logger::debug('SAML2.0 - sp_logout_completed FALSE');
	}
    
    //return the  xajaxResponse object
    return $objResponse;
}



$xajax = new xajax();
$xajax->registerFunction("updateslostatus");
$xajax->processRequests();




/**
 * Which URL to send the user to after logout?
 */
$relayState = NULL;
if (array_key_exists('RelayState', $_REQUEST)) $relayState = $_REQUEST['RelayState'];

// Do logout from the IdP
$session->doLogout();

// Debug entries in the log about what services the user is logged into.
$session->dump_sp_sessions();



/*
 * Generate a list of all service providers, and create a LogoutRequest message for all these SPs.
 */
$listofsps = $session->get_sp_list();
$sparray = array();
$sparrayNoLogout = array();
foreach ($listofsps AS $spentityid) {

	// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
	$nameId = $session->getSessionNameId('saml20-sp-remote', $spentityid);
	if($nameId === NULL) {
		$nameId = $session->getNameID();
	}

	$spMetadata = $metadata->getMetaDataConfig($spentityid, 'saml20-sp-remote');
	$name = $spMetadata->getValue('name', $spentityid);

	try {	
		$lr = sspmod_saml2_Message::buildLogoutRequest($idpMetadata, $spMetadata);
		$lr->setSessionIndex($session->getSessionIndex());
		$lr->setNameId($nameId);

		$httpredirect = new SAML2_HTTPRedirect();
		$url = $httpredirect->getRedirectURL($lr);

		$sparray[$spentityid] = array('url' => $url, 'name' => $name);
		
	} catch (Exception $e) {
		
		$sparrayNoLogout[$spentityid] = array('name' => $name);
		
	}

}


SimpleSAML_Logger::debug('SAML2.0 - SP Counter. other SPs with SLO support (' . count($sparray) . ')  without SLO support (' . count($sparrayNoLogout) . ')');


#print_r($sparray);




/*
 * If the user is not logged into any other SPs.
 */
if (count($sparray) + count($sparrayNoLogout) === 0) {
	SimpleSAML_Utilities::redirect($relayState);
	exit;
} 


$et = new SimpleSAML_XHTML_Template($config, 'logout-iframe.php');

$et->data['header'] = 'Logout';
$et->data['sparray'] = $sparray;
$et->data['sparrayNoLogout'] = $sparrayNoLogout;

$et->data['logoutresponse'] = $relayState;
$et->data['xajax'] = $xajax;

#$et->data['idpInitRelayState'] = $relayState;

# $et->data['requesterName'] = $spname;

$et->data['head'] = $xajax->getJavascript();

$et->show();

exit(0);




?>