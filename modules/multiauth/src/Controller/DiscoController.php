<?php

declare(strict_types=1);

namespace SimpleSAML\Module\multiauth\Controller;

use SimpleSAML\{Auth, Configuration, Error, Logger, Module, Session, Utils};
use SimpleSAML\Module\multiauth\Auth\Source\MultiAuth;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};

use function array_key_exists;
use function is_null;

/**
 * Controller class for the multiauth module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\multiauth
 */
class DiscoController
{
    /**
     * @var \SimpleSAML\Auth\Source|string
     * @psalm-var \SimpleSAML\Auth\Source|class-string
     */
    protected $authSource = Auth\Source::class;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and auth source configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration              $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session                    $session The session to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
    }


    /**
     * Inject the \SimpleSAML\Auth\Source dependency.
     *
     * @param \SimpleSAML\Auth\Source $authSource
     */
    public function setAuthSource(Auth\Source $authSource): void
    {
        $this->authSource = $authSource;
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
     * This controller shows a list of authentication sources. When the user selects
     * one of them if pass this information to the
     * \SimpleSAML\Module\multiauth\Auth\Source\MultiAuth class and call the
     * delegateAuthentication method on it.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template|\Symfony\Component\HttpFoundation\Response
     *   An HTML template or a redirection if we are not authenticated.
     */
    public function discovery(Request $request): Template|Response
    {
        // Retrieve the authentication state
        $authStateId = $request->query->get('AuthState', null);
        if (is_null($authStateId)) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }

        $state = $this->authState::loadState($authStateId, MultiAuth::STAGEID);

        $as = null;
        if (array_key_exists("\SimpleSAML\Auth\Source.id", $state)) {
            $authId = $state["\SimpleSAML\Auth\Source.id"];

            /** @var \SimpleSAML\Module\multiauth\Auth\Source\MultiAuth $as */
            $as = Auth\Source::getById($authId);
        }

        // Get a preselected source either from the URL or the discovery page
        $urlSource = $request->query->get('source', null);
        $discoSource = $request->query->get('sourceChoice', null);

        if ($urlSource !== null) {
            $selectedSource = $urlSource;
        } elseif ($discoSource !== null) {
            $selectedSource = array_key_first($discoSource);
        }

        if (isset($selectedSource)) {
            if ($as !== null) {
                $as->setPreviousSource($selectedSource);
            }
            return MultiAuth::delegateAuthentication($selectedSource, $state);
        }

        if (array_key_exists('multiauth:preselect', $state)) {
            $source = $state['multiauth:preselect'];
            return MultiAuth::delegateAuthentication($source, $state);
        }

        $t = new Template($this->config, 'multiauth:selectsource.twig');

        $t->data['authstate'] = $authStateId;
        $t->data['sources'] = $state[MultiAuth::SOURCESID];
        $t->data['preferred'] = is_null($as) ? null : $as->getPreviousSource();
        return $t;
    }
}
