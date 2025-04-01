<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Controller;

use SimpleSAML\{Auth, Configuration, Module, Session, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\Locale\Translate;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{Request, Response};

use function is_null;
use function time;
use function urlencode;

/**
 * Controller class for the admin module.
 *
 * This class serves the 'Test authentication sources' views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class Test
{
    /**
     * @var \SimpleSAML\Utils\Auth
     */
    protected Utils\Auth $authUtils;

    /**
     * @var \SimpleSAML\Auth\Simple|string
     * @psalm-var \SimpleSAML\Auth\Simple|class-string
     */
    protected $authSimple = Auth\Simple::class;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;

    /** @var \SimpleSAML\Module\admin\Controller\Menu */
    protected Menu $menu;


    /**
     * TestController constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     * @param \SimpleSAML\Session $session The current user session.
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
        $this->menu = new Menu();
        $this->authUtils = new Utils\Auth();
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
     * Inject the \SimpleSAML\Auth\Simple dependency.
     *
     * @param \SimpleSAML\Auth\Simple $authSimple
     */
    public function setAuthSimple(Auth\Simple $authSimple): void
    {
        $this->authSimple = $authSimple;
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
     * Display the list of available authsources.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string|null $as
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function main(Request $request, ?string $as = null): Response
    {
        $response = $this->authUtils->requireAdmin();
        if ($response instanceof Response) {
            return $response;
        }

        if (is_null($as)) {
            $t = new Template($this->config, 'admin:authsource_list.twig');
            $t->data = [
                'sources' => Auth\Source::getSources(),
            ];
        } else {
            /** @psalm-suppress UndefinedClass */
            $authsource = new $this->authSimple($as);

            if (!is_null($request->query->get('logout'))) {
                return $authsource->logout(Module::getModuleURL('admin/logout'));
            } elseif (!is_null($request->query->get(Auth\State::EXCEPTION_PARAM))) {
                // This is just a simple example of an error
                /** @var array $state */
                $state = $this->authState::loadExceptionState();
                Assert::keyExists($state, Auth\State::EXCEPTION_DATA);
                throw $state[Auth\State::EXCEPTION_DATA];
            }

            if (!$authsource->isAuthenticated()) {
                $url = Module::getModuleURL('admin/test/' . $as, []);
                $params = [
                    'ErrorURL' => $url,
                    'ReturnTo' => $url,
                    Auth\State::RESTART => $url,
                ];
                return $authsource->login($params);
            }

            $attributes = $authsource->getAttributes();
            $authData = $authsource->getAuthDataArray();
            $nameId = $authsource->getAuthData('saml:sp:NameID') ?? false;
            $nameId = $nameId ? $nameId->toArray() : $nameId;

            $httpUtils = new Utils\HTTP();
            $t = new Template($this->config, 'admin:status.twig');
            $l = $t->getLocalization();
            $l->addAttributeDomains();
            $t->data = [
                'attributes' => $attributes,
                'authData' => $authData,
                'remaining' => isset($authData['Expire']) ? $authData['Expire'] - time() : null,
                'nameid' => $nameId,
                'logouturl' => $httpUtils->getSelfURLNoQuery() . '?as=' . urlencode($as) . '&logout',
            ];
        }

        $this->menu->addOption('logout', $this->authUtils->getAdminLogoutURL(), Translate::noop('Log out'));
        return $this->menu->insert($t);
    }


    /**
     * Page to show after logout completed
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template
     */
    public function logout(/** @scrutinizer ignore-unused */Request $request): Template
    {
        return new Template($this->config, 'admin:logout.twig');
    }
}
