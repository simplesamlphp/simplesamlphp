<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\core\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use SimpleSAML\{Auth, Configuration, Error};
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\core\Controller;
use SimpleSAML\TestUtils\ClearStateTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "core" module.
 *
 * For now, this test extends ClearStateTestCase so that it doesn't interfere with other tests. Once every class has
 * been made PSR-7-aware, that won't be necessary any longer.
 *
 * @package SimpleSAML\Test
 */
#[CoversClass(Controller\Logout::class)]
class LogoutTest extends ClearStateTestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Configuration[] */
    protected array $loadedConfigs;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->config = Configuration::loadFromArray(
            [
                'baseurlpath' => 'https://example.org/simplesaml',
                'module.enable' => ['exampleauth' => true],
                'enable.saml20-idp' => true,
                'trusted.url.domains' => [],
            ],
            '[ARRAY]',
            'simplesaml',
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');
    }


    /**
     * Test basic operation of the logout controller.
     * @TODO check if the passed auth source is correctly used
     */
    public function testLogout(): void
    {
        $request = Request::create(
            '/logout',
            'GET',
        );

        $c = new Controller\Logout($this->config);

        $response = $c->logout($request, 'example-authsource');

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        /** @psalm-var array $callable */
        $callable = $response->getCallable();
        $this->assertInstanceOf(Auth\Simple::class, $callable[0]);
        $this->assertEquals('logout', $callable[1]);
    }


    public function testLogoutReturnToDisallowedUrlRejected(): void
    {
        $request = Request::create(
            '/logout/example-authsource',
            'GET',
            ['ReturnTo' => 'https://loeki.tv/asjemenou'],
        );
        $_SERVER['REQUEST_URI']  = 'https://example.com/simplesaml/module.php/core/logout/example-authsource';

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\Exception::class);
        $this->expectExceptionMessage('URL not allowed: https://loeki.tv/asjemenou');
        $response = $c->logout($request, 'example-authsource');
    }


    public function testLogoutReturnToAllowedUrl(): void
    {
        $request = Request::create(
            '/logout/example-authsource',
            'GET',
            ['ReturnTo' => 'https://example.org/something'],
        );
        $_SERVER['REQUEST_URI']  = 'https://example.com/simplesaml/module.php/core/logout/example-authsource';

        $c = new Controller\Logout($this->config);

        $response = $c->logout($request, 'example-authsource');
        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('https://example.org/something', $response->getArguments()[0]);
    }


    public function testLogoutIframeDoneUnknownEntityThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe-done',
            'GET',
            ['id' => 'someState'],
        );

        $c = new Controller\Logout($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return ['core:IdP' => 'saml2:something'];
            }
        });

        $this->expectException(Error\MetadataNotFound::class);
        $c->logoutIframeDone($request);
    }


    public function testLogoutIframeDoneWithoutStateThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe-done',
            'GET',
            ['id' => 'someState'],
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\NoState::class);
        $c->logoutIframeDone($request);
    }


    public function testLogoutIframeDoneWithoutIdThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe-done',
            'GET',
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\BadRequest::class);
        $c->logoutIframeDone($request);
    }


    public function testLogoutIframePostWithoutIdpThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe-post',
            'GET',
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\BadRequest::class);
        $c->logoutIframePost($request);
    }


    public function testLogoutIframePostUnknownEntityThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe-post',
            'GET',
            ['idp' => 'saml2:something'],
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\MetadataNotFound::class);
        $c->logoutIframePost($request);
    }


    public function testLogoutIframeWithoutIdThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe',
            'GET',
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\BadRequest::class);
        $c->logoutIframe($request);
    }


    public function testLogoutIframeWithUnknownTypeThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe',
            'GET',
            ['id' => 'abc123', 'type' => 'foobar'],
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\BadRequest::class);
        $c->logoutIframe($request);
    }


    public function testLogoutIframeUnknownEntityThrowsException(): void
    {
        $request = Request::create(
            '/logout-iframe-post',
            'GET',
            ['id' => 'abc123', 'type' => 'nojs'],
        );

        $c = new Controller\Logout($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return ['core:IdP' => 'saml2:something'];
            }
        });

        $this->expectException(Error\MetadataNotFound::class);
        $c->logoutIframe($request);
    }


    public function testResumeLogoutWithoutIdThrowsException(): void
    {
        $request = Request::create(
            '/logout-resume',
            'GET',
        );

        $c = new Controller\Logout($this->config);

        $this->expectException(Error\BadRequest::class);
        $c->resumeLogout($request);
    }


    public function testResumeLogoutWithUnknownEntityThrowsException(): void
    {
        $request = Request::create(
            '/logout-resume',
            'GET',
            ['id' => 'abc123'],
        );

        $c = new Controller\Logout($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return ['core:IdP' => 'saml2:something'];
            }
        });

        $this->expectException(Error\MetadataNotFound::class);
        $c->resumeLogout($request);
    }
}
