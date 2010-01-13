<?php

/**
 * IdP implementation for SAML 2.0 protocol.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_saml_IdP_SAML2 {

	/**
	 * Send a response to the SP.
	 *
	 * @param array $state  The authentication state.
	 */
	public static function sendResponse(array $state) {
		assert('isset($state["Attributes"])');
		assert('isset($state["SPMetadata"])');
		assert('isset($state["saml:ConsumerURL"])');
		assert('array_key_exists("saml:RequestId", $state)'); // Can be NULL.
		assert('array_key_exists("saml:RelayState", $state)'); // Can be NULL.

		$spMetadata = $state["SPMetadata"];
		$spEntityId = $spMetadata['entityid'];
		$spMetadata = SimpleSAML_Configuration::loadFromArray($spMetadata,
			'$metadata[' . var_export($spEntityId, TRUE) . ']');

		SimpleSAML_Logger::info('Sending SAML 2.0 Response to ' . var_export($spEntityId, TRUE));

		$attributes = $state['Attributes'];
		$requestId = $state['saml:RequestId'];
		$relayState = $state['saml:RelayState'];
		$consumerURL = $state['saml:ConsumerURL'];

		$idp = SimpleSAML_IdP::getByState($state);

		$idpMetadata = $idp->getConfig();

		$assertion = sspmod_saml2_Message::buildAssertion($idpMetadata, $spMetadata, $attributes, $consumerURL);
		$assertion->setInResponseTo($requestId);

		$nameId = $assertion->getNameId();

		/* Maybe encrypt the assertion. */
		$assertion = sspmod_saml2_Message::encryptAssertion($idpMetadata, $spMetadata, $assertion);

		/* Create the response. */
		$ar = sspmod_saml2_Message::buildResponse($idpMetadata, $spMetadata, $consumerURL);
		$ar->setInResponseTo($requestId);
		$ar->setRelayState($relayState);
		$ar->setAssertions(array($assertion));

		/* Add the session association (for logout). */
		$session = SimpleSAML_Session::getInstance();
		$session->add_sp_session($spEntityId);
		$session->setSessionNameId('saml20-sp-remote', $spEntityId, $nameId);

		/* Send the response. */
		$binding = new SAML2_HTTPPost();
		$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$binding->send($ar);
	}


	/**
	 * Handle authentication error.
	 *
	 * SimpleSAML_Error_Exception $exception  The exception.
	 * @param array $state  The error state.
	 */
	public static function handleAuthError(SimpleSAML_Error_Exception $exception, array $state) {
		assert('isset($state["SPMetadata"])');
		assert('isset($state["saml:ConsumerURL"])');
		assert('array_key_exists("saml:RequestId", $state)'); // Can be NULL.
		assert('array_key_exists("saml:RelayState", $state)'); // Can be NULL.

		$spMetadata = $state["SPMetadata"];
		$spEntityId = $spMetadata['entityid'];
		$spMetadata = SimpleSAML_Configuration::loadFromArray($spMetadata,
			'$metadata[' . var_export($spEntityId, TRUE) . ']');

		$requestId = $state['saml:RequestId'];
		$relayState = $state['saml:RelayState'];
		$consumerURL = $state['saml:ConsumerURL'];

		$idp = SimpleSAML_IdP::getByState($state);

		$idpMetadata = $idp->getConfig();

		$error = sspmod_saml2_Error::fromException($exception);

		SimpleSAML_Logger::warning('Returning error to sp: ' . var_export($spEntityId, TRUE));
		$error->logWarning();

		$ar = sspmod_saml2_Message::buildResponse($idpMetadata, $spMetadata, $consumerURL);
		$ar->setInResponseTo($requestId);
		$ar->setRelayState($relayState);

		$ar->setStatus(array(
			'Code' => $error->getStatus(),
			'SubCode' => $error->getSubStatus(),
			'Message' => $error->getStatusMessage(),
		));

		$binding = new SAML2_HTTPPost();
		$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$binding->send($ar);
	}


	/**
	 * Receive an authentication request.
	 *
	 * @param SimpleSAML_IdP $idp  The IdP we are receiving it for.
	 */
	public static function receiveAuthnRequest(SimpleSAML_IdP $idp) {

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $idp->getConfig();

		if (isset($_REQUEST['spentityid'])) {
			/* IdP initiated authentication. */

			if (isset($_REQUEST['cookieTime'])) {
				$cookieTime = (int)$_REQUEST['cookieTime'];
				if ($cookieTime + 5 > time()) {
					/*
					 * Less than five seconds has passed since we were
					 * here the last time. Cookies are probably disabled.
					 */
					SimpleSAML_Utilities::checkCookie(SimpleSAML_Utilities::selfURL());
				}
			}

			$spEntityId = (string)$_REQUEST['spentityid'];
			$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

			if (isset($_REQUEST['RelayState'])) {
				$relayState = (string)$_REQUEST['RelayState'];
			} else {
				$relayState = NULL;
			}

			$requestId = NULL;
			$IDPList = array();
			$forceAuthn = FALSE;
			$isPassive = FALSE;
			$consumerURL = NULL;

			SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: IdP initiated authentication: '. var_export($spEntityId, TRUE));

		} elseif (isset($_REQUEST['RequestID'])) {
			/*
			 * To allow for upgrading while people are logging in.
			 * Should be removed in 1.7.
			 */

			SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: RequestID received.');

			$session = SimpleSAML_Session::getInstance();

			$requestCache = $session->getAuthnRequest('saml2', (string)$_REQUEST['RequestID']);
			if (!$requestCache) {
				throw new Exception('Could not retrieve cached request...');
			}

			$spEntityId = $requestCache['Issuer'];
			$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

			$relayState = $requestCache['RelayState'];
			$requestId = $requestCache['RequestID'];
			$forceAuthn = $requestCache['ForceAuthn'];
			$isPassive = $requestCache['IsPassive'];

			if (isset($requestCache['IDPList'])) {
				$IDPList = $requestCache['IDPList'];
			} else {
				$IDPList = array();
			}

			if (isset($requestCache['ConsumerURL'])) {
				$consumerURL = $requestCache['ConsumerURL'];
			} else {
				$consumerURL = NULL;
			}

		} else {

			$binding = SAML2_Binding::getCurrentBinding();
			$request = $binding->receive();

			if (!($request instanceof SAML2_AuthnRequest)) {
				throw new SimpleSAML_Error_BadRequest('Message received on authentication request endpoint wasn\'t an authentication request.');
			}

			$spEntityId = $request->getIssuer();
			if ($spEntityId === NULL) {
				throw new SimpleSAML_Error_BadRequest('Received message on authentication request endpoint without issuer.');
			}
			$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

			sspmod_saml2_Message::validateMessage($spMetadata, $idpMetadata, $request);

			$relayState = $request->getRelayState();

			$requestId = $request->getId();
			$IDPList = $request->getIDPList();
			$forceAuthn = $request->getForceAuthn();
			$isPassive = $request->getIsPassive();
			$consumerURL = $request->getAssertionConsumerServiceURL();

			SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Incomming Authentication request: '. var_export($spEntityId, TRUE));
		}


		if ($consumerURL !== NULL) {
			$found = FALSE;
			foreach ($spMetadata->getEndpoints('AssertionConsumerService') as $ep) {
				if ($ep['Binding'] !== SAML2_Const::BINDING_HTTP_POST) {
					continue;
				}
				if ($ep['Location'] !== $consumerURL) {
					continue;
				}
				$found = TRUE;
				break;
			}

			if (!$found) {
				SimpleSAML_Logger::warning('Authentication request from ' . var_export($spEntityId, TRUE) .
					' contains invalid AssertionConsumerService URL. Was ' .
					var_export($consumerURL, TRUE) . '.');
				$consumerURL = NULL;
			}
		}
		if ($consumerURL === NULL) {
			/* Not specified or invalid. Use default. */
			$consumerURL = $spMetadata->getDefaultEndpoint('AssertionConsumerService', array(SAML2_Const::BINDING_HTTP_POST));
			$consumerURL = $consumerURL['Location'];
		}

		$IDPList = array_unique(array_merge($IDPList, $spMetadata->getArrayizeString('IDPList', array())));

		if (!$forceAuthn) {
			$forceAuthn = $spMetadata->getBoolean('ForceAuthn', FALSE);
		}

		$sessionLostParams = array(
			'spentityid' => $spEntityId,
			'cookieTime' => time(),
			);
		if ($relayState !== NULL) {
			$sessionLostParams['RelayState'] = $relayState;
		}

		$sessionLostURL = SimpleSAML_Utilities::addURLparameter(
			SimpleSAML_Utilities::selfURLNoQuery(),
			$sessionLostParams);

		$state = array(
			'Responder' => array('sspmod_saml_IdP_SAML2', 'sendResponse'),
			SimpleSAML_Auth_State::EXCEPTION_HANDLER_FUNC => array('sspmod_saml_IdP_SAML2', 'handleAuthError'),
			SimpleSAML_Auth_State::RESTART => $sessionLostURL,

			'SPMetadata' => $spMetadata->toArray(),
			'saml:RelayState' => $relayState,
			'saml:RequestId' => $requestId,
			'saml:IDPList' => $IDPList,
			'ForceAuthn' => $forceAuthn,
			'isPassive' => $isPassive,
			'saml:ConsumerURL' => $consumerURL,
		);

		$idp->handleAuthenticationRequest($state);
	}

}
