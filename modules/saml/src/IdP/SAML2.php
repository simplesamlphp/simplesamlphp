<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\IdP;

use Beste\Clock\LocalizedClock;
use DateInterval;
use DateTimeZone;
use DOMNodeList;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SimpleSAML\{Auth, Configuration, Error, IdP, Logger, Module, Stats, Utils};
use SimpleSAML\Assert\{Assert, AssertionFailedException};
use SimpleSAML\Metadata\MetaDataStorageHandler;
use SimpleSAML\Module\saml\Message;
use SimpleSAML\SAML2\{Binding, HTTPRedirect, SOAP}; // Bindings
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\ArrayValidationException;
use SimpleSAML\SAML2\XML\md\ContactPerson;
use SimpleSAML\SAML2\XML\saml\{Assertion, EncryptedAssertion}; // Assertions
use SimpleSAML\SAML2\XML\saml\{AttributeValue, Audience, Issuer, NameID, Subject, SubjectConfirmation, SubjectConfirmationData};
use SimpleSAML\SAML2\XML\saml\{AuthenticatingAuthority, AuthnContext, AuthnContextClassRef}; // AuthnContext
use SimpleSAML\SAML2\XML\samlp\{AuthnRequest, LogoutRequest, LogoutResponse, Response as SAML2_Response}; // Messages
use SimpleSAML\SAML2\XML\samlp\{Status, StatusCode, StatusMessage}; // Status
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XMLSecurity\XML\ds\{X509Certificate, X509Data, KeyInfo};
use Symfony\Bridge\PsrHttpMessage\Factory\{HttpFoundationFactory, PsrHttpFactory};
use Symfony\Component\HttpFoundation\{Request, Response};

use function array_key_exists;
use function array_map;
use function array_merge;
use function array_pop;
use function array_unique;
use function array_unshift;
use function base64_encode;
use function chunk_split;
use function get_class;
use function in_array;
use function intval;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;
use function microtime;
use function preg_match;
use function sprintf;
use function str_replace;
use function time;
use function var_export;

/**
 * IdP implementation for SAML 2.0 protocol.
 *
 * @package SimpleSAMLphp
 */
class SAML2
{
    /**
     * Send a response to the SP.
     *
     * @param array $state The authentication state.
     */
    public static function sendResponse(array $state): Response
    {
        Assert::keyExists($state, 'saml:RequestId'); // Can be NULL
        Assert::keyExists($state, 'saml:RelayState'); // Can be NULL.
        Assert::notNull($state['Attributes']);
        Assert::notNull($state['SPMetadata']);
        Assert::notNull($state['saml:ConsumerURL']);

        $spMetadata = $state["SPMetadata"];
        $spEntityId = $spMetadata['entityid'];
        $spMetadata = Configuration::loadFromArray(
            $spMetadata,
            '$metadata[' . var_export($spEntityId, true) . ']',
        );

        Logger::info('Sending SAML 2.0 Response to ' . var_export($spEntityId, true));

        $requestId = $state['saml:RequestId'];
        $relayState = $state['saml:RelayState'];
        $consumerURL = $state['saml:ConsumerURL'];
        $protocolBinding = $state['saml:Binding'];

        $idp = IdP::getByState(Configuration::getInstance(), $state);

        $idpMetadata = $idp->getConfig();

        $assertion = self::buildAssertion($idpMetadata, $spMetadata, $state);

        // create the session association (for logout)
        $association = [
            'id'                => 'saml:' . $spEntityId,
            'Handler'           => '\SimpleSAML\Module\saml\IdP\SAML2',
            'Expires'           => $assertion->getSessionNotOnOrAfter(),
            'saml:entityID'     => $spEntityId,
            'saml:NameID'       => $state['saml:idp:NameID'],
            'saml:SessionIndex' => $assertion->getSessionIndex(),
        ];

        // maybe encrypt the assertion
        $assertion = self::encryptAssertion($idpMetadata, $spMetadata, $assertion);

        // create the response
        $ar = self::buildResponse($idpMetadata, $spMetadata, $consumerURL);
        $ar->setInResponseTo($requestId);
        $ar->setRelayState($relayState);
        $ar->setAssertions([$assertion]);

        // register the session association with the IdP
        $idp->addAssociation($association);

        $statsData = [
            'spEntityID'  => $spEntityId,
            'idpEntityID' => $idpMetadata->getString('entityid'),
            'protocol'    => 'saml2',
        ];
        if (isset($state['saml:AuthnRequestReceivedAt'])) {
            $statsData['logintime'] = microtime(true) - $state['saml:AuthnRequestReceivedAt'];
        }
        Stats::log('saml:idp:Response', $statsData);

        // send the response
        $binding = Binding::getBinding($protocolBinding);
        $psrResponse = $binding->send($ar);

        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createResponse($psrResponse);
    }


    /**
     * Handle authentication error.
     *
     * \SimpleSAML\Error\Exception $exception  The exception.
     *
     * @param array $state The error state.
     */
    public static function handleAuthError(Error\Exception $exception, array $state): Response
    {
        Assert::keyExists($state, 'saml:RequestId'); // Can be NULL.
        Assert::keyExists($state, 'saml:RelayState'); // Can be NULL.
        Assert::notNull($state['SPMetadata']);
        Assert::notNull($state['saml:ConsumerURL']);

        $spMetadata = $state["SPMetadata"];
        $spEntityId = $spMetadata['entityid'];
        $spMetadata = Configuration::loadFromArray(
            $spMetadata,
            '$metadata[' . var_export($spEntityId, true) . ']',
        );

        $requestId = $state['saml:RequestId'];
        $relayState = $state['saml:RelayState'];
        $consumerURL = $state['saml:ConsumerURL'];
        $protocolBinding = $state['saml:Binding'];

        $idp = IdP::getByState(Configuration::getInstance(), $state);

        $idpMetadata = $idp->getConfig();

        /** @var \SimpleSAML\Module\saml\Error $error */
        $error = Module\saml\Error::fromException($exception);

        Logger::warning(sprintf("Returning error to SP with entity ID %s.", var_export($spEntityId, true)));
        $exception->log(Logger::WARNING);

        $ar = self::buildResponse($idpMetadata, $spMetadata, $consumerURL);
        $ar->setInResponseTo($requestId);
        $ar->setRelayState($relayState);

        $subStatus = $error->getSubStatus();
        if ($subStatus !== null) {
            $subStatus = new StatusCode($subStatus);
        }

        $statusMessage = $error->getStatusMessage();
        if ($statusMessage !== null) {
            $statusMessage = new StatusMessage($statusMessage);
        }

        $status = new Status(
            new StatusCode($error->getStatus(), $subStatus ? [$subStatus] : []),
            $statusMessage,
        );
        $ar->setStatus($status);

        $statsData = [
            'spEntityID'  => $spEntityId,
            'idpEntityID' => $idpMetadata->getString('entityid'),
            'protocol'    => 'saml2',
            'error'       => $status,
        ];
        if (isset($state['saml:AuthnRequestReceivedAt'])) {
            $statsData['logintime'] = microtime(true) - $state['saml:AuthnRequestReceivedAt'];
        }
        Stats::log('saml:idp:Response:error', $statsData);

        $binding = Binding::getBinding($protocolBinding);
        $psrResponse = $binding->send($ar);

        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createResponse($psrResponse);
    }


