<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\multiauth\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth\Source;
use SimpleSAML\Auth\State;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\multiauth\Controller;
use SimpleSAML\Session;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

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
    public function testDiscovery(): void
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
                    ]
                ];
            }
        });

        $response = $c->discovery($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }
}
