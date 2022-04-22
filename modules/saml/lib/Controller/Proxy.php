<?php

declare(strict_types=1);

namespace SimpleSAML\Module\saml\Controller;

use Exception;
use SAML2\Constants;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\IdP;
use SimpleSAML\Module\saml\Auth\Source\SP;
use SimpleSAML\Module\saml\Error\NoAvailableIDP;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Controller class for the saml module.
 *
 * This class serves the different views available in the module.
 *
 * @package simplesamlphp/simplesamlphp
 */
class Proxy
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

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
     */
    public function __construct(
        Configuration $config
    ) {
        $this->config = $config;
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
     * This controller will handle the case of a user with an existing session that's not valid for a specific
     * Service Provider, since the authenticating IdP is not in the list of IdPs allowed by the SP.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\Response
     */
    public function invalidSession(Request $request): Response
    {
        // retrieve the authentication state
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing mandatory parameter: AuthState');
        }
        $stateId = $request->query->get('AuthState');

        try {
            // try to get the state
            $state = $this->authState::loadState($stateId, 'saml:proxy:invalid_idp');
        } catch (Exception $e) {
            // the user probably hit the back button after starting the logout,
            // try to recover the state with another stage
            $state = $this->authState::loadState($stateId, 'core:Logout:afterbridge');

            // success! Try to continue with reauthentication, since we no longer have a valid session here
            $idp = IdP::getById($state['core:IdP']);
            return new RunnableResponse([SP::class, 'reauthPostLogout'], [$idp, $state]);
        }

        if ($request->request->has('cancel')) {
            // the user does not want to logout, cancel login
            $this->authState::throwException(
                $state,
                new NoAvailableIDP(
                    Constants::STATUS_RESPONDER,
                    'User refused to reauthenticate with any of the IdPs requested.'
                )
            );
        }

        if ($request->request->has('continue')) {
            /** @var \SimpleSAML\Module\saml\Auth\Source\SP $as */
            $as = Auth\Source::getById($state['saml:sp:AuthId'], SP::class);

            // log the user out before being able to login again
            return new RunnableResponse([$as, 'reauthLogout'], [$state]);
        }

        $template = new Template($this->config, 'saml:proxy/invalid_session.twig');
        $template->data['AuthState'] = $stateId;

        /** @var \SimpleSAML\Configuration $idpmdcfg */
        $idpmdcfg = $state['saml:sp:IdPMetadata'];

        $template->data['entity_idp'] = $idpmdcfg->toArray();
        $template->data['entity_sp'] = $state['SPMetadata'];

        return $template;
    }
}