    /**
     * Find SP AssertionConsumerService based on parameter in AuthnRequest.
     *
     * @param array                     $supportedBindings The bindings we allow for the response.
     * @param \SimpleSAML\Configuration $spMetadata The metadata for the SP.
     * @param string|null               $AssertionConsumerServiceURL AssertionConsumerServiceURL from request.
     * @param string|null               $ProtocolBinding ProtocolBinding from request.
     * @param int|null                  $AssertionConsumerServiceIndex AssertionConsumerServiceIndex from request.
     * @param bool                      $authnRequestSigned Whether or not the authn request was signed.
     *
     * @return array|null  Array with the Location and Binding we should use for the response.
     */
    private static function getAssertionConsumerService(
        array $supportedBindings,
        Configuration $spMetadata,
        ?string $AssertionConsumerServiceURL = null,
        ?string $ProtocolBinding = null,
        ?int $AssertionConsumerServiceIndex = null,
        bool $authnRequestSigned = false,
    ): ?array {
        /* We want to pick the best matching endpoint in the case where for example
         * only the ProtocolBinding is given. We therefore pick endpoints with the
         * following priority:
         *  1. isDefault="true"
         *  2. isDefault unset
         *  3. isDefault="false"
         */
        $firstNotFalse = null;
        $firstFalse = null;
        foreach ($spMetadata->getEndpoints('AssertionConsumerService') as $ep) {
            if ($AssertionConsumerServiceURL !== null && $ep['Location'] !== $AssertionConsumerServiceURL) {
                continue;
            }
            if ($ProtocolBinding !== null && $ep['Binding'] !== $ProtocolBinding) {
                continue;
            }
            if ($AssertionConsumerServiceIndex !== null && $ep['index'] !== $AssertionConsumerServiceIndex) {
                continue;
            }

            if (!in_array($ep['Binding'], $supportedBindings, true)) {
                /* The endpoint has an unsupported binding. */
                continue;
            }

            // we have an endpoint that matches all our requirements. Check if it is the best one

            if (array_key_exists('isDefault', $ep)) {
                if ($ep['isDefault'] === true) {
                    // this is the first matching endpoint with isDefault set to true
                    return $ep;
                }
                // isDefault is set to FALSE, but the endpoint is still usable
                if ($firstFalse === null) {
                    // this is the first endpoint that we can use
                    $firstFalse = $ep;
                }
            } else {
                if ($firstNotFalse === null) {
                    // this is the first endpoint without isDefault set
                    $firstNotFalse = $ep;
                }
            }
        }

        if ($firstNotFalse !== null) {
            return $firstNotFalse;
        } elseif ($firstFalse !== null) {
            return $firstFalse;
        }

        $skipEndpointValidation = false;
        if ($authnRequestSigned === true) {
            $skipEndpointValidationWhenSigned = $spMetadata->getOptionalValue(
                'skipEndpointValidationWhenSigned',
                false,
            );
            if (is_bool($skipEndpointValidationWhenSigned) === true) {
                $skipEndpointValidation = $skipEndpointValidationWhenSigned;
            } elseif (is_callable($skipEndpointValidationWhenSigned) === true) {
                $shouldSkipEndpointValidation = $skipEndpointValidationWhenSigned($spMetadata);
                if (is_bool($shouldSkipEndpointValidation) === true) {
                    $skipEndpointValidation = $shouldSkipEndpointValidation;
                }
            }
        }

        if (($AssertionConsumerServiceURL !== null) && ($skipEndpointValidation === true)) {
            Logger::info(
                'AssertionConsumerService specified in AuthnRequest not in metadata, ' .
                'using anyway because AuthnRequest signed and skipEndpointValidationWhenSigned was true',
            );
            return ['Location' => $AssertionConsumerServiceURL, 'Binding' => $ProtocolBinding];
        }

        Logger::warning('Authentication request specifies invalid AssertionConsumerService:');
        if ($AssertionConsumerServiceURL !== null) {
            Logger::warning('AssertionConsumerServiceURL: ' . var_export($AssertionConsumerServiceURL, true));
        }
        if ($ProtocolBinding !== null) {
            Logger::warning('ProtocolBinding: ' . var_export($ProtocolBinding, true));
        }
        if ($AssertionConsumerServiceIndex !== null) {
            Logger::warning(
                'AssertionConsumerServiceIndex: ' . var_export($AssertionConsumerServiceIndex, true),
            );
        }

        // we have no good endpoints. Our last resort is to just use the default endpoint
        return $spMetadata->getDefaultEndpoint('AssertionConsumerService', $supportedBindings);
    }


