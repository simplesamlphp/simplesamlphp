<?php


require_once('../../_include.php');

require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Utilities.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Session.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutRequest.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XML/SAML20/LogoutResponse.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
//require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . 'SimpleSAML/XHTML/Template.php');




session_start();

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();




$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');

$session = SimpleSAML_Session::getInstance();




require_once((isset($SIMPLESAML_INCPREFIX)?$SIMPLESAML_INCPREFIX:'') . '../../xajax/xajax.inc.php');





/*
 * This function is called via AJAX and will send LogoutRequest to one single SP by
 * sending a LogoutRequest using HTTP-REDIRECT
 */
function httpredirectslo($spentityid) {


	
	$config = SimpleSAML_Configuration::getInstance();
	$metadata = new SimpleSAML_XML_MetaDataStore($config);
	
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	
	$session = SimpleSAML_Session::getInstance();

	if (!isset($_GET['loggedoutservice'])) {
	
		try {
			$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
		
			// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
			$req = $lr->generate($idpentityid, $spentityid, $session->getNameID(), $session->getNameIDFormat(), $session->getSessionIndex(), 'IdP');
			
			$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
			
			$relayState = SimpleSAML_Utilities::selfURL();
	
			
			//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
			//$httpredirect->sendMessage($req, $spentityid, $relayState, 'SingleLogoutService', 'SAMLRequest', 'IdP');
			
			//header('Location: vg.no');
			header('Location: http://vg.no');
			
			exit();
	
		} catch(Exception $exception) {
			
			$et = new SimpleSAML_XHTML_Template($config, 'error.php');
			
			$et->data['header'] = 'Error sending logout request to service';
			$et->data['message'] = 'Some error occured when trying to issue the logout response, and send it to the SP.';	
			$et->data['e'] = $exception;
			
			$et->show();
			exit();
		}


	}




	$newContent = "Successfully logged out from ".$spentityid;

    // Instantiate the xajaxResponse object
    $objResponse = new xajaxResponse();
    
    // add a command to the response to assign the innerHTML attribute of
    // the element with id="SomeElementId" to whatever the new content is
    $objResponse->addAssign($spentityid,"innerHTML", $newContent);
    
    //return the  xajaxResponse object
    return $objResponse;
}



$xajax = new xajax();
$xajax->registerFunction("httpredirectslo", XAJAX_GET);
$xajax->processRequests();











$session->dump_sp_sessions();





$et = new SimpleSAML_XHTML_Template($config, 'logout-ajax.php');

$et->data['header'] = 'SAML 2.0 IdP Ajax Logout';
$et->data['listofsps'] = $session->get_sp_list();;
$et->data['xajax'] = $xajax;
$et->show();

exit(0);






/*
 * If we get an LogoutRequest then we initiate the logout process.
 */
if (isset($_GET['SAMLRequest'])) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	$logoutrequest = $binding->decodeLogoutRequest($_GET);
	
	$session->setAuthenticated(false);

	//$requestid = $authnrequest->getRequestID();
	//$session->setAuthnRequest($requestid, $authnrequest);
	
	//echo '<pre>' . htmlentities($logoutrequest->getXML()) . '</pre>';
	
	error_log('IdP LogoutService: got Logoutrequest from ' . $logoutrequest->getIssuer() . '  ');
	
	$session->set_sp_logout_completed($logoutrequest->getIssuer() );
	$session->setLogoutRequest($logoutrequest);

/*
 * We receive a Logout Response to a Logout Request that we have issued earlier.
 */
} elseif (isset($_GET['SAMLResponse'])) {

	$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	$loginresponse = $binding->decodeLogoutResponse($_GET);
	
	$session->set_sp_logout_completed($loginresponse->getIssuer());
	
	error_log('IdP LogoutService: got LogoutResponse from ' . $loginresponse->getIssuer() . '  ');
}

/*
 * We proceed to send logout requests to all remaining SPs.
 */
$spentityid = $session->get_next_sp_logout();
if ($spentityid) {

	error_log('IdP LogoutService: next SP ' . $spentityid);

	try {
		$lr = new SimpleSAML_XML_SAML20_LogoutRequest($config, $metadata);
	
		// ($issuer, $receiver, $nameid, $nameidformat, $sessionindex, $mode) {
		$req = $lr->generate($idpentityid, $spentityid, $session->getNameID(), $session->getNameIDFormat(), $session->getSessionIndex(), 'IdP');
		
		$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		
		$relayState = SimpleSAML_Utilities::selfURL();
		if (isset($_GET['RelayState'])) {
			$relayState = $_GET['RelayState'];
		}
		
		//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
		$httpredirect->sendMessage($req, $spentityid, $relayState, 'SingleLogoutService', 'SAMLRequest', 'IdP');
		
		exit();

	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error sending logout request to service';
		$et->data['message'] = 'Some error occured when trying to issue the logout response, and send it to the SP.';	
		$et->data['e'] = $exception;
		
		$et->show();
		exit(0);
	}


}

/*
 * Logout procedure is done and we send a Logout Response back to the SP
 */
error_log('IdP LogoutService:  SPs done ');
try {

	$logoutrequest = $session->getLogoutRequest();
	if (!$logoutrequest) {
		throw new Exception('Could not get reference to the logout request.');
	}

	$rg = new SimpleSAML_XML_SAML20_LogoutResponse($config, $metadata);
	
	// generate($issuer, $receiver, $inresponseto, $mode )
	
	$logoutResponseXML = $rg->generate($idpentityid, $logoutrequest->getIssuer(), $logoutrequest->getRequestID(), 'IdP');
	
	//	echo '<pre>' . htmlentities($logoutResponseXML) . '</pre>';
	//	exit();
	
	$httpredirect = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
	
	$relayState = SimpleSAML_Utilities::selfURL();
	if (isset($_GET['RelayState'])) {
		$relayState = $_GET['RelayState'];
	}
	
	//$request, $remoteentityid, $relayState = null, $endpoint = 'SingleLogoutService', $direction = 'SAMLRequest', $mode = 'SP'
	$httpredirect->sendMessage($logoutResponseXML, $logoutrequest->getIssuer(), $relayState, 'SingleLogoutService', 'SAMLResponse', 'IdP');

} catch(Exception $exception) {
	
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');
	
	$et->data['header'] = 'Error sending response to service';
	$et->data['message'] = 'Some error occured when trying to issue the logout response, and send it to the SP.';	
	$et->data['e'] = $exception;
	
	$et->show();

}



?>