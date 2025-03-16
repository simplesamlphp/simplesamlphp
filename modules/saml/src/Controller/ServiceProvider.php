<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use SimpleSAML\{Auth, Configuration, Error, Logger, Metadata, Module, Session, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\Module\saml\Auth\Source\SP;
use SimpleSAML\SAML2\{Assertion, Binding, HTTPArtifact, HTTPRedirect, LogoutRequest, LogoutResponse, SOAP};
use SimpleSAML\SAML2\Constants as C;
use SimpleSAML\SAML2\Exception\Protocol\UnsupportedBindingException;
use SimpleSAML\SAML2\XML\samlp\Response as SAML2_Response;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\Store\StoreFactory;
use SimpleSAML\XHTML\Template;
use Symfony\Bridge\PsrHttpMessage\Factory\{HttpFoundationFactory, PsrHttpFactory};
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};

use function array_merge;
use function basename;
use function count;
use function end;
use function get_class;
use function hash;
use function in_array;
use function is_null;
use function sprintf;
use function time;
use function var_export;

/**
 * Controller class for the saml module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class ServiceProvider
{
    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param   Configuration  $config   The configuration to use by the controllers.
     * @param   Session        $session  The Session to use by the controllers.
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
        $this->authUtils = new Utils\Auth();
    }


    /**
     * Inject the \SimpleSAML\Auth\State dependency.
     *
     * @param \SimpleSAML\Auth\State $authState
     */
    public function setAuthState(Auth\State $authState): void
    {
        $this->authState = $authState;
    }


    /**
     * Inject the \SimpleSAML\Utils\Auth dependency.
     *
     * @param \SimpleSAML\Utils\Auth $authUtils
     */
    public function setAuthUtils(Utils\Auth $authUtils): void
    {
        $this->authUtils = $authUtils;
    }


    /**
     * Start single sign-on for an SP identified with the specified Authsource ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $sourceId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws Error\Exception
     */
    public function login(Request $request, string $sourceId): RedirectResponse
    {
        // Initialize all the dependencies
        $authSource = new Auth\Simple($sourceId);
        $spSource = $authSource->getAuthSource();

        if (!($spSource instanceof SP)) {
            throw new Error\Exception('Authsource must be of type saml:SP.');
        }

        $httpUtils = new Utils\HTTP();

        // Redirect to the returnTo destination
        return $this->loginHandler($request, $authSource, $spSource, $httpUtils);
    }

    /**
     * @param   Request       $request
     * @param   Auth\Simple   $authSource
     * @param   Auth\Source   $spSource
     * @param   Utils\HTTP    $httpUtils
     *
     * @return string
     * @throws BadRequest
     * @throws Error\Exception
     */
    protected function loginHandler(
        Request $request,
        Auth\Simple $authSource,
        Auth\Source $spSource,
        Utils\HTTP $httpUtils,
    ): RedirectResponse {
        $options = [];

        if ($spSource->isRequestInitiation()) {
            if ($request->query->has('target')) {
                $options['ReturnTo'] = $httpUtils->checkURLAllowed($request->query->get('target'));
            }
            if ($request->query->has('forceAuthn')) {
                $options['ForceAuthn'] = $request->query->getBoolean('forceAuthn');
            }
            if ($request->query->has('entityID')) {
                $options['saml:idp'] = $request->query->get('entityID');
            }
            if ($request->query->has('isPassive')) {
                $options['isPassive'] = $request->query->getBoolean('isPassive');
            }
        }

        if (
            !isset($options['ReturnTo'])
            && !$request->query->has('ReturnTo')
            && !$spSource->getMetadata()->hasValue('RelayState')
        ) {
            throw new Error\BadRequest('Missing ReturnTo parameter.');
        }
        if (
            $request->query->has('ReturnTo') &&
            $request->query->getString('ReturnTo') === ""
        ) {
            throw new Error\BadRequest('Empty ReturnTo parameter specified.');
        }

        if (!isset($options['ReturnTo'])) {
            $options['ReturnTo'] = $httpUtils->checkURLAllowed(
                $request->query->get('ReturnTo') ?? $spSource->getMetadata()->getString('RelayState'),
            );
        }

        $authData = $authSource->getAuthDataArray();

        if (
            $authSource->isAuthenticated()
            && $spSource->isRequestInitiation()
        ) {
            if (
                // Check the IdP we are currently authenticated to
                (isset($authData['saml:sp:IdP'], $options['saml:idp'])
                 && $options['saml:idp'] !== $authData['saml:sp:IdP'])
                ||
                (isset($options['ForceAuthn']) && $options['ForceAuthn'])
            ) {
                // Force a re-authentication
                $authSource->login($options);
            }
            // We are already authenticated, do nothing
        }

        /**
         * Try to authenticate
         */
        $response = $authSource->requireAuth($options);
        if ($response === null) {
            // We are already authenticated
            return $httpUtils->redirectTrustedURL($options['ReturnTo']);
        }

        return $response;
    }


    /**
     * Handler for response from IdP discovery service.
     *
     * @param \Symfony\Component\HttpFoundation\Request|null $request
     */
    public function discoResponse(Request $request): ?Response
    {
        if (!$request->query->has('AuthID')) {
            throw new Error\BadRequest('Missing AuthID to discovery service response handler');
        }
        $authId = $request->query->get('AuthID');

        if (!$request->query->has('idpentityid')) {
            throw new Error\BadRequest('Missing idpentityid to discovery service response handler');
        }
        $idpEntityId = $request->query->get('idpentityid');

        $state = $this->authState::loadState($authId, 'saml:sp:sso');

        // Find authentication source
        Assert::keyExists($state, 'saml:sp:AuthId');
        $sourceId = $state['saml:sp:AuthId'];

        $source = Auth\Source::getById($sourceId);
        if ($source === null) {
            throw new Exception('Could not find authentication source with id ' . $sourceId);
        }

        if (!($source instanceof SP)) {
            throw new Error\Exception('Source type changed?');
        }

        return $source->startSSO($this->config, $idpEntityId, $state);
    }


    /**
     * @return \SimpleSAML\XHTML\Template
     */
    public function wrongAuthnContextClassRef(): Template
    {
        return new Template($this->config, 'saml:sp/wrong_authncontextclassref.twig');
    }


    /**
     * Handler for the Assertion Consumer Service.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $sourceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function assertionConsumerService(Request $request, string $sourceId): Response
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
        $source = Auth\Source::getById($sourceId, SP::class);

        $spMetadata = $source->getMetadata();
        try {
            $b = Binding::getCurrentBinding($psrRequest);
        } catch (UnsupportedBindingException $e) {
            throw new Error\Error(Error\ErrorCodes::ACSPARAMS, $e, 400);
        }

        if ($b instanceof HTTPArtifact) {
            $b->setSPMetadata($spMetadata);
        }

        $response = $b->receive($psrRequest);
        if (!($response instanceof SAML2_Response)) {
            throw new Error\BadRequest('Invalid message received at AssertionConsumerService endpoint.');
        }

        $issuer = $response->getIssuer();
        if ($issuer === null) {
            // no Issuer in the response. Look for an unencrypted assertion with an issuer
            foreach ($response->getAssertions() as $a) {
                if ($a instanceof Assertion) {
                    // we found an unencrypted assertion, there should be an issuer here
                    $issuer = $a->getIssuer();
                    break;
                }
            }
            if ($issuer === null) {
                // no issuer found in the assertions
                throw new Exception('Missing <saml:Issuer> in message delivered to AssertionConsumerService.');
            }
        }
        $issuer = $issuer->getContent();

        $prevAuth = $this->session->getAuthData($sourceId, 'saml:sp:prevAuth');

        $httpUtils = new Utils\HTTP();
        if ($prevAuth !== null && $prevAuth['id'] === $response->getId() && $prevAuth['issuer'] === $issuer) {
            /**
             * OK, it looks like this message has the same issuer
             * and ID as the SP session we already have active. We
             * therefore assume that the user has somehow triggered
             * a resend of the message.
             * In that case we may as well just redo the previous redirect
             * instead of displaying a confusing error message.
             */
            Logger::info(sprintf(
                '%s - %s',
                'Duplicate SAML 2 response detected',
                'ignoring the response and redirecting the user to the correct page.',
            ));
            if (isset($prevAuth['redirect'])) {
                return $httpUtils->redirectTrustedURL($prevAuth['redirect']);
            }

            Logger::info('No RelayState or ReturnURL available, cannot redirect.');
            throw new Error\Exception('Duplicate assertion received.');
        }

        $idpMetadata = null;
        $state = null;
        $stateId = $response->getInResponseTo();

        if (!empty($stateId)) {
            // this should be a response to a request we sent earlier
            try {
                $state = $this->authState::loadState($stateId, 'saml:sp:sso');
            } catch (Exception $e) {
                // something went wrong,
                Logger::warning(sprintf(
                    'Could not load state specified by InResponseTo: %s Processing response as unsolicited.',
                    $e->getMessage(),
                ));
            }
        }

        $enableUnsolicited = $spMetadata->getOptionalBoolean('enable_unsolicited', true);
        if ($state === null && $enableUnsolicited === false) {
            throw new Error\BadRequest('Unsolicited responses are denied by configuration.');
        }

        if ($state) {
            // check that the authentication source is correct
            Assert::keyExists($state, 'saml:sp:AuthId');
            if ($state['saml:sp:AuthId'] !== $sourceId) {
                throw new Error\Exception(
                    "The authentication source id in the URL doesn't match the authentication"
                    . " source that sent the request.",
                );
            }

            // check that the issuer is the one we are expecting
            Assert::keyExists($state, 'ExpectedIssuer');
            if ($state['ExpectedIssuer'] !== $issuer) {
                $idpMetadata = $source->getIdPMetadata($this->config, $issuer);
                $idplist = $idpMetadata->getOptionalArrayize('IDPList', []);
                if (!in_array($state['ExpectedIssuer'], $idplist, true)) {
                    Logger::warning(
                        'The issuer of the response not match to the identity provider we sent the request to.',
                    );
                }
            }
        } else {
            // this is an unsolicited response
            $relaystate = $spMetadata->getOptionalString('RelayState', $response->getRelayState());
            $state = [
                'saml:sp:isUnsolicited' => true,
                'saml:sp:AuthId'        => $sourceId,
                'saml:sp:RelayState'    => $relaystate === null ? null : $httpUtils->checkURLAllowed($relaystate),
            ];
        }

        Logger::debug('Received SAML2 Response from ' . var_export($issuer, true) . '.');

        if (is_null($idpMetadata)) {
            $idpMetadata = $source->getIdPmetadata($this->config, $issuer);
        }

        try {
            $assertions = Module\saml\Message::processResponse($spMetadata, $idpMetadata, $response);
        } catch (Module\saml\Error $e) {
            // the status of the response wasn't "success"
            $e = $e->toException();
            $this->authState::throwException($state, $e);
        }

        $authenticatingAuthority = null;
        $nameId = null;
        $sessionIndex = null;
        $expire = null;
        $attributes = [];
        $foundAuthnStatement = false;

        $storeType = $this->config->getOptionalString('store.type', 'phpsession');

        $store = StoreFactory::getInstance($storeType);

        foreach ($assertions as $assertion) {
            // check for duplicate assertion (replay attack)
            if ($store !== false) {
                $aID = $assertion->getId();
                if ($store->get('saml.AssertionReceived', $aID) !== null) {
                    $e = new Error\Exception('Received duplicate assertion.');
                    $this->authState::throwException($state, $e);
                }

                $notOnOrAfter = $assertion->getNotOnOrAfter();
                if ($notOnOrAfter === null) {
                    $notOnOrAfter = time() + 24 * 60 * 60;
                } else {
                    $notOnOrAfter += 60; // we allow 60 seconds clock skew, so add it here also
                }

                $store->set('saml.AssertionReceived', $aID, true, $notOnOrAfter);
            }

            if ($authenticatingAuthority === null) {
                $authenticatingAuthority = $assertion->getAuthnContext()?->getAuthenticatingAuthorities();
            }
            if ($nameId === null) {
                $nameId = $assertion->getNameId();
            }
            if ($sessionIndex === null) {
                $sessionIndex = $assertion->getSessionIndex();
            }
            if ($expire === null) {
                $expire = $assertion->getSessionNotOnOrAfter();
            }

            $attributes = array_merge($attributes, $assertion->getAttributes());

            if ($assertion->getAuthnInstant() !== null) {
                // assertion contains AuthnStatement, since AuthnInstant is a required attribute
                $foundAuthnStatement = true;
            }
        }
        $assertion = end($assertions);

        if (!$foundAuthnStatement) {
            $e = new Error\Exception('No AuthnStatement found in assertion(s).');
            $this->authState::throwException($state, $e);
        }

        if ($expire !== null) {
            $logoutExpire = $expire;
        } else {
            // just expire the logout association 24 hours into the future
            $logoutExpire = time() + 24 * 60 * 60;
        }

        if (!empty($nameId)) {
            // register this session in the logout store
            Module\saml\SP\LogoutStore::addSession($sourceId, $nameId, $sessionIndex, $logoutExpire);

            // we need to save the NameID and SessionIndex for logout
            $logoutState = [
                'saml:logout:Type'         => 'saml2',
                'saml:logout:IdP'          => $issuer,
                'saml:logout:NameID'       => $nameId,
                'saml:logout:SessionIndex' => $sessionIndex,
            ];

            $state['saml:sp:NameID'] = $nameId; // no need to mark it as persistent, it already is
        } else {
            /*
             * No NameID provided, we can't logout from this IdP!
             *
             * Even though interoperability profiles "require" a NameID, the SAML 2.0 standard does not require
             * it to be present in assertions. That way, we could have a Subject with only a SubjectConfirmation,
             * or even no Subject element at all.
             *
             * In case we receive a SAML assertion with no NameID, we can be graceful and continue, but we won't
             * be able to perform a Single Logout since the SAML logout profile mandates the use of a NameID to
             * identify the individual we want to be logged out. In order to minimize the impact of this, we keep
             * logout state information (without saving it to the store), marking the IdP as SAML 1.0, which
             * does not implement logout. Then we can safely log the user out from the local session, skipping
             * Single Logout upstream to the IdP.
             */
            $logoutState = [
                'saml:logout:Type'         => 'saml1',
            ];
        }

        $state['LogoutState'] = $logoutState;
        $state['saml:AuthenticatingAuthority'] = array_map(
            fn($authority): string => $authority->getContent(),
            $authenticatingAuthority,
        );
        $state['saml:AuthenticatingAuthority'][] = $issuer;
        $state['PersistentAuthData'][] = 'saml:AuthenticatingAuthority';
        $state['saml:AuthnInstant'] = $assertion->getAuthnInstant();
        $state['PersistentAuthData'][] = 'saml:AuthnInstant';
        $state['saml:sp:SessionIndex'] = $sessionIndex;
        $state['PersistentAuthData'][] = 'saml:sp:SessionIndex';
        $state['saml:sp:AuthnContext'] = $assertion->getAuthnContext()?->getAuthnContextClassRef();
        $state['PersistentAuthData'][] = 'saml:sp:AuthnContext';

        if ($expire !== null) {
            $state['Expire'] = $expire;
        }

        // note some information about the authentication, in case we receive the same response again
        $state['saml:sp:prevAuth'] = [
            'id'     => $response->getId(),
            'issuer' => $issuer,
            'inResponseTo' => $response->getInResponseTo(),
        ];
        if (isset($state['\SimpleSAML\Auth\Source.ReturnURL'])) {
            $state['saml:sp:prevAuth']['redirect'] = $state['\SimpleSAML\Auth\Source.ReturnURL'];
        } elseif (isset($state['saml:sp:RelayState'])) {
            $state['saml:sp:prevAuth']['redirect'] = $state['saml:sp:RelayState'];
        }
        $state['PersistentAuthData'][] = 'saml:sp:prevAuth';

        return $source->handleResponse($state, $issuer, $attributes);
    }


    /**
     * Logout endpoint handler for SAML SP authentication client.
     *
     * This endpoint handles both logout requests and logout responses.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $sourceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function singleLogoutService(Request $request, string $sourceId): Response
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $psrRequest = $psrHttpFactory->createRequest($request);

        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
        $source = Auth\Source::getById($sourceId);

        if ($source === null) {
            throw new Error\Exception('No authentication source with id \'' . $sourceId . '\' found.');
        } elseif (!($source instanceof Module\saml\Auth\Source\SP)) {
            throw new Error\Exception('Source type changed?');
        }

        try {
            $binding = Binding::getCurrentBinding($psrRequest);
        } catch (UnsupportedBindingException $e) {
            throw new Error\Error(Error\ErrorCodes::SLOSERVICEPARAMS, $e, 400);
        }
        $message = $binding->receive($psrRequest);

        $issuer = $message->getIssuer();
        if ($issuer instanceof Issuer) {
            $idpEntityId = $issuer->getContent();
        } else {
            $idpEntityId = $issuer;
        }

        if ($idpEntityId === null) {
            // Without an issuer we have no way to respond to the message.
            throw new Error\BadRequest('Received message on logout endpoint without issuer.');
        }

        $spEntityId = $source->getEntityId();

        $idpMetadata = $source->getIdPMetadata($this->config, $idpEntityId);
        $spMetadata = $source->getMetadata();

        Module\saml\Message::validateMessage($idpMetadata, $spMetadata, $message);

        $httpUtils = new Utils\HTTP();
        $destination = $message->getDestination();
        if ($destination !== null && $destination !== $httpUtils->getSelfURLNoQuery()) {
            throw new Error\Exception('Destination in logout message is wrong.');
        }

        if ($message instanceof LogoutResponse) {
            $relayState = $message->getRelayState();
            if ($relayState === null) {
                // Somehow, our RelayState has been lost.
                throw new Error\BadRequest('Missing RelayState in logout response.');
            }

            if (!$message->isSuccess()) {
                Logger::warning(
                    'Unsuccessful logout. Status was: ' . Module\saml\Message::getResponseError($message),
                );
            }

            $state = $this->authState::loadState($relayState, 'saml:slosent');
            $state['saml:sp:LogoutStatus'] = $message->getStatus();

            // Destroy session cookies.
            $this->session->updateSessionCookies(['expire' => true]);

            return $source::completeLogout($state);
        } elseif ($message instanceof LogoutRequest) {
            Logger::debug('module/saml2/sp/logout: Request from ' . $idpEntityId);
            Logger::stats('saml20-idp-SLO idpinit ' . $spEntityId . ' ' . $idpEntityId);

            if ($message->isNameIdEncrypted()) {
                try {
                    $keys = Module\saml\Message::getDecryptionKeys($idpMetadata, $spMetadata);
                } catch (Exception $e) {
                    throw new Error\Exception('Error decrypting NameID: ' . $e->getMessage());
                }

                $blacklist = Module\saml\Message::getBlacklistedAlgorithms($idpMetadata, $spMetadata);

                $lastException = null;
                foreach ($keys as $i => $key) {
                    try {
                        $message->decryptNameId($key, $blacklist);
                        Logger::debug('Decryption with key #' . $i . ' succeeded.');
                        $lastException = null;
                        break;
                    } catch (Exception $e) {
                        Logger::debug('Decryption with key #' . $i . ' failed with exception: ' . $e->getMessage());
                        $lastException = $e;
                    }
                }
                if ($lastException !== null) {
                    throw $lastException;
                }
            }

            $nameId = $message->getNameId();
            $sessionIndexes = $message->getSessionIndexes();

            /** @psalm-suppress PossiblyNullArgument  This will be fixed in saml2 5.0 */
            $numLoggedOut = Module\saml\SP\LogoutStore::logoutSessions($sourceId, $nameId, $sessionIndexes);
            if ($numLoggedOut === false) {
                // This type of logout was unsupported. Use the old method
                $response = $source->handleLogout($idpEntityId);
                Assert::null($response);
                $numLoggedOut = count($sessionIndexes);
            }

            // Create and send response
            $lr = Module\saml\Message::buildLogoutResponse($spMetadata, $idpMetadata);
            $lr->setRelayState($message->getRelayState());
            $lr->setInResponseTo($message->getId());

            if ($numLoggedOut < count($sessionIndexes)) {
                Logger::warning('Logged out of ' . $numLoggedOut . ' of ' . count($sessionIndexes) . ' sessions.');
            }

            $dst = $idpMetadata->getEndpointPrioritizedByBinding(
                'SingleLogoutService',
                [
                    C::BINDING_HTTP_REDIRECT,
                    C::BINDING_HTTP_POST,
                ],
            );

            $dst = $dst['Location'];
            if (!($binding instanceof SOAP)) {
                $binding = Binding::getBinding($dst['Binding']);
                if (isset($dst['ResponseLocation'])) {
                    $dst = $dst['ResponseLocation'];
                }
            }
            $lr->setDestination($dst);

            $psrResponse = $binding->send($lr);
            $httpFoundationFactory = new HttpFoundationFactory();
            return $httpFoundationFactory->createResponse($psrResponse);
        } else {
            throw new Error\BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
        }
    }


    /**
     * Metadata endpoint for SAML SP
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $sourceId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function metadata(Request $request, string $sourceId): Response
    {
        $protectedMetadata = $this->config->getOptionalBoolean('admin.protectmetadata', false);
        if ($protectedMetadata) {
            $response = $this->authUtils->requireAdmin();
            if ($response instanceof Response) {
                return $response;
            }
        }

        $source = Auth\Source::getById($sourceId);
        if ($source === null) {
            throw new Error\AuthSource($sourceId, 'Could not find authentication source.');
        }

        if (!($source instanceof SP)) {
            throw new Error\AuthSource(
                $sourceId,
                'The authentication source is not a SAML Service Provider.',
            );
        }

        $entityId = $source->getEntityId();
        $spconfig = $source->getMetadata();
        $metaArray20 = $source->getHostedMetadata();

        $metaBuilder = new Metadata\SAMLBuilder($entityId);
        $metaBuilder->addMetadataSP20($metaArray20, $source->getSupportedProtocols());
        $metaBuilder->addOrganizationInfo($metaArray20);

        $xml = $metaBuilder->getEntityDescriptorText();

        // sign the metadata if enabled
        $metaxml = Metadata\Signer::sign($xml, $spconfig->toArray(), 'SAML 2 SP');

        $response = new Response();
        $response->setEtag(hash('sha256', $metaxml));
        $response->setCache([
            'no_cache' => $protectedMetadata === true,
            'public' => $protectedMetadata === false,
            'private' => $protectedMetadata === true,
        ]);

        if ($response->isNotModified($request)) {
            return $response;
        }
        $response->headers->set('Content-Type', 'application/samlmetadata+xml');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($sourceId) . '.xml"');
        $response->setContent($metaxml);

        return $response;
    }
}
