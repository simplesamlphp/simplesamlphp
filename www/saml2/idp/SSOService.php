<?php
/**
 * The SSOService is part of the SAML 2.0 IdP code, and it receives incoming Authentication Requests
 * from a SAML 2.0 SP, parses, and process it, and then authenticates the user and sends the user back
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

try {
	$idpentityid = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted');
	$idmetaindex = $metadata->getMetaDataCurrentEntityID('saml20-idp-hosted', 'metaindex');
	$idpmetadata = $metadata->getMetaDataCurrent('saml20-idp-hosted');
	
	if (!array_key_exists('auth', $idpmetadata)) {
		throw new Exception('Missing mandatory parameter in SAML 2.0 IdP Hosted Metadata: [auth]');
	}
	
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Accessing SAML 2.0 IdP endpoint SSOService');

if (!$config->getValue('enable.saml20-idp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');


/**
 * Helper function for handling exception/errors.
 *
 * This function will send an error response to the SP which contacted this IdP.
 *
 * @param Exception $exception  The exception.
 */
function handleError(Exception $exception) {

	global $requestcache, $config, $metadata, $idpentityid;
	assert('is_array($requestcache)');

	assert('array_key_exists("Issuer", $requestcache)');
	$issuer = $requestcache['Issuer'];

	if (array_key_exists('RequestID', $requestcache)) {
		$requestID = $requestcache['RequestID'];
	} else {
		$requestID = NULL;
	}

	if (array_key_exists('RelayState', $requestcache)) {
		$relayState = $requestcache['RelayState'];
	} else {
		$relayState = NULL;
	}

	$error = sspmod_saml2_Error::fromException($exception);

	SimpleSAML_Logger::warning('Returning error to sp: ' . var_export($issuer, TRUE));
	$error->logWarning();

	try {
		$idpMetadata = $metadata->getMetaDataConfig($idpentityid, 'saml20-idp-hosted');
		$spMetadata = $metadata->getMetaDataConfig($issuer, 'saml20-sp-remote');

		$ar = sspmod_saml2_Message::buildResponse($idpMetadata, $spMetadata);
		$ar->setInResponseTo($requestID);
		$ar->setRelayState($relayState);

		$ar->setStatus(array(
			'Code' => $error->getStatus(),
			'SubCode' => $error->getSubStatus(),
			'Message' => $error->getStatusMessage(),
			));

		$binding = new SAML2_HTTPPost();
		$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$binding->send($ar);

	} catch(Exception $e) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $e);
	}

}


/*
 * Initiate some variables
 */
$isPassive = FALSE;

