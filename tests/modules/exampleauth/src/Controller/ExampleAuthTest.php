<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\exampleauth\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SimpleSAML\{Auth, Configuration, Error, Session};
use SimpleSAML\Module\exampleauth\Controller;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request};

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
            'simplesaml'
        );

        Configuration::setPreLoadedConfig($this->config, 'config.php');

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'external-example' => ['exampleauth:External'],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );

        $this->session = Session::getSessionFromRequest();
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
            ['ReturnTo' => '/SomeBogusValue'],
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Invalid ReturnTo URL for this example.');

        $c->authpage($request);
    }


    /**
     * Test that accessing the authpage-endpoint without ReturnTo parameter
     *
     * @return void
     */
    public function testAuthpageMissingReturnTo(): void
    {
        $request = Request::create(
            '/authpage',
            'POST',
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [];
            }
        });

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('Missing ReturnTo parameter.');
        $c->authpage($request);
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
        $this->assertInstanceOf(Response::class, $response);
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
            ['ReturnTo' => '/State=/', 'username' => 'user', 'password' => 'something stupid'],
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
    public function testResume(): void
    {
        $_SESSION['uid'] = 'phpunit';
        $_SESSION['name'] = 'John Doe';
        $_SESSION['mail'] = 'JohnDoe@example.org';
        $_SESSION['type'] = 'member';

        $request = Request::create(
            '/resume',
            'GET',
            ['AuthState' => 'someState']
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'exampleauth:AuthID' => 'external-example',
                    'SimpleSAML\Module\exampleauth\Auth\Source\External.AuthId' => 'example-external',
                    'LoginCompletedHandler' => [Auth\Source::class, 'loginCompleted'],
                    '\SimpleSAML\Auth\Source.Return' => 'https://example.org',
                    '\SimpleSAML\Auth\Source.id' => 'phpunit',
                ];
            }
        });

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(Response::class, $response);
    }
     */


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
            ['AuthState' => '_abc123']
        );

        $c = new Controller\ExampleAuth($this->config, $this->session);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'ReturnURL' => 'https://example.org/phpunit',
                    Auth\ProcessingChain::FILTERS_INDEX => [],
                ];
            }
        });

        $response = $c->redirecttest($request);
        $this->assertTrue($response->isRedirection());
        $this->assertInstanceOf(RedirectResponse::class, $response);
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
        $this->expectExceptionMessage('Missing required AuthState query parameter.');

        $c->redirecttest($request);
    }
}
