<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Module\admin\Controller;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\admin\Controller\Test as TestController;
use SimpleSAML\SAML2\XML\saml\NameID;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set of tests for the controllers in the "admin" module.
 *
 * @covers \SimpleSAML\Module\admin\Controller\Test
 * @package SimpleSAML\Test
 */
class TestTest extends TestCase
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;

    /** @var \SimpleSAML\Utils\Auth */
    protected Utils\Auth $authUtils;

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
                'module.enable' => ['admin' => true],
            ],
            '[ARRAY]',
            'simplesaml'
        );

        $this->authUtils = new class () extends Utils\Auth {
            public function requireAdmin(): void
            {
                // stub
            }
        };

        $this->session = Session::getSessionFromRequest();

        Configuration::setPreLoadedConfig(
            Configuration::loadFromArray(
                [
                    'admin' => ['core:AdminPassword'],
                ],
                '[ARRAY]',
                'simplesaml'
            ),
            'authsources.php',
            'simplesaml'
        );
    }


    /**
     */
    public function testMainWithoutAuthSource(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/test';
        $request = Request::create(
            '/test',
            'GET'
        );

        $c = new TestController($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $response = $c->main($request);

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     */
    public function testMainWithAuthSourceAndLogout(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/test';
        $request = Request::create(
            '/test',
            'GET',
            ['logout' => 'notnull']
        );

        $c = new TestController($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $c->setAuthSimple(new class ('admin') extends Auth\Simple {
            public function logout($params = null): void
            {
                // stub
            }
        });

        $response = $c->main($request, 'admin');

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     */
    public function testMainWithAuthSourceAndException(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/test';
        $request = Request::create(
            '/test',
            'GET',
            [Auth\State::EXCEPTION_PARAM => 'someException']
        );

        $c = new TestController($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $c->setAuthState(new class () extends Auth\State {
            public static function loadExceptionState(?string $id = null): ?array
            {
                return [Auth\State::EXCEPTION_DATA => new Error\NoState()];
            }
        });

        $this->expectException(Error\NoState::class);
        $this->expectExceptionMessage('NOSTATE');
        $c->main($request, 'admin');
    }


    /**
     */
    public function testMainWithAuthSourceNotAuthenticated(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/test';
        $request = Request::create(
            '/test',
            'GET',
            ['as' => 'admin']
        );

        $c = new TestController($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $c->setAuthSimple(new class ('admin') extends Auth\Simple {
            public function isAuthenticated(): bool
            {
                return false;
            }

            public function login(array $params = []): void
            {
                // stub
            }
        });

        $response = $c->main($request, 'admin');

        $this->assertInstanceOf(RunnableResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
    }


    /**
     */
    public function testMainWithAuthSourceAuthenticated(): void
    {
        $_SERVER['REQUEST_URI'] = '/module.php/admin/test';
        $request = Request::create(
            '/test',
            'GET'
        );

        $c = new TestController($this->config, $this->session);
        $c->setAuthUtils($this->authUtils);
        $c->setAuthSimple(new class ('admin') extends Auth\Simple {
            public function isAuthenticated(): bool
            {
                return true;
            }

            public function getAttributes(): array
            {
                $nameId = new NameID();
                $nameId->setValue('_b806c4f98188b42e48d3eb5444db613dbde463e2e8');
                $nameId->setSPProvidedID('some:entity');
                $nameId->setNameQualifier('some name qualifier');
                $nameId->setSPNameQualifier('some SP name qualifier');
                $nameId->setFormat('urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

                /** @psalm-suppress PossiblyNullPropertyFetch */
                return [
                    'urn:mace:dir:attribute-def:cn' => [
                        'Tim van Dijen'
                    ],
                    'urn:mace:dir:attribute-def:givenName' => [
                        'Tim'
                    ],
                    'urn:mace:dir:attribute-def:sn' => [
                        'van Dijen'
                    ],
                    'urn:mace:dir:attribute-def:displayName' => [
                        'Mr. T. van Dijen BSc'
                    ],
                    'urn:mace:dir:attribute-def:mail' => [
                        'tvdijen@hotmail.com',
                        'tvdijen@gmail.com'
                    ],
                    'urn:mace:dir:attribute-def:eduPersonTargetedID' => [
                        $nameId->toXML()->ownerDocument->childNodes
                    ],
                    'jpegPhoto' => [
                        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
                    ],
                    'nameId' => [
                        $nameId
                    ]
                ];
            }

            public function getAuthDataArray(): ?array
            {
                return [];
            }

            public function getAuthData(string $name)
            {
                $nameId = new NameID();
                $nameId->setValue('_b806c4f98188b42e48d3eb5444db613dbde463e2e8');
                $nameId->setSPProvidedID('some:entity');
                $nameId->setNameQualifier('some name qualifier');
                $nameId->setSPNameQualifier('some SP name qualifier');
                $nameId->setFormat('urn:oasis:names:tc:SAML:2.0:nameid-format:transient');

                return $nameId;
            }
        });

        $response = $c->main($request, 'admin');

        $this->assertInstanceOf(Template::class, $response);
        $this->assertTrue($response->isSuccessful());
    }
}
