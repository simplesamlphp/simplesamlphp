<?php
/**
 * The SSOService is part of the Shibboleth 1.3 IdP code, and it receives incoming Authentication Requests
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

if (!$config->getBoolean('enable.shib13-idp', false))
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOACCESS');

try {
	$idpmetadata = $metadata->getMetaDataCurrent('shib13-idp-hosted');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'METADATA', $exception);
}

/*
 * If the shire query parameter is set, we got an incoming Authentication Request
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
		);

		SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Got incoming Shib authnRequest requestid: ' . $requestid);

		if (empty($requestcache['Issuer']))
			throw new Exception('Could not retrieve issuer of the AuthNRequest (ProviderID)');

	} catch(Exception $exception) {
		SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSAUTHNREQUEST', $exception);
	}


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

		$authId = $_GET['RequestID'];

		$requestcache = $session->getAuthnRequest('shib13', $authId);

		SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Got incoming RequestID: '. $authId);

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

/* Make sure that the issuer is a valid SP. */
try {
	$spMetadata = $metadata->getMetaDataConfig($requestcache['Issuer'], 'shib13-sp-remote');
} catch (Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'PROCESSAUTHNREQUEST', $exception);
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
	$authority = SimpleSAML_Utilities::getAuthority($idpmetadata);
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
if (!$session->isValid($authority) ) {

	$authId = SimpleSAML_Utilities::generateID();
	$session->setAuthnRequest('shib13', $authId, $requestcache);

	$redirectTo = SimpleSAML_Utilities::selfURLNoQuery() . '?RequestID=' . urlencode($authId);

	if($authSource) {
		/* Authenticate with an AuthSource. */
		$hints = array(
			'SPMetadata' => $spMetadata->toArray(),
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
}

/*
 * We got an request, and we hav a valid session. Then we send an AuthenticationResponse back to the
 * service.
 */
try {

	/* Validate the Shire the response should be sent to. */
	$shire = $requestcache['shire'];
	$foundACS = FALSE;
	foreach ($spMetadata->getEndpoints('AssertionConsumerService') as $acs) {
		if ($acs['Binding'] !== 'urn:oasis:names:tc:SAML:1.0:profiles:browser-post') {
			continue;
		}
		if ($acs['Location'] !== $shire) {
			continue;
		}

		SimpleSAML_Logger::info('Shib1.3 - IdP.SSOService: Found AssertionConsumerService: '. $acs);
		$foundACS = TRUE;
		break;
	}
	if (!$foundACS) {
		throw new Exception('Invalid AssertionConsumerService for SP ' .
			var_export($spMetadata->getString('entityid'), TRUE) . ': ' . var_export($shire, TRUE));
	}

	$attributes = $session->getAttributes();

	/* Authentication processing operations. */
	if (!isset($authProcState)) {
		/* Not processed. */
		$pc = new SimpleSAML_Auth_ProcessingChain($idpmetadata, $spMetadata->toArray(), 'idp');

		$authProcState = array(
			'core:shib13-idp:requestcache' => $requestcache,
			'ReturnURL' => SimpleSAML_Utilities::selfURLNoQuery(),
			'Attributes' => $attributes,
			'Destination' => $spMetadata->toArray(),
			'Source' => $idpmetadata,
			);

		$pc->processState($authProcState);
	}

	$attributes = $authProcState['Attributes'];

	/* Generate and send response. */
	$ar = new SimpleSAML_XML_Shib13_AuthnResponse();
	$authnResponseXML = $ar->generate($idpmetadata, $spMetadata->toArray(), $shire, $attributes);

	$httppost = new SimpleSAML_Bindings_Shib13_HTTPPost($config, $metadata);
	$httppost->sendResponse($authnResponseXML, $idpmetadata, $spMetadata->toArray(), $requestcache['RelayState'], $shire);

} catch(Exception $exception) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'GENERATEAUTHNRESPONSE', $exception);
}

?>