<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use SAML2\Assertion;
use SAML2\Binding;
use SAML2\Response;
use SAML2\Exception\Protocol\UnsupportedBindingException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Http\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\saml\Auth\Source\SP;
use SimpleSAML\Session;
use SimpleSAML\Store\StoreFactory;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

use function array_merge;
use function end;
use function in_array;
use function is_null;
use function substr;
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
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session $session The Session to use by the controllers.
     */
    public function __construct(
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
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
     * Handler for response from IdP discovery service.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\Http\RunnableResponse
     */
    public function discoResponse(Request $request): RunnableResponse
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

        return new RunnableResponse([$source, 'startSSO'], [$idpEntityId, $state]);
    }


    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template
     */
    public function wrongAuthnContextClassRef(Request $request): Template
    {
        return new Template($this->config, 'saml:sp/wrong_authncontextclassref.twig');
    }


    /**
     * Handler for the Assertion Consumer Service.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $sourceId
     * @return \SimpleSAML\Http\RunnableResponse
     */
    public function assertionConsumerService(Request $request, string $sourceId): RunnableResponse
    {
        /** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
        $source = Auth\Source::getById($sourceId, SP::class);

        $spMetadata = $source->getMetadata();
        try {
            $b = Binding::getCurrentBinding();
        } catch (UnsupportedBindingException $e) {
            throw new Error\Error('ACSPARAMS', $e, 400);
        }

        if ($b instanceof HTTPArtifact) {
            $b->setSPMetadata($spMetadata);
        }

        $response = $b->receive();
        if (!($response instanceof Response)) {
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
        $issuer = $issuer->getValue();

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
            Logger::info(
                'Duplicate SAML 2 response detected - ignoring the response and redirecting the user to the correct page.'
            );
            if (isset($prevAuth['redirect'])) {
                return RunnableResponse([$httpUtils, 'redirectTrustedURL'], [$prevAuth['redirect']]);
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
                    'The authentication source id in the URL does not match the authentication source which sent the request.'
                );
            }

            // check that the issuer is the one we are expecting
            Assert::keyExists($state, 'ExpectedIssuer');
            if ($state['ExpectedIssuer'] !== $issuer) {
                $idpMetadata = $source->getIdPMetadata($issuer);
                $idplist = $idpMetadata->getOptionalArrayize('IDPList', []);
                if (!in_array($state['ExpectedIssuer'], $idplist, true)) {
                    Logger::warning(
                        'The issuer of the response not match to the identity provider we sent the request to.'
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
            $idpMetadata = $source->getIdPmetadata($issuer);
        }

        try {
            $assertions = Module\saml\Message::processResponse($spMetadata, $idpMetadata, $response);
        } catch (Module\saml\Error $e) {
            // the status of the response wasn't "success"
            $e = $e->toException();
            $this->authState::throwException($state, $e);
            Assert::true(false);
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
                $authenticatingAuthority = $assertion->getAuthenticatingAuthority();
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
             * Even though interoperability profiles "require" a NameID, the SAML 2.0 standard does not require it to be present
             * in assertions. That way, we could have a Subject with only a SubjectConfirmation, or even no Subject element at
             * all.
             *
             * In case we receive a SAML assertion with no NameID, we can be graceful and continue, but we won't be able to
             * perform a Single Logout since the SAML logout profile mandates the use of a NameID to identify the individual we
             * want to be logged out. In order to minimize the impact of this, we keep logout state information (without saving
             * it to the store), marking the IdP as SAML 1.0, which does not implement logout. Then we can safely log the user
             * out from the local session, skipping Single Logout upstream to the IdP.
             */
            $logoutState = [
                'saml:logout:Type'         => 'saml1',
            ];
        }

        $state['LogoutState'] = $logoutState;
        $state['saml:AuthenticatingAuthority'] = $authenticatingAuthority;
        $state['saml:AuthenticatingAuthority'][] = $issuer;
        $state['PersistentAuthData'][] = 'saml:AuthenticatingAuthority';
        $state['saml:AuthnInstant'] = $assertion->getAuthnInstant();
        $state['PersistentAuthData'][] = 'saml:AuthnInstant';
        $state['saml:sp:SessionIndex'] = $sessionIndex;
        $state['PersistentAuthData'][] = 'saml:sp:SessionIndex';
        $state['saml:sp:AuthnContext'] = $assertion->getAuthnContextClassRef();
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

        return new RunnableResponse([$source, 'handleResponse'], [$state, $issuer, $attributes]);
    }
}
