<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\saml\Controller;

use Exception;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Http\RunnableResponse;
use SimpleSAML\Module\saml\Controller;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Set of tests for the controllers in the "saml" module.
 *
 * @covers \SimpleSAML\Module\saml\Controller\ServiceProvider
 * @package SimpleSAML\Test
 */
class ServiceProviderTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;


    /**
     * Set up for each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = Configuration::loadFromArray(
            [
                'module.enable' => ['saml' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );
        Configuration::setPreLoadedConfig($this->config, 'config.php');

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'phpunit' => ['saml:SP'],
                    'fake' => ['core:AdminPassword'],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );
    }


    /**
     * Test that accessing the discoResponse-endpoint without AuthID leads to an exception
     *
     * @return void
     */
    public function testDiscoResponseMissingAuthId(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing AuthID to discovery service response handler');

        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with AuthID but without idpentityid results in an exception
     *
     * @return void
     */
    public function testWithAuthIdWithoutEntity(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123']
        );

        $c = new Controller\ServiceProvider($this->config);

        $this->expectException(Error\BadRequest::class);
        $this->expectExceptionMessage('Missing idpentityid to discovery service response handler');

        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with unknown authsource in state results in an exception
     *
     * @return void
     */
    public function testWithUnknownAuthSource(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123', 'idpentityid' => 'urn:idp:entity'],
        );

        $c = new Controller\ServiceProvider($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'unknown',
                ];
            }
        });

        $this->expectException(Exception::class);
        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with non-SP authsource in state results in an exception
     *
     * @return void
     */
    public function testWithNonSPAuthSource(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123', 'idpentityid' => 'urn:idp:entity'],
        );

        $c = new Controller\ServiceProvider($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'fake',
                ];
            }
        });

        $this->expectException(Error\Exception::class);
        $c->discoResponse($request);
    }


    /**
     * Test that accessing the discoResponse-endpoint with SP authsource in state results in a RunnableResponse
     *
     * @return void
     */
    public function testWithSPAuthSource(): void
    {
        $request = Request::create(
            '/discoResponse',
            'GET',
            ['AuthID' => 'abc123', 'idpentityid' => 'urn:idp:entity'],
        );

        $c = new Controller\ServiceProvider($this->config);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadState(string $id, string $stage, bool $allowMissing = false): ?array
            {
                return [
                    'saml:sp:AuthId' => 'phpunit',
                ];
            }
        });

        $result = $c->discoResponse($request);
        $this->assertInstanceOf(RunnableResponse::class, $result);
    }


    /**
     * Test that accessing the wrongAuthnContextClassRef-endpoint without AuthID leads to a Template
     *
     * @return void
     */
    public function testWrongAuthnContextClassRef(): void
    {
        $request = Request::create(
            '/wrongAuthnContextClassRef',
            'GET',
        );

        $c = new Controller\ServiceProvider($this->config);

        $result = $c->wrongAuthnContextClassRef($request);
        $this->assertInstanceOf(Template::class, $result);
    }
}