/*
 * If the SAMLRequest query parameter is set, we got an incoming Authentication Request 
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

		$requestid = $authnrequest->getRequestID();
		$issuer = $authnrequest->getIssuer();
		
		/*
		 * Create an assoc array of the request to store in the session cache.
		 */
		$requestcache = array(
			'RequestID' => $requestid,
			'Issuer' => $issuer,
			'RelayState' => $authnrequest->getRelayState()
		);
			

		/*
		 * Handle the ForceAuthn option.
		 */

		/* The default value is FALSE. */
		$forceAuthn = FALSE;

		$spentityid = $requestcache['Issuer'];
		$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		if(array_key_exists('ForceAuthn', $spmetadata)) {
			/* The ForceAuthn flag is set in the metadata for this SP. */
			$forceAuthn = $spmetadata['ForceAuthn'];
			if(!is_bool($spmetadata['ForceAuthn'])) {
				throw new Exception('The ForceAuthn option in the metadata for the sp [' . $spentityid . '] is not a boolean.');
			}

			if($spmetadata['ForceAuthn']) {
				/* ForceAuthn enabled in the metadata for the SP. */
				$forceAuthn = TRUE;
			}
		}

		if($authnrequest->getForceAuthn()) {
			/* The ForceAuthn flag was set to true in the authentication request. */
			$forceAuthn = TRUE;
		}

		$isPassive = $authnrequest->getIsPassive();
		/* 
		 * The ForceAuthn flag was set to true in the authentication request
		 * and IsPassive was not - IsPassive overrides ForceAuthn thus the check
		 *
		 */

		if($forceAuthn && !$isPassive) {
			/* ForceAuthn is enabled. Mark the request as needing authentication. This flag
			 * will be cleared by a call to setAuthenticated(TRUE, ...) to the current session.
			 *
			 */
			$requestcache['NeedAuthentication'] = TRUE;
		}

		if ($binding->validateQuery($issuer, 'IdP')) {
			SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Valid signature found for ' . $requestid);
		}
		
		SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Incomming Authentication request: '.$issuer.' id '.$requestid);
	
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSAUTHNREQUEST', $exception);
	}

} elseif(isset($_REQUEST[SimpleSAML_Auth_State::EXCEPTION_PARAM])) {
	/*
	 * We have received an exception. It can either be from the authentication module,
	 * or from the authentication processing filters.
	 */

	$state = SimpleSAML_Auth_State::loadExceptionState();
	if (array_key_exists('core:saml20-idp:requestcache', $state)) {
		/* This was from a processing chain. */
		$requestcache = $state['core:saml20-idp:requestcache'];

	} elseif (array_key_exists('RequestID', $_REQUEST)) {
		/* This was from an authentication module. */
		$authId = $_REQUEST['RequestId'];
		$requestcache = $session->getAuthnRequest('saml2', $authId);
		if (!$requestcache) {
			throw new Exception('Could not retrieve saved request while handling exceptions. RequestId=' . var_export($authId, TRUE));
		}

	} else {
		/* We have no idea where this comes from. We have received a bad request. */
		throw new Exception('Bad request to exception handing code.');
	}

	assert('array_key_exists(SimpleSAML_Auth_State::EXCEPTION_DATA, $state)');
	$exception = $state[SimpleSAML_Auth_State::EXCEPTION_DATA];

	handleError($exception);


/*
 * If we did not get an incoming Authenticaiton Request, we need a RequestID parameter.
 *
 * The RequestID parameter is used to retrieve the information stored in the session object
 * related to the request that was received earlier. Usually the request is processed with 
 * code above, then the user is redirected to some login module, and when successfully authenticated
 * the user isredirected back to this endpoint, and then the user will need to have the RequestID 
 * parmeter attached.
 */
} elseif(isset($_GET['RequestID'])) {

	try {
	
		SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Got incoming authentication ID');
		
		$authId = $_GET['RequestID'];
		$requestcache = $session->getAuthnRequest('saml2', $authId);
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
	$requestcache = $authProcState['core:saml20-idp:requestcache'];

/**
 * If the spentityid parameter is provided, we will fallback to a unsolited response to the SP.
 */
} elseif(array_key_exists('spentityid', $_GET)) {
	
	/* Creating a request cache, even though there was no request, and adding the
	 * information that is neccessary to be able to respond with an unsolited response
	 */
	$requestcache = array(
		'Issuer' => $_GET['spentityid'],
	);


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


/**
 * As we have passed the code above, we have an associated request that is already processed.
 *
 * Now we check whether we have a authenticated session. If we do not have an authenticated session,
 * we look up in the metadata of the IdP, to see what authenticaiton module to use, then we redirect
 * the user to the authentication module, to authenticate. Later the user is redirected back to this
 * endpoint - then the session is authenticated and set, and the user is redirected back with a RequestID
 * parameter so we can retrieve the cached information from the request.
 */
if (!isset($session) || !$session->isValid($authority) ) {
	/* We don't have a valid session. */
	$needAuth = TRUE;
} elseif (array_key_exists('NeedAuthentication', $requestcache) && $requestcache['NeedAuthentication']) {
	/* We have a valid session, but ForceAuthn is on. */
	$needAuth = TRUE;
} else {
	/* We have a valid session. */
	$needAuth = FALSE;
}

if($needAuth && !$isPassive) {

	SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Will go to authentication module ' . $idpmetadata['auth']);

	$authId = SimpleSAML_Utilities::generateID();
	$session->setAuthnRequest('saml2', $authId, $requestcache);

	$redirectTo = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($authId);

	if($authSource) {
		/* Authenticate with an AuthSource. */

		/* The user will be redirected to this URL if the session is lost. This will cause an
		 * unsoliced authentication response to be sent to the SP.
		 */
		$sessionLostURL = SimpleSAML_Utilities::addURLparameter(
			$metadata->getGenerated('SingleSignOnService', 'saml20-idp-hosted'),
			array(
				'spentityid' => $requestcache['Issuer'],
			));

		$hints = array(
			'SPMetadata' => $metadata->getMetaData($requestcache['Issuer'], 'saml20-sp-remote'),
			'IdPMetadata' => $idpmetadata,
			SimpleSAML_Auth_State::RESTART => $sessionLostURL,
		);

		SimpleSAML_Auth_Default::initLogin($idpmetadata['auth'], $redirectTo, $redirectTo, $hints);
	} else {
		$authurl = '/' . $config->getBaseURL() . $idpmetadata['auth'];

		SimpleSAML_Utilities::redirect($authurl, array(
		       'RelayState' => $redirectTo,
		       'AuthId' => $authId,
		       'protocol' => 'saml2',
		));
	}

} elseif($needAuth) {
	/* We have a passive request, but need authentication. Send back a response indicating that
	 * the user didn't have a valid session.
	 */

	handleError(new SimpleSAML_Error_NoPassive('Passive authentication requested, but no session available.'));

/**
 * We got an request, and we have a valid session. Then we send an AuthnResponse back to the
 * service.
 */
} else {

	try {
	
		$spentityid = $requestcache['Issuer'];
		$spmetadata = $metadata->getMetaData($spentityid, 'saml20-sp-remote');
		
		$sp_name = (isset($spmetadata['name']) ? $spmetadata['name'] : $spentityid);

		SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Sending back AuthnResponse to ' . $spentityid);
		
		/*
		 * Attribute handling
		 */
		$attributes = $session->getAttributes();
		
		/* Authentication processing operations. */
		if (!isset($authProcState)) {
			/* Not processed. */
			$pc = new SimpleSAML_Auth_ProcessingChain($idpmetadata, $spmetadata, 'idp');

			$authProcState = array(
				'core:saml20-idp:requestcache' => $requestcache,
				'ReturnURL' => SimpleSAML_Utilities::selfURLNoQuery(),
				'Attributes' => $attributes,
				'Destination' => $spmetadata,
				'Source' => $idpmetadata,
				'isPassive' => $isPassive,
				SimpleSAML_Auth_State::EXCEPTION_HANDLER_URL => SimpleSAML_Utilities::selfURLNoQuery(),
			);

			/*
			 * Check whether the user has been authenticated to this SP previously
			 * during this session. If the SP is authenticated earlier, we include
			 * the timestamp to the authentication processing filters.
			 */
			$previousSSOTime = $session->getData('saml2-idp-ssotime', $spentityid);
			if ($previousSSOTime !== NULL) {
				$authProcState['PreviousSSOTimestamp'] = $previousSSOTime;
			}

			try {
				$pc->processState($authProcState);
			} catch (Exception $e) {
				handleError($e);
			}

			$requestcache['AuthProcState'] = $authProcState;
		}

		$attributes = $authProcState['Attributes'];

		
		

		/*
		 * Save the time we authenticated to this SP. This can be used later to detect an
		 * SP which reauthenticates a user very often.
		 */
		$session->setData('saml2-idp-ssotime', $spentityid, time(),
			SimpleSAML_Session::DATA_TIMEOUT_LOGOUT);

		// Adding this service provider to the list of sessions.
		// Right now the list is used for SAML 2.0 only.
		$session->add_sp_session($spentityid);
		
		$requestID = NULL; $relayState = NULL;
		if (array_key_exists('RequestID', $requestcache)) $requestID = $requestcache['RequestID'];
		if (array_key_exists('RelayState', $requestcache)) $relayState = $requestcache['RelayState'];
		
		// Generate an SAML 2.0 AuthNResponse message
		$ar = new SimpleSAML_XML_SAML20_AuthnResponse($config, $metadata);
		$authnResponseXML = $ar->generate($idpentityid, $spentityid, $requestID, NULL, $attributes, 'Success', $config->getValue('session.duration', 3600));
	
		// Sending the AuthNResponse using HTTP-Post SAML 2.0 binding
		$httppost = new SimpleSAML_Bindings_SAML20_HTTPPost($config, $metadata);
		$httppost->sendResponse($authnResponseXML, $idmetaindex, $spentityid, $relayState);
		
	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
	}
	
}


?>