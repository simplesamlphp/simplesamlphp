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

		$requestId = $state['saml:RequestId'];
		$relayState = $state['saml:RelayState'];
		$consumerURL = $state['saml:ConsumerURL'];

		if (isset($state['saml:Binding'])) {
			$protocolBinding = $state['saml:Binding'];
		} else {
			/*
			 * To allow for upgrading while people are logging in.
			 * Should be removed in 1.7.
			 */
			$protocolBinding = SAML2_Const::BINDING_HTTP_POST;
		}

		$idp = SimpleSAML_IdP::getByState($state);

		$idpMetadata = $idp->getConfig();

		$assertion = sspmod_saml2_Message::buildAssertion($idpMetadata, $spMetadata, $state);
		$assertion->setInResponseTo($requestId);
		
		if (isset($state['saml:AuthenticatingAuthority'])) {
			$assertion->setAuthenticatingAuthority($state['saml:AuthenticatingAuthority']);
		}

		/* Create the session association (for logout). */
		$association = array(
			'id' => 'saml:' . $spEntityId,
			'Handler' => 'sspmod_saml_IdP_SAML2',
			'Expires' => $assertion->getSessionNotOnOrAfter(),
			'saml:entityID' => $spEntityId,
			'saml:NameID' => $assertion->getNameId(),
			'saml:SessionIndex' => $assertion->getSessionIndex(),
		);

		/* Maybe encrypt the assertion. */
		$assertion = sspmod_saml2_Message::encryptAssertion($idpMetadata, $spMetadata, $assertion);

		/* Create the response. */
		$ar = sspmod_saml2_Message::buildResponse($idpMetadata, $spMetadata, $consumerURL);
		$ar->setInResponseTo($requestId);
		$ar->setRelayState($relayState);
		$ar->setAssertions(array($assertion));

		/* Register the session association with the IdP. */
		$idp->addAssociation($association);

		/* Send the response. */
		$binding = SAML2_Binding::getBinding($protocolBinding);
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

		if (isset($state['saml:Binding'])) {
			$protocolBinding = $state['saml:Binding'];
		} else {
			/*
			 * To allow for upgrading while people are logging in.
			 * Should be removed in 1.7.
			 */
			$protocolBinding = SAML2_Const::BINDING_HTTP_POST;
		}

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

		$binding = SAML2_Binding::getBinding($protocolBinding);
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

		$supportedBindings = array(SAML2_Const::BINDING_HTTP_POST);
		if ($idpMetadata->getBoolean('saml20.sendartifact', FALSE)) {
			$supportedBindings[] = SAML2_Const::BINDING_HTTP_ARTIFACT;
		}

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

			if (isset($_REQUEST['binding'])){
				$protocolBinding = (string)$_REQUEST['binding'];
			} else {
				$protocolBinding = NULL;
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
			$protocolBinding = SAML2_Const::BINDING_HTTP_POST; /* HTTP-POST was the only supported binding before 1.6. */

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
			$ProxyCount = $request->getProxyCount();
			if ($ProxyCount !== null) $ProxyCount--;
			$RequesterID = $request->getRequesterID();
			$forceAuthn = $request->getForceAuthn();
			$isPassive = $request->getIsPassive();
			$consumerURL = $request->getAssertionConsumerServiceURL();
			$protocolBinding = $request->getProtocolBinding();

			SimpleSAML_Logger::info('SAML2.0 - IdP.SSOService: Incomming Authentication request: '. var_export($spEntityId, TRUE));
		}

		if ($protocolBinding === NULL || !in_array($protocolBinding, $supportedBindings, TRUE)) {
			/*
			 * No binding specified or unsupported binding requested - default to HTTP-POST.
			 * TODO: Select any supported binding based on default endpoint?
			 */
			$protocolBinding = SAML2_Const::BINDING_HTTP_POST;
		}

		if ($consumerURL !== NULL) {
			$found = FALSE;
			foreach ($spMetadata->getEndpoints('AssertionConsumerService') as $ep) {
				if ($ep['Binding'] !== $protocolBinding) {
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
			$consumerURL = $spMetadata->getDefaultEndpoint('AssertionConsumerService', array($protocolBinding));
			$consumerURL = $consumerURL['Location'];
		}

		$IDPList = array_unique(array_merge($IDPList, $spMetadata->getArrayizeString('IDPList', array())));
		if ($ProxyCount == null) $ProxyCount = $spMetadata->getInteger('ProxyCount', null);

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
			'saml:ProxyCount' => $ProxyCount,
			'saml:RequesterID' => $RequesterID,
			'ForceAuthn' => $forceAuthn,
			'isPassive' => $isPassive,
			'saml:ConsumerURL' => $consumerURL,
			'saml:Binding' => $protocolBinding,
		);

		$idp->handleAuthenticationRequest($state);
	}


	/**
	 * Send a logout response.
	 *
	 * @param array &$state  The logout state array.
	 */
	public static function sendLogoutResponse(SimpleSAML_IdP $idp, array $state) {
		assert('isset($state["saml:SPEntityId"])');
		assert('isset($state["saml:RequestId"])');
		assert('array_key_exists("saml:RelayState", $state)'); // Can be NULL.

		$spEntityId = $state['saml:SPEntityId'];

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $idp->getConfig();
		$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

		$lr = sspmod_saml2_Message::buildLogoutResponse($idpMetadata, $spMetadata);
		$lr->setInResponseTo($state['saml:RequestId']);
		$lr->setRelayState($state['saml:RelayState']);

		if (isset($state['core:Failed']) && $state['core:Failed']) {
			$lr->setStatus(array(
				'Code' => SAML2_Const::STATUS_SUCCESS,
				'SubCode' => SAML2_Const::STATUS_PARTIAL_LOGOUT,
			));
			SimpleSAML_Logger::info('Sending logout response for partial logout to SP ' . var_export($spEntityId, TRUE));
		} else {
			SimpleSAML_Logger::debug('Sending logout response to SP ' . var_export($spEntityId, TRUE));
		}

		$binding = new SAML2_HTTPRedirect();
		$binding->setDestination(sspmod_SAML2_Message::getDebugDestination());
		$binding->send($lr);
	}


	/**
	 * Receive a logout message.
	 *
	 * @param SimpleSAML_IdP $idp  The IdP we are receiving it for.
	 */
	public static function receiveLogoutMessage(SimpleSAML_IdP $idp) {

		$binding = SAML2_Binding::getCurrentBinding();
		$message = $binding->receive();

		$spEntityId = $message->getIssuer();
		if ($spEntityId === NULL) {
			/* Without an issuer we have no way to respond to the message. */
			throw new SimpleSAML_Error_BadRequest('Received message on logout endpoint without issuer.');
		}

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $idp->getConfig();
		$spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

		sspmod_saml2_Message::validateMessage($spMetadata, $idpMetadata, $message);

		if ($message instanceof SAML2_LogoutResponse) {

			SimpleSAML_Logger::info('Received SAML 2.0 LogoutResponse from: '. var_export($spEntityId, TRUE));

			$relayState = $message->getRelayState();

			if (!$message->isSuccess()) {
				$logoutError = sspmod_saml2_Message::getResponseError($message);
				SimpleSAML_Logger::warning('Unsuccessful logout. Status was: ' . $logoutError);
			} else {
				$logoutError = NULL;
			}

			$assocId = 'saml:' . $spEntityId;

			$idp->handleLogoutResponse($assocId, $relayState, $logoutError);


		} elseif ($message instanceof SAML2_LogoutRequest) {

			SimpleSAML_Logger::info('Received SAML 2.0 LogoutRequest from: '. var_export($spEntityId, TRUE));

			$spStatsId = $spMetadata->getString('core:statistics-id', $spEntityId);
			SimpleSAML_Logger::stats('saml20-idp-SLO spinit ' . $spStatsId . ' ' . $idpMetadata->getString('entityid'));

			$state = array(
				'Responder' => array('sspmod_saml_IdP_SAML2', 'sendLogoutResponse'),
				'saml:SPEntityId' => $spEntityId,
				'saml:RelayState' => $message->getRelayState(),
				'saml:RequestId' => $message->getId(),
			);

			$assocId = 'saml:' . $spEntityId;
			$idp->handleLogoutRequest($state, $assocId);

		} else {
			throw new SimpleSAML_Error_BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
		}

	}


	/**
	 * Retrieve a logout URL for a given logout association.
	 *
	 * @param SimpleSAML_IdP $idp  The IdP we are sending a logout request from.
	 * @param array $association  The association that should be terminated.
	 * @param string|NULL $relayState  An id that should be carried across the logout.
	 */
	public static function getLogoutURL(SimpleSAML_IdP $idp, array $association, $relayState) {
		assert('is_string($relayState) || is_null($relayState)');

		SimpleSAML_Logger::info('Sending SAML 2.0 LogoutRequest to: '. var_export($association['saml:entityID'], TRUE));

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$idpMetadata = $idp->getConfig();
		$spMetadata = $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');

		$lr = sspmod_saml2_Message::buildLogoutRequest($idpMetadata, $spMetadata);
		$lr->setRelayState($relayState);
		$lr->setSessionIndex($association['saml:SessionIndex']);
		$lr->setNameId($association['saml:NameID']);

		$binding = new SAML2_HTTPRedirect();
		return $binding->getRedirectURL($lr);
	}

}
