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


$idpentityid = $metadata->getMetaDataCurrentEntityID('shib13-idp-hosted');
$idpmetadata = $metadata->getMetaDataCurrent('shib13-idp-hosted');

$requestid = null;

SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Accessing Shibboleth 1.3 IdP endpoint SSOService');

if (!$config->getValue('enable.shib13-idp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');



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
		
		$requestid = $authnrequest->getRequestID();

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
		
		SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Got incomming Shib authnRequest requestid: '.$requestid);
	
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

		$requestcache = $session->getAuthnRequest('shib13', $requestid);
		
		SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Got incomming RequestID: '.$requestid);
		
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
if (!$session->isAuthenticated($authority) ) {

	$relaystate = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($requestid);
	$authurl = SimpleSAML_Utilities::addURLparameter('/' . $config->getValue('baseurlpath') . $idpmetadata['auth'], 
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
		 * Attribute handling
		 */
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $session->getAttributes());
		if (isset($idpmetadata['attributemap'])) {
			SimpleSAML_Logger::debug('Applying IdP specific attributemap: ' . $idpmetadata['attributemap']);
			$afilter->namemap($idpmetadata['attributemap']);
		}
		if (isset($spmetadata['attributemap'])) {
			SimpleSAML_Logger::debug('Applying SP specific attributemap: ' . $spmetadata['attributemap']);
			$afilter->namemap($spmetadata['attributemap']);
		}
		if (isset($idpmetadata['attributealter'])) {
			if (!is_array($idpmetadata['attributealter'])) {
				SimpleSAML_Logger::debug('Applying IdP specific attribute alter: ' . $idpmetadata['attributealter']);
				$afilter->alter($idpmetadata['attributealter']);
			} else {
				foreach($idpmetadata['attributealter'] AS $alterfunc) {
					SimpleSAML_Logger::debug('Applying IdP specific attribute alter: ' . $alterfunc);
					$afilter->alter($alterfunc);
				}
			}
		}
		if (isset($spmetadata['attributealter'])) {
			if (!is_array($spmetadata['attributealter'])) {
				SimpleSAML_Logger::debug('Applying SP specific attribute alter: ' . $spmetadata['attributealter']);
				$afilter->alter($spmetadata['attributealter']);
			} else {
				foreach($spmetadata['attributealter'] AS $alterfunc) {
					SimpleSAML_Logger::debug('Applying SP specific attribute alter: ' . $alterfunc);
					$afilter->alter($alterfunc);
				}
			}
		}

		/**
		 * Make a log entry in the statistics for this SSO login.
		 */
		$tempattr = $afilter->getAttributes();
		$realmattr = $config->getValue('statistics.realmattr', null);
		$realmstr = 'NA';
		if (!empty($realmattr)) {
			if (array_key_exists($realmattr, $tempattr) && is_array($tempattr[$realmattr]) ) {
				$realmstr = $tempattr[$realmattr][0];
			} else {
				SimpleSAML_Logger::warning('Could not get realm attribute to log [' . $realmattr. ']');
			}
		} 
		SimpleSAML_Logger::stats('shib13-idp-SSO ' . $spentityid . ' ' . $idpentityid . ' ' . $realmstr);
		
		/**
		 * Filter away attributes that are not allowed for this SP.
		 */
		if (isset($spmetadata['attributes'])) {
			SimpleSAML_Logger::debug('Applying SP specific attribute filter: ' . join(',', $spmetadata['attributes']));
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
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
	}
	
}


?>