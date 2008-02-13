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

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idpmetadata = $metadata->getMetaDataCurrent('saml20-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

$requestid = null;

Logger::info('SAML2.0 - IdP.SSOService: Accessing SAML 2.0 IdP endpoint SSOService');

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
			Logger::info('SAML2.0 - IdP.SSOService: Valid signature found for '.$requestid);
		}
		
		Logger::info('SAML2.0 - IdP.SSOService: Incomming Authentication request: '.$authnrequest->getIssuer().' id '.$requestid);
	
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSAUTHNREQUEST', $exception);
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
		
		Logger::info('SAML2.0 - IdP.SSOService: Got incomming RequestID');
		
		if (!$requestcache) throw new Exception('Could not retrieve cached RequestID = ' . $requestid);
		
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CACHEAUTHNREQUEST', $exception);
	}
	

} else {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SSOSERVICEPARAMS');
}


$authority = isset($idpmetadata['authority']) ? $idpmetadata['authority'] : null;


/*
 * As we have passed the code above, we have an accociated request that is already processed.
 *
 * Now we check whether we have a authenticated session. If we do not have an authenticated session,
 * we look up in the metadata of the IdP, to see what authenticaiton module to use, then we redirect
 * the user to the authentication module, to authenticate. Later the user is redirected back to this
 * endpoint - then the session is authenticated and set, and the user is redirected back with a RequestID
 * parameter so we can retrieve the cached information from the request.
 */
if (!isset($session) || !$session->isValid($authority) ) {


	Logger::notice('SAML2.0 - IdP.SSOService: Will go to authentication module ' . $idpmetadata['auth']);

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() .
		'?RequestID=' . urlencode($requestid);
	$authurl = '/' . $config->getValue('baseurlpath') . $idpmetadata['auth'];

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
	
		if (array_key_exists('requireconsent', $idpmetadata)
		    && $idpmetadata['requireconsent']) {
			
			if (!isset($_GET['consent'])) {

				Logger::notice('SAML2.0 - IdP.SSOService: Requires consent from user for attribute release');

				$t = new SimpleSAML_XHTML_Template($config, 'consent.php');
				$t->data['header'] = 'Consent';
				$t->data['spentityid'] = $spentityid;
				$t->data['attributes'] = $session->getAttributes();
				$t->data['consenturl'] = SimpleSAML_Utilities::addURLparameter(SimpleSAML_Utilities::selfURL(), 'consent=1');
				$t->show();
				exit(0);
				
			} else {
			
				Logger::notice('SAML2.0 - IdP.SSOService: Got consent from user');
			}
			
		}
	
		// Adding this service provider to the list of sessions.
		// Right now the list is used for SAML 2.0 only.
		$session->add_sp_session($spentityid);

		Logger::notice('SAML2.0 - IdP.SSOService: Sending back AuthnResponse to '.$spentityid);

		/*
		 * Filtering attributes.
		 */
		$ar = new SimpleSAML_XML_SAML20_AuthnResponse($config, $metadata);
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $session->getAttributes());
		
		if (isset($spmetadata['attributemap'])) {
			$afilter->namemap($spmetadata['attributemap']);
		}
		if (isset($idpmetadata['attributealter'])) {
			if (!is_array($idpmetadata['attributealter']))
				$afilter->alter($idpmetadata['attributealter']);
			else
				foreach($idpmetadata['attributealter'] AS $alterfunc) 
					$afilter->alter($alterfunc);
		}
		if (isset($spmetadata['attributealter'])) {
			if (!is_array($spmetadata['attributealter']))
				$afilter->alter($spmetadata['attributealter']);
			else
				foreach($spmetadata['attributealter'] AS $alterfunc) 
					$afilter->alter($alterfunc);
		}
		if (isset($spmetadata['attributes'])) {
			$afilter->filter($spmetadata['attributes']);
		}
		$filteredattributes = $afilter->getAttributes();
		
		//echo '<pre>before filter:' ; print_r($session->getAttributes()); echo "\n\n"; print_r($filteredattributes); echo '</pre>'; exit;
		
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
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
	}
	
}


?>
