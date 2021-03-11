<?php

namespace SimpleSAML\Module\multiauth\Controller;

use SimpleSAML\Auth;
use SimpleSAML\Auth\AuthenticationFactory;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\multiauth\Auth\Source\MultiAuth;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the multiauth module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\multiauth
 */
class DiscoController
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;

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
        Configuration $config,
        Session $session
    ) {
        $this->config = $config;
        $this->session = $session;
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
     * @return \SimpleSAML\XHTML\Template|\SimpleSAML\HTTP\RunnableResponse
     *   An HTML template or a redirection if we are not authenticated.
     */
    public function discovery(Request $request)
    {
        // Retrieve the authentication state
        $authStateId = $request->get('AuthState', null);
        if (is_null($authStateId)) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }

        /** @var array $state */
        $state = $this->authState::loadState($authStateId, MultiAuth::STAGEID);

        $as = null;
        if (array_key_exists("\SimpleSAML\Auth\Source.id", $state)) {
            $authId = $state["\SimpleSAML\Auth\Source.id"];

            /** @var \SimpleSAML\Module\multiauth\Auth\Source\MultiAuth $as */
            $as = Auth\Source::getById($authId);
        }

        $source = $request->get('source', null);

        if ($source !== null) {
            if ($as !== null) {
                $as->setPreviousSource($source);
            }
            return MultiAuth::delegateAuthentication($source, $state);
        }

        if (array_key_exists('multiauth:preselect', $state)) {
            $source = $state['multiauth:preselect'];
            return MultiAuth::delegateAuthentication($source, $state);
        }

        $t = new Template($this->config, 'multiauth:selectsource.twig');

        $defaultLanguage = $this->config->getString('language.default', 'en');
        $language = $t->getTranslator()->getLanguage()->getLanguage();

        $sources = $state[MultiAuth::SOURCESID];
        foreach ($sources as $key => $source) {
            $sources[$key]['source64'] = base64_encode($sources[$key]['source']);
            if (isset($sources[$key]['text'][$language])) {
                $sources[$key]['text'] = $sources[$key]['text'][$language];
            } else {
                $sources[$key]['text'] = $sources[$key]['text'][$defaultLanguage];
            }

            if (isset($sources[$key]['help'][$language])) {
                $sources[$key]['help'] = $sources[$key]['help'][$language];
            } else {
                $sources[$key]['help'] = $sources[$key]['help'][$defaultLanguage];
            }
        }

        $baseurl = explode("/", Utils\HTTP::getBaseURL());
        $elements = array_slice($baseurl, 3 - count($baseurl), count($baseurl) - 4);
        $path = implode("/", $elements);

        $t->data['selfUrl'] = '/' . $path;
        $t->data['authstate'] = $authStateId;
        $t->data['sources'] = $sources;

        if ($as !== null) {
            $t->data['preferred'] = $as->getPreviousSource();
        } else {
            $t->data['preferred'] = null;
        }
        return $t;
    }
}
