<?php
/**
 * The SSOService is part of the Shibboleth 1.3 IdP code, and it receives incomming Authentication Requests
 * from a Shibboleth 1.3 SP, parses, and process it, and then authenticates the user and sends the user back
 * to the SP with an Authentication Response.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

require_once('../../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XML/AttributeFilter.php');
require_once('SimpleSAML/XML/Shib13/AuthnRequest.php');
require_once('SimpleSAML/XML/Shib13/AuthnResponse.php');
require_once('SimpleSAML/Bindings/Shib13/HTTPPost.php');

require_once('SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);

$logger = new SimpleSAML_Logger();

$idpentityid = $metadata->getMetaDataCurrentEntityID('shib13-idp-hosted');
$idpmeta = $metadata->getMetaDataCurrent('shib13-idp-hosted');

$requestid = null;

$logger->log(LOG_INFO, $session->getTrackID(), 'Shib1.3', 'IdP.SSOService', 'EVENT', 'Access', 'Accessing Shibboleth 1.3 IdP endpoint SSOService');

/*
 * If the shire query parameter is set, we got an incomming Authentication Request 
 * at this interface.
 *
 * In this case, what we should do is to process the request and set the neccessary information
 * from the request into the session object to be used later.
 *
 */
if (isset($_GET['shire'])) {


	try {
		$authnrequest = new SimpleSAML_XML_Shib13_AuthnRequest($config, $metadata);
		$authnrequest->parseGet($_GET);
		
		//$session = $authnrequest->createSession();
	
		$requestid = $authnrequest->getRequestID();

		//$session->setShibAuthnRequest($authnrequest);
		
		

		/*
		 * Create an assoc array of the request to store in the session cache.
		 */
		$requestcache = array(
			'Issuer'    => $authnrequest->getIssuer(),
			'shire'		=> $authnrequest->getShire(),
		);
		if ($relaystate = $authnrequest->getRelayState() )
			$requestcache['RelayState'] = $relaystate;
			
		$session->setAuthnRequest('shib13', $requestid, $requestcache);

	
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error getting incomming request';
		$et->data['message'] = 'Something bad happened when simpleSAML got the incomming authentication request';	
		$et->data['e'] = $exception;
		
		$et->show();
		exit(0);
	}


/*
 * If we did not get an incomming Authenticaiton Request, we need a RequestID parameter.
 *
 * The RequestID parameter is used to retrieve the information stored in the session object
 * related to the request that was received earlier. Usually the request is processed with 
 * code above, then the user is redirected to some login module, and when successfully authenticated
 * the user isredirected back to this endpoint, and then the user will need to have the RequestID 
 * parmeter attached.
 */
} elseif(isset($_GET['RequestID'])) {
	
	try {

		$requestid = $_GET['RequestID'];

		$requestcache = $session->getAuthnRequest('shib13', $requestid);
		
		$logger->log(LOG_INFO, $session->getTrackID(), 'Shib1.3', 'IdP.SSOService', 'EVENT', $requestid, 'Got incomming RequestID');
		
		if (!$requestcache) throw new Exception('Could not retrieve cached RequestID = ' . $requestid);

	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error retrieving authnrequest cache';
		$et->data['message'] = 'simpleSAML cannot find the authnrequest that it earlier stored.';	
		$et->data['e'] = $exception;
		
		$et->show();
	}

} else {
	/*
	 * We did neither get a request or a requestID as a parameter. Then throw an error.
	 */
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');
	
	$et->data['header'] = 'No parameters found';
	$et->data['message'] = 'You must either provide a Shibboleth Request message or a RequestID on this interface.';	
	$et->data['e'] = $exception;
	
	$et->show();
	exit(0);

}



/*
 * As we have passed the code above, we have an accociated request that is already processed.
 *
 * Now we check whether we have a authenticated session. If we do not have an authenticated session,
 * we look up in the metadata of the IdP, to see what authenticaiton module to use, then we redirect
 * the user to the authentication module, to authenticate. Later the user is redirected back to this
 * endpoint - then the session is authenticated and set, and the user is redirected back with a RequestID
 * parameter so we can retrieve the cached information from the request.
 */
if (!$session->isAuthenticated() ) {

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($requestid);
	$authurl = SimpleSAML_Utilities::addURLparameter('/' . $config->getValue('baseurlpath') . $idpmeta['auth'], 
		'RelayState=' . urlencode($relaystate));
	SimpleSAML_Utilities::redirect($authurl);
	
	
/*
 * We got an request, and we hav a valid session. Then we send an AuthenticationResponse back to the
 * service.
 */
} else {

	try {
	
		//$session->add_sp_session($authnrequest->getIssuer());


		//$session->setAttribute('eduPersonAffiliation', array('student'));

		$spentityid = $requestcache['Issuer'];
		$spmetadata = $metadata->getMetaData($spentityid, 'shib13-sp-remote');

		/*
		 * Filtering attributes.
		 */
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $session->getAttributes());
		if (isset($spmetadata['attributemap'])) {
			$afilter->namemap($spmetadata['attributemap']);
		}
		if (isset($spmetadata['attributes'])) {
			$afilter->filter($spmetadata['attributes']);
		}
		$filteredattributes = $afilter->getAttributes();
		



		// Generating a Shibboleth 1.3 Response.
		$ar = new SimpleSAML_XML_Shib13_AuthnResponse($config, $metadata);
		$authnResponseXML = $ar->generate($idpentityid, $requestcache['Issuer'], 
			$requestid, null, $filteredattributes);
		
		
		#echo $authnResponseXML;
		#print_r($authnResponseXML);
		
		//sendResponse($response, $idpentityid, $spentityid, $relayState = null) {
		$httppost = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
		
		//echo 'Relaystate[' . $authnrequest->getRelayState() . ']';
		
		$issuer = $requestcache['Issuer'];
		$shire = $requestcache['shire'];
		if ($issuer == null || $issuer == '')
			throw new Exception('Could not retrieve issuer of the AuthNRequest (ProviderID)');
		
		$httppost->sendResponse($authnResponseXML, 
			$idpentityid, $issuer, isset($requestcache['RelayState']) ? $requestcache['RelayState'] : null, $shire);
			
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error sending response to service';
		$et->data['message'] = 'Some error occured when trying to issue the authentication response, and send it back to the SP.';	
		$et->data['e'] = $exception;
		
		$et->show();

	}
	
}


?>