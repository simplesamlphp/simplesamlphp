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

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();


SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Accessing Shibboleth 1.3 IdP endpoint SSOService');

if (!$config->getValue('enable.shib13-idp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('shib13-idp-hosted', 'entityid');
	$idpmetaindex = $metadata->getMetaDataCurrentEntityID('shib13-idp-hosted', 'metaindex');
	$idpmetadata = $metadata->getMetaDataCurrent('shib13-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

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
			'RequestID' => $requestid,
			'Issuer'    => $authnrequest->getIssuer(),
			'shire'		=> $authnrequest->getShire(),
			'RelayState' => $authnrequest->getRelayState(),
			'ConsentCookie' => SimpleSAML_Utilities::generateID(),
		);
			
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

		$authId = $_GET['RequestID'];

		$requestcache = $session->getAuthnRequest('shib13', $authId);
		
		SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Got incomming RequestID: '. $authId);
		
		if (!$requestcache) {
			throw new Exception('Could not retrieve cached RequestID = ' . $authId);
		}

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'CACHEAUTHNREQUEST', $exception);
	}
	
} elseif(isset($_REQUEST[SimpleSAML_Auth_ProcessingChain::AUTHPARAM])) {

	/* Resume from authentication processing chain. */
	$authProcId = $_REQUEST[SimpleSAML_Auth_ProcessingChain::AUTHPARAM];
	$authProcState = SimpleSAML_Auth_ProcessingChain::fetchProcessedState($authProcId);
	$requestcache = $authProcState['core:shib13-idp:requestcache'];

} else {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'SSOSERVICEPARAMS');
}

/* Check whether we should authenticate with an AuthSource. Any time the auth-option matches a
 * valid AuthSource, we assume that this is the case.
 */
