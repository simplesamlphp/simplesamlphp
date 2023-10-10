<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\admin\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SimpleSAML\{Configuration, Session, Utils};
use SimpleSAML\Module\admin\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};

/**
 * Set of tests for the controllers in the "admin" module.
 *
 * @covers \SimpleSAML\Module\admin\Controller\Config
 * @package SimpleSAML\Test
 */
class ConfigTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    protected MockObject $utilsMock;

    /** @var \SimpleSAML\Session */
    protected Session $session;
    private MockObject $requestMock;
    private MockObject $responseMock;
    private MockObject $utilAuthMock;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // TODO move to mocks
        $this->config = new class (
            [
                'module.enable' => ['admin' => true],
                'secretsalt' => 'defaultsecretsalt',
                'admin.checkforupdates' => true
            ],
            '[ARRAY]'
        ) extends Configuration
        {
            public function getVersion(): string
            {
                return '1.14.7';
            }
        };

        // Dirty hack, but Session relies on config being actually loaded
        $this->config::setPreloadedConfig(
            Configuration::loadFromArray([], '[ARRAY]', 'simplesaml'),
            'config.php',
            'simplesaml'
        );

        $this->utilsMock = $this->createMock(Utils::class);

        $session = $this->createMock(Session::class);
        $session->method('getData')->willReturn(['tag_name' => 'v1.18.7', 'html_url' => 'https://example.org']);

        /** @var \SimpleSAML\Session $session */
        $this->session = $session;

        $this->requestMock = $this->createMock(Request::class);
        $this->responseMock = $this->createMock(Response::class);

        $this->utilAuthMock = $this->createMock(Utils\Auth::class);
    }

    protected function mocked(): Controller\Config
    {
        return new Controller\Config($this->config, $this->session, $this->utilsMock);
    }

    /**
     */
    public function testDiagnostics(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/diagnostics';
        $request = Request::create(
            '/diagnostics',
            'GET'
        );

        $c = new Controller\Config($this->config, $this->session, $this->utilsMock);
        $response = $c->diagnostics($request);

        $this->assertTrue($response->isSuccessful());
    }

    public function testDiagnosticsRequireAdmin(): void
    {
        $this->responseMock->method('isRedirection')->willReturn(true);
        $this->utilAuthMock->method('requireAdmin')->willReturn($this->responseMock);
        $this->utilsMock->method('auth')->willReturn($this->utilAuthMock);

        $response = $this->mocked()->diagnostics($this->requestMock);

        $this->assertTrue($response->isRedirection());
    }



    /**
     */
    public function testMain(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin';
        $request = Request::create(
            '/',
            'GET'
        );

        $c = new Controller\Config($this->config, $this->session, $this->utilsMock);
        $response = $c->main($request);

        $this->assertTrue($response->isSuccessful());
    }


    /**
     */
    public function testPhpinfo(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/phpinfo';
        $request = Request::create(
            '/phpinfo',
            'GET'
        );

        $c = new Controller\Config($this->config, $this->session, $this->utilsMock);
        $response = $c->phpinfo($request);

        $this->assertTrue($response->isSuccessful());
    }
}