    /**
     * Receive an authentication request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\IdP $idp The IdP we are receiving it for.
     * @throws \SimpleSAML\Error\BadRequest In case an error occurs when trying to receive the request.
     */
    public static function receiveAuthnRequest(Request $request, IdP $idp): Response
    {
        $metadata = MetaDataStorageHandler::getMetadataHandler(Configuration::getInstance());
        $idpMetadata = $idp->getConfig();
        $httpUtils = new Utils\HTTP();

        $supportedBindings = [C::BINDING_HTTP_POST];
        if ($idpMetadata->getOptionalBoolean('saml20.sendartifact', false)) {
            $supportedBindings[] = C::BINDING_HTTP_ARTIFACT;
        }
        if ($idpMetadata->getOptionalBoolean('saml20.hok.assertion', false)) {
            $supportedBindings[] = C::BINDING_HOK_SSO;
        }
        if ($idpMetadata->getOptionalBoolean('saml20.ecp', false)) {
            $supportedBindings[] = C::BINDING_PAOS;
        }

        $authnRequestSigned = false;
        $username = null;

        if ($request->query->has('spentityid') || $request->query->has('providerId')) {
            /* IdP initiated authentication. */

            if ($request->query->has('cookieTime')) {
                $cookieTime = intval($request->query->get('cookieTime'));
                if ($cookieTime + 5 > time()) {
                    /*
                     * Less than five seconds has passed since we were
                     * here the last time. Cookies are probably disabled.
                     */
                    $httpUtils->checkSessionCookie($httpUtils->getSelfURL());
                }
            }

            $spEntityId = $request->query->has('spentityid')
                ? $request->query->get('spentityid')
                : $request->query->get('providerId');
            $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

            $relayState = null;
            if ($request->query->has('RelayState')) {
                $relayState = $request->query->get('RelayState');
            } elseif ($request->query->has('target')) {
                $relayState = $request->query->get('target');
            }

            $protocolBinding = null;
            if ($request->query->has('binding')) {
                $protocolBinding = $request->query->get('binding');
            }

            $nameIDFormat = null;
            if ($request->query->has('NameIDFormat')) {
                $nameIDFormat = $request->query->get('NameIDFormat');
            }

            $consumerURL = null;
            if ($request->query->has('ConsumerURL')) {
                $consumerURL = $request->query->get('ConsumerURL');
            } elseif ($request->query->has('shire')) {
                $consumerURL = $request->query->get('shire');
            }

            $requestId = null;
            $IDPList = [];
            $ProxyCount = null;
            $RequesterID = null;
            $forceAuthn = false;
            $isPassive = false;
            $consumerIndex = null;
            $extensions = null;
            $allowCreate = true;
            $authnContext = null;

            $idpInit = true;

            Logger::info(
                'SAML2.0 - IdP.SSOService: IdP initiated authentication: ' . var_export($spEntityId, true),
            );
        } else {
            $psr17Factory = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            $psrRequest = $psrHttpFactory->createRequest($request);
            $binding = Binding::getCurrentBinding($psrRequest);
            $authnRequest = $binding->receive($psrRequest);

            if (!($authnRequest instanceof AuthnRequest)) {
                throw new Error\BadRequest(
                    "Message received on authentication request endpoint wasn't an authentication request.",
                );
            }

            if (isset($_REQUEST['username'])) {
                $username = (string) $_REQUEST['username'];
            }

            $issuer = $authnRequest->getIssuer();
            if ($issuer === null) {
                throw new Error\BadRequest(
                    'Received message on authentication request endpoint without issuer.',
                );
            }
            $spEntityId = $issuer->getContent();
            $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

            $authnRequestSigned = Message::validateMessage($spMetadata, $idpMetadata, $authnRequest);

            $relayState = $authnRequest->getRelayState();

            $requestId = $authnRequest->getId();
            $scoping = $authnRequest->getScoping();

            $ProxyCount = $scoping?->getProxyCount();
            if ($ProxyCount !== null) {
                $ProxyCount--;
            }

            $IDPList = $scoping?->getIDPList();
            if ($IDPList !== null) {
                $IDPList = ($IDPList->toArray())['IDPEntry'];
            } else {
                $IDPList = [];
            }

            $RequesterID = $scoping?->getRequesterID();
            if ($RequesterID !== null) {
                foreach ($RequesterID as $k => $rid) {
                    $rid = $rid->toArray();
                    $RequesterID[$k] = array_pop($rid);
                }
            }

            $forceAuthn = $authnRequest->getForceAuthn();
            $isPassive = $authnRequest->getIsPassive();
            $consumerURL = $authnRequest->getAssertionConsumerServiceURL();
            $protocolBinding = $authnRequest->getProtocolBinding();
            $consumerIndex = $authnRequest->getAssertionConsumerServiceIndex();
            $extensions = $authnRequest->getExtensions();
            $authnContext = $authnRequest->getRequestedAuthnContext();

            $nameIdPolicy = $authnRequest->getNameIdPolicy();
            $nameIDFormat = $nameIdPolicy?->getFormat();
            $allowCreate = $nameIdPolicy?->getAllowCreate() ?? false;

            $idpInit = false;

            Logger::info(
                'SAML2.0 - IdP.SSOService: incoming authentication request: ' . var_export($spEntityId, true),
            );
        }

        Stats::log('saml:idp:AuthnRequest', [
            'spEntityID'  => $spEntityId,
            'idpEntityID' => $idpMetadata->getString('entityid'),
            'forceAuthn'  => $forceAuthn,
            'isPassive'   => $isPassive,
            'protocol'    => 'saml2',
            'idpInit'     => $idpInit,
        ]);

        $acsEndpoint = self::getAssertionConsumerService(
            $supportedBindings,
            $spMetadata,
            $consumerURL,
            $protocolBinding,
            $consumerIndex,
            $authnRequestSigned,
        );
        if ($acsEndpoint === null) {
            throw new Exception('Unable to use any of the ACS endpoints found for SP \'' . $spEntityId . '\'');
        }

        $IDPList = array_unique(array_merge($IDPList, $spMetadata->getOptionalArrayizeString('IDPList', [])));
        if ($ProxyCount === null) {
            $ProxyCount = $spMetadata->getOptionalInteger('ProxyCount', null);
        }

        if (!$forceAuthn) {
            $forceAuthn = $spMetadata->getOptionalBoolean('ForceAuthn', false);
        }

        $sessionLostParams = [
            'spentityid' => $spEntityId,
        ];
        if ($relayState !== null) {
            $sessionLostParams['RelayState'] = $relayState;
        }
        /*
        Putting cookieTime as the last parameter makes unit testing easier since we don't need to handle a
        changing time component in the middle of the url
        */
        $sessionLostParams['cookieTime'] = time();

        $sessionLostURL = $httpUtils->addURLParameters(
            $httpUtils->getSelfURLNoQuery(),
            $sessionLostParams,
        );

        $state = [
            'Responder' => ['\SimpleSAML\Module\saml\IdP\SAML2', 'sendResponse'],
            Auth\State::EXCEPTION_HANDLER_FUNC => [
                '\SimpleSAML\Module\saml\IdP\SAML2',
                'handleAuthError',
            ],
            Auth\State::RESTART => $sessionLostURL,

            'SPMetadata'                  => $spMetadata->toArray(),
            'saml:RelayState'             => $relayState,
            'saml:RequestId'              => $requestId,
            'saml:IDPList'                => $IDPList,
            'saml:ProxyCount'             => $ProxyCount,
            'saml:RequesterID'            => $RequesterID,
            'ForceAuthn'                  => $forceAuthn,
            'isPassive'                   => $isPassive,
            'saml:ConsumerURL'            => $acsEndpoint['Location'],
            'saml:Binding'                => $acsEndpoint['Binding'],
            'saml:NameIDFormat'           => $nameIDFormat,
            'saml:AllowCreate'            => $allowCreate,
            'saml:Extensions'             => $extensions,
            'saml:AuthnRequestReceivedAt' => microtime(true),
            'saml:RequestedAuthnContext'  => $authnContext,
        ];

        if ($username !== null) {
            $state['core:username'] = $username;
        }

        return $idp->handleAuthenticationRequest($state);
    }


