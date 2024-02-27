<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\multiauth\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\{Configuration, Error, Session};
use SimpleSAML\Auth\{Source, State};
use SimpleSAML\Module\multiauth\Auth\Source\MultiAuth;
use SimpleSAML\Module\multiauth\Controller;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};

/**
 * Set of tests for the controllers in the "multiauth" module.
 *
 * @package SimpleSAML\Test
 */
class DiscoControllerTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Session */
    protected Session $session;

    /** @var \SimpleSAML\Auth\Source */
    protected Source $authSource;


    /**
     * Set up for each test.
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['multiauth' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php', 'simplesaml');

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'admin' => ['core:AdminPassword'],
                    'admin2' => ['core:AdminPassword'],
                    'multi' => [
                        'multiauth:MultiAuth',
                        'sources' => [
                            'admin' => [],
                            'admin2' => [],
                        ]
                    ],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );

        $this->authSource = new class () extends MultiAuth {
            public function __construct()
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?Source
            {
                return new static();
            }
        };
    }


    /**
     * Test that a missing AuthState results in a BadRequest-error
     * @return void
     * @throws \SimpleSAML\Error\BadRequest
     */
    public function testDiscoveryMissingState(): void
    {
        $request = Request::create(
            '/discovery',
            'GET'
        );

        $c = new Controller\DiscoController($this->config, $this->session);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing AuthState parameter.');

        $c->discovery($request);
    }


    /**
     * Test that a valid requests results in a Twig template
     * @return void
     */
    public function testDiscoveryFallthru(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => '_abc123']
        );

        $c = new Controller\DiscoController($this->config, $this->session);

        $c->setAuthState(new class () extends State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'LogoutState' => [
                        'multiauth:discovery' => 'foo'
                    ],
                    MultiAuth::SOURCESID => [
                        'admin' => ['help' => ['en' => 'help'], 'text' => ['en' => 'text']],
                        'admin2' => ['help' => ['en' => 'help'], 'text' => ['en' => 'text']]
                    ]
                ];
            }
        });

        $c->setAuthSource($this->authSource);

        $response = $c->discovery($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     * Test that a valid requests results in a Twig template
     * @return void
     */
    public function testDiscoveryFallthruWithSource(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => '_abc123']
        );

        $c = new Controller\DiscoController($this->config, $this->session);

        $c->setAuthState(new class () extends State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'LogoutState' => [
                        'multiauth:discovery' => 'foo'
                    ],
                    '\SimpleSAML\Auth\Source.id' => 'multi',
                    MultiAuth::SOURCESID => [
                        'admin' => ['help' => ['en' => 'help'], 'text' => ['en' => 'text']],
                        'admin2' => ['help' => ['en' => 'help'], 'text' => ['en' => 'text']]
                    ]
                ];
            }
        });

        $c->setAuthSource($this->authSource);

        $response = $c->discovery($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     * Test that a valid requests results in a RedirectResponse
     * @return void
     */
    public function testDiscoveryDelegateAuth1(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => '_abc123']
        );

        $c = new Controller\DiscoController($this->config, $this->session);

        $c->setAuthState(new class () extends State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'LogoutState' => [
                        'multiauth:discovery' => 'foo'
                    ],
                    'multiauth:preselect' => 'admin',
                    '\SimpleSAML\Auth\Source.id' => 'multi',
                    MultiAuth::AUTHID => 'bar',
                    MultiAuth::SOURCESID => [
                        'admin' => ['help' => ['en' => 'help'], 'text' => ['nl' => 'text']],
                        'admin2' => ['text' => ['en' => 'text'], 'help' => ['nl' => 'help']]
                    ]
                ];
            }
        });

        $c->setAuthSource($this->authSource);

        $response = $c->discovery($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
    }


    /**
     * Test that a valid request results in a RedirectResponse
     * @return void
     */
    public function testDiscoveryDelegateAuth1WithPreviousSource(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => '_abc123', 'source' => 'admin']
        );

        $c = new Controller\DiscoController($this->config, $this->session);

        $c->setAuthState(new class () extends State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'LogoutState' => [
                        'multiauth:discovery' => 'foo'
                    ],
                    'multiauth:preselect' => 'admin',
                    '\SimpleSAML\Auth\Source.id' => 'multi',
                    MultiAuth::AUTHID => 'bar',
                    MultiAuth::SOURCESID => [
                        'admin' => ['help' => ['en' => 'help']],
                        'admin2' => ['text' => ['en' => 'text']]
                    ]
                ];
            }
        });

        $c->setAuthSource($this->authSource);

        $response = $c->discovery($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertTrue($response->isRedirection());
    }


    /**
     * Test that a valid request results in a RedirectResponse
     * @return void
     */
    public function testDiscoveryDelegateAuth2(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => '_abc123', 'sourceChoice[admin]' => 'something admin']
        );

        $c = new Controller\DiscoController($this->config, $this->session);

        $c->setAuthState(new class () extends State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'LogoutState' => [
                        'multiauth:discovery' => 'foo'
                    ],
                    MultiAuth::AUTHID => 'bar',
                    MultiAuth::SOURCESID => [
                        'admin' => ['help' => ['en' => 'help'], 'text' => ['en' => 'text']],
                        'admin2' => ['text' => ['en' => 'text'], 'help' => ['en' => 'help']]
                    ]
                ];
            }
        });

        $c->setAuthSource($this->authSource);

        $response = $c->discovery($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }
}
