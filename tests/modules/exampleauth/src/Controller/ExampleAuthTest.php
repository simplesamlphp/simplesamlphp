<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\exampleauth\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\exampleauth\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "exampleauth" module.
 */
#[CoversClass(Controller\ExampleAuth::class)]
class ExampleAuthTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['exampleauth' => true],
            ],
            '[ARRAY]',
            'simplesaml',
        );

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test that accessing the authpage-endpoint without ReturnTo parameter throws an exception
     *
     * @return void
     */
    public function testAuthpageNoReturnTo(): void
    {
        $request = Request::create(
            '/authpage',
            'POST',
            ['NoReturnTo' => 'Limbo'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Missing ReturnTo parameter.');

        $c->authpage($request);
    }


    /**
     * Test that accessing the authpage-endpoint without a valid ReturnTo parameter throws an exception
     *
     * @return void
     */
    public function testAuthpageInvalidReturnTo(): void
    {
        $request = Request::create(
            '/authpage',
            'POST',
            ['ReturnTo' => 'SomeBogusValue'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Invalid ReturnTo URL for this example.');

        $c->authpage($request);
    }


    /**
     * Test that accessing the authpage-endpoint using GET-method show a login-screen
     *
     * @return void
     */
    public function testAuthpageGetMethod(): void
    {
        $request = Request::create(
            '/authpage',
            'POST',
            ['ReturnTo' => 'State=/'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });

        $response = $c->authpage($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the authpage-endpoint using POST-method and using the correct password triggers a redirect
     *
     * @return void
     */
    public function testAuthpagePostMethodCorrectPassword(): void
    {
        $this->markTestSkipped('Needs debugging');

        $request = Request::create(
            '/authpage',
            'POST',
            ['ReturnTo' => 'State=/', 'username' => 'student', 'password' => 'student'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });

        $response = $c->authpage($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(RunnableResponse::class, $response);
    }


    /**
     * Test that accessing the authpage-endpoint using POST-method and
     * an incorrect password shows the login-screen again
     *
     * @return void
     */
    public function testAuthpagePostMethodIncorrectPassword(): void
    {
        $request = Request::create(
            '/authpage',
            'POST',
            ['ReturnTo' => 'State=/', 'username' => 'user', 'password' => 'something stupid'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });

        $response = $c->authpage($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Template::class, $response);
    }


    /**
     * Test that accessing the resume-endpoint leads to a redirect
     *
     * @return void
     */
    public function testResume(): void
    {
        $request = Request::create(
            '/resume',
            'GET',
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);

        $response = $c->resume($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(RunnableResponse::class, $response);
    }


    /**
     * Test that accessing the redirecttest-endpoint leads to a redirect
     *
     * @return void
     */
    public function testRedirect(): void
    {
        $request = Request::create(
            '/redirecttest',
            'GET',
            ['StateId' => 'someState'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });

        $response = $c->redirecttest($request);
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(RunnableResponse::class, $response);
    }


    /**
     * Test that accessing the redirecttest-endpoint without StateId leads to an exception
     *
     * @return void
     */
    public function testRedirectMissingStateId(): void
    {
        $request = Request::create(
            '/redirecttest',
            'GET',
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing required StateId query parameter.');

        $c->redirecttest($request);
    }
}