    /**
     * Send a logout request to a given association.
     *
     * @param \SimpleSAML\IdP $idp The IdP we are sending a logout request from.
     * @param array           $association The association that should be terminated.
     * @param string|null     $relayState An id that should be carried across the logout.
     */
    public static function sendLogoutRequest(IdP $idp, array $association, ?string $relayState = null): Response
    {
        Logger::info('Sending SAML 2.0 LogoutRequest to: ' . var_export($association['saml:entityID'], true));

        $metadata = MetaDataStorageHandler::getMetadataHandler(Configuration::getInstance());
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');

        Stats::log('saml:idp:LogoutRequest:sent', [
            'spEntityID'  => $association['saml:entityID'],
            'idpEntityID' => $idpMetadata->getString('entityid'),
        ]);

        /** @var array $dst */
        $dst = $spMetadata->getEndpointPrioritizedByBinding(
            'SingleLogoutService',
            [
                C::BINDING_HTTP_REDIRECT,
                C::BINDING_HTTP_POST,
            ],
        );

        $binding = Binding::getBinding($dst['Binding']);
        $lr = self::buildLogoutRequest($idpMetadata, $spMetadata, $association, $relayState);
        $lr->setDestination($dst['Location']);

        $psrResponse = $binding->send($lr);
        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createResponse($psrResponse);
    }


    /**
     * Send a logout response.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\IdP $idp The IdP we are sending a logout request from.
     * @param array           &$state The logout state array.
     */
    public static function sendLogoutResponse(Request $request, IdP $idp, array $state): Response
    {
        Assert::keyExists($state, 'saml:RelayState'); // Can be NULL.
        Assert::notNull($state['saml:SPEntityId']);
        Assert::notNull($state['saml:RequestId']);

        $spEntityId = $state['saml:SPEntityId'];

        $metadata = MetaDataStorageHandler::getMetadataHandler(Configuration::getInstance());
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

        $lr = Message::buildLogoutResponse($idpMetadata, $spMetadata);
        $lr->setInResponseTo($state['saml:RequestId']);
        $lr->setRelayState($state['saml:RelayState']);

        if (isset($state['core:Failed']) && $state['core:Failed']) {
            $partial = true;
            $lr->setStatus(new Status(new StatusCode(
                C::STATUS_SUCCESS,
                [
                    new StatusCode(C::STATUS_PARTIAL_LOGOUT),
                ],
            )));
            Logger::info('Sending logout response for partial logout to SP ' . var_export($spEntityId, true));
        } else {
            $partial = false;
            Logger::debug('Sending logout response to SP ' . var_export($spEntityId, true));
        }

        Stats::log('saml:idp:LogoutResponse:sent', [
            'spEntityID'  => $spEntityId,
            'idpEntityID' => $idpMetadata->getString('entityid'),
            'partial'     => $partial,
        ]);

        /** @var array $dst */
        $dst = $spMetadata->getEndpointPrioritizedByBinding(
            'SingleLogoutService',
            [
                C::BINDING_HTTP_REDIRECT,
                C::BINDING_HTTP_POST,
            ],
        );
        $binding = Binding::getBinding($dst['Binding']);
        if (isset($dst['ResponseLocation'])) {
            $dst = $dst['ResponseLocation'];
        } else {
            $dst = $dst['Location'];
        }
        $lr->setDestination($dst);

        $psrResponse = $binding->send($lr);

        $httpFoundationFactory = new HttpFoundationFactory();
        return $httpFoundationFactory->createResponse($psrResponse);
    }


    /**
     * Receive a logout message.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\IdP $idp The IdP we are receiving it for.
     * @throws \SimpleSAML\Error\BadRequest In case an error occurs while trying to receive the logout message.
     */
    public static function receiveLogoutMessage(Request $request, IdP $idp): Response
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        $binding = Binding::getCurrentBinding($psrRequest);
        $message = $binding->receive($psrRequest);

        $issuer = $message->getIssuer();
        if ($issuer === null) {
            /* Without an issuer we have no way to respond to the message. */
            throw new Error\BadRequest('Received message on logout endpoint without issuer.');
        } else {
            $spEntityId = $issuer->getContent();
        }

        $metadata = MetaDataStorageHandler::getMetadataHandler(Configuration::getInstance());
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($spEntityId, 'saml20-sp-remote');

        Message::validateMessage($spMetadata, $idpMetadata, $message);

        if ($message instanceof LogoutResponse) {
            Logger::info('Received SAML 2.0 LogoutResponse from: ' . var_export($spEntityId, true));
            $statsData = [
                'spEntityID'  => $spEntityId,
                'idpEntityID' => $idpMetadata->getString('entityid'),
            ];
            if (!$message->isSuccess()) {
                $statsData['error'] = $message->getStatus();
            }
            Stats::log('saml:idp:LogoutResponse:recv', $statsData);

            $relayState = $message->getRelayState();

            if (!$message->isSuccess()) {
                $logoutError = Message::getResponseError($message);
                Logger::warning('Unsuccessful logout. Status was: ' . $logoutError);
            } else {
                $logoutError = null;
            }

            $assocId = 'saml:' . $spEntityId;

            return $idp->handleLogoutResponse($assocId, $relayState, $logoutError);
        } elseif ($message instanceof LogoutRequest) {
            Logger::info('Received SAML 2.0 LogoutRequest from: ' . var_export($spEntityId, true));
            Stats::log('saml:idp:LogoutRequest:recv', [
                'spEntityID'  => $spEntityId,
                'idpEntityID' => $idpMetadata->getString('entityid'),
            ]);

            $spStatsId = $spMetadata->getOptionalString('core:statistics-id', $spEntityId);
            Logger::stats('saml20-idp-SLO spinit ' . $spStatsId . ' ' . $idpMetadata->getString('entityid'));

            $state = [
                'Responder'       => ['\SimpleSAML\Module\saml\IdP\SAML2', 'sendLogoutResponse'],
                'saml:SPEntityId' => $spEntityId,
                'saml:RelayState' => $message->getRelayState(),
                'saml:RequestId'  => $message->getId(),
            ];

            $assocId = 'saml:' . $spEntityId;
            return $idp->handleLogoutRequest($state, $assocId);
        }

