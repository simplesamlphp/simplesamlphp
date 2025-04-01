<?php

declare(strict_types=1);

namespace SimpleSAML\Module\debugsp\Controller;

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class for the debugsp module.
 *
 * This class serves the 'Test authentication sources' views available in the module.
 *
 * @package SimpleSAML\Module\debugsp
 */
class Test
{
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
     * Create a page listing the SPs that can be tested
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string|null $as
     * @return \SimpleSAML\XHTML\Template|\SimpleSAML\HTTP\RunnableResponse
     */
    private function makeSPList(Request $request, ?string $as = null): Response
    {
        $t = new Template($this->config, 'debugsp:authsource_list.twig');
        $samlSpSources = Auth\Source::getSourcesOfType('saml:SP');
        $flattenedSources = [];
        foreach ($samlSpSources as $source) {
            $flattenedSources[] = $source->getAuthId();
        }

        $t->data = [
            'sources' => $flattenedSources,
        ];

        return $t;
    }

    /**
     * Display the list of available authsources.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string|null $as
     * @return \SimpleSAML\XHTML\Template|\SimpleSAML\HTTP\RunnableResponse
     */
    public function main(Request $request, ?string $as = null): Response
    {
        if (is_null($as)) {
            $t = $this->makeSPList($request, $as);
        } else {
            /** @psalm-suppress UndefinedClass */
            $authsource = new $this->authSimple($as);

            try {
                $authsource->getAuthSource();
            } catch (Error\AuthSource $e) {
                // no authsource, user might be probing to find non Source\SP?
                $t = $this->makeSPList($request, $as);
                return $t;
            }

            // make sure we are only talking about an SP
            if (! $authsource->getAuthSource() instanceof Module\saml\Auth\Source\SP) {
                $t = $this->makeSPList($request, $as);
                return $t;
            }

            if (!is_null($request->query->get('logout'))) {
                return new RunnableResponse([$authsource, 'logout'], [Module::getModuleURL('debugsp/logout')]);
            } elseif (!is_null($request->query->get(Auth\State::EXCEPTION_PARAM))) {
                // This is just a simple example of an error
                /** @var array $state */
                $state = $this->authState::loadExceptionState();
                Assert::keyExists($state, Auth\State::EXCEPTION_DATA);
                throw $state[Auth\State::EXCEPTION_DATA];
            }

            if (!$authsource->isAuthenticated()) {
                $url = Module::getModuleURL('debugsp/test/' . $as, []);
                $params = [
                    'ErrorURL' => $url,
                    'ReturnTo' => $url,
                    Auth\State::RESTART => $url,
                ];
                return new RunnableResponse([$authsource, 'login'], [$params]);
            }

            $attributes = $authsource->getAttributes();
            $authData = $authsource->getAuthDataArray();
            $nameId = $authsource->getAuthData('saml:sp:NameID') ?? false;

            $httpUtils = new Utils\HTTP();
            $t = new Template($this->config, 'debugsp:status.twig');
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

        return $t;
    }


    /**
     * Page to show after logout completed
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \SimpleSAML\XHTML\Template
     */
    public function logout(Request $request): Template
    {
        return new Template($this->config, 'debugsp:logout.twig');
    }
}