if(SimpleSAML_Auth_Source::getById($idpmetadata['auth']) !== NULL) {
	/* Authenticate with an AuthSource. */
	$authSource = TRUE;
	$authority = $idpmetadata['auth'];
} else {
	$authSource = FALSE;
	$authority = isset($idpmetadata['authority']) ? $idpmetadata['authority'] : NULL;
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
if (!$session->isAuthenticated($authority) ) {

	$authId = SimpleSAML_Utilities::generateID();
	$session->setAuthnRequest('shib13', $authId, $requestcache);

	$redirectTo = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($authId);

	if($authSource) {
		/* Authenticate with an AuthSource. */
		$hints = array(
			'SPMetadata' => $metadata->getMetaData($requestcache['Issuer'], 'shib13-sp-remote'),
			'IdPMetadata' => $idpmetadata,
		);

		SimpleSAML_Auth_Default::initLogin($idpmetadata['auth'], $redirectTo, NULL, $hints);
	} else {
		$authurl = '/' . $config->getBaseURL() . $idpmetadata['auth'];

		SimpleSAML_Utilities::redirect($authurl, array(
			'RelayState' => $redirectTo,
			'AuthId' => $authId,
			'protocol' => 'shib13',
		));
	}
	
/*
 * We got an request, and we hav a valid session. Then we send an AuthenticationResponse back to the
 * service.
 */
} else {

	try {
	
		$spentityid = $requestcache['Issuer'];
		$spmetadata = $metadata->getMetaData($spentityid, 'shib13-sp-remote');

		$sp_name = (isset($spmetadata['name']) ? $spmetadata['name'] : $spentityid);
		
		/*
		 * Attribute handling
		 */
		$attributes = $session->getAttributes();
		$afilter = new SimpleSAML_XML_AttributeFilter($config, $attributes);
		$afilter->process($idpmetadata, $spmetadata);

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
		$afilter->processFilter($idpmetadata, $spmetadata);
		
		$filteredattributes = $afilter->getAttributes();
		

		/* Authentication processing operations. */
		if (array_key_exists('AuthProcState', $requestcache)) {
			/* Processed earlier, saved in requestcache. */
			$authProcState = $requestcache['AuthProcState'];

		} elseif (isset($authProcState)) {
			/* Returned from redirect during processing. */
			$requestcache['AuthProcState'] = $authProcState;

		} else {
			/* Not processed. */
			$pc = new SimpleSAML_Auth_ProcessingChain($idpmetadata, $spmetadata, 'idp');

			$authProcState = array(
				'core:shib13-idp:requestcache' => $requestcache,
				'ReturnURL' => SimpleSAML_Utilities::selfURLNoQuery(),
				'Attributes' => $filteredattributes,
				'Destination' => $spmetadata,
				'Source' => $idpmetadata,
				);

			$pc->processState($authProcState);

			$requestcache['AuthProcState'] = $authProcState;
		}

		$filteredattributes = $authProcState['Attributes'];

		
		/*
		 * Dealing with attribute release consent.
		 */
		$requireconsent = false;
		if (isset($idpmetadata['requireconsent'])) {
			if (is_bool($idpmetadata['requireconsent'])) {
				$requireconsent = $idpmetadata['requireconsent'];
			} else {
				throw new Exception('Shib1.3 IdP hosted metadata parameter [requireconsent] is in illegal format, must be a PHP boolean type.');
			}
		}
		if ($requireconsent) {
			
			$consent = new SimpleSAML_Consent_Consent($config, $session, $spentityid, $idpentityid, $attributes, $filteredattributes, $requestcache['ConsentCookie']);
			
			if (!$consent->consent()) {
				/* Save the request information. */
				$authId = SimpleSAML_Utilities::generateID();
				$session->setAuthnRequest('shib13', $authId, $requestcache);
				
				$t = new SimpleSAML_XHTML_Template($config, 'consent.php', 'attributes');
				$t->data['header'] = 'Consent';
				$t->data['sp_name'] = $sp_name;
				$t->data['sp_description'] = (isset($spmetadata['description']) ? $spmetadata['description'] : "SP DESCRIPTION");
				$t->data['idp_name'] = (isset($idpmetadata['name']) ? $idpmetadata['name'] : $idpentityid);
				$t->data['spentityid'] = $spentityid;
				$t->data['spmetadata'] = $spmetadata;
				$t->data['attributes'] = $filteredattributes;
				$t->data['consenturl'] = SimpleSAML_Utilities::selfURLNoQuery();
				$t->data['requestid'] = $authId;
				$t->data['consent_cookie'] = $requestcache['ConsentCookie'];
				$t->data['usestorage'] = $consent->useStorage();
				$t->data['noconsent'] = '/' . $config->getBaseURL() . 'noconsent.php';
				$t->data['noconsent_data'] = array(
					'sptype' => 'shib13-sp-remote',
					'spentityid' => $spentityid,
					'resumeFrom' => SimpleSAML_Utilities::selfURL(),
					);

				if (array_key_exists('privacypolicy', $spmetadata)) {
					$privacypolicy = $spmetadata['privacypolicy'];
				} elseif (array_key_exists('privacypolicy', $idpmetadata)) {
					$privacypolicy = $idpmetadata['privacypolicy'];
				} else {
					$privacypolicy = FALSE;
				}
				if($privacypolicy !== FALSE) {
					$privacypolicy = str_replace('%SPENTITYID%', urlencode($spentityid),
						$privacypolicy);
				}
				$t->data['sppp'] = $privacypolicy;

				switch($config->getValueValidate('consent_autofocus', array(NULL, 'yes', 'no'), NULL)) {
				case NULL:
					break;
				case 'yes':
					$t->data['autofocus'] = 'yesbutton';
					break;
				case 'no':
					$t->data['autofocus'] = 'nobutton';
					break;
				}

				$t->show();
				exit;
			}

		}
		// END ATTRIBUTE CONSENT CODE
		
		
		// Generating a Shibboleth 1.3 Response.
		$ar = new SimpleSAML_XML_Shib13_AuthnResponse($config, $metadata);
		$authnResponseXML = $ar->generate($idpentityid, $requestcache['Issuer'],
			$requestcache['RequestID'], null, $filteredattributes);
		
		
		#echo $authnResponseXML;
		#print_r($authnResponseXML);
		
		//sendResponse($response, $idpentityid, $spentityid, $relayState = null) {
		$httppost = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
		
		//echo 'Relaystate[' . $authnrequest->getRelayState() . ']';
		
		$issuer = $requestcache['Issuer'];
		$shire = $requestcache['shire'];
		if ($issuer == null || $issuer == '')
			throw new Exception('Could not retrieve issuer of the AuthNRequest (ProviderID)');
		
		$httppost->sendResponse($authnResponseXML, $idpmetaindex, $issuer, $requestcache['RelayState'], $shire);
			
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
	}
	
}


?>