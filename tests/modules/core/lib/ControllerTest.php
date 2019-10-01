<?php

namespace SimpleSAML\Test\Module\core;

use SimpleSAML\Auth\Simple;
use SimpleSAML\Auth\AuthenticationFactory;
use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Locale\Localization;
use SimpleSAML\Module\core\Controller;
use SimpleSAML\Session;
use SimpleSAML\Test\Utils\ClearStateTestCase;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * For now, this test extends ClearStateTestCase so that it doesn't interfere with other tests. Once every class has
 * been made PSR-7-aware, that won't be necessary any longer.
 *
 * @package SimpleSAML\Test
 */
class ControllerTest extends ClearStateTestCase
{
    /** @var array */
    protected $authSources;

    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Configuration[] */
    protected $loadedConfigs;

    /** @var \SimpleSAML\HTTP\Router */
    protected $router;


    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->authSources = [
            'admin' => [
                'core:adminPassword'
            ],
            'example-userpass' => [
                'exampleauth:UserPass',
                'username:password' => [
                   'uid' => ['test']
                ]
            ]
        ];
        $this->config = Configuration::loadFromArray(
            [
                'baseurlpath' => 'https://example.org/simplesaml',
                'module.enable' => ['exampleauth' => true],
                'usenewui' => true,
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that authentication is started immediately if we hit the login endpoint and there's only one non-admin
     * source configured.
     * @return void
     */
    public function testAutomaticLoginWhenOnlyOneSource()
    {
        $asConfig = Configuration::loadFromArray($this->authSources);
        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');

        $request = new Request();
        $session = Session::getSessionFromRequest();
        $factory = new AuthenticationFactory($this->config, $session);

        $c = new Controller($this->config, $session, $factory);
        /** @var \SimpleSAML\HTTP\RunnableResponse $response */
        $response = $c->login($request);

        $this->assertInstanceOf(RunnableResponse::class, $response);
        list($object, $method) = $response->getCallable();

        $this->assertInstanceOf(Simple::class, $object);
        $this->assertEquals('login', $method);
        $arguments = $response->getArguments();
        $this->assertArrayHasKey('ErrorURL', $arguments[0]);
        $this->assertArrayHasKey('ReturnTo', $arguments[0]);
    }


    /**
     * Test that the user can choose what auth source to use when there are multiple defined (admin excluded).
     * @return void
     */
    public function testMultipleAuthSources()
    {
        $_SERVER['REQUEST_URI'] = '/';
        $asConfig = Configuration::loadFromArray(
            array_merge(
                $this->authSources,
                [
                    'example-static' => [
                        'exampleauth:StaticSource',
                        'uid' => ['test']
                    ]
                ]
            )
        );

        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');
        $request = new Request();
        $session = Session::getSessionFromRequest();
        $factory = new AuthenticationFactory($this->config, $session);

        $c = new Controller($this->config, $session, $factory);
        /** @var \SimpleSAML\XHTML\Template $response */
        $response = $c->login($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertEquals('core:login.twig', $response->getTemplateName());
        $this->assertArrayHasKey('sources', $response->data);
        $this->assertArrayHasKey('example-userpass', $response->data['sources']);
        $this->assertArrayHasKey('example-static', $response->data['sources']);
    }


    /**
     * Test that specifying an invalid auth source while trying to login raises an exception.
     * @return void
     */
    public function testLoginWithInvalidAuthSource()
    {
        $asConfig = Configuration::loadFromArray($this->authSources);
        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');
        $request = new Request();
        $session = Session::getSessionFromRequest();
        $factory = new AuthenticationFactory($this->config, $session);
        $c = new Controller($this->config, $session, $factory);
        $this->expectException(Exception::class);
        $c->login($request, 'invalid-auth-source');
    }


    /**
     * Test that we get redirected to /account/authsource when accessing the login endpoint while being already
     * authenticated.
     * @return void
     */
    public function testLoginWhenAlreadyAuthenticated()
    {
        $asConfig = Configuration::loadFromArray($this->authSources);
        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');

        $session = Session::getSessionFromRequest();
        $session->setConfiguration($this->config);
        $class = new \ReflectionClass($session);

        $authData = $class->getProperty('authData');
        $authData->setAccessible(true);
        $authData->setValue($session, [
            'example-userpass' => [
                'exampleauth:UserPass',
                'Attributes' => ['uid' => ['test']],
                'Authority' => 'example-userpass',
                'AuthnInstant' => time(),
                'Expire' => time() + 8 * 60 * 60
            ]
        ]);

        $factory = new AuthenticationFactory($this->config, $session);
        $c = new Controller($this->config, $session, $factory);

        $request = new Request();
        /** @var \Symfony\Component\HttpFoundation\RedirectResponse $response */
        $response = $c->login($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(
            'https://example.org/simplesaml/module.php/core/account/example-userpass',
            $response->getTargetUrl()
        );
    }


    /**
     * Test that triggering the logout controller actually proceeds to log out from the specified source.
     * @return void
     */
    public function testLogout()
    {
        $asConfig = Configuration::loadFromArray($this->authSources);
        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');
        $session = Session::getSessionFromRequest();
        $factory = new AuthenticationFactory($this->config, $session);
        $c = new Controller($this->config, $session, $factory);
        $response = $c->logout('example-userpass');
        $this->assertInstanceOf(RunnableResponse::class, $response);
        list($object, $method) = $response->getCallable();
        $this->assertInstanceOf(Simple::class, $object);
        $this->assertEquals('logout', $method);
        $this->assertEquals('/simplesaml/logout.php', $response->getArguments()[0]);
    }


    /**
     * Test that accessing the "account" endpoint without being authenticated gets you redirected to the "login"
     * endpoint.
     * @return void
     */
    public function testNotAuthenticated()
    {
        $asConfig = Configuration::loadFromArray($this->authSources);
        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');
        $session = Session::getSessionFromRequest();
        $factory = new AuthenticationFactory($this->config, $session);
        $c = new Controller($this->config, $session, $factory);
        /** @var RedirectResponse $response */
        $response = $c->account('example-userpass');
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(
            'https://example.org/simplesaml/module.php/core/login/example-userpass',
            $response->getTargetUrl()
        );
    }


    /**
     * Test that we are presented with a regular page if we are authenticated and try to access the "account" endpoint.
     * @return void
     */
    public function testAuthenticated()
    {
        $asConfig = Configuration::loadFromArray($this->authSources);
        Configuration::setPreLoadedConfig($asConfig, 'authsources.php');
        $session = Session::getSessionFromRequest();
        $class = new \ReflectionClass($session);
        $authData = $class->getProperty('authData');
        $authData->setAccessible(true);
        $authData->setValue($session, [
            'example-userpass' => [
                'exampleauth:UserPass',
                'Attributes' => ['uid' => ['test']],
                'Authority' => 'example-userpass',
                'AuthnInstant' => time(),
                'Expire' => time() + 8 * 60 * 60
            ]
        ]);
        $factory = new AuthenticationFactory($this->config, $session);
        $c = new Controller($this->config, $session, $factory);
        /** @var \SimpleSAML\XHTML\Template $response */
        $response = $c->account('example-userpass');
        $this->assertInstanceOf(Template::class, $response);
        $this->assertEquals('auth_status.twig', $response->getTemplateName());
    }
}
