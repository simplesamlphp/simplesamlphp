<?php
/**
 * The SSOService is part of the SAML 2.0 IdP code, and it receives incomming Authentication Requests
 * from a SAML 2.0 SP, parses, and process it, and then authenticates the user and sends the user back
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
require_once('SimpleSAML/XML/SAML20/AuthnRequest.php');
require_once('SimpleSAML/XML/SAML20/AuthnResponse.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPRedirect.php');
require_once('SimpleSAML/Bindings/SAML20/HTTPPost.php');
require_once('SimpleSAML/XHTML/Template.php');


$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance(true);

$logger = new SimpleSAML_Logger();

$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
$idpmeta = $metadata->getMetaDataCurrent('saml20-idp-hosted');

$requestid = null;

$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'EVENT', 'Access', 'Accessing SAML 2.0 IdP endpoint SSOService');

/*
 * If the SAMLRequest query parameter is set, we got an incomming Authentication Request 
 * at this interface.
 *
 * In this case, what we should do is to process the request and set the neccessary information
 * from the request into the session object to be used later.
 *
 */
if (isset($_GET['SAMLRequest'])) {


	try {
		$binding = new SimpleSAML_Bindings_SAML20_HTTPRedirect($config, $metadata);
		$authnrequest = $binding->decodeRequest($_GET);
		
		//$session = $authnrequest->createSession();
		$requestid = $authnrequest->getRequestID();
		
		/*
		 * Create an assoc array of the request to store in the session cache.
		 */
		$requestcache = array(
			'Issuer'    => $authnrequest->getIssuer()
		);
		if ($relaystate = $authnrequest->getRelayState() )
			$requestcache['RelayState'] = $relaystate;
			
		$session->setAuthnRequest('saml2', $requestid, $requestcache);
		
		
		if ($binding->validateQuery($authnrequest->getIssuer(),'IdP')) {
			$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthnRequest', $requestid, 'Valid signature found');
		}
		
		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthnRequest', 
			array($authnrequest->getIssuer(), $requestid), 
			'Incomming Authentication request');
	
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

		$requestcache = $session->getAuthnRequest('saml2', $requestid);
		
		$logger->log(LOG_INFO, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'EVENT', $requestid, 'Got incomming RequestID');
		
		if (!$requestcache) throw new Exception('Could not retrieve cached RequestID = ' . $requestid);
		
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error retrieving authnrequest cache';
		$et->data['message'] = 'simpleSAML cannot find the authnrequest that it earlier stored.';	
		$et->data['e'] = $exception;
		
		$et->show();
		exit(0);

	}
	

} else {
	/*
	 * We did neither get a request or a requestID as a parameter. Then throw an error.
	 */
	$et = new SimpleSAML_XHTML_Template($config, 'error.php');
	
	$et->data['header'] = 'No parameters found';
	$et->data['message'] = 'You must either provide a SAML Request message or a RequestID on this interface.';	
	$et->data['e'] = $exception;
	
	$et->show();
	exit(0);
}


$authority = isset($idpmeta['authority']) ? $idpmeta['authority'] : null;


/*
 * As we have passed the code above, we have an accociated request that is already processed.
 *
 * Now we check whether we have a authenticated session. If we do not have an authenticated session,
 * we look up in the metadata of the IdP, to see what authenticaiton module to use, then we redirect
 * the user to the authentication module, to authenticate. Later the user is redirected back to this
 * endpoint - then the session is authenticated and set, and the user is redirected back with a RequestID
 * parameter so we can retrieve the cached information from the request.
 */
if (!isset($session) || !$session->isValid($authority) ) {

	$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthNext', $idpmeta['auth'], 
		'Will go to authentication module ' . $idpmeta['auth']);

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() .
		'?RequestID=' . urlencode($requestid);
	$authurl = '/' . $config->getValue('baseurlpath') . $idpmeta['auth'];

	SimpleSAML_Utilities::redirect($authurl,
		array('RelayState' => $relaystate));
		
/*
 * We got an request, and we hav a valid session. Then we send an AuthenticationResponse back to the
 * service.
 */
} else {

	try {
	
		$spentityid = $requestcache['Issuer'];
		$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		/*
		 * Dealing with attribute release consent.
		 */
	
		if (array_key_exists('requireconsent', $idpmeta)
		    && $idpmeta['requireconsent']) {
			
			if (!isset($_GET['consent'])) {

				$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'Consent', 'request', 
					'Requires consent from user for attribute release');

				$t = new SimpleSAML_XHTML_Template($config, 'consent.php');
				$t->data['header'] = 'Consent';
				$t->data['spentityid'] = $spentityid;
				$t->data['attributes'] = $session->getAttributes();
				$t->data['consenturl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 'consent=1');
				$t->show();
				exit(0);
				
			} else {
			
				$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'ConsentOK', '-', 
					'Got consent from user');
			}
			
		}
	
		// Adding this service provider to the list of sessions.
		// Right now the list is used for SAML 2.0 only.
		$session->add_sp_session($spentityid);

		$logger->log(LOG_NOTICE, $session->getTrackID(), 'SAML2.0', 'IdP.SSOService', 'AuthnResponse', $spentityid, 
			'Sending back AuthnResponse');

		/*
		 * Filtering attributes.
		 */
		$ar = new SimpleSAML_XML_SAML20_AuthnResponse($config, $metadata);
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $session->getAttributes());
		if (isset($spmetadata['attributemap'])) {
			$afilter->namemap($spmetadata['attributemap']);
		}
		if (isset($spmetadata['attributes'])) {
			$afilter->filter($spmetadata['attributes']);
		}
		$filteredattributes = $afilter->getAttributes();
		
		// Generate an SAML 2.0 AuthNResponse message
		$authnResponseXML = $ar->generate($idpentityid, $spentityid, 
			$requestid, null, $filteredattributes);
	
		// Sending the AuthNResponse using HTTP-Post SAML 2.0 binding
		$httppost = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
		$httppost->sendResponse($authnResponseXML, 
			$idpentityid, $spentityid, 
			isset($requestcache['RelayState']) ? $requestcache['RelayState'] : null
		);
		
	} catch(Exception $exception) {
		
		$et = new SimpleSAML_XHTML_Template($config, 'error.php');
		
		$et->data['header'] = 'Error sending response to service';
		$et->data['message'] = 'Some error occured when trying to issue the authentication response, and send it back to the SP.';	
		$et->data['e'] = $exception;
		
		$et->show();
	}
	
}


?>
