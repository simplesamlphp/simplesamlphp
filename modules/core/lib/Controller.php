<?php

namespace SimpleSAML\Module\core;

use SimpleSAML\Auth;
use SimpleSAML\Auth\AuthenticationFactory;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class for the core module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\core
 */
class Controller
{
    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Auth\AuthenticationFactory */
    protected $factory;

    /** @var \SimpleSAML\Session */
    protected $session;

    /** @var array */
    protected $sources;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration and auth source configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration              $config The configuration to use by the controllers.
     * @param \SimpleSAML\Session                    $session The session to use by the controllers.
     * @param \SimpleSAML\Auth\AuthenticationFactory $factory A factory to instantiate \SimpleSAML\Auth\Simple objects.
     *
     * @throws \Exception
     */
    public function __construct(
        Configuration $config,
        Session $session,
        AuthenticationFactory $factory
    ) {
        $this->config = $config;
        $this->factory = $factory;
        $this->sources = $config::getOptionalConfig('authsources.php')->toArray();
        $this->session = $session;
    }


    /**
     * Show account information for a given authentication source.
     *
     * @param string $as The identifier of the authentication source.
     *
     * @return \SimpleSAML\XHTML\Template|RedirectResponse An HTML template or a redirection if we are not
     * authenticated.
     *
     * @throws \SimpleSAML\Error\Exception An exception in case the auth source specified is invalid.
     */
    public function account($as)
    {
        if (!array_key_exists($as, $this->sources)) {
            throw new Error\Exception('Invalid authentication source');
        }

        $auth = $this->factory->create($as);
        if (!$auth->isAuthenticated()) {
            // not authenticated, start auth with specified source
            return new RedirectResponse(Module::getModuleURL('core/login/' . urlencode($as)));
        }

        $attributes = $auth->getAttributes();
        
        $session = Session::getSessionFromRequest();

        $t = new Template($this->config, 'auth_status.twig', 'attributes');
        $l = $t->getLocalization();
        $l->addDomain($l->getLocaleDir(), 'attributes');
        $t->data['header'] = '{status:header_saml20_sp}';
        $t->data['attributes'] = $attributes;
        $t->data['nameid'] = !is_null($auth->getAuthData('saml:sp:NameID'))
            ? $auth->getAuthData('saml:sp:NameID')
            : false;
        $t->data['authData'] = $auth->getAuthDataArray();
        $t->data['trackid'] = $session->getTrackID();
        $t->data['logouturl'] = Module::getModuleURL('core/logout/' . urlencode($as));
        $t->data['remaining'] = $this->session->getAuthData($as, 'Expire') - time();
        $t->setStatusCode(200);

        return $t;
    }


    /**
     * Perform a login operation.
     *
     * This controller will either start a login operation (if that was requested, or if only one authentication
     * source is available), or show a template allowing the user to choose which auth source to use.
     *
     * @param Request $request The request that lead to this login operation.
     * @param string|null $as The name of the authentication source to use, if any. Optional.
     *
     * @return \SimpleSAML\XHTML\Template|\SimpleSAML\HTTP\RunnableResponse|RedirectResponse An HTML template, a
     * redirect or a "runnable" response.
     *
     * @throws \SimpleSAML\Error\Exception
     */
    public function login(Request $request, $as = null)
    {
        //delete admin
        if (isset($this->sources['admin'])) {
            unset($this->sources['admin']);
        }

        if (count($this->sources) === 1 && $as === null) { // we only have one source available
            $as = key($this->sources);
        }

        if ($as === null) { // no authentication source specified
            $t = new Template($this->config, 'core:login.twig');
            $t->data['loginurl'] = Utils\Auth::getAdminLoginURL();
            $t->data['sources'] = $this->sources;
            return $t;
        }

        // auth source defined, check if valid
        if (!array_key_exists($as, $this->sources)) {
            throw new Error\Exception('Invalid authentication source');
        }

        // at this point, we have a valid auth source selected, start auth
        $auth = $this->factory->create($as);
        $as = urlencode($as);

        if ($request->get(Auth\State::EXCEPTION_PARAM, false) !== false) {
            // This is just a simple example of an error

            /** @var array $state */
            $state = Auth\State::loadExceptionState();

            assert(array_key_exists(Auth\State::EXCEPTION_DATA, $state));
            $e = $state[Auth\State::EXCEPTION_DATA];

            throw $e;
        }

        if ($auth->isAuthenticated()) {
            return new RedirectResponse(Module::getModuleURL('core/account/' . $as));
        }

        // we're not logged in, start auth
        $url = Module::getModuleURL('core/login/' . $as);
        $params = array(
            'ErrorURL' => $url,
            'ReturnTo' => $url,
        );
        return new RunnableResponse([$auth, 'login'], [$params]);
    }


    /**
     * Log the user out of a given authentication source.
     *
     * @param string $as The name of the auth source.
     *
     * @return \SimpleSAML\HTTP\RunnableResponse A runnable response which will actually perform logout.
     *
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function logout($as)
    {
        $auth = new Auth\Simple($as);
        return new RunnableResponse([$auth, 'logout'], [$this->config->getBasePath() . 'logout.php']);
    }
}