        throw new Error\BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
    }


    /**
     * Retrieve a logout URL for a given logout association.
     *
     * @param \SimpleSAML\IdP $idp The IdP we are sending a logout request from.
     * @param array           $association The association that should be terminated.
     * @param string|NULL     $relayState An id that should be carried across the logout.
     *
     * @return string The logout URL.
     */
    public static function getLogoutURL(IdP $idp, array $association, ?string $relayState = null): string
    {
        Logger::info('Sending SAML 2.0 LogoutRequest to: ' . var_export($association['saml:entityID'], true));

        $metadata = MetaDataStorageHandler::getMetadataHandler(Configuration::getInstance());
        $idpMetadata = $idp->getConfig();
        $spMetadata = $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');

        $bindings = [
            C::BINDING_HTTP_REDIRECT,
            C::BINDING_HTTP_POST,
        ];

        /** @var array $dst */
        $dst = $spMetadata->getEndpointPrioritizedByBinding('SingleLogoutService', $bindings);

        if ($dst['Binding'] === C::BINDING_HTTP_POST) {
            $params = ['association' => $association['id'], 'idp' => $idp->getId()];
            if ($relayState !== null) {
                $params['RelayState'] = $relayState;
            }
            return Module::getModuleURL('core/logout-iframe-post', $params);
        }

        $lr = self::buildLogoutRequest($idpMetadata, $spMetadata, $association, $relayState);
        $lr->setDestination($dst['Location']);

        $binding = new HTTPRedirect();
        return $binding->getRedirectURL($lr);
    }


    /**
     * Retrieve the metadata for the given SP association.
     *
     * @param \SimpleSAML\IdP $idp The IdP the association belongs to.
     * @param array           $association The SP association.
     *
     * @return \SimpleSAML\Configuration  Configuration object for the SP metadata.
     */
    public static function getAssociationConfig(IdP $idp, array $association): Configuration
    {
        $metadata = MetaDataStorageHandler::getMetadataHandler(Configuration::getInstance());
        try {
            return $metadata->getMetaDataConfig($association['saml:entityID'], 'saml20-sp-remote');
        } catch (Exception $e) {
            return Configuration::loadFromArray([], 'Unknown SAML 2 entity.');
        }
    }


    /**
     * Retrieve the metadata of a hosted SAML 2 IdP.
     *
     * @param string $entityid The entity ID of the hosted SAML 2 IdP whose metadata we want.
     * @param MetaDataStorageHandler|null $handler Optionally the metadata storage to use,
     *        if omitted the configured handler will be used.
     *
     * @return array
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     * @throws \SimpleSAML\Error\Exception
     * @throws \SimpleSAML\Error\MetadataNotFound
     */
    public static function getHostedMetadata(string $entityid, ?MetaDataStorageHandler $handler = null): array
    {
        $globalConfig = Configuration::getInstance();
        if ($handler === null) {
            $handler = MetaDataStorageHandler::getMetadataHandler($globalConfig);
        }
        $config = $handler->getMetaDataConfig($entityid, 'saml20-idp-hosted');

        $host = $config->getOptionalString('host', null);
        $host = $host === '__DEFAULT__' ? null : $host;

        // configure endpoints
        $ssob = $handler->getGenerated('SingleSignOnServiceBinding', 'saml20-idp-hosted', $host, $entityid);
        $slob = $handler->getGenerated('SingleLogoutServiceBinding', 'saml20-idp-hosted', $host, $entityid);
        $ssol = $handler->getGenerated('SingleSignOnService', 'saml20-idp-hosted', $host, $entityid);
        $slol = $handler->getGenerated('SingleLogoutService', 'saml20-idp-hosted', $host, $entityid);

        $sso = [];
        if (is_array($ssob)) {
            foreach ($ssob as $binding) {
                $sso[] = [
                    'Binding'  => $binding,
                    'Location' => $ssol,
                ];
            }
        } else {
            $sso[] = [
                'Binding'  => $ssob,
                'Location' => $ssol,
            ];
        }

        $slo = [];
        if (is_array($slob)) {
            foreach ($slob as $binding) {
                $slo[] = [
                    'Binding'  => $binding,
                    'Location' => $slol,
                ];
            }
        } else {
            $slo[] = [
                'Binding'  => $slob,
                'Location' => $slol,
            ];
        }

        $metadata = [
            'metadata-set' => 'saml20-idp-hosted',
            'entityid' => $entityid,
            'SingleSignOnService' => $sso,
            'SingleLogoutService' => $slo,
            'NameIDFormat' => $config->getOptionalArrayizeString('NameIDFormat', [C::NAMEID_TRANSIENT]),
        ];

        // metadata signing
        if ($config->hasValue('metadata.sign.enable')) {
            $metadata += ['metadata.sign.enable' => $config->getBoolean('metadata.sign.enable')];

            if ($config->hasValue('metadata.sign.privatekey')) {
                $metadata += ['metadata.sign.privatekey' => $config->getString('metadata.sign.privatekey')];
            }
            if ($config->hasValue('metadata.sign.privatekey_pass')) {
                $metadata += ['metadata.sign.privatekey_pass' => $config->getString('metadata.sign.privatekey_pass')];
            }
            if ($config->hasValue('metadata.sign.certificate')) {
                $metadata += ['metadata.sign.certificate' => $config->getString('metadata.sign.certificate')];
            }
            if ($config->hasValue('metadata.sign.algorithm')) {
                $metadata += ['metadata.sign.algorithm' => $config->getString('metadata.sign.algorithm')];
            }
        }

        $cryptoUtils = new Utils\Crypto();
        $httpUtils = new Utils\HTTP();

        // add certificates
        $keys = [];
        $certInfo = $cryptoUtils->loadPublicKey($config, false, 'new_');
        $hasNewCert = false;
        if ($certInfo !== null) {
            $keys[] = [
                'name' => $certInfo['name'] ?? null,
                'type' => 'X509Certificate',
                'signing' => true,
                'encryption' => true,
                'X509Certificate' => $certInfo['certData'],
                'prefix' => 'new_',
            ];
            $hasNewCert = true;
        }

        /** @var array $certInfo */
        $certInfo = $cryptoUtils->loadPublicKey($config, true);
        $keys[] = [
            'name' => $certInfo['name'] ?? null,
            'type' => 'X509Certificate',
            'signing' => true,
            'encryption' => $hasNewCert === false,
            'X509Certificate' => $certInfo['certData'],
            'prefix' => '',
        ];

        if ($config->hasValue('https.certificate')) {
            /** @var array $httpsCert */
            $httpsCert = $cryptoUtils->loadPublicKey($config, true, 'https.');
            $keys[] = [
                'name' => $httpsCert['name'] ?? null,
                'type' => 'X509Certificate',
                'signing' => true,
                'encryption' => false,
                'X509Certificate' => $httpsCert['certData'],
                'prefix' => 'https.',
            ];
        }
        $metadata['keys'] = $keys;

        // add ArtifactResolutionService endpoint, if enabled
        if ($config->getOptionalBoolean('saml20.sendartifact', false)) {
            $metadata['ArtifactResolutionService'][] = [
                'index' => 0,
                'Binding' => C::BINDING_SOAP,
                'Location' => Module::getModuleURL('saml/idp/artifactResolutionService'),
            ];
        }

        // add Holder of Key, if enabled
        if ($config->getOptionalBoolean('saml20.hok.assertion', false)) {
            array_unshift(
                $metadata['SingleSignOnService'],
                [
                    'hoksso:ProtocolBinding' => C::BINDING_HTTP_REDIRECT,
                    'Binding' => C::BINDING_HOK_SSO,
                    'Location' => Module::getModuleURL('saml/idp/singleSignOnService'),
                ],
            );
        }

        // add ECP profile, if enabled
        if ($config->getOptionalBoolean('saml20.ecp', false)) {
            $metadata['SingleSignOnService'][] = [
                'index' => 0,
                'Binding' => C::BINDING_SOAP,
                'Location' => Module::getModuleURL('saml/idp/singleSignOnService'),
            ];
        }

        // add organization information
        if ($config->hasValue('OrganizationName')) {
            $metadata['OrganizationName'] = $config->getLocalizedString('OrganizationName');
            $metadata['OrganizationDisplayName'] = $config->getOptionalLocalizedString(
                'OrganizationDisplayName',
                $metadata['OrganizationName'],
            );

            if (!$config->hasValue('OrganizationURL')) {
                throw new Error\Exception('If OrganizationName is set, OrganizationURL must also be set.');
            }
            $metadata['OrganizationURL'] = $config->getLocalizedString('OrganizationURL');
        }

        // add scope
        if ($config->hasValue('scope')) {
            $metadata['scope'] = $config->getArray('scope');
        }

        // add extensions
        if ($config->hasValue('EntityAttributes')) {
            $metadata['EntityAttributes'] = $config->getArray('EntityAttributes');

            // check for entity categories
            if (Utils\Config\Metadata::isHiddenFromDiscovery($metadata)) {
                $metadata['hide.from.discovery'] = true;
            }
        }

        if ($config->hasValue('saml:Extensions')) {
            $metadata['saml:Extensions'] = $config->getArray('saml:Extensions');
        }

        if ($config->hasValue('UIInfo')) {
            $metadata['UIInfo'] = $config->getArray('UIInfo');
        }

        if ($config->hasValue('DiscoHints')) {
            $metadata['DiscoHints'] = $config->getArray('DiscoHints');
        }

        if ($config->hasValue('RegistrationInfo')) {
            $metadata['RegistrationInfo'] = $config->getArray('RegistrationInfo');
        }

        // configure signature options
        if ($config->hasValue('validate.authnrequest')) {
            $metadata['sign.authnrequest'] = $config->getBoolean('validate.authnrequest');
        }

        if ($config->hasValue('redirect.validate')) {
            $metadata['redirect.sign'] = $config->getBoolean('redirect.validate');
        }

        // add contact information
        if ($config->hasValue('contacts')) {
            $contacts = $config->getArray('contacts');
            foreach ($contacts as $contact) {
                try {
                    $metadata['contacts'][] = ContactPerson::fromArray($contact)->toArray();
                } catch (ArrayValidationException $e) {
                    Logger::warning('Federation: invalid content found in contact: ' . $e->getMessage());
                    continue;
                }
            }
        }

        $email = $globalConfig->getOptionalString('technicalcontact_email', 'na@example.org');
        if (!empty($email) && $email !== 'na@example.org') {
            $contact = [
                'EmailAddress' => [$email],
                'GivenName' => $globalConfig->getOptionalString('technicalcontact_name', null),
                'ContactType' => 'technical',
            ];

            try {
                $metadata['contacts'][] = ContactPerson::fromArray($contact)->toArray();
            } catch (ArrayValidationException $e) {
                Logger::warning('Federation: invalid content found in contact: ' . $e->getMessage());
            }
        }

        return $metadata;
    }


    /**
     * Helper function for encoding attributes.
     *
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the IdP.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the SP.
     * @param array $attributes The attributes of the user.
     *
     * @return array  The encoded attributes.
     *
     * @throws \SimpleSAML\Error\Exception In case an unsupported encoding is specified by configuration.
     */
    private static function encodeAttributes(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        array $attributes,
    ): array {
        $defaultEncoding = 'string';

        $srcEncodings = $idpMetadata->getOptionalArray('attributeencodings', []);
        $dstEncodings = $spMetadata->getOptionalArray('attributeencodings', []);

        /*
         * Merge the two encoding arrays. Encodings specified in the target metadata
         * takes precedence over the source metadata.
         */
        $encodings = array_merge($srcEncodings, $dstEncodings);

        $ret = [];
        foreach ($attributes as $name => $values) {
            $ret[$name] = [];
            if (array_key_exists($name, $encodings)) {
                $encoding = $encodings[$name];
            } else {
                $encoding = $defaultEncoding;
            }

            foreach ($values as $value) {
                // allow null values
                if ($value === null) {
                    $ret[$name][] = $value;
                    continue;
                }

                $attrval = $value;
                if ($value instanceof DOMNodeList) {
                    /** @psalm-suppress PossiblyNullPropertyFetch */
                    $attrval = new AttributeValue($value->item(0)->parentNode);
                }

                switch ($encoding) {
                    case 'string':
                        $value = (string) $attrval;
                        break;
                    case 'base64':
                        $value = base64_encode((string) $attrval);
                        break;
                    case 'raw':
                        if (is_string($value)) {
                            $doc = DOMDocumentFactory::fromString('<root>' . $value . '</root>');
                            /** @psalm-suppress PossiblyNullPropertyFetch */
                            $value = $doc->firstChild->childNodes;
                        }
                        Assert::isInstanceOfAny($value, [DOMNodeList::class, NameID::class]);
                        break;
                    default:
                        throw new Error\Exception('Invalid encoding for attribute ' .
                            var_export($name, true) . ': ' . var_export($encoding, true));
                }
                $ret[$name][] = $value;
            }
        }

        return $ret;
    }


    /**
     * Determine which NameFormat we should use for attributes.
     *
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the IdP.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the SP.
     *
     * @return string  The NameFormat.
     */
    private static function getAttributeNameFormat(
        Configuration $idpMetadata,
        Configuration $spMetadata,
    ): string {
        // try SP metadata first
        $attributeNameFormat = $spMetadata->getOptionalString('attributes.NameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }
        $attributeNameFormat = $spMetadata->getOptionalString('AttributeNameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }

        // look in IdP metadata
        $attributeNameFormat = $idpMetadata->getOptionalString('attributes.NameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }
        $attributeNameFormat = $idpMetadata->getOptionalString('AttributeNameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }

        // default
        return C::NAMEFORMAT_URI;
    }


    /**
     * Build an assertion based on information in the metadata.
     *
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the IdP.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the SP.
     * @param array &$state The state array with information about the request.
     *
     * @return \SimpleSAML\SAML2\Assertion  The assertion.
     *
     * @throws \SimpleSAML\Error\Exception In case an error occurs when creating a holder-of-key assertion.
     */
    private static function buildAssertion(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        array &$state,
    ): Assertion {
        Assert::notNull($state['Attributes']);
        Assert::notNull($state['saml:ConsumerURL']);

        $httpUtils = new Utils\HTTP();

        $signAssertion = $spMetadata->getOptionalBoolean('saml20.sign.assertion', null);
        if ($signAssertion === null) {
            $signAssertion = $idpMetadata->getOptionalBoolean('saml20.sign.assertion', true);
        }

        $config = Configuration::getInstance();

        $issuer = new Issuer(
            value: $idpMetadata->getString('entityid'),
            Format: C::NAMEID_ENTITY,
        );

        $nameId = self::generateNameId($idpMetadata, $spMetadata, $state);
        $state['saml:idp:NameID'] = $nameId;
        $subject = new Subject($nameId);

        $a = new Assertion($issuer, new \DateTimeImmutable('now', new \DateTimeZone('Z')), null, $subject);
        if ($signAssertion) {
            Message::addSign($idpMetadata, $spMetadata, $a);
        }

        $audiences = array_merge([$spMetadata->getString('entityid')], $spMetadata->getOptionalArray('audience', []));
        $audiences = array_map(fn($audience): Audience => new Audience($audience), $audiences);
        $a->setValidAudiences($audiences);

        $issuer = new Issuer();
        $issuer->setValue($state['IdPMetadata']['entityid']);
        $issuer->setFormat(Constants::NAMEID_ENTITY);
        $a->setIssuer($issuer);

        $a->setNotBefore(time() - 30);

        $assertionLifetime = $spMetadata->getOptionalInteger('assertion.lifetime', null);
        if ($assertionLifetime === null) {
            $assertionLifetime = $idpMetadata->getOptionalInteger('assertion.lifetime', 300);
        }
        $a->setNotOnOrAfter(time() + $assertionLifetime);

        $passAuthnContextClassRef = $config->getOptionalBoolean('proxymode.passAuthnContextClassRef', false);
        if (isset($state['saml:AuthnContextClassRef'])) {
            $classRef = $state['saml:AuthnContextClassRef'];
        } elseif ($passAuthnContextClassRef && isset($state['saml:sp:AuthnContext'])) {
            // AuthnContext has been set by the upper IdP in front of the proxy, pass it back to the SP behind the proxy
            $classRef = $state['saml:sp:AuthnContext'];
        } else {
            $classRef = $httpUtils->isHTTPS() ? C::AC_PASSWORD_PROTECTED_TRANSPORT : C::AC_PASSWORD;
        }

        $authorities = [];
        if (isset($state['saml:AuthenticatingAuthority'])) {
            $authorities[] = new AuthenticatingAuthority($state['saml:AuthenticatingAuthority']);
        }

        $a->setAuthnContext(
            new AuthnContext(
                authnContextClassRef: new AuthnContextClassRef($classRef),
                authnContextDecl: null,
                authnContextDeclRef: null,
                authenticatingAuthorities: $authorities,
            ),
        );

        $systemClock = LocalizedClock::in(new DateTimeZone('Z'));
        $now = $systemClock->now();

        $sessionStart = $now;
        if (isset($state['AuthnInstant'])) {
            $a->setAuthnInstant($state['AuthnInstant']);
            $sessionStart = $state['AuthnInstant'];
        }

        $sessionLifetime = $config->getOptionalInteger('session.duration', 8 * 60 * 60);
        $a->setSessionNotOnOrAfter($sessionStart + $sessionLifetime);

        $randomUtils = new Utils\Random();
        $a->setSessionIndex($randomUtils->generateID());

        // ProtcolBinding of SP's <AuthnRequest> overwrites IdP hosted metadata configuration
        $hokAssertion = null;
        if ($state['saml:Binding'] === C::BINDING_HOK_SSO) {
            $hokAssertion = true;
        }
        if ($hokAssertion === null) {
            $hokAssertion = $idpMetadata->getOptionalBoolean('saml20.hok.assertion', false);
        }

        $children = [];
        if ($hokAssertion) {
            // Holder-of-Key
            $method = C::CM_HOK;

            if ($httpUtils->isHTTPS()) {
                if (isset($_SERVER['SSL_CLIENT_CERT']) && !empty($_SERVER['SSL_CLIENT_CERT'])) {
                    // extract certificate data (if this is a certificate)
                    $clientCert = $_SERVER['SSL_CLIENT_CERT'];
                    $pattern = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
                    if (preg_match($pattern, $clientCert, $matches)) {
                        // we have a client certificate from the browser which we add to the HoK assertion
                        $x509Certificate = new X509Certificate(
                            str_replace(["\r", "\n", " "], '', $matches[1]),
                        );

                        $x509Data = new X509Data([$x509Certificate]);
                        $children[] = new KeyInfo([$x509Data]);
                    } else {
                        throw new Error\Exception(
                            'Error creating HoK assertion: No valid client certificate provided during '
                            . 'TLS handshake with IdP',
                        );
                    }
                } else {
                    throw new Error\Exception(
                        'Error creating HoK assertion: No client certificate provided during TLS handshake with IdP',
                    );
                }
            } else {
                throw new Error\Exception(
                    'Error creating HoK assertion: No HTTPS connection to IdP, but required for Holder-of-Key SSO',
                );
            }
        } else {
            // Bearer
            $method = C::CM_BEARER;
        }

        $scd = new SubjectConfirmationData(
            notBefore: $now,
            notOnOrAfter: $now->add(new DateInterval(sprintf('PT%dS', $assertionLifetime))),
            recipient: $state['saml:ConsumerURL'],
            inResponseTo: $state['saml:RequestId'],
            children: $children,
        );

        $sc = new SubjectConfirmation(
            method: $method,
            subjectConfirmationData: $scd,
        );
        $a->setSubjectConfirmation([$sc]);

        // add attributes
        if ($spMetadata->getOptionalBoolean('simplesaml.attributes', true)) {
            $attributeNameFormat = self::getAttributeNameFormat($idpMetadata, $spMetadata);
            $a->setAttributeNameFormat($attributeNameFormat);
            $attributes = self::encodeAttributes($idpMetadata, $spMetadata, $state['Attributes']);
            $a->setAttributes($attributes);
        }

        $encryptNameId = $spMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($encryptNameId === null) {
            $encryptNameId = $idpMetadata->getOptionalBoolean('nameid.encryption', false);
        }
        if ($encryptNameId) {
            $a->encryptNameId(Message::getEncryptionKey($spMetadata));
        }

        return $a;
    }

    /**
     * Helper for buildAssertion to decide on an NameID to set
     */
    private static function generateNameId(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        array $state,
    ): NameID {
        Logger::debug('Determining value for NameID');
        $nameIdFormat = null;

        if (isset($state['saml:NameIDFormat'])) {
            $nameIdFormat = $state['saml:NameIDFormat'];
        }

        if ($nameIdFormat === null || !isset($state['saml:NameID'][$nameIdFormat])) {
            // either not set in request, or not set to a format we supply. Fall back to old generation method
            $nameIdFormat = current($spMetadata->getOptionalArrayizeString('NameIDFormat', []));
            if ($nameIdFormat === false) {
                $nameIdFormat = current(
                    $idpMetadata->getOptionalArrayizeString('NameIDFormat', [C::NAMEID_TRANSIENT]),
                );
            }
        }

        if (isset($state['saml:NameID'][$nameIdFormat])) {
            Logger::debug(sprintf('NameID of desired format %s found in state', var_export($nameIdFormat, true)));
            return $state['saml:NameID'][$nameIdFormat];
        }

        // We have nothing else to work with, so default to transient
        if ($nameIdFormat !== C::NAMEID_TRANSIENT) {
            Logger::notice(sprintf(
                'Requested NameID of format %s, but can only provide transient',
                var_export($nameIdFormat, true),
            ));
            $nameIdFormat = C::NAMEID_TRANSIENT;
        }

        $randomUtils = new Utils\Random();
        $nameIdValue = $randomUtils->generateID();

        $spNameQualifier = $spMetadata->getOptionalString('SPNameQualifier', null);
        if ($spNameQualifier === null) {
            $spNameQualifier = $spMetadata->getString('entityid');
        }

        Logger::info(sprintf(
            'Setting NameID to (%s, %s, %s)',
            var_export($nameIdFormat, true),
            var_export($nameIdValue, true),
            var_export($spNameQualifier, true),
        ));
        $nameId = new NameID(
            value: $nameIdValue,
            Format: $nameIdFormat,
            SPNameQualifier: $spNameQualifier,
        );

        return $nameId;
    }

    /**
     * Encrypt an assertion.
     *
     * This function takes in a \SimpleSAML\SAML2\Assertion and encrypts it if encryption of
     * assertions are enabled in the metadata.
     *
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the IdP.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the SP.
     * @param \SimpleSAML\SAML2\Assertion $assertion The assertion we are encrypting.
     *
     * @return \SimpleSAML\SAML2\Assertion|\SimpleSAML\SAML2\EncryptedAssertion  The assertion.
     *
     * @throws \SimpleSAML\Error\Exception In case the encryption key type is not supported.
     */
    private static function encryptAssertion(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        Assertion $assertion,
    ): Assertion|EncryptedAssertion {
        $encryptAssertion = $spMetadata->getOptionalBoolean('assertion.encryption', null);
        if ($encryptAssertion === null) {
            $encryptAssertion = $idpMetadata->getOptionalBoolean('assertion.encryption', false);
        }
        if (!$encryptAssertion) {
            // we are _not_ encrypting this assertion, and are therefore done
            return $assertion;
        }


        $sharedKey = $spMetadata->getOptionalString('sharedkey', null);
        if ($sharedKey !== null) {
            $algo = $spMetadata->getOptionalString('sharedkey_algorithm', null);
            if ($algo === null) {
                // If no algorithm is configured, use a sane default
                $algo = $idpMetadata->getOptionalString('sharedkey_algorithm', XMLSecurityKey::AES128_GCM);
            }

            $key = new XMLSecurityKey($algo);
            $key->loadKey($sharedKey);
        } else {
            $keys = $spMetadata->getPublicKeys('encryption');
            if (!empty($keys)) {
                $key = $keys[0];
                switch ($key['type']) {
                    case 'X509Certificate':
                        $pemKey = "-----BEGIN CERTIFICATE-----\n" .
                            chunk_split($key['X509Certificate'], 64) .
                            "-----END CERTIFICATE-----\n";
                        break;
                    default:
                        throw new Error\Exception('Unsupported encryption key type: ' . $key['type']);
                }

                // extract the public key from the certificate for encryption
                $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
                $key->loadKey($pemKey);
            } elseif ($idpMetadata->getOptionalBoolean('encryption.optional', false) === true) {
                return $assertion;
            } else {
                throw new Error\ConfigurationError(
                    'Missing encryption key for entity `' . $spMetadata->getString('entityid') . '`',
                    $spMetadata->getString('metadata-set') . '.php',
                    null,
                );
            }
        }

        $ea = new EncryptedAssertion();
        $ea->setAssertion($assertion, $key);
        return $ea;
    }


    /**
     * Build a logout request based on information in the metadata.
     *
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the IdP.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the SP.
     * @param array $association The SP association.
     * @param string|null $relayState An id that should be carried across the logout.
     *
     * @return \SimpleSAML\SAML2\LogoutRequest The corresponding SAML2 logout request.
     */
    private static function buildLogoutRequest(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        array $association,
        ?string $relayState = null,
    ): LogoutRequest {
        $lr = Message::buildLogoutRequest($idpMetadata, $spMetadata);
        $lr->setRelayState($relayState);
        $lr->setSessionIndex($association['saml:SessionIndex']);
        $lr->setNameId($association['saml:NameID']);

        $assertionLifetime = $spMetadata->getOptionalInteger('assertion.lifetime', null);
        if ($assertionLifetime === null) {
            $assertionLifetime = $idpMetadata->getOptionalInteger('assertion.lifetime', 300);
        }
        $lr->setNotOnOrAfter(time() + $assertionLifetime);

        $encryptNameId = $spMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($encryptNameId === null) {
            $encryptNameId = $idpMetadata->getOptionalBoolean('nameid.encryption', false);
        }
        if ($encryptNameId) {
            $lr->encryptNameId(Message::getEncryptionKey($spMetadata));
        }

        return $lr;
    }


    /**
     * Build a authentication response based on information in the metadata.
     *
     * @param \SimpleSAML\Configuration $idpMetadata The metadata of the IdP.
     * @param \SimpleSAML\Configuration $spMetadata The metadata of the SP.
     * @param string                    $consumerURL The Destination URL of the response.
     *
     * @return \SimpleSAML\SAML2\Response The SAML2 Response corresponding to the given data.
     */
    private static function buildResponse(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        string $consumerURL,
    ): SAML2_Response {
        $signResponse = $spMetadata->getOptionalBoolean('saml20.sign.response', null);
        if ($signResponse === null) {
            $signResponse = $idpMetadata->getOptionalBoolean('saml20.sign.response', true);
        }

        $status = new Status(new StatusCode(C::STATUS_SUCCESS));
        $issuer = new Issuer(
            value: $idpMetadata->getString('entityid'),
            Format: C::NAMEID_ENTITY,
        );
        $r = new SAML2_Response(
            $status,
            new \DateTimeImmutable('now', new \DateTimeZone('Z')),
            $issuer,
            null,
            '2.0',
            null,
            $consumerURL,
        );

        if ($signResponse) {
            Message::addSign($idpMetadata, $spMetadata, $r);
        }

        return $r;
    }
}
