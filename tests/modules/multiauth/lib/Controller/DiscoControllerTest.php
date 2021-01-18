<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\multiauth\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Source;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\multiauth\Auth\Source\MultiAuth;
use SimpleSAML\Module\multiauth\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "multiauth" module.
 *
 * @package SimpleSAML\Test
 */
class DiscoControllerTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var \SimpleSAML\Session */
    protected $session;


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

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'admin' => ['core:AdminPassword'],
                    'admin2' => ['core:AdminPassword'],
                    'multi' => [
                        'multiauth:MultiAuth',
                        'sources' => [
                            'admin',
                            'admin2'
                        ]
                    ],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );
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
            ['AuthState' => 'someState']
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
                        'source1' => ['source' => 'admin', 'help' => ['en' => 'help'], 'text' => ['en' => 'text']],
                        'source2' => ['source' => 'test', 'help' => ['en' => 'help'], 'text' => ['en' => 'text']]
                    ]
                ];
            }
        });

        $c->setAuthSource(new class () extends MultiAuth {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?Source
            {
                return new static();
            }
        });

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
            ['AuthState' => 'someState']
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
                        'source1' => ['source' => 'admin', 'help' => ['en' => 'help'], 'text' => ['en' => 'text']],
                        'source2' => ['source' => 'test', 'help' => ['en' => 'help'], 'text' => ['en' => 'text']]
                    ]
                ];
            }
        });

        $c->setAuthSource(new class () extends MultiAuth {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?Source
            {
                return new static();
            }
        });

        $response = $c->discovery($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     * Test that a valid requests results in a RunnableResponse
     * @return void
     */
    public function testDiscoveryDelegateAuth1(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => 'someState']
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
                        'source1' => ['source' => 'admin', 'help' => ['en' => 'help'], 'text' => ['nl' => 'text']],
                        'source2' => ['source' => 'test', 'text' => ['en' => 'text'], 'help' => ['nl' => 'help']]
                    ]
                ];
            }
        });

        $c->setAuthSource(new class () extends MultiAuth {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?Source
            {
                return new static();
            }
        });

        $response = $c->discovery($request);

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     * Test that a valid requests results in a RunnableResponse
     * @return void
     */
    public function testDiscoveryDelegateAuth1WithPreviousSource(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => 'someState', 'source' => 'admin']
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
                        'source1' => ['source' => 'admin', 'help' => ['en' => 'help']],
                        'source2' => ['source' => 'test', 'text' => ['en' => 'text']]
                    ]
                ];
            }
        });

        $c->setAuthSource(new class () extends MultiAuth {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?Source
            {
                return new static();
            }
        });

        $response = $c->discovery($request);

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     * Test that a valid requests results in a RunnableResponse
     * @return void
     */
    public function testDiscoveryDelegateAuth2(): void
    {
        $request = Request::create(
            '/discovery',
            'GET',
            ['AuthState' => 'someState', 'src-YWRtaW4=' => 'admin']
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
                        'source1' => ['source' => 'admin', 'help' => ['en' => 'help'], 'text' => ['en' => 'text']],
                        'source2' => ['source' => 'test', 'text' => ['en' => 'text'], 'help' => ['en' => 'help']]
                    ]
                ];
            }
        });

        $c->setAuthSource(new class () extends MultiAuth {
            public function __construct()
            {
                // stub
            }

            public function authenticate(array &$state): void
            {
                // stub
            }

            public static function getById(string $authId, ?string $type = null): ?Source
            {
                return new static();
            }
        });

        $response = $c->discovery($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->isSuccessful());
    }
}
